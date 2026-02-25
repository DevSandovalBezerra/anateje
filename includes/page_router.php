<?php
// Template Base - Sistema de Roteamento de Páginas

require_once __DIR__ . '/rbac.php';

class PageRouter
{
    private $rbac;
    private $basePath;

    public function __construct()
    {
        $this->rbac = new RBAC();
        $this->basePath = __DIR__ . '/../frontend';
    }

    /**
     * Mapeia nomes de páginas para arquivos físicos
     */
    private function getPageMapping()
    {
        return [
            'home' => 'home.php',
            'dashboard/admin' => 'dashboard/admin.php',
            'dashboard/user' => 'dashboard/user.php',
            'admin/permissoes' => 'admin/permissoes.php',
            'cadastros/usuarios' => 'cadastros/usuarios.php'
        ];
    }

    /**
     * Obtém a página padrão baseada no perfil do usuário
     */
    public function getDefaultPage($perfil_id)
    {
        // 1 = Admin, Outros = User Comum
        return ($perfil_id == 1) ? 'home' : 'dashboard/user';
    }

    /**
     * Valida se o usuário tem permissão para acessar a página
     */
    public function validatePage($perfil_id, $page)
    {
        if ($page === 'home') {
            return true; // Todos que logarem podem ter acesso se liberado, mas vamos verificar com RBAC no arquivo
        }

        $parts = explode('/', $page);
        if (count($parts) !== 2) {
            // Home ou página solta
            return true;
        }

        $module = $parts[0];
        $pageName = $parts[1];

        // Página de usuários: apenas Admin Global
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

        if (!$this->validatePage($perfil_id, $page)) {
            return [
                'success' => false,
                'redirect' => $this->getDefaultPage($perfil_id)
            ];
        }

        $mapping = $this->getPageMapping();

        // Se a página não está no mapeamento rígido, mas segue um padrão
        if (!isset($mapping[$page])) {
            $filePath = $this->basePath . '/' . $page . '.php';
        } else {
            $filePath = $this->basePath . '/' . $mapping[$page];
        }

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
