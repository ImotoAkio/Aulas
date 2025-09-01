<div class="container-fluid page-body-wrapper">
  <!-- partial:partials/_sidebar.html -->
  <nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
      <li class="nav-item nav-profile">
        <a href="#" class="nav-link">
          <div class="nav-profile-image">
            <i class="mdi mdi-account-circle" style="font-size: 40px; color: #667eea;"></i>
            <span class="login-status online"></span>
            <!--change to offline or busy as needed-->
          </div>
          <div class="nav-profile-text d-flex flex-column">
            <span class="font-weight-bold mb-2"><?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[0] . ' ' . (explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[1] ?? '')) ?></span>
            <span class="text-secondary text-small">Professor</span>
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
      <!--<li class="nav-item">
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
              <a class="nav-link" href="notas.php">Inserir Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="ver_notas.php">Visualizar Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="parecer.php">Parecer Pedagógico</a>
            </li>
          </ul>
        </div>
      </li>
-->
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#icons" aria-expanded="false" aria-controls="icons">
          <span class="menu-title">Planos de Aula</span>
          <i class="mdi mdi-contacts menu-icon"></i>
        </a>
        <div class="collapse" id="icons">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="planos.php">Planos de Aula</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="planos_revisao.php">Revisão</a>
            </li>

          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#forms" aria-expanded="false" aria-controls="forms">
          <span class="menu-title">Notas</span>
          <i class="mdi mdi-format-list-bulleted menu-icon"></i>
        </a>
        <div class="collapse" id="forms">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="notas.php">Inserir Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="ver_notas.php">Ver Notas</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#tables" aria-expanded="false" aria-controls="tables">
          <span class="menu-title">Parecer</span>
          <i class="mdi mdi-table-large menu-icon"></i>
        </a>
        <div class="collapse" id="tables">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="parecer.php">Parecer</a>
            </li>
          </ul>
        </div>
      </li>


    </ul>
  </nav>
  <!-- partial -->