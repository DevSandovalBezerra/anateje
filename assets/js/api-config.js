// LiderGest - Configuração de URLs
// Sistema de Gestão Pedagógico-Financeira Líder School

// Proteger contra redeclaração em navegação AJAX
if (typeof window.ApiConfig === 'undefined') {
    window.ApiConfig = class ApiConfig {
        constructor() {
            // Não armazenar baseUrl no construtor - calcular dinamicamente sempre
        }

        getBaseUrl() {
            // Prioridade 1: Usar variável global injetada pelo PHP (mais confiável)
            if (typeof window.LIDERGEST_BASE_URL !== 'undefined') {
                return window.LIDERGEST_BASE_URL;
            }
            
            // Prioridade 2: Detectar automaticamente a partir do pathname
            const path = window.location.pathname;
            
            // Se estiver em /frontend/, extrair base antes de /frontend/
            const frontendIndex = path.indexOf('/frontend/');
            if (frontendIndex > 0) {
                return path.substring(0, frontendIndex);
            }
            
            // Se estiver em /api/, extrair base antes de /api/
            const apiIndex = path.indexOf('/api/');
            if (apiIndex > 0) {
                return path.substring(0, apiIndex);
            }
            
            // Fallback: raiz do domínio
            return '';
        }

        getApiEndpoint(endpoint) {
            const baseUrl = this.getBaseUrl();
            const apiUrl = baseUrl ? `${baseUrl}/api` : '/api';
            const result = apiUrl + '/' + endpoint.replace(/^\//, '');
            return result;
        }

        getFrontendPath(path) {
            const baseUrl = this.getBaseUrl();
            const frontendUrl = baseUrl ? `${baseUrl}/frontend` : '/frontend';
            return frontendUrl + '/' + path.replace(/^\//, '');
        }

        getRelativePath(from, to) {
            const fromParts = from.split('/').filter(part => part);
            const toParts = to.split('/').filter(part => part);

            let commonLength = 0;
            for (let i = 0; i < Math.min(fromParts.length, toParts.length); i++) {
                if (fromParts[i] === toParts[i]) {
                    commonLength++;
                } else {
                    break;
                }
            }

            const upLevels = fromParts.length - commonLength - 1;
            const downPath = toParts.slice(commonLength).join('/');

            const result = '../'.repeat(upLevels) + downPath;
            return result || './';
        }
    };
}

// Instância global (apenas se não existir)
if (typeof window.apiConfig === 'undefined') {
    window.apiConfig = new window.ApiConfig();
}

// Criar referência local (usar var para permitir redeclaração sem erro em recarregamentos AJAX)
// var tem hoisting e permite redeclaração, diferente de const/let
var apiConfig = window.apiConfig;

// Funções utilitárias
function getApiUrl(endpoint) {
    return apiConfig.getApiEndpoint(endpoint);
}

function getFrontendUrl(path) {
    return apiConfig.getFrontendPath(path);
}

function getRelativeUrl(from, to) {
    return apiConfig.getRelativePath(from, to);
}

function isApiDebugEnabled() {
    try {
        if (window.LIDERGEST_DEBUG_API === true) {
            return true;
        }
        return window.localStorage.getItem('lidergest_debug_api') === '1';
    } catch (e) {
        return false;
    }
}

function redactUrlEncodedBody(body) {
    if (typeof body !== 'string') {
        return body;
    }
    return body
        .replace(/(^|&)password=[^&]*/gi, '$1password=[REDACTED]')
        .replace(/(^|&)senha=[^&]*/gi, '$1senha=[REDACTED]')
        .replace(/(^|&)token=[^&]*/gi, '$1token=[REDACTED]')
        .replace(/(^|&)authorization=[^&]*/gi, '$1authorization=[REDACTED]');
}

function sanitizeHeadersForLog(headers) {
    if (!headers || typeof headers !== 'object') {
        return headers;
    }
    const safe = {};
    Object.keys(headers).forEach((key) => {
        const lower = key.toLowerCase();
        if (lower === 'authorization' || lower === 'cookie' || lower === 'set-cookie') {
            safe[key] = '[REDACTED]';
            return;
        }
        safe[key] = headers[key];
    });
    return safe;
}

function sanitizeOptionsForLog(options) {
    if (!options || typeof options !== 'object') {
        return options;
    }
    const safe = { ...options };
    if (typeof safe.body === 'string') {
        safe.body = redactUrlEncodedBody(safe.body);
    } else if (safe.body instanceof FormData) {
        safe.body = '[FormData]';
    }
    if (safe.headers) {
        safe.headers = sanitizeHeadersForLog(safe.headers);
    }
    return safe;
}

function apiDebugLog(...args) {
    if (isApiDebugEnabled()) {
        console.log(...args);
    }
}

// Função para fazer requisições à API
async function apiRequest(endpoint, options = {}) {
    const url = getApiUrl(endpoint);
    const method = (options.method || 'GET').toUpperCase();
    const hasBody = typeof options.body !== 'undefined' && options.body !== null;
    const isFormData = hasBody && (options.body instanceof FormData);
    const mergedHeaders = {
        ...(options.headers || {})
    };
    if (hasBody && !isFormData && !('Content-Type' in mergedHeaders) && !('content-type' in mergedHeaders)) {
        mergedHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
    }
    if (isFormData) {
        delete mergedHeaders['Content-Type'];
        delete mergedHeaders['content-type'];
    }
    const finalOptions = {
        ...options,
        method,
        headers: mergedHeaders
    };
    apiDebugLog('API REQUEST', endpoint, url, sanitizeOptionsForLog(finalOptions));

    try {
        const response = await fetch(url, finalOptions);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
            const jsonData = await response.json();
            return jsonData;
        } else {
            const textData = await response.text();
            return textData;
        }
    } catch (error) {
        apiDebugLog('API Request Error', error);
        throw error;
    }
}

// Função para fazer login
async function login(email, password) {
    const formData = new URLSearchParams();
    formData.append('action', 'login');
    formData.append('email', email);
    formData.append('password', password);

    try {
        const response = await fetch(getApiUrl('auth/login.php'), {
            method: 'POST',
            credentials: 'include',
            body: formData.toString(),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        const json = await response.json().catch(() => null);

        if (json && typeof json === 'object') {
            return json;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return { success: false, message: 'Resposta invalida da API de login' };
    } catch (error) {
        apiDebugLog('Login API Error', error);
        throw error;
    }
}

// Função para fazer logout
async function logout() {
    try {
        const response = await apiRequest('auth/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        });

        // Determinar baseUrl dinamicamente
        let baseUrl = '';
        if (typeof window.LIDERGEST_BASE_URL !== 'undefined') {
            baseUrl = window.LIDERGEST_BASE_URL;
        } else {
            const path = window.location.pathname;
            const frontendIndex = path.indexOf('/frontend/');
            if (frontendIndex > 0) {
                baseUrl = path.substring(0, frontendIndex);
            }
        }
        
        // Construir URL relativa (sem caminho absoluto)
        const redirectUrl = baseUrl ? `${baseUrl}/index.php` : 'index.php';
        
        // Redirecionar para index.php que tratará autenticação
        window.location.href = redirectUrl;

        return response;
    } catch (error) {
        apiDebugLog('Logout Error', error);
        
        // Determinar baseUrl dinamicamente
        let baseUrl = '';
        if (typeof window.LIDERGEST_BASE_URL !== 'undefined') {
            baseUrl = window.LIDERGEST_BASE_URL;
        } else {
            const path = window.location.pathname;
            const frontendIndex = path.indexOf('/frontend/');
            if (frontendIndex > 0) {
                baseUrl = path.substring(0, frontendIndex);
            }
        }
        
        // Construir URL relativa (sem caminho absoluto)
        const redirectUrl = baseUrl ? `${baseUrl}/index.php` : 'index.php';
        
        // Mesmo com erro, redirecionar para index.php
        window.location.href = redirectUrl;
        return {
            success: false,
            message: 'Erro no logout'
        };
    }
}

// Função para verificar autenticação
async function checkAuth() {
    return await apiRequest('auth/login.php?action=check_auth');
}

// Função para obter perfil do usuário
async function getUserProfile() {
    return await apiRequest('auth/login.php?action=profile');
}

// Função para registrar responsável
async function registerResponsavel(data) {
    const formData = new FormData();
    formData.append('action', 'register');

    Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
    });

    return await apiRequest('auth/login.php', {
        method: 'POST',
        body: formData
    });
}

// Exportar para uso global (apenas se não existirem)
if (typeof window.getApiUrl === 'undefined') {
    window.getApiUrl = getApiUrl;
}
if (typeof window.getFrontendUrl === 'undefined') {
    window.getFrontendUrl = getFrontendUrl;
}
if (typeof window.getRelativeUrl === 'undefined') {
    window.getRelativeUrl = getRelativeUrl;
}
if (typeof window.apiRequest === 'undefined') {
    window.apiRequest = apiRequest;
}
if (typeof window.login === 'undefined') {
    window.login = login;
}
if (typeof window.logout === 'undefined') {
    window.logout = logout;
}
if (typeof window.checkAuth === 'undefined') {
    window.checkAuth = checkAuth;
}
if (typeof window.getUserProfile === 'undefined') {
    window.getUserProfile = getUserProfile;
}
if (typeof window.registerResponsavel === 'undefined') {
    window.registerResponsavel = registerResponsavel;
}
