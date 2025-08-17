<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Bem-vindo ao sistema de aulas</h1>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="aulas.php">Aulas</a></li>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Aulas disponíveis</h2>
        <ul>
            <li><a href="aula1.php">Aula 1: Introdução ao PHP</a></li>
            <li><a href="aula2.php">Aula 2: Estruturas de Controle</a></li>
            <li><a href="aula3.php">Aula 3: Funções</a></li>
        </ul>
    </main>
    <footer>
        <p>&copy; 2023 Sistema de Aulas</p>
    </footer>
</body>
</html>