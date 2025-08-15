<?php
session_start(); // Deve ser a primeira linha executável

// Ativar exibição de erros (para depuração, remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php'; // Certifique-se de que db.php está no mesmo nível ou ajuste o caminho

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC); // Adicionado FETCH_ASSOC para garantir array associativo

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // CORREÇÃO AQUI: Usar a variável $usuario que contém os dados buscados do DB
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome']; 
        $_SESSION['tipo'] = $usuario['tipo'];

        if ($usuario['tipo'] == 'professor') {
            header('Location: professor/index.php'); // Redirecionamento para a página inicial do professor
        } elseif ($usuario['tipo'] == 'secretaria' || $usuario['tipo'] == 'coordenador') {
            header('Location: secretaria/index.php'); // Redirecionamento para a página inicial da secretaria/coordenador
        } else {
            // Caso existam outros tipos de usuário ou um default
            header('Location: index.php'); 
        }
        exit;
    } else {
        // Credenciais inválidas
        echo "<div class='alert alert-danger'>Email ou senha inválidos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="brand-logo">
                  <img src="assets/images/logo.svg">
                </div>
                <h4>Olá! Seja bem vindo ao Echo Edu</h4>
                <h6 class="font-weight-light">Faça Login para continuar.</h6>
                <form class="pt-3" method="POST">
                  <div class="form-group">
                    <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Email">
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" id="senha" name="senha" placeholder="Senha">
                  </div>
                  <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">Entrar</button>
                  </div>
                  <div class="my-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                      <label class="form-check-label text-muted">
                        <input type="checkbox" class="form-check-input"> Manter-me conectado </label>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/misc.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    <script src="assets/js/jquery.cookie.js"></script>
    <!-- endinject -->
  </body>
</html>