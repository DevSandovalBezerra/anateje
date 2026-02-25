// Verificar se Swal está disponível antes de criar o objeto
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
                title: options.title || 'Formulário',
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
                        return 'Este campo é obrigatório!';
                    }
                }
            });
        }
    };
} else {
    // Fallback se SweetAlert não estiver carregado
    SweetAlertConfig = {
        success: function(title, text) {
            alert(`${title}: ${text}`);
            return Promise.resolve({ isConfirmed: true });
        },
        error: function(title, text) {
            alert(`ERRO - ${title}: ${text}`);
            return Promise.resolve({ isConfirmed: true });
        },
        warning: function(title, text) {
            alert(`ATENÇÃO - ${title}: ${text}`);
            return Promise.resolve({ isConfirmed: true });
        },
        info: function(title, text) {
            alert(`INFO - ${title}: ${text}`);
            return Promise.resolve({ isConfirmed: true });
        },
        confirm: function(title, text) {
            return Promise.resolve({ isConfirmed: confirm(`${title}\n${text}`) });
        },
        
        select: function(title, text, options) {
            const selected = prompt(`${title}\n${text}\n\nOpções:\n${options.map((o, i) => `${i + 1}. ${o.text}`).join('\n')}\n\nDigite o número:`);
            const index = parseInt(selected) - 1;
            if (index >= 0 && index < options.length) {
                return Promise.resolve({ value: options[index].value });
            }
            return Promise.resolve({ value: null });
        },
        
        form: function(options) {
            return Promise.resolve({ value: options.preConfirm ? options.preConfirm() : null });
        },
        
        html: function(options) {
            return Promise.resolve({ isConfirmed: confirm(options.title || 'Confirmar?') });
        },
        
        prompt: function(title, text, placeholder = '') {
            const value = prompt(`${title}\n${text}`, placeholder);
            return Promise.resolve({ 
                isConfirmed: value !== null && value !== '', 
                value: value 
            });
        }
    };
}

// Disponibilizar globalmente via window
if (typeof window !== 'undefined') {
    window.SweetAlertConfig = SweetAlertConfig;
}
}
