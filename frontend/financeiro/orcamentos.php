<?php
// ANATEJE - OrÃ§amentos (ConteÃºdo)
// Sistema de Gestao Financeira Associativa ANATEJE

require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <button id="btnNovoOrcamento" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Novo OrÃ§amento
        </button>
    </div>
    
    <!-- Filtros -->
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Ano</label>
                <input type="number" id="inputFiltroAno" placeholder="2024" class="input-primary" min="2020" max="2100">
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">MÃªs</label>
                <select id="selectFiltroMes" class="input-primary">
                    <option value="">Todos</option>
                    <option value="1">Janeiro</option>
                    <option value="2">Fevereiro</option>
                    <option value="3">MarÃ§o</option>
                    <option value="4">Abril</option>
                    <option value="5">Maio</option>
                    <option value="6">Junho</option>
                    <option value="7">Julho</option>
                    <option value="8">Agosto</option>
                    <option value="9">Setembro</option>
                    <option value="10">Outubro</option>
                    <option value="11">Novembro</option>
                    <option value="12">Dezembro</option>
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
        </div>
    </div>

    <!-- Lista de OrÃ§amentos -->
    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">PerÃ­odo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Centro de Custo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Categoria</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor OrÃ§ado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor Revisado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Unidade</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">AÃ§Ãµes</th>
                    </tr>
                </thead>
                <tbody id="tbodyOrcamentos" class="divide-y divide-secondary-gray">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/orcamentos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>




