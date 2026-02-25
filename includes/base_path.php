<?php
// Helper para detectar o caminho base do LiderGest
// Evita hardcode da pasta em ambientes distintos
// Funciona em dev (localhost) e produção (hospedagem compartilhada)

if (!function_exists('lidergest_base_url')) {
    function lidergest_base_url()
    {
        // Cache estático para evitar recálculo em múltiplas chamadas
        static $cachedBaseUrl = null;
        if ($cachedBaseUrl !== null) {
            return $cachedBaseUrl;
        }

        // Prioridade 1: Verificar variável de ambiente (útil para configuração manual)
        if (defined('LIDERGEST_BASE_URL')) {
            $cachedBaseUrl = LIDERGEST_BASE_URL;
            return $cachedBaseUrl;
        }

        // Prioridade 2: Usar SCRIPT_NAME (mais confiável que REQUEST_URI)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (!empty($scriptName)) {
            $normalizedPath = str_replace('\\', '/', $scriptName);
            
            // Se estiver em /api/, subir até encontrar a raiz (independente de quantos níveis dentro de /api/)
            $apiPos = strpos($normalizedPath, '/api/');
            if ($apiPos !== false) {
                // Se /api/ está no início (posição 0), base URL é raiz
                if ($apiPos === 0) {
                    $cachedBaseUrl = '';
                    return $cachedBaseUrl;
                }
                // Se /api/ está depois, extrair tudo antes para obter a raiz do projeto
                $cachedBaseUrl = rtrim(substr($normalizedPath, 0, $apiPos), '/');
                return $cachedBaseUrl;
            }
            
            // Se estiver em /frontend/, pegar o caminho antes
            $frontendPos = strpos($normalizedPath, '/frontend/');
            if ($frontendPos !== false && $frontendPos > 0) {
                $cachedBaseUrl = rtrim(substr($normalizedPath, 0, $frontendPos), '/');
                return $cachedBaseUrl;
            }
            
            // Se estiver em subdiretório dentro de /frontend/ (ex: /frontend/dashboard/index.php)
            // Subir até encontrar a raiz do projeto
            if (strpos($normalizedPath, '/frontend/') !== false) {
                $parts = explode('/frontend/', $normalizedPath);
                if (count($parts) > 1) {
                    $cachedBaseUrl = rtrim($parts[0], '/');
                    return $cachedBaseUrl;
                }
            }

            // Se o script está na raiz (index.php), verificar REQUEST_URI
            if (basename($normalizedPath) === 'index.php' || $normalizedPath === '/index.php') {
                $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                if (!empty($requestUri)) {
                    $uriPath = parse_url($requestUri, PHP_URL_PATH);
                    if ($uriPath && $uriPath !== '/') {
                        // Remover query string e fragment
                        $uriPath = str_replace('\\', '/', $uriPath);
                        // Se contém /frontend/ ou /api/, extrair base
                        $frontendPos = strpos($uriPath, '/frontend/');
                        if ($frontendPos !== false && $frontendPos > 0) {
                            $cachedBaseUrl = rtrim(substr($uriPath, 0, $frontendPos), '/');
                            return $cachedBaseUrl;
                        }
                        $apiPos = strpos($uriPath, '/api/');
                        if ($apiPos !== false && $apiPos > 0) {
                            $cachedBaseUrl = rtrim(substr($uriPath, 0, $apiPos), '/');
                            return $cachedBaseUrl;
                        }
                    }
                }
            }

            // Fallback: usar dirname do script, mas verificar se não está dentro de /api/ ou /frontend/
            $dir = rtrim(dirname($normalizedPath), '/');
            if ($dir !== '/' && $dir !== '.') {
                // Se o próprio dirname começa com /api ou /frontend, base URL é raiz
                if (strpos($dir, '/api') === 0 || strpos($dir, '/frontend') === 0) {
                    $cachedBaseUrl = '';
                    return $cachedBaseUrl;
                }
                
                // Se o dirname contém /api/ ou /frontend/, extrair apenas a parte antes
                $apiPos = strpos($dir, '/api/');
                if ($apiPos !== false) {
                    // Se /api/ está no início (posição 0), base URL é raiz
                    if ($apiPos === 0) {
                        $cachedBaseUrl = '';
                        return $cachedBaseUrl;
                    }
                    // Se /api/ está depois, extrair tudo antes
                    $cachedBaseUrl = rtrim(substr($dir, 0, $apiPos), '/');
                    return $cachedBaseUrl;
                }
                $frontendPos = strpos($dir, '/frontend/');
                if ($frontendPos !== false) {
                    // Se /frontend/ está no início (posição 0), base URL é raiz
                    if ($frontendPos === 0) {
                        $cachedBaseUrl = '';
                        return $cachedBaseUrl;
                    }
                    // Se /frontend/ está depois, extrair tudo antes
                    $cachedBaseUrl = rtrim(substr($dir, 0, $frontendPos), '/');
                    return $cachedBaseUrl;
                }
                // Se não contém /api/ ou /frontend/, usar o dirname normalmente
                $cachedBaseUrl = $dir;
                return $cachedBaseUrl;
            }
        }

        // Prioridade 3: Tentar detectar via REQUEST_URI (útil quando SCRIPT_NAME não é confiável)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($requestUri)) {
            $uriPath = parse_url($requestUri, PHP_URL_PATH);
            if ($uriPath && $uriPath !== '/') {
                $uriPath = str_replace('\\', '/', $uriPath);
                $frontendPos = strpos($uriPath, '/frontend/');
                if ($frontendPos !== false && $frontendPos > 0) {
                    $cachedBaseUrl = rtrim(substr($uriPath, 0, $frontendPos), '/');
                    return $cachedBaseUrl;
                }
                $apiPos = strpos($uriPath, '/api/');
                if ($apiPos !== false && $apiPos > 0) {
                    $cachedBaseUrl = rtrim(substr($uriPath, 0, $apiPos), '/');
                    return $cachedBaseUrl;
                }
            }
        }

        // Fallback final: string vazia (raiz do domínio)
        $cachedBaseUrl = '';
        return $cachedBaseUrl;
    }
}

if (!function_exists('lidergest_base_prefix')) {
    function lidergest_base_prefix()
    {
        $baseUrl = lidergest_base_url();
        return $baseUrl ? "{$baseUrl}/" : '/';
    }
}

if (!function_exists('lidergest_full_url')) {
    /**
     * Gera URL completa (com protocolo e domínio) para links públicos
     * Detecta automaticamente se está em localhost ou produção
     */
    function lidergest_full_url($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                    ? 'https://' : 'http://';
        
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Se estiver em localhost, manter porta se não for padrão
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            if (strpos($host, ':') === false) {
                $port = $_SERVER['SERVER_PORT'] ?? '';
                if ($port && $port != '80' && $port != '443') {
                    $host .= ':' . $port;
                }
            }
        }
        
        // Usar função base_url que já detecta corretamente o caminho base
        $basePath = lidergest_base_url();
        
        // Limpar e normalizar o path
        $path = ltrim($path, '/');
        
        // Construir caminho completo
        $fullPath = $basePath ? rtrim($basePath, '/') . '/' . $path : '/' . $path;
        
        // Limpar barras duplicadas
        $fullPath = preg_replace('#/+#', '/', $fullPath);
        
        return $protocol . $host . $fullPath;
    }
}

