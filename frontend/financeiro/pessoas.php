<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-secondary-black">Pessoas</h1>
            <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                Gerencie associados, fornecedores, colaboradores e outros contatos relacionados ao financeiro.
            </p>
        </div>
        <button id="btnNovaPessoa" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Nova Pessoa
        </button>
    </div>
    
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo</label>
                <select id="selectFiltroTipo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="cliente">Associado</option>
                    <option value="fornecedor">Fornecedor</option>
                    <option value="colaborador">Colaborador</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="selectFiltroAtivo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="inputBuscar" placeholder="Nome, documento, email..." class="input-primary pl-10">
                </div>
            </div>
            <div class="flex items-end">
                <button id="btnFiltrar" class="btn-secondary">
                    <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                    Filtrar
                </button>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Documento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Telefone</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbodyPessoas" class="divide-y divide-secondary-gray">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">
                            <i data-lucide="loader" class="w-6 h-6 mx-auto mb-2 animate-spin"></i>
                            <p>Carregando...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/pessoas.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>


