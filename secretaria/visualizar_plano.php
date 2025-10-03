<?php
session_start();
require 'partials/db.php';
if (!isset($_SESSION['usuario_id'])) {
  require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
  exit;
}

$id = $_GET['id'];
//Função aprovar plano de aula
if (isset($_GET['aprovar'])) {
  $id = $_GET['aprovar'];
  $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'aprovado' WHERE id = :id");
  $stmt->execute(['id' => $id]);
  echo "<div class='alert alert-success'>Plano de aula aprovado com sucesso!</div>";
  header('Location: planos.php');
  exit;
}

// Verifica se o coordenador enviou o formulário de revisão
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['marcar_revisao'])) {
  $id = $_POST['id'];
  $mensagem_revisao = $_POST['mensagem_revisao'];

  $stmt = $pdo->prepare("UPDATE planos_aula SET status = 'revisao', mensagem_revisao = :mensagem_revisao WHERE id = :id");
  $stmt->execute(['mensagem_revisao' => $mensagem_revisao, 'id' => $id]);

  echo "<div class='alert alert-warning'>Plano de aula marcado para revisão.</div>";
}

// Consulta para obter os detalhes do plano de aula e o nome do professor
$stmt = $pdo->prepare("
    SELECT pa.*, u.nome AS professor_nome 
    FROM planos_aula pa 
    JOIN usuarios u ON pa.professor_id = u.id 
    WHERE pa.id = :id
");
$stmt->execute(['id' => $id]);
$plano = $stmt->fetch();

if (!$plano) {
  die("Plano de aula não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Purple Admin</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <!-- End Plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../partials/_navbar.html -->
<?php include 'partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.html -->
<?php include 'partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title"> Visualizar Plano de Aula </h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="planos.php">Planos de Aula</a></li>
                <li class="breadcrumb-item active" aria-current="page">Visualizar</li>
              </ol>
            </nav>
          </div>
          <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Informações do Plano de Aula</h4>
                  <div class="template-demo">
                    <h4> Professor: <small class="text-muted"><?= htmlspecialchars($plano['professor_nome']) ?> </small>
                    </h4>
                    <h4> Turma:<small class="text-muted"> <?= htmlspecialchars($plano['turma']) ?></small>
                    </h4>
                    <h4> Disciplina:<small class="text-muted"> <?= htmlspecialchars($plano['disciplina']) ?> </small>
                    </h4>
                    <h4> Data:<small class="text-muted"> <?= htmlspecialchars($plano['data']) ?> </small>
                    </h4>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-9 d-flex align-items-stretch">
              <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Conteúdo</h4>
                      <p class="card-description"> O conteúdo que será trabalhado na aula </p>
                      <p> <?= nl2br(htmlspecialchars($plano['conteudo'])) ?> </p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Metodologia</h4>
                      <p class="card-description"> A metodologia que será utilizada na aula </p>
                      <p> <?= nl2br(htmlspecialchars($plano['metodologia'])) ?> </p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Recursos</h4>
                      <p class="card-description"> Os recursos que serão utilizados na aula </p>
                      <p> <?= nl2br(htmlspecialchars($plano['recursos'])) ?> </p>
                      galley not only five centuries, </p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Método Avaliativo</h4>
                      <p class="card-description"> O método avaliativo que será utilizado na aula </p>
                      <p> <?= nl2br(htmlspecialchars($plano['metodo_avaliativo'])) ?> </p>
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Ações</h4>
                  <p class="card-description"> Aqui você pode aprovar ou revisar o plano de aula do professor. Se
                    você aprovar, o status do plano será atualizado para "aprovado". Se você marcar para revisão, o
                    status
                    será atualizado para "revisão" e uma mensagem de revisão poderá ser adicionada.
                  </p>
                  <div class="template-demo">
                    <h3>Aprovar</h3>
                    <a href="?aprovar=<?= $plano['id'] ?>" class="btn btn-gradient-success btn-fw">Aprovar</a>
                    <h3>Enviar para Revisão</h3>
                    <button type="button" class="btn btn-gradient-warning btn-fw" data-bs-toggle="modal" data-bs-target="#revisaoModal<?= $plano['id'] ?>">Revisão</button>
                    <h3>Editar</h3>
                    <a href="editar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-gradient-info btn-fw">Editar</a>
                    <h3>Voltar para lista</h3>
                    <a href="planos.php" class="btn btn-gradient-danger btn-fw">Voltar</a>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <?php include 'partials/_footer.php'; ?>

        <div class="modal fade" id="revisaoModal<?= $plano['id'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel"
          aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="exampleModalLabel">Marcar Plano para Revisão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="POST">
                  <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                  <div class="mb-3">
                    <label for="mensagem_revisao" class="form-label">Mensagem de Revisão</label>
                    <textarea class="form-control" id="mensagem_revisao" name="mensagem_revisao" rows="3"
                      required></textarea>
                  </div>
                  <button style="width: 100%;" type="submit" name="marcar_revisao" class="btn btn-warning w-100">Enviar para Revisão</button>
                </form>
              </div>
            </div>
          </div>
        </div>
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
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <script src="../assets/js/settings.js"></script>
  <script src="../assets/js/todolist.js"></script>
  <script src="../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
</body>

</html>