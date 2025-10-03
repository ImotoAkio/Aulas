<?php
// Garantir que as funções estejam disponíveis
if (!function_exists('getPageUrl')) {
    require_once __DIR__ . '/../../config/database.php';
}

session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('login.php');
    exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $ano_letivo = trim($_POST['ano_letivo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Validações
    if (empty($nome)) {
        $erro = 'Nome da turma é obrigatório.';
    } elseif (empty($ano_letivo)) {
        $erro = 'Ano letivo é obrigatório.';
    } elseif (!is_numeric($ano_letivo) || $ano_letivo < 2020 || $ano_letivo > 2030) {
        $erro = 'Ano letivo deve ser um número entre 2020 e 2030.';
    } else {
        try {
            // Verificar se já existe turma com mesmo nome e ano
            $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND ano_letivo = ?");
            $stmt->execute([$nome, $ano_letivo]);
            
            if ($stmt->fetch()) {
                $erro = 'Já existe uma turma com este nome no ano letivo ' . $ano_letivo . '.';
            } else {
                // Inserir nova turma
                $stmt = $pdo->prepare("INSERT INTO turmas (nome, ano_letivo, descricao) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $ano_letivo, $descricao]);
                
                $_SESSION['sucesso'] = 'Turma "' . $nome . '" cadastrada com sucesso!';
                header('Location: turmas.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar turma: " . $e->getMessage());
            $erro = 'Erro interno do sistema. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Adicionar Turma - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/ti-icons/css/themify-icons.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/font-awesome/css/font-awesome.min.css'); ?>">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:../partials/_navbar.html -->
    <?php include '../partials/_navbar.php'; ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../partials/_sidebar.html -->
      <?php include '../partials/_sidebar.php'; ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <h3 class="page-title">Adicionar Turma</h3>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href='<?php echo getPageUrl("secretaria/index.php"); ?>'>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="turmas.php">Turmas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Adicionar</li>
              </ol>
            </nav>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-school"></i> Nova Turma
                  </h4>
                  <p class="card-description">
                    Preencha os dados da nova turma
                  </p>

                  <?php if ($erro): ?>
                    <div class="alert alert-danger">
                      <i class="mdi mdi-alert-circle"></i>
                      <?= htmlspecialchars($erro) ?>
                    </div>
                  <?php endif; ?>

                  <form method="POST" class="forms-sample">
                    <div class="form-group">
                      <label for="nome">Nome da Turma *</label>
                      <input type="text" 
                             class="form-control" 
                             id="nome" 
                             name="nome" 
                             placeholder="Ex: 1° ANO, 2° ANO, G2/G3" 
                             value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                             required>
                      <small class="form-text text-muted">Nome identificador da turma</small>
                    </div>

                    <div class="form-group">
                      <label for="ano_letivo">Ano Letivo *</label>
                      <input type="number" 
                             class="form-control" 
                             id="ano_letivo" 
                             name="ano_letivo" 
                             placeholder="2025" 
                             min="2020" 
                             max="2030"
                             value="<?= htmlspecialchars($_POST['ano_letivo'] ?? date('Y')) ?>"
                             required>
                      <small class="form-text text-muted">Ano letivo da turma</small>
                    </div>

                    <div class="form-group">
                      <label for="descricao">Descrição</label>
                      <textarea class="form-control" 
                                id="descricao" 
                                name="descricao" 
                                rows="3" 
                                placeholder="Descrição opcional da turma"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                      <small class="form-text text-muted">Informações adicionais sobre a turma</small>
                    </div>

                    <div class="form-group">
                      <button type="submit" class="btn btn-primary mr-2">
                        <i class="mdi mdi-content-save"></i> Salvar
                      </button>
                      <a href="turmas.php" class="btn btn-light">
                        <i class="mdi mdi-close"></i> Cancelar
                      </a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
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
  <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
  <!-- endinject -->
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- inject:js -->
  <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>
  <script src="<?php echo getAssetUrl('assets/js/jquery.cookie.js'); ?>"></script>
  <!-- endinject -->
</body>

</html>
