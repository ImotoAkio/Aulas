<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db.php');
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    header('Location: login.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$turma_id = $_GET['turma_id'] ?? null;
$disciplina_id = $_GET['disciplina_id'] ?? null;
$unidade_get = $_GET['unidade'] ?? null;
$aluno_id_get = $_GET['aluno_id'] ?? null;
$unidade = $unidade_get !== null ? (int)$unidade_get : null;
$exibir_tabela_resumo = isset($_GET['finalizado']) && $_GET['finalizado'] === '1';

// Buscar turmas
$stmt_turmas = $pdo->prepare("SELECT t.id, t.nome FROM turmas t JOIN professores_turmas pt ON t.id = pt.turma_id WHERE pt.professor_id = :professor_id");
$stmt_turmas->bindParam(':professor_id', $professor_id);
$stmt_turmas->execute();
$result_turmas = $stmt_turmas->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas
$stmt_disciplinas = $pdo->prepare("SELECT d.* FROM disciplinas d JOIN professores_disciplinas pd ON d.id = pd.disciplina_id WHERE pd.professor_id = :professor_id");
$stmt_disciplinas->bindParam(':professor_id', $professor_id);
$stmt_disciplinas->execute();
$result_disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

// Buscar alunos
if ($turma_id) {
    $stmt_alunos = $pdo->prepare("SELECT * FROM alunos WHERE turma_id = :turma_id");
    $stmt_alunos->bindParam(':turma_id', $turma_id);
    $stmt_alunos->execute();
    $result_alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
}

// Processar envio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluno_id = $_POST['aluno_id'];
    $turma_id = $_POST['turma_id'];
    $disciplina_id = $_POST['disciplina_id'];
    $nota_1 = $_POST['nota_1'];
    $nota_2 = $_POST['nota_2'];
    $unidade = (int)$_POST['unidade'];
    $media_column = "media_$unidade";
    $media = ($nota_1 + $nota_2) / 2;

    $stmt_check = $pdo->prepare("SELECT * FROM notas WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id AND unidade = :unidade");
    $stmt_check->execute([
        ':aluno_id' => $aluno_id,
        ':turma_id' => $turma_id,
        ':disciplina_id' => $disciplina_id,
        ':unidade' => $unidade
    ]);

    if ($stmt_check->rowCount() === 0) {
        $stmt_insert = $pdo->prepare("INSERT INTO notas (aluno_id, turma_id, disciplina_id, unidade, $media_column) VALUES (:aluno_id, :turma_id, :disciplina_id, :unidade, :media)");
        $stmt_insert->execute([
            ':aluno_id' => $aluno_id,
            ':turma_id' => $turma_id,
            ':disciplina_id' => $disciplina_id,
            ':unidade' => $unidade,
            ':media' => $media
        ]);
    } else {
        $stmt_update = $pdo->prepare("UPDATE notas SET $media_column = :media WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id");
        $stmt_update->execute([
            ':media' => $media,
            ':aluno_id' => $aluno_id,
            ':turma_id' => $turma_id,
            ':disciplina_id' => $disciplina_id
        ]);
    }

    // Próximo aluno
    $stmt_alunos = $pdo->prepare("SELECT * FROM alunos WHERE turma_id = :turma_id");
    $stmt_alunos->bindParam(':turma_id', $turma_id);
    $stmt_alunos->execute();
    $result_alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
    $current_index = array_search($aluno_id, array_column($result_alunos, 'id'));
    $next_index = $current_index + 1;

    if (isset($result_alunos[$next_index])) {
        $next_aluno_id = $result_alunos[$next_index]['id'];
        header("Location: inserir_notas.php?turma_id=$turma_id&disciplina_id=$disciplina_id&unidade=$unidade&aluno_id=$next_aluno_id");
    } else {
        header("Location: inserir_notas.php?turma_id=$turma_id&disciplina_id=$disciplina_id&unidade=$unidade&finalizado=1");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inserir Notas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Inserir Notas</h2>

    <?php if ($exibir_tabela_resumo && $turma_id && $disciplina_id && $unidade): ?>
        <div class="alert alert-info">Notas inseridas para todos os alunos.</div>
        <h4 class="mt-4">Resumo das Notas Inseridas</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Média Unidade <?= $unidade ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result_alunos as $aluno):
                    $stmt_nota = $pdo->prepare("SELECT * FROM notas WHERE aluno_id = :aluno_id AND turma_id = :turma_id AND disciplina_id = :disciplina_id AND unidade = :unidade");
                    $stmt_nota->execute([
                        ':aluno_id' => $aluno['id'],
                        ':turma_id' => $turma_id,
                        ':disciplina_id' => $disciplina_id,
                        ':unidade' => $unidade
                    ]);
                    $nota = $stmt_nota->fetch(PDO::FETCH_ASSOC);
                    $media_col = "media_$unidade";
                    $media = $nota[$media_col] ?? '-';
                    echo "<tr><td>{$aluno['nome']}</td><td>{$media}</td></tr>";
                endforeach; ?>
            </tbody>
        </table>
        <div class="text-center">
            <a href="inserir_notas.php" class="btn btn-secondary mt-4">Inserir novas notas</a>
        </div>

    <?php elseif ($turma_id && $disciplina_id && $unidade): ?>
        <?php
        $aluno_id = $aluno_id_get ?? $result_alunos[0]['id'];
        $aluno = current(array_filter($result_alunos, fn($a) => $a['id'] == $aluno_id));
        ?>
        <div class="card mb-3">
            <div class="card-header"><h5><?= $aluno['nome'] ?></h5></div>
            <div class="card-body">
                <form method="POST" action="inserir_notas.php">
                    <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>">
                    <input type="hidden" name="turma_id" value="<?= $turma_id ?>">
                    <input type="hidden" name="disciplina_id" value="<?= $disciplina_id ?>">
                    <input type="hidden" name="unidade" value="<?= $unidade ?>">
                    <div class="form-group">
                        <label>Nota 1</label>
                        <input type="number" name="nota_1" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Nota 2</label>
                        <input type="number" name="nota_2" class="form-control" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-success">Salvar Nota</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <form method="GET" action="inserir_notas.php">
            <div class="form-group">
                <label>Turma</label>
                <select name="turma_id" class="form-control" required>
                    <option value="">Selecione a turma</option>
                    <?php foreach ($result_turmas as $turma): ?>
                        <option value="<?= $turma['id'] ?>"><?= $turma['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Disciplina</label>
                <select name="disciplina_id" class="form-control" required>
                    <option value="">Selecione a disciplina</option>
                    <?php foreach ($result_disciplinas as $disciplina): ?>
                        <option value="<?= $disciplina['id'] ?>"><?= $disciplina['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Unidade</label>
                <select name="unidade" class="form-control" required>
                    <option value="">Selecione a unidade</option>
                    <option value="1">1ª Unidade</option>
                    <option value="2">2ª Unidade</option>
                    <option value="3">3ª Unidade</option>
                    <option value="4">4ª Unidade</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Continuar</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
