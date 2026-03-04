<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="cadastros-content" data-perfil="<?php echo $_SESSION['perfil_id'] ?? 0; ?>" data-unidade="<?php echo $_SESSION['unidade_id'] ?? ''; ?>">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-secondary-black">Dashboard Financeiro</h1>
        <p class="text-secondary-dark-gray mt-1 max-w-3xl">
            Visão geral das finanças com indicadores e gráficos.
        </p>
    </div>
    
    <div class="card-primary mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Período</label>
                <select id="selectPeriodo" class="input-primary">
                    <option value="hoje">Hoje</option>
                    <option value="semana">Esta Semana</option>
                    <option value="mes" selected>Este Mês</option>
                    <option value="trimestre">Este Trimestre</option>
                    <option value="ano">Este Ano</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div class="min-w-32" id="divDataInicio" style="display: none;">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Início</label>
                <input type="date" id="inputDataInicio" class="input-primary">
            </div>
            <div class="min-w-32" id="divDataFim" style="display: none;">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Data Fim</label>
                <input type="date" id="inputDataFim" class="input-primary">
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Conta Bancária</label>
                <select id="selectConta" class="input-primary">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="btnAtualizar" class="btn-secondary">
                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                    Atualizar
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Entradas Realizadas</div>
            <div class="text-2xl font-bold text-green-600" id="statEntradasRealizado">R$ 0,00</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Saídas Realizadas</div>
            <div class="text-2xl font-bold text-red-600" id="statSaidasRealizado">R$ 0,00</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Saldo Atual</div>
            <div class="text-2xl font-bold text-secondary-black" id="statSaldoAtual">R$ 0,00</div>
        </div>
        <div class="card-primary">
            <div class="text-sm text-secondary-dark-gray mb-1">Saldo Previsto</div>
            <div class="text-2xl font-bold text-blue-600" id="statSaldoPrevisto">R$ 0,00</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="card-primary">
            <h3 class="text-lg font-semibold text-secondary-black mb-4">Por Categoria</h3>
            <canvas id="chartCategorias"></canvas>
        </div>
        <div class="card-primary">
            <h3 class="text-lg font-semibold text-secondary-black mb-4">Por Centro de Custo</h3>
            <canvas id="chartCentrosCusto"></canvas>
        </div>
    </div>

    <div class="card-primary">
        <h3 class="text-lg font-semibold text-secondary-black mb-4">Saldos por Conta</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-secondary-light-gray">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Conta</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Saldo Inicial</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Entradas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Saídas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Saldo Real</th>
                    </tr>
                </thead>
                <tbody id="tbodySaldos" class="divide-y divide-secondary-gray">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
<script src="<?php echo $baseUrl; ?>/frontend/js/financeiro/dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>



