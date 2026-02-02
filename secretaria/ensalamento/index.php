<?php
session_start();
require_once '../../config/database.php';

// Verificar permissão
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    header("Location: ../login.php");
    exit;
}

$pdo = getConnection();

// Processar alteração de ensalamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_ensalamento') {
    $aluno_id = (int) $_POST['aluno_id'];
    $nova_turma_id = !empty($_POST['turma_id']) ? (int) $_POST['turma_id'] : null;
    $nova_sala = !empty($_POST['sala']) ? trim($_POST['sala']) : null;

    if ($aluno_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET turma_id = ?, sala = ? WHERE id = ?");
            $stmt->execute([$nova_turma_id, $nova_sala, $aluno_id]);
            $sucesso = "Ensalamento atualizado com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar ensalamento: " . $e->getMessage();
        }
    }
}

// Buscar turmas para o select
try {
    $stmt = $pdo->prepare("SELECT id, nome, ano_letivo FROM turmas ORDER BY ano_letivo DESC, nome");
    $stmt->execute();
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $turmas = [];
}

// Buscar alunos e suas turmas
try {
    $busca = $_GET['busca'] ?? '';
    $filtro_turma = $_GET['filtro_turma'] ?? '';

    $sql = "
        SELECT a.id, a.nome, a.sala, t.nome as turma_nome, t.ano_letivo, t.id as turma_id
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE 1=1
    ";

    $params = [];
    if (!empty($busca)) {
        $sql .= " AND a.nome LIKE ?";
        $params[] = "%$busca%";
    }

    if (!empty($filtro_turma)) {
        $sql .= " AND a.turma_id = ?";
        $params[] = $filtro_turma;
    }

    $sql .= " ORDER BY a.nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alunos = [];
    $erro = "Erro ao buscar alunos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ensalamento - Secretaria</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
</head>

<body>
    <div class="container-scroller">
        <?php include '../partials/_navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include '../partials/_sidebar.php'; ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">
                            <span class="page-title-icon bg-gradient-primary text-white me-2">
                                <i class="mdi mdi-account-switch"></i>
                            </span>
                            Ensalamento de Alunos
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Ensalamento</li>
                            </ul>
                        </nav>
                    </div>

                    <?php if (isset($sucesso)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($erro); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="card-title mb-0">Lista de Alunos</h4>
                                        <form class="d-flex align-items-center" method="GET">
                                            <div class="me-2">
                                                <select class="form-select" name="filtro_turma"
                                                    onchange="this.form.submit()" style="min-width: 200px;">
                                                    <option value="">Todas as Turmas</option>
                                                    <?php foreach ($turmas as $turma): ?>
                                                        <option value="<?php echo $turma['id']; ?>" <?php echo (isset($filtro_turma) && $filtro_turma == $turma['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($turma['nome']); ?>
                                                            <?php if ($turma['ano_letivo']): ?>
                                                                (<?php echo htmlspecialchars($turma['ano_letivo']); ?>)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="busca"
                                                    placeholder="Buscar aluno..."
                                                    value="<?php echo htmlspecialchars($busca); ?>">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Aluno</th>
                                                    <th>Turma</th>
                                                    <th>Sala</th>
                                                    <th class="text-center" style="width: 150px;">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($alunos)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Nenhum aluno
                                                            encontrado.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($alunos as $aluno): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="mdi mdi-account text-primary me-2"></i>
                                                                <?php echo htmlspecialchars($aluno['nome']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($aluno['turma_nome']): ?>
                                                                    <span class="badge badge-info">
                                                                        <?php echo htmlspecialchars($aluno['turma_nome']); ?>
                                                                        <?php if ($aluno['ano_letivo']): ?>
                                                                            <small>(<?php echo htmlspecialchars($aluno['ano_letivo']); ?>)</small>
                                                                        <?php endif; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">Sem turma</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($aluno['sala'] ?? '-'); ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                                    onclick="abrirModalEnsalamento(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>', '<?php echo $aluno['turma_id']; ?>', '<?php echo htmlspecialchars(addslashes($aluno['sala'] ?? '')); ?>')">
                                                                    <i class="mdi mdi-pencil"></i> Editar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ensalamento -->
    <div class="modal fade" id="modalEnsalamento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Ensalamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="salvar_ensalamento">
                        <input type="hidden" name="aluno_id" id="aluno_id_modal">

                        <div class="mb-3">
                            <label class="form-label">Aluno</label>
                            <input type="text" class="form-control" id="nome_aluno_modal" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="sala_input" class="form-label">Sala</label>
                            <input type="text" class="form-control" name="sala" id="sala_input"
                                placeholder="Ex: Sala 101, Auditório...">
                        </div>

                        <div class="mb-3">
                            <label for="turma_select" class="form-label">Turma</label>
                            <select class="form-select form-control" name="turma_id" id="turma_select">
                                <option value="">Sem turma (Remover)</option>
                                <?php foreach ($turmas as $turma): ?>
                                    <option value="<?php echo $turma['id']; ?>">
                                        <?php echo htmlspecialchars($turma['nome']); ?>
                                        <?php if ($turma['ano_letivo']): ?>
                                            (<?php echo htmlspecialchars($turma['ano_letivo']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alteração</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>

    <script>
        function abrirModalEnsalamento(id, nome, turmaId, sala) {
            document.getElementById('aluno_id_modal').value = id;
            document.getElementById('nome_aluno_modal').value = nome;
            document.getElementById('turma_select').value = turmaId || "";
            document.getElementById('sala_input').value = sala || "";

            var modal = new bootstrap.Modal(document.getElementById('modalEnsalamento'));
            modal.show();
        }
    </script>
</body>

</html>