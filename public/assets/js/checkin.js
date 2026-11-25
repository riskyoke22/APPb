(() => {
  const video = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  const preview = document.getElementById('capturedPreview');
  const faceOverlay = document.getElementById('faceOverlay');
  const statusBadge = document.getElementById('cameraStatusText');
  const countdownEl = document.getElementById('countdownOverlay');
  const shutterFlash = document.getElementById('shutterFlash');
  const fallbackBox = document.getElementById('cameraFallback');
  const fallbackInput = document.getElementById('fotoFileFallback');

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

  let currentStream = null;
  let facingMode = 'user';
  let isCaptured = false;
  let fallbackObjectUrl = null;
  let fallbackReadPromise = null;
  let modalObjectUrl = null;

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

  async function startCamera() {
    if (currentStream) {
      currentStream.getTracks().forEach((t) => t.stop());
      currentStream = null;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      if (statusBadge) statusBadge.textContent = 'Kamera butuh HTTPS / upload manual';
      if (fallbackBox) fallbackBox.style.display = 'block';
      if (btnCapture) btnCapture.disabled = true;
      if (btnTimerCapture) btnTimerCapture.disabled = true;
      return;
    }

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
      if (statusBadge) statusBadge.textContent = 'Kamera Aktif';
      if (fallbackBox) fallbackBox.style.display = 'none';
      if (btnCapture) btnCapture.disabled = false;
      if (btnTimerCapture) btnTimerCapture.disabled = false;
    } catch (err) {
      if (statusBadge) statusBadge.textContent = 'Akses kamera ditolak';
      if (fallbackBox) fallbackBox.style.display = 'block';
      if (btnCapture) btnCapture.disabled = true;
      if (btnTimerCapture) btnTimerCapture.disabled = true;
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

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');

    if (facingMode === 'user') {
      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
    }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
    fotoBase64Input.value = dataUrl;
    preview.src = dataUrl;

    preview.style.display = 'block';
    video.style.display = 'none';
    if (faceOverlay) faceOverlay.style.display = 'none';
    if (statusBadge) statusBadge.textContent = 'Foto Tersimpan';

    btnCapture.style.display = 'none';
    btnTimerCapture.style.display = 'none';
    btnSwitchCam.style.display = 'none';
    btnRetake.style.display = 'inline-flex';
    isCaptured = true;
  }

  function retakePhoto() {
    isCaptured = false;
    fotoBase64Input.value = '';
    if (fallbackInput) fallbackInput.value = '';
    fallbackReadPromise = null;
    if (fallbackObjectUrl && fallbackObjectUrl !== modalObjectUrl) {
      revokeUrl(fallbackObjectUrl);
    }
    fallbackObjectUrl = null;

    preview.style.display = 'none';
    if (statusBadge) statusBadge.textContent = currentStream ? 'Kamera Aktif' : 'Mode Upload';

    if (currentStream) {
      video.style.display = 'block';
      if (faceOverlay) faceOverlay.style.display = 'flex';
      btnCapture.style.display = 'inline-flex';
      btnTimerCapture.style.display = 'inline-flex';
      btnSwitchCam.style.display = 'inline-flex';
    } else {
      video.style.display = 'none';
      if (faceOverlay) faceOverlay.style.display = 'none';
      btnCapture.style.display = 'none';
      btnTimerCapture.style.display = 'none';
      btnSwitchCam.style.display = 'none';
      if (fallbackBox) fallbackBox.style.display = 'block';
    }
    btnRetake.style.display = 'none';
  }

  if (btnCapture) {
    btnCapture.addEventListener('click', capturePhoto);
  }

  if (btnTimerCapture) {
    btnTimerCapture.addEventListener('click', () => {
      if (!video || !video.videoWidth) return;
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
    return new Promise((resolve) => {
      const img = new Image();
      const tempUrl = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(tempUrl);
        const maxDim = 1280;
        let { width, height } = img;
        if (width > maxDim || height > maxDim) {
          const ratio = Math.min(maxDim / width, maxDim / height);
          width = Math.round(width * ratio);
          height = Math.round(height * ratio);
        }
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        resolve(canvas.toDataURL('image/jpeg', 0.88));
      };
      img.onerror = () => {
        URL.revokeObjectURL(tempUrl);
        resolve('');
      };
      img.src = tempUrl;
    });
  }

  if (fallbackInput) {
    fallbackInput.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (!file) {
        fallbackReadPromise = null;
        return;
      }

      revokeUrl(fallbackObjectUrl);
      fallbackObjectUrl = URL.createObjectURL(file);
      preview.src = fallbackObjectUrl;
      preview.style.display = 'block';
      video.style.display = 'none';
      if (faceOverlay) faceOverlay.style.display = 'none';
      if (statusBadge) statusBadge.textContent = 'Foto Tersimpan';
      btnCapture.style.display = 'none';
      btnTimerCapture.style.display = 'none';
      btnSwitchCam.style.display = 'none';
      btnRetake.style.display = 'inline-flex';
      isCaptured = true;

      fallbackReadPromise = compressImageFile(file).then((dataUrl) => {
        fotoBase64Input.value = dataUrl || '';
      });
    });
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (fallbackReadPromise) {
        await fallbackReadPromise;
      }

      const fallbackFile = fallbackInput?.files?.[0] || null;
      if (!fotoBase64Input.value && !fallbackFile) {
        showToast('Silakan ambil foto wajah terlebih dahulu sebagai bukti kehadiran.', 'error');
        return;
      }

      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Menyimpan Data...';

      try {
        const formData = new FormData(form);
        // Send foto_file only if base64 conversion did not produce a value
        if (!fotoBase64Input.value && fallbackFile) {
          formData.append('foto_file', fallbackFile);
        }

        const res = await fetch('/api/checkin', {
          method: 'POST',
          body: formData,
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
          showToast(json.message || 'Gagal menyimpan data kunjungan.', 'error');
          return;
        }

        // Update live stats
        if (json.stats) {
          const elToday = document.getElementById('statToday');
          const elActive = document.getElementById('statActive');
          if (elToday) elToday.textContent = json.stats.today;
          if (elActive) elActive.textContent = json.stats.active;
        }

        // Populate success modal using local photo (base64 or object URL)
        revokeUrl(modalObjectUrl);
        modalObjectUrl = null;
        let passSrc = fotoBase64Input.value || '';
        if (!passSrc && fallbackFile) {
          modalObjectUrl = URL.createObjectURL(fallbackFile);
          passSrc = modalObjectUrl;
        } else if (!passSrc) {
          passSrc = preview.src || '';
        }

        document.getElementById('passPhoto').src = passSrc;
        document.getElementById('passCode').textContent = json.data.kode_kunjungan;
        document.getElementById('passName').textContent = json.data.nama_lengkap;
        document.getElementById('passInstansi').textContent = json.data.instansi;
        document.getElementById('passTujuan').textContent = json.data.tujuan;
        document.getElementById('passTime').textContent = json.data.waktu_masuk;

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
    revokeUrl(modalObjectUrl);
    modalObjectUrl = null;
  }

  if (btnCloseModal && successModal) {
    btnCloseModal.addEventListener('click', closeSuccessModal);
    successModal.addEventListener('click', (e) => {
      if (e.target === successModal) {
        closeSuccessModal();
      }
    });
  }

  startCamera();
})();
