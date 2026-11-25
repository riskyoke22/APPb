<?php
use App\Core\Csrf;
use App\Core\Helpers;

$pageTitle = 'Dashboard Administrator — Buku Tamu BDI Surabaya';
require dirname(__DIR__) . '/layouts/header.php';

$exportQuery = http_build_query(array_filter($filters));
$currentQuery = http_build_query(array_filter(array_merge($filters, ['page' => $page > 1 ? $page : null])));
?>

<header class="topbar">
  <div class="topbar-inner">
    <a href="/admin" class="brand">
      <div class="brand-logo-wrap">
        <img src="/assets/img/logo-bdi.png" alt="Logo BDI Surabaya" class="brand-logo">
      </div>
      <div>
        <div class="brand-title">Admin Buku Tamu</div>
        <div class="brand-sub">BDI Surabaya • <?= Helpers::e($adminUser['nama_lengkap'] ?? 'Admin') ?></div>
      </div>
    </a>

    <div class="nav-actions">
      <a href="/" target="_blank" class="btn btn-outline btn-sm">Halaman Tamu</a>
      <a href="/admin/settings" class="btn btn-soft btn-sm">Pengaturan Akun</a>
      <a href="/admin/logout" class="btn btn-danger-soft btn-sm">Keluar</a>
    </div>
  </div>
</header>

<main class="container">
  <?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success animate-in"><?= Helpers::e($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if (!empty($flashError)): ?>
    <div class="alert alert-error animate-in"><?= Helpers::e($flashError) ?></div>
  <?php endif; ?>

  <div class="hero-header animate-in" style="margin-bottom: 1.25rem;">
    <div>
      <h1 class="hero-title" style="font-size: 1.7rem;">Dashboard Pengunjung</h1>
      <p class="hero-desc" style="font-size: 0.9rem;">Pantau statistik kunjungan, bukti foto wajah, dan ekspor laporan secara real-time.</p>
    </div>
    <div class="nav-actions no-print">
      <a href="/admin/export<?= $exportQuery ? '?' . Helpers::e($exportQuery) : '' ?>" class="btn btn-primary btn-sm">
        Export CSV / Excel
      </a>
      <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">
        Cetak Laporan
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <section class="kpi-grid animate-in delay-1">
    <div class="glass-card kpi-card">
      <div class="kpi-label">Tamu Hari Ini</div>
      <div class="kpi-value" id="kpiToday" data-count="<?= (int) $stats['today'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--brand-blue); font-weight: 600;">Check-in hari ini</div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Sedang Berkunjung</div>
      <div class="kpi-value" id="kpiActive" style="color: var(--brand-teal);" data-count="<?= (int) $stats['active'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--brand-teal); font-weight: 600;">Belum check-out</div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Tamu Bulan Ini</div>
      <div class="kpi-value" id="kpiMonth" data-count="<?= (int) $stats['month'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;"><?= Helpers::e(Helpers::formatBulanTahunIndo()) ?></div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Total Tercatat</div>
      <div class="kpi-value" id="kpiTotal" data-count="<?= (int) $stats['total'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">Seluruh riwayat</div>
    </div>
  </section>

  <!-- Charts -->
  <section class="charts-grid animate-in delay-2 no-print">
    <div class="glass-card panel-card">
      <h2 class="panel-title">Tren Kunjungan 7 Hari Terakhir</h2>
      <div style="height: 240px; margin-top: 0.75rem;">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
    <div class="glass-card panel-card">
      <h2 class="panel-title">Distribusi Unit Tujuan</h2>
      <div style="height: 240px; margin-top: 0.75rem;">
        <canvas id="tujuanChart"></canvas>
      </div>
    </div>
  </section>

  <!-- Filter & Data Table -->
  <section class="glass-card panel-card animate-in delay-3">
    <div class="live-sync-bar no-print">
      <div class="live-sync-pill" id="liveSyncBadge">
        <span class="pulse-dot" id="liveSyncDot"></span>
        <span id="liveSyncText">Live Sync Aktif</span>
      </div>
      <div class="live-sync-actions">
        <button type="button" id="btnEnableDesktopNotif" class="btn btn-soft btn-sm">
          Aktifkan Notifikasi Desktop
        </button>
        <button type="button" id="btnToggleLiveSync" class="btn btn-outline btn-sm">
          Jeda Sync
        </button>
      </div>
    </div>

    <form method="GET" action="/admin" class="no-print admin-filter-bar">
      <div class="form-group">
        <label class="form-label">Cari Tamu</label>
        <input type="text" name="q" value="<?= Helpers::e($filters['search']) ?>" class="form-control" placeholder="Nama, instansi, HP, kode...">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">Semua Status</option>
          <option value="berkunjung" <?= $filters['status'] === 'berkunjung' ? 'selected' : '' ?>>Berkunjung</option>
          <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tujuan</label>
        <select name="tujuan" class="form-control">
          <option value="">Semua Tujuan</option>
          <?php foreach ($tujuanList as $t): ?>
            <option value="<?= Helpers::e($t) ?>" <?= $filters['tujuan'] === $t ? 'selected' : '' ?>><?= Helpers::e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Dari Tanggal</label>
        <input type="date" name="from" value="<?= Helpers::e($filters['date_from']) ?>" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Sampai</label>
        <input type="date" name="to" value="<?= Helpers::e($filters['date_to']) ?>" class="form-control">
      </div>
      <div style="display: flex; gap: 0.4rem;">
        <button type="submit" class="btn btn-primary btn-sm" style="height: 43px;">Filter</button>
        <a href="/admin" class="btn btn-outline btn-sm" style="height: 43px;">Reset</a>
      </div>
    </form>

    <?php
      $baseFilterParams = array_filter([
          'q' => $filters['search'],
          'tujuan' => $filters['tujuan'],
          'from' => $filters['date_from'],
          'to' => $filters['date_to'],
      ]);
      $allTabQs = http_build_query($baseFilterParams);
      $activeTabQs = http_build_query(array_merge($baseFilterParams, ['status' => 'berkunjung']));
    ?>
    <div class="quick-filter-pills no-print" role="tablist" aria-label="Filter cepat status tamu">
      <a
        href="/admin<?= $allTabQs ? '?' . Helpers::e($allTabQs) : '' ?>"
        class="quick-pill <?= $filters['status'] === '' ? 'active' : '' ?>"
      >
        Semua Tamu
      </a>
      <a
        href="/admin?<?= Helpers::e($activeTabQs) ?>"
        class="quick-pill <?= $filters['status'] === 'berkunjung' ? 'active' : '' ?>"
      >
        Sedang Berkunjung
        <span class="quick-pill-badge" id="quickTabActiveCount"><?= (int) $stats['active'] ?></span>
      </a>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Pengunjung</th>
            <th>Instansi &amp; Kontak</th>
            <th>Tujuan &amp; Keperluan</th>
            <th>Waktu</th>
            <th>Status</th>
            <th class="no-print">Aksi</th>
          </tr>
        </thead>
        <tbody id="guestTableBody">
          <?php require dirname(__DIR__) . '/admin/_guest_rows.php'; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php
        $paginationBase = array_filter([
            'q' => $filters['search'],
            'status' => $filters['status'],
            'tujuan' => $filters['tujuan'],
            'from' => $filters['date_from'],
            'to' => $filters['date_to'],
        ]);
      ?>
      <nav class="pagination no-print" aria-label="Navigasi halaman tabel tamu">
        <?php if ($page > 1): ?>
          <?php $prevQs = http_build_query(array_merge($paginationBase, ['page' => $page - 1])); ?>
          <a href="/admin?<?= Helpers::e($prevQs) ?>" class="page-link">&lsaquo; Sebelumnya</a>
        <?php else: ?>
          <span class="page-link is-disabled" aria-disabled="true">&lsaquo; Sebelumnya</span>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php $qs = http_build_query(array_merge($paginationBase, ['page' => $p])); ?>
          <a href="/admin?<?= Helpers::e($qs) ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <?php $nextQs = http_build_query(array_merge($paginationBase, ['page' => $page + 1])); ?>
          <a href="/admin?<?= Helpers::e($nextQs) ?>" class="page-link">Berikutnya &rsaquo;</a>
        <?php else: ?>
          <span class="page-link is-disabled" aria-disabled="true">Berikutnya &rsaquo;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
</main>

<!-- Photo Lightbox Modal -->
<div id="photoLightbox" class="modal-backdrop" role="dialog" aria-modal="true">
  <div class="modal-card" style="max-width: 520px; text-align: center;">
    <img id="lbImage" src="" alt="Bukti Foto Tamu" style="width: 100%; max-height: 360px; object-fit: cover; border-radius: 16px;">
    <h3 id="lbName" style="margin: 1rem 0 0.25rem; font-weight: 800;"></h3>
    <div id="lbMeta" style="font-size: 0.84rem; color: var(--brand-blue); font-weight: 600;"></div>
    <p id="lbKeperluan" style="margin: 0.75rem 0 1.25rem; font-size: 0.9rem; color: var(--text-secondary); background: #f8fafc; padding: 0.8rem; border-radius: 12px;"></p>
    <button type="button" id="lbClose" class="btn btn-primary" style="width: 100%;">Tutup</button>
  </div>
</div>

<!-- Admin Action Confirmation Modal -->
<div id="adminConfirmModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
  <div class="modal-card confirm-modal-card">
    <div id="confirmIconWrap" class="confirm-icon-circle is-danger" aria-hidden="true">
      <svg id="confirmIconSvg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        <line x1="10" y1="11" x2="10" y2="17"></line>
        <line x1="14" y1="11" x2="14" y2="17"></line>
      </svg>
    </div>

    <h3 id="confirmTitle" class="confirm-title">Konfirmasi Hapus Data</h3>
    <p id="confirmDesc" class="confirm-desc">Data kunjungan beserta foto wajah tamu akan dihapus secara permanen dan tidak dapat dipulihkan.</p>

    <div class="confirm-preview-badge">
      <div id="confirmGuestName" class="confirm-preview-name">-</div>
      <div id="confirmGuestCode" class="confirm-preview-code">-</div>
    </div>

    <div class="confirm-actions">
      <button type="button" id="confirmCancelBtn" class="btn btn-outline">Batal</button>
      <button type="button" id="confirmSubmitBtn" class="btn btn-danger-gradient">Ya, Hapus Data</button>
    </div>
  </div>
</div>

<!-- Top-Right Live Guest Notification Toast -->
<div id="adminLiveToast" class="admin-live-toast no-print" role="status" aria-live="polite">
  <div class="live-toast-icon" aria-hidden="true">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
    </svg>
  </div>
  <div class="live-toast-body">
    <div class="live-toast-title">Tamu Baru Check-In</div>
    <div class="live-toast-name" id="liveToastName">-</div>
    <div class="live-toast-meta" id="liveToastMeta">-</div>
  </div>
  <button type="button" id="liveToastClose" class="live-toast-close" aria-label="Tutup notifikasi">&times;</button>
</div>

<script>
  window.BDI_CHART_DATA = <?= json_encode([
      'trend' => $stats['trend'],
      'tujuan' => $stats['tujuan_chart'],
  ], JSON_UNESCAPED_UNICODE) ?>;
  window.BDI_POLL_CONFIG = <?= json_encode([
      'latestId' => $latestGuest ? (int) $latestGuest['id'] : 0,
      'queryString' => $currentQuery,
  ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="/assets/js/admin.js?v=20261125-2"></script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
