<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

session_start();
require 'partials/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit;
}

$professor_id = $_SESSION['usuario_id'];

// Filtra apenas os planos do professor com status "revisao"
$stmt = $pdo->prepare("SELECT * FROM planos_aula WHERE professor_id = :professor_id AND status = 'revisao'");
$stmt->execute(['professor_id' => $professor_id]);
$planos = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Planos de Aula Pendentes para Revisão</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>"
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2/select2.min.css"); ?>"
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css"); ?>"
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
    <style>
        /* Estilos para as mensagens de feedback */
        .feedback-message-container {
            margin-bottom: 25px;
            text-align: center;
        }
        .feedback-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px; /* Espaço entre múltiplas mensagens */
            font-weight: bold;
            opacity: 0; /* Começa invisível */
            transform: translateY(-20px); /* Começa um pouco acima */
            animation: fadeInSlideDown 0.5s ease-out forwards; /* Animação */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .feedback-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .feedback-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6fb;
        }
        .feedback-message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Animação para as mensagens de feedback */
        @keyframes fadeInSlideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Estilos específicos para a tabela de pareceres */
        .table th, .table td {
            vertical-align: middle;
            font-size: 0.9em;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.8em;
            text-transform: capitalize;
        }
        /* Definição das cores para os novos status */
        .status-pendente_professor { background-color: #ffc107; color: #343a40; } /* Amarelo */
        .status-finalizado_professor { background-color: #17a2b8; color: white; } /* Azul ciano */
        .status-pendente_coordenador { background-color: #6f42c1; color: white; } /* Roxo */
        .status-finalizado_coordenador { background-color: #28a745; color: white; } /* Verde */
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <?php include('partials/_navbar.php'); ?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include('partials/_sidebar.php'); ?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="page-header">
              <h3 class="page-title"> Planos de Aula Pendentes para Revisão </h3>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Pendentes para Revisão</li>
                </ol>
              </nav>
            </div>
            <div class="row">
              <div class="col-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Lista de Planos de Aula Pendentes para Revisão</h4>
                    <p class="card-description">Aqui você pode ver os planos de aula que precisam ser revisados conforme o parecer do coordenador.</p>
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>ID</th>
                            <th>Turma</th>
                            <th>Disciplina</th>
                            <th>Data</th>
                            <th>Mensagem de Revisão</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($planos as $plano): ?>
                          <tr>
                            <td><?= $plano['id'] ?></td>
                            <td><?= $plano['turma'] ?></td>
                            <td><?= $plano['disciplina'] ?></td>
                            <td><?= $plano['data'] ?></td>
                            <td><?= $plano['mensagem_revisao'] ?? 'Nenhuma mensagem disponível.' ?></td>
                            <td>
                              <a href="editar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-info btn-sm">Editar</a>
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
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <?php include('partials/_footer.php'); ?>
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
    <!-- Plugin js for this page -->
    <script src="<?php echo getAssetUrl("assets/vendors/select2/select2.min.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/vendors/typeahead.js/typeahead.bundle.min.js"); ?>"</script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"</script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="<?php echo getAssetUrl("assets/js/file-upload.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/typeahead.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/select2.js"); ?>"</script>
    <!-- End custom js for this page -->
  </body>
</html>