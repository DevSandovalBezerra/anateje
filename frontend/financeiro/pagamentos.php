<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-secondary-black">Pagamentos</h1>
            <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                Acompanhe recebimentos e pagamentos confirmados no financeiro da associacao.
            </p>
        </div>
    </div>

    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select id="selectFiltroStatus" class="input-primary">
                    <option value="">Todos</option>
                    <option value="confirmado">Confirmado</option>
                    <option value="pendente">Pendente</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Forma de Pagamento</label>
                <select id="selectFiltroFormaPagamento" class="input-primary">
                    <option value="">Todas</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="pix">PIX</option>
                    <option value="cartao">Cartao</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Inicio</label>
                <input type="date" id="inputDataInicio" class="input-primary">
            </div>
            <div class="min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Fim</label>
                <input type="date" id="inputDataFim" class="input-primary">
            </div>
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                    <input type="text" id="inputBuscar" placeholder="Associado, numero do pagamento..." class="input-primary pl-10">
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Total de Pagamentos</div>
            <div class="text-2xl font-bold text-secondary-black" id="statTotalPagamentos">0</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Valor Total Recebido</div>
            <div class="text-2xl font-bold text-green-600" id="statValorTotal">R$ 0,00</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Pagamentos Confirmados</div>
            <div class="text-2xl font-bold text-green-600" id="statConfirmados">0</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Pagamentos Pendentes</div>
            <div class="text-2xl font-bold text-yellow-600" id="statPendentes">0</div>
        </div>
    </div>

    <div class="card-primary">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Numero</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Associado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Data Pagamento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Forma</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Cobranca</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbodyPagamentos" class="divide-y divide-secondary-gray"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/pagamentos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>


