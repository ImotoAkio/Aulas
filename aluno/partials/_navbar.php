<?php
// Garantir que a função getBaseUrl() esteja disponível
if (!function_exists('getBaseUrl')) {
    require_once __DIR__ . '/../../config/database.php';
}
?>
<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <style>
    .nav-profile-img {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      margin-right: 10px;
    }
    .nav-profile-text {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .nav-profile-text p {
      margin: 0;
      line-height: 1.2;
    }
    .nav-profile-text small {
      line-height: 1;
    }
  </style>
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
    <a class="navbar-brand brand-logo" href="index.php">
      <i class="mdi mdi-school" style="font-size: 24px; color: #667eea;"></i>
      Rosa de Sharom
    </a>
    <a class="navbar-brand brand-logo-mini" href="index.php">
      <i class="mdi mdi-school" style="font-size: 20px; color: #667eea;"></i>
    </a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-stretch">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="mdi mdi-menu"></span>
    </button>
    <div class="search-field d-none d-md-block">
      <form class="d-flex align-items-center h-100" action="#" method="get">
        <div class="input-group">
          <div class="input-group-prepend bg-transparent">
            <i class="input-group-text border-0 mdi mdi-magnify"></i>
          </div>
          <input type="text" class="form-control bg-transparent border-0" placeholder="Buscar...">
        </div>
      </form>
    </div>
    <ul class="navbar-nav navbar-nav-right">
             <li class="nav-item nav-profile dropdown">
         <a class="nav-link dropdown-toggle d-flex align-items-center" id="profileDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
           <div class="nav-profile-img">
             <i class="mdi mdi-account-circle" style="font-size: 40px; color: #667eea;"></i>
             <span class="availability-status online"></span>
           </div>
                       <div class="nav-profile-text">
              <p class="mb-1 text-black"><?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Aluno')[0] . ' ' . (explode(' ', $_SESSION['usuario_nome'] ?? 'Aluno')[1] ?? '')) ?></p>
              <small class="text-muted">Aluno</small>
            </div>
         </a>
        <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
          <a class="dropdown-item" href="perfil.php">
            <i class="mdi mdi-account me-2"></i> Meu Perfil
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="<?php echo getBaseUrl(); ?>logout.php">
            <i class="mdi mdi-logout me-2"></i> Sair
          </a>
        </div>
      </li>
      <li class="nav-item dropdown d-none d-lg-block">
        <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
          <i class="mdi mdi-bell-outline"></i>
          <span class="count-symbol bg-danger"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
          <h6 class="p-3 mb-0">Notificações</h6>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="notas.php">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-success">
                <i class="mdi mdi-chart-line"></i>
              </div>
            </div>
            <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
              <h6 class="preview-subject font-weight-normal mb-1">Suas Notas</h6>
              <p class="text-gray ellipsis mb-0">Acompanhe seu desempenho acadêmico</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="pareceres.php">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-info">
                <i class="mdi mdi-file-document"></i>
              </div>
            </div>
            <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
              <h6 class="preview-subject font-weight-normal mb-1">Pareceres Pedagógicos</h6>
              <p class="text-gray ellipsis mb-0">Avaliações dos professores</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item" href="declaracoes.php">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-warning">
                <i class="mdi mdi-certificate"></i>
              </div>
            </div>
            <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
              <h6 class="preview-subject font-weight-normal mb-1">Declarações</h6>
              <p class="text-gray ellipsis mb-0">Gere seus documentos escolares</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <h6 class="p-3 mb-0 text-center">Acesse o menu lateral para mais opções</h6>
        </div>
      </li>
      <li class="nav-item nav-logout d-none d-lg-block">
        <a class="nav-link" href="<?php echo getBaseUrl(); ?>logout.php">
          <i class="mdi mdi-power"></i>
        </a>
      </li>
      <li class="nav-item nav-settings d-none d-lg-block">
        <a class="nav-link" href="perfil.php">
          <i class="mdi mdi-format-line-spacing"></i>
        </a>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-menu"></span>
    </button>
    
    <!-- Botão de logout para dispositivos móveis -->
    <div class="d-lg-none">
      <a class="nav-link" href="<?php echo getBaseUrl(); ?>logout.php" style="color: #6c757d;">
        <i class="mdi mdi-power" style="font-size: 20px;"></i>
      </a>
    </div>
  </div>
</nav>
