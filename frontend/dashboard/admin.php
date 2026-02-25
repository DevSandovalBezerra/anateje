<?php
// Dashboard Admin

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Dashboard Admin</h1>
        <p class="text-gray-600 mb-6">Este conteudo foi carregado dentro do wrapper principal via include do index.php.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-lg border border-indigo-100 bg-indigo-50">
                <p class="text-sm text-indigo-700">Gerencie permissoes e acessos.</p>
            </div>
            <div class="p-4 rounded-lg border border-amber-100 bg-amber-50">
                <p class="text-sm text-amber-700">Monitore usuarios e configuracoes.</p>
            </div>
            <div class="p-4 rounded-lg border border-emerald-100 bg-emerald-50">
                <p class="text-sm text-emerald-700">Adicione modulos ANATEJE de forma incremental.</p>
            </div>
        </div>
    </div>
</div>
