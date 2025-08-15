<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: login.php');
    exit;
}

// Filtra apenas os planos com status "aprovado"
$stmt = $pdo->query("SELECT pa.id, u.nome AS professor, pa.turma, pa.disciplina, pa.data, pa.status FROM planos_aula pa JOIN usuarios u ON pa.professor_id = u.id WHERE pa.status = 'aprovado'");
$planos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Aula Aprovados</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="text-center">Planos de Aula Aprovados</h4>
                        <a href="listar_planos.php" class="btn btn-primary">Voltar</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($planos) > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Professor</th>
                                        <th>Turma</th>
                                        <th>Disciplina</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planos as $plano): ?>
                                    <tr>
                                        <td><?= $plano['id'] ?></td>
                                        <td><?= $plano['professor'] ?></td>
                                        <td><?= $plano['turma'] ?></td>
                                        <td><?= $plano['disciplina'] ?></td>
                                        <td><?= $plano['data'] ?></td>
                                        <td><?= $plano['status'] ?></td>
                                        <td>
                                            <a href="visualizar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-info btn-sm">Visualizar</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">Nenhum plano de aula aprovado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>