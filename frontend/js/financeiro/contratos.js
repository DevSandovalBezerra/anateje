if (typeof window.contratosData === 'undefined') {
    window.contratosData = [];
}

contratosData = window.contratosData;

async function carregarContratos() {
    try {
        const url = typeof getApiUrl !== 'undefined' 
            ? getApiUrl('financeiro/contratos.php?action=listar')
            : (typeof apiConfig !== 'undefined' 
                ? `${apiConfig.getBaseUrl()}/api/financeiro/contratos.php?action=listar`
                : `../../api/financeiro/contratos.php?action=listar`);
        
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
            contratosData = result.data || [];
        }
    } catch (error) {
        console.error('Erro ao carregar contratos:', error);
    }
}

let inicializandoContratos = false;
let contratosInicializado = false;

async function initContratos() {
    if (inicializandoContratos) return;
    if (contratosInicializado) return;
    
    inicializandoContratos = true;
    
    setTimeout(async () => {
        const container = document.querySelector('main') || document.body;
        if (!container) {
            inicializandoContratos = false;
            setTimeout(async () => {
                if (document.querySelector('main') || document.body) {
                    await initContratos();
                }
            }, 200);
            return;
        }
        
        await carregarContratos();
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        contratosInicializado = true;
        inicializandoContratos = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContratos);
} else {
    initContratos();
}

