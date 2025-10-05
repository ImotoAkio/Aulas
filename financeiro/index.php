<?php
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
}

// --- Dados do dashboard (carregados do banco) ---
$chartLabels = [];
$chartReceitas = [];
$chartDespesas = [];
$statusLabels = [];
$statusData = [];
$qtdAlunos = 0;
$resumoMes = ['receitas' => 0, 'pendentes' => 0];

try {
  // Quantidade de alunos
  $stmtAlunos = $pdo->query("SELECT COUNT(*) FROM alunos");
  $qtdAlunos = (int) $stmtAlunos->fetchColumn();
} catch (Throwable $e) {
  $qtdAlunos = 0;
}

// Receitas x Despesas (últimos 6 meses)
try {
  // Tenta considerar uma coluna 'tipo' em pagamentos: 'receita'/'despesa'
  $stmtRD = $pdo->query(
    "SELECT DATE_FORMAT(referencia_mes,'%Y-%m') AS mes,
            SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) AS receitas,
            SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) AS despesas
     FROM pagamentos
     GROUP BY mes
     ORDER BY mes DESC
     LIMIT 6"
  );
  $rowsRD = array_reverse($stmtRD->fetchAll(PDO::FETCH_ASSOC));
  if (!$rowsRD) { throw new Exception('Sem dados RD'); }
  foreach ($rowsRD as $r) {
    $chartLabels[] = $r['mes'];
    $chartReceitas[] = (float)$r['receitas'];
    $chartDespesas[] = (float)$r['despesas'];
  }
} catch (Throwable $e) {
  // Fallback: sem coluna 'tipo', usa status='pago' como receita e 0 para despesas
  try {
    $stmtR = $pdo->query(
      "SELECT DATE_FORMAT(referencia_mes,'%Y-%m') AS mes,
              SUM(CASE WHEN status='pago' THEN valor ELSE 0 END) AS receitas
       FROM pagamentos
       GROUP BY mes
       ORDER BY mes DESC
       LIMIT 6"
    );
    $rowsR = array_reverse($stmtR->fetchAll(PDO::FETCH_ASSOC));
    foreach ($rowsR as $r) {
      $chartLabels[] = $r['mes'];
      $chartReceitas[] = (float)$r['receitas'];
      $chartDespesas[] = 0.0;
    }
  } catch (Throwable $e2) {
    // Último fallback estático (evitar quebra)
    $chartLabels = ['2025-01','2025-02','2025-03','2025-04','2025-05','2025-06'];
    $chartReceitas = [12, 19, 15, 17, 22, 25];
    $chartDespesas = [8, 11, 9, 14, 12, 16];
  }
}

// Status de pagamentos (doughnut)
try {
  $stmtS = $pdo->query(
    "SELECT status, COUNT(*) AS total
     FROM pagamentos
     GROUP BY status"
  );
  $rowsS = $stmtS->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rowsS as $r) {
    $statusLabels[] = $r['status'];
    $statusData[] = (int)$r['total'];
  }
} catch (Throwable $e) {
  $statusLabels = ['Em dia','Atrasados','Isentos'];
  $statusData = [60,30,10];
}

// Resumo do mês atual (receitas pagas e pendentes)
try {
  $mesAtual = date('Y-m');
  $stmtResumo = $pdo->prepare(
    "SELECT 
        SUM(CASE WHEN status='pago' THEN valor ELSE 0 END) AS receitas,
        SUM(CASE WHEN status='pendente' THEN valor ELSE 0 END) AS pendentes
     FROM pagamentos
     WHERE referencia_mes = :m"
  );
  $stmtResumo->execute([':m' => $mesAtual]);
  $resumoMes = $stmtResumo->fetch(PDO::FETCH_ASSOC) ?: $resumoMes;
  $resumoMes['receitas'] = (float)($resumoMes['receitas'] ?? 0);
  $resumoMes['pendentes'] = (float)($resumoMes['pendentes'] ?? 0);
} catch (Throwable $e) {
  // mantém fallback
}

// Receita esperada do mês (todas as mensalidades do mês atual)
$receitaEsperada = 0;
try {
  $mesAtual = date('Y-m');
  $stmtEsperada = $pdo->prepare(
    "SELECT SUM(valor_final) AS total_esperado
     FROM mensalidades
     WHERE competencia = :m"
  );
  $stmtEsperada->execute([':m' => $mesAtual]);
  $resultado = $stmtEsperada->fetch(PDO::FETCH_ASSOC);
  $receitaEsperada = (float)($resultado['total_esperado'] ?? 0);
} catch (Throwable $e) {
  $receitaEsperada = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Financeiro - Dashboard</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
  <!-- endinject -->
  <!-- inject:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
  <!-- endinject -->
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
            <h3 class="page-title">Dashboard Financeiro</h3>
          </div>

          <div class="row">
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Alunos</h4>
                  <h2 class="mb-0"><?php echo (int)$qtdAlunos; ?></h2>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Receitas (mês)</h4>
                  <h2 class="text-success mb-0">R$ <?php echo number_format((float)$resumoMes['receitas'], 2, ',', '.'); ?></h2>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Pendentes (mês)</h4>
                  <h2 class="text-warning mb-0">R$ <?php echo number_format((float)$resumoMes['pendentes'], 2, ',', '.'); ?></h2>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Receita Esperada (mês)</h4>
                  <h2 class="text-info mb-0">R$ <?php echo number_format($receitaEsperada, 2, ',', '.'); ?></h2>
                  <small class="text-muted">Total das mensalidades</small>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Receitas x Despesas (mês)</h4>
                  <div style="height:280px">
                    <canvas id="chartReceitasDespesas"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Status de Pagamentos</h4>
                  <div style="height:280px">
                    <canvas id="chartStatusPagamentos"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Ações Rápidas</h4>
                  <a href="pagamentos.php" class="btn btn-gradient-primary me-2">Ir para Pagamentos</a>
                  <a href="alunos.php" class="btn btn-outline-secondary">Ver Alunos</a>
                </div>
              </div>
            </div>
          </div>

        </div>
        <?php include __DIR__ . '/partials/_footer.php'; ?>
      </div>
    </div>
  </div>

  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="<?php echo getAssetUrl("assets/vendors/chart.js/chart.umd.js"); ?>"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"></script>
  <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"></script>
  <!-- endinject -->
  <script>
    (function(){
      try {
        var labels = <?php echo json_encode($chartLabels); ?>;
        var receitas = <?php echo json_encode($chartReceitas); ?>;
        var despesas = <?php echo json_encode($chartDespesas); ?>;
        var ctx1 = document.getElementById('chartReceitasDespesas').getContext('2d');
        new Chart(ctx1, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              { label: 'Receitas', data: receitas, borderColor: '#4caf50', backgroundColor: 'rgba(76,175,80,0.15)', tension: 0.3 },
              { label: 'Despesas', data: despesas, borderColor: '#f44336', backgroundColor: 'rgba(244,67,54,0.15)', tension: 0.3 }
            ]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });

        var statusLabels = <?php echo json_encode($statusLabels); ?>;
        var statusData = <?php echo json_encode($statusData); ?>;
        var ctx2 = document.getElementById('chartStatusPagamentos').getContext('2d');
        new Chart(ctx2, {
          type: 'doughnut',
          data: {
            labels: statusLabels,
            datasets: [{ data: statusData, backgroundColor: ['#2196f3', '#ff9800', '#9e9e9e', '#4caf50', '#e91e63'] }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });
      } catch(e) { console.error(e); }
    })();
  </script>
</body>
</html>


