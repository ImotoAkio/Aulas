<?php
if (!function_exists('getPageUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item">
      <a class="nav-link" href="<?php echo getPageUrl('financeiro/index.php'); ?>">
        <span class="menu-title">Dashboard</span>
        <i class="mdi mdi-view-dashboard menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo getPageUrl('financeiro/pagamentos.php'); ?>">
        <span class="menu-title">Pagamentos</span>
        <i class="mdi mdi-cash-multiple menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo getPageUrl('financeiro/alunos.php'); ?>">
        <span class="menu-title">Alunos</span>
        <i class="mdi mdi-account-multiple menu-icon"></i>
      </a>
    </li>
  </ul>
</nav>


