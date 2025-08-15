<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM planos_aula WHERE id = :id");
$stmt->execute(['id' => $id]);
$plano = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma = $_POST['turma'];
    $disciplina = $_POST['disciplina'];
    $data = $_POST['data'];
    $conteudo = $_POST['conteudo'];
    $objetivos = $_POST['objetivos'];
    $metodologia = $_POST['metodologia'];
    $recursos = $_POST['recursos'];
    $metodo_avaliativo = $_POST['metodo_avaliativo'];

    $stmt = $pdo->prepare("UPDATE planos_aula SET turma = :turma, disciplina = :disciplina, data = :data, conteudo = :conteudo, objetivos = :objetivos, metodologia = :metodologia, recursos = :recursos, metodo_avaliativo = :metodo_avaliativo WHERE id = :id");
    $stmt->execute([
        'turma' => $turma,
        'disciplina' => $disciplina,
        'data' => $data,
        'conteudo' => $conteudo,
        'objetivos' => $objetivos,
        'metodologia' => $metodologia,
        'recursos' => $recursos,
        'metodo_avaliativo' => $metodo_avaliativo,
        'id' => $id
    ]);

    echo "<div class='alert alert-success'>Plano de aula atualizado com sucesso!</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Plano de Aula</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="text-center">Editar Plano de Aula</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="turma" class="form-label">Turma</label>
                                <input type="text" class="form-control" id="turma" name="turma" value="<?= $plano['turma'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="disciplina" class="form-label">Disciplina</label>
                                <input type="text" class="form-control" id="disciplina" name="disciplina" value="<?= $plano['disciplina'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data" name="data" value="<?= $plano['data'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="conteudo" class="form-label">Conteúdo</label>
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="3" required><?= $plano['conteudo'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="objetivos" class="form-label">Objetivos</label>
                                <textarea class="form-control" id="objetivos" name="objetivos" rows="3" required><?= $plano['objetivos'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodologia" class="form-label">Metodologia</label>
                                <textarea class="form-control" id="metodologia" name="metodologia" rows="3" required><?= $plano['metodologia'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="recursos" class="form-label">Recursos</label>
                                <textarea class="form-control" id="recursos" name="recursos" rows="3" required><?= $plano['recursos'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodo_avaliativo" class="form-label">Método Avaliativo</label>
                                <textarea class="form-control" id="metodo_avaliativo" name="metodo_avaliativo" rows="3" required><?= $plano['metodo_avaliativo'] ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>