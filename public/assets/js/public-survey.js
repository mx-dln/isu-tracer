/* =====================================================================
   ISU IAT Tracer Study - Public survey (no-login) interaction
   Wizard steps, progress bar, star/scale/likert controls, client checks.
   ===================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------------------------
    // Question control bindings used on BOTH the public (no-login) survey
    // and the authenticated graduate fill page: star rating + chip visuals.
    // Runs whenever the controls are present (no wizard required).
    // ------------------------------------------------------------------
    function bindControls() {
        // Star rating
        document.querySelectorAll('[data-rating]').forEach(function (box) {
            const starsInput = box.querySelector('input[type="hidden"]');
            const starBtns = Array.from(box.querySelectorAll('[data-star]'));
            starBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const n = Number(btn.dataset.star);
                    starsInput.value = String(n);
                    starBtns.forEach(function (s, i) {
                        s.classList.toggle('active', i < n);
                    });
                });
            });
        });

        // Chip visual state (scale / likert / single)
        document.querySelectorAll('[data-scale], [data-likert], [data-single]').forEach(function (group) {
            group.addEventListener('change', function (e) {
                if (e.target.type !== 'radio') return;
                const name = e.target.name;
                group.querySelectorAll('input[type="radio"][name="' + name + '"]').forEach(function (r) {
                    r.closest('label')?.classList.toggle('active', r.checked);
                });
            });
        });
    }

    function init() {
        bindControls();

        const wizard = document.querySelector('[data-survey-wizard]');
        if (!wizard) return;

        const form = document.getElementById('survey-form');
        const steps = Array.from(wizard.querySelectorAll('.survey-step'));
        const nextBtn = wizard.querySelector('[data-nav="next"]');
        const prevBtn = wizard.querySelector('[data-nav="prev"]');
        const submitBtn = wizard.querySelector('[data-nav="submit"]');
        const bar = document.getElementById('progress-bar');
        const label = document.getElementById('progress-label');
        const percent = document.getElementById('progress-percent');
        const dots = Array.from(wizard.querySelectorAll('[data-dot]'));

        let current = 0;
        const total = steps.length;

        function setError(el, msg) {
            const err = el.parentElement?.querySelector('.q-error');
            if (!err) return;
            if (msg) {
                err.textContent = msg;
                err.classList.remove('hidden');
            } else {
                err.textContent = '';
                err.classList.add('hidden');
            }
        }

        function questionValue(qBlock, name) {
            // Return selected value(s) for a question block.
            const input = qBlock.querySelector('input[name="' + name + '"]');
            const radio = qBlock.querySelector('input[name="' + name + '"]:checked');
            if (radio) return radio.value;
            const hidden = qBlock.querySelector('input[type="hidden"][name="' + name + '"]');
            if (hidden) return hidden.value;
            return input ? input.value : null;
        }

        function dataAttr(input) {
            return input.closest('[data-min]');
        }

        function validateBlock(qBlock) {
            const labelEl = qBlock.querySelector('label');
            const req = labelEl && labelEl.querySelector('span.text-red-600');
            if (!req) {
                setError(qBlock, '');
                return true;
            }

            const inputs = Array.from(qBlock.querySelectorAll('input,select,textarea'));
            let ok = false;

            // multiple choice
            const mc = qBlock.querySelector('input[type="checkbox"]');
            if (mc) {
                const name = mc.name;
                const selArr = qBlock.querySelectorAll('input[type="checkbox"]:checked');
                const min = 1;
                ok = selArr.length >= min;
                if (!ok) {
                    setError(qBlock, min > 1 ? 'Please select at least ' + min + ' option(s).' : 'This question is required.');
                    return false;
                }
                setError(qBlock, '');
                return true;
            }

            // radio/scale/likert/yes_no/single: any checked radio in the block
            const checkedRadio = qBlock.querySelector('input[type="radio"]:checked');
            if (checkedRadio) {
                setError(qBlock, '');
                return true;
            }

            // rating uses a hidden input bound to stars
            const hidden = qBlock.querySelector('input[type="hidden"][name]');
            if (hidden) {
                ok = hidden.value !== '';
                setError(qBlock, ok ? '' : 'Please tap a rating.');
                return ok;
            }

            for (const input of inputs) {
                if (input.type === 'hidden' || input.type === 'radio' || input.type === 'checkbox') continue;
                const isSelect = input.tagName === 'SELECT';
                const empty = isSelect ? input.value === '' : (input.value === null || String(input.value).trim() === '');
                if (empty) continue;
                ok = true;

                // range checks
                if (input.type === 'number') {
                    const v = Number(input.value);
                    if (input.min && v < Number(input.min)) {
                        setError(qBlock, 'Enter a value of at least ' + input.min + '.');
                        return false;
                    }
                    if (input.max && v > Number(input.max)) {
                        setError(qBlock, 'Enter a value of at most ' + input.max + '.');
                        return false;
                    }
                }
                if (input.type === 'email') {
                    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
                    if (!emailOk) {
                        setError(qBlock, 'Enter a valid email address.');
                        return false;
                    }
                }
            }

            if (!ok) {
                setError(qBlock, 'This question is required.');
                return false;
            }
            setError(qBlock, '');
            return true;
        }

        function validateStep(idx) {
            if (idx < 0 || idx >= steps.length) return true;
            const blocks = steps[idx].querySelectorAll('.question-block');
            for (const block of blocks) {
                if (!validateBlock(block)) return false;
            }
            return true;
        }

        function showStep(idx) {
            current = Math.max(0, Math.min(idx, total - 1));
            steps.forEach(function (step, i) {
                step.classList.toggle('hidden', i !== current);
            });
            if (prevBtn) prevBtn.classList.toggle('hidden', current === 0);
            if (nextBtn) nextBtn.classList.toggle('hidden', current === total - 1);
            if (submitBtn) submitBtn.classList.toggle('hidden', current !== total - 1);

            if (bar) {
                const pct = total > 1 ? Math.round(((current + 1) / total) * 100) : 100;
                bar.style.width = pct + '%';
                if (percent) percent.textContent = pct + '%';
            }
            if (label) label.textContent = 'Section ' + (current + 1) + ' of ' + total;
            if (dots) {
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('bg-brand-700', i <= current);
                    dot.classList.toggle('bg-ink-200', i > current);
                });
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (!validateStep(current)) return;
                showStep(current + 1);
            });
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                showStep(current - 1);
            });
        }
        if (submitBtn && form) {
            submitBtn.addEventListener('click', function (e) {
                // Validate every previously skipped section too.
                for (let i = 0; i < total; i++) {
                    if (!validateStep(i)) {
                        e.preventDefault();
                        showStep(i);
                        window.toast('Please complete the required questions.', 'error');
                        return false;
                    }
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                // Final safety net before native submission (novalidate is set).
                for (let i = 0; i < total; i++) {
                    if (!validateStep(i)) {
                        e.preventDefault();
                        showStep(i);
                        return false;
                    }
                }
                return true;
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();