<?php
session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    header('Location: ../../login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Buscar dados do aluno
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch();
    
    if (!$aluno) {
        die("Aluno não encontrado.");
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do aluno: " . $e->getMessage());
    die("Erro interno do sistema.");
}

// Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Dados básicos
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $sexo = $_POST['sexo'] ?? '';
        $nacionalidade = trim($_POST['nacionalidade'] ?? '');
        $naturalidade_cidade = trim($_POST['naturalidade_cidade'] ?? '');
        $naturalidade_estado = trim($_POST['naturalidade_estado'] ?? '');
        
        // Dados de contato
        $endereco = trim($_POST['endereco'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cep = trim($_POST['cep'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $telefone1 = trim($_POST['telefone1'] ?? '');
        $telefone2 = trim($_POST['telefone2'] ?? '');
        
        // Dados de saúde
        $tipo_sanguineo = $_POST['tipo_sanguineo'] ?? '';
        $fator_rh = $_POST['fator_rh'] ?? '';
        $alergias = trim($_POST['alergias'] ?? '');
        
        // Validar dados obrigatórios
        if (empty($nome_completo)) {
            throw new Exception("Nome completo é obrigatório.");
        }
        
        if (empty($data_nascimento)) {
            throw new Exception("Data de nascimento é obrigatória.");
        }
        
        // Atualizar dados do aluno
        $sql = "UPDATE alunos SET 
                nome_completo = ?, data_nascimento = ?, sexo = ?, nacionalidade = ?,
                naturalidade_cidade = ?, naturalidade_estado = ?, endereco = ?,
                numero = ?, complemento = ?, bairro = ?, cep = ?, cidade = ?,
                estado = ?, telefone1 = ?, telefone2 = ?, tipo_sanguineo = ?,
                fator_rh = ?, alergias = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_completo, $data_nascimento, $sexo, $nacionalidade,
            $naturalidade_cidade, $naturalidade_estado, $endereco,
            $numero, $complemento, $bairro, $cep, $cidade,
            $estado, $telefone1, $telefone2, $tipo_sanguineo,
            $fator_rh, $alergias, $aluno_id
        ]);
        
        $pdo->commit();
        $mensagem = "Dados atualizados com sucesso!";
        $tipo_mensagem = "success";
        
        // Atualizar dados na sessão
        $_SESSION['usuario_nome'] = $nome_completo;
        
        // Recarregar dados do aluno
        $stmt = $pdo->prepare("
            SELECT a.*, t.nome as turma_nome, t.ano_letivo
            FROM alunos a 
            LEFT JOIN turmas t ON a.turma_id = t.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$aluno_id]);
        $aluno = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao atualizar dados: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Meus Dados - Rosa de Sharom</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="../assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="../assets/vendors/font-awesome/css/font-awesome.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="../assets/vendors/select2/select2.min.css">
  <link rel="stylesheet" href="../assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- End layout styles -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.php -->
    <?php include('partials/_navbar.php'); ?>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_sidebar.php -->
      <?php include('partials/_sidebar.php'); ?>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">
                    <i class="mdi mdi-account-edit"></i> Meus Dados Pessoais
                  </h4>
                  
                  <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                      <i class="mdi mdi-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                      <?= htmlspecialchars($mensagem) ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>
                  
                  <form method="POST" class="forms-sample">
                    <!-- Informações Básicas -->
                    <div class="row">
                      <div class="col-md-12">
                        <h5 class="mb-3 text-primary">
                          <i class="mdi mdi-information"></i> Informações Básicas
                        </h5>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Nome Completo *</label>
                          <input type="text" name="nome_completo" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['nome_completo'] ?: $aluno['nome']) ?>" required>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Data de Nascimento *</label>
                          <input type="date" name="data_nascimento" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['data_nascimento'] ?? '') ?>" required>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Sexo</label>
                          <select name="sexo" class="form-control">
                            <option value="">Selecione</option>
                            <option value="M" <?= ($aluno['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($aluno['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Nacionalidade</label>
                          <input type="text" name="nacionalidade" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['nacionalidade'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Cidade de Nascimento</label>
                          <input type="text" name="naturalidade_cidade" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['naturalidade_cidade'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Estado de Nascimento</label>
                          <input type="text" name="naturalidade_estado" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['naturalidade_estado'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                    
                    <!-- Informações de Contato -->
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <h5 class="mb-3 text-primary">
                          <i class="mdi mdi-phone"></i> Informações de Contato
                        </h5>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Endereço</label>
                          <input type="text" name="endereco" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['endereco'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Número</label>
                          <input type="text" name="numero" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['numero'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Complemento</label>
                          <input type="text" name="complemento" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['complemento'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Bairro</label>
                          <input type="text" name="bairro" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['bairro'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>CEP</label>
                          <input type="text" name="cep" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['cep'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Cidade</label>
                          <input type="text" name="cidade" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['cidade'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Estado</label>
                          <input type="text" name="estado" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['estado'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Telefone 1</label>
                          <input type="text" name="telefone1" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['telefone1'] ?? '') ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Telefone 2</label>
                          <input type="text" name="telefone2" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['telefone2'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                    
                    <!-- Informações de Saúde -->
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <h5 class="mb-3 text-primary">
                          <i class="mdi mdi-heart-pulse"></i> Informações de Saúde
                        </h5>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Tipo Sanguíneo</label>
                          <select name="tipo_sanguineo" class="form-control">
                            <option value="">Selecione</option>
                            <option value="A" <?= ($aluno['tipo_sanguineo'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= ($aluno['tipo_sanguineo'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                            <option value="AB" <?= ($aluno['tipo_sanguineo'] ?? '') === 'AB' ? 'selected' : '' ?>>AB</option>
                            <option value="O" <?= ($aluno['tipo_sanguineo'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Fator RH</label>
                          <select name="fator_rh" class="form-control">
                            <option value="">Selecione</option>
                            <option value="+" <?= ($aluno['fator_rh'] ?? '') === '+' ? 'selected' : '' ?>>Positivo (+)</option>
                            <option value="-" <?= ($aluno['fator_rh'] ?? '') === '-' ? 'selected' : '' ?>>Negativo (-)</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label>Alergias</label>
                          <textarea name="alergias" class="form-control" rows="2" 
                                    placeholder="Descreva suas alergias, se houver"><?= htmlspecialchars($aluno['alergias'] ?? '') ?></textarea>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Informações Escolares (Somente Leitura) -->
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <h5 class="mb-3 text-info">
                          <i class="mdi mdi-school"></i> Informações Escolares
                        </h5>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>CPF</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['cpf'] ?? '') ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>RG</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['rg'] ?? '') ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>NIS</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['nis'] ?? '') ?>" readonly>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Turma</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['turma_nome'] ?? '') ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Ano Letivo</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['ano_letivo'] ?? '') ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Data de Cadastro</label>
                          <input type="text" class="form-control" 
                                 value="<?= htmlspecialchars($aluno['data_cadastro'] ?? '') ?>" readonly>
                        </div>
                      </div>
                    </div>
                    
                    <div class="row mt-4">
                      <div class="col-md-12">
                        <button type="submit" class="btn btn-gradient-primary mr-2">
                          <i class="mdi mdi-content-save"></i> Salvar Alterações
                        </button>
                        <a href="index.php" class="btn btn-light">
                          <i class="mdi mdi-arrow-left"></i> Voltar
                        </a>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.php -->
        <?php include('../secretaria/partials/_footer.php'); ?>
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- plugins:js -->
  <script src="../assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="../assets/vendors/select2/select2.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="../assets/js/off-canvas.js"></script>
  <script src="../assets/js/misc.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page -->
  <script src="../assets/js/file-upload.js"></script>
  <!-- End custom js for this page -->
  
  <script>
    // Máscaras para os campos
    $(document).ready(function() {
      // Máscara para CEP
      $('input[name="cep"]').mask('00000-000');
      
      // Máscara para telefones
      $('input[name="telefone1"], input[name="telefone2"]').mask('(00) 00000-0000');
      
      // Validação de CPF (se necessário)
      $('input[name="cpf"]').mask('000.000.000-00');
      
      // Validação de RG
      $('input[name="rg"]').mask('00.000.000-0');
    });
  </script>
</body>

</html>
