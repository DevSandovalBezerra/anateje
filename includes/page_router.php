<?php
// Template Base - Sistema de roteamento de paginas

require_once __DIR__ . '/rbac.php';

class PageRouter
{
    private $rbac;
    private $basePath;
    private $pageMapping;

    public function __construct()
    {
        $this->rbac = new RBAC();
        $this->basePath = __DIR__ . '/../frontend';
        $this->pageMapping = $this->getPageMapping();
    }

    /**
     * Whitelist de paginas permitidas para include no wrapper
     */
    private function getPageMapping()
    {
        return [
            'home' => 'home.php',

            'dashboard/admin' => 'dashboard/admin.php',
            'dashboard/financeiro' => 'financeiro/dashboard.php',
            'dashboard/user' => 'dashboard/user.php',

            'associado/perfil' => 'associado/perfil.php',
            'associado/meus_beneficios' => 'associado/meus_beneficios.php',
            'associado/meus_eventos' => 'associado/meus_eventos.php',
            'associado/comunicados' => 'associado/comunicados.php',

            'admin/associados' => 'admin/associados.php',
            'admin/pastas_associados' => 'admin/pastas_associados.php',
            'admin/beneficios' => 'admin/beneficios.php',
            'admin/eventos' => 'admin/eventos.php',
            'admin/comunicados' => 'admin/comunicados.php',
            'admin/campanhas' => 'admin/campanhas.php',
            'admin/integracoes' => 'admin/integracoes.php',
            'admin/permissoes' => 'admin/permissoes.php',
            'admin/auditoria' => 'admin/auditoria.php',

            'financeiro/manual' => 'financeiro/manual.php',
            'financeiro/lancamentos' => 'financeiro/lancamentos.php',
            'financeiro/contas_bancarias' => 'financeiro/contas_bancarias.php',
            'financeiro/pessoas' => 'financeiro/pessoas.php',
            'financeiro/categorias_financeiras' => 'financeiro/categorias_financeiras.php',
            'financeiro/centros_custos' => 'financeiro/centros_custos.php',
            'financeiro/receitas_despesas' => 'financeiro/receitas_despesas.php',
            'financeiro/planos' => 'financeiro/planos.php',
            'financeiro/orcamentos' => 'financeiro/orcamentos.php',
            'financeiro/contratos' => 'financeiro/contratos.php',
            'financeiro/cobrancas' => 'financeiro/cobrancas.php',
            'financeiro/renovacao_filiacao' => 'financeiro/rematricula.php',
            'financeiro/rematricula' => 'financeiro/rematricula.php',
            'financeiro/dashboard' => 'financeiro/dashboard.php',
            'financeiro/fluxo_caixa' => 'financeiro/fluxo_caixa.php',
            'financeiro/conciliacao' => 'financeiro/conciliacao.php',
            'financeiro/relatorios' => 'financeiro/relatorios.php',
            'financeiro/contas' => 'financeiro/contas.php',
            'financeiro/contas_financeiras' => 'financeiro/contas_financeiras.php',
            'financeiro/pagamentos' => 'financeiro/pagamentos.php',
            'financeiro/transferencias' => 'financeiro/transferencias.php',

            'cadastros/usuarios' => 'cadastros/usuarios.php'
        ];
    }

    /**
     * Obtem a pagina padrao baseada no perfil do usuario
     */
    public function getDefaultPage($perfil_id)
    {
        $permissions = $this->rbac->getUserPermissions($perfil_id);
        $dashboards = $permissions['dashboard'] ?? [];

        if (in_array('admin', $dashboards, true)) {
            return 'dashboard/admin';
        }
        if (in_array('financeiro', $dashboards, true)) {
            return 'dashboard/financeiro';
        }
        if (in_array('user', $dashboards, true)) {
            return 'dashboard/user';
        }
        if (!empty($dashboards)) {
            return 'dashboard/' . (string) reset($dashboards);
        }

        foreach ($permissions as $module => $pages) {
            if ($module === 'dashboard' || empty($pages)) {
                continue;
            }
            $page = (string) reset($pages);
            if ($page !== '' && isset($this->pageMapping[$module . '/' . $page])) {
                return $module . '/' . $page;
            }
        }

        return 'home';
    }

    private function isValidPageFormat($page)
    {
        if ($page === 'home') {
            return true;
        }

        return (bool) preg_match('/^[a-z0-9_]+\/[a-z0-9_]+$/', $page);
    }

    /**
     * Valida se o usuario tem permissao para acessar a pagina
     */
    public function validatePage($perfil_id, $page)
    {
        if ($page === 'home') {
            return true;
        }

        $parts = explode('/', $page);
        if (count($parts) !== 2) {
            return false;
        }

        $module = $parts[0];
        $pageName = $parts[1];

        return $this->rbac->canAccessPage($perfil_id, $module, $pageName);
    }

    /**
     * Resolve a rota e retorna o caminho do arquivo
     */
    public function resolve($page, $perfil_id)
    {
        if (empty($page)) {
            $page = $this->getDefaultPage($perfil_id);
        }

        // Canonicaliza rota antiga para manter coerencia de titulo/menu.
        if ($page === 'financeiro/rematricula') {
            $page = 'financeiro/renovacao_filiacao';
        }

        if (!$this->isValidPageFormat($page)) {
            return [
                'success' => false,
                'redirect' => $this->getDefaultPage($perfil_id)
            ];
        }

        if (!isset($this->pageMapping[$page])) {
            return [
                'success' => false,
                'redirect' => $this->getDefaultPage($perfil_id)
            ];
        }

        if (!$this->validatePage($perfil_id, $page)) {
            return [
                'success' => false,
                'redirect' => $this->getDefaultPage($perfil_id)
            ];
        }

        $filePath = $this->basePath . '/' . $this->pageMapping[$page];

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'redirect' => $this->getDefaultPage($perfil_id)
            ];
        }

        return [
            'success' => true,
            'file' => $filePath,
            'page' => $page
        ];
    }
}
