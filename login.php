<?php
session_start();
include('secretaria/partials/db.php');

// Verificar se já está logado
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['tipo'] === 'aluno') {
        header('Location: aluno/index.php');
    } else {
        header('Location: secretaria/index.php');
    }
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = trim($_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($cpf) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Limpar CPF (remover pontos e traços)
            $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
            
            // Primeiro, tentar login como usuário (professor/coordenador)
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$cpf]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login como usuário do sistema
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['tipo'] = $usuario['tipo'];
                
                if ($usuario['tipo'] === 'coordenador') {
                    header('Location: secretaria/index.php');
                } else {
                    header('Location: professor/index.php');
                }
                exit();
            } else {
                // Tentar login como aluno
                $stmt = $pdo->prepare("
                    SELECT a.id, a.nome_completo, a.nome, a.cpf, a.cpf_mae, a.cpf_pai, t.nome as turma_nome, t.ano_letivo
                    FROM alunos a 
                    LEFT JOIN turmas t ON a.turma_id = t.id 
                    WHERE a.cpf = ? OR a.cpf_mae = ? OR a.cpf_pai = ?
                ");
                $stmt->execute([$cpf_limpo, $cpf_limpo, $cpf_limpo]);
                $aluno = $stmt->fetch();
                
                if ($aluno && $senha === 'CRS2025') {
                    // Login como aluno
                    $_SESSION['usuario_id'] = $aluno['id'];
                    $_SESSION['usuario_nome'] = $aluno['nome_completo'] ?: $aluno['nome'];
                    $_SESSION['usuario_email'] = $aluno['cpf'];
                    $_SESSION['tipo'] = 'aluno';
                    $_SESSION['aluno_turma'] = $aluno['turma_nome'];
                    $_SESSION['aluno_ano_letivo'] = $aluno['ano_letivo'];
                    
                    header('Location: aluno/index.php');
                    exit();
                } else {
                    $erro = 'CPF ou senha incorretos.';
                }
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $erro = 'Erro interno do sistema. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Login - Colégio Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <style>
    .login-container {
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      overflow: hidden;
      width: 100%;
      max-width: 450px;
      margin: 20px;
    }
    .login-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }
    .logo-container {
      margin-bottom: 20px;
    }
    .logo-container img {
      height: 80px;
      margin-bottom: 15px;
    }
    .school-name {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .system-name {
      font-size: 14px;
      opacity: 0.9;
    }
    .login-body {
      padding: 40px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-control {
      border-radius: 8px;
      border: 2px solid #e9ecef;
      padding: 12px 15px;
      transition: all 0.3s ease;
    }
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 8px;
      padding: 12px;
      color: white;
      font-weight: bold;
      width: 100%;
      transition: all 0.3s ease;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    .alert {
      border-radius: 8px;
      border: none;
    }
    .info-box {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-top: 20px;
      border-left: 4px solid #667eea;
    }
    .info-box h6 {
      color: #667eea;
      margin-bottom: 10px;
    }
    .info-box ul {
      margin-bottom: 0;
      padding-left: 20px;
    }
    .info-box li {
      margin-bottom: 5px;
      color: #6c757d;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-container">
          <img src="assets/images/logo.png" alt="Logo Colégio Rosa de Sharom">
          <div class="school-name">Colégio Rosa de Sharom</div>
          <div class="system-name">Sistema de Gestão Escolar</div>
        </div>
      </div>
      
      <div class="login-body">
        <h4 class="text-center mb-4">Acesso ao Sistema</h4>
        
        <?php if ($erro): ?>
          <div class="alert alert-danger">
            <i class="mdi mdi-alert"></i> <?= htmlspecialchars($erro) ?>
          </div>
        <?php endif; ?>
        
        <form method="POST">
          <div class="form-group">
            <label for="cpf">CPF ou Email</label>
            <input type="text" class="form-control" id="cpf" name="cpf" 
                   placeholder="CPF: 000.000.000-00 ou Email" required>
          </div>
          
          <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
          </div>
          
          <button type="submit" class="btn btn-login">
            <i class="mdi mdi-login"></i> Entrar
          </button>
        </form>
        
        <div class="info-box">
          <h6><i class="mdi mdi-information"></i> Informações de Acesso</h6>
          <ul>
            <li><strong>Alunos:</strong> Use seu CPF ou CPF dos responsáveis</li>
            <li><strong>Senha padrão:</strong> CRS2025</li>
            <li><strong>Professores/Secretaria:</strong> Use email e senha cadastrada</li>
            <li><strong>Suporte:</strong> Entre em contato com a secretaria</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- plugins:js -->
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/misc.js"></script>
  <!-- endinject -->

  <script>
    // Máscara inteligente para CPF ou Email
    document.getElementById('cpf').addEventListener('input', function(e) {
      let value = e.target.value;
      
      // Se contém @, é email - não aplicar máscara
      if (value.includes('@')) {
        return;
      }
      
      // Se não contém @, aplicar máscara de CPF
      value = value.replace(/\D/g, '');
      if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        e.target.value = value;
      }
    });
  </script>
</body>

</html>