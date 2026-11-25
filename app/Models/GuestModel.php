<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class GuestModel
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO guests (
                kode_kunjungan, nama_lengkap, instansi, no_hp, email,
                tujuan, bertemu_dengan, keperluan, foto_path, status,
                waktu_masuk, ip_address, user_agent
            ) VALUES (
                :kode_kunjungan, :nama_lengkap, :instansi, :no_hp, :email,
                :tujuan, :bertemu_dengan, :keperluan, :foto_path, 'berkunjung',
                NOW(), :ip_address, :user_agent
            )
        ");

        $stmt->execute([
            ':kode_kunjungan' => $data['kode_kunjungan'],
            ':nama_lengkap' => $data['nama_lengkap'],
            ':instansi' => $data['instansi'],
            ':no_hp' => $data['no_hp'],
            ':email' => $data['email'] ?: null,
            ':tujuan' => $data['tujuan'],
            ':bertemu_dengan' => $data['bertemu_dengan'] ?: null,
            ':keperluan' => $data['keperluan'],
            ':foto_path' => $data['foto_path'],
            ':ip_address' => $data['ip_address'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM guests WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActiveByCodeOrPhone(string $query): ?array
    {
        $q = trim($query);
        $normalizedDigits = \App\Core\Helpers::normalizePhoneDigits($q);
        $altIntlDigits = str_starts_with($normalizedDigits, '0')
            ? '62' . substr($normalizedDigits, 1)
            : $normalizedDigits;

        $stmt = $this->db->prepare("
            SELECT * FROM guests
            WHERE status = 'berkunjung'
              AND (
                kode_kunjungan = :kode
                OR no_hp = :hp
                OR (
                  :norm_local != ''
                  AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(no_hp, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') IN (:norm_local2, :norm_intl)
                )
              )
            ORDER BY waktu_masuk DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':kode' => strtoupper($q),
            ':hp' => $q,
            ':norm_local' => $normalizedDigits,
            ':norm_local2' => $normalizedDigits,
            ':norm_intl' => $altIntlDigits,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function checkout(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE guests
            SET status = 'selesai', waktu_keluar = NOW()
            WHERE id = :id AND status = 'berkunjung'
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM guests WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getPublicStats(): array
    {
        $today = (int) $this->db->query("
            SELECT COUNT(*) FROM guests WHERE DATE(waktu_masuk) = CURDATE()
        ")->fetchColumn();

        $active = (int) $this->db->query("
            SELECT COUNT(*) FROM guests WHERE status = 'berkunjung'
        ")->fetchColumn();

        return [
            'today' => $today,
            'active' => $active,
        ];
    }

    public function getDashboardStats(): array
    {
        $today = (int) $this->db->query("
            SELECT COUNT(*) FROM guests WHERE DATE(waktu_masuk) = CURDATE()
        ")->fetchColumn();

        $active = (int) $this->db->query("
            SELECT COUNT(*) FROM guests WHERE status = 'berkunjung'
        ")->fetchColumn();

        $month = (int) $this->db->query("
            SELECT COUNT(*) FROM guests
            WHERE YEAR(waktu_masuk) = YEAR(CURDATE()) AND MONTH(waktu_masuk) = MONTH(CURDATE())
        ")->fetchColumn();

        $total = (int) $this->db->query("SELECT COUNT(*) FROM guests")->fetchColumn();

        // Last 7 days trend
        $trendStmt = $this->db->query("
            SELECT DATE(waktu_masuk) AS tanggal, COUNT(*) AS jumlah
            FROM guests
            WHERE waktu_masuk >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(waktu_masuk)
            ORDER BY tanggal ASC
        ");
        $rawTrend = [];
        foreach ($trendStmt->fetchAll() as $row) {
            $rawTrend[$row['tanggal']] = (int) $row['jumlah'];
        }

        $trendLabels = [];
        $trendCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $trendLabels[] = date('d M', strtotime($d));
            $trendCounts[] = $rawTrend[$d] ?? 0;
        }

        // Top purposes
        $tujuanStmt = $this->db->query("
            SELECT tujuan, COUNT(*) AS jumlah
            FROM guests
            GROUP BY tujuan
            ORDER BY jumlah DESC
            LIMIT 6
        ");
        $tujuanData = $tujuanStmt->fetchAll();

        return [
            'today' => $today,
            'active' => $active,
            'month' => $month,
            'total' => $total,
            'trend' => [
                'labels' => $trendLabels,
                'counts' => $trendCounts,
            ],
            'tujuan_chart' => [
                'labels' => array_column($tujuanData, 'tujuan'),
                'counts' => array_map('intval', array_column($tujuanData, 'jumlah')),
            ],
        ];
    }

    public function listFiltered(array $filters, int $limit = 50, int $offset = 0): array
    {
        [$whereSql, $params] = $this->buildFilterWhere($filters);

        $sql = "SELECT * FROM guests {$whereSql} ORDER BY waktu_masuk DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countFiltered(array $filters): int
    {
        [$whereSql, $params] = $this->buildFilterWhere($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM guests {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllForExport(array $filters): array
    {
        [$whereSql, $params] = $this->buildFilterWhere($filters);
        $stmt = $this->db->prepare("SELECT * FROM guests {$whereSql} ORDER BY waktu_masuk DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function buildFilterWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(nama_lengkap LIKE :q1 OR instansi LIKE :q2 OR no_hp LIKE :q3 OR kode_kunjungan LIKE :q4 OR bertemu_dengan LIKE :q5)";
            $like = '%' . $filters['search'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['berkunjung', 'selesai'], true)) {
            $conditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['tujuan'])) {
            $conditions[] = "tujuan = :tujuan";
            $params[':tujuan'] = $filters['tujuan'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(waktu_masuk) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(waktu_masuk) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$whereSql, $params];
    }
}
