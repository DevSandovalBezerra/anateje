if (typeof window.relatoriosData === 'undefined') {
    window.relatoriosData = null;
}

relatoriosData = window.relatoriosData;

let inicializandoRelatorios = false;
let relatoriosInicializado = false;

async function initRelatorios() {
    if (inicializandoRelatorios) return;
    if (relatoriosInicializado) return;
    
    inicializandoRelatorios = true;
    
    setTimeout(() => {
        const container = document.querySelector('main') || document.body;
        if (!container) {
            inicializandoRelatorios = false;
            setTimeout(() => {
                if (document.querySelector('main') || document.body) {
                    initRelatorios();
                }
            }, 200);
            return;
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        if (typeof Chart !== 'undefined') {
        }
        
        relatoriosInicializado = true;
        inicializandoRelatorios = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRelatorios);
} else {
    initRelatorios();
}

