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

  // Photo Lightbox Modal
  const lightbox = document.getElementById('photoLightbox');
  const lbImg = document.getElementById('lbImage');
  const lbName = document.getElementById('lbName');
  const lbMeta = document.getElementById('lbMeta');
  const lbKeperluan = document.getElementById('lbKeperluan');
  const lbClose = document.getElementById('lbClose');

  document.querySelectorAll('.js-open-lightbox').forEach((el) => {
    el.addEventListener('click', () => {
      if (!lightbox) return;
      lbImg.src = el.dataset.photo || '';
      lbName.textContent = el.dataset.name || '';
      lbMeta.textContent = `${el.dataset.instansi || ''} • ${el.dataset.tujuan || ''} • ${el.dataset.kode || ''}`;
      lbKeperluan.textContent = el.dataset.keperluan || '-';
      lightbox.classList.add('open');
    });
  });

  if (lbClose && lightbox) {
    lbClose.addEventListener('click', () => lightbox.classList.remove('open'));
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) lightbox.classList.remove('open');
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
            backgroundColor: 'rgba(30, 58, 138, 0.86)',
            hoverBackgroundColor: '#b48327',
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
              grid: { color: 'rgba(180, 131, 39, 0.12)' },
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
                '#0b132b', '#1e3a8a', '#b48327', '#0f766e', '#3b82f6', '#d4af37'
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
