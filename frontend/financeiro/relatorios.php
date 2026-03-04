<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<!-- Header -->
    <!-- Conteúdo -->
    <main class="p-6">
        <!-- Filtros -->
        <div class="card-primary mb-6">
            <div class="flex flex-wrap gap-4">
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Período</label>
                    <select class="input-primary">
                        <option>Últimos 3 meses</option>
                        <option>Últimos 6 meses</option>
                        <option>Último ano</option>
                        <option>Personalizado</option>
                    </select>
                </div>
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Unidade</label>
                    <select class="input-primary">
                        <option>Todas</option>
                        <option>Centro</option>
                        <option>Norte</option>
                        <option>Sul</option>
                    </select>
                </div>
                <div class="min-w-48">
                    <label class="block text-sm font-medium text-secondary-dark-gray mb-2">Tipo de Relatório</label>
                    <select class="input-primary">
                        <option>Receitas</option>
                        <option>Inadimplência</option>
                        <option>Comparativo</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="card-primary">
                <h3 class="text-lg font-semibold text-secondary-black mb-4">Receita Mensal</h3>
                <canvas id="receitaChart" width="400" height="200"></canvas>
            </div>
            
            <div class="card-primary">
                <h3 class="text-lg font-semibold text-secondary-black mb-4">Inadimplência</h3>
                <canvas id="inadimplenciaChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Tabelas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Resumo Financeiro -->
            <div class="card-primary">
                <h3 class="text-lg font-semibold text-secondary-black mb-4">Resumo Financeiro</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <div>
                            <p class="font-medium text-secondary-black">Receita Total</p>
                            <p class="text-sm text-secondary-dark-gray">Últimos 3 meses</p>
                        </div>
                        <p class="text-xl font-bold text-green-600">R$ 76.200</p>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                        <div>
                            <p class="font-medium text-secondary-black">Inadimplência</p>
                            <p class="text-sm text-secondary-dark-gray">Valor em aberto</p>
                        </div>
                        <p class="text-xl font-bold text-red-600">R$ 3.600</p>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <div>
                            <p class="font-medium text-secondary-black">Taxa de Inadimplência</p>
                            <p class="text-sm text-secondary-dark-gray">Percentual</p>
                        </div>
                        <p class="text-xl font-bold text-blue-600">4.7%</p>
                    </div>
                </div>
            </div>

            <!-- Top Inadimplentes -->
            <div class="card-primary">
                <h3 class="text-lg font-semibold text-secondary-black mb-4">Top Inadimplentes</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                GO
                            </div>
                            <div>
                                <p class="font-medium text-secondary-black">Gabriel Oliveira</p>
                                <p class="text-sm text-secondary-dark-gray">2 meses em atraso</p>
                            </div>
                        </div>
                        <p class="font-bold text-red-600">R$ 1.200</p>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                IS
                            </div>
                            <div>
                                <p class="font-medium text-secondary-black">Isabella Santos</p>
                                <p class="text-sm text-secondary-dark-gray">1 mês em atraso</p>
                            </div>
                        </div>
                        <p class="font-bold text-yellow-600">R$ 250</p>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                MC
                            </div>
                            <div>
                                <p class="font-medium text-secondary-black">Mateus Costa</p>
                                <p class="text-sm text-secondary-dark-gray">1 mês em atraso</p>
                            </div>
                        </div>
                        <p class="font-bold text-orange-600">R$ 150</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        // Gráfico de Receita
        const receitaCtx = document.getElementById('receitaChart').getContext('2d');
        new Chart(receitaCtx, {
            type: 'line',
            data: {
                labels: ['Ago', 'Set', 'Out'],
                datasets: [{
                    label: 'Receita (R$)',
                    data: [22000, 25400, 28800],
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Inadimplência
        const inadimplenciaCtx = document.getElementById('inadimplenciaChart').getContext('2d');
        new Chart(inadimplenciaCtx, {
            type: 'doughnut',
            data: {
                labels: ['Em Dia', 'Inadimplente'],
                datasets: [{
                    data: [95.3, 4.7],
                    backgroundColor: ['#10B981', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>


