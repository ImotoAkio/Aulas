<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$aluno = null;
$historico = [];
$mensalidades = [];

try {
  $stmt = $pdo->prepare("SELECT id, nome, turma_id FROM alunos WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt2 = $pdo->prepare("SELECT referencia_mes, valor, status FROM pagamentos WHERE aluno_id = :id ORDER BY referencia_mes DESC");
  $stmt2->execute([':id' => $id]);
  $historico = $stmt2->fetchAll(PDO::FETCH_ASSOC);

  // Mensalidades do aluno
  $stmt3 = $pdo->prepare("SELECT competencia, valor_final, vencimento, status FROM mensalidades WHERE aluno_id = :id ORDER BY vencimento DESC");
  $stmt3->execute([':id' => $id]);
  $mensalidades = $stmt3->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $aluno = $aluno ?: ['id'=>$id,'nome'=>'Aluno Exemplo','turma_id'=>1];
  $historico = [['referencia_mes'=>date('Y-m'),'valor'=>250.00,'status'=>'pago']];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Detalhe do Aluno</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Detalhes do Aluno</h3>
          </div>

          <?php if (!$aluno): ?>
            <div class="alert alert-danger">Aluno não encontrado.</div>
          <?php else: ?>
            <div class="card mb-3">
              <div class="card-body">
                <h4 class="card-title"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                <p>Turma: <?php echo (int)$aluno['turma_id']; ?></p>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h4 class="card-title">Histórico de Pagamentos</h4>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Mês</th>
                        <th>Valor</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($historico as $h): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($h['referencia_mes']); ?></td>
                          <td>R$ <?php echo number_format((float)$h['valor'], 2, ',', '.'); ?></td>
                          <td><?php echo htmlspecialchars($h['status']); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          <div class="card mt-3">
            <div class="card-body">
              <h4 class="card-title">Mensalidades</h4>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Competência</th>
                      <th>Vencimento</th>
                      <th>Status</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($mensalidades as $m): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($m['competencia']); ?></td>
                        <td><?php echo htmlspecialchars($m['vencimento']); ?></td>
                        <td><?php echo htmlspecialchars($m['status']); ?></td>
                        <td>R$ <?php echo number_format((float)$m['valor_final'], 2, ',', '.'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$mensalidades): ?>
                      <tr><td colspan="4">Nenhum registro.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
</body>
</html>


