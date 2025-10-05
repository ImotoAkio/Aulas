<?php
if (!function_exists('getPageUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <i class="mdi mdi-account-circle" style="font-size: 40px; color: #667eea;"></i>
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column">
          <span class="font-weight-bold mb-2"><?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[0] . ' ' . (explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[1] ?? '')) ?></span>
          <span class="text-secondary text-small">Financeiro</span>
        </div>
        <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo getPageUrl('financeiro/index.php'); ?>">
        <span class="menu-title">Página Inicial</span>
        <i class="mdi mdi-home menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#pagamentos" aria-expanded="false" aria-controls="pagamentos">
        <span class="menu-title">Pagamentos</span>
        <i class="mdi mdi-cash-multiple menu-icon"></i>
      </a>
      <div class="collapse" id="pagamentos">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo getPageUrl('financeiro/pagamentos.php'); ?>">Listar/Filtrar</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo getPageUrl('financeiro/mensalidades.php'); ?>">Gerar Mensalidades</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo getPageUrl('financeiro/listar_mensalidades.php'); ?>">Listar Mensalidades</a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#alunos" aria-expanded="false" aria-controls="alunos">
        <span class="menu-title">Alunos</span>
        <i class="mdi mdi-account-multiple menu-icon"></i>
      </a>
      <div class="collapse" id="alunos">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo getPageUrl('financeiro/alunos.php'); ?>">Lista</a>
          </li>
        </ul>
      </div>
    </li>
  </ul>
</nav>


