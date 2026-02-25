// LiderGest - Navegação AJAX no painel principal
(function () {
    const sidebar = document.getElementById('sidebar');
    const pageWrapper = document.getElementById('page-wrapper');
    const headerTitle = document.querySelector('.header-primary h1');

    if (!sidebar || !pageWrapper) {
        return;
    }

    // Em telas menores que 1024px, recolher o sidebar automaticamente quando um item de menu
    // for clicado (melhor UX no mobile) ou após a navegação AJAX completar.
    // Isso evita poluir a tela com o menu e mostra o conteúdo imediatamente.

    let isNavigating = false;
    const loadingSpinner = document.createElement('div');
    loadingSpinner.className = 'page-loading-spinner';
    loadingSpinner.style.display = 'none';

    // Detectar toques e movimentos para distinguir scroll de cliques em dispositivos touch
    let touchStartY = null;
    let touchMoved = false;

    // Usar listeners passivos para evitar impacto na rolagem
    sidebar.addEventListener('touchstart', e => {
        touchStartY = (e.touches && e.touches[0]) ? e.touches[0].clientY : null;
        touchMoved = false;
    }, { passive: true });

    sidebar.addEventListener('touchmove', e => {
        const y = (e.touches && e.touches[0]) ? e.touches[0].clientY : null;
        if (touchStartY !== null && y !== null && Math.abs(y - touchStartY) > 10) {
            touchMoved = true;
            // Marcar timestamp para evitar ações imediatas (logout, cliques) logo após scrolling
            window.__sidebarLastTouchMove = Date.now();
        }
    }, { passive: true });

    sidebar.addEventListener('touchend', e => {
        // Resetar após breve timeout para permitir que o click não seja disparado por um scroll
        setTimeout(() => {
            touchMoved = false;
            touchStartY = null;
        }, 50);
    }, { passive: true });

    function showLoading() {
        if (isNavigating) return;
        isNavigating = true;
        pageWrapper.classList.add('page-loading');
        pageWrapper.appendChild(loadingSpinner);
        loadingSpinner.style.display = 'block';
    }

    function hideLoading() {
        if (!isNavigating) return;
        isNavigating = false;
        loadingSpinner.style.display = 'none';
        if (loadingSpinner.parentNode === pageWrapper) {
            loadingSpinner.remove();
        }
        pageWrapper.classList.remove('page-loading');
    }

    function extractPageFromHref(href) {
        if (!href) {
            return null;
        }
        try {
            const url = new URL(href, window.location.origin);
            return url.searchParams.get('page');
        } catch (error) {
            const match = href.match(/index\.php\?page=([^&#]+)/);
            return match ? decodeURIComponent(match[1]) : null;
        }
    }

    function buildPageUrl(page) {
        const basePath = (window.LIDERGEST_BASE_URL || '') + '/index.php';
        const url = new URL(basePath, window.location.origin);
        url.searchParams.set('page', page);
        return url.pathname + '?' + url.searchParams.toString();
    }

    function updateActiveLinks(targetPage) {
        const links = sidebar.querySelectorAll('a[href*="index.php?page="]');
        links.forEach(link => {
            const page = extractPageFromHref(link.getAttribute('href'));
            if (!page) {
                return;
            }
            const isActive = page === targetPage;
            link.classList.toggle('active', isActive);
            link.classList.toggle('financeiro-active', isActive && link.classList.contains('financeiro-submenu-item'));
        });
    }

    /**
     * Aguarda elementos críticos específicos de cada página aparecerem no DOM
     * @param {string} pageName - Nome da página (ex: 'cadastros/professores')
     * @returns {Promise<boolean>} - true se elementos foram encontrados, false se timeout
     */
    async function waitForCriticalElements(pageName) {
        // Mapeamento de elementos críticos por página
        const criticalElementsMap = {
            // Cadastros
            'cadastros/professores': ['#tbodyProfessores'],
            'cadastros/alunos': ['#tbodyAlunos'],
            'cadastros/unidades': ['#tbodyUnidades'],
            'cadastros/turmas': ['#tbodyTurmas'],
            'cadastros/responsaveis': ['#tbodyResponsaveis'],
            'cadastros/disciplinas': ['#tbodyDisciplinas'],
            'cadastros/calendario_letivo': ['#calendar-container', '.calendar-container'],
            'cadastros/usuarios': ['#tbodyUsuarios'],
            
            // Pedagógico
            'pedagogico/frequencia': ['#selectTurma'],
            'pedagogico/notas': ['#selectTurma', '#tbodyAlunos'],
            'pedagogico/planos_aula': ['tbody', '.planos-container'],
            'pedagogico/anexos': ['#anexos-container', '.anexos-container'],
            'pedagogico/aniversariantes': ['#tbodyAniversariantes', '.pedagogico-content'],
            
            // Financeiro
            'financeiro/categorias_financeiras': ['#tbodyCategorias'],
            'financeiro/centros_custos': ['#tbodyCentros'],
            'financeiro/contas_financeiras': ['#tbodyContas'],
            'financeiro/contas_bancarias': ['#tbodyContas'],
            'financeiro/pessoas': ['#tbodyPessoas'],
            'financeiro/orcamentos': ['#orcamentos-container', '.orcamentos-container'],
            
            // Dashboard
            'dashboard/admin': ['.dashboard-content', '#dashboard-admin'],
            'dashboard/financeiro': ['.dashboard-content', '#dashboard-financeiro'],
            'dashboard/coordenacao': ['.dashboard-content', '#dashboard-coordenacao'],
            
            // Admin
            'admin/permissoes': ['#tbodyPerfis', '.admin-content']
        };
        
        // Seletores padrão caso a página não esteja no mapa
        const defaultSelectors = ['table', '.card-primary', '.cadastros-content', '.pedagogico-content', '.dashboard-content'];
        
        // Obter seletores específicos ou usar padrão
        const selectors = criticalElementsMap[pageName] || defaultSelectors;
        const maxAttempts = 30; // 1.5 segundos total (30 * 50ms)
        
        for (let attempt = 0; attempt < maxAttempts; attempt++) {
            // Verificar se todos os seletores críticos foram encontrados
            const allFound = selectors.some(selector => {
                try {
                    return document.querySelector(selector) !== null;
                } catch (e) {
                    return false;
                }
            });
            
            if (allFound) {
                // Aguardar um frame adicional para garantir renderização completa
                await new Promise(resolve => requestAnimationFrame(resolve));
                return true;
            }
            
            // Aguardar antes da próxima tentativa
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        
        // Timeout: elementos não foram encontrados, mas continuar mesmo assim
        console.warn(`Elementos críticos não encontrados para ${pageName} após ${maxAttempts} tentativas`);
        return false;
    }

    async function executeScripts(scope) {
        const scripts = scope.querySelectorAll('script:not([data-processed])');
        const scriptPromises = [];
        const loadedScripts = new Set(); // Rastrear scripts já carregados

        scripts.forEach(oldScript => {
            if (oldScript.src) {
                // Script externo: verificar se já foi carregado
                const scriptUrl = oldScript.src;
                if (loadedScripts.has(scriptUrl)) {
                    // Script já carregado, apenas remover o elemento antigo
                    oldScript.remove();
                    return;
                }
                
                loadedScripts.add(scriptUrl);
                
                const newScript = document.createElement('script');
                newScript.setAttribute('data-processed', 'true');
                
                Array.from(oldScript.attributes).forEach(attr => {
                    if (attr.name !== 'data-processed') {
                        newScript.setAttribute(attr.name, attr.value);
                    }
                });
                
                const scriptPromise = new Promise((resolve) => {
                    newScript.onload = () => {
                        setTimeout(resolve, 20);
                    };
                    newScript.onerror = () => {
                        console.warn(`Script falhou ao carregar: ${oldScript.src}`);
                        resolve(); // Não bloquear outros scripts
                    };
                });
                scriptPromises.push(scriptPromise);
                
                // Adicionar ao DOM antes de definir src
                const parent = oldScript.parentNode;
                if (parent) {
                    parent.insertBefore(newScript, oldScript);
                    newScript.src = oldScript.src;
                    parent.removeChild(oldScript);
                }
            } else {
                // Script inline: executar apenas uma vez
                if (oldScript.hasAttribute('data-processed')) {
                    oldScript.remove();
                    return;
                }
                
                oldScript.setAttribute('data-processed', 'true');
                // Script inline já está no DOM, apenas marcar como processado
                // O navegador já executou o script quando foi inserido
            }
        });

        // Aguardar todos os scripts externos carregarem
        if (scriptPromises.length > 0) {
            await Promise.all(scriptPromises);
        }
        
        // Aguardar um pouco para garantir que scripts foram executados e funções estão disponíveis
        // Usar requestAnimationFrame para garantir que estamos no próximo ciclo de renderização
        await new Promise(resolve => requestAnimationFrame(resolve));
        await new Promise(resolve => setTimeout(resolve, 50));
    }

    async function simulateDOMContentLoaded() {
        // Nota: As verificações de elementos críticos já foram feitas em waitForCriticalElements()
        // Aqui apenas garantimos um frame adicional e então disparamos o evento
        
        // Aguardar um frame adicional para garantir que tudo está pronto
        await new Promise(resolve => requestAnimationFrame(resolve));
        
        // Simular evento DOMContentLoaded para que todos os listeners sejam executados
        // Esta é a abordagem padrão usada por frameworks como Turbolinks e é genérica
        // Funciona para QUALQUER script que use addEventListener('DOMContentLoaded', ...)
        
        // Criar e disparar o evento no document
        const domContentLoadedEvent = new Event('DOMContentLoaded', {
            bubbles: true,
            cancelable: true
        });
        
        // Disparar no document para que todos os listeners sejam notificados
        document.dispatchEvent(domContentLoadedEvent);
        
        // Também disparar no window para scripts que podem escutar lá
        window.dispatchEvent(domContentLoadedEvent);
        
        // Aguardar um tick do event loop para garantir que listeners sejam processados
        await new Promise(resolve => setTimeout(resolve, 0));
        
        // Disparar novamente para capturar listeners adicionados dinamicamente após o primeiro disparo
        document.dispatchEvent(new Event('DOMContentLoaded', { bubbles: true, cancelable: true }));
        window.dispatchEvent(new Event('DOMContentLoaded', { bubbles: true, cancelable: true }));
        
        // Delay final para garantir que todos os listeners sejam processados completamente
        await new Promise(resolve => setTimeout(resolve, 50));
    }

    function triggerPageInit(pageName) {
        // Disparar evento customizado para scripts que escutam
        // Disparar no document para garantir que todos os listeners sejam notificados
        const pageReadyEvent = new CustomEvent('lidergest:page-ready', {
            detail: { page: pageName },
            bubbles: true,
            cancelable: true
        });
        document.dispatchEvent(pageReadyEvent);

        // Aguardar um pouco para garantir que os listeners do evento sejam processados
        setTimeout(() => {
            // Mapear nomes de páginas para funções de inicialização conhecidas
            const initFunctionMap = {
                'cadastros/professores': () => {
                    // Tentar função init primeiro, depois fallback para carregar
                    if (typeof initProfessores === 'function') {
                        initProfessores();
                    } else if (typeof carregarProfessores === 'function') {
                        carregarProfessores();
                    }
                },
                'cadastros/alunos': () => {
                    if (typeof initAlunos === 'function') {
                        initAlunos();
                    } else if (typeof carregarAlunos === 'function') {
                        carregarAlunos();
                    }
                },
                'cadastros/unidades': () => {
                    if (typeof initUnidades === 'function') {
                        initUnidades();
                    } else if (typeof carregarUnidades === 'function') {
                        carregarUnidades();
                    }
                },
                'cadastros/turmas': () => {
                    if (typeof initTurmas === 'function') {
                        initTurmas();
                    } else if (typeof carregarTurmas === 'function') {
                        carregarTurmas();
                    }
                },
                'cadastros/responsaveis': () => {
                    if (typeof initResponsaveis === 'function') {
                        initResponsaveis();
                    } else if (typeof carregarResponsaveis === 'function') {
                        carregarResponsaveis();
                    }
                },
                'cadastros/disciplinas': () => {
                    if (typeof initDisciplinas === 'function') {
                        initDisciplinas();
                    } else if (typeof carregarDisciplinas === 'function') {
                        carregarDisciplinas();
                    }
                },
                'cadastros/calendario_letivo': () => {
                    if (typeof initCalendarioLetivo === 'function') {
                        initCalendarioLetivo();
                    } else if (typeof carregarEventos === 'function') {
                        carregarEventos();
                    }
                },
                'pedagogico/notas': () => {
                    if (typeof initNotas === 'function') {
                        initNotas();
                    } else if (typeof carregarAlunos === 'function') {
                        carregarAlunos();
                    }
                },
                'pedagogico/frequencia': () => {
                    if (typeof initFrequencia === 'function') {
                        initFrequencia();
                    } else if (typeof carregarFrequencia === 'function') {
                        carregarFrequencia();
                    }
                },
                'pedagogico/anexos': () => {
                    if (typeof initAnexos === 'function') {
                        initAnexos();
                    } else if (typeof carregarAnexos === 'function') {
                        carregarAnexos();
                    }
                },
                'pedagogico/planos_aula': () => {
                    if (typeof initPlanosAula === 'function') {
                        initPlanosAula();
                    } else if (typeof carregarPlanosAula === 'function') {
                        carregarPlanosAula();
                    }
                },
                'pedagogico/aniversariantes': () => {
                    document.dispatchEvent(new Event('DOMContentLoaded', { bubbles: true }));
                    window.dispatchEvent(new Event('DOMContentLoaded', { bubbles: true }));
                },
                'cadastros/matriculas_pendentes': () => {
                    if (typeof initMatriculasAtivas === 'function') {
                        initMatriculasAtivas();
                    }
                    if (typeof initMatriculasPendentes === 'function') {
                        initMatriculasPendentes();
                    }
                },
                'pedagogico/matriculas_pendentes': () => {
                    if (typeof initMatriculasAtivas === 'function') {
                        initMatriculasAtivas();
                    }
                    if (typeof initMatriculasPendentes === 'function') {
                        initMatriculasPendentes();
                    }
                },
                'financeiro/planos': () => {
                    if (typeof initPlanos === 'function') {
                        initPlanos();
                    } else if (typeof carregarPlanos === 'function') {
                        carregarPlanos();
                    }
                },
                'financeiro/contratos': () => {
                    if (typeof initContratos === 'function') {
                        initContratos();
                    } else if (typeof carregarContratos === 'function') {
                        carregarContratos();
                    }
                },
                'financeiro/cobrancas': () => {
                    if (typeof initCobrancas === 'function') {
                        initCobrancas();
                    } else if (typeof carregarCobrancas === 'function') {
                        carregarCobrancas();
                    }
                },
                'financeiro/contas_financeiras': () => {
                    if (typeof initContas === 'function') {
                        initContas();
                    } else if (typeof carregarContas === 'function') {
                        carregarContas();
                    }
                },
                'financeiro/contas_bancarias': () => {
                    if (typeof initContasBancarias === 'function') {
                        initContasBancarias();
                    } else if (typeof carregarContas === 'function') {
                        carregarContas();
                    }
                },
                'financeiro/pessoas': () => {
                    if (typeof initPessoas === 'function') {
                        initPessoas();
                    } else if (typeof carregarPessoas === 'function') {
                        carregarPessoas();
                    }
                },
                'financeiro/categorias_financeiras': () => {
                    if (typeof initCategorias === 'function') {
                        initCategorias();
                    } else if (typeof carregarCategorias === 'function') {
                        carregarCategorias();
                    }
                },
                'financeiro/centros_custos': () => {
                    if (typeof initCentrosCustos === 'function') {
                        initCentrosCustos();
                    } else if (typeof carregarCentrosCustos === 'function') {
                        carregarCentrosCustos();
                    }
                },
                'financeiro/orcamentos': () => {
                    if (typeof initOrcamentos === 'function') {
                        initOrcamentos();
                    } else if (typeof carregarOrcamentos === 'function') {
                        carregarOrcamentos();
                    }
                },
                'financeiro/receitas_despesas': () => {
                    if (typeof initReceitasDespesas === 'function') {
                        initReceitasDespesas();
                    } else if (typeof carregarReceitasDespesas === 'function') {
                        carregarReceitasDespesas();
                    }
                },
                'dashboard/admin': () => {
                    if (typeof initDashboardAdmin === 'function') {
                        initDashboardAdmin();
                    } else if (typeof carregarDashboardAdmin === 'function') {
                        carregarDashboardAdmin();
                    }
                },
                'dashboard/financeiro': () => {
                    if (typeof initDashboardFinanceiro === 'function') {
                        initDashboardFinanceiro();
                    } else if (typeof carregarDashboardFinanceiro === 'function') {
                        carregarDashboardFinanceiro();
                    }
                },
                'dashboard/coordenacao': () => {
                    if (typeof initDashboardCoordenacao === 'function') {
                        initDashboardCoordenacao();
                    } else if (typeof carregarDashboardCoordenacao === 'function') {
                        carregarDashboardCoordenacao();
                    }
                },
                'admin/permissoes': () => {
                    if (typeof initPermissoes === 'function') {
                        initPermissoes();
                    } else if (typeof carregarPerfis === 'function') {
                        carregarPerfis();
                    }
                },
                'cadastros/usuarios': () => {
                    if (typeof initUsuarios === 'function') {
                        initUsuarios();
                    } else if (typeof carregarUsuarios === 'function') {
                        carregarUsuarios();
                    }
                }
            };

            // Tentar chamar função de inicialização específica da página
            if (pageName && initFunctionMap[pageName]) {
                try {
                    initFunctionMap[pageName]();
                } catch (error) {
                    console.warn(`Erro ao chamar função de inicialização para ${pageName}:`, error);
                }
            }

            // Tentar chamar função genérica initPage se existir
            if (typeof initPage === 'function') {
                try {
                    initPage();
                } catch (error) {
                    console.warn('Erro ao chamar initPage():', error);
                }
            }
        }, 100);
    }

    async function replaceContent(html, pageName) {
        // Inserir HTML no DOM
        const temp = document.createElement('div');
        temp.innerHTML = html;
        pageWrapper.innerHTML = '';
        while (temp.firstChild) {
            pageWrapper.appendChild(temp.firstChild);
        }
        
        // Aguardar renderização do navegador (múltiplos frames para garantir)
        await new Promise(resolve => requestAnimationFrame(resolve));
        await new Promise(resolve => requestAnimationFrame(resolve));
        await new Promise(resolve => requestAnimationFrame(resolve));
        
        // Aguardar elementos críticos específicos da página aparecerem no DOM
        // Isso garante que o HTML foi completamente renderizado antes de executar scripts
        await waitForCriticalElements(pageName || window.LIDERGEST_CURRENT_PAGE);
        
        // Executar scripts e aguardar carregamento completo
        await executeScripts(pageWrapper);
        
        // Simular DOMContentLoaded para que TODOS os listeners sejam executados
        // Esta é a abordagem genérica que funciona para qualquer página
        // Funciona automaticamente para scripts que usam addEventListener('DOMContentLoaded', ...)
        await simulateDOMContentLoaded();
        
        // Recriar ícones Lucide após conteúdo estar pronto
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Disparar evento customizado para scripts que preferem usar este evento
        document.dispatchEvent(new CustomEvent('lidergest:page-loaded', {
            detail: { page: pageName || window.LIDERGEST_CURRENT_PAGE },
            bubbles: true
        }));
        
        // Disparar evento lidergest:page-ready como fallback adicional
        triggerPageInit(pageName || window.LIDERGEST_CURRENT_PAGE);
    }

    async function navigateTo(url, pushHistory = true) {
        if (isNavigating) {
            return;
        }
        showLoading();
        try {
            const target = new URL(url, window.location.origin);
            target.searchParams.set('ajax', '1');

            const response = await fetch(target.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Falha ao carregar página');
            }

            const data = await response.json();
            if (!data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                throw new Error(data.message || 'Erro ao carregar conteúdo');
            }

            window.LIDERGEST_CURRENT_PAGE = data.page;
            window.LIDERGEST_PAGE_TITLE = data.title;

            // Aguardar conteúdo ser inserido e scripts executarem
            await replaceContent(data.content, data.page);
            
            if (headerTitle) {
                headerTitle.textContent = data.title;
            }
            document.title = `${data.title} - LiderGest`;
            updateActiveLinks(data.page);
            if (typeof abrirSubmenuAtual === 'function') {
                abrirSubmenuAtual();
            }

            // Sidebar permanece aberto para manter contexto visual

            if (pushHistory) {
                const newUrl = buildPageUrl(data.page);
                history.pushState({ page: data.page }, data.title, newUrl);
            }
        } catch (error) {
            console.error('Erro na navegação AJAX:', error);
            window.location.href = url;
        } finally {
            hideLoading();
        }
    }

    sidebar.addEventListener('click', event => {
        // Ignorar clicks gerados após rolagem em touch (evitar fechar o menu ao rolar)
        if (typeof touchMoved !== 'undefined' && touchMoved) {
            touchMoved = false;
            return;
        }

        const link = event.target.closest('a[href*="index.php?page="]');
        if (!link) {
            return;
        }
        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }
        const targetPage = extractPageFromHref(link.getAttribute('href'));
        if (!targetPage || targetPage === window.LIDERGEST_CURRENT_PAGE) {
            return;
        }
        event.preventDefault();

        // Sidebar permanece aberto ao clicar em links do menu

        navigateTo(link.href, true);
    });

    window.addEventListener('popstate', event => {
        if (event.state && event.state.page) {
            const url = buildPageUrl(event.state.page);
            navigateTo(url, false);
        }
    });

    if (!history.state || !history.state.page) {
        history.replaceState({ page: window.LIDERGEST_CURRENT_PAGE }, document.title, window.location.href);
    }

    updateActiveLinks(window.LIDERGEST_CURRENT_PAGE);
    if (typeof abrirSubmenuAtual === 'function') {
        abrirSubmenuAtual();
    }
})();
