  <div id="appToast" class="toast" role="status" aria-live="polite"></div>

  <script>
    (function updateWibClock() {
      const el = document.getElementById('liveWibClock');
      if (!el) return;
      const now = new Date();
      const opts = {
        timeZone: 'Asia/Jakarta',
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      };
      el.textContent = new Intl.DateTimeFormat('id-ID', opts).format(now) + ' WIB';
      setTimeout(updateWibClock, 1000);
    })();
  </script>
</body>
</html>
