<?php
session_start();
include('../partials/db.php');

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'coordenador') {
    header('Location: ../../login.php');
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: professor.php');
    exit();
}

// Função para limpar e validar dados
function limparDados($dados) {
    return trim(strip_tags($dados));
}



try {
    // Coletar e limpar dados do formulário
    $dados = [
        'nome' => limparDados($_POST['nome'] ?? ''),
        'email' => limparDados($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? '',
        'disciplinas' => $_POST['disciplinas'] ?? [],
        'turmas' => $_POST['turmas'] ?? []
    ];

    // Validações básicas
    $erros = [];

    if (empty($dados['nome'])) {
        $erros[] = "Nome completo é obrigatório";
    }

    if (empty($dados['email'])) {
        $erros[] = "Email é obrigatório";
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email inválido";
    }



    if (empty($dados['senha'])) {
        $erros[] = "Senha é obrigatória";
    } elseif (strlen($dados['senha']) < 6) {
        $erros[] = "Senha deve ter pelo menos 6 caracteres";
    }

    if ($dados['senha'] !== $dados['confirmar_senha']) {
        $erros[] = "As senhas não coincidem";
    }

    if (empty($dados['disciplinas'])) {
        $erros[] = "Pelo menos uma disciplina deve ser selecionada";
    }

    if (empty($dados['turmas'])) {
        $erros[] = "Pelo menos uma turma deve ser selecionada";
    }

    // Verificar se email já existe
    if (!empty($dados['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            $erros[] = "Email já cadastrado no sistema";
        }
    }



    // Verificar se disciplinas existem
    if (!empty($dados['disciplinas'])) {
        $placeholders = str_repeat('?,', count($dados['disciplinas']) - 1) . '?';
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disciplinas WHERE id IN ($placeholders)");
        $stmt->execute($dados['disciplinas']);
        $result = $stmt->fetch();
        if ($result['total'] != count($dados['disciplinas'])) {
            $erros[] = "Uma ou mais disciplinas selecionadas não existem";
        }
    }

    // Verificar se turmas existem
    if (!empty($dados['turmas'])) {
        $placeholders = str_repeat('?,', count($dados['turmas']) - 1) . '?';
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turmas WHERE id IN ($placeholders)");
        $stmt->execute($dados['turmas']);
        $result = $stmt->fetch();
        if ($result['total'] != count($dados['turmas'])) {
            $erros[] = "Uma ou mais turmas selecionadas não existem";
        }
    }

    // Se há erros, redirecionar com mensagem
    if (!empty($erros)) {
        $_SESSION['erro_cadastro'] = implode("<br>", $erros);
        header('Location: professor.php');
        exit();
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Criptografar senha
    $senha_criptografada = password_hash($dados['senha'], PASSWORD_DEFAULT);

    // Inserir na tabela usuarios (apenas campos que existem)
    $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'professor')";
    
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute([
        $dados['nome'],
        $dados['email'],
        $senha_criptografada
    ]);

    $professor_id = $pdo->lastInsertId();

    // Inserir disciplinas do professor
    if (!empty($dados['disciplinas'])) {
        $sql_disciplinas = "INSERT INTO professores_disciplinas (professor_id, disciplina_id) VALUES (?, ?)";
        $stmt_disciplinas = $pdo->prepare($sql_disciplinas);
        
        foreach ($dados['disciplinas'] as $disciplina_id) {
            $stmt_disciplinas->execute([$professor_id, $disciplina_id]);
        }
    }

    // Inserir turmas do professor
    if (!empty($dados['turmas'])) {
        $sql_turmas = "INSERT INTO professores_turmas (professor_id, turma_id) VALUES (?, ?)";
        $stmt_turmas = $pdo->prepare($sql_turmas);
        
        foreach ($dados['turmas'] as $turma_id) {
            $stmt_turmas->execute([$professor_id, $turma_id]);
        }
    }

    // Commit da transação
    $pdo->commit();

    // Sucesso
    $_SESSION['sucesso_cadastro'] = "Professor cadastrado com sucesso!";
    header('Location: sucesso_cadastro_professor.php');
    exit();

} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro PDO ao cadastrar professor: " . $e->getMessage() . " - Código: " . $e->getCode());
    error_log("SQL State: " . $e->getCode());
    error_log("Trace: " . $e->getTraceAsString());
    $_SESSION['erro_cadastro'] = "Erro interno do sistema: " . $e->getMessage();
    header('Location: professor.php');
    exit();
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro inesperado ao cadastrar professor: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    $_SESSION['erro_cadastro'] = "Erro inesperado: " . $e->getMessage();
    header('Location: professor.php');
    exit();
}
?>
