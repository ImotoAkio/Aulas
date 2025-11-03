<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
  require_once __DIR__ . '/../../config/database.php';
  redirectTo('login.php');
}

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
  if ($_GET['acao'] === 'marcar_pago' && isset($_GET['id'])) {
    $recibo_id = (int)$_GET['id'];
    try {
      $stmt = $pdo->prepare("UPDATE recibos SET status='pago', data_pagamento=CURDATE(), pago_por=:usuario WHERE id=:id");
      $stmt->execute([':id' => $recibo_id, ':usuario' => $_SESSION['usuario_id']]);
      $sucesso = "Recibo marcado como pago!";
    } catch (Throwable $e) {
      $erro = "Erro ao marcar recibo como pago: " . $e->getMessage();
    }
  }
}

// Filtros de listagem
$f_tipo = trim($_GET['tipo'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_aluno = trim($_GET['aluno'] ?? '');
$f_turma = $_GET['turma_id'] ?? '';
$f_data_inicio = trim($_GET['data_inicio'] ?? '');
$f_data_fim = trim($_GET['data_fim'] ?? '');

// Carregar turmas
$turmas = [];
try { $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

// Query lista
$recibos = [];
try {
  $sql = "SELECT r.id, r.aluno_id, r.tipo, r.referencia, r.valor_original, r.desconto, r.acrescimos, r.valor_final,
                 r.vencimento, r.data_pagamento, r.status, r.numero_recibo, r.descricao, r.observacoes,
                 a.nome AS aluno_nome, t.nome AS turma_nome
          FROM recibos r
          JOIN alunos a ON a.id = r.aluno_id
          LEFT JOIN turmas t ON t.id = a.turma_id
          WHERE 1=1";
  $params = [];
  
  if ($f_tipo !== '') { 
    $sql .= " AND r.tipo = :tipo"; 
    $params[':tipo'] = $f_tipo; 
  }
  if ($f_status !== '') { 
    $sql .= " AND r.status = :status"; 
    $params[':status'] = $f_status; 
  }
  if ($f_aluno !== '') { 
    $sql .= " AND a.nome LIKE :aluno"; 
    $params[':aluno'] = "%$f_aluno%"; 
  }
  if ($f_turma !== '') { 
    $sql .= " AND a.turma_id = :t"; 
    $params[':t'] = (int)$f_turma; 
  }
  if ($f_data_inicio !== '') { 
    $sql .= " AND r.criado_em >= :data_inicio"; 
    $params[':data_inicio'] = $f_data_inicio; 
  }
  if ($f_data_fim !== '') { 
    $sql .= " AND r.criado_em <= :data_fim"; 
    $params[':data_fim'] = $f_data_fim . ' 23:59:59'; 
  }
  
  $sql .= " ORDER BY r.criado_em DESC, r.numero_recibo DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $recibos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $recibos = [];
}

// Estatísticas
$stats = [
  'total' => count($recibos),
  'pagos' => count(array_filter($recibos, fn($r) => $r['status'] === 'pago')),
  'gerados' => count(array_filter($recibos, fn($r) => $r['status'] === 'gerado')),
  'cancelados' => count(array_filter($recibos, fn($r) => $r['status'] === 'cancelado')),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Recibos</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/ti-icons/css/themify-icons.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
  <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>">
</head>
<body>
  <div class="container-scroller">
    <?php include __DIR__ . '/../partials/_navbar.php'; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include __DIR__ . '/../partials/_sidebar.php'; ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Gestão de Recibos</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('financeiro/index.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Recibos</li>
              </ol>
            </nav>
          </div>

          <?php if (isset($sucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Sucesso!</strong> <?php echo $sucesso; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Erro!</strong> <?php echo $erro; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- FILTROS -->
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">
                <i class="mdi mdi-filter text-primary me-2"></i>
                Filtros
              </h4>
              <form class="row g-3" method="get" action="index.php">
                <div class="col-md-2">
                  <label class="form-label">Tipo</label>
                  <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="mensalidade" <?php echo $f_tipo==='mensalidade'?'selected':''; ?>>Mensalidade</option>
                    <option value="fardamento" <?php echo $f_tipo==='fardamento'?'selected':''; ?>>Fardamento</option>
                    <option value="atividade" <?php echo $f_tipo==='atividade'?'selected':''; ?>>Atividade</option>
                    <option value="matricula" <?php echo $f_tipo==='matricula'?'selected':''; ?>>Matrícula</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="gerado" <?php echo $f_status==='gerado'?'selected':''; ?>>Gerado</option>
                    <option value="emitido" <?php echo $f_status==='emitido'?'selected':''; ?>>Emitido</option>
                    <option value="pago" <?php echo $f_status==='pago'?'selected':''; ?>>Pago</option>
                    <option value="cancelado" <?php echo $f_status==='cancelado'?'selected':''; ?>>Cancelado</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Aluno</label>
                  <input type="text" name="aluno" class="form-control" value="<?php echo htmlspecialchars($f_aluno); ?>" placeholder="Buscar...">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Turma</label>
                  <select name="turma_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($turmas as $t): ?>
                      <option value="<?php echo (int)$t['id']; ?>" <?php echo $f_turma!=='' && (int)$f_turma===(int)$t['id']?'selected':''; ?>><?php echo htmlspecialchars($t['nome']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Data Início</label>
                  <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($f_data_inicio); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Data Fim</label>
                  <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($f_data_fim); ?>">
                </div>
                <div class="col-md-12 d-flex align-items-end">
                  <button class="btn btn-outline-primary me-2" type="submit">
                    <i class="mdi mdi-magnify me-1"></i>Filtrar
                  </button>
                  <a href="index.php" class="btn btn-outline-secondary me-2">
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
                  <i class="mdi mdi-receipt text-primary" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Total</h5>
                  <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                  <p class="card-text">Recibos</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-check-circle text-success" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Pagos</h5>
                  <h3 class="text-success"><?php echo $stats['pagos']; ?></h3>
                  <p class="card-text">Recibos</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-clock text-warning" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Gerados</h5>
                  <h3 class="text-warning"><?php echo $stats['gerados']; ?></h3>
                  <p class="card-text">Recibos</p>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card text-center">
                <div class="card-body">
                  <i class="mdi mdi-close-circle text-danger" style="font-size: 2rem;"></i>
                  <h5 class="card-title mt-2">Cancelados</h5>
                  <h3 class="text-danger"><?php echo $stats['cancelados']; ?></h3>
                  <p class="card-text">Recibos</p>
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
                  Lista de Recibos
                </h4>
                <a href="gerar.php" class="btn btn-gradient-primary">
                  <i class="mdi mdi-plus me-2"></i>Gerar Novo Recibo
                </a>
              </div>
              
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Nº</th>
                      <th>Aluno</th>
                      <th>Tipo</th>
                      <th>Valor</th>
                      <th>Status</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recibos as $r): ?>
                      <tr>
                        <td><strong>#<?php echo htmlspecialchars($r['numero_recibo'] ?? $r['id']); ?></strong></td>
                        <td>
                          <div><?php echo htmlspecialchars($r['aluno_nome']); ?></div>
                          <small class="text-muted"><?php echo htmlspecialchars($r['turma_nome'] ?? '-'); ?></small>
                        </td>
                        <td>
                          <span class="badge badge-info">
                            <?php 
                              echo match($r['tipo']) {
                                'mensalidade' => '💰 Mens.',
                                'fardamento' => '👔 Fard.',
                                'atividade' => '🎉 Ativ.',
                                'matricula' => '📝 Matr.',
                                default => $r['tipo']
                              };
                            ?>
                          </span>
                        </td>
                        <td><strong>R$ <?php echo number_format((float)$r['valor_final'], 2, ',', '.'); ?></strong></td>
                        <td>
                          <span class="badge badge-<?php 
                            echo match($r['status']) {
                              'pago' => 'success',
                              'gerado' => 'warning', 
                              'emitido' => 'info',
                              'cancelado' => 'secondary',
                              default => 'warning'
                            };
                          ?>">
                            <?php echo htmlspecialchars(ucfirst($r['status'])); ?>
                          </span>
                        </td>
                        <td>
                          <div class="btn-group" role="group">
                            <a href="gerar_pdf.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Imprimir PDF">
                              <i class="mdi mdi-printer"></i>
                            </a>
                            <?php if ($r['status'] !== 'pago' && $r['status'] !== 'cancelado'): ?>
                              <button class="btn btn-sm btn-outline-success" onclick="marcarComoPago(<?php echo $r['id']; ?>)" title="Marcar como pago">
                                <i class="mdi mdi-check"></i>
                              </button>
                            <?php endif; ?>
                            <?php if ($r['status'] !== 'cancelado'): ?>
                              <button class="btn btn-sm btn-outline-danger" onclick="cancelarRecibo(<?php echo $r['id']; ?>)" title="Cancelar">
                                <i class="mdi mdi-close"></i>
                              </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (!$recibos): ?>
                      <tr><td colspan="6" class="text-center text-muted">Nenhum recibo encontrado. <a href="gerar.php">Criar primeiro recibo</a></td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
        <?php include __DIR__ . '/../partials/_footer.php'; ?>
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
    function marcarComoPago(reciboId) {
      if (confirm('Deseja marcar este recibo como pago?')) {
        window.location.href = 'index.php?acao=marcar_pago&id=' + reciboId;
      }
    }
    
    function cancelarRecibo(reciboId) {
      const motivo = prompt('Informe o motivo do cancelamento:');
      if (motivo !== null && motivo.trim() !== '') {
        window.location.href = 'cancelar.php?id=' + reciboId + '&motivo=' + encodeURIComponent(motivo);
      }
    }
  </script>
</body>
</html>
