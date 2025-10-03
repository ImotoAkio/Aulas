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
