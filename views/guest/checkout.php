<?php
use App\Core\Csrf;

$pageTitle = 'Check-Out Tamu — BDI Surabaya';
require dirname(__DIR__) . '/layouts/header.php';
?>

<header class="topbar">
  <div class="topbar-inner">
    <a href="/" class="brand">
      <img src="/assets/img/bdi-logo.svg" alt="Logo BDI Surabaya" class="brand-logo">
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
      <a href="/admin" class="btn btn-soft btn-sm">Panel Admin</a>
    </div>
  </div>
</header>

<main class="container" style="max-width: 560px; padding-top: 3rem;">
  <div class="glass-card panel-card animate-in">
    <div style="text-align: center; margin-bottom: 1.5rem;">
      <div class="hero-badge">Check-Out Pengunjung</div>
      <h1 class="display-heading" style="margin: 0.25rem 0; font-size: 1.8rem;">Selesai Berkunjung</h1>
      <p style="margin: 0; color: var(--text-secondary); font-size: 0.92rem;">
        Masukkan <strong>Kode Kunjungan</strong> atau <strong>Nomor HP</strong> yang Anda daftarkan saat masuk.
      </p>
    </div>

    <form id="checkoutForm">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label" for="checkoutQuery">Kode Kunjungan / Nomor HP</label>
        <input type="text" id="checkoutQuery" name="query" class="form-control" placeholder="Contoh: BDI-20261124-A1B2 atau 0812xxxx" required autofocus>
      </div>
      <button type="submit" id="btnCheckoutSubmit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.85rem;">
        Konfirmasi Check-Out
      </button>
    </form>

    <div id="checkoutResult" style="display: none; margin-top: 1.25rem; padding: 1rem; border-radius: 14px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;">
      <div style="font-weight: 800; font-size: 1.02rem; margin-bottom: 0.35rem;">Check-Out Berhasil!</div>
      <div id="checkoutResultBody" style="font-size: 0.88rem; display: grid; gap: 0.25rem;"></div>
    </div>
  </div>
</main>

<script>
(() => {
  const form = document.getElementById('checkoutForm');
  const btn = document.getElementById('btnCheckoutSubmit');
  const resultBox = document.getElementById('checkoutResult');
  const resultBody = document.getElementById('checkoutResultBody');

  function toast(msg, type = 'error') {
    const t = document.getElementById('appToast');
    if (!t) return;
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3500);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btn.disabled = true;
    btn.textContent = 'Memproses...';
    resultBox.style.display = 'none';

    try {
      const res = await fetch('/api/checkout', {
        method: 'POST',
        body: new FormData(form),
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        toast(json.message || 'Data tidak ditemukan.', 'error');
        return;
      }

      resultBody.replaceChildren();
      const fields = [
        ['Nama', json.data.nama_lengkap],
        ['Instansi', json.data.instansi],
        ['Kode', json.data.kode_kunjungan],
        ['Masuk', json.data.waktu_masuk],
        ['Keluar', json.data.waktu_keluar],
      ];
      fields.forEach(([label, val]) => {
        const row = document.createElement('div');
        const strong = document.createElement('strong');
        strong.textContent = `${label}: `;
        row.appendChild(strong);
        row.appendChild(document.createTextNode(val ?? '-'));
        resultBody.appendChild(row);
      });
      resultBox.style.display = 'block';
      form.reset();
      toast('Check-out berhasil dicatat!', 'success');
    } catch {
      toast('Gagal terhubung ke server.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Konfirmasi Check-Out';
    }
  });
})();
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
