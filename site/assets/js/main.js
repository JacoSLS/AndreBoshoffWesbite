/* Freeland Well and Water Services — small, dependency-free front-end behaviour. */
(function () {
  'use strict';

  /* ---- mobile navigation ---- */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('mainnav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    nav.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---- sticky header shadow ---- */
  var header = document.querySelector('.header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-stuck', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---- reveal on scroll ---- */
  var reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
        });
      }, { rootMargin: '0px 0px -40px 0px', threshold: 0.05 });
      reveals.forEach(function (el) { io.observe(el); });
    } else {
      reveals.forEach(function (el) { el.classList.add('is-visible'); });
    }
  }

  /* ---- gallery: rotate the photos on multi-photo posts ---- */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('.post__media[data-rotate="1"]').forEach(function (media) {
    var imgs = media.querySelectorAll('.post__img');
    var dots = media.querySelectorAll('.post__dots i');
    if (imgs.length < 2 || reduce) return;
    var i = 0;
    var delay = parseFloat(getComputedStyle(media).getPropertyValue('--delay')) || 0;
    // the other photos are only downloaded just before they are shown
    var load = function (n) {
      var im = imgs[n];
      if (im && im.dataset.src) { im.loading = 'eager'; im.src = im.dataset.src; im.removeAttribute('data-src'); }
      else if (im && im.loading === 'lazy') im.loading = 'eager';   // hidden photos never start loading while lazy
    };
    // Move to the next photo only once it has loaded; a photo that fails to load is skipped.
    var step = function () {
      for (var t = 1; t < imgs.length; t++) {
        var n = (i + t) % imgs.length, im = imgs[n];
        load(n);
        if (!im.complete) return;          // still loading: keep the current photo, try again next time
        if (!im.naturalWidth) continue;    // broken: skip it
        imgs[i].classList.remove('is-active');
        if (dots[i]) dots[i].classList.remove('on');
        i = n;
        im.classList.add('is-active');
        if (dots[i]) dots[i].classList.add('on');
        load((i + 1) % imgs.length);
        return;
      }
    };
    // Start once the page has fully loaded (so the first photo counts as the page's
    // main image for Google's speed test), staggered so the grid does not flip in unison.
    var start = function () {
      setTimeout(function () {
        load(1);
        setTimeout(function () { step(); setInterval(step, 4000); }, 1500);
      }, 3500 + delay * 1000);
    };
    if (document.readyState === 'complete') start(); else window.addEventListener('load', start);
  });

  /* ---- gallery filtering ---- */
  var filters = document.querySelectorAll('.filter-btn');
  var posts = document.querySelectorAll('.post[data-post]');
  if (filters.length && posts.length) {
    filters.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cat = btn.getAttribute('data-filter');
        filters.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        var shown = 0;
        posts.forEach(function (p) {
          var match = cat === 'all' || p.getAttribute('data-category') === cat;
          p.style.display = match ? '' : 'none';
          if (match) shown++;
        });
        var none = document.getElementById('gallery-none');
        if (none) none.style.display = shown ? 'none' : '';
      });
    });
  }

  /* ---- job viewer: full detail, all photos, prev/next ---- */
  var viewer = document.getElementById('viewer');
  var dataEl = document.getElementById('post-data');
  if (viewer && dataEl && posts.length) {
    var DATA = {};
    try {
      JSON.parse(dataEl.textContent).forEach(function (p) { DATA[p.id] = p; });
    } catch (e) { DATA = {}; }

    var vImg     = document.getElementById('viewer-img');
    var vThumbs  = document.getElementById('viewer-thumbs');
    var vTitle   = document.getElementById('viewer-title');
    var vCaption = document.getElementById('viewer-caption');
    var vBody    = document.getElementById('viewer-body');
    var vTag     = document.getElementById('viewer-tag');
    var vCount   = document.getElementById('viewer-counter');
    var prevBtn  = viewer.querySelector('.viewer__nav--prev');
    var nextBtn  = viewer.querySelector('.viewer__nav--next');
    var current = null, idx = 0, lastFocus = null;

    function paint() {
      if (!current || !current.photos.length) return;
      vImg.src = current.photos[idx];
      vImg.alt = current.title || '';
      vCount.textContent = current.photos.length > 1 ? (idx + 1) + ' / ' + current.photos.length : '';
      vThumbs.querySelectorAll('img').forEach(function (t, k) { t.classList.toggle('on', k === idx); });
    }

    function open(id) {
      current = DATA[id];
      if (!current) return;
      idx = 0;
      lastFocus = document.activeElement;

      vTitle.textContent   = current.title || '';
      vCaption.textContent = current.caption || '';
      vTag.textContent     = current.tag || '';

      vBody.innerHTML = '';
      (current.body || '').split(/\n\s*\n/).forEach(function (para) {
        if (!para.trim()) return;
        var p = document.createElement('p');
        p.textContent = para.trim();
        vBody.appendChild(p);
      });

      vThumbs.innerHTML = '';
      var multi = current.photos.length > 1;
      vThumbs.style.display = multi ? '' : 'none';
      prevBtn.style.display = nextBtn.style.display = multi ? '' : 'none';
      if (multi) {
        current.photos.forEach(function (src, k) {
          var t = document.createElement('img');
          t.src = src; t.alt = '';
          t.addEventListener('click', function () { idx = k; paint(); });
          vThumbs.appendChild(t);
        });
      }

      paint();
      viewer.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      background(true);
      viewer.querySelector('.viewer__close').focus();
    }

    function close() {
      viewer.classList.remove('is-open');
      document.body.style.overflow = '';
      background(false);
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    // While the viewer is open, the rest of the page cannot be reached with the keyboard.
    function background(off) {
      for (var node = viewer; node && node !== document.body; node = node.parentElement) {
        Array.prototype.forEach.call(node.parentElement.children, function (el) {
          if (el !== node && el.tagName !== 'SCRIPT') el.inert = off;
        });
      }
    }
    function move(d) {
      if (!current || current.photos.length < 2) return;
      idx = (idx + d + current.photos.length) % current.photos.length;
      paint();
    }

    posts.forEach(function (p) {
      var id = p.getAttribute('data-post');
      p.addEventListener('click', function () { open(id); });
    });

    prevBtn.addEventListener('click', function (e) { e.stopPropagation(); move(-1); });
    nextBtn.addEventListener('click', function (e) { e.stopPropagation(); move(1); });
    viewer.addEventListener('click', function (e) {
      if (e.target === viewer || e.target.classList.contains('viewer__close')) close();
    });
    document.addEventListener('keydown', function (e) {
      if (!viewer.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') move(-1);
      if (e.key === 'ArrowRight') move(1);
      if (e.key === 'Tab') {   // keep Tab and Shift+Tab inside the viewer
        var f = Array.prototype.filter.call(viewer.querySelectorAll('button, a[href]'), function (el) { return el.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && (document.activeElement === first || !viewer.contains(document.activeElement))) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
  }

  /* ---- reviews: shortened ones open in full when tapped, and close again ---- */
  document.querySelectorAll('.review--more').forEach(function (card) {
    function toggle() {
      var open = card.getAttribute('aria-expanded') !== 'true';
      card.setAttribute('aria-expanded', open);
      card.querySelector('.review__short').hidden = open;
      card.querySelector('.review__full').hidden = !open;
    }
    card.addEventListener('click', toggle);
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
  });

  /* ---- contact form: client-side nicety only, server still validates ---- */
  var form = document.querySelector('form[data-validate]');
  if (form) {
    form.addEventListener('submit', function (e) {
      var ok = true;
      form.querySelectorAll('[required]').forEach(function (f) {
        if (!f.value.trim()) { ok = false; f.style.borderColor = '#c0392b'; }
        else { f.style.borderColor = ''; }
      });
      if (!ok) { e.preventDefault(); var first = form.querySelector('[required]'); if (first) first.focus(); }
    });
  }
})();
