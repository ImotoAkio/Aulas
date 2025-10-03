<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

// Ativar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('partials/db.php');
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$turma_id = $_GET['turma_id'] ?? null;
$disciplina_id = $_GET['disciplina_id'] ?? null;
$unidade_get = $_GET['unidade'] ?? null;
$aluno_id_get = $_GET['aluno_id'] ?? null;
$unidade = $unidade_get !== null ? (int) $unidade_get : null;
$exibir_tabela_resumo = isset($_GET['finalizado']) && $_GET['finalizado'] === '1';

// Buscar turmas
$stmt_turmas = $pdo->prepare("SELECT DISTINCT  t.id, t.nome FROM turmas t JOIN professores_turmas pt ON t.id = pt.turma_id WHERE pt.professor_id = :professor_id");
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
    $unidade = (int) $_POST['unidade'];

    // Buscar nome da turma para decidir a lógica das notas
    $stmt_nome_turma = $pdo->prepare("SELECT nome FROM turmas WHERE id = :turma_id");
    $stmt_nome_turma->execute([':turma_id' => $turma_id]);
    $nome_turma = $stmt_nome_turma->fetchColumn();

    $media_column = "media_$unidade";
    $disciplinas_extracurriculares_ids = [11,12,13,14]; // Exemplo: [5, 6, 7]

    $id_robotica = 13; 

    // CASO 1: Se a disciplina for Robótica, pega o valor de um campo único.
    if ($disciplina_id == $id_robotica) {
        // O valor virá de um campo chamado 'media_final' no seu formulário.
        $media = (float)$_POST['media_final'];
    } 
    // CASO 2: Se for 1º Ano ou outra Extracurricular, calcula a média de 2 notas.
    elseif (
        (stripos($nome_turma, '1º ano') !== false || stripos($nome_turma, '1° ano') !== false || stripos($nome_turma, '1 ano') !== false) ||
        in_array($disciplina_id, $disciplinas_extracurriculares_ids)
    ) {
        $nota_1 = (float)$_POST['nota_1'];
        $nota_2 = (float)$_POST['nota_2'];
        $media = ($nota_1 + $nota_2) / 2;
    } 
    // CASO 3: Para todas as outras disciplinas, calcula a média de 3 notas.
    else {
        $nota_1 = (float)$_POST['nota_1'];
        $nota_2 = (float)$_POST['nota_2'];
        $nota_3 = (float)$_POST['nota_3'];
        $media = ($nota_1 + $nota_2 + $nota_3) / 3;
    }

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
        header("Location: notas.php?turma_id=$turma_id&disciplina_id=$disciplina_id&unidade=$unidade&aluno_id=$next_aluno_id");
    } else {
        header("Location: notas.php?turma_id=$turma_id&disciplina_id=$disciplina_id&unidade=$unidade&finalizado=1");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notas do Professor</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
    <!-- inject:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
    <!-- Layout styles -->
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
    <style>
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include 'partials/_navbar.php'; ?>
        <div class="container-fluid page-body-wrapper">
            <?php include 'partials/_sidebar.php'; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Gerenciar Notas</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Notas do Professor</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">

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
                                            <a href="notas.php" class="btn btn-secondary mt-4">Inserir novas
                                                notas</a>
                                        </div>

                                    <?php elseif ($turma_id && $disciplina_id && $unidade): ?>
                                        <?php
                                        $aluno_id = $aluno_id_get ?? $result_alunos[0]['id'];
                                        $aluno = current(array_filter($result_alunos, fn($a) => $a['id'] == $aluno_id));
                                        ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h5><?= $aluno['nome'] ?></h5>
                                            </div>
<div class="card-body">
    <?php
    // Definir as condições com base nos parâmetros da URL
    $is_robotica = ($disciplina_id == 13);
    $is_primeiro_ano = ($turma_id == 1);
    
    // Buscar as disciplinas extracurriculares para uma lógica mais completa (opcional, mas recomendado)
    $disciplinas_extracurriculares_ids = [11, 12, 13, 14]; // Exemplo de IDs
    $is_extracurricular = in_array($disciplina_id, $disciplinas_extracurriculares_ids);

    ?>
    <form method="POST" action="notas.php">
        <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>">
        <input type="hidden" name="turma_id" value="<?= $turma_id ?>">
        <input type="hidden" name="disciplina_id" value="<?= $disciplina_id ?>">
        <input type="hidden" name="unidade" value="<?= $unidade ?>">

        <?php if ($is_robotica): ?>
            <div class="form-group">
                <label>Nota Final (Robótica)</label>
                <input type="number" name="media_final" class="form-control" step="0.01" min="0" max="10" required>
            </div>

        <?php elseif ($is_primeiro_ano || $is_extracurricular): ?>
            <div class="form-group">
                <label>Nota 1</label>
                <input type="number" name="nota_1" class="form-control" step="0.01" min="0" max="10" required>
            </div>
            <div class="form-group">
                <label>Nota 2</label>
                <input type="number" name="nota_2" class="form-control" step="0.01" min="0" max="10" required>
            </div>

        <?php else: ?>
            <div class="form-group">
                <label>Nota 1</label>
                <input type="number" name="nota_1" class="form-control" step="0.01" min="0" max="10" required>
            </div>
            <div class="form-group">
                <label>Nota 2</label>
                <input type="number" name="nota_2" class="form-control" step="0.01" min="0" max="10" required>
            </div>
            <div class="form-group">
                <label>Nota 3</label>
                <input type="number" name="nota_3" class="form-control" step="0.01" min="0" max="10" required>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-success">Salvar e Próximo Aluno</button>
    </form>
</div>
                                        </div>

                                    <?php else: ?>
                                        <form method="GET" action="notas.php">
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
                                                        <option value="<?= $disciplina['id'] ?>"><?= $disciplina['nome'] ?>
                                                        </option>
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
                                <!-- content-wrapper ends -->
                                
                            </div>
                            <div class="card">
                                <div class="card-body"></div>
                            </div>
                            <!-- main-panel ends -->
                        </div>
                        <div class="col-lg-12 grid-margin stretch-card">
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <a href="ver_notas.php" class="btn btn-gradient-primary me-2 col-12">Ver notas</a>
                                </div>
                            </div>
                        </div>
                        <!-- page-body-wrapper ends -->
                    </div>
                    <?php include 'partials/_footer.php'; ?>
                    <!-- container-scroller -->
                    <!-- plugins:js -->
                    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
                    <!-- inject:js -->
                    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
                    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
                    <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"</script>
                    <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"</script>
                    <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"</script>
                    <!-- End custom js for this page -->
</body>

</html>