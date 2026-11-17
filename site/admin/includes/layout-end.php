  </div>
</main>

<footer class="afoot">
  <?= e(s('business')) ?> &middot; website admin
</footer>

<script>
/* Collapsible service and job panels — but not when a tool button was clicked */
document.querySelectorAll('.svc__head, .job__head').forEach(function (h) {
  h.addEventListener('click', function (e) {
    if (e.target.closest('button, a, form')) return;
    var box = h.parentElement;
    box.classList.toggle('open');
    var chev = h.querySelector('.chev');
    if (chev) chev.textContent = box.classList.contains('open') ? 'Close' : 'Edit';
  });
});

/* Repeating rows: add and remove */
document.addEventListener('click', function (e) {
  var add = e.target.closest('[data-add-row]');
  if (add) {
    var host = add.previousElementSibling;
    if (!host || !host.hasAttribute('data-repeat')) return;
    var last = host.querySelector('.repeat-row:last-child');
    if (!last) return;
    var copy = last.cloneNode(true);
    copy.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    host.appendChild(copy);
    copy.querySelector('input').focus();
    return;
  }
  var addG = e.target.closest('[data-add-group]');
  if (addG) {
    var hostG = addG.previousElementSibling;
    if (!hostG || !hostG.hasAttribute('data-repeat')) return;
    var lastG = hostG.querySelector('.repeat-group:last-child');
    if (!lastG) return;
    var copyG = lastG.cloneNode(true);
    // renumber so the new block does not overwrite the one it was copied from
    var next = hostG.querySelectorAll('.repeat-group').length;
    copyG.querySelectorAll('input, textarea, select').forEach(function (i) {
      if (i.name) i.name = i.name.replace(/\[(\d+)\]/, '[' + next + ']');
      if (i.tagName === 'SELECT') i.selectedIndex = 0; else i.value = '';
    });
    hostG.appendChild(copyG);
    var first = copyG.querySelector('input, textarea');
    if (first) first.focus();
    return;
  }
  var rm = e.target.closest('[data-remove-row]');
  if (rm) {
    var row = rm.closest('.repeat-row, .repeat-group');
    if (!row) return;
    var parent = row.parentElement;
    if (parent.children.length > 1) row.remove();
    else row.querySelectorAll('input, textarea').forEach(function (i) { i.value = ''; });
  }
});

/* Icon dropdowns preview their choice */
document.querySelectorAll('.icon-pick .icon-select').forEach(function (sel) {
  sel.addEventListener('change', function () {
    var pv = sel.parentElement.querySelector('.icon-pick__preview');
    if (pv) pv.style.opacity = '.45';
  });
});

/* Confirm before anything destructive */
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  });
});

/* Preview every photo chosen for a new gallery job */
var file = document.getElementById('photo-input');
var prev = document.getElementById('photo-preview');
if (file && prev) {
  file.addEventListener('change', function () {
    prev.innerHTML = '';
    if (!file.files || !file.files.length) return;
    Array.prototype.forEach.call(file.files, function (f) {
      if (!/^image\//.test(f.type)) return;
      var img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      img.alt = f.name;
      prev.appendChild(img);
    });
    var p = document.createElement('p');
    p.className = 'hint';
    p.style.width = '100%';
    p.textContent = file.files.length + ' photo' + (file.files.length === 1 ? '' : 's') +
      ' selected. The first one becomes the cover.';
    prev.appendChild(p);
  });
}
</script>

<aside class="pv" id="pv" hidden>
  <div class="pv__bar">
    <strong>Live preview</strong>
    <span class="pv__state" id="pvState">Showing the website as saved</span>
    <span class="pv__modes">
      <button type="button" data-pv-mode="desktop">Desktop</button>
      <button type="button" data-pv-mode="phone">Phone</button>
    </span>
    <button type="button" class="pv__close" id="pvClose" aria-label="Close preview">&times;</button>
  </div>
  <div class="pv__stage" id="pvStage">
    <iframe id="pvFrame" title="Live preview" data-src="<?= e($PV_URL) ?>"></iframe>
  </div>
</aside>

<script>
/* Live preview pane: stays open (or closed) as you move between admin tabs */
(function () {
  var KEY = 'bww_pv', body = document.body,
      pane = document.getElementById('pv'), frame = document.getElementById('pvFrame'),
      stage = document.getElementById('pvStage'), state = document.getElementById('pvState'),
      btn = document.getElementById('pvOpen'), wide = window.matchMedia('(min-width: 1100px)');
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
  var mode = saved.mode === 'phone' ? 'phone' : 'desktop', timer = null, busy = false, again = null;

  function remember() { try { localStorage.setItem(KEY, JSON.stringify({ open: !pane.hidden, mode: mode })); } catch (e) {} }
  function url() { return frame.dataset.src + (frame.dataset.src.indexOf('?') < 0 ? '?' : '&') + 'bww_preview=1&t=' + Date.now(); }

  // Desktop: draw the page at laptop width and shrink it to fit. Phone: real phone width.
  function fit() {
    var w = mode === 'desktop' ? 1280 : 390,
        sc = Math.min(1, (stage.clientWidth - (mode === 'phone' ? 40 : 0)) / w);
    frame.style.width = w + 'px';
    frame.style.height = (stage.clientHeight / sc) + 'px';
    frame.style.transform = 'scale(' + sc + ')';
    frame.style.marginLeft = mode === 'phone' ? Math.max(0, (stage.clientWidth - w * sc) / 2) + 'px' : '0';
    document.querySelectorAll('[data-pv-mode]').forEach(function (b) { b.classList.toggle('on', b.dataset.pvMode === mode); });
  }
  function open() {
    if (!wide.matches) return;
    pane.hidden = false; body.classList.add('pv-on'); btn.setAttribute('aria-pressed', 'true');
    fit(); frame.src = url(); remember();
  }
  function close() {
    pane.hidden = true; body.classList.remove('pv-on'); btn.setAttribute('aria-pressed', 'false');
    frame.removeAttribute('src'); remember();
  }

  // Forms marked data-pv-live send their unsaved values as you type.
  function send(form) {
    if (pane.hidden) return;
    if (busy) { again = form; return; }
    busy = true; state.textContent = 'Updating…';
    var fd = new FormData(form);
    fd.set('_preview', '1');
    Array.from(fd.keys()).forEach(function (k) { if (fd.get(k) instanceof File) fd.delete(k); });
    fetch(location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function () {
        try { frame.dataset.y = frame.contentWindow.scrollY; } catch (e) {}
        frame.dataset.changed = '1';
        frame.src = url();
      })
      .catch(function () { busy = false; state.textContent = 'Preview could not update — your edits are still here, Save works as normal.'; });
  }
  document.querySelectorAll('form[data-pv-live]').forEach(function (form) {
    function later() { clearTimeout(timer); timer = setTimeout(function () { send(form); }, 600); }
    form.addEventListener('input', later);
    form.addEventListener('change', later);
    form.addEventListener('click', function (e) { if (e.target.closest('[data-remove-row]')) later(); });
  });

  frame.addEventListener('load', function () {
    if (!frame.getAttribute('src')) return;
    busy = false;
    state.textContent = frame.dataset.changed ? 'Showing your changes — not saved yet' : 'Showing the website as saved';
    try {
      var w = frame.contentWindow;
      w.scrollTo(0, +frame.dataset.y || 0); frame.dataset.y = 0;
      // keep showing unsaved changes when clicking around inside the preview
      w.document.querySelectorAll('a[href]').forEach(function (a) {
        var u = new URL(a.href, w.location.href);
        if (u.origin === location.origin && u.pathname.indexOf('/admin/') === -1) { u.searchParams.set('bww_preview', '1'); a.href = u.href; }
      });
    } catch (e) {}
    if (again) { var f = again; again = null; send(f); }
  });

  btn.addEventListener('click', function () { pane.hidden ? open() : close(); });
  document.getElementById('pvClose').addEventListener('click', close);
  document.querySelectorAll('[data-pv-mode]').forEach(function (b) {
    b.addEventListener('click', function () { mode = b.dataset.pvMode; fit(); remember(); });
  });
  window.addEventListener('resize', function () { if (!pane.hidden) { wide.matches ? fit() : close(); } });
  if (saved.open) open();
})();

/* Warn before leaving a page with unsaved changes; stop photos that are too big before they upload */
(function () {
  var dirty = false, limit = +document.body.dataset.uploadLimit || 0;
  document.querySelectorAll('main form[method="post"]').forEach(function (f) {
    f.addEventListener('input', function () { dirty = true; });
    f.addEventListener('change', function () { dirty = true; });
    f.addEventListener('submit', function (e) {
      var total = 0, big = null;
      f.querySelectorAll('input[type=file]').forEach(function (i) {
        Array.prototype.forEach.call(i.files || [], function (file) { total += file.size; if (limit && file.size > limit) big = file; });
      });
      if (limit && (big || total > limit)) {
        e.preventDefault();
        window.alert((big ? '"' + big.name + '" is ' : 'These photos are ') + ((big ? big.size : total) / 1048576).toFixed(1) +
          ' MB. The server accepts up to ' + (limit / 1048576).toFixed(1) + ' MB per save. Please choose a smaller photo (or fewer photos) and save again.');
        return;
      }
      if (!e.defaultPrevented) dirty = false;
    });
  });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
</body>
</html>
