(() => {
  const standbyBox = document.getElementById('cameraStandby');
  const video = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  const preview = document.getElementById('capturedPreview');
  const faceOverlay = document.getElementById('faceOverlay');
  const viewfinderCorners = document.getElementById('viewfinderCorners');
  const statusBadge = document.getElementById('cameraStatusText');
  const countdownEl = document.getElementById('countdownOverlay');
  const shutterFlash = document.getElementById('shutterFlash');
  const fallbackBox = document.getElementById('cameraFallback');
  const fallbackInput = document.getElementById('fotoFileFallback');

  const btnActivateCamera = document.getElementById('btnActivateCamera');
  const btnCapture = document.getElementById('btnCapture');
  const btnTimerCapture = document.getElementById('btnTimerCapture');
  const btnRetake = document.getElementById('btnRetake');
  const btnSwitchCam = document.getElementById('btnSwitchCam');
  const btnToggleFallback = document.getElementById('btnToggleFallback');

  const form = document.getElementById('checkinForm');
  const fotoBase64Input = document.getElementById('fotoBase64');
  const submitBtn = document.getElementById('btnSubmitCheckin');

  const successModal = document.getElementById('successModal');
  const btnCloseModal = document.getElementById('btnCloseSuccessModal');
  const btnDownloadPass = document.getElementById('btnDownloadPass');
  const passQrContainer = document.getElementById('passQrCode');

  let currentStream = null;
  let facingMode = 'user';
  let capturedPhotoData = '';
  let fallbackReadPromise = null;
  let idleTimer = null;
  let lastPassData = null;

  const IDLE_TIMEOUT_MS = 60000;

  function revokeUrl(url) {
    if (url && url.startsWith('blob:')) {
      URL.revokeObjectURL(url);
    }
  }

  function showToast(msg, type = 'success') {
    const toast = document.getElementById('appToast');
    if (!toast) return;
    toast.textContent = msg;
    toast.className = `toast ${type} show`;
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3600);
  }

  function clearIdleTimer() {
    if (idleTimer) {
      clearTimeout(idleTimer);
      idleTimer = null;
    }
  }

  function resetIdleTimer() {
    clearIdleTimer();
    if (!currentStream) return;
    idleTimer = setTimeout(() => {
      stopCameraStream();
      setStandbyView();
      showToast('Kamera kembali ke mode standby karena tidak ada aktivitas.', 'error');
    }, IDLE_TIMEOUT_MS);
  }

  function stopCameraStream() {
    clearIdleTimer();
    if (currentStream) {
      currentStream.getTracks().forEach((t) => t.stop());
      currentStream = null;
    }
    if (video) {
      video.srcObject = null;
    }
  }

  function setStandbyView() {
    if (standbyBox) standbyBox.style.display = 'flex';
    if (video) video.style.display = 'none';
    if (preview) preview.style.display = 'none';
    if (faceOverlay) faceOverlay.style.display = 'none';
    if (viewfinderCorners) viewfinderCorners.style.display = 'none';
    if (statusBadge) statusBadge.textContent = 'Kamera Standby';
    if (btnCapture) btnCapture.style.display = 'none';
    if (btnTimerCapture) btnTimerCapture.style.display = 'none';
    if (btnSwitchCam) btnSwitchCam.style.display = 'none';
    if (btnRetake) btnRetake.style.display = 'none';
  }

  async function startCamera() {
    stopCameraStream();

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      if (statusBadge) statusBadge.textContent = 'Kamera butuh HTTPS / upload manual';
      if (fallbackBox) fallbackBox.style.display = 'block';
      showToast('Browser memblokir kamera (butuh HTTPS). Silakan unggah foto manual.', 'error');
      return;
    }

    if (statusBadge) statusBadge.textContent = 'Menghubungkan kamera...';

    try {
      currentStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode,
          width: { ideal: 960 },
          height: { ideal: 720 },
        },
        audio: false,
      });

      video.srcObject = currentStream;
      video.classList.toggle('mirror', facingMode === 'user');

      if (standbyBox) standbyBox.style.display = 'none';
      preview.style.display = 'none';
      video.style.display = 'block';
      if (faceOverlay) faceOverlay.style.display = 'flex';
      if (viewfinderCorners) viewfinderCorners.style.display = 'block';
      if (statusBadge) statusBadge.textContent = 'Kamera Aktif';
      if (fallbackBox) fallbackBox.style.display = 'none';

      btnCapture.style.display = 'inline-flex';
      btnTimerCapture.style.display = 'inline-flex';
      btnSwitchCam.style.display = 'inline-flex';
      btnCapture.disabled = false;
      btnTimerCapture.disabled = false;
      btnSwitchCam.disabled = false;
      btnRetake.style.display = 'none';

      resetIdleTimer();
    } catch (err) {
      if (statusBadge) statusBadge.textContent = 'Akses kamera ditolak';
      if (fallbackBox) fallbackBox.style.display = 'block';
      showToast('Akses kamera ditolak atau tidak tersedia. Gunakan unggah foto manual.', 'error');
    }
  }

  function triggerShutterEffect() {
    if (!shutterFlash) return;
    shutterFlash.classList.remove('active');
    void shutterFlash.offsetWidth;
    shutterFlash.classList.add('active');
  }

  function capturePhoto() {
    if (!video || !video.videoWidth) {
      showToast('Kamera belum siap. Tunggu sebentar.', 'error');
      return;
    }

    triggerShutterEffect();

    const maxDim = 800;
    let targetW = video.videoWidth;
    let targetH = video.videoHeight;
    if (targetW > maxDim || targetH > maxDim) {
      const ratio = Math.min(maxDim / targetW, maxDim / targetH);
      targetW = Math.round(targetW * ratio);
      targetH = Math.round(targetH * ratio);
    }

    canvas.width = targetW;
    canvas.height = targetH;
    const ctx = canvas.getContext('2d');

    if (facingMode === 'user') {
      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
    }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.78);
    capturedPhotoData = dataUrl;
    fotoBase64Input.value = dataUrl;
    preview.src = dataUrl;

    // Auto-stop camera stream immediately after capture
    stopCameraStream();

    if (standbyBox) standbyBox.style.display = 'none';
    preview.style.display = 'block';
    video.style.display = 'none';
    if (faceOverlay) faceOverlay.style.display = 'none';
    if (viewfinderCorners) viewfinderCorners.style.display = 'none';
    if (statusBadge) statusBadge.textContent = 'Foto Tersimpan';

    btnCapture.style.display = 'none';
    btnTimerCapture.style.display = 'none';
    btnSwitchCam.style.display = 'none';
    btnRetake.style.display = 'inline-flex';
  }

  function retakePhoto() {
    capturedPhotoData = '';
    fotoBase64Input.value = '';
    if (fallbackInput) fallbackInput.value = '';
    fallbackReadPromise = null;

    stopCameraStream();
    setStandbyView();
  }

  if (btnActivateCamera) {
    btnActivateCamera.addEventListener('click', startCamera);
  }

  if (btnCapture) {
    btnCapture.addEventListener('click', () => {
      resetIdleTimer();
      capturePhoto();
    });
  }

  if (btnTimerCapture) {
    btnTimerCapture.addEventListener('click', () => {
      if (!video || !video.videoWidth) return;
      clearIdleTimer();
      btnTimerCapture.disabled = true;
      btnCapture.disabled = true;
      if (btnSwitchCam) btnSwitchCam.disabled = true;
      let count = 3;
      countdownEl.style.display = 'flex';
      countdownEl.textContent = count;

      const interval = setInterval(() => {
        count -= 1;
        if (count > 0) {
          countdownEl.textContent = count;
        } else {
          clearInterval(interval);
          countdownEl.style.display = 'none';
          btnTimerCapture.disabled = false;
          btnCapture.disabled = false;
          if (btnSwitchCam) btnSwitchCam.disabled = false;
          capturePhoto();
        }
      }, 900);
    });
  }

  if (btnRetake) {
    btnRetake.addEventListener('click', retakePhoto);
  }

  if (btnSwitchCam) {
    btnSwitchCam.addEventListener('click', () => {
      facingMode = facingMode === 'user' ? 'environment' : 'user';
      startCamera();
    });
  }

  if (btnToggleFallback && fallbackBox) {
    btnToggleFallback.addEventListener('click', () => {
      const isHidden = fallbackBox.style.display === 'none' || !fallbackBox.style.display;
      fallbackBox.style.display = isHidden ? 'block' : 'none';
    });
  }

  function compressImageFile(file) {
    return new Promise((resolve, reject) => {
      if (file.type && !file.type.startsWith('image/')) {
        reject(new Error('Format file harus berupa gambar (JPG, PNG, atau WEBP).'));
        return;
      }

      const reader = new FileReader();
      reader.onerror = () => {
        reject(new Error('Gagal membaca file gambar dari perangkat.'));
      };
      reader.onload = (ev) => {
        const rawDataUrl = typeof ev.target?.result === 'string' ? ev.target.result : '';
        if (!rawDataUrl) {
          reject(new Error('Data gambar kosong atau tidak valid.'));
          return;
        }

        const img = new Image();
        img.onload = () => {
          const maxDim = 800;
          let { width, height } = img;
          if (!width || !height) {
            reject(new Error('Dimensi gambar tidak valid.'));
            return;
          }
          if (width > maxDim || height > maxDim) {
            const ratio = Math.min(maxDim / width, maxDim / height);
            width = Math.round(width * ratio);
            height = Math.round(height * ratio);
          }
          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);
          const compressed = canvas.toDataURL('image/jpeg', 0.78);
          if (!compressed || !compressed.startsWith('data:image/jpeg')) {
            reject(new Error('Gagal mengompresi foto ke format JPEG.'));
            return;
          }
          resolve(compressed);
        };
        img.onerror = () => {
          reject(new Error('File bukan gambar yang valid atau format tidak didukung browser.'));
        };
        img.src = rawDataUrl;
      };
      reader.readAsDataURL(file);
    });
  }

  if (fallbackInput) {
    fallbackInput.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (!file) {
        fallbackReadPromise = null;
        return;
      }

      stopCameraStream();
      if (statusBadge) statusBadge.textContent = 'Memproses foto...';

      fallbackReadPromise = compressImageFile(file)
        .then((dataUrl) => {
          capturedPhotoData = dataUrl;
          fotoBase64Input.value = dataUrl;

          if (standbyBox) standbyBox.style.display = 'none';
          preview.src = dataUrl;
          preview.style.display = 'block';
          video.style.display = 'none';
          if (faceOverlay) faceOverlay.style.display = 'none';
          if (viewfinderCorners) viewfinderCorners.style.display = 'none';
          if (statusBadge) statusBadge.textContent = 'Foto Tersimpan';
          btnCapture.style.display = 'none';
          btnTimerCapture.style.display = 'none';
          btnSwitchCam.style.display = 'none';
          btnRetake.style.display = 'inline-flex';
        })
        .catch((err) => {
          capturedPhotoData = '';
          fotoBase64Input.value = '';
          fallbackInput.value = '';
          setStandbyView();
          showToast(err?.message || 'Gagal memproses gambar yang dipilih.', 'error');
        });
    });
  }

  function renderQrCode(codeText) {
    if (!passQrContainer) return;
    passQrContainer.replaceChildren();
    if (typeof QRCode !== 'undefined') {
      new QRCode(passQrContainer, {
        text: codeText,
        width: 136,
        height: 136,
        colorDark: '#0f172a',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
    }
  }

  function downloadPassImage() {
    if (!lastPassData) return;

    const qrCanvas = passQrContainer?.querySelector('canvas');
    const qrImg = passQrContainer?.querySelector('img');
    const qrSource = qrCanvas || qrImg;

    const cardCanvas = document.createElement('canvas');
    cardCanvas.width = 640;
    cardCanvas.height = 760;
    const ctx = cardCanvas.getContext('2d');

    // Background
    ctx.fillStyle = '#ebf0f5';
    ctx.fillRect(0, 0, 640, 760);

    // Inner Card
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.roundRect(28, 28, 584, 704, 28);
    ctx.fill();

    // Header Ribbon
    const grad = ctx.createLinearGradient(28, 28, 612, 92);
    grad.addColorStop(0, '#1d4ed8');
    grad.addColorStop(1, '#2563eb');
    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.roundRect(28, 28, 584, 68, [28, 28, 0, 0]);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 16px sans-serif';
    ctx.fillText('VISITOR PASS • BDI SURABAYA', 56, 68);

    // Title & Code
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 26px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Kehadiran Tercatat', 320, 145);

    ctx.fillStyle = '#2563eb';
    ctx.font = 'bold 28px monospace';
    ctx.fillText(lastPassData.kode_kunjungan, 320, 195);

    // QR Code
    if (qrSource) {
      ctx.fillStyle = '#f8fafc';
      ctx.beginPath();
      ctx.roundRect(205, 222, 230, 230, 18);
      ctx.fill();
      ctx.drawImage(qrSource, 220, 237, 200, 200);
    }

    // Visitor Details
    ctx.textAlign = 'left';
    ctx.fillStyle = '#f1f5f9';
    ctx.beginPath();
    ctx.roundRect(56, 480, 528, 200, 18);
    ctx.fill();

    const rows = [
      ['Nama', lastPassData.nama_lengkap],
      ['Instansi', lastPassData.instansi],
      ['Tujuan', lastPassData.tujuan],
      ['Waktu Masuk', lastPassData.waktu_masuk],
    ];
    rows.forEach(([label, val], idx) => {
      const y = 522 + idx * 42;
      ctx.fillStyle = '#64748b';
      ctx.font = '600 16px sans-serif';
      ctx.fillText(label, 80, y);

      ctx.fillStyle = '#0f172a';
      ctx.font = 'bold 16px sans-serif';
      const textVal = String(val || '-').slice(0, 34);
      ctx.fillText(textVal, 215, y);
    });

    const link = document.createElement('a');
    link.download = `visitor-pass-${lastPassData.kode_kunjungan}.png`;
    link.href = cardCanvas.toDataURL('image/png');
    link.click();
  }

  if (btnDownloadPass) {
    btnDownloadPass.addEventListener('click', downloadPassImage);
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (fallbackReadPromise) {
        await fallbackReadPromise;
      }

      const photoPayload = capturedPhotoData || fotoBase64Input.value;
      if (!photoPayload) {
        showToast('Silakan ambil foto wajah atau unggah foto yang valid terlebih dahulu.', 'error');
        return;
      }

      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Menyimpan Data...';

      try {
        const formData = new FormData(form);
        formData.set('photo_data', photoPayload);

        const res = await fetch('/api/checkin', {
          method: 'POST',
          body: formData,
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
          showToast(json.message || 'Gagal menyimpan data kunjungan.', 'error');
          return;
        }

        if (json.stats) {
          const elToday = document.getElementById('statToday');
          const elActive = document.getElementById('statActive');
          if (elToday) elToday.textContent = json.stats.today;
          if (elActive) elActive.textContent = json.stats.active;
        }

        const passSrc = photoPayload || preview.src || '';

        lastPassData = json.data;
        document.getElementById('passPhoto').src = passSrc;
        document.getElementById('passCode').textContent = json.data.kode_kunjungan;
        document.getElementById('passName').textContent = json.data.nama_lengkap;
        document.getElementById('passInstansi').textContent = json.data.instansi;
        document.getElementById('passTujuan').textContent = json.data.tujuan;
        document.getElementById('passTime').textContent = json.data.waktu_masuk;

        renderQrCode(json.data.kode_kunjungan);

        successModal.classList.add('open');
        form.reset();
        retakePhoto();
      } catch (err) {
        showToast('Terjadi kesalahan jaringan. Coba lagi.', 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }

  function closeSuccessModal() {
    successModal.classList.remove('open');
    document.getElementById('passPhoto').src = '';
    if (passQrContainer) passQrContainer.replaceChildren();
    stopCameraStream();
    setStandbyView();
  }

  if (btnCloseModal && successModal) {
    btnCloseModal.addEventListener('click', closeSuccessModal);
    successModal.addEventListener('click', (e) => {
      if (e.target === successModal) {
        closeSuccessModal();
      }
    });
  }

  window.addEventListener('beforeunload', stopCameraStream);
  setStandbyView();
})();
