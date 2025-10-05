<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../config/database.php';
  redirectTo('login.php');
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
  $sql = "SELECT m.id, m.aluno_id, a.nome AS aluno_nome, a.turma_id, t.nome as turma_nome, m.competencia, m.valor_original, m.desconto, m.acrescimos, m.valor_final, m.vencimento, m.status, m.observacoes
          FROM mensalidades m
          JOIN alunos a ON a.id = m.aluno_id
          LEFT JOIN turmas t ON t.id = a.turma_id
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
  <title>Financeiro - Listar Mensalidades</title>
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
            <h3 class="page-title">Listar Mensalidades</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listar Mensalidades</li>
              </ol>
            </nav>
          </div>

          <!-- FILTROS -->
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">
                <i class="mdi mdi-filter text-primary me-2"></i>
                Filtros
              </h4>
              <form class="row g-3" method="get" action="listar_mensalidades.php">
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
                  <button class="btn btn-outline-primary me-2" type="submit">
                    <i class="mdi mdi-magnify me-1"></i>Filtrar
                  </button>
                  <a href="listar_mensalidades.php" class="btn btn-outline-secondary">
                    <i class="mdi mdi-refresh me-1"></i>Limpar
                  </a>
                </div>
              </form>
            </div>
          </div>

          <!-- ESTATÍSTICAS -->
          <div class="row mb-3">
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-format-list-bulleted text-primary" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Total</h5>
                  <h3 class="text-primary"><?php echo count($mensalidades); ?></h3>
                  <p class="card-text">Mensalidades</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-check-circle text-success" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Pagas</h5>
                  <h3 class="text-success"><?php echo count(array_filter($mensalidades, fn($m) => $m['status'] === 'paga')); ?></h3>
                  <p class="card-text">Mensalidades</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-clock text-warning" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Pendentes</h5>
                  <h3 class="text-warning"><?php echo count(array_filter($mensalidades, fn($m) => $m['status'] === 'pendente')); ?></h3>
                  <p class="card-text">Mensalidades</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-alert-circle text-danger" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Atrasadas</h5>
                  <h3 class="text-danger"><?php echo count(array_filter($mensalidades, fn($m) => $m['status'] === 'atrasada')); ?></h3>
                  <p class="card-text">Mensalidades</p>
                </div>
              </div>
            </div>
          </div>

          <!-- TABELA -->
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title">
                  <i class="mdi mdi-table text-info me-2"></i>
                  Mensalidades
                </h4>
                <a href="<?php echo getPageUrl('financeiro/mensalidades.php'); ?>" class="btn btn-gradient-primary">
                  <i class="mdi mdi-plus me-2"></i>Gerar Nova Mensalidade
                </a>
              </div>
              
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Aluno</th>
                      <th>Turma</th>
                      <th>Competência</th>
                      <th>Vencimento</th>
                      <th>Valor Original</th>
                      <th>Desconto</th>
                      <th>Acréscimos</th>
                      <th>Valor Final</th>
                      <th>Status</th>
                      <th>Observações</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($mensalidades as $m): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($m['aluno_nome']); ?></td>
                        <td><?php echo htmlspecialchars($m['turma_nome'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($m['competencia']); ?></td>
                        <td><?php echo htmlspecialchars($m['vencimento']); ?></td>
                        <td>R$ <?php echo number_format((float)$m['valor_original'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format((float)$m['desconto'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format((float)$m['acrescimos'], 2, ',', '.'); ?></td>
                        <td><strong>R$ <?php echo number_format((float)$m['valor_final'], 2, ',', '.'); ?></strong></td>
                        <td>
                          <span class="badge badge-<?php 
                            echo match($m['status']) {
                              'paga' => 'success',
                              'pendente' => 'warning', 
                              'atrasada' => 'danger',
                              'cancelada' => 'secondary',
                              default => 'info'
                            };
                          ?>">
                            <?php echo htmlspecialchars($m['status']); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($m['observacoes'] ?? '-'); ?></td>
                        <td>
                          <div class="btn-group" role="group">
                            <a href="<?php echo getPageUrl('financeiro/aluno_detalhe.php?id=' . $m['aluno_id']); ?>" class="btn btn-sm btn-outline-info" title="Ver detalhes do aluno">
                              <i class="mdi mdi-eye"></i>
                            </a>
                            <?php if ($m['status'] === 'pendente' || $m['status'] === 'atrasada'): ?>
                              <button class="btn btn-sm btn-outline-success" onclick="marcarComoPago(<?php echo $m['id']; ?>)" title="Marcar como pago">
                                <i class="mdi mdi-check"></i>
                              </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$mensalidades): ?>
                      <tr><td colspan="11" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
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
  
  <script>
    function marcarComoPago(mensalidadeId) {
      if (confirm('Deseja marcar esta mensalidade como paga?')) {
        // Aqui você pode implementar uma requisição AJAX para atualizar o status
        // Por enquanto, vamos redirecionar para a página de pagamentos
        window.location.href = '<?php echo getPageUrl('financeiro/pagamentos.php'); ?>?mensalidade_id=' + mensalidadeId;
      }
    }
  </script>
</body>
</html>
