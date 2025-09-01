  <!-- partial:partials/_sidebar.html -->
  <nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
      <li class="nav-item nav-profile">
        <a href="#" class="nav-link">
          <div class="nav-profile-image">
            <i class="mdi mdi-account-circle" style="font-size: 40px; color: #667eea;"></i>
            <span class="login-status online"></span>
          </div>
          <div class="nav-profile-text d-flex flex-column">
            <span class="font-weight-bold mb-2"><?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Aluno')[0] . ' ' . (explode(' ', $_SESSION['usuario_nome'] ?? 'Aluno')[1] ?? '')) ?></span>
            <span class="text-secondary text-small">Aluno</span>
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
        <a class="nav-link" data-bs-toggle="collapse" href="#academico" aria-expanded="false" aria-controls="academico">
          <span class="menu-title">Acadêmico</span>
          <i class="menu-arrow"></i>
          <i class="mdi mdi-book-open-page-variant menu-icon"></i>
        </a>
        <div class="collapse" id="academico">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="notas.php">Minhas Notas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="pareceres.php">Pareceres Pedagógicos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="boletim.php">Boletim Escolar</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#documentos" aria-expanded="false" aria-controls="documentos">
          <span class="menu-title">Documentos</span>
          <i class="menu-arrow"></i>
          <i class="mdi mdi-file-document menu-icon"></i>
        </a>
        <div class="collapse" id="documentos">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="declaracoes.php">Gerar Declarações</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="historico.php">Histórico Escolar</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#perfil" aria-expanded="false" aria-controls="perfil">
          <span class="menu-title">Perfil</span>
          <i class="menu-arrow"></i>
          <i class="mdi mdi-account menu-icon"></i>
        </a>
        <div class="collapse" id="perfil">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="perfil.php">Meus Dados</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="alterar_senha.php">Alterar Senha</a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../../logout.php">
          <span class="menu-title">Sair</span>
          <i class="mdi mdi-logout menu-icon"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- partial -->
