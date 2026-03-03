<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-secondary-black">Transferencias entre Contas</h1>
            <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                Registre transferencias internas entre contas bancarias e carteiras da associacao.
            </p>
        </div>
        <button id="btnNovaTransferencia" class="btn-primary">
            <i data-lucide="arrow-left-right" class="w-4 h-4 mr-2"></i>
            Nova Transferencia
        </button>
    </div>

    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Conta Origem</label>
                <select id="selectFiltroOrigem" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Conta Destino</label>
                <select id="selectFiltroDestino" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Inicio</label>
                <input type="date" id="inputDataInicio" class="input-primary">
            </div>
            <div class="min-w-32">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Fim</label>
                <input type="date" id="inputDataFim" class="input-primary">
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
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Titulo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Conta Origem</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Conta Destino</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Acoes</th>
                    </tr>
                </thead>
                <tbody id="tbodyTransferencias" class="divide-y divide-secondary-gray">
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-secondary-dark-gray">
                            <i data-lucide="loader" class="w-6 h-6 mx-auto mb-2 animate-spin"></i>
                            <p>Carregando...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/transferencias.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>


