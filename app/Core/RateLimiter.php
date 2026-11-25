<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? dirname(__DIR__, 2) . '/storage/ratelimit';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0775, true);
        }
    }

    /**
     * Atomically checks if the key is within the allowed attempts and increments the counter.
     * Fails closed (returns allowed = false) if file/lock operations fail or data is corrupted.
     *
     * @return array{allowed: bool, attempts: int, remaining_seconds: int}
     */
    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 900): array
    {
        $file = $this->filePath($key);
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return ['allowed' => false, 'attempts' => $maxAttempts, 'remaining_seconds' => $decaySeconds];
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return ['allowed' => false, 'attempts' => $maxAttempts, 'remaining_seconds' => $decaySeconds];
        }

        try {
            $parsed = $this->readLockedHandle($fp, $decaySeconds);
            if ($parsed === null) {
                return ['allowed' => false, 'attempts' => $maxAttempts, 'remaining_seconds' => $decaySeconds];
            }

            $now = time();
            if ($parsed['attempts'] >= $maxAttempts && $parsed['reset_at'] > $now) {
                return [
                    'allowed' => false,
                    'attempts' => $parsed['attempts'],
                    'remaining_seconds' => max(1, $parsed['reset_at'] - $now),
                ];
            }

            $newData = [
                'attempts' => $parsed['attempts'] + 1,
                'reset_at' => ($parsed['attempts'] === 0 || $parsed['reset_at'] <= $now)
                    ? $now + $decaySeconds
                    : $parsed['reset_at'],
            ];

            if (!$this->writeLockedHandle($fp, $newData)) {
                return ['allowed' => false, 'attempts' => $maxAttempts, 'remaining_seconds' => $decaySeconds];
            }

            return [
                'allowed' => true,
                'attempts' => $newData['attempts'],
                'remaining_seconds' => 0,
            ];
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function tooManyAttempts(string $key, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        $data = $this->read($key, $decaySeconds);
        if ($data === null) {
            return true;
        }
        return $data['attempts'] >= $maxAttempts;
    }

    public function remainingSeconds(string $key, int $decaySeconds = 900): int
    {
        $data = $this->read($key, $decaySeconds);
        if ($data === null) {
            return $decaySeconds;
        }
        if ($data['attempts'] === 0) {
            return 0;
        }
        return max(0, $data['reset_at'] - time());
    }

    public function hit(string $key, int $decaySeconds = 900): void
    {
        $file = $this->filePath($key);
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }

        try {
            $parsed = $this->readLockedHandle($fp, $decaySeconds);
            if ($parsed === null) {
                return;
            }

            $now = time();
            $newData = [
                'attempts' => $parsed['attempts'] + 1,
                'reset_at' => ($parsed['attempts'] === 0 || $parsed['reset_at'] <= $now)
                    ? $now + $decaySeconds
                    : $parsed['reset_at'],
            ];

            $this->writeLockedHandle($fp, $newData);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function clear(string $key): void
    {
        $file = $this->filePath($key);
        if (!is_file($file)) {
            return;
        }

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return;
        }

        if (flock($fp, LOCK_EX)) {
            try {
                $this->writeLockedHandle($fp, ['attempts' => 0, 'reset_at' => 0]);
            } finally {
                flock($fp, LOCK_UN);
            }
        }
        fclose($fp);
    }

    /**
     * @return array{attempts: int, reset_at: int}|null Null indicates read/lock/parse error (fail-closed).
     */
    private function read(string $key, int $decaySeconds): ?array
    {
        $file = $this->filePath($key);
        if (!is_file($file)) {
            return ['attempts' => 0, 'reset_at' => time() + $decaySeconds];
        }

        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return null;
        }

        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return null;
        }

        try {
            return $this->readLockedHandle($fp, $decaySeconds);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * @param resource $fp
     * @return array{attempts: int, reset_at: int}|null
     */
    private function readLockedHandle($fp, int $decaySeconds): ?array
    {
        rewind($fp);
        $raw = stream_get_contents($fp);
        if ($raw === false) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            $stat = fstat($fp);
            // Only treat as fresh if the file is genuinely 0 bytes (newly created)
            if ($stat !== false && $stat['size'] === 0) {
                return ['attempts' => 0, 'reset_at' => time() + $decaySeconds];
            }
            return null;
        }

        $data = json_decode($trimmed, true);
        if (!is_array($data) || !isset($data['attempts'], $data['reset_at'])) {
            return null;
        }

        $now = time();
        $resetAt = (int) $data['reset_at'];
        if ($resetAt <= $now) {
            return ['attempts' => 0, 'reset_at' => $now + $decaySeconds];
        }

        return [
            'attempts' => max(0, (int) $data['attempts']),
            'reset_at' => $resetAt,
        ];
    }

    /**
     * @param resource $fp
     */
    private function writeLockedHandle($fp, array $data): bool
    {
        $encoded = json_encode($data);
        if ($encoded === false) {
            return false;
        }
        if (!ftruncate($fp, 0)) {
            return false;
        }
        if (!rewind($fp)) {
            return false;
        }
        $written = fwrite($fp, $encoded);
        fflush($fp);
        return $written !== false;
    }

    private function filePath(string $key): string
    {
        return $this->storageDir . '/' . hash('sha256', $key) . '.json';
    }
}
