<?php
use App\Core\Csrf;
use App\Core\Helpers;

$pageTitle = 'Login Administrator — Buku Tamu BDI Surabaya';
require dirname(__DIR__) . '/layouts/header.php';
?>

<main class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
  <div class="glass-card panel-card animate-in" style="width: 100%; max-width: 430px; padding: 2rem;">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <img src="/assets/img/logo-bdi.png" alt="Logo BDI Surabaya" style="width: 68px; height: 68px; object-fit: contain; margin-bottom: 0.75rem;">
      <h1 class="display-heading" style="margin: 0; font-size: 1.55rem;">Admin Panel BDI Surabaya</h1>
      <p style="margin: 0.3rem 0 0; font-size: 0.88rem; color: var(--text-muted);">
        Masuk untuk mengelola data tamu &amp; laporan kehadiran
      </p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= Helpers::e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login">
      <?= Csrf::field() ?>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
      </div>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
        Masuk ke Dashboard
      </button>
    </form>

    <div style="margin-top: 1.25rem; text-align: center; font-size: 0.84rem; color: var(--text-muted);">
      <a href="/" style="color: var(--brand-blue); text-decoration: none; font-weight: 600;">&larr; Kembali ke Halaman Buku Tamu</a>
    </div>
  </div>
</main>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
