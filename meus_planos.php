<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    header('Location: login.php');
    exit;
}

$professor_id = $_SESSION['usuario_id'];

// Filtra os planos de aula do professor logado
$stmt = $pdo->prepare("
    SELECT pa.*, u.nome AS professor_nome 
    FROM planos_aula pa 
    JOIN usuarios u ON pa.professor_id = u.id 
    WHERE pa.professor_id = :professor_id
");
$stmt->execute(['professor_id' => $professor_id]);
$planos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Planos de Aula</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="text-center m-0">Meus Planos de Aula</h4>
                        <a href="inserir_plano.php" class="btn btn-secondary">Inserir Novo Plano</a>
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
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($planos as $plano): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($plano['id']) ?></td>
                                        <td><?= htmlspecialchars($plano['turma']) ?></td>
                                        <td><?= htmlspecialchars($plano['disciplina']) ?></td>
                                        <td><?= htmlspecialchars($plano['data']) ?></td>
                                        <td><?= htmlspecialchars($plano['status']) ?></td>
                                        <td>
                                            <a href="visualizar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-info btn-sm">Visualizar</a>
                                            <?php if ($plano['status'] == 'revisao'): ?>
                                                <a href="editar_plano_professor.php?id=<?= $plano['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">Você ainda não enviou nenhum plano de aula.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>