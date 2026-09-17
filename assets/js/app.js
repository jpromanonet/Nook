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

  function initSortable() {
    $$('[data-sortable]').forEach(function (board) {
      var reorderUrl = board.getAttribute('data-reorder-url');
      var moveUrl = board.getAttribute('data-move-url');
      var csrf = board.getAttribute('data-csrf') || '';
      var parentId = board.getAttribute('data-parent-id') || '';
      var dragEl = null;

      function items() {
        return $$('[data-id]', board);
      }

      function clearMarks() {
        items().forEach(function (el) {
          el.classList.remove('drop-target', 'drop-before', 'drop-after', 'is-dragging');
        });
      }

      function post(url, body) {
        return fetch(url, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'fetch',
            'Accept': 'application/json'
          },
          body: body,
          credentials: 'same-origin'
        }).then(function (res) { return res.json().catch(function () { return { ok: false }; }); });
      }

      function saveOrder() {
        var ids = items().map(function (el) { return el.getAttribute('data-id'); });
        var body = new URLSearchParams();
        body.set('_csrf', csrf);
        body.set('parent_id', parentId);
        ids.forEach(function (id) { body.append('ids[]', id); });
        return post(reorderUrl, body);
      }

      function moveInto(itemId, folderId) {
        var body = new URLSearchParams();
        body.set('_csrf', csrf);
        body.set('id', itemId);
        body.set('folder_id', folderId === null || folderId === undefined ? '' : String(folderId));
        return post(moveUrl, body).then(function (data) {
          if (data && data.ok) window.location.reload();
        });
      }

      board.addEventListener('dragstart', function (ev) {
        if (ev.target && ev.target.closest && ev.target.closest('a') && !ev.target.closest('.drag-handle')) {
          // Allow dragging the card, not the browser link ghost.
          var tile = ev.target.closest('[data-id]');
          if (tile && board.contains(tile)) {
            dragEl = tile;
            tile.classList.add('is-dragging');
            try {
              ev.dataTransfer.effectAllowed = 'move';
              ev.dataTransfer.setData('text/plain', tile.getAttribute('data-id') || '');
            } catch (e) {}
          }
          return;
        }
        var tile = ev.target && ev.target.closest ? ev.target.closest('[data-id]') : null;
        if (!tile || !board.contains(tile)) return;
        dragEl = tile;
        tile.classList.add('is-dragging');
        try {
          ev.dataTransfer.effectAllowed = 'move';
          ev.dataTransfer.setData('text/plain', tile.getAttribute('data-id') || '');
        } catch (e) {}
      });

      board.addEventListener('dragend', function () {
        clearMarks();
        dragEl = null;
      });

      board.addEventListener('dragover', function (ev) {
        if (!dragEl) return;
        ev.preventDefault();
        var over = ev.target && ev.target.closest ? ev.target.closest('[data-id]') : null;
        clearMarks();
        dragEl.classList.add('is-dragging');
        if (!over || over === dragEl || !board.contains(over)) return;

        var folderId = over.getAttribute('data-drop-folder');
        var rect = over.getBoundingClientRect();
        var ratio = (ev.clientY - rect.top) / Math.max(rect.height, 1);
        if (folderId && dragEl.getAttribute('data-id') !== folderId && ratio > 0.22 && ratio < 0.78) {
          over.classList.add('drop-target');
          return;
        }

        var before = ratio < 0.5;
        over.classList.add(before ? 'drop-before' : 'drop-after');
        if (before) board.insertBefore(dragEl, over);
        else board.insertBefore(dragEl, over.nextSibling);
      });

      board.addEventListener('drop', function (ev) {
        ev.preventDefault();
        if (!dragEl) return;
        var over = ev.target && ev.target.closest ? ev.target.closest('[data-id]') : null;
        var folderId = over && over.getAttribute('data-drop-folder');
        if (folderId && dragEl.getAttribute('data-id') !== folderId && over.classList.contains('drop-target')) {
          var id = dragEl.getAttribute('data-id');
          clearMarks();
          moveInto(id, folderId);
          dragEl = null;
          return;
        }
        clearMarks();
        saveOrder();
        dragEl = null;
      });
    });
  }

  function initBack() {
    $$('[data-back]').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        var fallback = btn.getAttribute('href') || btn.getAttribute('data-fallback') || '';
        var ref = document.referrer || '';
        var sameOrigin = false;
        try {
          sameOrigin = ref !== '' && new URL(ref).origin === window.location.origin
            && new URL(ref).href.split('#')[0] !== window.location.href.split('#')[0];
        } catch (e) {}
        if (sameOrigin && window.history.length > 1) {
          ev.preventDefault();
          window.history.back();
          return;
        }
        if (!fallback) {
          ev.preventDefault();
          window.location.href = '/home';
        }
      });
    });
  }

  function initChunkUploads() {
    var CHUNK = 1024 * 1024; // 1 MB — under server upload_max_filesize (2M)

    $$('form[data-chunk-upload]').forEach(function (form) {
      form.addEventListener('submit', function (ev) {
        if (form.getAttribute('data-chunking') === '1') return;
        var input = form.querySelector('input[type="file"][name="upload"]');
        if (!input || !input.files || !input.files.length) return;
        var file = input.files[0];
        if (!file || file.size <= CHUNK) return;

        ev.preventDefault();
        ev.stopPropagation();
        form.setAttribute('data-chunking', '1');

        var btn = form.querySelector('button[type="submit"]');
        var originalLabel = btn ? btn.textContent : '';
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Uploading… 0%';
        }

        var uploadUrl = form.getAttribute('data-chunk-upload');
        var csrfInput = form.querySelector('input[name="_csrf"]');
        var csrf = csrfInput ? csrfInput.value : '';
        var uploadId = (window.crypto && crypto.randomUUID)
          ? crypto.randomUUID().replace(/-/g, '')
          : String(Date.now()) + Math.random().toString(16).slice(2);
        var total = Math.ceil(file.size / CHUNK);
        var index = 0;

        function fail(msg) {
          form.removeAttribute('data-chunking');
          if (btn) {
            btn.disabled = false;
            btn.textContent = originalLabel || 'Save';
          }
          alert(msg || 'No se pudo subir el archivo.');
        }

        function sendNext() {
          if (index >= total) {
            var idField = form.querySelector('[data-chunk-id]');
            if (idField) idField.value = uploadId;
            input.value = '';
            if (btn) btn.textContent = 'Saving…';
            HTMLFormElement.prototype.submit.call(form);
            return;
          }
          var start = index * CHUNK;
          var blob = file.slice(start, Math.min(start + CHUNK, file.size));
          var body = new FormData();
          body.set('_csrf', csrf);
          body.set('upload_id', uploadId);
          body.set('index', String(index));
          body.set('total', String(total));
          body.set('filename', file.name);
          body.set('chunk', blob, file.name + '.part' + index);

          fetch(uploadUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
          }).then(function (res) {
            return res.json().then(function (data) {
              if (!res.ok || !data || !data.ok) {
                throw new Error((data && data.error) || 'Error al subir fragmento');
              }
              index += 1;
              if (btn) {
                var pct = Math.min(99, Math.round((index / total) * 100));
                btn.textContent = 'Uploading… ' + pct + '%';
              }
              sendNext();
            });
          }).catch(function (err) {
            fail(err && err.message ? err.message : 'Error de red al subir');
          });
        }

        sendNext();
      });
    });
  }

  function initBoard() {
    var board = $('[data-board]');
    if (!board) return;
    var moveUrl = board.getAttribute('data-move-url');
    var csrf = board.getAttribute('data-csrf') || '';
    var dragCard = null;

    board.addEventListener('dragstart', function (ev) {
      var card = ev.target && ev.target.closest ? ev.target.closest('.board-card') : null;
      if (!card || !board.contains(card)) return;
      if (ev.target.closest && ev.target.closest('a')) {
        // still allow card drag
      }
      dragCard = card;
      card.classList.add('is-dragging');
      try {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', card.getAttribute('data-id') || '');
      } catch (e) {}
    });

    board.addEventListener('dragend', function () {
      $$('.task-board-cell.is-over', board).forEach(function (el) { el.classList.remove('is-over'); });
      if (dragCard) dragCard.classList.remove('is-dragging');
      dragCard = null;
    });

    board.addEventListener('dragover', function (ev) {
      if (!dragCard) return;
      var cell = ev.target && ev.target.closest ? ev.target.closest('.task-board-cell') : null;
      if (!cell || !board.contains(cell)) return;
      ev.preventDefault();
      $$('.task-board-cell.is-over', board).forEach(function (el) {
        if (el !== cell) el.classList.remove('is-over');
      });
      cell.classList.add('is-over');
    });

    board.addEventListener('drop', function (ev) {
      ev.preventDefault();
      if (!dragCard) return;
      var cell = ev.target && ev.target.closest ? ev.target.closest('.task-board-cell') : null;
      $$('.task-board-cell.is-over', board).forEach(function (el) { el.classList.remove('is-over'); });
      if (!cell || !board.contains(cell)) return;
      var status = cell.getAttribute('data-status') || 'todo';
      var workspace = cell.getAttribute('data-workspace') || '';
      var id = dragCard.getAttribute('data-id');
      cell.appendChild(dragCard);
      dragCard.classList.remove('is-dragging');

      var body = new URLSearchParams();
      body.set('_csrf', csrf);
      body.set('id', id);
      body.set('status', status);
      body.set('workspace_id', workspace);
      fetch(moveUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function (res) { return res.json().catch(function () { return { ok: false }; }); })
        .then(function (data) {
          if (!data || !data.ok) {
            window.location.reload();
          }
        }).catch(function () { window.location.reload(); });
      dragCard = null;
    });
  }

  function initWorkspaceSort() {
    var list = $('[data-ws-sortable]');
    if (!list) return;
    var reorderUrl = list.getAttribute('data-reorder-url');
    var csrf = list.getAttribute('data-csrf') || '';
    var dragEl = null;

    function rows() {
      return $$('.ws-row[data-id]', list);
    }

    function clearMarks() {
      rows().forEach(function (el) {
        el.classList.remove('is-dragging', 'drop-before', 'drop-after');
      });
    }

    function saveOrder() {
      var body = new URLSearchParams();
      body.set('_csrf', csrf);
      rows().forEach(function (el) {
        body.append('ids[]', el.getAttribute('data-id'));
      });
      return fetch(reorderUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
        body: body,
        credentials: 'same-origin'
      }).then(function (res) { return res.json().catch(function () { return { ok: false }; }); });
    }

    list.addEventListener('dragstart', function (ev) {
      var row = ev.target && ev.target.closest ? ev.target.closest('.ws-row[data-id]') : null;
      if (!row || !list.contains(row)) return;
      if (ev.target.closest && ev.target.closest('a') && !ev.target.closest('.ws-drag')) {
        // allow dragging from handle or row chrome
      }
      dragEl = row;
      row.classList.add('is-dragging');
      try {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
      } catch (e) {}
    });

    list.addEventListener('dragend', function () {
      clearMarks();
      dragEl = null;
    });

    list.addEventListener('dragover', function (ev) {
      if (!dragEl) return;
      ev.preventDefault();
      var over = ev.target && ev.target.closest ? ev.target.closest('.ws-row[data-id]') : null;
      clearMarks();
      dragEl.classList.add('is-dragging');
      if (!over || over === dragEl || !list.contains(over)) return;
      var rect = over.getBoundingClientRect();
      var before = (ev.clientY - rect.top) < rect.height / 2;
      over.classList.add(before ? 'drop-before' : 'drop-after');
      if (before) list.insertBefore(dragEl, over);
      else list.insertBefore(dragEl, over.nextSibling);
    });

    list.addEventListener('drop', function (ev) {
      ev.preventDefault();
      if (!dragEl) return;
      clearMarks();
      saveOrder();
      dragEl = null;
    });
  }

  initSidebar();
  initThemeToggle();
  initQuickAdd();
  initCommand();
  initSortable();
  initBack();
  initChunkUploads();
  initBoard();
  initWorkspaceSort();
})();
