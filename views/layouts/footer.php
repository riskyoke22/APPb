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

    (function initSmoothPageTransitions() {
      const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      window.bdiNavigateWithTransition = function (url) {
        if (!url) return;
        if (prefersReduced) {
          window.location.href = url;
          return;
        }
        document.body.classList.add('is-leaving');
        setTimeout(() => {
          window.location.href = url;
        }, 180);
      };

      window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
          document.body.classList.remove('is-leaving');
        }
      });

      document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;

        const rawHref = link.getAttribute('href') || '';
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;
        if (rawHref.startsWith('/admin/export')) return;

        let targetUrl;
        try {
          targetUrl = new URL(link.href, window.location.origin);
        } catch {
          return;
        }

        if (targetUrl.origin !== window.location.origin) return;
        if (targetUrl.href === window.location.href) return;

        e.preventDefault();
        window.bdiNavigateWithTransition(targetUrl.href);
      });
    })();
  </script>
</body>
</html>
