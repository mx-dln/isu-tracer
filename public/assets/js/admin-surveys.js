/* =====================================================================
   ISU IAT Tracer Study - Google-Forms-style survey builder
   Live type switching, option rows, drag-and-drop reorder, quick actions.
   ===================================================================== */
(function () {
    'use strict';

    const cfg = window.SURVEY_BUILDER || {};

    function setupTypePanels(root) {
        root.querySelectorAll('[data-question-form]').forEach(function (form) {
            const typeSelect = form.querySelector('[data-type-select]');
            const settings = form.querySelector('[data-type-settings]');
            if (!typeSelect || !settings) return;

            function applyType() {
                const type = typeSelect.value;
                let matched = false;
                settings.querySelectorAll('[data-panel]').forEach(function (panel) {
                    const allowed = (panel.dataset.panel || '').split('|');
                    const show = allowed.includes(type);
                    panel.style.display = show ? '' : 'none';
                    if (show) matched = true;
                });
                const emptyNote = settings.querySelector('[data-panel-empty]');
                if (emptyNote) emptyNote.style.display = matched ? 'none' : '';
            }
            typeSelect.addEventListener('change', applyType);
            applyType();
        });
    }

    function setupOptionRows(root) {
        root.querySelectorAll('[data-option-list]').forEach(function (list) {
            const addBtn = list.parentElement.querySelector('[data-add-option]');
            if (!addBtn) return;

            addBtn.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                row.dataset.optionRow = '';
                const count = list.querySelectorAll('[data-option-row]').length + 1;
                row.innerHTML =
                    '<i data-lucide="circle-dot" class="w-4 h-4 text-ink-300 shrink-0"></i>' +
                    '<input type="text" name="options[]" class="input" value="" placeholder="Option ' + count + '">' +
                    '<button type="button" class="icon-btn !text-red-500 hover:!bg-red-50" data-remove-option title="Remove option"><i data-lucide="x" class="w-4 h-4"></i></button>';
                list.appendChild(row);
                bindRemove(row.querySelector('[data-remove-option]'));
                if (window.lucide) lucide.createIcons({});
            });

            list.querySelectorAll('[data-remove-option]').forEach(bindRemove);
        });
    }

    function bindRemove(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            const row = btn.closest('[data-option-row]');
            if (row) row.remove();
        });
    }

    // ------------------------------------------------------------------
    // New-question "Cancel" clears the editor back to its defaults.
    // ------------------------------------------------------------------
    function setupClearQuestion(root) {
        root.querySelectorAll('[data-clear-question]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('[data-question-form]');
                if (!form) return;
                form.reset();
                const ta = form.querySelector('textarea[name="question_text"]');
                if (ta) ta.style.height = 'auto';
                const typeSelect = form.querySelector('[data-type-select]');
                if (typeSelect) typeSelect.dispatchEvent(new Event('change'));
            });
        });
    }

    // ------------------------------------------------------------------
    // Drag & drop reorder (sections and questions within a section).
    //
    // HANDLE-ONLY: the cards themselves are NOT draggable. A reorder may
    // only start from the dedicated grip handles (.question-drag-handle /
    // .section-drag-handle). Text selection, typing, selects, toggles and
    // buttons inside a card never initiate a drag.
    // ------------------------------------------------------------------
    function setupDnD() {
        let dragging = null;

        function serializeAndPost(container, url) {
            const ids = [];
            container.querySelectorAll(':scope > .builder-question, :scope > .builder-section').forEach(function (el) {
                const id = el.dataset.sectionId || el.dataset.questionId;
                if (id) ids.push(id);
            });
            if (!ids.length || !cfg.surveyId || !cfg.csrf) return;

            const body = new URLSearchParams();
            body.append('survey_id', cfg.surveyId);
            ids.forEach(function (id) { body.append('ids[]', id); });

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': cfg.csrf,
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body.toString(),
            }).then(function (res) {
                if (res.ok) {
                    window.toast('Order saved.', 'success');
                } else {
                    window.toast('Could not save order.', 'error');
                }
            }).catch(function () {
                window.toast('Could not save order.', 'error');
            });
        }

        function moveBefore(dragged, target, ev) {
            if (!dragged || !target || dragged === target) return false;
            if (!target.parentElement || dragged.parentElement !== target.parentElement) return false;
            const rect = target.getBoundingClientRect();
            const before = ev.clientY < rect.top + rect.height / 2;
            target.parentElement.insertBefore(dragged, before ? target : target.nextSibling);
            return true;
        }

        document.addEventListener('dragstart', function (e) {
            // A drag may only begin from a handle. Ignore drags that start
            // inside inputs, selects, buttons, or anywhere else in a card.
            const handle = e.target.closest ? e.target.closest('.question-drag-handle, .section-drag-handle') : null;
            if (!handle) return;

            const el = handle.closest('.builder-section, .builder-question');
            if (!el) return;
            dragging = el;
            el.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', el.dataset.sectionId || el.dataset.questionId); } catch (_) {}
        });

        document.addEventListener('dragover', function (e) {
            if (!dragging) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const target = e.target.closest('.builder-section, .builder-question');
            if (target && target !== dragging && target.parentElement === dragging.parentElement) {
                target.classList.add('drop-target');
            }
        });

        document.addEventListener('dragleave', function (e) {
            const t = e.target.closest('.drop-target');
            if (t) t.classList.remove('drop-target');
        });

        document.addEventListener('drop', function (e) {
            e.preventDefault();
            document.querySelectorAll('.drop-target').forEach(function (el) { el.classList.remove('drop-target'); });
            if (!dragging) return;

            const target = e.target.closest('.builder-section, .builder-question');
            if (target && moveBefore(dragging, target, e)) {
                if (dragging.classList.contains('builder-section')) {
                    serializeAndPost(document.getElementById('section-list'), cfg.sectionsReorderUrl);
                } else {
                    serializeAndPost(dragging.parentElement, cfg.questionsReorderUrl);
                }
            }
            dragging.classList.remove('dragging');
            dragging = null;
        });

        document.addEventListener('dragend', function () {
            document.querySelectorAll('.drop-target').forEach(function (el) { el.classList.remove('drop-target'); });
            if (dragging) { dragging.classList.remove('dragging'); dragging = null; }
        });
    }

    function setupQuickActions() {
        const anchor = document.getElementById('new-question-anchor');

        const scrollToNew = function (sectionId) {
            if (!anchor) return;
            const form = anchor.querySelector('[data-question-form]');
            if (form && sectionId) {
                const select = form.querySelector('[data-qsection]');
                if (select) select.value = String(sectionId);
            }
            anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const qtext = anchor.querySelector('[name="question_text"]');
            if (qtext) setTimeout(function () { qtext.focus(); }, 400);
        };

        document.querySelectorAll('[data-new-question]').forEach(function (btn) {
            btn.addEventListener('click', function () { scrollToNew(); });
        });
        document.querySelectorAll('[data-add-question-in]').forEach(function (btn) {
            btn.addEventListener('click', function () { scrollToNew(btn.dataset.addQuestionIn); });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-builder]');
        if (!root) return;
        setupTypePanels(root);
        setupOptionRows(root);
        setupClearQuestion(root);
        setupQuickActions();
        setupDnD();
    });
})();