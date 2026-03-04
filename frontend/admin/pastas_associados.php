<?php
// ANATEJE - Pastas de Associados (Explorer)

if (!defined('TEMPLATE_ROUTED')) {
    header('Location: ../../index.php');
    exit;
}

$basePrefix = isset($prefix) ? $prefix : '/';
?>
<div class="pedagogico-content">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-secondary-black">Pastas de Associados</h2>
            <p class="text-sm text-secondary-dark-gray">Explorer de pastas e arquivos por associado.</p>
        </div>
        <button id="btnAtualizarArvoreTop" type="button" class="btn-secondary text-sm">Atualizar</button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <aside class="card-primary xl:col-span-3">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-secondary-black">Pastas</h3>
                <button id="btnAtualizarArvore" class="text-xs text-primary-blue hover:text-primary-light-blue" type="button">Atualizar</button>
            </div>
            <div id="explorerTree" class="max-h-[70vh] overflow-y-auto space-y-1 pr-1"></div>
        </aside>

        <section id="explorerWorkspace" class="card-primary xl:col-span-9">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-[260px] flex-1">
                    <p id="explorerBreadcrumb" class="text-sm text-secondary-dark-gray">Carregando...</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button id="btnVoltarPai" type="button" class="btn-secondary text-sm hidden">Voltar</button>
                    <button id="btnIrRaiz" type="button" class="btn-secondary text-sm hidden">Raiz</button>
                    <button id="btnNovaPasta" class="btn-secondary text-sm" type="button">
                        <i data-lucide="folder-plus" class="w-4 h-4 mr-2"></i>
                        Nova Pasta
                    </button>
                    <label for="inputUploadArquivo" class="btn-primary text-sm cursor-pointer">
                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                        Upload
                    </label>
                    <input id="inputUploadArquivo" type="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
                </div>
            </div>

            <div id="dropzoneUpload" class="mb-4 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-secondary-dark-gray transition-colors">
                Arraste e solte arquivos aqui para enviar para a pasta atual.
            </div>

            <div class="mb-4">
                <input id="inputBuscaItens" type="text" placeholder="Buscar item nesta pasta" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-blue focus:border-transparent" />
            </div>

            <div id="explorerRootCards" class="mb-4 hidden"></div>

            <div id="explorerList" class="overflow-x-auto hidden">
                <table id="explorerTable" class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-secondary-dark-gray">
                            <th class="px-3 py-2">Nome</th>
                            <th class="px-3 py-2">Tipo</th>
                            <th class="px-3 py-2">Tamanho</th>
                            <th class="px-3 py-2">Atualizado</th>
                            <th class="px-3 py-2">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyExplorerItens"></tbody>
                </table>
            </div>

            <div id="fileContextMenu" class="hidden fixed z-[70] min-w-[160px] rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-context-action="copy">Copiar</button>
                <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-context-action="cut">Recortar</button>
                <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-context-action="paste">Colar</button>
                <button type="button" class="w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100" data-context-action="rename">Renomear</button>
                <button type="button" class="w-full rounded px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50" data-context-action="delete">Excluir</button>
            </div>
        </section>
    </div>
</div>

<script src="<?php echo $basePrefix; ?>frontend/js/admin/pastas_associados.js?v=<?php echo (int) @filemtime(__DIR__ . '/../js/admin/pastas_associados.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
