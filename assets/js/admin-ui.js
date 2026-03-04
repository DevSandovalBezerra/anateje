(function () {
    function getSwal() {
        if (typeof window !== 'undefined' && window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal;
        }
        return null;
    }

    function getSweetAlertConfig() {
        if (typeof window !== 'undefined' && window.SweetAlertConfig) {
            return window.SweetAlertConfig;
        }
        return null;
    }

    async function alertMessage(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const type = String(opts.type || 'info');
        const title = String(opts.title || 'Aviso');
        const text = String(opts.text || '');

        const cfg = getSweetAlertConfig();
        if (cfg) {
            if (type === 'success' && typeof cfg.success === 'function') return cfg.success(title, text, opts.timer || 0);
            if (type === 'error' && typeof cfg.error === 'function') return cfg.error(title, text);
            if (type === 'warning' && typeof cfg.warning === 'function') return cfg.warning(title, text);
            if (typeof cfg.info === 'function') return cfg.info(title, text);
        }

        const swal = getSwal();
        if (swal) {
            return swal.fire({
                title: title,
                text: text,
                icon: ['success', 'error', 'warning', 'info', 'question'].includes(type) ? type : 'info',
                confirmButtonColor: '#a6764c'
            });
        }

        if (typeof console !== 'undefined') {
            console[type === 'error' ? 'error' : 'log']((title ? title + ': ' : '') + text);
        }
        return Promise.resolve({ isConfirmed: true });
    }

    async function confirmAction(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const title = String(opts.title || 'Confirmar acao');
        const text = String(opts.text || 'Deseja continuar?');
        const confirmText = String(opts.confirmText || 'Confirmar');
        const cancelText = String(opts.cancelText || 'Cancelar');
        const icon = String(opts.icon || 'question');
        const isDanger = !!opts.danger;

        const swal = getSwal();
        if (swal) {
            const result = await swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonColor: isDanger ? '#b74646' : '#a6764c',
                cancelButtonColor: '#6b7280'
            });
            return !!(result && result.isConfirmed);
        }

        const cfg = getSweetAlertConfig();
        if (cfg && typeof cfg.confirm === 'function') {
            const result = await cfg.confirm(title, text, confirmText, cancelText);
            return !!(result && result.isConfirmed);
        }

        return false;
    }

    async function confirmDelete(targetLabel) {
        const label = String(targetLabel || 'este registro');
        return confirmAction({
            title: 'Confirmar exclusao',
            text: 'Deseja excluir ' + label + '?',
            confirmText: 'Excluir',
            cancelText: 'Cancelar',
            icon: 'warning',
            danger: true
        });
    }

    async function promptText(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const title = String(opts.title || 'Informe um valor');
        const text = String(opts.text || '');
        const placeholder = String(opts.placeholder || '');
        const defaultValue = String(opts.defaultValue || '');
        const required = !!opts.required;
        const requiredMessage = String(opts.requiredMessage || 'Campo obrigatorio');
        const confirmText = String(opts.confirmText || 'Confirmar');
        const cancelText = String(opts.cancelText || 'Cancelar');

        const swal = getSwal();
        if (swal) {
            const result = await swal.fire({
                title: title,
                text: text,
                input: 'text',
                inputValue: defaultValue,
                inputPlaceholder: placeholder,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                reverseButtons: true,
                preConfirm: (value) => {
                    const v = String(value || '').trim();
                    if (required && !v) {
                        swal.showValidationMessage(requiredMessage);
                        return false;
                    }
                    return v;
                }
            });
            if (!result || !result.isConfirmed) {
                return null;
            }
            return String(result.value || '').trim();
        }

        return null;
    }

    function resolveFieldElement(form, fieldId) {
        if (!form || !fieldId) return null;
        return form.querySelector('#' + fieldId) || document.getElementById(fieldId);
    }

    function clearFieldErrors(form) {
        if (!form) return;
        form.querySelectorAll('.input-invalid').forEach(function (input) {
            input.classList.remove('input-invalid');
            input.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('.form-field-error').forEach(function (node) {
            node.remove();
        });
    }

    function setFieldError(form, fieldId, message) {
        const input = resolveFieldElement(form, fieldId);
        if (!input) return false;

        input.classList.add('input-invalid');
        input.setAttribute('aria-invalid', 'true');

        const holder = input.closest('label') || input.parentElement || form;
        if (!holder) return false;

        let errEl = holder.querySelector('.form-field-error[data-error-for="' + fieldId + '"]');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.className = 'form-field-error';
            errEl.setAttribute('data-error-for', fieldId);
            holder.appendChild(errEl);
        }
        errEl.textContent = String(message || 'Campo invalido');
        return true;
    }

    function applyValidationFromMessage(form, message, mappings) {
        if (!form || !message) return false;
        if (!Array.isArray(mappings) || mappings.length === 0) return false;

        const text = String(message);
        for (let i = 0; i < mappings.length; i += 1) {
            const map = mappings[i];
            if (!map || !map.pattern || !map.field) continue;
            if (map.pattern.test(text)) {
                return setFieldError(form, map.field, text);
            }
        }
        return false;
    }

    function applyValidationError(form, err, mappings) {
        if (!form) return false;
        clearFieldErrors(form);

        let applied = false;
        const payload = err && err.payload && err.payload.error ? err.payload.error : null;
        const details = payload && payload.details ? payload.details : null;

        if (details && typeof details === 'object' && !Array.isArray(details)) {
            if (typeof details.field === 'string' && details.field) {
                applied = setFieldError(form, details.field, details.message || err.message || 'Campo invalido') || applied;
            }

            if (details.fields && typeof details.fields === 'object' && !Array.isArray(details.fields)) {
                Object.keys(details.fields).forEach(function (fieldId) {
                    const fieldMsg = details.fields[fieldId];
                    if (typeof fieldMsg === 'string' && fieldMsg) {
                        applied = setFieldError(form, fieldId, fieldMsg) || applied;
                    }
                });
            }
        }

        if (!applied) {
            applied = applyValidationFromMessage(form, err && err.message ? err.message : '', mappings || []);
        }

        return applied;
    }

    if (typeof window !== 'undefined') {
        window.anatejeUi = {
            success: function (title, text, timer) { return alertMessage({ type: 'success', title: title, text: text, timer: timer || 0 }); },
            error: function (title, text) { return alertMessage({ type: 'error', title: title, text: text }); },
            warning: function (title, text) { return alertMessage({ type: 'warning', title: title, text: text }); },
            info: function (title, text) { return alertMessage({ type: 'info', title: title, text: text }); },
            alertMessage: alertMessage,
            confirmAction: confirmAction,
            confirmDelete: confirmDelete,
            promptText: promptText,
            clearFieldErrors: clearFieldErrors,
            setFieldError: setFieldError,
            applyValidationFromMessage: applyValidationFromMessage,
            applyValidationError: applyValidationError
        };
    }
})();
