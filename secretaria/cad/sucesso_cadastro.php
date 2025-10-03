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

// Verificar se há mensagem de sucesso
if (!isset($_SESSION['sucesso_cadastro'])) {
    require_once __DIR__ . '/../../config/database.php';
    redirectTo('secretaria/index.php');
    exit();
}

$mensagem = $_SESSION['sucesso_cadastro'];
unset($_SESSION['sucesso_cadastro']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Sucesso - Cadastro de Aluno</title>
  <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
              <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
        <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .success-modal {
      background: white;
      padding: 40px;
      border-radius: 15px;
      text-align: center;
      max-width: 450px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideIn 0.5s ease-out;
      position: relative;
      overflow: hidden;
    }
    
    .success-modal::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
    }
    
    .success-icon {
      font-size: 80px;
      color: #28a745;
      margin-bottom: 20px;
      animation: bounce 0.6s ease-out;
    }
    
    .success-title {
      font-size: 28px;
      color: #28a745;
      margin-bottom: 15px;
      font-weight: bold;
    }
    
    .success-message {
      font-size: 18px;
      color: #666;
      margin-bottom: 30px;
      line-height: 1.6;
    }
    
    .redirect-message {
      font-size: 14px;
      color: #999;
      font-style: italic;
      margin-bottom: 20px;
    }
    
    .progress-bar {
      width: 100%;
      height: 4px;
      background-color: #e9ecef;
      border-radius: 2px;
      overflow: hidden;
      margin-top: 20px;
    }
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #28a745, #20c997);
      width: 0%;
      animation: progress 1s linear forwards;
    }
    
    @keyframes slideIn {
      from { 
        transform: translateY(-100px) scale(0.9);
        opacity: 0;
      }
      to { 
        transform: translateY(0) scale(1);
        opacity: 1;
      }
    }
    
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
      }
      40% {
        transform: translateY(-10px);
      }
      60% {
        transform: translateY(-5px);
      }
    }
    
    @keyframes progress {
      from { width: 0%; }
      to { width: 100%; }
    }
    
    @keyframes fadeOut {
      from { 
        opacity: 1;
        transform: scale(1);
      }
      to { 
        opacity: 0;
        transform: scale(0.9);
      }
    }
    
    .fade-out {
      animation: fadeOut 0.3s ease-out forwards;
    }
    
    .close-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      background: none;
      border: none;
      font-size: 24px;
      color: #999;
      cursor: pointer;
      padding: 5px;
      border-radius: 50%;
      transition: all 0.3s ease;
    }
    
    .close-btn:hover {
      background-color: #f8f9fa;
      color: #666;
    }
  </style>
</head>

<body>
  <!-- Modal de Sucesso -->
  <div class="success-modal" id="successModal">
    <button class="close-btn" onclick="closeModal()">×</button>
    <i class="mdi mdi-check-circle success-icon"></i>
    <h4 class="success-title">Sucesso!</h4>
    <p class="success-message"><?= htmlspecialchars($mensagem) ?></p>
    <p class="redirect-message">Redirecionando automaticamente...</p>
    <div class="progress-bar">
      <div class="progress-fill"></div>
    </div>
  </div>

  <script>
    // Função para fechar a modal
    function closeModal() {
      const modal = document.getElementById('successModal');
      modal.classList.add('fade-out');
      
      setTimeout(() => {
        window.location.href = '<?php echo getPageUrl("secretaria/index.php"); ?>';
      }, 300);
    }

    // Fechar modal automaticamente após 1 segundo
    setTimeout(closeModal, 1000);

    // Permitir fechar com ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  </script>
</body>

</html>
