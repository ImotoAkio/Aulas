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

$id = $_GET['id'];
$professor_id = $_SESSION['usuario_id'];

// Verifica se o plano pertence ao professor logado
$stmt = $pdo->prepare("SELECT * FROM planos_aula WHERE id = :id AND professor_id = :professor_id");
$stmt->execute(['id' => $id, 'professor_id' => $professor_id]);
$plano = $stmt->fetch();

if (!$plano) {
    die("Plano de aula não encontrado ou você não tem permissão para editá-lo.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma = $_POST['turma'];
    $disciplina = $_POST['disciplina'];
    $data = $_POST['data'];
    $conteudo = $_POST['conteudo'];
    $objetivos = $_POST['objetivos'];
    $metodologia = $_POST['metodologia'];
    $recursos = $_POST['recursos'];
    $metodo_avaliativo = $_POST['metodo_avaliativo'];

    // Atualiza o plano de aula e define o status como "pendente"
    $stmt = $pdo->prepare("UPDATE planos_aula SET turma = :turma, disciplina = :disciplina, data = :data, conteudo = :conteudo, objetivos = :objetivos, metodologia = :metodologia, recursos = :recursos, metodo_avaliativo = :metodo_avaliativo, status = 'pendente' WHERE id = :id");
    $stmt->execute([
        'turma' => $turma,
        'disciplina' => $disciplina,
        'data' => $data,
        'conteudo' => $conteudo,
        'objetivos' => $objetivos,
        'metodologia' => $metodologia,
        'recursos' => $recursos,
        'metodo_avaliativo' => $metodo_avaliativo,
        'id' => $id
    ]);

    echo "<div class='alert alert-success'>Plano de aula atualizado com sucesso! Ele foi enviado novamente para avaliação.</div>";
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
                            <li class="breadcrumb-item active" aria-current="page"> Editar Planos de Aula</li>
                        </ol>
                    </nav>
                </div>
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Planos de Aula Pendentes </h4>
                                <p class="card-description"> Clique em <code>visualizar</code> para ver mais opções
                                    sobre o plano de
                                    aula pendente. Você pode aprovar ou marcar para revisão. </p>

                                </p>

                                <form class="forms-sample" method="POST">
                                    
                                    <div class="form-group row">
                                        <label for="turma" class="col-sm-3 col-form-label">Turma</label>
                                        <input type="text" class="form-control" id="turma" name="turma"
                                            value="<?= $plano['turma'] ?>" required>
                                    </div>
                                    <div class="form-group row">
                                        <label for="disciplina" class="col-sm-3 col-form-label">Disciplina</label>
                                        <input type="text" class="form-control" id="disciplina" name="disciplina"
                                            value="<?= $plano['disciplina'] ?>" required>
                                    </div>
                                    <div class="form-group row">
                                        <label for="data" class="col-sm-3 col-form-label">Data</label>
                                        <input type="date" class="form-control" id="data" name="data"
                                            value="<?= $plano['data'] ?>" required>
                                    </div>
                                    <div class="form-group row">
                                        <label for="conteudo" class="col-sm-3 col-form-label">Conteúdo</label>
                                        <textarea rows="5" rows="5" class="form-control" id="conteudo" name="conteudo" rows="3"
                                            required><?= $plano['conteudo'] ?></textarea>
                                    </div>
                                    <div class="form-group row">
                                        <label for="objetivos" class="col-sm-3 col-form-label">Objetivos</label>
                                        <textarea rows="5" class="form-control" id="objetivos" name="objetivos" rows="3"
                                            required><?= $plano['objetivos'] ?></textarea>
                                    </div>
                                    <div class="form-group row">
                                        <label for="metodologia" class="col-sm-3 col-form-label">Metodologia</label>
                                        <textarea rows="6" class="form-control" id="metodologia" name="metodologia" rows="3"
                                            required><?= $plano['metodologia'] ?></textarea>
                                    </div>
                                    <div class="form-group row">
                                        <label for="recursos" class="col-sm-3 col-form-label">Recursos</label>
                                        <textarea rows="5" class="form-control" id="recursos" name="recursos" rows="3"
                                            required><?= $plano['recursos'] ?></textarea>
                                    </div>
                                    <div class="form-group row">
                                        <label for="metodo_avaliativo" class="col-sm-3 col-form-label">Método Avaliativo</label>
                                        <textarea rows="5" class="form-control" id="metodo_avaliativo" name="metodo_avaliativo"
                                            rows="3" required><?= $plano['metodo_avaliativo'] ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
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
    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
    <!-- endinject -->
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