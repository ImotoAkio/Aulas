<?php
require_once 'config/database.php';

$pdo = getConnection();
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die("Código de acesso inválido.");
}

// Buscar aluno pelo código
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, pc.link_expiracao
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        WHERE a.codigo_pre_cadastro = ? AND a.status_cadastro = 'pre_cadastro'
    ");
    $stmt->execute([$codigo]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        die("Link inválido ou expirado.");
    }
    
    // Verificar se o link não expirou
    if ($aluno['link_expiracao'] && strtotime($aluno['link_expiracao']) < time()) {
        die("Este link expirou. Entre em contato com a secretaria.");
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar aluno: " . $e->getMessage());
    die("Erro interno. Tente novamente mais tarde.");
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Atualizar dados do aluno
        $stmt = $pdo->prepare("
            UPDATE alunos SET 
                nome = ?, cpf = ?, rg = ?, data_nascimento = ?, sexo = ?,
                nacionalidade = ?, naturalidade_cidade = ?, cep = ?, endereco = ?,
                numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
                telefone1 = ?, telefone2 = ?,
                nome_resp_legal = ?, cpf_resp_legal = ?, grau_parentesco_resp_legal = ?,
                alergias = ?,
                status_cadastro = 'completo', preenchido_por_responsavel = TRUE,
                dados_preenchidos_em = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nome_completo'],
            $_POST['cpf'],
            $_POST['rg'],
            $_POST['data_nascimento'] ?: null,
            $_POST['sexo'] ?: null,
            $_POST['nacionalidade'] ?: 'Brasileira',
            $_POST['naturalidade'],
            $_POST['cep'],
            $_POST['endereco'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cidade'],
            $_POST['estado'],
            $_POST['telefone_principal'],
            $_POST['telefone_secundario'],
            $_POST['nome_responsavel'],
            $_POST['cpf_responsavel'],
            $_POST['parentesco'],
            $_POST['alergias'],
            $aluno['id']
        ]);
        
        // Atualizar status no controle
        $stmt = $pdo->prepare("UPDATE pre_cadastros_controle SET status = 'preenchido', preenchido_em = NOW() WHERE aluno_id = ?");
        $stmt->execute([$aluno['id']]);
        
        $pdo->commit();
        
        // Redirecionar para página de sucesso
        redirectTo('cadastro_sucesso.php?codigo=' . $codigo);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro = "Erro ao salvar dados: " . $e->getMessage();
        error_log("Erro ao salvar dados do aluno: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro do Aluno - Educandário Rosa de Sharom</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/mdi/css/materialdesignicons.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/vendors/css/vendor.bundle.base.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/style.css'); ?>">
    <style>
        .form-section {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .required {
            color: #e74c3c;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-submit {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 25px;
        }
        .btn-submit:hover {
            background: linear-gradient(45deg, #2980b9, #1f618d);
            transform: translateY(-2px);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Cabeçalho -->
                <div class="text-center mb-5">
                    <h1 class="text-white mb-3">
                        <i class="mdi mdi-school"></i>
                        Educandário Rosa de Sharom
                    </h1>
                    <h2 class="text-white mb-4">Cadastro do Aluno</h2>
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i>
                        <strong>Aluno:</strong> <?php echo htmlspecialchars($aluno['nome']); ?>
                        <?php if ($aluno['turma_nome']): ?>
                        | <strong>Turma:</strong> <?php echo htmlspecialchars($aluno['turma_nome']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <i class="mdi mdi-alert-circle"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="cadastro_aluno.php?codigo=<?php echo htmlspecialchars($codigo); ?>">
                    
                    <!-- Dados Pessoais -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-account text-primary me-2"></i>
                            Dados Pessoais
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="nome_completo" class="form-label">Nome Completo <span class="required">*</span></label>
                                <input type="text" class="form-control" id="nome_completo" name="nome_completo" 
                                       value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" 
                                       value="<?php echo htmlspecialchars($aluno['cpf'] ?? ''); ?>" 
                                       placeholder="000.000.000-00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <input type="text" class="form-control" id="rg" name="rg" 
                                       value="<?php echo htmlspecialchars($aluno['rg'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                       value="<?php echo htmlspecialchars($aluno['data_nascimento'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="sexo" class="form-label">Sexo</label>
                                <select class="form-control" id="sexo" name="sexo">
                                    <option value="">Selecione</option>
                                    <option value="M" <?php echo ($aluno['sexo'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo ($aluno['sexo'] ?? '') === 'F' ? 'selected' : ''; ?>>Feminino</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nacionalidade" class="form-label">Nacionalidade</label>
                                <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" 
                                       value="<?php echo htmlspecialchars($aluno['nacionalidade'] ?? 'Brasileira'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="naturalidade" class="form-label">Naturalidade</label>
                                <input type="text" class="form-control" id="naturalidade" name="naturalidade" 
                                       value="<?php echo htmlspecialchars($aluno['naturalidade'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-home text-success me-2"></i>
                            Endereço
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" 
                                       value="<?php echo htmlspecialchars($aluno['cep'] ?? ''); ?>" 
                                       placeholder="00000-000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endereco" class="form-label">Endereço</label>
                                <input type="text" class="form-control" id="endereco" name="endereco" 
                                       value="<?php echo htmlspecialchars($aluno['endereco'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero" 
                                       value="<?php echo htmlspecialchars($aluno['numero'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complemento" name="complemento" 
                                       value="<?php echo htmlspecialchars($aluno['complemento'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" 
                                       value="<?php echo htmlspecialchars($aluno['bairro'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       value="<?php echo htmlspecialchars($aluno['cidade'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="">Selecione</option>
                                    <option value="PE" <?php echo ($aluno['estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="BA" <?php echo ($aluno['estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                                    <!-- Adicionar outros estados conforme necessário -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-phone text-warning me-2"></i>
                            Informações de Contato
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone_principal" class="form-label">Telefone Principal</label>
                                <input type="text" class="form-control" id="telefone_principal" name="telefone_principal" 
                                       value="<?php echo htmlspecialchars($aluno['telefone_principal'] ?? ''); ?>" 
                                       placeholder="(87) 99999-9999">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone_secundario" class="form-label">Telefone Secundário</label>
                                <input type="text" class="form-control" id="telefone_secundario" name="telefone_secundario" 
                                       value="<?php echo htmlspecialchars($aluno['telefone_secundario'] ?? ''); ?>" 
                                       placeholder="(87) 99999-9999">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($aluno['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Responsável -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-account-group text-info me-2"></i>
                            Dados do Responsável
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_responsavel" class="form-label">Nome do Responsável</label>
                                <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel" 
                                       value="<?php echo htmlspecialchars($aluno['nome_responsavel'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="parentesco" class="form-label">Parentesco</label>
                                <select class="form-control" id="parentesco" name="parentesco">
                                    <option value="">Selecione</option>
                                    <option value="Pai" <?php echo ($aluno['parentesco'] ?? '') === 'Pai' ? 'selected' : ''; ?>>Pai</option>
                                    <option value="Mãe" <?php echo ($aluno['parentesco'] ?? '') === 'Mãe' ? 'selected' : ''; ?>>Mãe</option>
                                    <option value="Avô" <?php echo ($aluno['parentesco'] ?? '') === 'Avô' ? 'selected' : ''; ?>>Avô</option>
                                    <option value="Avó" <?php echo ($aluno['parentesco'] ?? '') === 'Avó' ? 'selected' : ''; ?>>Avó</option>
                                    <option value="Tio(a)" <?php echo ($aluno['parentesco'] ?? '') === 'Tio(a)' ? 'selected' : ''; ?>>Tio(a)</option>
                                    <option value="Outro" <?php echo ($aluno['parentesco'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cpf_responsavel" class="form-label">CPF do Responsável</label>
                                <input type="text" class="form-control" id="cpf_responsavel" name="cpf_responsavel" 
                                       value="<?php echo htmlspecialchars($aluno['cpf_responsavel'] ?? ''); ?>" 
                                       placeholder="000.000.000-00">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="rg_responsavel" class="form-label">RG do Responsável</label>
                                <input type="text" class="form-control" id="rg_responsavel" name="rg_responsavel" 
                                       value="<?php echo htmlspecialchars($aluno['rg_responsavel'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="telefone_responsavel" class="form-label">Telefone do Responsável</label>
                                <input type="text" class="form-control" id="telefone_responsavel" name="telefone_responsavel" 
                                       value="<?php echo htmlspecialchars($aluno['telefone_responsavel'] ?? ''); ?>" 
                                       placeholder="(87) 99999-9999">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email_responsavel" class="form-label">Email do Responsável</label>
                                <input type="email" class="form-control" id="email_responsavel" name="email_responsavel" 
                                       value="<?php echo htmlspecialchars($aluno['email_responsavel'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Informações Médicas -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-medical-bag text-danger me-2"></i>
                            Informações Médicas
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="alergias" class="form-label">Alergias</label>
                                <textarea class="form-control" id="alergias" name="alergias" rows="3" 
                                          placeholder="Descreva qualquer alergia conhecida..."><?php echo htmlspecialchars($aluno['alergias'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="medicamentos" class="form-label">Medicamentos em Uso</label>
                                <textarea class="form-control" id="medicamentos" name="medicamentos" rows="3" 
                                          placeholder="Liste medicamentos que o aluno utiliza regularmente..."><?php echo htmlspecialchars($aluno['medicamentos'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="observacoes_medicas" class="form-label">Observações Médicas</label>
                                <textarea class="form-control" id="observacoes_medicas" name="observacoes_medicas" rows="3" 
                                          placeholder="Outras informações médicas importantes..."><?php echo htmlspecialchars($aluno['observacoes_medicas'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Envio -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit text-white">
                            <i class="mdi mdi-content-save"></i> Enviar Cadastro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl('assets/vendors/js/vendor.bundle.base.js'); ?>"></script>
    <script>
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });

        document.getElementById('cpf_responsavel').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });

        // Máscara para telefone
        function maskPhone(input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            });
        }

        maskPhone(document.getElementById('telefone_principal'));
        maskPhone(document.getElementById('telefone_secundario'));
        maskPhone(document.getElementById('telefone_responsavel'));

        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>
