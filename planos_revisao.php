<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    require_once __DIR__ . '/config/database.php';
    redirectTo('login.php');
    exit;
}

$professor_id = $_SESSION['usuario_id'];

// Filtra apenas os planos do professor com status "revisao"
$stmt = $pdo->prepare("SELECT * FROM planos_aula WHERE professor_id = :professor_id AND status = 'revisao'");
$stmt->execute(['professor_id' => $professor_id]);
$planos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Aula em Revisão</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h4 class="text-center">Planos de Aula em Revisão</h4>
                        <a href="listar_planos.php" class="btn btn-primary">Voltar</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($planos) > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Turma</th>
                                        <th>Disciplina</th>
                                        <th>Data</th>
                                        <th>Mensagem de Revisão</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planos as $plano): ?>
                                    <tr>
                                        <td><?= $plano['id'] ?></td>
                                        <td><?= $plano['turma'] ?></td>
                                        <td><?= $plano['disciplina'] ?></td>
                                        <td><?= $plano['data'] ?></td>
                                        <td><?= $plano['mensagem_revisao'] ?? 'Nenhuma mensagem disponível.' ?></td>
                                        <td>
                                            <a href="editar_plano_professor.php?id=<?= $plano['id'] ?>" class="btn btn-info btn-sm">Editar</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">Nenhum plano de aula em revisão.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>