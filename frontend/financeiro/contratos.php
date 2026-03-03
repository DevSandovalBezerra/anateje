<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<!-- Header -->
    <!-- ConteÃºdo -->
    <main class="p-6">
        <!-- Filtros -->
        <div class="card-primary mb-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Buscar</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-secondary-dark-gray"></i>
                        <input type="text" placeholder="Nome do associado..." class="input-primary pl-10">
                    </div>
                </div>
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Status</label>
                    <select class="input-primary">
                        <option>Todos</option>
                        <option>Ativo</option>
                        <option>Inativo</option>
                        <option>Pendente</option>
                    </select>
                </div>
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Plano</label>
                    <select class="input-primary">
                        <option>Todos</option>
                        <option>BÃ¡sico</option>
                        <option>Premium</option>
                        <option>Anual</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Lista de Contratos -->
        <div class="card-primary">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-secondary-light-gray">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Associado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">ResponsÃ¡vel</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Plano</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Valor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-secondary-dark-gray uppercase">AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary-gray">
                        <tr>
                            <td class="px-4 py-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-primary-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                        LA
                                    </div>
                                    <div>
                                        <p class="font-medium text-secondary-black">Lucas Alves</p>
                                        <p class="text-sm text-secondary-dark-gray">Categoria Titular</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Carlos Alves</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Plano BÃ¡sico</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 150,00</td>
                            <td class="px-4 py-2">
                                <span class="badge-success">Ativo</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-700" title="Ver contrato">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="px-4 py-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                        SS
                                    </div>
                                    <div>
                                        <p class="font-medium text-secondary-black">Sofia Silva</p>
                                        <p class="text-sm text-secondary-dark-gray">Categoria Contribuinte</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Maria Silva</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Plano Premium</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 250,00</td>
                            <td class="px-4 py-2">
                                <span class="badge-success">Ativo</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-700" title="Ver contrato">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="px-4 py-2">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                        GO
                                    </div>
                                    <div>
                                        <p class="font-medium text-secondary-black">Gabriel Oliveira</p>
                                        <p class="text-sm text-secondary-dark-gray">Categoria Institucional</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Pedro Oliveira</td>
                            <td class="px-4 py-2 text-sm text-secondary-dark-gray">Plano Anual</td>
                            <td class="px-4 py-2 text-sm font-medium text-secondary-black">R$ 1.200,00</td>
                            <td class="px-4 py-2">
                                <span class="badge-warning">Pendente</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <button class="text-green-600 hover:text-green-700" title="Editar">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button class="text-purple-600 hover:text-purple-700" title="Ver contrato">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
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


