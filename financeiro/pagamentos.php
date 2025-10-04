<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
}

// Filtros simples
$mes = $_GET['mes'] ?? date('Y-m'); // formato YYYY-MM
$status = $_GET['status'] ?? '';

$pagamentos = [];
$erro_consulta = null;

try {
  // Exemplo de consulta (ajuste conforme sua tabela real)
  // Tabela sugerida: pagamentos (id, aluno_id, valor, status, referencia_mes)
  $sql = "SELECT p.id, p.aluno_id, a.nome AS aluno_nome, p.valor, p.status, p.referencia_mes
          FROM pagamentos p
          JOIN alunos a ON a.id = p.aluno_id
          WHERE p.referencia_mes = :mes";
  $params = [':mes' => $mes];
  if ($status !== '') {
    $sql .= " AND p.status = :status";
    $params[':status'] = $status;
  }
  $sql .= " ORDER BY a.nome";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $erro_consulta = 'Não foi possível consultar os pagamentos (ajuste o schema). Exibindo exemplo.';
  // Fallback de exemplo
  $pagamentos = [
    ['id' => 1, 'aluno_id' => 10, 'aluno_nome' => 'Aluno Exemplo', 'valor' => 250.00, 'status' => 'pago', 'referencia_mes' => $mes],
    ['id' => 2, 'aluno_id' => 11, 'aluno_nome' => 'Aluno 2', 'valor' => 250.00, 'status' => 'pendente', 'referencia_mes' => $mes],
  ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Pagamentos</title>
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
            <h3 class="page-title">Controle de Pagamentos</h3>
          </div>

          <?php if ($erro_consulta): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($erro_consulta); ?></div>
          <?php endif; ?>

          <div class="card mb-3">
            <div class="card-body">
              <form class="row g-3" method="get" action="pagamentos.php">
                <div class="col-md-3">
                  <label class="form-label">Mês (YYYY-MM)</label>
                  <input type="month" name="mes" class="form-control" value="<?php echo htmlspecialchars($mes); ?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="pago" <?php echo $status==='pago'?'selected':''; ?>>Pago</option>
                    <option value="pendente" <?php echo $status==='pendente'?'selected':''; ?>>Pendente</option>
                    <option value="isento" <?php echo $status==='isento'?'selected':''; ?>>Isento</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-gradient-primary" type="submit">Filtrar</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Aluno</th>
                      <th>Mês</th>
                      <th>Valor</th>
                      <th>Status</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pagamentos as $p): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($p['aluno_nome']); ?></td>
                        <td><?php echo htmlspecialchars($p['referencia_mes']); ?></td>
                        <td>R$ <?php echo number_format((float)$p['valor'], 2, ',', '.'); ?></td>
                        <td><span class="badge bg-<?php echo $p['status']==='pago'?'success':($p['status']==='pendente'?'warning':'secondary'); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                        <td>
                          <a class="btn btn-sm btn-outline-primary" href="aluno_detalhe.php?id=<?php echo (int)$p['aluno_id']; ?>">Ver aluno</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
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

  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
</body>
</html>


