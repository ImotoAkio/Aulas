<?php
if (!function_exists('getAssetUrl')) {
  require_once __DIR__ . '/../../config/database.php';
}
?>
<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <a class="navbar-brand brand-logo" href="<?php echo getPageUrl('financeiro/index.php'); ?>">Financeiro</a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-stretch">
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item nav-logout">
        <a class="nav-link" href="<?php echo getBaseUrl(); ?>logout.php"><i class="mdi mdi-power"></i></a>
      </li>
    </ul>
  </div>
</nav>


