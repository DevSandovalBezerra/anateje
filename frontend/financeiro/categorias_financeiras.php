<?php
// ANATEJE - Categorias Financeiras (Conteúdo)
// Sistema de Gestao Financeira Associativa ANATEJE

require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <button id="btnNovaCategoria" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Nova Categoria
        </button>
    </div>
    
    <!-- Filtros -->
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="inputBuscar" placeholder="Nome da categoria..." class="input-primary pl-10">
                </div>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo</label>
                <select id="selectFiltroTipo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="selectFiltroAtivo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="1">Ativa</option>
                    <option value="0">Inativa</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Lista de Categorias -->
    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Categoria</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Categoria Pai</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Unidade</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodyCategorias" class="divide-y divide-secondary-gray">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/categorias_financeiras.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>




