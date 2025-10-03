<?php
/**
 * salvar_parecer.php
 *
 * Este script processa o formulário de parecer completo enviado por um professor.
 * Ele salva ou atualiza o parecer na tabela `pareceres`, que agora contém
 * todas as informações (específicas da disciplina e gerais).
 *
 * Redireciona de volta para a próxima avaliação da turma ou para a lista de turmas.
 */

session_start(); // Inicia a sessão
require_once 'partials/db.php'; // Inclui o arquivo de conexão com o banco de dados

// Garante que a requisição é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido. Este script só pode ser acessado via POST.");
}

// Verifica se o professor está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Você precisa estar logado como professor para salvar pareceres.'];
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

// --- Recupera e sanitiza os dados do formulário POST ---
$id_aluno = filter_input(INPUT_POST, 'id_aluno', FILTER_VALIDATE_INT);
$id_professor = $_SESSION['usuario_id'];
$id_disciplina = filter_input(INPUT_POST, 'id_disciplina', FILTER_VALIDATE_INT);
$periodo = filter_input(INPUT_POST, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Campos da Parte A (Avaliação Específica da Disciplina)
$disposicao_aula = filter_input(INPUT_POST, 'disposicao_aula', FILTER_SANITIZE_STRING);
$desempenho_disciplina = filter_input(INPUT_POST, 'desempenho_disciplina', FILTER_SANITIZE_STRING);
$obs_disciplina = filter_input(INPUT_POST, 'obs_disciplina', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Campos da Parte B (Percepção Geral do Aluno)
$comportamento = filter_input(INPUT_POST, 'comportamento', FILTER_SANITIZE_STRING);
$participacao_grupo = filter_input(INPUT_POST, 'participacao_grupo', FILTER_SANITIZE_STRING);
$respeito_regras = filter_input(INPUT_POST, 'respeito_regras', FILTER_SANITIZE_STRING);
$postura_atividades = filter_input(INPUT_POST, 'postura_atividades', FILTER_SANITIZE_STRING);
$postura_desafios = filter_input(INPUT_POST, 'postura_desafios', FILTER_SANITIZE_STRING);

// --- Informações para redirecionamento de volta para a avaliação da turma ---
$id_turma_redirecionamento = filter_input(INPUT_POST, 'id_turma_redirecionamento', FILTER_VALIDATE_INT);
$id_disciplina_redirecionamento = filter_input(INPUT_POST, 'id_disciplina_redirecionamento', FILTER_VALIDATE_INT);
$periodo_redirecionamento = filter_input(INPUT_POST, 'periodo_redirecionamento', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$aluno_index_atual = filter_input(INPUT_POST, 'aluno_index_atual', FILTER_VALIDATE_INT);
$total_alunos_turma = filter_input(INPUT_POST, 'total_alunos_turma', FILTER_VALIDATE_INT);

// Validação básica dos dados recebidos
if (
    !$id_aluno || !$id_professor || !$id_disciplina || empty($periodo) ||
    empty($disposicao_aula) || empty($desempenho_disciplina) ||
    empty($comportamento) || empty($participacao_grupo) ||
    empty($respeito_regras) || empty($postura_atividades) || empty($postura_desafios)
) {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Dados incompletos ou inválidos para salvar o parecer. Por favor, preencha todos os campos obrigatórios.'];
    // Redireciona de volta para o aluno atual para que o professor possa corrigir
    $redirect_url = "avaliar_alunos_na_turma.php?id_turma={$id_turma_redirecionamento}&id_disciplina={$id_disciplina_redirecionamento}&periodo=" . urlencode($periodo_redirecionamento) . "&aluno_index={$aluno_index_atual}";
    header("Location: " . $redirect_url);
    exit();
}

try {
    // Tenta inserir ou atualizar o parecer na tabela `pareceres`
    // A UNIQUE KEY `idx_parecer_unico_prof_aluno_disc_periodo` garante que o ON DUPLICATE KEY UPDATE funcione.
    $stmt = $pdo->prepare(
        "INSERT INTO pareceres (
            id_aluno, id_professor, id_disciplina, periodo,
            disposicao_aula, desempenho_disciplina, obs_disciplina,
            comportamento, participacao_grupo, respeito_regras, postura_atividades, postura_desafios
        ) VALUES (
            :id_aluno, :id_professor, :id_disciplina, :periodo,
            :disposicao_aula, :desempenho_disciplina, :obs_disciplina,
            :comportamento, :participacao_grupo, :respeito_regras, :postura_atividades, :postura_desafios
        ) ON DUPLICATE KEY UPDATE
            disposicao_aula = VALUES(disposicao_aula),
            desempenho_disciplina = VALUES(desempenho_disciplina),
            obs_disciplina = VALUES(obs_disciplina),
            comportamento = VALUES(comportamento),
            participacao_grupo = VALUES(participacao_grupo),
            respeito_regras = VALUES(respeito_regras),
            postura_atividades = VALUES(postura_atividades),
            postura_desafios = VALUES(postura_desafios),
            data_geracao = CURRENT_TIMESTAMP(), -- Atualiza a data de geração ao salvar/atualizar
            status = 'em_aberto' -- Garante que o status volte para 'em_aberto' se for atualizado após finalização
        "
    );

    $stmt->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
    $stmt->bindParam(':id_professor', $id_professor, PDO::PARAM_INT);
    $stmt->bindParam(':id_disciplina', $id_disciplina, PDO::PARAM_INT);
    $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
    $stmt->bindParam(':disposicao_aula', $disposicao_aula, PDO::PARAM_STR);
    $stmt->bindParam(':desempenho_disciplina', $desempenho_disciplina, PDO::PARAM_STR);
    $stmt->bindParam(':obs_disciplina', $obs_disciplina, PDO::PARAM_STR);
    $stmt->bindParam(':comportamento', $comportamento, PDO::PARAM_STR);
    $stmt->bindParam(':participacao_grupo', $participacao_grupo, PDO::PARAM_STR);
    $stmt->bindParam(':respeito_regras', $respeito_regras, PDO::PARAM_STR);
    $stmt->bindParam(':postura_atividades', $postura_atividades, PDO::PARAM_STR);
    $stmt->bindParam(':postura_desafios', $postura_desafios, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Parecer salvo com sucesso!'];

        // Lógica de redirecionamento para o próximo aluno
        $next_aluno_index = $aluno_index_atual + 1;
        if ($next_aluno_index < $total_alunos_turma) {
            // Redireciona para o próximo aluno na turma, mantendo turma, disciplina e período
            $redirect_url = "avaliar_alunos_na_turma.php?id_turma={$id_turma_redirecionamento}&id_disciplina={$id_disciplina_redirecionamento}&periodo=" . urlencode($periodo_redirecionamento) . "&aluno_index={$next_aluno_index}";
        } else {
            // Se não há mais alunos, redireciona para a página de seleção de turmas com uma mensagem de finalização
            $_SESSION['feedback_message']['text'] .= ' Todos os alunos desta disciplina na turma foram avaliados.';
            $redirect_url = "avaliar_turmas_professor.php";
        }
        header("Location: " . $redirect_url);
        exit();

    } else {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao salvar o parecer.'];
        // Redireciona de volta para o aluno atual
        $redirect_url = "avaliar_alunos_na_turma.php?id_turma={$id_turma_redirecionamento}&id_disciplina={$id_disciplina_redirecionamento}&periodo=" . urlencode($periodo_redirecionamento) . "&aluno_index={$aluno_index_atual}";
        header("Location: " . $redirect_url);
        exit();
    }

} catch (PDOException $e) {
    error_log("Erro PDO ao salvar parecer: " . $e->getMessage());
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro no banco de dados ao salvar parecer: ' . $e->getMessage()];
    // Redireciona de volta para o aluno atual para que o professor possa corrigir
    $redirect_url = "avaliar_alunos_na_turma.php?id_turma={$id_turma_redirecionamento}&id_disciplina={$id_disciplina_redirecionamento}&periodo=" . urlencode($periodo_redirecionamento) . "&aluno_index={$aluno_index_atual}";
    header("Location: " . $redirect_url);
    exit();
}
