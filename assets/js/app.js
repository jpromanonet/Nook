(function () {
  'use strict';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function initSidebar() {
    var shell = $('.app-shell');
    var toggle = $('#sidebar-toggle');
    var backdrop = $('.sidebar-backdrop');
    if (!shell || !toggle) return;
    function open() { shell.classList.add('sidebar-open'); toggle.setAttribute('aria-expanded', 'true'); }
    function close() { shell.classList.remove('sidebar-open'); toggle.setAttribute('aria-expanded', 'false'); }
    toggle.addEventListener('click', function () {
      shell.classList.contains('sidebar-open') ? close() : open();
    });
    if (backdrop) backdrop.addEventListener('click', close);
  }

  function resolveTheme(pref) {
    if (pref === 'dark' || pref === 'light') return pref;
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    return 'light';
  }

  function applyTheme(pref) {
    var root = document.documentElement;
    var theme = resolveTheme(pref);
    root.setAttribute('data-theme', theme);
    root.setAttribute('data-theme-pref', pref);
    try { localStorage.setItem('nook-theme', pref); } catch (e) {}
    return theme;
  }

  function initThemeToggle() {
    var root = document.documentElement;
    var pref = root.getAttribute('data-theme-pref') || 'system';
    try {
      var stored = localStorage.getItem('nook-theme');
      if (stored) pref = stored;
    } catch (e) {}
    applyTheme(pref);
    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      var onChange = function () {
        if ((root.getAttribute('data-theme-pref') || 'system') === 'system') applyTheme('system');
      };
      if (mq.addEventListener) mq.addEventListener('change', onChange);
      else if (mq.addListener) mq.addListener(onChange);
    }
    $$('[data-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var current = root.getAttribute('data-theme') || 'light';
        var next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        var url = btn.getAttribute('data-theme-url');
        var csrf = btn.getAttribute('data-csrf') || '';
        if (!url) return;
        var body = new URLSearchParams();
        body.set('_csrf', csrf);
        body.set('theme', next);
        fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }, body: body, credentials: 'same-origin' });
      });
    });
  }

  function openModal(el) { if (el) el.hidden = false; }
  function closeModal(el) { if (el) el.hidden = true; }

  function initQuickAdd() {
    var modal = $('#quickadd');
    $$('[data-open-quickadd]').forEach(function (btn) {
      btn.addEventListener('click', function () { openModal(modal); });
    });
    $$('[data-close-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () { closeModal(btn.closest('.modal-root')); });
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') $$('.modal-root').forEach(function (m) { m.hidden = true; });
      var tag = (ev.target && ev.target.tagName) || '';
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || ev.target.isContentEditable) return;
      if (ev.key === 'n' || ev.key === 'N' || ((ev.ctrlKey || ev.metaKey) && (ev.key === 'n' || ev.key === 'N'))) {
        ev.preventDefault();
        openModal(modal);
      }
      if ((ev.ctrlKey || ev.metaKey) && (ev.key === 'k' || ev.key === 'K')) {
        ev.preventDefault();
        openCommand();
      }
    });
    $$('.modal-root').forEach(function (root) {
      root.addEventListener('click', function (ev) {
        if (ev.target === root) closeModal(root);
      });
    });
  }

  function openCommand() {
    var pal = $('#command-palette');
    var input = $('#command-input');
    openModal(pal);
    if (input) {
      input.value = '';
      input.focus();
      renderCommand([]);
    }
  }

  function renderCommand(results) {
    var box = $('#command-results');
    if (!box) return;
    if (!results.length) {
      box.innerHTML = '<p class="muted tiny">Type to search.</p>';
      return;
    }
    box.innerHTML = results.map(function (r) {
      return '<a href="' + r.url + '"><strong>' + escapeHtml(r.title) + '</strong><div class="muted tiny">' + escapeHtml(r.subtitle || r.kind) + '</div></a>';
    }).join('');
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function initCommand() {
    var input = $('#command-input');
    var pal = $('#command-palette');
    if (!input || !pal) return;
    var timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      timer = setTimeout(function () {
        if (!q) { renderCommand([]); return; }
        var form = document.querySelector('.command-form');
        var base = (form && form.getAttribute('data-search-json')) || '';
        var joiner = base.indexOf('?') >= 0 ? '&' : '?';
        var searchJson = base + joiner + 'q=' + encodeURIComponent(q);
        fetch(searchJson, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
          .then(function (res) { return res.json(); })
          .then(function (data) { renderCommand(data.results || []); })
          .catch(function () { renderCommand([]); });
      }, 180);
    });
    var top = $('#global-search');
    if (top) {
      top.addEventListener('focus', function () { openCommand(); });
    }
  }

  initSidebar();
  initThemeToggle();
  initQuickAdd();
  initCommand();
})();
