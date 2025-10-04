<?php
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'financeiro') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
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
            <div class="col-md-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Receitas x Despesas (mês)</h4>
                  <canvas id="chartReceitasDespesas" height="120"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Status de Pagamentos</h4>
                  <canvas id="chartStatusPagamentos" height="120"></canvas>
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
        var ctx1 = document.getElementById('chartReceitasDespesas').getContext('2d');
        new Chart(ctx1, {
          type: 'line',
          data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [
              { label: 'Receitas', data: [12, 19, 15, 17, 22, 25], borderColor: '#4caf50', backgroundColor: 'rgba(76,175,80,0.1)' },
              { label: 'Despesas', data: [8, 11, 9, 14, 12, 16], borderColor: '#f44336', backgroundColor: 'rgba(244,67,54,0.1)' }
            ]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });

        var ctx2 = document.getElementById('chartStatusPagamentos').getContext('2d');
        new Chart(ctx2, {
          type: 'doughnut',
          data: {
            labels: ['Em dia', 'Atrasados', 'Isentos'],
            datasets: [{ data: [60, 30, 10], backgroundColor: ['#2196f3', '#ff9800', '#9e9e9e'] }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });
      } catch(e) { console.error(e); }
    })();
  </script>
</body>
</html>


