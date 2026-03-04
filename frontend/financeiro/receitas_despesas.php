<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-secondary-black">Receitas e Despesas Fixas</h1>
            <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                Modelos padronizados de lançamentos recorrentes para geração automática de contas a pagar/receber.
            </p>
        </div>
        <button id="btnNovoRegistro" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Novo Registro
        </button>
    </div>
    
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo</label>
                <select id="selectFiltroTipo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="receita">Receitas</option>
                    <option value="despesa">Despesas</option>
                </select>
            </div>
            <div class="min-w-48" id="filtroUnidadeContainer">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Unidade</label>
                <select id="selectFiltroUnidade" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Centro de Custo</label>
                <select id="selectFiltroCentroCusto" class="input-primary">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Categoria</label>
                <select id="selectFiltroCategoria" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="inputBuscar" placeholder="Nome ou descrição..." class="input-primary pl-10">
                </div>
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="selectFiltroAtivo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Centro de Custo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Categoria</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Periodicidade</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Dia Venc.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodyRegistros" class="divide-y divide-secondary-gray">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/receitas_despesas.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>



