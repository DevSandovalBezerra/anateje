<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<!-- Header -->
    <!-- ConteÃºdo -->
    <main class="p-6">
        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-blue rounded-lg">
                        <i data-lucide="credit-card" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-dark-gray">Planos Ativos</p>
                        <p class="text-2xl font-bold text-secondary-black">5</p>
                    </div>
                </div>
            </div>
            
            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-green-500 rounded-lg">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-dark-gray">Associados Ativos</p>
                        <p class="text-2xl font-bold text-secondary-black">156</p>
                    </div>
                </div>
            </div>
            
            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-500 rounded-lg">
                        <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-dark-gray">Receita Mensal</p>
                        <p class="text-2xl font-bold text-secondary-black">R$ 25.400</p>
                    </div>
                </div>
            </div>
            
            <div class="card-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-red-500 rounded-lg">
                        <i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-secondary-dark-gray">Inadimplentes</p>
                        <p class="text-2xl font-bold text-secondary-black">12</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Planos -->
        <div class="card-primary">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-secondary-light-gray">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Plano</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">PerÃ­odo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Associados</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-gray">
                        <tr>
                            <td class="px-4 py-2">
                                <div>
                                    <p class="font-medium text-secondary-black">Plano BÃ¡sico</p>
                                    <p class="text-sm text-secondary-dark-gray">Acesso completo</p>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 150,00</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Mensal</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">89</td>
                            <td class="px-4 py-2">
                                <span class="badge-success">Ativo</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-red-500 hover:text-red-600" title="Excluir">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="px-4 py-2">
                                <div>
                                    <p class="font-medium text-secondary-black">Plano Premium</p>
                                    <p class="text-sm text-secondary-dark-gray">Acesso completo + extras</p>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 250,00</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Mensal</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">45</td>
                            <td class="px-4 py-2">
                                <span class="badge-success">Ativo</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-red-500 hover:text-red-600" title="Excluir">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="px-4 py-2">
                                <div>
                                    <p class="font-medium text-secondary-black">Plano Anual</p>
                                    <p class="text-sm text-secondary-dark-gray">Desconto anual</p>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 1.200,00</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Anual</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">22</td>
                            <td class="px-4 py-2">
                                <span class="badge-success">Ativo</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-red-500 hover:text-red-600" title="Excluir">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    </script>


