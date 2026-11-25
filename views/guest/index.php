<?php
use App\Core\Csrf;
use App\Core\Helpers;

$pageTitle = 'Buku Tamu Digital — BDI Surabaya';
require dirname(__DIR__) . '/layouts/header.php';
?>

<header class="topbar">
  <div class="topbar-inner">
    <a href="/" class="brand">
      <div class="brand-logo-wrap">
        <img src="/assets/img/logo-bdi.png" alt="Logo BDI Surabaya" class="brand-logo">
      </div>
      <div>
        <div class="brand-title">BDI Surabaya</div>
        <div class="brand-sub">Kementerian Perindustrian RI</div>
      </div>
    </a>

    <div class="nav-actions">
      <div class="live-clock-pill">
        <span class="pulse-dot"></span>
        <span id="liveWibClock">Memuat waktu...</span>
      </div>
      <a href="/checkout" class="btn btn-primary btn-sm">Check-Out Tamu</a>
    </div>
  </div>
</header>

<main class="container">
  <section class="hero-header animate-in">
    <div>
      <div class="hero-badge">Balai Diklat Industri Surabaya • Smart Reception</div>
      <h1 class="hero-title">Buku Tamu Digital <span class="gradient-text">BDI Surabaya</span></h1>
      <p class="hero-desc">
        Selamat datang! Silakan isi data kunjungan dan ambil foto wajah sebagai bukti kehadiran resmi Anda.
      </p>
    </div>

    <div class="hero-stats">
      <div class="mini-stat">
        <div class="mini-stat-label">Tamu Hari Ini</div>
        <div class="mini-stat-value" id="statToday"><?= (int) $stats['today'] ?></div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-label">Sedang Berkunjung</div>
        <div class="mini-stat-value" id="statActive" style="color: var(--brand-teal);"><?= (int) $stats['active'] ?></div>
      </div>
    </div>
  </section>

  <section class="checkin-grid">
    <!-- Left Column: Live Face Photo Capture -->
    <div class="glass-card panel-card animate-in delay-1">
      <h2 class="panel-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--brand-blue);">
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
          <circle cx="12" cy="13" r="4"/>
        </svg>
        Foto Wajah Bukti Kehadiran
      </h2>
      <p class="panel-subtitle">Posisikan wajah Anda di dalam bingkai oval lalu tekan tombol ambil foto.</p>

      <div class="camera-stage">
        <div id="cameraStandby" class="camera-standby">
          <div class="standby-icon-ring">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
          </div>
          <div class="standby-title">Kamera Standby</div>
          <p class="standby-desc">Klik tombol di bawah untuk mengaktifkan kamera dan mengambil foto wajah Anda.</p>
          <button type="button" id="btnActivateCamera" class="btn btn-primary">
            Aktifkan Kamera
          </button>
        </div>

        <video id="cameraVideo" class="mirror" autoplay playsinline muted style="display: none;"></video>
        <img id="capturedPreview" class="captured-preview" alt="Hasil Foto Wajah" style="display: none;">
        <canvas id="cameraCanvas" style="display: none;"></canvas>

        <div id="faceOverlay" class="face-guide-overlay" style="display: none;">
          <div class="face-oval"></div>
        </div>

        <div id="viewfinderCorners" class="viewfinder-corners" aria-hidden="true" style="display: none;">
          <span></span><span></span><span></span><span></span>
        </div>

        <div class="camera-status-badge">
          <span class="pulse-dot" id="cameraStatusDot"></span>
          <span id="cameraStatusText">Kamera Standby</span>
        </div>

        <div id="countdownOverlay" class="countdown-overlay" style="display: none;">3</div>
        <div id="shutterFlash" class="shutter-flash"></div>
      </div>

      <div class="camera-controls">
        <button type="button" id="btnCapture" class="btn btn-primary" style="display: none;">
          Ambil Foto
        </button>
        <button type="button" id="btnTimerCapture" class="btn btn-soft" style="display: none;">
          Timer 3 Detik
        </button>
        <button type="button" id="btnSwitchCam" class="btn btn-outline" style="display: none;">
          Putar Kamera
        </button>
        <button type="button" id="btnRetake" class="btn btn-outline" style="display: none;">
          Ulangi Foto
        </button>
      </div>

      <div class="manual-upload-toggle">
        <button type="button" id="btnToggleFallback" class="btn-link-muted">
          Kamera bermasalah? Unggah foto secara manual
        </button>
      </div>

      <div id="cameraFallback" class="camera-fallback" style="display: none;">
        <strong>Unggah Foto Wajah</strong> — Pilih atau ambil foto langsung dari perangkat Anda (otomatis dikompresi).
        <div style="margin-top: 0.6rem;">
          <input type="file" id="fotoFileFallback" accept="image/*" capture="user" class="form-control">
        </div>
      </div>
    </div>

    <!-- Right Column: Guest Form -->
    <div class="glass-card panel-card animate-in delay-2">
      <h2 class="panel-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--brand-teal);">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Formulir Data Pengunjung
      </h2>
      <p class="panel-subtitle">Lengkapi identitas kunjungan Anda dengan benar.</p>

      <form id="checkinForm" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" id="fotoBase64" name="foto_base64" value="">

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="req">*</span></label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" required maxlength="120">
          </div>

          <div class="form-group">
            <label class="form-label" for="instansi">Asal Instansi / Perusahaan <span class="req">*</span></label>
            <input type="text" id="instansi" name="instansi" class="form-control" placeholder="Contoh: PT Maju Jaya / Umum" required maxlength="150">
          </div>

          <div class="form-group">
            <label class="form-label" for="no_hp">No. HP / WhatsApp <span class="req">*</span></label>
            <input type="tel" id="no_hp" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required maxlength="25">
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email (Opsional)</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" maxlength="120">
          </div>

          <div class="form-group">
            <label class="form-label" for="tujuan">Unit / Tujuan Kunjungan <span class="req">*</span></label>
            <select id="tujuan" name="tujuan" class="form-control" required>
              <option value="">-- Pilih Tujuan --</option>
              <?php foreach ($tujuanList as $item): ?>
                <option value="<?= Helpers::e($item) ?>"><?= Helpers::e($item) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="bertemu_dengan">Bertemu Dengan (Opsional)</label>
            <input type="text" id="bertemu_dengan" name="bertemu_dengan" class="form-control" placeholder="Nama pegawai / PIC" maxlength="120">
          </div>

          <div class="form-group full">
            <label class="form-label" for="keperluan">Keperluan Kunjungan <span class="req">*</span></label>
            <textarea id="keperluan" name="keperluan" class="form-control" placeholder="Jelaskan keperluan kunjungan Anda di BDI Surabaya..." required></textarea>
          </div>

          <div class="form-group full" style="margin-top: 0.35rem;">
            <button type="submit" id="btnSubmitCheckin" class="btn btn-primary" style="width: 100%; padding: 0.9rem 1.5rem; font-size: 1rem;">
              Catat Kehadiran &amp; Simpan
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>
</main>

<!-- Success Digital Pass Modal -->
<div id="successModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="successTitle">
  <div class="modal-card visitor-pass-card">
    <div class="visitor-pass-ribbon">
      <span>EXECUTIVE VISITOR PASS</span>
      <span>BDI SURABAYA</span>
    </div>

    <div style="text-align: center; padding-top: 0.5rem;">
      <img id="passPhoto" src="" alt="Foto Pengunjung" class="visitor-pass-photo">
      <h3 id="successTitle" class="modal-display-title">Kehadiran Tercatat</h3>
      <p style="margin: 0; color: var(--text-muted); font-size: 0.88rem;">Selamat datang di Balai Diklat Industri Surabaya</p>
    </div>

    <div class="visit-code-box">
      <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.08em;">Kode Kunjungan Resmi</div>
      <div id="passCode" class="visit-code-value">BDI-00000000-XXXX</div>
      <div class="pass-qr-wrap">
        <div id="passQrCode" class="pass-qr-box" aria-label="QR Code Kode Kunjungan"></div>
      </div>
      <div style="font-size: 0.76rem; color: var(--text-secondary); margin-top: 0.35rem;">Tunjukkan atau pindai QR Code ini saat Check-Out.</div>
    </div>

    <div class="pass-ticket-divider" aria-hidden="true"></div>

    <div class="pass-details-box">
      <div><span>Nama</span><strong id="passName"></strong></div>
      <div><span>Instansi</span><strong id="passInstansi"></strong></div>
      <div><span>Tujuan</span><strong id="passTujuan"></strong></div>
      <div><span>Waktu Masuk</span><strong id="passTime"></strong></div>
    </div>

    <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem; flex-wrap: wrap;">
      <button type="button" id="btnDownloadPass" class="btn btn-soft" style="flex: 1;">Simpan QR / Pass</button>
      <button type="button" id="btnCloseSuccessModal" class="btn btn-primary" style="flex: 1;">Selesai</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="/assets/js/checkin.js?v=20261125-2"></script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
