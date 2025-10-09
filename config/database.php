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
    if (!isset($_SERVER['HTTP_HOST'])) {
        return '/';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Detecta se está em produção
    $isProduction = strpos($host, 'colegiorosadesharom.com.br') !== false;
    
    if ($isProduction) {
        return $protocol . '://' . $host . '/';
    } else {
        return $protocol . '://' . $host . '/aulas/';
    }
}

// Função para gerar URL de assets
function getAssetUrl($path) {
    if (!isset($_SERVER['HTTP_HOST'])) {
        return $path;
    }
    
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    return $baseUrl . $path;
}

// Função para gerar URL de páginas
function getPageUrl($path) {
    if (!isset($_SERVER['HTTP_HOST'])) {
        return $path;
    }
    
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // Garantir que sempre seja uma URL absoluta
    return $baseUrl . $path;
}

// Função para redirecionamento com caminho correto
function redirectTo($path) {
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    
    if (strpos($path, 'http') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . $baseUrl . $path);
    }
    exit();
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

// Função para obter conexão PDO
function getConnection() {
    global $pdo;
    return $pdo;
}
?>