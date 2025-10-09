<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado e é secretaria ou coordenador
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    redirectTo('login.php');
}

$pdo = getConnection();

// Processar salvamento das configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_configuracoes'])) {
    try {
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $webhook_aprovacao_url = trim($_POST['webhook_aprovacao_url'] ?? '');

        // Validar URLs dos webhooks
        $erros = [];
        if ($webhook_url && !filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            $erros[] = "URL do webhook de envio inválida!";
        }
        if ($webhook_aprovacao_url && !filter_var($webhook_aprovacao_url, FILTER_VALIDATE_URL)) {
            $erros[] = "URL do webhook de aprovação inválida!";
        }

        if (empty($erros)) {
            // Salvar webhook de envio
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, descricao, atualizado_em) 
                VALUES ('webhook_url', ?, 'URL do webhook para envio de dados JSON', NOW())
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor), 
                atualizado_em = NOW()
            ");
            $stmt->execute([$webhook_url]);

            // Salvar webhook de aprovação
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, descricao, atualizado_em) 
                VALUES ('webhook_aprovacao_url', ?, 'URL do webhook para notificação de aprovação', NOW())
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor), 
                atualizado_em = NOW()
            ");
            $stmt->execute([$webhook_aprovacao_url]);

            $sucesso = "Configurações salvas com sucesso!";
        } else {
            $erro = implode('<br>', $erros);
        }
    } catch (PDOException $e) {
        $erro = "Erro ao salvar configurações: " . $e->getMessage();
        error_log("Erro ao salvar configurações: " . $e->getMessage());
    }
}

// Buscar configurações atuais
$webhook_url = '';
$webhook_aprovacao_url = '';
try {
    $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('webhook_url', 'webhook_aprovacao_url')");
    $stmt->execute();
    $configuracoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $webhook_url = $configuracoes['webhook_url'] ?? '';
    $webhook_aprovacao_url = $configuracoes['webhook_aprovacao_url'] ?? '';
} catch (PDOException $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Avançadas - Secretaria</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl('assets/images/favicon.png'); ?>" />

    <style>
        /* Garantir que a sidebar funcione corretamente */
        .sidebar {
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Garantir que os links da sidebar funcionem */
        .nav-link {
            color: #333;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #007bff;
        }

        /* Garantir que o collapse funcione */
        .collapse {
            display: none;
        }

        .collapse.show {
            display: block;
        }

        /* Corrigir posicionamento do conteúdo principal */
        .main-panel {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .content-wrapper {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .page-header {
            margin-top: 0 !important;
            padding-top: 1rem !important;
        }

        /* Garantir que o conteúdo não fique por baixo da navbar */
        .container-scroller {
            padding-top: 0 !important;
        }

        .page-body-wrapper {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include 'partials/_navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include 'partials/_sidebar.php'; ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">
                            <span class="page-title-icon bg-gradient-primary text-white me-2">
                                <i class="mdi mdi-cog"></i>
                            </span>
                            Configurações Avançadas
                        </h3>
                        <nav aria-label="breadcrumb">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo getPageUrl('secretaria/index.php'); ?>">Secretaria</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Configurações</li>
                            </ul>
                        </nav>
                    </div>

                    <?php if (isset($sucesso)): ?>
                        <div class="alert alert-success">
                            <i class="mdi mdi-check-circle"></i>
                            <?php echo htmlspecialchars($sucesso); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert-circle"></i>
                            <?php echo htmlspecialchars($erro); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">
                                        <i class="mdi mdi-webhook text-primary me-2"></i>
                                        Configuração de Webhook
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">
                                        Configure as URLs dos webhooks para integração com sistemas externos. O primeiro webhook é usado para envio de dados JSON, e o segundo para notificações de aprovação.
                                    </p>

                                    <form method="POST" action="configuracoes.php">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group mb-4">
                                                    <label for="webhook_url" class="form-label">
                                                        <i class="mdi mdi-link text-primary me-1"></i>
                                                        URL do Webhook (Envio de Dados)
                                                    </label>
                                                    <input type="url"
                                                        class="form-control form-control-lg"
                                                        id="webhook_url"
                                                        name="webhook_url"
                                                        value="<?php echo htmlspecialchars($webhook_url); ?>"
                                                        placeholder="https://exemplo.com/webhook-envio"
                                                        required>
                                                    <div class="form-text">
                                                        <i class="mdi mdi-information text-info me-1"></i>
                                                        Esta URL receberá os dados JSON quando o botão "Enviar para WhatsApp" for clicado.
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group mb-4">
                                                    <label for="webhook_aprovacao_url" class="form-label">
                                                        <i class="mdi mdi-check-circle text-success me-1"></i>
                                                        URL do Webhook (Aprovação)
                                                    </label>
                                                    <input type="url"
                                                        class="form-control form-control-lg"
                                                        id="webhook_aprovacao_url"
                                                        name="webhook_aprovacao_url"
                                                        value="<?php echo htmlspecialchars($webhook_aprovacao_url); ?>"
                                                        placeholder="https://exemplo.com/webhook-aprovacao">
                                                    <div class="form-text">
                                                        <i class="mdi mdi-information text-info me-1"></i>
                                                        Esta URL receberá notificação quando um pré-cadastro for aprovado.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-4">
                                                    <label class="form-label">&nbsp;</label>
                                                    <div class="d-flex gap-2">
                                                        <button type="submit" name="salvar_configuracoes" class="btn btn-primary btn-lg flex-fill">
                                                            <i class="mdi mdi-content-save me-2"></i>
                                                            Salvar
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success btn-lg flex-fill" onclick="testarWebhook()">
                                                            <i class="mdi mdi-test-tube me-2"></i>
                                                            Testar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Resultado do Teste -->
                                    <div id="teste-card" style="display: none;">
                                        <hr>
                                        <h5 class="mb-3">
                                            <i class="mdi mdi-test-tube text-success me-2"></i>
                                            Resultado do Teste
                                        </h5>
                                        <div id="teste-resultado">
                                            <div class="alert" id="teste-alert">
                                                <div id="teste-mensagem"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informações sobre o Webhook -->
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">
                                                <i class="mdi mdi-help-circle text-info me-2"></i>
                                                Como Funciona
                                            </h6>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge badge-primary badge-pill me-2">1</span>
                                                    <strong>Configuração</strong>
                                                </div>
                                                <p class="text-muted small">Configure a URL do webhook onde os dados serão enviados.</p>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge badge-primary badge-pill me-2">2</span>
                                                    <strong>Uso</strong>
                                                </div>
                                                <p class="text-muted small">Ao clicar em "Enviar para WhatsApp" na visualização do pré-cadastro, os dados JSON serão enviados para esta URL.</p>
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge badge-primary badge-pill me-2">3</span>
                                                    <strong>Integração</strong>
                                                </div>
                                                <p class="text-muted small">Use os dados recebidos para enviar mensagens via WhatsApp, SMS ou outros serviços.</p>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <h6 class="mb-3">
                                                <i class="mdi mdi-code-json text-warning me-2"></i>
                                                Formato dos Dados
                                            </h6>
                                            <div class="mb-3">
                                                <label class="form-label small">Estrutura JSON:</label>
                                                <div class="bg-dark text-light p-3 rounded">
                                                    <pre class="mb-0 small"><code>{
  "link_cadastro": "https://app.colegiorosadesharom.com.br/cadastro_aluno.php?codigo=ABC123",
  "telefone": "5587991682773",
  "aluno_nome": "Nome do Aluno",
  "timestamp": "2025-10-07 01:30:15"
}</code></pre>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small">Método HTTP:</label>
                                                <div class="bg-light p-2 rounded">
                                                    <code class="text-primary">POST</code> com <code class="text-info">Content-Type: application/json</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="mdi mdi-alert me-2"></i>
                                        <strong>Importante:</strong> O webhook deve retornar status HTTP 200-299 para ser considerado sucesso. Teste sempre o webhook antes de usar em produção.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/off-canvas.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/hoverable-collapse.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/misc.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/settings.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/todolist.js'); ?>"></script>

    <script>
        function testarWebhook() {
            const webhookUrl = document.getElementById('webhook_url').value;
            const resultadoDiv = document.getElementById('teste-resultado');
            const alertDiv = document.getElementById('teste-alert');
            const mensagemDiv = document.getElementById('teste-mensagem');
            const testeCard = document.getElementById('teste-card');

            if (!webhookUrl) {
                alert('Por favor, configure a URL do webhook primeiro.');
                return;
            }

            // Dados de teste
            const dadosTeste = {
                link_cadastro: "https://app.colegiorosadesharom.com.br/cadastro_aluno.php?codigo=TESTE123",
                telefone: "5587991682773",
                aluno_nome: "Aluno Teste",
                timestamp: new Date().toISOString().slice(0, 19).replace('T', ' ')
            };

            // Mostrar card de resultado
            testeCard.style.display = 'block';

            // Mostrar loading
            alertDiv.className = 'alert alert-info';
            mensagemDiv.innerHTML = '<i class="mdi mdi-loading mdi-spin me-2"></i>Testando webhook...';

            // Fazer requisição
            fetch(webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dadosTeste)
                })
                .then(response => {
                    if (response.ok) {
                        alertDiv.className = 'alert alert-success';
                        mensagemDiv.innerHTML = '<i class="mdi mdi-check-circle me-2"></i><strong>Sucesso!</strong> Webhook funcionando corretamente! Status: ' + response.status;
                    } else {
                        alertDiv.className = 'alert alert-warning';
                        mensagemDiv.innerHTML = '<i class="mdi mdi-alert me-2"></i><strong>Atenção!</strong> Webhook retornou status: ' + response.status;
                    }
                })
                .catch(error => {
                    alertDiv.className = 'alert alert-danger';
                    mensagemDiv.innerHTML = '<i class="mdi mdi-close-circle me-2"></i><strong>Erro!</strong> Erro ao testar webhook: ' + error.message;
                });
        }

        // Garantir que a sidebar funcione
        $(document).ready(function() {
            // Inicializar collapse da sidebar
            $('[data-toggle="collapse"]').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $(target).collapse('toggle');
            });

            // Garantir que os links funcionem
            $('.nav-link').on('click', function(e) {
                if ($(this).attr('href') === '#') {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>