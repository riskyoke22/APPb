<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $effectiveMethod = $method === 'HEAD' ? 'GET' : $method;
        $handler = $this->routes[$effectiveMethod][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 - Tidak Ditemukan</title>';
            echo '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#0b132b;}';
            echo '.box{text-align:center;padding:2.5rem;background:#fff;border-radius:24px;border:1px solid rgba(180,131,39,.25);box-shadow:0 20px 50px -15px rgba(11,19,43,.12);max-width:420px;}';
            echo '.code{font-size:3.5rem;font-weight:800;margin:0;color:#1e3a8a;}.btn{display:inline-block;margin-top:1.25rem;padding:.75rem 1.5rem;background:linear-gradient(135deg,#0b132b,#1e3a8a);color:#fff;text-decoration:none;border-radius:12px;font-weight:600;}</style></head>';
            echo '<body><div class="box"><p class="code">404</p><p>Halaman yang Anda cari tidak ditemukan.</p><a class="btn" href="/">Kembali ke Beranda</a></div></body></html>';
            return;
        }

        $handler();
    }
}
