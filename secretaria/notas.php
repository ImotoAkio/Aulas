<?php
session_start();
include('partials/db.php');

// Verify user is logged in and is a coordinator
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: login.php');
    exit();
}

// Initialize variables to avoid notices
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$alunos = [];
$turmas = [];
$disciplinas = [];

// Fetch all turmas
try {
    $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error instead of displaying it to the user.
    error_log("Erro ao buscar turmas: " . $e->getMessage());
    // Provide a user-friendly message
    echo "<p class='error-message'>Erro ao carregar a página. Por favor, tente novamente mais tarde.</p>";
    exit(); // Stop execution to prevent further errors.
}

// Fetch all disciplinas
try {
    $disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas: " . $e->getMessage());
    echo "<p class='error-message'>Erro ao carregar a página. Por favor, tente novamente mais tarde.</p>";
    exit();
}

// Fetch student data and grades if turma_id and disciplina_id are provided
if (!empty($turma_id) && !empty($disciplina_id)) {
    try {
        $alunos_stmt = $pdo->prepare("SELECT a.id, a.nome FROM alunos a WHERE a.turma_id = ?");
        $alunos_stmt->execute([$turma_id]);
        $alunos = $alunos_stmt->fetchAll(PDO::FETCH_ASSOC);

        $dados = [];
        foreach ($alunos as $a) {
            $notas_stmt = $pdo->prepare("SELECT media_1, media_2, media_3, media_4 FROM notas WHERE aluno_id = ? AND turma_id = ? AND disciplina_id = ?");
            $notas_stmt->execute([$a['id'], $turma_id, $disciplina_id]);
            $notas = $notas_stmt->fetch(PDO::FETCH_ASSOC);
            if ($notas) {
                $dados[] = [
                    'id' => $a['id'],
                    'nome' => $a['nome'],
                    'media_1' => $notas['media_1'],
                    'media_2' => $notas['media_2'],
                    'media_3' => $notas['media_3'],
                    'media_4' => $notas['media_4'],
                ];
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar dados de notas: " . $e->getMessage());
        echo "<p class='error-message'>Erro ao carregar dados de notas. Por favor, tente novamente mais tarde.</p>";
        $dados = []; // Ensure $dados is empty to avoid errors in the table.
    }
}


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notas</title>
    <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="shortcut icon" href="../assets/images/favicon.png" />
    <style>
        /* Style for error messages */
        .error-message {
            color: red;
            font-weight: bold;
            margin-top: 10px;
            /* Add some spacing */
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
                        <h3 class="page-title">Visualizar Notas </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item active" aria-current="page">Visualizar Notas</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 grid-margin">
                            <form method="GET" class="row mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Selecione</h4>
                                        <p class="card-description"> Escolha a<code>turma</code>e
                                            a<code>disciplina</code>
                                        </p>
                                        <div class="template-demo">
                                            <div class="dropdown">
                                                <select name="turma_id" class="form-select" required>
                                                    <option value="">Selecione</option>
                                                    <?php foreach ($turmas as $t): ?>
                                                        <option value="<?= $t['id'] ?>" <?= ($turma_id == $t['id'] ? 'selected' : '') ?>><?= $t['nome'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="dropdown">
                                                <select name="disciplina_id" class="form-select" required>
                                                    <option value="">Selecione</option>
                                                    <?php foreach ($disciplinas as $d): ?>
                                                        <option value="<?= $d['id'] ?>" <?= ($disciplina_id == $d['id'] ? 'selected' : '') ?>><?= $d['nome'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit"
                                                class="btn btn-gradient-success btn-fw">Consultar</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Notas Por Aluno</h4>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Aluno</th>
                                                    <th>1ª Unidade</th>
                                                    <th>2ª Unidade</th>
                                                    <th>3ª Unidade</th>
                                                    <th>4ª Unidade</th>
                                                    <th>Média Final</th>
                                                    <th>Boletim</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($dados)): ?>
                                                    <tr>
                                                        <td colspan="7">Nenhum dado encontrado para a turma e disciplina
                                                            selecionadas.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($dados as $d):
                                                        $medias = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null);
                                                        $media_final = count($medias) ? array_sum($medias) / count($medias) : '-';
                                                        ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($d['nome']) ?></td>
                                                            <td><?= htmlspecialchars($d['media_1'] ?? '-') ?></td>
                                                            <td><?= htmlspecialchars($d['media_2'] ?? '-') ?></td>
                                                            <td><?= htmlspecialchars($d['media_3'] ?? '-') ?></td>
                                                            <td><?= htmlspecialchars($d['media_4'] ?? '-') ?></td>
                                                            <td><?= is_numeric($media_final) ? number_format($media_final, 2) : '-' ?>
                                                            </td>
                                                            <td>
                                                                <a href="boletim/gerar_boletim.php?aluno_id=<?= $d['id'] ?>"
                                                                    target="_blank"
                                                                    class="btn btn-sm btn-outline-secondary">Boletim PDF</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-4">
                                        <a href="boletim/gerar_boletins_turma.php?turma_id=<?= $turma_id ?>&disciplina_id=<?= $disciplina_id ?>"
                                            class="btn btn-success">Baixar Boletins de Todos</a>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                    <?php include 'partials/_footer.php'; ?>
                </div>
            </div>
        </div>
        <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/off-canvas.js"></script>
        <script src="../assets/js/misc.js"></script>
        <script src="../assets/js/settings.js"></script>
        <script src="../assets/js/todolist.js"></script>
        <script src="../assets/js/jquery.cookie.js"></script>
        <script>
                const ctx = document.getElementById('graficoMedias').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($dados, 'nome')) ?>,
                datasets: [{
                    label: 'Média Final',
                data: <?= json_encode(array_map(function ($d) {
                    $m = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null);
                    return count($m) ? round(array_sum($m) / count($m), 2) : 0;
                }, $dados)) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
                }]
            },
                options: {
                    responsive: true,
                scales: {
                    y: {
                    beginAtZero: true,
                max: 10
                    }
                }
            }
        });
        </script>
        </script>
</body>

</html>