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

$marcarPagoId = isset($_GET['marcar_pago']) ? (int)$_GET['marcar_pago'] : 0;
if ($marcarPagoId > 0) {
  try {
    $stmtP = $pdo->prepare("UPDATE mensalidades SET status='paga', atualizado_em=NOW() WHERE id=:id");
    $stmtP->execute([':id' => $marcarPagoId]);
  } catch (Throwable $e) {
    // loga e segue
    error_log('Erro marcar pago: ' . $e->getMessage());
  }
  // Redireciona para limpar a query string de ação
  $qs = [];
  if ($mes) { $qs['mes'] = $mes; }
  if ($status !== '') { $qs['status'] = $status; }
  $redir = 'pagamentos.php' . (count($qs) ? ('?' . http_build_query($qs)) : '');
  header('Location: ' . $redir);
  exit;
}

$pagamentos = [];
$erro_consulta = null;

try {
  // Usar tabela mensalidades que tem dados reais
  $sql = "SELECT m.id, m.aluno_id, a.nome AS aluno_nome, m.valor_final AS valor, m.status, m.competencia AS referencia_mes,
                 m.vencimento, m.criado_em, m.atualizado_em
          FROM mensalidades m
          JOIN alunos a ON a.id = m.aluno_id
          WHERE m.competencia = :mes";
  $params = [':mes' => $mes];
  if ($status !== '') {
    $sql .= " AND m.status = :status";
    $params[':status'] = $status;
  }
  $sql .= " ORDER BY a.nome";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Se não há dados para o mês selecionado, mostrar todos os meses disponíveis
  if (empty($pagamentos)) {
    $sqlTodos = "SELECT m.id, m.aluno_id, a.nome AS aluno_nome, m.valor_final AS valor, m.status, m.competencia AS referencia_mes,
                        m.vencimento, m.criado_em, m.atualizado_em
                 FROM mensalidades m
                 JOIN alunos a ON a.id = m.aluno_id";
    $paramsTodos = [];
    if ($status !== '') {
      $sqlTodos .= " WHERE m.status = :status";
      $paramsTodos[':status'] = $status;
    }
    $sqlTodos .= " ORDER BY m.competencia DESC, a.nome LIMIT 20";
    
    $stmtTodos = $pdo->prepare($sqlTodos);
    $stmtTodos->execute($paramsTodos);
    $pagamentos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $erro_consulta = 'Erro ao consultar mensalidades: ' . $e->getMessage();
  $pagamentos = [];
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
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pagamentos</li>
              </ol>
            </nav>
          </div>

          <?php if ($erro_consulta): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($erro_consulta); ?></div>
          <?php endif; ?>

          <!-- Resumo dos Pagamentos -->
          <?php if (!empty($pagamentos)): ?>
            <?php
            $totalValor = array_sum(array_column($pagamentos, 'valor'));
            $totalPago = 0;
            $totalPendente = 0;
            $totalAtrasado = 0;
            
            foreach ($pagamentos as $p) {
              if ($p['status'] === 'paga') {
                $totalPago += $p['valor'];
              } elseif ($p['status'] === 'pendente' || $p['status'] === 'gerada') {
                $totalPendente += $p['valor'];
              } elseif ($p['status'] === 'atrasada') {
                $totalAtrasado += $p['valor'];
              }
            }
            ?>
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <h3>R$ <?php echo number_format($totalValor, 2, ',', '.'); ?></h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h5 class="card-title">Pago</h5>
                    <h3>R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h5 class="card-title">Pendente</h5>
                    <h3>R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body">
                    <h5 class="card-title">Atrasado</h5>
                    <h3>R$ <?php echo number_format($totalAtrasado, 2, ',', '.'); ?></h3>
                  </div>
                </div>
              </div>
            </div>
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
                    <option value="paga" <?php echo $status==='paga'?'selected':''; ?>>Paga</option>
                    <option value="pendente" <?php echo $status==='pendente'?'selected':''; ?>>Pendente</option>
                    <option value="gerada" <?php echo $status==='gerada'?'selected':''; ?>>Gerada</option>
                    <option value="atrasada" <?php echo $status==='atrasada'?'selected':''; ?>>Atrasada</option>
                    <option value="cancelada" <?php echo $status==='cancelada'?'selected':''; ?>>Cancelada</option>
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
                      <th>Competência</th>
                      <th>Valor</th>
                      <th>Vencimento</th>
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
                        <td>
                          <?php if ($p['vencimento']): ?>
                            <?php 
                            $vencimento = new DateTime($p['vencimento']);
                            $hoje = new DateTime();
                            $diasAtraso = $hoje->diff($vencimento)->days;
                            $classeVencimento = '';
                            
                            if ($vencimento < $hoje && $p['status'] !== 'paga') {
                              $classeVencimento = 'text-danger';
                            } elseif ($diasAtraso <= 3 && $p['status'] !== 'paga') {
                              $classeVencimento = 'text-warning';
                            }
                            ?>
                            <span class="<?php echo $classeVencimento; ?>">
                              <?php echo $vencimento->format('d/m/Y'); ?>
                            </span>
                          <?php else: ?>
                            <span class="text-muted">Não definido</span>
                          <?php endif; ?>
                        </td>
                <td>
                  <?php
                  $statusClass = '';
                  $statusText = '';
                  switch ($p['status']) {
                    case 'paga':
                      $statusClass = 'success';
                      $statusText = 'Paga';
                      break;
                    case 'pendente':
                      $statusClass = 'warning';
                      $statusText = 'Pendente';
                      break;
                    case 'gerada':
                      $statusClass = 'info';
                      $statusText = 'Gerada';
                      break;
                    case 'atrasada':
                      $statusClass = 'danger';
                      $statusText = 'Atrasada';
                      break;
                    case 'cancelada':
                      $statusClass = 'secondary';
                      $statusText = 'Cancelada';
                      break;
                    default:
                      $statusClass = 'secondary';
                      $statusText = ucfirst($p['status']);
                  }
                  ?>
                  <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </td>
                        <td>
                          <div class="btn-group" role="group">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo getPageUrl('financeiro/aluno_detalhe.php?id=' . $p['aluno_id']); ?>" title="Ver detalhes do aluno">
                              <i class="mdi mdi-eye"></i>
                            </a>
                            <?php if ($p['status'] !== 'paga'): ?>
                              <a class="btn btn-sm btn-outline-success" href="?mes=<?php echo urlencode($mes); ?>&status=<?php echo urlencode($status); ?>&marcar_pago=<?php echo (int)$p['id']; ?>" title="Marcar como paga">
                                <i class="mdi mdi-check"></i>
                              </a>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
              <?php if (empty($pagamentos)): ?>
                <div class="text-center py-4">
                  <i class="mdi mdi-information-outline" style="font-size: 48px; color: #ccc;"></i>
                  <h5 class="text-muted mt-3">Nenhum pagamento encontrado</h5>
                  <p class="text-muted">Tente ajustar os filtros ou verifique se há mensalidades cadastradas.</p>
                </div>
              <?php endif; ?>
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
  
  <script>
    $(document).ready(function() {
      // Confirmar antes de marcar como pago
      $('a[href*="marcar_pago"]').on('click', function(e) {
        if (!confirm('Tem certeza que deseja marcar esta mensalidade como paga?')) {
          e.preventDefault();
        }
      });
      
      // Auto-submit do formulário quando o mês muda
      $('input[name="mes"]').on('change', function() {
        $(this).closest('form').submit();
      });
      
      // Destacar linhas com vencimento próximo ou atrasado
      $('tr').each(function() {
        var vencimentoText = $(this).find('td:nth-child(4) span').text();
        if (vencimentoText && vencimentoText !== 'Não definido') {
          var vencimento = new Date(vencimentoText.split('/').reverse().join('-'));
          var hoje = new Date();
          var diffTime = vencimento - hoje;
          var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          
          if (diffDays < 0) {
            $(this).addClass('table-danger');
          } else if (diffDays <= 3) {
            $(this).addClass('table-warning');
          }
        }
      });
    });
  </script>
</body>
</html>


