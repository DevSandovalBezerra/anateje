<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<main class="px-6 py-6 space-y-6">
    <div class="card-primary">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-secondary-black">Contas a Pagar e Receber</h1>
                <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                    Controle compromissos financeiros da associacao com parcelas, vencimentos e status de quitacao.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="btnNovaConta" class="btn-primary" type="button">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    Novo Lancamento
                </button>
                <a href="<?php echo $baseUrl; ?>/index.php?page=financeiro/lancamentos" class="btn-secondary">
                    <i data-lucide="file-text" class="w-4 h-4 mr-2"></i>
                    Abrir Lancamentos
                </a>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo</label>
                <select id="filtroTipo" class="input-primary">
                    <option value="">Todos</option>
                    <option value="pagar">A Pagar</option>
                    <option value="receber">A Receber</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="filtroStatus" class="input-primary">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="parcial">Parcial</option>
                    <option value="pago">Pago</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="filtroBusca" placeholder="Descricao, documento ou associado..." class="input-primary pl-10">
                </div>
            </div>
            <div>
                <button id="btnFiltrar" class="btn-secondary">
                    <i data-lucide="sliders" class="w-4 h-4 mr-2"></i>
                    Aplicar
                </button>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Descricao</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Vencimento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Parcelas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbodyLancamentos" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</main>

<div id="modalLancamento" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-secondary-black">Novo Lancamento</h2>
            <button id="btnFecharModal" class="text-secondary-dark-gray hover:text-secondary-black">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <form id="formLancamento" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo *</label>
                    <select id="inputTipo" required class="input-primary w-full">
                        <option value="receber">A Receber</option>
                        <option value="pagar">A Pagar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data de Emissao *</label>
                    <input type="date" id="inputDataEmissao" required class="input-primary w-full" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Descricao *</label>
                <input type="text" id="inputDescricao" required class="input-primary w-full" placeholder="Ex: Anuidade associativa - lote de janeiro">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Valor Total (R$) *</label>
                    <input type="number" step="0.01" id="inputValorTotal" required class="input-primary w-full" placeholder="0,00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data de Vencimento</label>
                    <input type="date" id="inputVencimento" class="input-primary w-full">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Parcelas *</label>
                    <input type="number" min="1" id="inputParcelas" required class="input-primary w-full" value="1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">1o Vencimento *</label>
                    <input type="date" id="inputPrimeiroVencimento" required class="input-primary w-full" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 pt-4">
                <button type="button" id="btnCancelar" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalParcelas" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-secondary-black">Parcelas e Pagamentos</h2>
            <button id="btnFecharParcelas" class="text-secondary-dark-gray hover:text-secondary-black">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div id="parcelasInfo" class="text-secondary-dark-gray"></div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-secondary-light-gray">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Parcela</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Vencimento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Pagamento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyParcelas" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/assets/js/api-config.js"></script>
<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/contas.js"></script>
<script>
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
</script>


