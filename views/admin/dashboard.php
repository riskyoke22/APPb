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
      <img src="/assets/img/bdi-logo.svg" alt="Logo BDI Surabaya" class="brand-logo">
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
      <div class="kpi-value" data-count="<?= (int) $stats['today'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--brand-blue); font-weight: 600;">Check-in hari ini</div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Sedang Berkunjung</div>
      <div class="kpi-value" style="color: var(--brand-teal);" data-count="<?= (int) $stats['active'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--brand-teal); font-weight: 600;">Belum check-out</div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Tamu Bulan Ini</div>
      <div class="kpi-value" data-count="<?= (int) $stats['month'] ?>">0</div>
      <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;"><?= Helpers::e(Helpers::formatBulanTahunIndo()) ?></div>
    </div>
    <div class="glass-card kpi-card">
      <div class="kpi-label">Total Tercatat</div>
      <div class="kpi-value" data-count="<?= (int) $stats['total'] ?>">0</div>
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
        <tbody>
          <?php if (empty($guestList)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                Belum ada data tamu yang sesuai filter.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($guestList as $g): ?>
              <?php $photoUrl = '/photo?f=' . urlencode($g['foto_path']); ?>
              <tr>
                <td>
                  <img
                    src="<?= Helpers::e($photoUrl) ?>"
                    alt="Foto <?= Helpers::e($g['nama_lengkap']) ?>"
                    class="avatar-thumb js-open-lightbox"
                    data-photo="<?= Helpers::e($photoUrl) ?>"
                    data-name="<?= Helpers::e($g['nama_lengkap']) ?>"
                    data-instansi="<?= Helpers::e($g['instansi']) ?>"
                    data-tujuan="<?= Helpers::e($g['tujuan']) ?>"
                    data-kode="<?= Helpers::e($g['kode_kunjungan']) ?>"
                    data-keperluan="<?= Helpers::e($g['keperluan']) ?>"
                    title="Klik untuk memperbesar foto"
                  >
                </td>
                <td>
                  <div style="font-weight: 700;"><?= Helpers::e($g['nama_lengkap']) ?></div>
                  <div style="font-size: 0.76rem; color: var(--brand-blue); font-weight: 600;"><?= Helpers::e($g['kode_kunjungan']) ?></div>
                </td>
                <td>
                  <div style="font-weight: 600;"><?= Helpers::e($g['instansi']) ?></div>
                  <div style="font-size: 0.8rem; color: var(--text-muted);"><?= Helpers::e($g['no_hp']) ?></div>
                </td>
                <td style="max-width: 260px;">
                  <div style="font-weight: 600; color: var(--brand-navy);"><?= Helpers::e($g['tujuan']) ?></div>
                  <?php if (!empty($g['bertemu_dengan'])): ?>
                    <div style="font-size: 0.78rem; color: var(--text-secondary);">PIC: <?= Helpers::e($g['bertemu_dengan']) ?></div>
                  <?php endif; ?>
                  <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= Helpers::e($g['keperluan']) ?>">
                    <?= Helpers::e($g['keperluan']) ?>
                  </div>
                </td>
                <td style="white-space: nowrap; font-size: 0.8rem;">
                  <div><strong>Masuk:</strong> <?= Helpers::e(Helpers::formatTanggalIndo($g['waktu_masuk'])) ?></div>
                  <div style="color: var(--text-muted);"><strong>Keluar:</strong> <?= Helpers::e(Helpers::formatTanggalIndo($g['waktu_keluar'])) ?></div>
                </td>
                <td>
                  <?php if ($g['status'] === 'berkunjung'): ?>
                    <span class="badge badge-active">Berkunjung</span>
                  <?php else: ?>
                    <span class="badge badge-done">Selesai</span>
                  <?php endif; ?>
                </td>
                <td class="no-print">
                  <div style="display: flex; gap: 0.4rem;">
                    <?php if ($g['status'] === 'berkunjung'): ?>
                      <form method="POST" action="/admin/guest/checkout" style="margin: 0;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <input type="hidden" name="redirect_query" value="<?= Helpers::e($currentQuery) ?>">
                        <button type="submit" class="btn btn-soft btn-sm" title="Tandai selesai berkunjung">Check-Out</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" action="/admin/guest/delete" style="margin: 0;" onsubmit="return confirm('Hapus data tamu ini beserta fotonya?');">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                      <input type="hidden" name="redirect_query" value="<?= Helpers::e($currentQuery) ?>">
                      <button type="submit" class="btn btn-danger-soft btn-sm">Hapus</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination no-print">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php
            $qs = http_build_query(array_merge(array_filter($filters), ['page' => $p]));
          ?>
          <a href="/admin?<?= Helpers::e($qs) ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
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

<script>
  window.BDI_CHART_DATA = <?= json_encode([
      'trend' => $stats['trend'],
      'tujuan' => $stats['tujuan_chart'],
  ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="/assets/js/admin.js"></script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
