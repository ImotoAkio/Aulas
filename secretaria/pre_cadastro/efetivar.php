<?php
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

$sucesso = '';
$erro = '';

// Processar a efetivação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'efetivar') {
    $alunos_selecionados = $_POST['alunos'] ?? [];

    if (!empty($alunos_selecionados)) {
        try {
            $pdo->beginTransaction();

            $count = 0;
            foreach ($alunos_selecionados as $aluno_id) {
                // Buscar a turma futura do aluno
                $stmt = $pdo->prepare("SELECT turma_futura_id FROM pre_cadastros_controle WHERE aluno_id = ? AND status = 'aprovado'");
                $stmt->execute([$aluno_id]);
                $controle = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($controle && $controle['turma_futura_id']) {
                    // Atualizar a turma do aluno na tabela principal
                    $stmtUpdate = $pdo->prepare("UPDATE alunos SET turma_id = ? WHERE id = ?");
                    $stmtUpdate->execute([$controle['turma_futura_id'], $aluno_id]);
                    $count++;
                }
            }

            $pdo->commit();
            $sucesso = "$count aluno(s) tiveram suas matrículas efetivadas com sucesso!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao efetivar matrículas: " . $e->getMessage();
            error_log("Erro na efetivação: " . $e->getMessage());
        }
    } else {
        $erro = "Nenhum aluno selecionado.";
    }
}

// Buscar alunos aptos para efetivação (Aprovados e com Turma Futura definida)
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, t_atual.nome as turma_atual, t_futura.nome as turma_futura, pc.turma_futura_id
        FROM alunos a
        INNER JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN turmas t_atual ON a.turma_id = t_atual.id
        LEFT JOIN turmas t_futura ON pc.turma_futura_id = t_futura.id
        WHERE pc.status = 'aprovado' 
        AND pc.turma_futura_id IS NOT NULL
        -- Opcional: Mostrar apenas quem ainda não está na turma futura
        AND (a.turma_id IS NULL OR a.turma_id != pc.turma_futura_id)
        ORDER BY t_futura.nome, a.nome
    ");
    $stmt->execute();
    $alunos_aptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alunos_aptos = [];
    $erro = "Erro ao buscar alunos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Efetivar Matrículas - Secretaria</title>
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
                            <span class="page-title-icon bg-gradient-success text-white me-2">
                                <i class="mdi mdi-account-check"></i>
                            </span>
                            Efetivar Matrículas
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Efetivar Matrículas</li>
                            </ul>
                        </nav>
                    </div>

                    <?php if ($sucesso): ?>
                        <div class="alert alert-success">
                            <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($erro); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Alunos Prontos para Promoção</h4>
                                    <p class="card-description">
                                        Selecione os alunos abaixo para transferi-los oficialmente para suas novas
                                        turmas.
                                        <br><small class="text-danger">Atenção: Esta ação altera a turma atual do aluno
                                            no sistema.</small>
                                    </p>

                                    <?php if (empty($alunos_aptos)): ?>
                                        <div class="alert alert-info">Nenhum aluno pendente de efetivação encontrado.</div>
                                    <?php else: ?>
                                        <form method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja efetivar as matrículas selecionadas? Os alunos serão movidos para as novas turmas.');">
                                            <input type="hidden" name="acao" value="efetivar">

                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 50px;">
                                                                <div class="form-check form-check-flat form-check-primary">
                                                                    <label class="form-check-label">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            id="checkAll">
                                                                    </label>
                                                                </div>
                                                            </th>
                                                            <th>Aluno</th>
                                                            <th>Turma Atual</th>
                                                            <th>Turma Futura (Destino)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($alunos_aptos as $aluno): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check form-check-flat form-check-primary">
                                                                        <label class="form-check-label">
                                                                            <input type="checkbox"
                                                                                class="form-check-input aluno-check"
                                                                                name="alunos[]"
                                                                                value="<?php echo $aluno['id']; ?>">
                                                                        </label>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                                                <td>
                                                                    <?php if ($aluno['turma_atual']): ?>
                                                                        <span
                                                                            class="badge badge-secondary"><?php echo htmlspecialchars($aluno['turma_atual']); ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-warning">Sem Turma</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="badge badge-success"><?php echo htmlspecialchars($aluno['turma_futura']); ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-gradient-success btn-lg btn-block">
                                                    <i class="mdi mdi-check-all"></i> Efetivar Matrículas Selecionadas
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
    <script>
        document.getElementById('checkAll').addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('.aluno-check');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
    </script>
</body>

</html>