<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
include('partials/db.php');

// Verify user is logged in and is a coordinator
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// Initialize variables
$ano_letivo = $_GET['ano_letivo'] ?? date('Y');
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$aluno_id = $_GET['aluno_id'] ?? '';

$alunos_filtro = [];
$turmas = [];
$disciplinas = [];
$dados = [];
$modo_exibicao = ''; // 'aluno' ou 'turma'

// Fetch filter data
try {
    // Anos letivos disponíveis (baseado nas turmas)
    $anos_stmt = $pdo->query("SELECT DISTINCT ano_letivo FROM turmas ORDER BY ano_letivo DESC");
    $anos_disponiveis = $anos_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($anos_disponiveis)) $anos_disponiveis = [date('Y')];

    // Turmas do ano selecionado
    $turmas_stmt = $pdo->prepare("SELECT id, nome FROM turmas WHERE ano_letivo = ? ORDER BY nome");
    $turmas_stmt->execute([$ano_letivo]);
    $turmas = $turmas_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Disciplinas
    $disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    // Alunos para o filtro (se turma selecionada, filtra por turma, senão busca todos do ano)
    $sql_alunos = "SELECT a.id, a.nome, t.nome as turma_nome 
                   FROM alunos a 
                   JOIN turmas t ON a.turma_id = t.id 
                   WHERE t.ano_letivo = ? ";
    $params_alunos = [$ano_letivo];
    
    if (!empty($turma_id)) {
        $sql_alunos .= " AND a.turma_id = ?";
        $params_alunos[] = $turma_id;
    }
    $sql_alunos .= " ORDER BY a.nome";
    
    $alunos_stmt = $pdo->prepare($sql_alunos);
    $alunos_stmt->execute($params_alunos);
    $alunos_filtro = $alunos_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar filtros: " . $e->getMessage());
    $erro = "Erro ao carregar filtros.";
}

// Processar a busca
if (!empty($aluno_id)) {
    // MODO ALUNO: Ver todas as notas de todas as matérias de um aluno
    $modo_exibicao = 'aluno';
    try {
        // Buscar dados do aluno e sua turma
        $stmt_aluno = $pdo->prepare("SELECT a.nome, t.nome as turma_nome FROM alunos a JOIN turmas t ON a.turma_id = t.id WHERE a.id = ?");
        $stmt_aluno->execute([$aluno_id]);
        $info_aluno = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

        // Buscar notas de todas as disciplinas (Agrupado por disciplina)
        $sql = "SELECT d.nome as disciplina, 
                       MAX(n.media_1) as media_1, 
                       MAX(n.media_2) as media_2, 
                       MAX(n.media_3) as media_3, 
                       MAX(n.media_4) as media_4 
                FROM disciplinas d 
                LEFT JOIN notas n ON n.disciplina_id = d.id AND n.aluno_id = ? 
                GROUP BY d.id, d.nome
                ORDER BY d.nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aluno_id]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = "Erro ao buscar notas do aluno: " . $e->getMessage();
    }

} elseif (!empty($turma_id) && !empty($disciplina_id)) {
    // MODO TURMA: Ver notas de todos os alunos da turma em uma matéria
    $modo_exibicao = 'turma';
    try {
        // Buscar notas de todos os alunos (Agrupado por aluno)
        $sql = "SELECT a.id, a.nome, 
                       MAX(n.media_1) as media_1, 
                       MAX(n.media_2) as media_2, 
                       MAX(n.media_3) as media_3, 
                       MAX(n.media_4) as media_4 
                FROM alunos a 
                LEFT JOIN notas n ON n.aluno_id = a.id AND n.disciplina_id = ? 
                WHERE a.turma_id = ? 
                GROUP BY a.id, a.nome
                ORDER BY a.nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$disciplina_id, $turma_id]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = "Erro ao buscar notas da turma: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Notas - Secretaria</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
    <style>
        .select2-container { width: 100% !important; }
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
                        <h3 class="page-title">
                            <span class="page-title-icon bg-gradient-primary text-white me-2">
                                <i class="mdi mdi-format-list-numbered"></i>
                            </span>
                            Gestão de Notas
                        </h3>
                    </div>

                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Filtros de Busca</h4>
                                    <form method="GET" class="row g-3 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label">Ano Letivo</label>
                                            <select name="ano_letivo" class="form-select" onchange="this.form.submit()">
                                                <?php foreach ($anos_disponiveis as $ano): ?>
                                                    <option value="<?= $ano ?>" <?= $ano == $ano_letivo ? 'selected' : '' ?>><?= $ano ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label">Turma</label>
                                            <select name="turma_id" class="form-select" onchange="this.form.submit()">
                                                <option value="">Todas as Turmas</option>
                                                <?php foreach ($turmas as $t): ?>
                                                    <option value="<?= $t['id'] ?>" <?= $turma_id == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Aluno (Opcional)</label>
                                            <select name="aluno_id" class="form-select">
                                                <option value="">Selecione um aluno...</option>
                                                <?php foreach ($alunos_filtro as $a): ?>
                                                    <option value="<?= $a['id'] ?>" <?= $aluno_id == $a['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($a['nome']) ?> <?= empty($turma_id) ? '('.$a['turma_nome'].')' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Selecione para ver todas as matérias deste aluno.</small>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Disciplina</label>
                                            <select name="disciplina_id" class="form-select">
                                                <option value="">Selecione...</option>
                                                <?php foreach ($disciplinas as $d): ?>
                                                    <option value="<?= $d['id'] ?>" <?= $disciplina_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Obrigatório se não selecionar aluno.</small>
                                        </div>

                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="mdi mdi-magnify"></i> Consultar
                                            </button>
                                            <a href="notas.php" class="btn btn-light">Limpar</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php if ($modo_exibicao == 'aluno' && !empty($dados)): ?>
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h4 class="card-title mb-0">Boletim: <?= htmlspecialchars($info_aluno['nome']) ?></h4>
                                            <span class="badge bg-info"><?= htmlspecialchars($info_aluno['turma_nome']) ?></span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Disciplina</th>
                                                        <th class="text-center">1ª Unidade</th>
                                                        <th class="text-center">2ª Unidade</th>
                                                        <th class="text-center">3ª Unidade</th>
                                                        <th class="text-center">4ª Unidade</th>
                                                        <th class="text-center">Média Final</th>
                                                        <th class="text-center">Situação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dados as $d): 
                                                        $notas = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null && $v > 0);
                                                        $media = count($notas) > 0 ? array_sum($notas) / count($notas) : 0;
                                                        $situacao = $media >= 6 ? '<span class="text-success">Aprovado</span>' : '<span class="text-warning">Em Recuperação</span>';
                                                        if (count($notas) == 0) $situacao = '-';
                                                    ?>
                                                        <tr>
                                                            <td class="fw-bold"><?= htmlspecialchars($d['disciplina']) ?></td>
                                                            <td class="text-center"><?= $d['media_1'] > 0 ? $d['media_1'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_2'] > 0 ? $d['media_2'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_3'] > 0 ? $d['media_3'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_4'] > 0 ? $d['media_4'] : '-' ?></td>
                                                            <td class="text-center fw-bold"><?= count($notas) > 0 ? number_format($media, 1) : '-' ?></td>
                                                            <td class="text-center"><?= $situacao ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-4 text-end">
                                            <a href="boletim/gerar_boletim.php?aluno_id=<?= $aluno_id ?>" target="_blank" class="btn btn-success">
                                                <i class="mdi mdi-printer"></i> Imprimir Boletim
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($modo_exibicao == 'turma' && !empty($dados)): ?>
                            <div class="col-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title mb-4">Notas da Turma</h4>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Aluno</th>
                                                        <th class="text-center">1ª Unidade</th>
                                                        <th class="text-center">2ª Unidade</th>
                                                        <th class="text-center">3ª Unidade</th>
                                                        <th class="text-center">4ª Unidade</th>
                                                        <th class="text-center">Média</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dados as $d): 
                                                        $notas = array_filter([$d['media_1'], $d['media_2'], $d['media_3'], $d['media_4']], fn($v) => $v !== null && $v > 0);
                                                        $media = count($notas) > 0 ? array_sum($notas) / count($notas) : 0;
                                                    ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($d['nome']) ?></td>
                                                            <td class="text-center"><?= $d['media_1'] > 0 ? $d['media_1'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_2'] > 0 ? $d['media_2'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_3'] > 0 ? $d['media_3'] : '-' ?></td>
                                                            <td class="text-center"><?= $d['media_4'] > 0 ? $d['media_4'] : '-' ?></td>
                                                            <td class="text-center fw-bold"><?= count($notas) > 0 ? number_format($media, 1) : '-' ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['aluno_id']) || isset($_GET['turma_id']))): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information"></i> Nenhum registro encontrado para os filtros selecionados.
                                    Certifique-se de selecionar um <strong>Aluno</strong> OU uma <strong>Turma e Disciplina</strong>.
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <?php include 'partials/_footer.php'; ?>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
</body>
</html>