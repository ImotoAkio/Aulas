<div class="container-fluid page-body-wrapper">
  <!-- partial:partials/_sidebar.html -->
  <nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
      <li class="nav-item nav-profile">
        <a href="#" class="nav-link">
          <div class="nav-profile-image">
            <img src="../assets/images/faces/face1.jpg" alt="profile" />
            <span class="login-status online"></span>
            <!--change to offline or busy as needed-->
          </div>
          <div class="nav-profile-text d-flex flex-column">
            <span class="font-weight-bold mb-2"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?></span>
            <span class="text-secondary text-small">Secretaria</span>
          </div>
          <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php">
          <span class="menu-title">Página Inicial</span>
          <i class="mdi mdi-home menu-icon"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
          <span class="menu-title">Pedagógico</span>
          <i class="menu-arrow"></i>
          <i class="mdi mdi-crosshairs-gps menu-icon"></i>
        </a>
        <div class="collapse" id="ui-basic">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="planos.php">Planos de Aula</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="notas.php">Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="parecer.php">Parecer Pedagógico</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#icons" aria-expanded="false" aria-controls="icons">
          <span class="menu-title">Cadastros</span>
          <i class="mdi mdi-contacts menu-icon"></i>
        </a>
        <div class="collapse" id="icons">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="cad/professor.php">Professor</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="cad/aluno.php">Aluno</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="cad/secretario.php">Secretario</a>
            </li>
          </ul>
        </div>
      </li>





    </ul>
  </nav>
  <!-- partial -->