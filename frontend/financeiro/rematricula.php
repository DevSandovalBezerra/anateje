<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<div class="p-6 space-y-6">
    <div class="card-primary">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-secondary-black">Renovacao de Filiacao</h1>
                <p class="text-secondary-dark-gray mt-1 max-w-3xl">
                    Controle o ciclo de anuidade dos associados com visao de status, pendencias e acompanhamento de renovacao.
                </p>
                <p class="text-sm text-secondary-dark-gray mt-2">Ciclo atual: 2026 | Isentos: 7</p>
            </div>
            <div class="flex gap-2">
                <button class="btn-secondary">
                    <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                    Enviar Lembretes
                </button>
                <button class="btn-primary">
                    <i data-lucide="receipt" class="w-4 h-4 mr-2"></i>
                    Gerar Cobrancas de Anuidade
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card-primary">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-secondary-dark-gray">Associados Elegiveis</p>
                    <p class="text-2xl font-bold text-secondary-black">156</p>
                </div>
            </div>
        </div>

        <div class="card-primary">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-secondary-dark-gray">Renovados</p>
                    <p class="text-2xl font-bold text-secondary-black">89</p>
                </div>
            </div>
        </div>

        <div class="card-primary">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-secondary-dark-gray">Pendentes</p>
                    <p class="text-2xl font-bold text-secondary-black">45</p>
                </div>
            </div>
        </div>

        <div class="card-primary">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-secondary-dark-gray">Nao Renovados</p>
                    <p class="text-2xl font-bold text-secondary-black">22</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <h3 class="text-lg font-semibold text-secondary-black mb-4">Funil de Anuidade</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600 mb-2">45</div>
                <div class="text-sm text-yellow-800 font-medium">Pendente</div>
                <div class="text-xs text-yellow-600 mt-1">29%</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600 mb-2">89</div>
                <div class="text-sm text-green-800 font-medium">Renovado</div>
                <div class="text-xs text-green-600 mt-1">57%</div>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-3xl font-bold text-red-600 mb-2">22</div>
                <div class="text-sm text-red-800 font-medium">Nao Renovado</div>
                <div class="text-xs text-red-600 mt-1">14%</div>
            </div>
            <div class="text-center p-4 bg-gray-100 rounded-lg">
                <div class="text-3xl font-bold text-gray-700 mb-2">7</div>
                <div class="text-sm text-gray-800 font-medium">Isento</div>
                <div class="text-xs text-gray-600 mt-1">4%</div>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar Associado</label>
                <input type="text" placeholder="Nome do associado..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-blue focus:border-transparent">
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-blue focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="renovado">Renovado</option>
                    <option value="nao_renovado">Nao Renovado</option>
                    <option value="isento">Isento</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Nucleo/Categoria</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-blue focus:border-transparent">
                    <option value="">Todas</option>
                    <option value="1">Titular</option>
                    <option value="2">Contribuinte</option>
                    <option value="3">Institucional</option>
                </select>
            </div>
            <div class="min-w-40">
                <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Competencia</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-blue focus:border-transparent">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="btn-primary">
                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                    Filtrar
                </button>
            </div>
        </div>
    </div>

    <div class="card-primary">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-secondary-black">Lista de Renovacao</h3>
            <button class="btn-secondary">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                Exportar CSV
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Associado</th>
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Categoria</th>
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Plano de Anuidade</th>
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Status</th>
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Data Limite</th>
                        <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">JS</div>
                                <div>
                                    <h4 class="font-medium text-secondary-black">Joao Silva</h4>
                                    <p class="text-sm text-secondary-dark-gray">joao.silva@email.com</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Titular</td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Anuidade Integral</td>
                        <td class="py-4 px-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Renovado</span></td>
                        <td class="py-4 px-4 text-secondary-dark-gray">15/02/2026</td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                <button class="text-green-600 hover:text-green-700" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">AS</div>
                                <div>
                                    <h4 class="font-medium text-secondary-black">Ana Santos</h4>
                                    <p class="text-sm text-secondary-dark-gray">ana.santos@email.com</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Contribuinte</td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Anuidade Parcelada</td>
                        <td class="py-4 px-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendente</span></td>
                        <td class="py-4 px-4 text-red-500 font-medium">15/02/2026</td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                <button class="text-orange-600 hover:text-orange-700" title="Lembrete"><i data-lucide="mail" class="w-4 h-4"></i></button>
                                <button class="text-green-600 hover:text-green-700" title="Registrar pagamento"><i data-lucide="check" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">PC</div>
                                <div>
                                    <h4 class="font-medium text-secondary-black">Pedro Costa</h4>
                                    <p class="text-sm text-secondary-dark-gray">pedro.costa@email.com</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Institucional</td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Anuidade Integral</td>
                        <td class="py-4 px-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Nao Renovado</span></td>
                        <td class="py-4 px-4 text-red-500 font-medium">10/02/2026</td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                <button class="text-orange-600 hover:text-orange-700" title="Reabrir negociacao"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">ML</div>
                                <div>
                                    <h4 class="font-medium text-secondary-black">Marina Lima</h4>
                                    <p class="text-sm text-secondary-dark-gray">marina.lima@email.com</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Titular</td>
                        <td class="py-4 px-4 text-secondary-dark-gray">Isencao</td>
                        <td class="py-4 px-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Isento</span></td>
                        <td class="py-4 px-4 text-secondary-dark-gray">--</td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                <button class="text-green-600 hover:text-green-700" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-secondary-dark-gray">Mostrando 1-4 de 156 associados</div>
            <div class="flex space-x-2">
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>Anterior</button>
                <button class="px-3 py-2 text-sm bg-primary-blue text-white rounded-lg">1</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">2</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Proximo</button>
            </div>
        </div>
    </div>
</div>

<script>
if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
}
</script>
