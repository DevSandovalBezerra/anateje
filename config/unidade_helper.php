<?php
// Helpers de unidade para compatibilidade do modulo financeiro legado.

if (!function_exists('getUserUnidadeId')) {
    function getUserUnidadeId(): ?int
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return null;
        }

        $raw = $_SESSION['unidade_id'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }
}

