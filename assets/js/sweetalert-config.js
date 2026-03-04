// Verificar se Swal estÃ¡ disponÃ­vel antes de criar o objeto
// Usar window para disponibilizar globalmente
if (typeof window.SweetAlertConfig === 'undefined') {
    var SweetAlertConfig;

if (typeof Swal !== 'undefined') {
    SweetAlertConfig = {
        defaultOptions: {
            confirmButtonColor: '#8B5CF6',
            cancelButtonColor: '#6B7280',
            buttonsStyling: true,
            allowOutsideClick: false
        },
        
        success: function(title, text, timer = 2000) {
            return Swal.fire({
                ...this.defaultOptions,
                icon: 'success',
                title: title,
                text: text,
                timer: timer,
                showConfirmButton: timer === 0
            });
        },
        
        error: function(title, text) {
            return Swal.fire({
                ...this.defaultOptions,
                icon: 'error',
                title: title,
                text: text
            });
        },
        
        warning: function(title, text) {
            return Swal.fire({
                ...this.defaultOptions,
                icon: 'warning',
                title: title,
                text: text
            });
        },
        
        info: function(title, text) {
            return Swal.fire({
                ...this.defaultOptions,
                icon: 'info',
                title: title,
                text: text
            });
        },
        
        confirm: function(title, text, confirmText = 'Confirmar', cancelText = 'Cancelar') {
            return Swal.fire({
                ...this.defaultOptions,
                icon: 'question',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText
            });
        },
        
        select: function(title, text, options) {
            const selectOptions = options.map(opt => `<option value="${opt.value}">${opt.text}</option>`).join('');
            return Swal.fire({
                ...this.defaultOptions,
                title: title,
                html: `<p class="mb-4">${text}</p>
                       <select id="swal-select" class="swal2-input" style="width: 100%; padding: 0.5rem;">
                           ${selectOptions}
                       </select>`,
                showCancelButton: true,
                confirmButtonText: 'Selecionar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const select = document.getElementById('swal-select');
                    return select ? { value: select.value } : null;
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    return { value: result.value.value };
                }
                return { value: null };
            });
        },
        
        form: function(options) {
            return Swal.fire({
                ...this.defaultOptions,
                title: options.title || 'FormulÃ¡rio',
                html: options.html || '',
                showCancelButton: options.showCancelButton !== false,
                confirmButtonText: options.confirmButtonText || 'Salvar',
                cancelButtonText: options.cancelButtonText || 'Cancelar',
                focusConfirm: options.focusConfirm !== false,
                preConfirm: options.preConfirm || (() => null)
            });
        },
        
        html: function(options) {
            return Swal.fire({
                ...this.defaultOptions,
                title: options.title || '',
                html: options.html || '',
                showCancelButton: options.showCancelButton !== false,
                confirmButtonText: options.confirmButtonText || 'Confirmar',
                cancelButtonText: options.cancelButtonText || 'Cancelar',
                didOpen: options.didOpen || (() => {}),
                preConfirm: options.preConfirm || (() => null)
            });
        },
        
        prompt: function(title, text, placeholder = '') {
            return Swal.fire({
                ...this.defaultOptions,
                title: title,
                text: text,
                input: 'text',
                inputPlaceholder: placeholder,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Este campo Ã© obrigatÃ³rio!';
                    }
                }
            });
        }
    };
} else {
    // Fallback sem uso de alert/confirm/prompt nativos
    SweetAlertConfig = {
        success: function(title, text) {
            if (typeof console !== 'undefined') console.log((title || '') + ': ' + (text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        error: function(title, text) {
            if (typeof console !== 'undefined') console.error('ERRO - ' + (title || '') + ': ' + (text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        warning: function(title, text) {
            if (typeof console !== 'undefined') console.warn('ATENCAO - ' + (title || '') + ': ' + (text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        info: function(title, text) {
            if (typeof console !== 'undefined') console.log('INFO - ' + (title || '') + ': ' + (text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        confirm: function(title, text) {
            if (typeof console !== 'undefined') console.warn('CONFIRM indisponivel: ' + (title || '') + ' - ' + (text || ''));
            return Promise.resolve({ isConfirmed: false });
        },
        select: function(title, text, options) {
            if (typeof console !== 'undefined') console.warn('SELECT indisponivel: ' + (title || '') + ' - ' + (text || ''));
            return Promise.resolve({ value: null });
        },
        form: function(options) {
            return Promise.resolve({ value: options.preConfirm ? options.preConfirm() : null });
        },
        html: function(options) {
            if (typeof console !== 'undefined') console.warn('HTML modal indisponivel: ' + ((options && options.title) || ''));
            return Promise.resolve({ isConfirmed: false });
        },
        prompt: function(title, text, placeholder = '') {
            if (typeof console !== 'undefined') console.warn('PROMPT indisponivel: ' + (title || '') + ' - ' + (text || ''));
            return Promise.resolve({ isConfirmed: false, value: null });
        }
    };
}

// Disponibilizar globalmente via window
if (typeof window !== 'undefined') {
    window.SweetAlertConfig = SweetAlertConfig;
}
}
