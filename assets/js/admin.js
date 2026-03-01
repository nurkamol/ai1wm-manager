/**
 * AI1WM Manager — Admin JavaScript  v4.0.0
 * Handles AJAX, toast notifications, modals, diff viewer, search, and UI helpers.
 */

/* global ai1wmManager */

(function () {
  'use strict';

  // ─── Config ──────────────────────────────────────────────────────────────

  const cfg = typeof ai1wmManager !== 'undefined' ? ai1wmManager : {};
  const ajaxUrl = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
  const nonce   = cfg.nonce   || '';
  const i18n    = cfg.i18n   || {};

  // ─── Public API object ───────────────────────────────────────────────────

  window.AI1WM = {
    ajax: ajax,
    toast: { success, error, warning, info },
    modal: { open: modalOpen, close: modalClose },
    toggleCard,
    escHtml,
  };

  // ─── AJAX helper ─────────────────────────────────────────────────────────

  function ajax(action, data, opts) {
    opts = opts || {};
    const formData = new FormData();
    formData.append('action', 'ai1wm_manager_' + action);
    formData.append('nonce', nonce);

    // Append flat key-value pairs and File objects
    for (const [key, value] of Object.entries(data || {})) {
      if (value instanceof File) {
        formData.append(key, value, value.name);
      } else if (value !== null && value !== undefined) {
        formData.append(key, value);
      }
    }

    // Handle nested objects (e.g. options, updates arrays)
    if (opts.nested) {
      for (const [key, value] of Object.entries(opts.nested)) {
        appendNested(formData, key, value);
      }
    }

    return fetch(ajaxUrl, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(result => {
        if (!result.success) {
          throw new Error(result.data?.message || 'Unknown error');
        }
        return result.data;
      });
  }

  function appendNested(formData, prefix, value) {
    if (Array.isArray(value)) {
      value.forEach(v => formData.append(prefix + '[]', v));
    } else if (value !== null && typeof value === 'object') {
      for (const [k, v] of Object.entries(value)) {
        appendNested(formData, prefix + '[' + k + ']', v);
      }
    } else {
      formData.append(prefix, value);
    }
  }

  // ─── Toast System ────────────────────────────────────────────────────────

  function showToast(message, type) {
    const icons = {
      success: '✓',
      error:   '✕',
      warning: '⚠',
      info:    'ℹ',
    };

    const container = document.getElementById('ai1wm-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'ai1wm-toast ai1wm-toast-' + type;
    toast.innerHTML =
      '<span class="ai1wm-toast-icon">' + (icons[type] || '') + '</span>' +
      '<span class="ai1wm-toast-message">' + escHtml(message) + '</span>';

    container.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add('show'));
    });

    // Auto-dismiss
    const timer = setTimeout(() => dismissToast(toast), 4500);
    toast.addEventListener('click', () => { clearTimeout(timer); dismissToast(toast); });
  }

  function dismissToast(toast) {
    toast.classList.remove('show');
    toast.classList.add('hiding');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }

  function success(msg) { showToast(msg, 'success'); }
  function error(msg)   { showToast(msg, 'error'); }
  function warning(msg) { showToast(msg, 'warning'); }
  function info(msg)    { showToast(msg, 'info'); }

  // ─── Modal System ────────────────────────────────────────────────────────

  function modalOpen(title, bodyHtml, buttons) {
    const overlay = document.getElementById('ai1wm-modal-overlay');
    const titleEl = document.getElementById('ai1wm-modal-title');
    const bodyEl  = document.getElementById('ai1wm-modal-body');
    const footerEl= document.getElementById('ai1wm-modal-footer');

    if (!overlay) return;

    titleEl.textContent = title;
    bodyEl.innerHTML    = bodyHtml;

    footerEl.innerHTML = '';
    (buttons || []).forEach(btn => {
      const el = document.createElement('button');
      el.type = 'button';
      el.textContent = btn.label;
      el.className = 'ai1wm-btn ' + (btn.cls || 'ai1wm-btn-secondary');
      el.addEventListener('click', btn.onClick);
      footerEl.appendChild(el);
    });

    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function modalClose() {
    const overlay = document.getElementById('ai1wm-modal-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  // Close modal on overlay click
  document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'ai1wm-modal-overlay') {
      modalClose();
    }
  });

  // Close modal on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') modalClose();
  });

  // ─── Card toggle ─────────────────────────────────────────────────────────

  function toggleCard(headerEl) {
    const body = headerEl.parentElement.querySelector('.ai1wm-card-body');
    const icon = headerEl.querySelector('.ai1wm-toggle-icon');
    if (!body) return;

    if (body.style.display === 'none') {
      body.style.display = '';
      if (icon) icon.classList.add('rotated');
    } else {
      body.style.display = 'none';
      if (icon) icon.classList.remove('rotated');
    }
  }

  // ─── Escape HTML helper ──────────────────────────────────────────────────

  function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
  }

  // ─── Set button loading state ────────────────────────────────────────────

  function setLoading(btn, loading, originalText) {
    if (loading) {
      btn._originalHTML = btn.innerHTML;
      btn.innerHTML = '<span class="ai1wm-spinner"></span> ' + (i18n.loading || 'Loading…');
      btn.disabled = true;
    } else {
      btn.innerHTML = originalText !== undefined ? originalText : btn._originalHTML || btn.innerHTML;
      btn.disabled = false;
    }
  }

  // ─── Extension Actions ───────────────────────────────────────────────────

  function handleBackupExtensions(btn) {
    setLoading(btn, true);
    ajax('backup_extensions', {})
      .then(data => {
        success(data.message);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleUpdateExtensions(btn) {
    const updates = {};
    let hasSelected = false;

    document.querySelectorAll('.ai1wm-ext-checkbox:checked').forEach(cb => {
      const prefix  = cb.dataset.prefix;
      const vInput  = document.querySelector('.ai1wm-ext-version-input[data-prefix="' + prefix + '"]');
      if (vInput) {
        updates[prefix] = vInput.value.trim();
        hasSelected = true;
      }
    });

    if (!hasSelected) {
      warning('Please select at least one extension to update.');
      return;
    }

    setLoading(btn, true);
    ajax('update_extensions', {}, { nested: { updates } })
      .then(data => {
        if (data.warnings && data.warnings.length) {
          data.warnings.forEach(w => warning(w));
        }
        if (data.errors && data.errors.length) {
          data.errors.forEach(e => error(e));
        }
        success(data.message);
        setTimeout(() => location.reload(), 1200);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleRevertExtensions(btn, backupKey) {
    if (!confirm(i18n.confirmRevert || 'Revert extension versions to this backup?')) return;
    setLoading(btn, true);
    ajax('revert_extensions', { backup_key: backupKey })
      .then(data => {
        success(data.message);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  // ─── Settings Actions ────────────────────────────────────────────────────

  function handleExportSettings(btn) {
    const redact   = document.getElementById('ai1wm-export-redact')?.checked ? '1' : '0';
    const metadata = document.getElementById('ai1wm-export-metadata')?.checked ? '1' : '0';

    setLoading(btn, true);
    ajax('export_settings', { redact, include_metadata: metadata })
      .then(data => {
        triggerDownload(data.filename, data.content);
        success(data.message);
        setLoading(btn, false);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleBackupSettings(btn) {
    setLoading(btn, true);
    ajax('backup_settings', {})
      .then(data => {
        success(data.message);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleDryRunImport(btn) {
    const fileInput = document.getElementById('ai1wm-import-file');
    if (!fileInput || !fileInput.files.length) {
      warning('Please select a JSON file first.');
      return;
    }

    setLoading(btn, true);
    const formData = new FormData();
    formData.append('action', 'ai1wm_manager_dry_run_import');
    formData.append('nonce', nonce);
    formData.append('json_file', fileInput.files[0], fileInput.files[0].name);

    fetch(ajaxUrl, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(result => {
        setLoading(btn, false);
        if (!result.success) throw new Error(result.data?.message || 'Error');
        renderDiffModal(result.data.diff, result.data.message);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleImportSettings(btn) {
    const fileInput = document.getElementById('ai1wm-import-file');
    if (!fileInput || !fileInput.files.length) {
      warning('Please select a JSON file first.');
      return;
    }

    setLoading(btn, true);
    const formData = new FormData();
    formData.append('action', 'ai1wm_manager_import_settings');
    formData.append('nonce', nonce);
    formData.append('json_file', fileInput.files[0], fileInput.files[0].name);

    fetch(ajaxUrl, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(result => {
        setLoading(btn, false);
        if (!result.success) throw new Error(result.data?.message || 'Error');
        success(result.data.message);
        if (result.data.warning) warning(result.data.warning);
        setTimeout(() => location.reload(), 1000);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleRestoreSettings(btn, backupKey) {
    setLoading(btn, true);
    // First fetch diff, then show restore modal
    ajax('download_backup', { backup_key: backupKey })
      .then(data => {
        setLoading(btn, false);
        const backup = JSON.parse(atob(data.content));
        const backupSettings = backup.settings || backup;

        // Build selectable list of settings keys
        const keys = Object.keys(backupSettings);
        let listHtml = '<p style="margin-top:0;"><strong>' + keys.length + '</strong> settings available. Select which to restore:</p>';
        listHtml += '<div style="margin-bottom:10px;"><label><input type="checkbox" id="ai1wm-restore-all" checked> <strong>Select All</strong></label></div>';
        listHtml += '<div class="ai1wm-restore-list">';
        keys.forEach(k => {
          const valPreview = typeof backupSettings[k] === 'object'
            ? '[object]'
            : String(backupSettings[k]).substring(0, 60);
          listHtml += '<label><input type="checkbox" class="ai1wm-restore-key" value="' + escHtml(k) + '" checked>';
          listHtml += '<span><strong>' + escHtml(k) + '</strong><code>' + escHtml(valPreview) + '</code></span></label>';
        });
        listHtml += '</div>';

        modalOpen(
          'Restore Settings from Backup',
          listHtml,
          [
            {
              label: 'Cancel',
              cls: 'ai1wm-btn-ghost',
              onClick: () => modalClose(),
            },
            {
              label: 'Restore Selected',
              cls: 'ai1wm-btn-primary',
              onClick: () => {
                const selected = Array.from(
                  document.querySelectorAll('.ai1wm-restore-key:checked')
                ).map(el => el.value);

                if (!selected.length) {
                  warning('No settings selected.');
                  return;
                }

                const confirmBtn = document.querySelector('#ai1wm-modal-footer .ai1wm-btn-primary');
                if (confirmBtn) setLoading(confirmBtn, true);

                const fd = new FormData();
                fd.append('action', 'ai1wm_manager_restore_settings');
                fd.append('nonce', nonce);
                fd.append('backup_key', backupKey);
                selected.forEach(k => fd.append('selected_keys[]', k));

                fetch(ajaxUrl, { method: 'POST', body: fd })
                  .then(r => r.json())
                  .then(result => {
                    modalClose();
                    if (!result.success) throw new Error(result.data?.message || 'Error');
                    success(result.data.message);
                    setTimeout(() => location.reload(), 800);
                  })
                  .catch(err => { error(err.message); if (confirmBtn) setLoading(confirmBtn, false); });
              },
            },
          ]
        );

        // Select-all checkbox logic
        const allCb = document.getElementById('ai1wm-restore-all');
        if (allCb) {
          allCb.addEventListener('change', () => {
            document.querySelectorAll('.ai1wm-restore-key').forEach(cb => {
              cb.checked = allCb.checked;
            });
          });
        }
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  // ─── Backup Actions ──────────────────────────────────────────────────────

  function handleRemoveBackup(btn, key) {
    if (!confirm(i18n.confirmDelete || 'Remove this backup?')) return;
    setLoading(btn, true);
    ajax('remove_backup', { backup_key: key })
      .then(data => {
        success(data.message);
        const item = btn.closest('.ai1wm-backup-item');
        if (item) item.remove();
        setLoading(btn, false);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleRemoveAllBackups(btn, type) {
    if (!confirm(i18n.confirmDeleteAll || 'Remove ALL backups?')) return;
    setLoading(btn, true);
    ajax('remove_all_backups', { type })
      .then(data => {
        success(data.message);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  function handleEditNote(btn, key, currentNote) {
    modalOpen(
      'Edit Backup Note',
      '<div class="ai1wm-field">' +
        '<label class="ai1wm-label" for="ai1wm-note-input">Note</label>' +
        '<input type="text" id="ai1wm-note-input" class="ai1wm-input" style="width:100%;margin-top:6px;" ' +
          'value="' + escHtml(currentNote || '') + '" maxlength="200" placeholder="Optional label for this backup">' +
      '</div>',
      [
        { label: 'Cancel', cls: 'ai1wm-btn-ghost', onClick: () => modalClose() },
        {
          label: 'Save Note',
          cls: 'ai1wm-btn-primary',
          onClick: () => {
            const note = document.getElementById('ai1wm-note-input').value;
            const saveBtn = document.querySelector('#ai1wm-modal-footer .ai1wm-btn-primary');
            if (saveBtn) setLoading(saveBtn, true);

            ajax('update_backup_note', { backup_key: key, note })
              .then(data => {
                modalClose();
                success(data.message);
                // Update note display in DOM
                const item = document.querySelector('.ai1wm-backup-item[data-key="' + key + '"]');
                if (item) {
                  let noteEl = item.querySelector('.ai1wm-backup-note');
                  if (note) {
                    if (!noteEl) {
                      noteEl = document.createElement('div');
                      noteEl.className = 'ai1wm-backup-note';
                      item.querySelector('.ai1wm-backup-meta').appendChild(noteEl);
                    }
                    noteEl.textContent = note;
                    // Update button dataset
                    btn.dataset.note = note;
                  } else if (noteEl) {
                    noteEl.remove();
                    btn.dataset.note = '';
                  }
                }
              })
              .catch(err => { error(err.message); if (saveBtn) setLoading(saveBtn, false); });
          },
        },
      ]
    );

    // Focus the input
    setTimeout(() => {
      const input = document.getElementById('ai1wm-note-input');
      if (input) { input.focus(); input.select(); }
    }, 100);
  }

  function handleDownloadBackup(btn, key) {
    setLoading(btn, true);
    ajax('download_backup', { backup_key: key })
      .then(data => {
        triggerDownload(data.filename, data.content);
        success(data.message);
        setLoading(btn, false);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  // ─── Plugin Options ──────────────────────────────────────────────────────

  function handleSaveOptions(btn) {
    const form = document.getElementById('ai1wm-options-form');
    if (!form) return;

    setLoading(btn, true);

    // Serialize form into nested options object
    const options = {};
    form.querySelectorAll('[name]').forEach(el => {
      const name = el.name;
      if (!name.startsWith('options[')) return;

      if (el.type === 'checkbox' && !el.checked) return;
      if (el.type === 'radio' && !el.checked) return;

      // Parse options[key] and options[key][]
      const arrayMatch = name.match(/^options\[([^\]]+)\]\[\]$/);
      const scalarMatch = name.match(/^options\[([^\]]+)\]$/);

      if (arrayMatch) {
        const k = arrayMatch[1];
        if (!options[k]) options[k] = [];
        options[k].push(el.value);
      } else if (scalarMatch) {
        options[scalarMatch[1]] = el.value;
      }
    });

    // Ensure notifications_enabled is present even when unchecked
    if (!options.notifications_enabled) options.notifications_enabled = '';

    ajax('save_options', {}, { nested: { options } })
      .then(data => {
        success(data.message);
        if (data.next_run) {
          // Update next-run display if present
          const el = document.querySelector('.ai1wm-field-desc [data-next-run]');
          if (el) el.textContent = data.next_run;
        }
        setLoading(btn, false);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  // ─── Activity Log ────────────────────────────────────────────────────────

  function handleClearActivityLog(btn) {
    if (!confirm(i18n.confirmClearLog || 'Clear the entire activity log?')) return;
    setLoading(btn, true);
    ajax('clear_activity_log', {})
      .then(data => {
        success(data.message);
        setTimeout(() => location.reload(), 800);
      })
      .catch(err => { error(err.message); setLoading(btn, false); });
  }

  // ─── Diff Modal ──────────────────────────────────────────────────────────

  function renderDiffModal(diff, summary) {
    const addedCount   = Object.keys(diff.added   || {}).length;
    const changedCount = Object.keys(diff.changed  || {}).length;
    const removedCount = Object.keys(diff.removed  || {}).length;

    let html = '<div class="ai1wm-diff-summary">';
    html += '<span class="ai1wm-badge ai1wm-badge-green">+ ' + addedCount + ' added</span>';
    html += '<span class="ai1wm-badge ai1wm-badge-orange">~ ' + changedCount + ' changed</span>';
    html += '<span class="ai1wm-badge ai1wm-badge-red">- ' + removedCount + ' removed</span>';
    html += '</div>';
    html += '<p style="color:#646970;font-size:12px;margin:0 0 14px;">' + escHtml(summary) + '</p>';

    if (addedCount + changedCount + removedCount === 0) {
      html += '<p>No differences found. Current settings match the file.</p>';
      modalOpen('Import Preview — No Changes', html, [
        { label: 'Close', cls: 'ai1wm-btn-secondary', onClick: () => modalClose() },
      ]);
      return;
    }

    html += '<table class="ai1wm-diff-table">';
    html += '<thead><tr><th>Key</th><th>Old Value</th><th>New Value</th></tr></thead>';
    html += '<tbody>';

    for (const [key, val] of Object.entries(diff.added || {})) {
      html += '<tr class="ai1wm-diff-added"><td class="ai1wm-diff-key">' + escHtml(key) + '</td>';
      html += '<td>—</td>';
      html += '<td>' + escHtml(truncate(JSON.stringify(val), 100)) + '</td></tr>';
    }

    for (const [key, item] of Object.entries(diff.changed || {})) {
      html += '<tr class="ai1wm-diff-changed"><td class="ai1wm-diff-key">' + escHtml(key) + '</td>';
      html += '<td>' + escHtml(truncate(JSON.stringify(item.old), 100)) + '</td>';
      html += '<td>' + escHtml(truncate(JSON.stringify(item.new), 100)) + '</td></tr>';
    }

    for (const [key, val] of Object.entries(diff.removed || {})) {
      html += '<tr class="ai1wm-diff-removed"><td class="ai1wm-diff-key">' + escHtml(key) + '</td>';
      html += '<td>' + escHtml(truncate(JSON.stringify(val), 100)) + '</td>';
      html += '<td>—</td></tr>';
    }

    html += '</tbody></table>';

    modalOpen('Import Preview — Changes', html, [
      { label: 'Close', cls: 'ai1wm-btn-secondary', onClick: () => modalClose() },
      {
        label: 'Proceed with Import',
        cls: 'ai1wm-btn-primary',
        onClick: () => {
          modalClose();
          const importBtn = document.getElementById('ai1wm-import-btn');
          if (importBtn) handleImportSettings(importBtn);
        },
      },
    ]);
  }

  // ─── Extension search & filter ───────────────────────────────────────────

  function initExtensionSearch() {
    const input = document.getElementById('ai1wm-ext-search');
    if (!input) return;

    input.addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      const cards = document.querySelectorAll('.ai1wm-ext-card');
      const noResults = document.getElementById('ai1wm-ext-no-results');
      let visible = 0;

      cards.forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        if (!q || name.includes(q)) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      if (noResults) {
        noResults.style.display = visible === 0 ? '' : 'none';
      }
    });
  }

  function initSelectAll() {
    const btn = document.getElementById('ai1wm-select-all-ext');
    if (!btn) return;
    let allSelected = false;
    btn.addEventListener('click', () => {
      allSelected = !allSelected;
      document.querySelectorAll('.ai1wm-ext-checkbox').forEach(cb => {
        cb.checked = allSelected;
        const card = cb.closest('.ai1wm-ext-card');
        if (card) card.classList.toggle('selected', allSelected);
      });
      btn.textContent = allSelected ? 'Deselect All' : 'Select All';
    });
  }

  // Update card selection highlight when individual checkbox changes
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('ai1wm-ext-checkbox')) return;
    const card = e.target.closest('.ai1wm-ext-card');
    if (card) card.classList.toggle('selected', e.target.checked);
  });

  // ─── Settings search ─────────────────────────────────────────────────────

  function initSettingsSearch() {
    const input = document.getElementById('ai1wm-settings-search');
    if (!input) return;
    input.addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      document.querySelectorAll('.ai1wm-setting-row').forEach(row => {
        const key = row.dataset.key || '';
        row.classList.toggle('hidden', q && !key.includes(q));
      });
    });
  }

  // ─── File input: drag & drop + preview ──────────────────────────────────

  function initFileInput() {
    const fileInput  = document.getElementById('ai1wm-import-file');
    const dropZone   = document.getElementById('ai1wm-import-drop');
    const fileNameEl = document.getElementById('ai1wm-import-file-name');
    const dryRunBtn  = document.getElementById('ai1wm-dry-run-btn');
    const importBtn  = document.getElementById('ai1wm-import-btn');

    if (!fileInput) return;

    function updateFile(file) {
      if (!file) return;
      if (fileNameEl) {
        fileNameEl.innerHTML = '✓ ' + escHtml(file.name) + ' (' + formatBytes(file.size) + ')';
        fileNameEl.style.display = '';
      }
      if (dryRunBtn) dryRunBtn.disabled = false;
      if (importBtn) importBtn.disabled = false;
    }

    fileInput.addEventListener('change', () => updateFile(fileInput.files[0]));

    if (dropZone) {
      dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
      });
      dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
      dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer?.files;
        if (files && files[0]) {
          const dt = new DataTransfer();
          dt.items.add(files[0]);
          fileInput.files = dt.files;
          updateFile(files[0]);
        }
      });
    }
  }

  // ─── Delegated click handler ─────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    e.preventDefault();

    const action = btn.dataset.action;
    const key    = btn.dataset.key || '';
    const type   = btn.dataset.type || 'all';
    const note   = btn.dataset.note || '';

    switch (action) {
      case 'backupExtensions':   handleBackupExtensions(btn); break;
      case 'updateExtensions':   handleUpdateExtensions(btn); break;
      case 'revertExtensions':   handleRevertExtensions(btn, key); break;
      case 'exportSettings':     handleExportSettings(btn); break;
      case 'backupSettings':     handleBackupSettings(btn); break;
      case 'importSettings':     handleImportSettings(btn); break;
      case 'restoreSettings':    handleRestoreSettings(btn, key); break;
      case 'removeBackup':       handleRemoveBackup(btn, key); break;
      case 'removeAllBackups':   handleRemoveAllBackups(btn, type); break;
      case 'editNote':           handleEditNote(btn, key, note); break;
      case 'downloadBackup':     handleDownloadBackup(btn, key); break;
      case 'saveOptions':        handleSaveOptions(btn); break;
      case 'clearActivityLog':   handleClearActivityLog(btn); break;
    }
  });

  // Dedicated buttons for dry-run and import (separate from delegated events)
  document.addEventListener('DOMContentLoaded', function () {
    const dryRunBtn = document.getElementById('ai1wm-dry-run-btn');
    const importBtn = document.getElementById('ai1wm-import-btn');

    if (dryRunBtn) {
      dryRunBtn.addEventListener('click', function () {
        handleDryRunImport(this);
      });
    }

    if (importBtn) {
      importBtn.addEventListener('click', function () {
        handleImportSettings(this);
      });
    }

    initExtensionSearch();
    initSelectAll();
    initSettingsSearch();
    initFileInput();
  });

  // ─── Utilities ───────────────────────────────────────────────────────────

  function triggerDownload(filename, base64Content) {
    const blob = base64ToBlob(base64Content, 'application/json');
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function base64ToBlob(base64, mimeType) {
    const binary = atob(base64);
    const bytes  = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return new Blob([bytes], { type: mimeType });
  }

  function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function truncate(str, max) {
    if (!str) return '';
    return str.length > max ? str.substring(0, max) + '…' : str;
  }

})();
