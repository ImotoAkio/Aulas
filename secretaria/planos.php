<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
require 'partials/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
  require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
  exit;
}

// Verifica se o coordenador clicou no botão "Aprovar"
if (isset($_GET['aprovar'])) {
  $id = $_GET['aprovar'];
  $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'aprovado' WHERE id = :id");
  $stmt->execute(['id' => $id]);
  echo "<div class='alert alert-success'>Plano de aula aprovado com sucesso!</div>";
}

// Verifica se o coordenador enviou o formulário de revisão
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['marcar_revisao'])) {
  $id = $_POST['id'];
  $mensagem_revisao = $_POST['mensagem_revisao'];

  $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'revisao', mensagem_revisao = :mensagem_revisao WHERE id = :id");
  $stmt->execute(['mensagem_revisao' => $mensagem_revisao, 'id' => $id]);

  echo "<div class='alert alert-warning'>Plano de aula marcado para revisão.</div>";
}

// Filtra apenas os planos com status "pendente"
$stmt = $pdo->query("SELECT pa.id, u.nome AS professor, pa.turma, pa.disciplina, pa.data, pa.status FROM planos_aula pa JOIN usuarios u ON pa.professor_id = u.id WHERE pa.status = 'pendente'");
$planos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Planos de Aula</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
              <!-- Layout styles -->
        <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
        <!-- End layout styles -->
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
</head>

<body>
  <div class="container-scroller">


    <?php include 'partials/_navbar.php'; ?> <!-- Barra de navegação-->

    <?php include 'partials/_sidebar.php'; ?> <!-- Barra lateral-->
    <!-- partial -->
    <div class="main-panel">
      <div class="content-wrapper">
        <div class="page-header">
          <h3 class="page-title">Listar Planos de Aula </h3>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item active" aria-current="page">Planos de Aula</li>
            </ol>
          </nav>
        </div>
        <div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <h4 class="card-title">Planos de Aula Pendentes </h4>
                <p class="card-description"> Clique em <code>visualizar</code> para ver mais opções sobre o plano de aula pendente. Você pode aprovar ou marcar para revisão. </p>
                
                </p>
                <table class="table">
                  <thead>
                                    <th>ID</th>
                                    <th>Professor</th>
                                    <th>Turma</th>
                                    <th>Disciplina</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                  </thead>
                  <tbody>
                    <?php foreach ($planos as $plano): ?>
                    <tr>
                      <td><?php echo $plano['id']; ?></td>
                      <td><?php echo $plano['professor']; ?></td>
                      <td><?php echo $plano['turma']; ?></td>
                      <td><?php echo $plano['disciplina']; ?></td>
                      <td><?php echo $plano['data']; ?></td>
                      <td><label class="badge badge-danger"><?php echo $plano['status']; ?></label></td>
                      <td>
                        <a href="visualizar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-gradient-primary btn-fw">Visualizar</a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          

        </div>
      </div>
      <!-- content-wrapper ends -->
      <!-- partial:../../partials/_footer.html -->
        <?php include 'partials/_footer.php'; ?>
      <!-- partial -->
    </div>
    <!-- main-panel ends -->
  </div>
  <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
  <!-- endinject -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Plugin js for this page -->
  <!-- End plugin js for this page -->
  <!-- inject:js -->
      <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
            <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"</script>
        <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"</script>
        <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"</script>
        <!-- endinject -->
  <!-- Custom js for this page -->
  <!-- End custom js for this page -->
</body>
</html>