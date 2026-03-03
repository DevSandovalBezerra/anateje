(function () {
    function getSwal() {
        if (typeof window !== 'undefined' && window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal;
        }
        return null;
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

        return window.confirm(title + '\n' + text);
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
            confirmAction: confirmAction,
            confirmDelete: confirmDelete,
            clearFieldErrors: clearFieldErrors,
            setFieldError: setFieldError,
            applyValidationFromMessage: applyValidationFromMessage,
            applyValidationError: applyValidationError
        };
    }
})();
