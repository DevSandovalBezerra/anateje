<?php
// Template Base - Home Page

if (!defined('TEMPLATE_ROUTED')) {
    header("Location: ../index.php");
    exit;
}
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Bem-vindo ao Template Base</h1>
        <p class="text-gray-600 mb-6">Este é um template genérico construído a partir do sistema original.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-6 border rounded-lg bg-indigo-50 border-indigo-100">
                <i data-lucide="shield" class="w-8 h-8 text-indigo-500 mb-3"></i>
                <h3 class="text-xl font-semibold mb-2 text-indigo-900">RBAC Simples</h3>
                <p class="text-sm text-indigo-700">Sistema de rotas e permissões componentizado, pronto para escalar.
                </p>
            </div>

            <div class="p-6 border rounded-lg bg-green-50 border-green-100">
                <i data-lucide="layout" class="w-8 h-8 text-green-500 mb-3"></i>
                <h3 class="text-xl font-semibold mb-2 text-green-900">Layout SPA</h3>
                <p class="text-sm text-green-700">Carregamento dinâmico otimizado, mantendo o controle no lado do
                    servidor com PHP.</p>
            </div>

            <div class="p-6 border rounded-lg bg-blue-50 border-blue-100">
                <i data-lucide="code" class="w-8 h-8 text-blue-500 mb-3"></i>
                <h3 class="text-xl font-semibold mb-2 text-blue-900">Design System</h3>
                <p class="text-sm text-blue-700">Baseado em Tailwind CSS para desenvolvimento rápido de componentes
                    visuais.</p>
            </div>
        </div>
    </div>
</div>