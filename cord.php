<?php
// iniciar.php

// Verificação se o usuário está logado
session_start();
if(!isset($_SESSION['usuario_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header('Location: login.php');
    exit();
}

// Caso esteja logado, exibe a página com as opções
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar - Sistema de Notas</title>
    <!-- Incluindo o Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 100px;
            text-align: center;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            background-color: #ffffff;
        }
        .card-header {
            background-color: #343a40;
            color: #fff;
            font-size: 1.5rem;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        .btn-custom {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: #28a745;
            color: #fff;
            transform: translateY(-2px);
        }
        .text-custom {
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Bem-vindo ao Colégio Rosa de Sharom</h1>
            </div>
            <div class="card-body">
                <p class="text-custom mb-4">Escolha uma das opções abaixo para continuar:</p>

                <a href="dashboard.php" class="btn btn-success btn-custom mb-3">Dashboard Notas</a>
                <a href="ver_planos.php" class="btn btn-primary btn-custom">Ver Plano de Aula</a>
            </div>
        </div>
    </div>

    <!-- Incluindo os scripts do Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
