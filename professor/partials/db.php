<?php
$host = 'localhost';
$dbname = 'u894209272_planos_aula';
$username = 'u894209272_planos_aula';
$password = 'Akio2604*';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>