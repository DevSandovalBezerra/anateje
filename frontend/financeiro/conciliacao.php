<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<!-- Header -->
    <!-- Conteúdo -->
    <div class="p-6">
        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-secondary-dark-gray">Arquivos Processados</p>
                        <p class="text-2xl font-bold text-secondary-black">12</p>
                    </div>
                </div>
            </div>

            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-secondary-dark-gray">Conciliados</p>
                        <p class="text-2xl font-bold text-secondary-black">89%</p>
                    </div>
                </div>
            </div>

            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-secondary-dark-gray">Pendentes</p>
                        <p class="text-2xl font-bold text-secondary-black">23</p>
                    </div>
                </div>
            </div>

            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i data-lucide="dollar-sign" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-secondary-dark-gray">Valor Total</p>
                        <p class="text-2xl font-bold text-secondary-black">R$ 45.230</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload de Arquivo -->
        <div class="card-primary mb-6">
            <h3 class="text-lg font-semibold text-secondary-black mb-4">Importar Arquivo de Retorno</h3>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <i data-lucide="upload" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                <p class="text-secondary-dark-gray mb-2">Arraste e solte o arquivo aqui ou clique para selecionar</p>
                <p class="text-sm text-gray-500 mb-4">Formatos suportados: .txt, .ret, .ofx</p>
                <button class="btn-primary">
                    <i data-lucide="file" class="w-4 h-4 mr-2"></i>
                    Selecionar Arquivo
                </button>
            </div>
        </div>

        <!-- Histórico de Conciliações -->
        <div class="card-primary">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-secondary-black">Histórico de Conciliações</h3>
                <div class="flex space-x-2">
                    <button class="btn-secondary">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                        Filtrar
                    </button>
                    <button class="btn-secondary">
                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                        Exportar
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Data</th>
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Arquivo</th>
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Registros</th>
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Conciliados</th>
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-secondary-dark-gray">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">15/01/2025</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 text-blue-500 mr-2"></i>
                                    <span class="text-secondary-dark-gray">retorno_15012025.txt</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">156</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">142</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Concluída
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-green-600 hover:text-green-700" title="Download">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">14/01/2025</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 text-blue-500 mr-2"></i>
                                    <span class="text-secondary-dark-gray">retorno_14012025.ret</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">89</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">89</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Concluída
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-green-600 hover:text-green-700" title="Download">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">13/01/2025</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 text-blue-500 mr-2"></i>
                                    <span class="text-secondary-dark-gray">retorno_13012025.ofx</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">203</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">195</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Processando
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-orange-600 hover:text-orange-700" title="Continuar">
                                        <i data-lucide="play" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">12/01/2025</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 text-red-500 mr-2"></i>
                                    <span class="text-secondary-dark-gray">retorno_12012025.txt</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">67</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-secondary-dark-gray">0</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Erro
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button class="text-primary-blue hover:text-primary-light-blue" title="Visualizar">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-700" title="Reprocessar">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Inicializar ícones
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    </script>


