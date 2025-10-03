<?php
include 'partials/db.php';
try {
  // Conexão PDO
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Define o modo de erro para lançar exceções

  // Query SQL para contar planos de aula pendentes
  $sql = "SELECT COUNT(*) AS total_pendente FROM planos_aula WHERE status = 'pendente'";

  // Prepara a query
  $stmt = $pdo->prepare($sql);

  // Executa a query
  $stmt->execute();

  // Obtém o resultado
  $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

  // Exibe a quantidade de planos de aula pendentes
  if ($resultado) {
    $total_pendente = $resultado['total_pendente'];
    echo "Quantidade de planos de aula pendentes: " . $total_pendente;
  } else {
    echo "Não foi possível obter a contagem de planos de aula.";
  }

} catch (PDOException $e) {
  // Em caso de erro na conexão ou na query
  echo "Erro: " . $e->getMessage();
}

?>

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Rosa de Sharom | Echo Edu</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css" />
  <link rel="stylesheet" href="../assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
              <!-- Layout styles -->
        <link rel="stylesheet" href="../assets/css/style.css">
        <!-- End layout styles -->
    <link rel="shortcut icon" href="../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">

    <!-- partial:partials/_navbar.html -->
    <?php include 'partials/_navbar.php'; ?> <!-- Barra de navegação-->

    <?php include 'partials/_sidebar.php'; ?> <!-- Barra lateral-->

    <!--pagina-->
    <div class="main-panel">
      <div class="content-wrapper">
        <div class="page-header">
          <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
              <i class="mdi mdi-home"></i>
            </span> Início
          </h3>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item active" aria-current="page">
                <span></span>Visão Geral <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
              </li>
            </ul>
          </nav>
        </div>

        <div class="row">
          <!--planos de aulas-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='planos.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-danger card-img-holder text-white">
              <div class="card-body">
                <img src="../assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Planos de Aula <i class="mdi mdi-chart-line mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5"> <?php echo $total_pendente; ?> Planos para revisão</h2>
                <!--exibe a quantidade de planos pendentes-->
                <h6 class="card-text">Revisar agora</h6>
              </div>
            </div>
          </div>
          <!--notas-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='notas.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-info card-img-holder text-white">
              <div class="card-body">
                <img src="../assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Notas<i class="mdi mdi-bookmark-outline mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5">Visualizar Boletim Escolar</h2>

              </div>
            </div>
          </div>
          <!--parecer-->
          <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='parecer.php'"
            style="cursor: pointer;">
            <div class="card bg-gradient-success card-img-holder text-white">
              <div class="card-body">
                <img src="../assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                <h4 class="font-weight-normal mb-3">Parecer Pedagógico <i
                    class="mdi mdi-diamond mdi-24px float-end"></i>
                </h4>
                <h2 class="mb-5">Visualizar Resultados</h2>

              </div>
            </div>
          </div>
        </div>


      </div>
      <!-- content-wrapper ends -->
      <!-- partial:partials/_footer.html -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2023 <a
              href="https://www.bootstrapdash.com/" target="_blank">BootstrapDash</a>. All rights reserved.</span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i
              class="mdi mdi-heart text-danger"></i></span>
        </div>
      </footer>
      <!-- partial -->
    </div>
    <!-- main-panel ends -->
  </div>
  <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <script src="../assets/vendors/chart.js/chart.umd.js"></script>
  <script src="../assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
      <script src="../assets/js/off-canvas.js"></script>
    <script src="../assets/js/misc.js"></script>
            <script src="../assets/js/settings.js"></script>
        <script src="../assets/js/todolist.js"></script>
        <script src="../assets/js/jquery.cookie.js"></script>
        <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../assets/js/dashboard.js"></script>
  <!-- End custom js for this page -->
</body>

</html>