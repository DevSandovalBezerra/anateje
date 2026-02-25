<?php
// Template Base - User Dashboard

if (!defined('TEMPLATE_ROUTED')) {
    header("Location: ../../index.php");
    exit;
}
?>
<div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Dashboard do Usuário</h1>
        <p class="text-gray-600 mb-6">Aqui os usuários comuns podem ver suas informações.</p>

        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-blue-800">Olá, <strong>
                    <?php echo htmlspecialchars($user['nome'] ?? 'Visitante'); ?>
                </strong>. Seu perfil é:
                <?php echo htmlspecialchars($user['perfil_nome'] ?? 'Padrão'); ?>.
            </p>
        </div>
    </div>
</div>