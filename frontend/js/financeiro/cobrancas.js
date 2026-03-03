if (typeof window.cobrancasData === 'undefined') {
    window.cobrancasData = [];
}

cobrancasData = window.cobrancasData;

async function carregarCobrancas() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/cobrancas.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/cobrancas.php?action=listar`
                : `../../api/financeiro/cobrancas.php?action=listar`);
        
        const response = await fetch(url, { credentials: 'include' });
        if (!response.ok) {
            if (response.status === 401) {
                if (typeof SweetAlertConfig !== 'undefined') {
                    SweetAlertConfig.warning('Sessão expirada', 'Você será redirecionado para fazer login.').then(() => {
                        window.location.href = '../auth/login.html';
                    });
                }
                return;
            }
            throw new Error(`Erro HTTP ${response.status}`);
        }
        
        const result = await response.json();
        if (result.success) {
            cobrancasData = result.data || [];
        }
    } catch (error) {
        console.error('Erro ao carregar cobranças:', error);
    }
}

let inicializandoCobrancas = false;
let cobrancasInicializado = false;

async function initCobrancas() {
    if (inicializandoCobrancas) return;
    if (cobrancasInicializado) return;
    
    inicializandoCobrancas = true;
    
    setTimeout(async () => {
        const container = document.querySelector('main') || document.body;
        if (!container) {
            inicializandoCobrancas = false;
            setTimeout(async () => {
                if (document.querySelector('main') || document.body) {
                    await initCobrancas();
                }
            }, 200);
            return;
        }
        
        await carregarCobrancas();
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        cobrancasInicializado = true;
        inicializandoCobrancas = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCobrancas);
} else {
    initCobrancas();
}

