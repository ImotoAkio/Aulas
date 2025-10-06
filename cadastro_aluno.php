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
                nome = ?, nome_completo = ?, cpf = ?, rg = ?, data_nascimento = ?, sexo = ?,
                nacionalidade = ?, naturalidade_cidade = ?, naturalidade_estado = ?,
                nis = ?, tipo_sanguineo = ?, fator_rh = ?,
                nome_mae = ?, cpf_mae = ?, nome_pai = ?, cpf_pai = ?,
                cep = ?, endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
                telefone1 = ?, telefone2 = ?,
                nome_resp_legal = ?, cpf_resp_legal = ?, grau_parentesco_resp_legal = ?,
                profissao_resp_legal = ?, local_trabalho_resp_legal = ?,
                alergias = ?,
                status_cadastro = 'completo', preenchido_por_responsavel = TRUE,
                dados_preenchidos_em = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nome_completo'],
            $_POST['nome_completo'],
            $_POST['cpf'],
            $_POST['rg'],
            $_POST['data_nascimento'] ?: null,
            $_POST['sexo'] ?: null,
            $_POST['nacionalidade'] ?: 'Brasileira',
            $_POST['naturalidade_cidade'],
            $_POST['naturalidade_estado'],
            $_POST['nis'] ?: null,
            $_POST['tipo_sanguineo'] ?: null,
            $_POST['fator_rh'] ?: null,
            $_POST['nome_mae'] ?: null,
            $_POST['cpf_mae'] ?: null,
            $_POST['nome_pai'] ?: null,
            $_POST['cpf_pai'] ?: null,
            $_POST['cep'],
            $_POST['endereco'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cidade'],
            $_POST['estado'],
            $_POST['telefone1'],
            $_POST['telefone2'],
            $_POST['nome_resp_legal'],
            $_POST['cpf_resp_legal'],
            $_POST['grau_parentesco_resp_legal'],
            $_POST['profissao_resp_legal'] ?: null,
            $_POST['local_trabalho_resp_legal'] ?: null,
            $_POST['alergias'] ?: null,
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
                                <label for="naturalidade_cidade" class="form-label">Cidade de Nascimento</label>
                                <input type="text" class="form-control" id="naturalidade_cidade" name="naturalidade_cidade" 
                                       value="<?php echo htmlspecialchars($aluno['naturalidade_cidade'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="naturalidade_estado" class="form-label">UF Nascimento</label>
                                <input type="text" class="form-control" id="naturalidade_estado" name="naturalidade_estado" 
                                       value="<?php echo htmlspecialchars($aluno['naturalidade_estado'] ?? ''); ?>" 
                                       maxlength="2" placeholder="Ex: PE">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nis" class="form-label">NIS</label>
                                <input type="text" class="form-control" id="nis" name="nis" 
                                       value="<?php echo htmlspecialchars($aluno['nis'] ?? ''); ?>" 
                                       placeholder="Número de Identificação Social">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>
                                <select class="form-control" id="tipo_sanguineo" name="tipo_sanguineo">
                                    <option value="">Selecione</option>
                                    <option value="A" <?php echo ($aluno['tipo_sanguineo'] ?? '') === 'A' ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo ($aluno['tipo_sanguineo'] ?? '') === 'B' ? 'selected' : ''; ?>>B</option>
                                    <option value="AB" <?php echo ($aluno['tipo_sanguineo'] ?? '') === 'AB' ? 'selected' : ''; ?>>AB</option>
                                    <option value="O" <?php echo ($aluno['tipo_sanguineo'] ?? '') === 'O' ? 'selected' : ''; ?>>O</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="fator_rh" class="form-label">Fator RH</label>
                                <select class="form-control" id="fator_rh" name="fator_rh">
                                    <option value="">Selecione</option>
                                    <option value="+" <?php echo ($aluno['fator_rh'] ?? '') === '+' ? 'selected' : ''; ?>>Positivo (+)</option>
                                    <option value="-" <?php echo ($aluno['fator_rh'] ?? '') === '-' ? 'selected' : ''; ?>>Negativo (-)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dados dos Pais -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="mdi mdi-account-group text-info me-2"></i>
                            Dados dos Pais
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_mae" class="form-label">Nome da Mãe</label>
                                <input type="text" class="form-control" id="nome_mae" name="nome_mae" 
                                       value="<?php echo htmlspecialchars($aluno['nome_mae'] ?? ''); ?>" 
                                       placeholder="Nome completo da mãe">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cpf_mae" class="form-label">CPF da Mãe</label>
                                <input type="text" class="form-control" id="cpf_mae" name="cpf_mae" 
                                       value="<?php echo htmlspecialchars($aluno['cpf_mae'] ?? ''); ?>" 
                                       placeholder="000.000.000-00">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_pai" class="form-label">Nome do Pai</label>
                                <input type="text" class="form-control" id="nome_pai" name="nome_pai" 
                                       value="<?php echo htmlspecialchars($aluno['nome_pai'] ?? ''); ?>" 
                                       placeholder="Nome completo do pai">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cpf_pai" class="form-label">CPF do Pai</label>
                                <input type="text" class="form-control" id="cpf_pai" name="cpf_pai" 
                                       value="<?php echo htmlspecialchars($aluno['cpf_pai'] ?? ''); ?>" 
                                       placeholder="000.000.000-00">
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
                                <label for="telefone1" class="form-label">Telefone Principal</label>
                                <input type="text" class="form-control" id="telefone1" name="telefone1" 
                                       value="<?php echo htmlspecialchars($aluno['telefone1'] ?? ''); ?>" 
                                       placeholder="(87) 99999-9999">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone2" class="form-label">Telefone Secundário</label>
                                <input type="text" class="form-control" id="telefone2" name="telefone2" 
                                       value="<?php echo htmlspecialchars($aluno['telefone2'] ?? ''); ?>" 
                                       placeholder="(87) 99999-9999">
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
                                <label for="nome_resp_legal" class="form-label">Nome do Responsável Legal</label>
                                <input type="text" class="form-control" id="nome_resp_legal" name="nome_resp_legal" 
                                       value="<?php echo htmlspecialchars($aluno['nome_resp_legal'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="grau_parentesco_resp_legal" class="form-label">Parentesco</label>
                                <select class="form-control" id="grau_parentesco_resp_legal" name="grau_parentesco_resp_legal">
                                    <option value="">Selecione</option>
                                    <option value="Pai" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Pai' ? 'selected' : ''; ?>>Pai</option>
                                    <option value="Mãe" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Mãe' ? 'selected' : ''; ?>>Mãe</option>
                                    <option value="Avô" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Avô' ? 'selected' : ''; ?>>Avô</option>
                                    <option value="Avó" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Avó' ? 'selected' : ''; ?>>Avó</option>
                                    <option value="Tio(a)" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Tio(a)' ? 'selected' : ''; ?>>Tio(a)</option>
                                    <option value="Outro" <?php echo ($aluno['grau_parentesco_resp_legal'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cpf_resp_legal" class="form-label">CPF do Responsável</label>
                                <input type="text" class="form-control" id="cpf_resp_legal" name="cpf_resp_legal" 
                                       value="<?php echo htmlspecialchars($aluno['cpf_resp_legal'] ?? ''); ?>" 
                                       placeholder="000.000.000-00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="profissao_resp_legal" class="form-label">Profissão do Responsável</label>
                                <input type="text" class="form-control" id="profissao_resp_legal" name="profissao_resp_legal" 
                                       value="<?php echo htmlspecialchars($aluno['profissao_resp_legal'] ?? ''); ?>" 
                                       placeholder="Ex: Professor, Médico, Comerciante...">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="local_trabalho_resp_legal" class="form-label">Local de Trabalho</label>
                                <input type="text" class="form-control" id="local_trabalho_resp_legal" name="local_trabalho_resp_legal" 
                                       value="<?php echo htmlspecialchars($aluno['local_trabalho_resp_legal'] ?? ''); ?>" 
                                       placeholder="Nome da empresa ou local de trabalho">
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
