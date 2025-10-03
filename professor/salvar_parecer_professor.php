<?php
/**
 * professor/salvar_parecer_professor.php
 *
 * Este script processa o formulário de parecer completo enviado por um professor.
 * Ele salva ou atualiza o parecer na tabela `pareceres`, que agora contém
 * todas as informações de avaliação geral do aluno pelo professor.
 *
 * Redireciona de volta para a lista de pareceres do professor.
 *
 * Utiliza a estrutura de template existente com partials.
 */

session_start(); // Inicia a sessão
require_once 'partials/db.php'; // Inclui o arquivo de conexão com o banco de dados

// Garante que a requisição é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido. Este script só pode ser acessado via POST.");
}

// Verifica se o usuário está logado e se é um professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'professor') {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Você precisa estar logado como professor para salvar pareceres.'];
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php'); // Redireciona para a página de login
    exit();
}

// --- Recupera e sanitiza o ID do parecer que está sendo salvo/atualizado ---
$id_parecer_url = filter_input(INPUT_POST, 'id_parecer', FILTER_VALIDATE_INT);

// Validação do parâmetro essencial
if (!$id_parecer_url) {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'ID do parecer não especificado para salvar.'];
    header('Location: parecer.php'); // Redireciona de volta para a lista de pareceres
    exit();
}

// --- Recupera e sanitiza todos os dados do formulário ---
$professor_id = $_SESSION['usuario_id']; // ID do professor logado

// Campos da Avaliação Geral
$disposicao_aula = filter_input(INPUT_POST, 'disposicao_aula', FILTER_SANITIZE_STRING);
$desempenho_geral = filter_input(INPUT_POST, 'desempenho_geral', FILTER_SANITIZE_STRING); // NOVO CAMPO
$obs_geral_professor = filter_input(INPUT_POST, 'obs_geral_professor', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // NOVO CAMPO
$comportamento = filter_input(INPUT_POST, 'comportamento', FILTER_SANITIZE_STRING);
$participacao_grupo = filter_input(INPUT_POST, 'participacao_grupo', FILTER_SANITIZE_STRING);
$respeito_regras = filter_input(INPUT_POST, 'respeito_regras', FILTER_SANITIZE_STRING);
$postura_atividades = filter_input(INPUT_POST, 'postura_atividades', FILTER_SANITIZE_STRING);
$postura_desafios = filter_input(INPUT_POST, 'postura_desafios', FILTER_SANITIZE_STRING);


// Validação básica dos campos obrigatórios
if (
    empty($disposicao_aula) || empty($desempenho_geral) || empty($obs_geral_professor) ||
    empty($comportamento) || empty($participacao_grupo) ||
    empty($respeito_regras) || empty($postura_atividades) || empty($postura_desafios)
) {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Dados incompletos ou inválidos. Por favor, preencha todos os campos obrigatórios.'];
    header('Location: avaliar_aluno.php?id_parecer=' . $id_parecer_url); // Redireciona de volta ao parecer atual
    exit();
}

try {
    // 1. Busca os dados existentes do parecer para validação e para obter o status atual
    $stmt_fetch_current_parecer = $pdo->prepare("
        SELECT status
        FROM pareceres
        WHERE id = :id_parecer AND id_professor_designado = :professor_id
    ");
    $stmt_fetch_current_parecer->bindParam(':id_parecer', $id_parecer_url, PDO::PARAM_INT);
    $stmt_fetch_current_parecer->bindParam(':professor_id', $professor_id, PDO::PARAM_INT);
    $stmt_fetch_current_parecer->execute();
    $current_parecer_db = $stmt_fetch_current_parecer->fetch(PDO::FETCH_ASSOC);

    if (!$current_parecer_db) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Parecer não encontrado ou você não tem permissão para editá-lo.'];
        header('Location: parecer.php');
        exit();
    }

    // Se o parecer já foi finalizado pelo coordenador, não permite alteração
    if ($current_parecer_db['status'] == 'finalizado_coordenador') {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Este parecer já foi finalizado pelo coordenador e não pode ser alterado.'];
        header('Location: avaliar_aluno.php?id_parecer=' . $id_parecer_url);
        exit();
    }
    
    // Define o novo status após o professor salvar.
    // Se o parecer estava 'pendente_professor', passa para 'finalizado_professor'.
    // Se já estava 'finalizado_professor' ou 'pendente_coordenador', mantém o status atual.
    $new_status = 'finalizado_professor';
    if ($current_parecer_db['status'] == 'pendente_coordenador') {
        $new_status = 'pendente_coordenador'; // Mantém o status de pendente para coordenador
    }


    // Atualiza o parecer na tabela `pareceres` com os dados gerais do professor
    // Os campos `desempenho_disciplina` e `obs_disciplina` foram renomeados para `desempenho_geral` e `obs_geral_professor`
    $stmt_update_parecer = $pdo->prepare(
        "UPDATE pareceres SET
            disposicao_aula = :disposicao_aula,
            desempenho_geral = :desempenho_geral, -- Campo renomeado
            obs_geral_professor = :obs_geral_professor, -- Campo renomeado
            comportamento = :comportamento,
            participacao_grupo = :participacao_grupo,
            respeito_regras = :respeito_regras,
            postura_atividades = :postura_atividades,
            postura_desafios = :postura_desafios,
            status = :new_status, -- Atualiza o status
            data_criacao = CURRENT_TIMESTAMP() -- Atualiza a data de modificação
        WHERE 
            id = :id_parecer_url 
            AND id_professor_designado = :professor_id_designado -- Garante que apenas o professor designado possa atualizar
        "
    );

    $stmt_update_parecer->bindParam(':disposicao_aula', $disposicao_aula, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':desempenho_geral', $desempenho_geral, PDO::PARAM_STR); // Binda novo campo
    $stmt_update_parecer->bindParam(':obs_geral_professor', $obs_geral_professor, PDO::PARAM_STR); // Binda novo campo
    $stmt_update_parecer->bindParam(':comportamento', $comportamento, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':participacao_grupo', $participacao_grupo, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':respeito_regras', $respeito_regras, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':postura_atividades', $postura_atividades, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':postura_desafios', $postura_desafios, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':new_status', $new_status, PDO::PARAM_STR);
    $stmt_update_parecer->bindParam(':id_parecer_url', $id_parecer_url, PDO::PARAM_INT);
    $stmt_update_parecer->bindParam(':professor_id_designado', $professor_id, PDO::PARAM_INT); // Usa o ID do professor logado

    if ($stmt_update_parecer->execute()) {
        $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Parecer salvo com sucesso!'];
        header('Location: parecer.php'); // Redireciona de volta para a lista de pareceres do professor
        exit();
    } else {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao salvar o parecer.'];
        header('Location: avaliar_aluno.php?id_parecer=' . $id_parecer_url); // Redireciona de volta ao parecer atual
        exit();
    }

} catch (PDOException $e) {
    error_log("Erro PDO ao salvar parecer: " . $e->getMessage());
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro no banco de dados ao salvar parecer: ' . $e->getMessage()];
    header('Location: avaliar_aluno.php?id_parecer=' . $id_parecer_url); // Redireciona de volta ao parecer atual
    exit();
}
