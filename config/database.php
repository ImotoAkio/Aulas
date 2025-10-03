<?php
/**
 * Arquivo de configuração centralizada para conexão com banco de dados
 * 
 * Para facilitar o deploy, altere apenas as configurações abaixo:
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u894209272_planos_aula');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8');

// Configurações PDO
define('PDO_ERRMODE', PDO::ERRMODE_EXCEPTION);
define('PDO_FETCH_MODE', PDO::FETCH_ASSOC);

// Função para detectar ambiente e corrigir caminhos
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Detecta se está em produção (baseado no domínio)
    $isProduction = strpos($host, 'colegiorosadesharom.com.br') !== false;
    
    if ($isProduction) {
        // Em produção, usar caminho raiz
        return $protocol . '://' . $host . '/';
    } else {
        // Em desenvolvimento local, detectar o diretório base
        $pathParts = explode('/', trim(dirname($scriptName), '/'));
        $basePath = '';
        
        // Se estiver em uma subpasta (como /aulas/), incluir no caminho
        if (in_array('aulas', $pathParts)) {
            $basePath = '/aulas/';
        } else {
            $basePath = '/';
        }
        
        return $protocol . '://' . $host . $basePath;
    }
}

// Função para gerar URL de assets
function getAssetUrl($path) {
    $baseUrl = getBaseUrl();
    
    // Remove barra inicial se existir
    $path = ltrim($path, '/');
    
    // Se o caminho já contém o domínio completo, usar como está
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    return $baseUrl . $path;
}

// Função para gerar URL de páginas
function getPageUrl($path) {
    return getAssetUrl($path);
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
        DB_USER, 
        DB_PASS
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO_ERRMODE);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO_FETCH_MODE);
    
} catch (PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Erro na conexão com o banco de dados. Verifique as configurações.");
}
?>
