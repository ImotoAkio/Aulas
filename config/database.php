<?php
/**
 * Arquivo de configuração centralizada para conexão com banco de dados
 * Ajustado para Ambiente Docker VPS
 */

// --- CONFIGURAÇÕES DO BANCO (DOCKER) ---
// O Host deve ser o nome do serviço do banco na Stack (não é localhost)
define('DB_HOST', 'database_mariadb');

// Nome do banco que você criou no phpMyAdmin para este sistema (ex: sistema_rosa)
// ATENÇÃO: Não use o nome antigo da Hostinger (u894209...) a menos que tenha criado igual.
define('DB_NAME', 'sistema_rosa');

// Usuário do banco (No Docker geralmente usamos root ou um user específico criado)
define('DB_USER', 'root');

// A senha que você definiu na Stack do banco de dados (A mesma usada no Educx)
define('DB_PASS', 'Akio2604*');

define('DB_CHARSET', 'utf8');

// Configurações PDO
define('PDO_ERRMODE', PDO::ERRMODE_EXCEPTION);
define('PDO_FETCH_MODE', PDO::FETCH_ASSOC);

// --- FUNÇÕES DE URL ---

// Função para detectar ambiente e corrigir caminhos
// Função para detectar ambiente e corrigir caminhos (VERSÃO CORRIGIDA PARA DOCKER/TRAEFIK)
function getBaseUrl()
{
    if (!isset($_SERVER['HTTP_HOST'])) {
        return '/';
    }

    $host = $_SERVER['HTTP_HOST'];

    // Detecta se está em produção (Seu domínio oficial)
    $isProduction = strpos($host, 'colegiorosadesharom.com.br') !== false;

    // --- A CORREÇÃO ESTÁ AQUI ---
    // 1. Se o Traefik avisar que é HTTPS (HTTP_X_FORWARDED_PROTO)
    // 2. OU se sabemos que é o domínio de produção (que sempre tem SSL)
    // Então forçamos 'https'
    if (
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        $isProduction
    ) {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    if ($isProduction) {
        return $protocol . '://' . $host . '/';
    } else {
        return $protocol . '://' . $host . '/aulas/';
    }
}

// Função para gerar URL de assets
function getAssetUrl($path)
{
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
function getPageUrl($path)
{
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

// Função para redirecionamento
function redirectTo($path)
{
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');

    if (strpos($path, 'http') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . $baseUrl . $path);
    }
    exit();
}

// --- CONEXÃO PDO ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO_ERRMODE);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO_FETCH_MODE);

} catch (PDOException $e) {
    // Log do erro para debug (pode ver nos logs do container)
    error_log("Erro SQL: " . $e->getMessage());

    // Mensagem amigável para o usuário
    die("Erro na conexão com o banco de dados. Verifique: 1. Se o banco '" . DB_NAME . "' existe. 2. Se a senha está correta.");
}

function getConnection()
{
    global $pdo;
    return $pdo;
}
?>