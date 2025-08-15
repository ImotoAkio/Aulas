<?php
session_start();
require 'partials/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
  header('Location: login.php');
  exit;
}

// Verifica quantos planos de aula estão marcados como "revisao" para o professor logado
$professor_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM planos_aula WHERE professor_id = :professor_id AND status = 'revisao'");
$stmt->execute(['professor_id' => $professor_id]);
$total_revisao = $stmt->fetch()['total'];

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $turma = $_POST['turma'];
  $disciplina = $_POST['disciplina'];
  $data = $_POST['data'];
  $conteudo = $_POST['conteudo'];
  $objetivos = $_POST['objetivos'];
  $metodologia = $_POST['metodologia'];
  $recursos = $_POST['recursos'];
  $metodo_avaliativo = $_POST['metodo_avaliativo'];

  // Insere o plano de aula no banco de dados
  $stmt = $pdo->prepare("INSERT INTO planos_aula (professor_id, turma, disciplina, data, conteudo, objetivos, metodologia, recursos, metodo_avaliativo) VALUES (:professor_id, :turma, :disciplina, :data, :conteudo, :objetivos, :metodologia, :recursos, :metodo_avaliativo)");
  $stmt->execute([
    'professor_id' => $professor_id,
    'turma' => $turma,
    'disciplina' => $disciplina,
    'data' => $data,
    'conteudo' => $conteudo,
    'objetivos' => $objetivos,
    'metodologia' => $metodologia,
    'recursos' => $recursos,
    'metodo_avaliativo' => $metodo_avaliativo
  ]);

  echo "<div class='alert alert-success'>Plano de aula inserido com sucesso!</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Planos de Aula</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
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
                <p class="card-description"> Clique em <code>visualizar</code> para ver mais opções sobre o plano de
                  aula pendente. Você pode aprovar ou marcar para revisão. </p>

                </p>
                                        <?php if ($total_revisao > 0): ?>
                            <div class="alert alert-warning">
                                <h5>Você tem <?= $total_revisao ?> plano(s) de aula para revisão!</h5>
                                <a href="planos_revisao.php" class="btn btn-warning">Ver Planos para Revisão</a>
                            </div>
                        <?php endif; ?>
                <form method="POST">
                            <div class="mb-3">
                                <label for="turma" class="form-label">Turma</label>
                                <select class="form-select" id="turma" name="turma" required>
                                    <option value="" disabled selected>Selecione uma turma</option>
                                    <option value="G2 & G4">G2 & G3</option>
                                    <option value="G4 & G5">G4 & G5</option>
                                    <?php
                                    // Loop para gerar as opções de turmas do 1° ao 9° ano
                                    for ($i = 1; $i <= 9; $i++) {
                                        echo "<option value='{$i}° ano'>{$i}° ano</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="disciplina" class="form-label">Disciplina</label>
                                <input type="text" class="form-control" id="disciplina" name="disciplina" required>
                            </div>
                            <div class="mb-3">
                                <label for="data" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data" name="data" required>
                            </div>
                            <div class="mb-3">
                                <label for="conteudo" class="form-label">Conteúdo</label>
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="objetivos" class="form-label">Objetivos</label>
                                <textarea class="form-control" id="objetivos" name="objetivos" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodologia" class="form-label">Metodologia</label>
                                <textarea class="form-control" id="metodologia" name="metodologia" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="recursos" class="form-label">Recursos</label>
                                <textarea class="form-control" id="recursos" name="recursos" rows="3"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="metodo_avaliativo" class="form-label">Método Avaliativo</label>
                                <textarea class="form-control" id="metodo_avaliativo" name="metodo_avaliativo" rows="3"
                                    required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Enviar</button>
                        </form>
              </div>
            </div>
          </div>


        </div>
      </div>
      <!-- content-wrapper ends -->
      <!-- partial:../partials/_footer.html -->
      <?php include 'partials/_footer.php'; ?>
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
  <!-- Plugin js for this page -->
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <script src="../assets/js/settings.js"></script>
  <script src="../assets/js/todolist.js"></script>
  <script src="../assets/js/jquery.cookie.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <!-- End custom js for this page -->
</body>

</html>