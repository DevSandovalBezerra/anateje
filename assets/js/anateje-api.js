(function () {
    async function anatejeApi(path, options = {}) {
        const finalOptions = {
            method: options.method || 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                ...(options.headers || {})
            }
        };

        if (typeof options.body !== 'undefined') {
            finalOptions.body = JSON.stringify(options.body);
        }

        const response = await fetch(path, finalOptions);
        const json = await response.json().catch(() => null);

        if (!response.ok || !json || json.ok !== true) {
            const message = json && json.error && json.error.message ? json.error.message : 'Erro na requisicao';
            const err = new Error(message);
            err.payload = json;
            err.status = response.status;
            throw err;
        }

        return json.data || {};
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function formatCpf(value) {
        const n = onlyDigits(value).slice(0, 11);
        return n
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function formatPhone(value) {
        const n = onlyDigits(value).slice(0, 11);
        if (n.length <= 10) {
            return n
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        }
        return n
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    }

    function formatCep(value) {
        const n = onlyDigits(value).slice(0, 8);
        return n.replace(/(\d{5})(\d{1,3})$/, '$1-$2');
    }

    window.anatejeApi = anatejeApi;
    window.anatejeMask = {
        onlyDigits,
        formatCpf,
        formatPhone,
        formatCep
    };
})();
