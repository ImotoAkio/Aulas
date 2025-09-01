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
            <span class="text-secondary text-small">Secretaria</span>
          </div>
          <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/aulas/secretaria/index.php">
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
              <a class="nav-link" href="/aulas/secretaria/planos.php">Planos de Aula</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/notas.php">Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/parecer.php">Parecer Pedagógico</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#cadastros" aria-expanded="false" aria-controls="cadastros">
          <span class="menu-title">Cadastros</span>
          <i class="mdi mdi-contacts menu-icon"></i>
        </a>
        <div class="collapse" id="cadastros">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/cad/professor.php">Professor</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/cad/aluno.php">Aluno</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/cad/listar_alunos.php">Listar Alunos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/cad/secretario.php">Secretário</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#declaracoes" aria-expanded="false" aria-controls="declaracoes">
          <span class="menu-title">Declarações</span>
          <i class="mdi mdi-file-document menu-icon"></i>
        </a>
        <div class="collapse" id="declaracoes">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/declaracoes/professor.php">Professor</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/aulas/secretaria/declaracoes/aluno.php">Vínculo - Aluno</a>
            </li>
          </ul>
        </div>
      </li>





    </ul>
  </nav>
  <!-- partial -->