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
            'dashboard/user' => 'dashboard/user.php',

            'associado/perfil' => 'associado/perfil.php',
            'associado/meus_beneficios' => 'associado/meus_beneficios.php',
            'associado/meus_eventos' => 'associado/meus_eventos.php',
            'associado/comunicados' => 'associado/comunicados.php',

            'admin/associados' => 'admin/associados.php',
            'admin/beneficios' => 'admin/beneficios.php',
            'admin/eventos' => 'admin/eventos.php',
            'admin/comunicados' => 'admin/comunicados.php',
            'admin/campanhas' => 'admin/campanhas.php',
            'admin/integracoes' => 'admin/integracoes.php',
            'admin/permissoes' => 'admin/permissoes.php',

            'cadastros/usuarios' => 'cadastros/usuarios.php'
        ];
    }

    /**
     * Obtem a pagina padrao baseada no perfil do usuario
     */
    public function getDefaultPage($perfil_id)
    {
        // 1 = Admin, outros = User comum
        return ($perfil_id == 1) ? 'home' : 'dashboard/user';
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

        // Pagina de usuarios: apenas Admin global
        if ($module === 'cadastros' && $pageName === 'usuarios') {
            return $perfil_id == 1;
        }

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
