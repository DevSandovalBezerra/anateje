<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-secondary-black">LanÃ§amentos Financeiros</h1>
            <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                Gerencie receitas e despesas com parcelamento, recorrÃªncia e aÃ§Ãµes em massa.
            </p>
        </div>
        <div class="flex gap-2">
            <button id="btnAcoesMassa" class="btn-secondary" style="display: none;">
                <i data-lucide="layers" class="w-4 h-4 mr-2"></i>
                AÃ§Ãµes em Massa
            </button>
            <button id="btnNovoLancamento" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                Novo LanÃ§amento
            </button>
        </div>
    </div>
    
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo</label>
                <select id="selectFiltroTipo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                    <option value="transferencia">TransferÃªncia</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="selectFiltroStatus" class="input-primary">
                    <option value="">Todos</option>
                    <option value="previsto">Previsto</option>
                    <option value="aberto">Aberto</option>
                    <option value="pendente">Pendente</option>
                    <option value="parcial">Parcial</option>
                    <option value="quitado">Quitado</option>
                    <option value="pago">Pago</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Conta BancÃ¡ria</label>
                <select id="selectFiltroConta" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Categoria</label>
                <select id="selectFiltroCategoria" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data InÃ­cio</label>
                <input type="date" id="inputDataInicio" class="input-primary">
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Fim</label>
                <input type="date" id="inputDataFim" class="input-primary">
            </div>
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="inputBuscar" placeholder="TÃ­tulo, descriÃ§Ã£o..." class="input-primary pl-10">
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
                        <th class="px-4 py-2 text-left">
                            <input type="checkbox" id="checkAll" class="rounded">
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">TÃ­tulo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Pessoa</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Vencimento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Parcelas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">AÃ§Ãµes</th>
                    </tr>
                </thead>
                <tbody id="tbodyLancamentos" class="divide-y divide-secondary-gray">
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-secondary-dark-gray">
                            <i data-lucide="loader" class="w-6 h-6 mx-auto mb-2 animate-spin"></i>
                            <p>Carregando...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/lancamentos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>



