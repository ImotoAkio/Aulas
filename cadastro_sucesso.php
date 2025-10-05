<?php
require_once 'config/database.php';

$pdo = getConnection();
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die("Código de acesso inválido.");
}

// Buscar dados do aluno para exibir
try {
    $stmt = $pdo->prepare("
        SELECT a.nome, a.status_cadastro, t.nome as turma_nome
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.codigo_pre_cadastro = ?
    ");
    $stmt->execute([$codigo]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        die("Dados não encontrados.");
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    die("Erro interno.");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Enviado - Educandário Rosa de Sharom</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <style>
        .success-container {
            background: #fff;
            border-radius: 20px;
            padding: 50px;
            margin: 50px auto;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 30px;
        }
        .success-title {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .success-message {
            color: #7f8c8d;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .next-steps {
            background: #ecf0f1;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .step-number {
            background: #3498db;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        .contact-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="mdi mdi-check-circle"></i>
            </div>
            
            <h1 class="success-title">Cadastro Enviado com Sucesso!</h1>
            
            <div class="success-message">
                <p>Obrigado! Os dados do aluno <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong> foram enviados com sucesso.</p>
                <?php if ($aluno['turma_nome']): ?>
                <p><strong>Turma:</strong> <?php echo htmlspecialchars($aluno['turma_nome']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="next-steps">
                <h4 style="color: #2c3e50; margin-bottom: 20px;">
                    <i class="mdi mdi-information text-info me-2"></i>
                    Próximos Passos
                </h4>
                
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Revisão pela Secretaria</strong><br>
                        <small class="text-muted">A secretaria revisará os dados enviados</small>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Aprovação do Cadastro</strong><br>
                        <small class="text-muted">Após a revisão, o cadastro será aprovado</small>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Matrícula Oficial</strong><br>
                        <small class="text-muted">O aluno será oficialmente matriculado</small>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Contato da Escola</strong><br>
                        <small class="text-muted">Você será contatado com informações sobre o início das aulas</small>
                    </div>
                </div>
            </div>
            
            <div class="contact-info">
                <h5 style="color: #2c3e50; margin-bottom: 15px;">
                    <i class="mdi mdi-phone text-primary me-2"></i>
                    Precisa de Ajuda?
                </h5>
                <p class="mb-2">
                    <strong>Telefone:</strong> (87) 98837-5103<br>
                    <strong>Email:</strong> rosasharom@gmail.com<br>
                    <strong>WhatsApp:</strong> (87) 98837-5103
                </p>
                <p class="text-muted mb-0">
                    <small>Horário de atendimento: Segunda a Sexta, 8h às 17h</small>
                </p>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    <i class="mdi mdi-shield-check text-success me-2"></i>
                    Seus dados estão seguros e serão utilizados apenas para fins educacionais.
                </p>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
</body>
</html>
