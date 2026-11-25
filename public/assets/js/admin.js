(() => {
  // Animated KPI counters
  document.querySelectorAll('[data-count]').forEach((el) => {
    const target = parseInt(el.getAttribute('data-count') || '0', 10);
    if (isNaN(target) || target === 0) {
      el.textContent = '0';
      return;
    }
    const duration = 650;
    const start = performance.now();
    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(target * eased).toLocaleString('id-ID');
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  });

  const tableBody = document.getElementById('guestTableBody');

  // Photo Lightbox Modal (delegated for AJAX-refreshed rows)
  const lightbox = document.getElementById('photoLightbox');
  const lbImg = document.getElementById('lbImage');
  const lbName = document.getElementById('lbName');
  const lbMeta = document.getElementById('lbMeta');
  const lbKeperluan = document.getElementById('lbKeperluan');
  const lbClose = document.getElementById('lbClose');

  if (tableBody) {
    tableBody.addEventListener('click', (e) => {
      const thumb = e.target.closest('.js-open-lightbox');
      if (!thumb || !lightbox) return;
      lbImg.src = thumb.dataset.photo || '';
      lbName.textContent = thumb.dataset.name || '';
      lbMeta.textContent = `${thumb.dataset.instansi || ''} • ${thumb.dataset.tujuan || ''} • ${thumb.dataset.kode || ''}`;
      lbKeperluan.textContent = thumb.dataset.keperluan || '-';
      lightbox.classList.add('open');
    });
  }

  if (lbClose && lightbox) {
    lbClose.addEventListener('click', () => lightbox.classList.remove('open'));
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) lightbox.classList.remove('open');
    });
  }

  // Custom Admin Confirmation Modal (delegated for AJAX-refreshed rows)
  const confirmModal = document.getElementById('adminConfirmModal');
  const confirmIconWrap = document.getElementById('confirmIconWrap');
  const confirmIconSvg = document.getElementById('confirmIconSvg');
  const confirmTitle = document.getElementById('confirmTitle');
  const confirmDesc = document.getElementById('confirmDesc');
  const confirmGuestName = document.getElementById('confirmGuestName');
  const confirmGuestCode = document.getElementById('confirmGuestCode');
  const confirmCancelBtn = document.getElementById('confirmCancelBtn');
  const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');

  let pendingForm = null;

  const TRASH_ICON = `
    <polyline points="3 6 5 6 21 6"></polyline>
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
    <line x1="10" y1="11" x2="10" y2="17"></line>
    <line x1="14" y1="11" x2="14" y2="17"></line>
  `;

  const CHECKOUT_ICON = `
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
    <polyline points="16 17 21 12 16 7"></polyline>
    <line x1="21" y1="12" x2="9" y2="12"></line>
  `;

  function closeConfirmModal() {
    if (!confirmModal) return;
    confirmModal.classList.remove('open');
    pendingForm = null;
  }

  if (tableBody) {
    tableBody.addEventListener('submit', (e) => {
      const form = e.target.closest('.js-confirm-action');
      if (!form || !confirmModal) return;
      e.preventDefault();
      pendingForm = form;

      const actionType = form.dataset.action === 'checkout' ? 'checkout' : 'delete';
      confirmGuestName.textContent = form.dataset.name || 'Tamu';
      confirmGuestCode.textContent = form.dataset.kode || '-';

      if (actionType === 'checkout') {
        confirmIconWrap.className = 'confirm-icon-circle is-primary';
        confirmIconSvg.innerHTML = CHECKOUT_ICON;
        confirmTitle.textContent = 'Konfirmasi Check-Out Tamu';
        confirmDesc.textContent = 'Status kunjungan tamu ini akan ditandai sebagai Selesai (Check-Out) pada waktu saat ini.';
        confirmSubmitBtn.className = 'btn btn-primary';
        confirmSubmitBtn.textContent = 'Ya, Check-Out';
      } else {
        confirmIconWrap.className = 'confirm-icon-circle is-danger';
        confirmIconSvg.innerHTML = TRASH_ICON;
        confirmTitle.textContent = 'Konfirmasi Hapus Data';
        confirmDesc.textContent = 'Data kunjungan beserta foto wajah tamu akan dihapus secara permanen dan tidak dapat dipulihkan.';
        confirmSubmitBtn.className = 'btn btn-danger-gradient';
        confirmSubmitBtn.textContent = 'Ya, Hapus Data';
      }

      confirmModal.classList.add('open');
    });
  }

  if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener('click', closeConfirmModal);
  }

  if (confirmSubmitBtn) {
    confirmSubmitBtn.addEventListener('click', () => {
      if (pendingForm) {
        const formToSubmit = pendingForm;
        closeConfirmModal();
        formToSubmit.submit();
      }
    });
  }

  if (confirmModal) {
    confirmModal.addEventListener('click', (e) => {
      if (e.target === confirmModal) {
        closeConfirmModal();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (confirmModal && confirmModal.classList.contains('open')) {
        closeConfirmModal();
      }
      if (lightbox && lightbox.classList.contains('open')) {
        lightbox.classList.remove('open');
      }
    }
  });

  // Smart AJAX Polling & Real-Time Notifications (Background + Desktop Notification)
  const liveSyncBadge = document.getElementById('liveSyncBadge');
  const liveSyncText = document.getElementById('liveSyncText');
  const btnToggleLiveSync = document.getElementById('btnToggleLiveSync');
  const btnEnableDesktopNotif = document.getElementById('btnEnableDesktopNotif');
  const liveToast = document.getElementById('adminLiveToast');
  const liveToastName = document.getElementById('liveToastName');
  const liveToastMeta = document.getElementById('liveToastMeta');
  const liveToastClose = document.getElementById('liveToastClose');

  let liveToastTimer = null;

  function updateDesktopNotifButton() {
    if (!btnEnableDesktopNotif) return;
    if (!('Notification' in window)) {
      btnEnableDesktopNotif.style.display = 'none';
      return;
    }
    if (Notification.permission === 'granted') {
      btnEnableDesktopNotif.textContent = 'Notif Desktop Aktif';
      btnEnableDesktopNotif.classList.remove('btn-soft');
      btnEnableDesktopNotif.classList.add('btn-outline');
      btnEnableDesktopNotif.disabled = true;
    } else if (Notification.permission === 'denied') {
      btnEnableDesktopNotif.textContent = 'Notif Diblokir Browser';
      btnEnableDesktopNotif.disabled = true;
    } else {
      btnEnableDesktopNotif.textContent = 'Aktifkan Notifikasi Desktop';
      btnEnableDesktopNotif.disabled = false;
    }
  }

  if (btnEnableDesktopNotif) {
    updateDesktopNotifButton();
    btnEnableDesktopNotif.addEventListener('click', async () => {
      if (!('Notification' in window)) return;
      try {
        await Notification.requestPermission();
      } catch (_) {
        // Ignore
      }
      updateDesktopNotifButton();
    });
  }

  function showDesktopNotification(guest) {
    if (!guest || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;
    // Trigger native desktop notification when tab is in background or minimized (or whenever permitted)
    if (document.hidden) {
      try {
        const notif = new Notification('Tamu Baru BDI Surabaya', {
          body: `${guest.nama_lengkap} (${guest.instansi}) — ${guest.tujuan}`,
          icon: '/assets/img/logo-bdi.png',
        });
        notif.onclick = () => {
          window.focus();
          notif.close();
        };
      } catch (_) {
        // Ignore if Notification constructor is blocked
      }
    }
  }

  function playNotificationChime() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const now = ctx.currentTime;

      const notes = [1046.5, 1318.5]; // C6 -> E6 soft 2-note chime
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, now + idx * 0.14);

        gain.gain.setValueAtTime(0.001, now + idx * 0.14);
        gain.gain.exponentialRampToValueAtTime(0.09, now + idx * 0.14 + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.14 + 0.28);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now + idx * 0.14);
        osc.stop(now + idx * 0.14 + 0.3);
      });

      setTimeout(() => {
        if (ctx.state !== 'closed') ctx.close().catch(() => {});
      }, 900);
    } catch (_) {
      // Browser autoplay policy blocked audio; ignore silently
    }
  }

  function showLiveGuestToast(guest) {
    if (!liveToast || !guest) return;
    liveToastName.textContent = guest.nama_lengkap || 'Pengunjung Baru';
    liveToastMeta.textContent = `${guest.instansi || '-'} • ${guest.tujuan || '-'}`;
    liveToast.classList.add('show');

    if (liveToastTimer) clearTimeout(liveToastTimer);
    liveToastTimer = setTimeout(() => {
      liveToast.classList.remove('show');
    }, 6000);
  }

  if (liveToastClose && liveToast) {
    liveToastClose.addEventListener('click', () => {
      liveToast.classList.remove('show');
      if (liveToastTimer) clearTimeout(liveToastTimer);
    });
  }

  function updateKpiValues(stats) {
    if (!stats) return;
    const map = [
      ['kpiToday', stats.today],
      ['kpiActive', stats.active],
      ['kpiMonth', stats.month],
      ['kpiTotal', stats.total],
    ];
    map.forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el && typeof val === 'number') {
        el.textContent = val.toLocaleString('id-ID');
      }
    });
    const quickActiveEl = document.getElementById('quickTabActiveCount');
    if (quickActiveEl && typeof stats.active === 'number') {
      quickActiveEl.textContent = stats.active.toLocaleString('id-ID');
    }
  }

  function applyTableHtmlSmoothly(html, highlightId = null) {
    tableBody.classList.add('is-updating');
    tableBody.innerHTML = html;
    if (highlightId) {
      const newRow = tableBody.querySelector(`tr[data-guest-id="${highlightId}"]`);
      if (newRow) {
        newRow.classList.add('row-highlight-new');
      }
    }
    requestAnimationFrame(() => {
      tableBody.classList.remove('is-updating');
    });
  }

  if (tableBody && window.BDI_POLL_CONFIG) {
    let lastKnownLatestId = Number(window.BDI_POLL_CONFIG.latestId || 0);
    let lastSignature = null;
    let isManuallyPaused = false;
    let isPolling = false;
    let pollIntervalId = null;

    function isModalOpen() {
      return Boolean(
        (confirmModal && confirmModal.classList.contains('open')) ||
        (lightbox && lightbox.classList.contains('open'))
      );
    }

    async function runPoll() {
      if (isManuallyPaused || isPolling) return;
      isPolling = true;
      try {
        const qs = window.BDI_POLL_CONFIG.queryString;
        const url = '/api/admin/poll' + (qs ? `?${qs}` : '');
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        if (!data || !data.success) return;

        const isNewGuest = data.latest_id > lastKnownLatestId;
        const hasStateChanged = lastSignature !== null && data.signature !== lastSignature;

        if (isNewGuest) {
          lastKnownLatestId = data.latest_id;
          lastSignature = data.signature;

          if (!isModalOpen()) {
            updateKpiValues(data.stats);
            applyTableHtmlSmoothly(data.table_html, data.latest_id);
          }

          showLiveGuestToast(data.latest_guest);
          showDesktopNotification(data.latest_guest);
          playNotificationChime();
        } else if (hasStateChanged) {
          lastSignature = data.signature;
          if (!isModalOpen()) {
            updateKpiValues(data.stats);
            applyTableHtmlSmoothly(data.table_html);
          }
        } else if (lastSignature === null) {
          lastSignature = data.signature;
        }
      } catch (_) {
        // Ignore transient network errors during background polling
      } finally {
        isPolling = false;
      }
    }

    function updateSyncBadge() {
      if (!liveSyncBadge || !liveSyncText || !btnToggleLiveSync) return;
      if (isManuallyPaused) {
        liveSyncBadge.classList.add('is-paused');
        liveSyncText.textContent = 'Live Sync Dijeda';
        btnToggleLiveSync.textContent = 'Lanjutkan';
      } else {
        liveSyncBadge.classList.remove('is-paused');
        liveSyncText.textContent = 'Live Sync Aktif';
        btnToggleLiveSync.textContent = 'Jeda Sync';
      }
    }

    if (btnToggleLiveSync) {
      btnToggleLiveSync.addEventListener('click', () => {
        isManuallyPaused = !isManuallyPaused;
        updateSyncBadge();
        if (!isManuallyPaused) {
          runPoll();
        }
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden && !isManuallyPaused) {
        runPoll();
      }
    });

    pollIntervalId = setInterval(runPoll, 5000);
    window.addEventListener('beforeunload', () => {
      if (pollIntervalId) clearInterval(pollIntervalId);
    });
  }

  function renderEmptyChartState(canvas, message) {
    const parent = canvas.parentElement;
    if (!parent) return;
    canvas.style.display = 'none';
    const empty = document.createElement('div');
    empty.style.cssText = 'height:100%;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:0.88rem;font-weight:500;text-align:center;';
    empty.textContent = message;
    parent.appendChild(empty);
  }

  // Charts (if Chart.js is loaded and window.BDI_CHART_DATA exists)
  if (typeof Chart !== 'undefined' && window.BDI_CHART_DATA) {
    const data = window.BDI_CHART_DATA;

    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
      new Chart(trendCanvas, {
        type: 'bar',
        data: {
          labels: data.trend.labels,
          datasets: [{
            label: 'Jumlah Tamu',
            data: data.trend.counts,
            backgroundColor: 'rgba(37, 99, 235, 0.85)',
            hoverBackgroundColor: '#1d4ed8',
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 38,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0, color: '#475569' },
              grid: { color: 'rgba(148, 163, 184, 0.2)' },
            },
            x: {
              ticks: { color: '#475569' },
              grid: { display: false },
            },
          },
        },
      });
    }

    const tujuanCanvas = document.getElementById('tujuanChart');
    if (tujuanCanvas) {
      if (data.tujuan.labels.length > 0) {
        new Chart(tujuanCanvas, {
          type: 'doughnut',
          data: {
            labels: data.tujuan.labels,
            datasets: [{
              data: data.tujuan.counts,
              backgroundColor: [
                '#2563eb', '#0ea5e9', '#14b8a6', '#6366f1', '#1d4ed8', '#38bdf8'
              ],
              borderWidth: 2,
              borderColor: '#ffffff',
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '66%',
            plugins: {
              legend: {
                position: 'bottom',
                labels: { boxWidth: 10, font: { size: 11 }, color: '#334155' },
              },
            },
          },
        });
      } else {
        renderEmptyChartState(tujuanCanvas, 'Belum ada data distribusi tujuan kunjungan.');
      }
    }
  }
})();
