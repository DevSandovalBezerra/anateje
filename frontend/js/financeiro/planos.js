if (typeof window.planosData === 'undefined') {
    window.planosData = [];
}

planosData = window.planosData;

async function carregarPlanos() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/planos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/planos.php?action=listar`
                : `../../api/financeiro/planos.php?action=listar`);
        
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
            planosData = result.data || [];
        }
    } catch (error) {
        console.error('Erro ao carregar planos:', error);
    }
}

let inicializandoPlanos = false;
let planosInicializado = false;

async function initPlanos() {
    if (inicializandoPlanos) return;
    if (planosInicializado) return;
    
    inicializandoPlanos = true;
    
    setTimeout(async () => {
        const container = document.querySelector('main') || document.body;
        if (!container) {
            inicializandoPlanos = false;
            setTimeout(async () => {
                if (document.querySelector('main') || document.body) {
                    await initPlanos();
                }
            }, 200);
            return;
        }
        
        await carregarPlanos();
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        planosInicializado = true;
        inicializandoPlanos = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlanos);
} else {
    initPlanos();
}

