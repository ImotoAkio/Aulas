<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    header('Location: login.php');
    exit;
}

// Verifica quantos planos de aula estão marcados como "revisao" para o professor logado
$professor_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM planos_aula WHERE professor_id = :professor_id AND status = 'revisao'");
$stmt->execute(['professor_id' => $professor_id]);
$total_revisao = $stmt->fetch()['total'];

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma = $_POST['turma'];
    $disciplina = $_POST['disciplina'];
    $data = $_POST['data'];
    $conteudo = $_POST['conteudo'];
    $objetivos = $_POST['objetivos'];
    $metodologia = $_POST['metodologia'];
    $recursos = $_POST['recursos'];
    $metodo_avaliativo = $_POST['metodo_avaliativo'];

    // Insere o plano de aula no banco de dados
    $stmt = $pdo->prepare("INSERT INTO planos_aula (professor_id, turma, disciplina, data, conteudo, objetivos, metodologia, recursos, metodo_avaliativo) VALUES (:professor_id, :turma, :disciplina, :data, :conteudo, :objetivos, :metodologia, :recursos, :metodo_avaliativo)");
    $stmt->execute([
        'professor_id' => $professor_id,
        'turma' => $turma,
        'disciplina' => $disciplina,
        'data' => $data,
        'conteudo' => $conteudo,
        'objetivos' => $objetivos,
        'metodologia' => $metodologia,
        'recursos' => $recursos,
        'metodo_avaliativo' => $metodo_avaliativo
    ]);

    echo "<div class='alert alert-success'>Plano de aula inserido com sucesso!</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserir Plano de Aula</title>
    <!-- Bootstrap CSS -->
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">

                        <h4 class="text-center">Inserir Plano de Aula</h4>
                        <a href="meus_planos.php" class="btn btn-secondary">Meus Planos</a>
                        <a href="javascript:location.reload();" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> <!-- Ícone de recarregar -->
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($total_revisao > 0): ?>
                            <div class="alert alert-warning">
                                <h5>Você tem <?= $total_revisao ?> plano(s) de aula para revisão!</h5>
                                <a href="planos_revisao.php" class="btn btn-warning">Ver Planos para Revisão</a>
                            </div>
                        <?php endif; ?>


                        <form method="POST">
                            <div class="mb-3">
                                <label for="turma" class="form-label">Turma</label>
                                <select class="form-select" id="turma" name="turma" required>
                                    <option value="" disabled selected>Selecione uma turma</option>
                                    <option value="G2 & G4">G2 & G3</option>
                                    <option value="G4 & G5">G4 & G5</option>
                                    <?php
                                    // Loop para gerar as opções de turmas do 1° ao 9° ano
                                    for ($i = 1; $i <= 9; $i++) {
                                        echo "<option value='{$i}° ano'>{$i}° ano</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="disciplina" class="form-label">Disciplina</label>
                                <input type="text" class="form-control" id="disciplina" name="disciplina" required>
                            </div>
                            <div class="mb-3">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data" name="data" required>
                            </div>
                            <div class="mb-3">
                                <label for="conteudo" class="form-label">Conteúdo</label>
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="objetivos" class="form-label">Objetivos</label>
                                <textarea class="form-control" id="objetivos" name="objetivos" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodologia" class="form-label">Metodologia</label>
                                <textarea class="form-control" id="metodologia" name="metodologia" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="recursos" class="form-label">Recursos</label>
                                <textarea class="form-control" id="recursos" name="recursos" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodo_avaliativo" class="form-label">Método Avaliativo</label>
                                <textarea class="form-control" id="metodo_avaliativo" name="metodo_avaliativo" rows="3"
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Enviar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>