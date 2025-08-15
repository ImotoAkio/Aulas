<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'];

// Consulta para obter os detalhes do plano de aula e o nome do professor
$stmt = $pdo->prepare("
    SELECT pa.*, u.nome AS professor_nome 
    FROM planos_aula pa 
    JOIN usuarios u ON pa.professor_id = u.id 
    WHERE pa.id = :id
");
$stmt->execute(['id' => $id]);
$plano = $stmt->fetch();

if (!$plano) {
    die("Plano de aula não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Plano de Aula</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                    <a href="listar_planos.php" class="btn btn-primary">Voltar</a>
                        <h4 class="text-center">Detalhes do Plano de Aula</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Professor:</strong> <?= htmlspecialchars($plano['professor_nome']) ?></p>
                        <p><strong>Turma:</strong> <?= htmlspecialchars($plano['turma']) ?></p>
                        <p><strong>Disciplina:</strong> <?= htmlspecialchars($plano['disciplina']) ?></p>
                        <p><strong>Data:</strong> <?= htmlspecialchars($plano['data']) ?></p>
                        <p><strong>Conteúdo:</strong> <?= nl2br(htmlspecialchars($plano['conteudo'])) ?></p>
                        <p><strong>Objetivos:</strong> <?= nl2br(htmlspecialchars($plano['objetivos'])) ?></p>
                        <p><strong>Metodologia:</strong> <?= nl2br(htmlspecialchars($plano['metodologia'])) ?></p>
                        <p><strong>Recursos:</strong> <?= nl2br(htmlspecialchars($plano['recursos'])) ?></p>
                        <p><strong>Método Avaliativo:</strong> <?= nl2br(htmlspecialchars($plano['metodo_avaliativo'])) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($plano['status']) ?></p>
                        <?php if ($plano['mensagem_revisao']): ?>
                            <p><strong>Mensagem de Revisão:</strong> <?= nl2br(htmlspecialchars($plano['mensagem_revisao'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>