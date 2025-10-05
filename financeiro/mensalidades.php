<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

$erro = '';
$sucesso = '';

// Gerar mensalidades em lote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar') {
  $competencia = trim($_POST['competencia'] ?? ''); // YYYY-MM
  $valor_padrao = (float)($_POST['valor_padrao'] ?? 0);
  $vencimento = $_POST['vencimento'] ?? '';
  $turma_id = $_POST['turma_id'] !== '' ? (int)$_POST['turma_id'] : null;

  if ($competencia === '' || $valor_padrao <= 0 || $vencimento === '') {
    $erro = 'Informe competência, valor padrão e vencimento.';
  } else {
    try {
      $pdo->beginTransaction();
      // Buscar alunos (por turma se informada)
      if ($turma_id) {
        $stmtAlunos = $pdo->prepare("SELECT id FROM alunos WHERE turma_id = :t");
        $stmtAlunos->execute([':t' => $turma_id]);
      } else {
        $stmtAlunos = $pdo->query("SELECT id FROM alunos");
      }
      $alunos = $stmtAlunos->fetchAll(PDO::FETCH_COLUMN);

      // Inserir se não existir mensalidade do mês
      $stmtCheck = $pdo->prepare("SELECT 1 FROM mensalidades WHERE aluno_id = :a AND competencia = :c");
      $stmtIns = $pdo->prepare(
        "INSERT INTO mensalidades (aluno_id, competencia, valor_original, desconto, acrescimos, valor_final, vencimento, status)
         VALUES (:a, :c, :vo, 0, 0, :vf, :venc, 'gerada')"
      );

      $qtInseridos = 0;
      foreach ($alunos as $aluno_id) {
        $stmtCheck->execute([':a' => $aluno_id, ':c' => $competencia]);
        if (!$stmtCheck->fetch()) {
          $stmtIns->execute([
            ':a' => $aluno_id,
            ':c' => $competencia,
            ':vo' => $valor_padrao,
            ':vf' => $valor_padrao,
            ':venc' => $vencimento
          ]);
          $qtInseridos++;
        }
      }
      $pdo->commit();
      $sucesso = "Mensalidades geradas: $qtInseridos";
    } catch (Throwable $e) {
      $pdo->rollBack();
      error_log('Erro gerar mensalidades: ' . $e->getMessage());
      $erro = 'Falha ao gerar mensalidades.';
    }
  }
}

// Filtros de listagem
$f_comp = trim($_GET['competencia'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_turma = $_GET['turma_id'] ?? '';

// Carregar turmas
$turmas = [];
try { $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

// Query lista
$mensalidades = [];
try {
  $sql = "SELECT m.id, m.aluno_id, a.nome AS aluno_nome, a.turma_id, m.competencia, m.valor_final, m.vencimento, m.status
          FROM mensalidades m
          JOIN alunos a ON a.id = m.aluno_id
          WHERE 1=1";
  $params = [];
  if ($f_comp !== '') { $sql .= " AND m.competencia = :c"; $params[':c'] = $f_comp; }
  if ($f_status !== '') { $sql .= " AND m.status = :s"; $params[':s'] = $f_status; }
  if ($f_turma !== '') { $sql .= " AND a.turma_id = :t"; $params[':t'] = (int)$f_turma; }
  $sql .= " ORDER BY m.vencimento DESC, a.nome";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $mensalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $mensalidades = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Mensalidades</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/ti-icons/css/themify-icons.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Mensalidades</h3>
          </div>

          <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>
          <?php if ($sucesso): ?><div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div><?php endif; ?>

          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">Gerar Mensalidades</h4>
              <form class="row g-3" method="post" action="mensalidades.php">
                <input type="hidden" name="acao" value="gerar">
                <div class="col-md-3">
                  <label class="form-label">Competência</label>
                  <input type="month" name="competencia" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Vencimento</label>
                  <input type="date" name="vencimento" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Valor Padrão (R$)</label>
                  <input type="number" name="valor_padrao" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Turma (opcional)</label>
                  <select name="turma_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($turmas as $t): ?>
                      <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <button class="btn btn-gradient-primary" type="submit">Gerar</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Listagem</h4>
              <form class="row g-3 mb-3" method="get" action="mensalidades.php">
                <div class="col-md-3">
                  <label class="form-label">Competência</label>
                  <input type="month" name="competencia" class="form-control" value="<?php echo htmlspecialchars($f_comp); ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach (['gerada','enviada','paga','pendente','atrasada','cancelada'] as $st): ?>
                      <option value="<?php echo $st; ?>" <?php echo $f_status===$st?'selected':''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Turma</label>
                  <select name="turma_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($turmas as $t): ?>
                      <option value="<?php echo (int)$t['id']; ?>" <?php echo $f_turma!=='' && (int)$f_turma===(int)$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['nome']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                </div>
              </form>

              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Aluno</th>
                      <th>Competência</th>
                      <th>Vencimento</th>
                      <th>Status</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($mensalidades as $m): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($m['aluno_nome']); ?></td>
                        <td><?php echo htmlspecialchars($m['competencia']); ?></td>
                        <td><?php echo htmlspecialchars($m['vencimento']); ?></td>
                        <td><?php echo htmlspecialchars($m['status']); ?></td>
                        <td>R$ <?php echo number_format((float)$m['valor_final'], 2, ',', '.'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$mensalidades): ?>
                      <tr><td colspan="5">Nenhum registro.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/jquery.cookie.js'); ?>"></script>
</body>
</html>


