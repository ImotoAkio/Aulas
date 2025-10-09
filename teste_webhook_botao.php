<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Processar teste do webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'testar_webhook') {
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $telefone_teste = trim($_POST['telefone_teste'] ?? '');
        $aluno_teste = trim($_POST['aluno_teste'] ?? '');
        
        if ($webhook_url && $telefone_teste && $aluno_teste) {
            // Dados de teste para envio
            $dados_teste = [
                'link_cadastro' => getBaseUrl() . 'cadastro_aluno.php?codigo=TESTE123',
                'telefone' => $telefone_teste,
                'aluno_nome' => $aluno_teste,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Enviar via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhook_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_teste));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: Sistema-Escolar-Teste/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $resultado_teste = [
                    'status' => 'erro',
                    'mensagem' => 'Erro cURL: ' . $error,
                    'dados_enviados' => $dados_teste
                ];
            } else {
                $resultado_teste = [
                    'status' => $http_code >= 200 && $http_code < 300 ? 'sucesso' : 'erro',
                    'http_code' => $http_code,
                    'response' => $response,
                    'dados_enviados' => $dados_teste
                ];
            }
        } else {
            $erro_teste = "Por favor, preencha todos os campos obrigatórios.";
        }
    }
}

// Buscar webhook configurado
$webhook_configurado = '';
try {
    $stmt = $pdo->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'webhook_url'");
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resultado && !empty($resultado['valor'])) {
        $webhook_configurado = $resultado['valor'];
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar webhook: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Webhook - Sistema Escolar</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />
    <style>
        .test-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .test-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-test {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        .json-preview {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-sucesso { background: #28a745; color: #fff; }
        .status-erro { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Header -->
        <div class="test-card">
            <div class="test-header">
                <h1><i class="mdi mdi-webhook"></i> Teste de Webhook</h1>
                <p class="mb-0">Página para testar a funcionalidade de webhook do sistema</p>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($erro_teste)): ?>
        <div class="alert alert-danger alert-custom">
            <h5><i class="mdi mdi-alert-circle"></i> Erro!</h5>
            <?php echo $erro_teste; ?>
        </div>
        <?php endif; ?>

        <!-- Formulário de Teste -->
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-test-tube"></i> Teste de Webhook</h3>
                <p class="text-muted">Configure os dados para testar o webhook</p>
                
                <form method="POST" action="teste_webhook_botao.php">
                    <input type="hidden" name="acao" value="testar_webhook">
                    
                    <div class="form-group">
                        <label for="webhook_url" class="form-label">
                            <i class="mdi mdi-link text-primary"></i> URL do Webhook *
                        </label>
                        <input type="url" 
                               class="form-control" 
                               id="webhook_url" 
                               name="webhook_url" 
                               value="<?php echo htmlspecialchars($webhook_configurado); ?>"
                               placeholder="https://exemplo.com/webhook"
                               required>
                        <small class="form-text text-muted">
                            URL que receberá os dados JSON via POST
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone_teste" class="form-label">
                            <i class="mdi mdi-phone text-success"></i> Telefone de Teste *
                        </label>
                        <input type="tel" 
                               class="form-control" 
                               id="telefone_teste" 
                               name="telefone_teste" 
                               value="5587999999999"
                               placeholder="5587999999999"
                               required>
                        <small class="form-text text-muted">
                            Número que receberá os dados (formato: 55 + DDD + número)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="aluno_teste" class="form-label">
                            <i class="mdi mdi-account text-info"></i> Nome do Aluno de Teste *
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="aluno_teste" 
                               name="aluno_teste" 
                               value="João Silva Teste"
                               placeholder="Nome do aluno para teste"
                               required>
                        <small class="form-text text-muted">
                            Nome que aparecerá nos dados enviados
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-test w-100">
                            <i class="mdi mdi-send"></i> Testar Webhook
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultado do Teste -->
        <?php if (isset($resultado_teste)): ?>
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-chart-line"></i> Resultado do Teste</h3>
                
                <div class="form-group">
                    <h5>Status do Envio:</h5>
                    <span class="status-badge status-<?php echo $resultado_teste['status']; ?>">
                        <?php echo ucfirst($resultado_teste['status']); ?>
                    </span>
                    
                    <?php if (isset($resultado_teste['http_code'])): ?>
                    <p class="mt-2"><strong>HTTP Code:</strong> <?php echo $resultado_teste['http_code']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($resultado_teste['mensagem'])): ?>
                    <p class="mt-2"><strong>Mensagem:</strong> <?php echo htmlspecialchars($resultado_teste['mensagem']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($resultado_teste['response'])): ?>
                    <p class="mt-2"><strong>Resposta:</strong></p>
                    <div class="json-preview"><?php echo htmlspecialchars($resultado_teste['response']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <h5>Dados Enviados:</h5>
                    <div class="json-preview"><?php echo json_encode($resultado_teste['dados_enviados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informações do Webhook -->
        <div class="test-card">
            <div class="test-body">
                <h3><i class="mdi mdi-information"></i> Informações do Webhook</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Método HTTP:</h5>
                        <p><code>POST</code></p>
                        
                        <h5>Content-Type:</h5>
                        <p><code>application/json</code></p>
                        
                        <h5>Headers:</h5>
                        <ul>
                            <li><code>Content-Type: application/json</code></li>
                            <li><code>User-Agent: Sistema-Escolar/1.0</code></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Estrutura dos Dados:</h5>
                        <div class="json-preview">{
  "link_cadastro": "https://exemplo.com/cadastro_aluno.php?codigo=ABC123",
  "telefone": "5587999999999",
  "aluno_nome": "João Silva",
  "timestamp": "2025-01-27 14:30:00"
}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="test-card">
            <div class="test-body text-center">
                <h3><i class="mdi mdi-navigation"></i> Navegação</h3>
                <div class="btn-group" role="group">
                    <a href="secretaria/configuracoes.php" class="btn btn-outline-primary">
                        <i class="mdi mdi-cog"></i> Configurações Secretaria
                    </a>
                    <a href="financeiro/configuracoes.php" class="btn btn-outline-success">
                        <i class="mdi mdi-cog"></i> Configurações Financeiro
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="mdi mdi-view-dashboard"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const webhookUrl = document.getElementById('webhook_url').value;
            const telefone = document.getElementById('telefone_teste').value;
            const aluno = document.getElementById('aluno_teste').value;
            
            if (!webhookUrl || !telefone || !aluno) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            // Validar URL
            try {
                new URL(webhookUrl);
            } catch (error) {
                e.preventDefault();
                alert('Por favor, insira uma URL válida.');
                return;
            }
            
            // Validar telefone (deve ter pelo menos 10 dígitos)
            const telefoneLimpo = telefone.replace(/[^0-9]/g, '');
            if (telefoneLimpo.length < 10) {
                e.preventDefault();
                alert('Por favor, insira um telefone válido (mínimo 10 dígitos).');
                return;
            }
        });
    </script>
</body>
</html>
