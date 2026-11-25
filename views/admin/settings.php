<?php
use App\Core\Csrf;
use App\Core\Helpers;

$pageTitle = 'Pengaturan Akun Admin — Buku Tamu BDI Surabaya';
require dirname(__DIR__) . '/layouts/header.php';
?>

<header class="topbar">
  <div class="topbar-inner">
    <a href="/admin" class="brand">
      <div class="brand-logo-wrap">
        <img src="/assets/img/logo-bdi.png" alt="Logo BDI Surabaya" class="brand-logo">
      </div>
      <div>
        <div class="brand-title">Pengaturan Admin</div>
        <div class="brand-sub">BDI Surabaya</div>
      </div>
    </a>
    <div class="nav-actions">
      <a href="/admin" class="btn btn-outline btn-sm">&larr; Kembali ke Dashboard</a>
      <a href="/admin/logout" class="btn btn-danger-soft btn-sm">Keluar</a>
    </div>
  </div>
</header>

<main class="container" style="max-width: 580px; padding-top: 2.5rem;">
  <div class="glass-card panel-card animate-in">
    <h1 class="panel-title" style="font-size: 1.35rem;">Profil &amp; Keamanan Akun</h1>
    <p class="panel-subtitle">Ubah nama tampilan atau perbarui password administrator.</p>

    <?php if (!empty($flashSuccess)): ?>
      <div class="alert alert-success"><?= Helpers::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
      <div class="alert alert-error"><?= Helpers::e($flashError) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/settings">
      <?= Csrf::field() ?>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" value="<?= Helpers::e($adminData['username'] ?? '') ?>" disabled>
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="req">*</span></label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= Helpers::e($adminData['nama_lengkap'] ?? '') ?>" required>
      </div>

      <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 1.25rem 0;">
      <div style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 0.85rem;">
        Kosongkan kolom password jika tidak ingin mengganti password.
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="current_password">Password Saat Ini</label>
        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Masukkan password lama">
      </div>

      <div class="form-group" style="margin-bottom: 1rem;">
        <label class="form-label" for="new_password">Password Baru (min. 6 karakter)</label>
        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Masukkan password baru">
      </div>

      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password baru">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
        Simpan Perubahan
      </button>
    </form>
  </div>
</main>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
