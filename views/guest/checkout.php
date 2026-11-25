<?php
use App\Core\Csrf;

$pageTitle = 'Check-Out Tamu — BDI Surabaya';
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
      <a href="/" class="btn btn-outline btn-sm">&larr; Kembali ke Buku Tamu</a>
    </div>
  </div>
</header>

<main class="container" style="max-width: 580px; padding-top: 2.75rem;">
  <div class="glass-card panel-card animate-in">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <div class="hero-badge">Check-Out Pengunjung</div>
      <h1 class="display-heading" style="margin: 0.25rem 0; font-size: 1.8rem;">Selesai Berkunjung</h1>
      <p style="margin: 0; color: var(--text-secondary); font-size: 0.92rem;">
        Masukkan <strong>Kode Kunjungan</strong> / <strong>Nomor HP</strong>, atau pindai <strong>QR Code</strong> pada Visitor Pass Anda.
      </p>
    </div>

    <div id="checkoutErrorAlert" class="alert alert-error checkout-error-alert" style="display: none;" role="alert"></div>

    <div style="margin-bottom: 1.15rem;">
      <button type="button" id="btnToggleQrScanner" class="btn btn-soft" style="width: 100%; padding: 0.8rem 1rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M20 14v7h-7"/>
        </svg>
        <span id="btnScannerLabel">Scan QR Code</span>
      </button>
    </div>

    <div id="qrScannerWrap" class="qr-scanner-wrap" style="display: none;">
      <div class="qr-scanner-header">
        <span>Arahkan kamera ke QR Code Visitor Pass</span>
        <button type="button" id="btnCloseQrScanner" class="btn btn-outline btn-sm">Tutup Scanner</button>
      </div>
      <div id="qrReader" class="qr-reader-box"></div>
    </div>

    <form id="checkoutForm">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="checkoutQuery">Kode Kunjungan / Nomor HP</label>
        <input type="text" id="checkoutQuery" name="query" class="form-control" placeholder="Contoh: BDI-20261125-A1B2 atau 0812xxxx" required autofocus>
      </div>
      <button type="submit" id="btnCheckoutSubmit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.88rem;">
        Konfirmasi Check-Out
      </button>
    </form>
  </div>
</main>

<!-- Glassmorphism Check-Out Success Modal -->
<div id="checkoutSuccessModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="checkoutSuccessTitle">
  <div class="modal-card visitor-pass-card checkout-modal-card">
    <div class="visitor-pass-ribbon">
      <span>CHECK-OUT SELESAI</span>
      <span>BDI SURABAYA</span>
    </div>

    <div style="text-align: center; padding-top: 0.35rem;">
      <div class="confirm-icon-circle is-success" aria-hidden="true">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h2 id="checkoutSuccessTitle" class="modal-display-title">Check-Out Berhasil!</h2>
      <p style="margin: 0 0 1rem; color: var(--text-muted); font-size: 0.88rem;">
        Terima kasih telah berkunjung ke Balai Diklat Industri Surabaya.
      </p>
    </div>

    <div id="checkoutResultBody" class="pass-details-box"></div>

    <div class="checkout-countdown-pill" id="checkoutCountdownText">
      Kembali ke halaman buku tamu dalam 4 detik...
    </div>

    <div style="display: flex; gap: 0.65rem; margin-top: 1rem;">
      <button type="button" id="btnCheckoutGoHome" class="btn btn-primary" style="flex: 1;">
        Kembali Sekarang
      </button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
  const form = document.getElementById('checkoutForm');
  const queryInput = document.getElementById('checkoutQuery');
  const btn = document.getElementById('btnCheckoutSubmit');
  const errorAlert = document.getElementById('checkoutErrorAlert');

  const successModal = document.getElementById('checkoutSuccessModal');
  const resultBody = document.getElementById('checkoutResultBody');
  const countdownText = document.getElementById('checkoutCountdownText');
  const btnGoHome = document.getElementById('btnCheckoutGoHome');

  const btnToggleScanner = document.getElementById('btnToggleQrScanner');
  const btnScannerLabel = document.getElementById('btnScannerLabel');
  const btnCloseScanner = document.getElementById('btnCloseQrScanner');
  const scannerWrap = document.getElementById('qrScannerWrap');

  let html5QrCode = null;
  let isScanning = false;
  let isSubmitting = false;
  let redirectTimer = null;

  function toast(msg, type = 'error') {
    const t = document.getElementById('appToast');
    if (!t) return;
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3600);
  }

  function showErrorAlert(msg) {
    if (!errorAlert) return;
    errorAlert.textContent = msg;
    errorAlert.style.display = 'block';
    errorAlert.classList.remove('animate-in');
    void errorAlert.offsetWidth;
    errorAlert.classList.add('animate-in');
  }

  function hideErrorAlert() {
    if (errorAlert) {
      errorAlert.style.display = 'none';
      errorAlert.textContent = '';
    }
  }

  function redirectToHome() {
    if (redirectTimer) {
      clearInterval(redirectTimer);
      redirectTimer = null;
    }
    if (typeof window.bdiNavigateWithTransition === 'function') {
      window.bdiNavigateWithTransition('/');
    } else {
      window.location.href = '/';
    }
  }

  function openCheckoutSuccessModal(data) {
    resultBody.replaceChildren();
    const fields = [
      ['Nama', data.nama_lengkap],
      ['Instansi', data.instansi],
      ['Kode Kunjungan', data.kode_kunjungan],
      ['Waktu Masuk', data.waktu_masuk],
      ['Waktu Keluar', data.waktu_keluar],
    ];
    fields.forEach(([label, val]) => {
      const row = document.createElement('div');
      const span = document.createElement('span');
      span.textContent = label;
      const strong = document.createElement('strong');
      strong.textContent = val ?? '-';
      row.appendChild(span);
      row.appendChild(strong);
      resultBody.appendChild(row);
    });

    successModal.classList.add('open');

    let remaining = 4;
    countdownText.textContent = `Kembali ke halaman buku tamu dalam ${remaining} detik...`;
    if (redirectTimer) clearInterval(redirectTimer);
    redirectTimer = setInterval(() => {
      remaining -= 1;
      if (remaining > 0) {
        countdownText.textContent = `Kembali ke halaman buku tamu dalam ${remaining} detik...`;
      } else {
        redirectToHome();
      }
    }, 1000);
  }

  async function stopQrScanner() {
    if (html5QrCode && isScanning) {
      try {
        await html5QrCode.stop();
        html5QrCode.clear();
      } catch (_) {
        // Ignore stop errors if already stopped
      }
    }
    isScanning = false;
    scannerWrap.style.display = 'none';
    btnScannerLabel.textContent = 'Scan QR Code';
  }

  async function startQrScanner() {
    if (typeof Html5Qrcode === 'undefined') {
      toast('Library QR Scanner gagal dimuat. Gunakan input manual.', 'error');
      return;
    }

    hideErrorAlert();
    scannerWrap.style.display = 'block';
    btnScannerLabel.textContent = 'Kamera Scanner Aktif...';

    try {
      if (!html5QrCode) {
        html5QrCode = new Html5Qrcode('qrReader');
      }
      isScanning = true;
      await html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        async (decodedText) => {
          const code = (decodedText || '').trim();
          if (!code || isSubmitting) return;
          queryInput.value = code;
          await stopQrScanner();
          toast('QR Code terbaca! Memproses check-out...', 'success');
          processCheckout();
        },
        () => {}
      );
    } catch (err) {
      isScanning = false;
      scannerWrap.style.display = 'none';
      btnScannerLabel.textContent = 'Scan QR Code';
      toast('Kamera tidak dapat diakses. Pastikan izin kamera aktif atau gunakan input manual.', 'error');
    }
  }

  async function processCheckout() {
    if (isSubmitting) return;
    isSubmitting = true;
    hideErrorAlert();
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    try {
      const res = await fetch('/api/checkout', {
        method: 'POST',
        body: new FormData(form),
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        const errMsg = json.message || 'Data kunjungan tidak ditemukan.';
        showErrorAlert(errMsg);
        toast(errMsg, 'error');
        return;
      }

      form.reset();
      openCheckoutSuccessModal(json.data);
    } catch {
      const errMsg = 'Gagal terhubung ke server.';
      showErrorAlert(errMsg);
      toast(errMsg, 'error');
    } finally {
      isSubmitting = false;
      btn.disabled = false;
      btn.textContent = 'Konfirmasi Check-Out';
    }
  }

  if (btnGoHome) {
    btnGoHome.addEventListener('click', redirectToHome);
  }

  if (btnToggleScanner) {
    btnToggleScanner.addEventListener('click', () => {
      if (isScanning) {
        stopQrScanner();
      } else {
        startQrScanner();
      }
    });
  }

  if (btnCloseScanner) {
    btnCloseScanner.addEventListener('click', stopQrScanner);
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    processCheckout();
  });

  window.addEventListener('beforeunload', () => {
    stopQrScanner();
    if (redirectTimer) clearInterval(redirectTimer);
  });
})();
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
