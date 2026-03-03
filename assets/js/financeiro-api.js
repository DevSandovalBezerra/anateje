(function () {
    if (window.__ANATEJE_FINANCEIRO_FETCH_PATCH__) {
        return;
    }
    window.__ANATEJE_FINANCEIRO_FETCH_PATCH__ = true;

    if (typeof window.fetch !== 'function') {
        return;
    }

    const originalFetch = window.fetch.bind(window);
    const writeMethods = { POST: true, PUT: true, PATCH: true, DELETE: true };

    function getUrlFromInput(input) {
        if (typeof input === 'string') {
            return input;
        }
        if (input && typeof input.url === 'string') {
            return input.url;
        }
        return '';
    }

    function getMethod(input, init) {
        const fromInit = init && init.method ? String(init.method) : '';
        if (fromInit) {
            return fromInit.toUpperCase();
        }
        const fromInput = input && input.method ? String(input.method) : '';
        if (fromInput) {
            return fromInput.toUpperCase();
        }
        return 'GET';
    }

    function isFinanceiroApiUrl(url) {
        const value = String(url || '').toLowerCase();
        return value.includes('/api/financeiro/') || value.includes('api/financeiro/');
    }

    window.fetch = function (input, init) {
        const url = getUrlFromInput(input);
        const method = getMethod(input, init);

        if (!isFinanceiroApiUrl(url) || !writeMethods[method]) {
            return originalFetch(input, init);
        }

        const nextInit = Object.assign({}, init || {});
        if (!nextInit.credentials) {
            nextInit.credentials = 'include';
        }

        const headers = new Headers(nextInit.headers || undefined);
        const csrfToken = String(window.LIDERGEST_CSRF_TOKEN || '').trim();
        if (csrfToken !== '' && !headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', csrfToken);
        }
        nextInit.headers = headers;

        return originalFetch(input, nextInit);
    };
})();
