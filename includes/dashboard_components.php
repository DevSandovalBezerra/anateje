<?php

if (!function_exists('dashboard_components_styles')) {
    function dashboard_components_styles(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
        <style>
            .dash-surface {
                background: var(--bg-card);
                border: 1px solid var(--border-primary);
                border-radius: var(--border-radius-lg);
                box-shadow: var(--shadow-sm);
            }
            .dash-muted {
                color: var(--text-secondary);
            }
            .dash-title {
                color: var(--text-primary);
                letter-spacing: 0.02em;
            }
            .dash-link {
                color: var(--an-info);
                text-decoration: none;
                font-weight: 600;
            }
            .dash-link:hover {
                text-decoration: underline;
            }
            .dash-focus:focus-visible {
                outline: 3px solid var(--border-focus);
                outline-offset: 2px;
                border-radius: 0.5rem;
            }
            .dash-kpi-value {
                color: var(--text-primary);
                line-height: 1.2;
            }
            .dash-empty {
                color: var(--text-secondary);
                border: 1px dashed var(--border-secondary);
                border-radius: 0.5rem;
                padding: 0.75rem;
                background: var(--bg-secondary);
            }
        </style>
        <?php
    }
}

if (!function_exists('dashboard_section_header')) {
    function dashboard_section_header(string $title, string $href = '', string $label = ''): void
    {
        echo '<div class="flex items-center justify-between mb-4">';
        echo '<h2 class="text-lg font-semibold dash-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
        if ($href !== '' && $label !== '') {
            echo '<a class="dash-link dash-focus text-sm" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        echo '</div>';
    }
}

if (!function_exists('dashboard_kpi_card')) {
    function dashboard_kpi_card(
        string $title,
        string $valueId,
        string $metaId,
        string $metaText = '---',
        string $initialValue = '0'
    ): void
    {
        echo '<article class="dash-surface p-4">';
        echo '<p class="text-xs uppercase tracking-wide dash-muted">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p id="' . htmlspecialchars($valueId, ENT_QUOTES, 'UTF-8') . '" class="mt-2 text-2xl font-bold dash-kpi-value">'
            . htmlspecialchars($initialValue, ENT_QUOTES, 'UTF-8')
            . '</p>';
        echo '<p id="' . htmlspecialchars($metaId, ENT_QUOTES, 'UTF-8') . '" class="text-sm dash-muted">'
            . htmlspecialchars($metaText, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</article>';
    }
}

if (!function_exists('dashboard_quick_link')) {
    function dashboard_quick_link(string $href, string $label): void
    {
        echo '<a class="btn-secondary dash-focus px-4 py-2 text-sm text-center" href="'
            . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
