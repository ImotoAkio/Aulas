<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados pessoais
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $nacionalidade = trim($_POST['nacionalidade'] ?? '');
    $naturalidade_cidade = trim($_POST['naturalidade_cidade'] ?? '');
    $naturalidade_estado = trim($_POST['naturalidade_estado'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $turma_id = $_POST['turma_id'] ?? '';
    $nis = trim($_POST['nis'] ?? '');
    
    // Endereço e contato
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $telefone1 = trim($_POST['telefone1'] ?? '');
    $telefone2 = trim($_POST['telefone2'] ?? '');
    
    // Saúde
    $tipo_sanguineo = $_POST['tipo_sanguineo'] ?? '';
    $fator_rh = $_POST['fator_rh'] ?? '';
    $alergias = trim($_POST['alergias'] ?? '');
    
    // Responsáveis
    $nome_mae = trim($_POST['nome_mae'] ?? '');
    $cpf_mae = trim($_POST['cpf_mae'] ?? '');
    $nome_pai = trim($_POST['nome_pai'] ?? '');
    $cpf_pai = trim($_POST['cpf_pai'] ?? '');
    $nome_resp_legal = trim($_POST['nome_resp_legal'] ?? '');
    $cpf_resp_legal = trim($_POST['cpf_resp_legal'] ?? '');
    $profissao_resp_legal = trim($_POST['profissao_resp_legal'] ?? '');
    $grau_parentesco_resp_legal = trim($_POST['grau_parentesco_resp_legal'] ?? '');
    $local_trabalho_resp_legal = trim($_POST['local_trabalho_resp_legal'] ?? '');
    
    // Validações obrigatórias
    if (empty($nome_completo) || empty($data_nascimento) || empty($sexo) || empty($cpf) || empty($turma_id)) {
        $_SESSION['erro_cadastro'] = 'Campos obrigatórios não preenchidos.';
        header('Location: aluno.php');
        exit();
    }
    
    // Validar CPF (formato básico)
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf_limpo) != 11) {
        $_SESSION['erro_cadastro'] = 'CPF inválido.';
        header('Location: aluno.php');
        exit();
    }
    
    // Validar turma
    if (!is_numeric($turma_id)) {
        $_SESSION['erro_cadastro'] = 'Turma inválida.';
        header('Location: aluno.php');
        exit();
    }
    
    try {
        // Verificar se CPF já existe
        $stmt = $pdo->prepare("SELECT id FROM alunos WHERE cpf = ?");
        $stmt->execute([$cpf_limpo]);
        if ($stmt->fetch()) {
            $_SESSION['erro_cadastro'] = 'Já existe um aluno cadastrado com este CPF.';
            header('Location: aluno.php');
            exit();
        }
        
        // Verificar se turma existe
        $stmt = $pdo->prepare("SELECT id FROM turmas WHERE id = ?");
        $stmt->execute([$turma_id]);
        if (!$stmt->fetch()) {
            $_SESSION['erro_cadastro'] = 'Turma não encontrada.';
            header('Location: aluno.php');
            exit();
        }
        
        // Inserir aluno
        $sql = "INSERT INTO alunos (
            nome, nome_completo, data_nascimento, sexo, nacionalidade, 
            naturalidade_cidade, naturalidade_estado, cpf, rg, turma_id, nis,
            endereco, numero, complemento, bairro, cep, cidade, estado,
            telefone1, telefone2, tipo_sanguineo, fator_rh, alergias,
            nome_mae, cpf_mae, nome_pai, cpf_pai, nome_resp_legal, cpf_resp_legal,
            profissao_resp_legal, grau_parentesco_resp_legal, local_trabalho_resp_legal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_completo, // nome (para compatibilidade)
            $nome_completo, // nome_completo
            $data_nascimento,
            $sexo,
            $nacionalidade,
            $naturalidade_cidade,
            $naturalidade_estado,
            $cpf_limpo,
            $rg,
            $turma_id,
            $nis,
            $endereco,
            $numero,
            $complemento,
            $bairro,
            $cep,
            $cidade,
            $estado,
            $telefone1,
            $telefone2,
            $tipo_sanguineo,
            $fator_rh,
            $alergias,
            $nome_mae,
            $cpf_mae,
            $nome_pai,
            $cpf_pai,
            $nome_resp_legal,
            $cpf_resp_legal,
            $profissao_resp_legal,
            $grau_parentesco_resp_legal,
            $local_trabalho_resp_legal
        ]);
        
        $_SESSION['sucesso_cadastro'] = 'Aluno cadastrado com sucesso!';
        header('Location: sucesso_cadastro.php');
        exit();
        
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar aluno: " . $e->getMessage());
        $_SESSION['erro_cadastro'] = 'Erro ao cadastrar aluno. Tente novamente.';
        header('Location: aluno.php');
        exit();
    }
} else {
    header('Location: aluno.php');
    exit();
}
?>
