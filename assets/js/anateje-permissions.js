(function () {
    const raw = Array.isArray(window.LIDERGEST_PERMISSION_CODES) ? window.LIDERGEST_PERMISSION_CODES : [];
    const normalized = raw
        .map((code) => String(code || '').trim())
        .filter((code) => code !== '');
    const codeSet = new Set(normalized);
    const perfilId = Number(window.LIDERGEST_PERFIL_ID || 0);

    function pageCodeFromAction(code) {
        const parts = String(code || '').split('.');
        if (parts.length === 3) {
            return parts[0] + '.' + parts[1];
        }
        return '';
    }

    function can(code) {
        const c = String(code || '').trim();
        if (!c) {
            return false;
        }
        if (perfilId === 1) {
            return true;
        }
        if (codeSet.has(c)) {
            return true;
        }
        const pageCode = pageCodeFromAction(c);
        if (pageCode && codeSet.has(pageCode)) {
            return true;
        }
        return false;
    }

    function hideIfNoPermission(selectorOrElement, permissionCode) {
        const target = typeof selectorOrElement === 'string'
            ? document.querySelector(selectorOrElement)
            : selectorOrElement;
        if (!target) {
            return false;
        }
        const allowed = can(permissionCode);
        if (!allowed) {
            target.classList.add('hidden');
        }
        return allowed;
    }

    function denyMessage(permissionCode) {
        const code = String(permissionCode || '').trim();
        if (!code) {
            return 'Acesso negado para esta acao.';
        }
        return 'Acesso negado para esta acao (' + code + ').';
    }

    window.anatejePerms = {
        perfilId,
        codes: normalized.slice(),
        can,
        hideIfNoPermission,
        denyMessage
    };
})();
