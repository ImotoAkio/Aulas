<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/config/database.php';
    redirectTo('login.php');
    exit;
}

// Verifica se o coordenador clicou no botão "Aprovar"
if (isset($_GET['aprovar'])) {
    $id = $_GET['aprovar'];
    $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'aprovado' WHERE id = :id");
    $stmt->execute(['id' => $id]);
    echo "<div class='alert alert-success'>Plano de aula aprovado com sucesso!</div>";
}

// Verifica se o coordenador enviou o formulário de revisão
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['marcar_revisao'])) {
    $id = $_POST['id'];
    $mensagem_revisao = $_POST['mensagem_revisao'];

    $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'revisao', mensagem_revisao = :mensagem_revisao WHERE id = :id");
    $stmt->execute(['mensagem_revisao' => $mensagem_revisao, 'id' => $id]);

    echo "<div class='alert alert-warning'>Plano de aula marcado para revisão.</div>";
}

// Filtra apenas os planos com status "pendente"
$stmt = $pdo->query("SELECT pa.id, u.nome AS professor, pa.turma, pa.disciplina, pa.data, pa.status FROM planos_aula pa JOIN usuarios u ON pa.professor_id = u.id WHERE pa.status = 'pendente'");
$planos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Planos de Aula</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <a href="javascript:location.reload();" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> <!-- Ícone de recarregar -->
                    </a>
                        <h4 class="text-center m-0">Planos de Aula Pendentes</h4>
                        <a href="aprovados.php" class="btn btn-success">Visualizar Aprovados</a>
                    </div>
                    <div class="card-body">
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
                                        <a href="?aprovar=<?= $plano['id'] ?>" class="btn btn-success btn-sm">Aprovar</a>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#revisaoModal<?= $plano['id'] ?>">Marcar para Revisão</button>
                                        <a href="editar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-info btn-sm">Editar</a>
                                        <a href="visualizar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-secondary btn-sm">Visualizar</a>

                                        <!-- Modal para Marcar como Revisão -->
                                        <div class="modal fade" id="revisaoModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning text-white">
                                                        <h5 class="modal-title" id="exampleModalLabel">Marcar Plano para Revisão</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="mensagem_revisao" class="form-label">Mensagem de Revisão</label>
                                                                <textarea class="form-control" id="mensagem_revisao" name="mensagem_revisao" rows="3" required></textarea>
                                                            </div>
                                                            <button type="submit" name="marcar_revisao" class="btn btn-warning w-100">Enviar para Revisão</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>