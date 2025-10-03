<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getAssetUrl')) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * professor/index.php
 *
 * Dashboard principal para o perfil de professor.
 * Exibe informações resumidas e links rápidos para as funcionalidades do professor.
 *
 * Utiliza a estrutura de template existente com partials.
 */

session_start();

// Ativar exibição de erros (para depuração, remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('partials/db.php'); // Inclui o arquivo de conexão com o banco de dados

// Verificar se a conexão foi estabelecida
if (!isset($pdo)) {
    die("Erro: Não foi possível conectar ao banco de dados.");
}

// Verifica se o usuário está logado e se é um professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$professor_nome = $_SESSION['usuario_nome'] ?? 'Professor';
$professor_tipo = $_SESSION['tipo'] ?? 'Desconhecido';

// --- Dados para os Cards ---
$total_planos_pendentes = 0;
$total_pareceres_pendentes = 0;

try {
    // Contar planos de aula pendentes para o professor logado
    $stmt_planos = $pdo->prepare("SELECT COUNT(*) AS total_pendente FROM planos_aula WHERE professor_id = :professor_id AND status = 'pendente'");
    $stmt_planos->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_planos->execute();
    $resultado_planos = $stmt_planos->fetch(PDO::FETCH_ASSOC);
    $total_planos_pendentes = $resultado_planos['total_pendente'] ?? 0;

    // Contar pareceres pedagógicos pendentes para o professor logado
    $stmt_pareceres = $pdo->prepare("SELECT COUNT(*) AS total_pendente FROM pareceres WHERE id_professor_designado = :professor_id AND status = 'pendente_professor'");
    $stmt_pareceres->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_pareceres->execute();
    $resultado_pareceres = $stmt_pareceres->fetch(PDO::FETCH_ASSOC);
    $total_pareceres_pendentes = $resultado_pareceres['total_pendente'] ?? 0;

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para o dashboard do professor: " . $e->getMessage());
    // Mensagem amigável, mas sem parar a execução
    echo "<div class='alert alert-danger'>Erro ao carregar dados do dashboard. Tente novamente.</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard do Professor</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/ti-icons/css/themify-icons.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/font-awesome/css/font-awesome.min.css"); ?>">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css"); ?>">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
            <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
        <!-- End layout styles -->
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
    <style>
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <?php include 'partials/_navbar.php'; ?> <!-- Barra de navegação-->

        <!-- partial:partials/_sidebar.html -->
        <?php include 'partials/_sidebar.php'; ?> <!-- Barra lateral-->

        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">
                        <span class="page-title-icon bg-gradient-primary text-white me-2">
                            <i class="mdi mdi-home"></i>
                        </span> Dashboard - Professor <?php echo htmlspecialchars($professor_nome); ?>
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
                    <!-- Card: Planos de Aula -->
                    <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='planos.php'" style="cursor: pointer;">
                        <div class="card bg-gradient-danger card-img-holder text-white">
                            <div class="card-body">
                                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Planos de Aula <i class="mdi mdi-chart-line mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5"><?php echo $total_planos_pendentes; ?> Planos para revisão</h2>
                                <h6 class="card-text">Revisar agora</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Card: Notas -->
                    <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='notas.php'" style="cursor: pointer;">
                        <div class="card bg-gradient-info card-img-holder text-white">
                            <div class="card-body">
                                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Notas<i
                                    class="mdi mdi-bookmark-outline mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5">Gerenciar Notas</h2> 
                                <h6 class="card-text">Inserir ou Visualizar</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Card: Parecer Pedagógico -->
                    <div class="col-md-4 stretch-card grid-margin" onclick="window.location.href='parecer.php'" style="cursor: pointer;">
                        <div class="card bg-gradient-success card-img-holder text-white">
                            <div class="card-body">
                                <img src="<?php echo getAssetUrl("assets/images/dashboard/circle.svg"); ?>" class="card-img-absolute" alt="circle-image" />
                                <h4 class="font-weight-normal mb-3">Parecer Pedagógico <i class="mdi mdi-diamond mdi-24px float-end"></i>
                                </h4>
                                <h2 class="mb-5"><?php echo $total_pareceres_pendentes; ?> Pareceres pendentes</h2>
                                <h6 class="card-text">Preencher agora</h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php
                // Removemos as seções de gráficos e "Recent Tickets" que não são relevantes para este dashboard.
                // Se precisar de dashboards mais complexos, eles podem ser adicionados aqui.
                ?>

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
    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"</script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="<?php echo getAssetUrl("assets/vendors/chart.js/chart.umd.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"); ?>"</script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"</script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"</script>
            <script src="<?php echo getAssetUrl("assets/js/settings.js"); ?>"</script>
        <script src="<?php echo getAssetUrl("assets/js/todolist.js"); ?>"</script>
        <script src="<?php echo getAssetUrl("assets/js/jquery.cookie.js"); ?>"</script>
        <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="<?php echo getAssetUrl("assets/js/dashboard.js"); ?>"</script>
    <!-- End custom js for this page -->
</body>

</html>
