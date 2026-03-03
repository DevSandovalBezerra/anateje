<?php

if (!function_exists('admin_render_toolbar')) {
    /**
     * Renderiza toolbar padrao do admin (titulo/subtitulo + acoes).
     *
     * @param string $title
     * @param string $subtitle
     * @param array<int, array<string, string>> $actions
     * @param array<string, string> $opts
     */
    function admin_render_toolbar(string $title, string $subtitle = '', array $actions = [], array $opts = []): void
    {
        $wrapperClass = trim((string) ($opts['wrapper_class'] ?? 'mb-6'));
        if ($wrapperClass === '') {
            $wrapperClass = 'mb-6';
        }

        echo '<div class="admin-toolbar ' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '">';
        echo '<div class="admin-toolbar-copy">';
        echo '<h1 class="text-2xl font-bold text-gray-800">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        if ($subtitle !== '') {
            echo '<p class="text-gray-600">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '</div>';

        if (!empty($actions)) {
            echo '<div class="admin-toolbar-actions">';
            foreach ($actions as $action) {
                $tag = strtolower(trim((string) ($action['tag'] ?? 'button')));
                if (!in_array($tag, ['button', 'a'], true)) {
                    $tag = 'button';
                }

                $id = trim((string) ($action['id'] ?? ''));
                $label = (string) ($action['label'] ?? '');
                $class = trim((string) ($action['class'] ?? 'btn-secondary px-4 py-2 text-sm'));
                if ($class === '') {
                    $class = 'btn-secondary px-4 py-2 text-sm';
                }

                $attrs = '';
                if ($id !== '') {
                    $attrs .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
                }
                if ($class !== '') {
                    $attrs .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
                }

                if ($tag === 'a') {
                    $href = (string) ($action['href'] ?? '#');
                    $attrs .= ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
                    echo '<a' . $attrs . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
                    continue;
                }

                $type = strtolower(trim((string) ($action['type'] ?? 'button')));
                if (!in_array($type, ['button', 'submit', 'reset'], true)) {
                    $type = 'button';
                }
                $attrs .= ' type="' . $type . '"';
                echo '<button' . $attrs . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
            }
            echo '</div>';
        }

        echo '</div>';
    }
}
