if (typeof window.conciliacaoData === 'undefined') {
    window.conciliacaoData = [];
}

conciliacaoData = window.conciliacaoData;

let inicializandoConciliacao = false;
let conciliacaoInicializado = false;

async function initConciliacao() {
    if (inicializandoConciliacao) return;
    if (conciliacaoInicializado) return;
    
    inicializandoConciliacao = true;
    
    setTimeout(() => {
        const container = document.querySelector('main') || document.body;
        if (!container) {
            inicializandoConciliacao = false;
            setTimeout(() => {
                if (document.querySelector('main') || document.body) {
                    initConciliacao();
                }
            }, 200);
            return;
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        conciliacaoInicializado = true;
        inicializandoConciliacao = false;
    }, 100);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConciliacao);
} else {
    initConciliacao();
}

