<?php
/**
 * secretaria/ver_parecer.php
 *
 * Esta página permite que um usuário com perfil de 'coordenador' ou 'secretaria'
 * visualize os pareceres de um aluno, consolide os votos gerais dos professores e finalize o parecer global.
 * As seções de intervenções e resultado final agora utilizam opções pré-definidas.
 *
 * Parâmetros GET esperados:
 * - id_aluno: ID do aluno para visualizar os pareceres.
 * - periodo: Período de avaliação.
 *
 * Utiliza a estrutura de template existente com partials.
 */

session_start(); // Inicia a sessão - DEVE SER A PRIMEIRA COSA NO ARQUIVO
require_once 'partials/db.php'; // Inclui o arquivo de conexão com o banco de dados

// Verifica se o usuário está logado e se é um coordenador ou secretaria
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] != 'coordenador' && $_SESSION['tipo'] != 'secretaria')) {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php'); // Redireciona para a página de login se não for coordenador ou secretaria
    exit();
}

$id_aluno = filter_input(INPUT_GET, 'id_aluno', FILTER_VALIDATE_INT);
$periodo = filter_input(INPUT_GET, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Valida os parâmetros GET
if (!$id_aluno || empty($periodo)) {
    die("Parâmetros 'id_aluno' ou 'periodo' ausentes na URL.");
}

/**
 * Função para apurar o vencedor de um voto ENUM.
 * Retorna a opção mais votada ou "EMPATE: opção1, opção2" se houver empate.
 * @param array $votos_campo Array associativo com as opções e suas contagens de votos.
 * @return string A opção vencedora ou a string de empate.
 */
function getVencedor($votos_campo)
{
    if (empty($votos_campo) || array_sum($votos_campo) === 0) {
        return "N/A (Sem votos)";
    }
    arsort($votos_campo); // Ordena do maior para o menor

    $max_votos = reset($votos_campo); // Pega a contagem máxima de votos
    $vencedores = [];
    foreach ($votos_campo as $opcao => $contagem) {
        if ($contagem === $max_votos) {
            $vencedores[] = $opcao;
        }
    }

    if (count($vencedores) > 1) {
        // Se houver empate, retorna uma string indicando as opções empatadas
        return "EMPATE: " . implode(", ", $vencedores);
    }
    // Retorna a única opção vencedora
    return reset($votos_campo); // Retorna a única opção vencedora
}

// Opções pré-definidas para Intervenções Pedagógicas e Resultado Final
$intervencoes_options = [
    '' => '-- Selecione uma intervenção --',
    'fortalecer_habilidades_cognitivas' => 'Fortalecer Habilidades Cognitivas: Recomenda-se a implementação de atividades lúdicas e desafiadoras que visem ao desenvolvimento do raciocínio lógico-matemático e da capacidade de leitura e interpretação de textos. O uso de jogos educativos e projetos em grupo pode estimular a curiosidade e o engajamento do aluno. Sugere-se acompanhamento individualizado em horários extraclasse para reforço nos tópicos de maior dificuldade.',
    'desenvolver_autonomia_colaboracao' => 'Desenvolver Autonomia e Colaboração: É fundamental criar oportunidades para que o aluno participe ativamente das decisões em sala de aula e em projetos coletivos. Incentivar a auto-organização, a resolução de problemas em grupo e a comunicação assertiva. Reuniões periódicas com a família podem reforçar a importância da participação e responsabilidade no ambiente escolar.',
    'apoio_socioemocional_comportamental' => 'Apoio Socioemocional e Comportamental: Sugere-se o acompanhamento por profissionais de apoio pedagógico para trabalhar aspectos socioemocionais. É importante reforçar regras de convivência, promover o diálogo e oferecer um ambiente seguro para expressão de sentimentos. Oportunidades para o aluno desenvolver empatia e resiliência em situações de desafio devem ser priorizadas.'
];

$resultado_final_options = [
    '' => '-- Selecione um parecer conclusivo --',
    'desempenho_satisfatorio' => 'Aluno com Desempenho Satisfatório: O aluno demonstra desempenho satisfatório e boa adaptação ao ambiente escolar. Apresenta comportamento colaborativo e participa ativamente das atividades propostas. Recomenda-se continuar estimulando seu engajamento e curiosidade, oferecendo desafios adequados ao seu desenvolvimento.',
    'necessita_acompanhamento' => 'Aluno Necessita de Acompanhamento: O aluno necessita de acompanhamento mais próximo em algumas áreas. Há pontos a serem desenvolvidos no desempenho acadêmico e na postura diante de desafios. A colaboração entre família e escola é fundamental para o sucesso das intervenções propostas.',
    'progresso_continuo_potencial' => 'Progresso Contínuo e Potencial: Percebe-se um progresso contínuo no desenvolvimento do aluno. Com maior segurança nas atividades e resiliência diante de frustrações, ele demonstra grande potencial. É essencial manter o estímulo e as estratégias de apoio para que continue avançando em seu percurso de aprendizagem.'
];


// Processar a atualização e finalização do parecer se a requisição for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_parecer_global'])) {
    // Apenas coordenadores/secretaria podem finalizar o parecer
    if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] != 'coordenador' && $_SESSION['tipo'] != 'secretaria')) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Acesso negado. Apenas coordenadores ou secretaria podem finalizar pareceres.'];
        header("Location: ver_parecer.php?id_aluno={$id_aluno}&periodo=" . urlencode($periodo));
        exit();
    }

    // Recebe a CHAVE selecionada do dropdown e mapeia para o TEXTO completo
    $intervencoes_key = filter_input(INPUT_POST, 'intervencoes_option', FILTER_SANITIZE_STRING);
    $resultado_final_key = filter_input(INPUT_POST, 'resultado_final_option', FILTER_SANITIZE_STRING);

    $intervencoes_post = $intervencoes_options[$intervencoes_key] ?? '';
    $resultado_final_post = $resultado_final_options[$resultado_final_key] ?? '';

    // Validação de que uma opção foi selecionada
    if (empty($intervencoes_key) || empty($resultado_final_key)) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Por favor, selecione uma opção para Intervenções Pedagógicas e Parecer Conclusivo.'];
        header("Location: ver_parecer.php?id_aluno={$id_aluno}&periodo=" . urlencode($periodo));
        exit();
    }


    // Mapeia os campos ENUM e suas opções para validação da parte GERAL
    $campos_enum_gerais = [
        'comportamento' => ['colaborativo', 'agressivo', 'retraido', 'proativo'],
        'participacao_grupo' => ['ativamente', 'pouco'],
        'respeito_regras' => ['sim', 'nao'],
        'postura_atividades' => ['seguranca', 'inseguranca', 'autonomia', 'dependencia', 'neutra'],
        'postura_desafios' => ['resiliencia', 'frustracao', 'flexibilidade', 'aceitacao']
    ];

    $consolidacao_resolvida_coordenador = []; // Armazena as escolhas do coordenador para empates

    try {
        $pdo->beginTransaction();

        // 1. Apurar os votos gerais de TODOS os professores que avaliaram este aluno/período
        $stmt_todos_pareceres_aluno_periodo = $pdo->prepare(
            "SELECT id, id_professor_designado, periodo,
                    disposicao_aula, desempenho_geral, obs_geral_professor, -- Campos do professor para o panorama geral
                    comportamento, participacao_grupo, respeito_regras, postura_atividades, postura_desafios
            FROM pareceres
            WHERE id_aluno = :id_aluno AND periodo = :periodo"
        );
        $stmt_todos_pareceres_aluno_periodo->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
        $stmt_todos_pareceres_aluno_periodo->bindParam(':periodo', $periodo, PDO::PARAM_STR);
        $stmt_todos_pareceres_aluno_periodo->execute();
        $todos_pareceres = $stmt_todos_pareceres_aluno_periodo->fetchAll();

        // Inicializar contadores para a apuração dos votos GERAIS (apenas campos gerais)
        $contadores_gerais_recheck = [
            'comportamento' => ['colaborativo' => 0, 'agressivo' => 0, 'retraido' => 0, 'proativo' => 0],
            'participacao_grupo' => ['ativamente' => 0, 'pouco' => 0],
            'respeito_regras' => ['sim' => 0, 'nao' => 0],
            'postura_atividades' => ['seguranca' => 0, 'inseguranca' => 0, 'autonomia' => 0, 'dependencia' => 0, 'neutra' => 0],
            'postura_desafios' => ['resiliencia' => 0, 'frustracao' => 0, 'flexibilidade' => 0, 'aceitacao' => 0]
        ];
        // Adiciona os campos 'disposicao_aula' e 'desempenho_geral' também para re-checagem de empate
        $contadores_gerais_recheck['disposicao_aula'] = ['facilidade' => 0, 'dificuldade' => 0, 'interesse' => 0, 'desinteresse' => 0];
        $contadores_gerais_recheck['desempenho_geral'] = ['acima' => 0, 'dentro' => 0, 'abaixo' => 0];

        foreach ($todos_pareceres as $parecer_individual) {
            // Contagem para campos gerais
            foreach ($contadores_gerais_recheck as $campo => $opcoes) {
                if (isset($parecer_individual[$campo]) && array_key_exists($parecer_individual[$campo], $contadores_gerais_recheck[$campo])) {
                    $contadores_gerais_recheck[$campo][$parecer_individual[$campo]]++;
                }
            }
        }

        $all_ties_resolved = true;
        foreach ($contadores_gerais_recheck as $campo => $votos) {
            $vencedor_apurado = getVencedor($votos);
            // IMPORTANTE: Aqui, também verificamos campos 'disposicao_aula' e 'desempenho_geral' para empates
            // e exigimos que a secretaria resolva se for o caso.
            // Precisamos definir as opções válidas para esses campos também, similar a $campos_enum_gerais
            $all_options_for_field = $campos_enum_gerais[$campo] ?? null; // Tenta usar as opções de $campos_enum_gerais
            if ($campo == 'disposicao_aula')
                $all_options_for_field = ['facilidade', 'dificuldade', 'interesse', 'desinteresse'];
            if ($campo == 'desempenho_geral')
                $all_options_for_field = ['acima', 'dentro', 'abaixo'];


            if (strpos($vencedor_apurado, 'EMPATE') === 0) {
                // Se há empate, verifica se a secretaria forneceu uma escolha final
                $input_name = 'final_' . $campo;
                $secretaria_choice = filter_input(INPUT_POST, $input_name, FILTER_SANITIZE_STRING);

                if (empty($secretaria_choice) || ($all_options_for_field && !in_array($secretaria_choice, $all_options_for_field))) {
                    $all_ties_resolved = false;
                    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro: Por favor, resolva todos os empates antes de finalizar o parecer. Empate em: ' . ucwords(str_replace('_', ' ', $campo))];
                    $pdo->rollBack();
                    header("Location: ver_parecer.php?id_aluno={$id_aluno}&periodo=" . urlencode($periodo));
                    exit();
                } else {
                    $consolidacao_resolvida_coordenador[$campo] = $secretaria_choice;
                }
            } else {
                $consolidacao_resolvida_coordenador[$campo] = $vencedor_apurado;
            }
        }

        // Constrói o texto da Seção de Observações Gerais dos Professores
        $texto_observacoes_professores = '';
        foreach ($todos_pareceres as $parecer_individual) {
            // Busca nome do professor para este parecer individual
            $stmt_prof_info = $pdo->prepare(
                "SELECT u.nome AS nome_professor
                 FROM usuarios u
                 WHERE u.id = :id_professor_designado"
            );
            $stmt_prof_info->bindParam(':id_professor_designado', $parecer_individual['id_professor_designado'], PDO::PARAM_INT);
            $stmt_prof_info->execute();
            $prof_info = $stmt_prof_info->fetch(PDO::FETCH_ASSOC);

            $nome_professor_designado = $prof_info['nome_professor'] ?? 'Professor Desconhecido';

            $texto_observacoes_professores .= "**Professor " . htmlspecialchars($nome_professor_designado) . "** (Unidade {$parecer_individual['unidade']}°): ";
            $texto_observacoes_professores .= "Disposição Geral: **" . htmlspecialchars(ucfirst($parecer_individual['disposicao_aula'] ?: 'Não informada')) . "**. ";
            $texto_observacoes_professores .= "Desempenho Geral: **" . htmlspecialchars(ucfirst($parecer_individual['desempenho_geral'] ?: 'Não informado')) . "**. ";
            if (!empty($parecer_individual['obs_geral_professor'])) {
                $texto_observacoes_professores .= "Observações: " . htmlspecialchars($parecer_individual['obs_geral_professor']) . ". ";
            }
            $texto_observacoes_professores .= "\n"; // Nova linha para cada parecer individual
        }
        $texto_observacoes_professores = trim($texto_observacoes_professores);

        // Combina o resultado final preenchido pela secretaria com a consolidação dos votos
        $final_text_combined = "Resultados Consolidados da Votação Geral:\n" . json_encode($consolidacao_resolvida_coordenador, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\nObservações Gerais dos Professores:\n" . $texto_observacoes_professores . "\n\n" . "Parecer Conclusivo da Secretaria/Coordenação:\n" . $resultado_final_post; // Texto ajustado para secretaria/coordenação

        // Encontre o ID de um parecer para armazenar os textos finais (intervencoes e resultado_final)
        // Como o status de finalização será aplicado a todos, podemos usar o ID do primeiro parecer encontrado
        $master_parecer_id_for_text = null;
        if (!empty($todos_pareceres)) {
            $master_parecer_id_for_text = $todos_pareceres[0]['id'];
        }

        if ($master_parecer_id_for_text) {
            $stmt_update_master_parecer = $pdo->prepare(
                "UPDATE pareceres SET intervencoes = :intervencoes, resultado_final = :resultado_final WHERE id = :id_parecer_master"
            );
            $stmt_update_master_parecer->bindParam(':intervencoes', $intervencoes_post, PDO::PARAM_STR);
            $stmt_update_master_parecer->bindParam(':resultado_final', $final_text_combined, PDO::PARAM_STR);
            $stmt_update_master_parecer->bindParam(':id_parecer_master', $master_parecer_id_for_text, PDO::PARAM_INT);
            $stmt_update_master_parecer->execute();
        }

        // Atualizar o status de TODOS os pareceres individuais do aluno/período para 'finalizado_coordenador'
        $stmt_update_status_all = $pdo->prepare(
            "UPDATE pareceres SET status = 'finalizado_coordenador' WHERE id_aluno = :id_aluno AND periodo = :periodo"
        );
        $stmt_update_status_all->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
        $stmt_update_status_all->bindParam(':periodo', $periodo, PDO::PARAM_STR);
        $stmt_update_status_all->execute();

        $pdo->commit();
        $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Parecer finalizado e salvo com sucesso!'];
        header("Location: ver_parecer.php?id_aluno={$id_aluno}&periodo=" . urlencode($periodo));
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao finalizar o parecer: " . $e->getMessage());
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao finalizar o parecer: ' . $e->getMessage()];
        header("Location: ver_parecer.php?id_aluno={$id_aluno}&periodo=" . urlencode($periodo));
        exit();
    }
}


// --- Obter dados do aluno e da turma para exibição ---
$aluno_info = null;
try {
    $stmt_aluno_info = $pdo->prepare(
        "SELECT a.nome AS nome_aluno, t.nome AS nome_turma, t.ano_letivo
         FROM alunos a
         LEFT JOIN turmas t ON a.turma_id = t.id
         WHERE a.id = :id_aluno"
    );
    $stmt_aluno_info->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
    $stmt_aluno_info->execute();
    $aluno_info = $stmt_aluno_info->fetch(PDO::FETCH_ASSOC);

    if (!$aluno_info) {
        die("Aluno não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao carregar informações do aluno: " . $e->getMessage());
}

// --- Obter TODOS os pareceres individuais para este aluno e período ---
$todos_pareceres_do_aluno_no_periodo = [];
$intervencoes_salvas = '';
$resultado_final_salvo = '';
$data_geracao_parecer_final_obj = null; // Variable to hold DateTime object
$is_finalizado_geral = false; // Define se o parecer geral está finalizado

try {
    // A consulta agora reflete a ausência de `id_disciplina` na tabela `pareceres`
    $stmt_pareceres_aluno_periodo = $pdo->prepare(
        "SELECT p.*, u.nome AS nome_professor
         FROM pareceres p
         JOIN usuarios u ON p.id_professor_designado = u.id
         WHERE p.id_aluno = :id_aluno AND p.periodo = :periodo
         ORDER BY u.nome"
    );
    $stmt_pareceres_aluno_periodo->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
    $stmt_pareceres_aluno_periodo->bindParam(':periodo', $periodo, PDO::PARAM_STR);
    $stmt_pareceres_aluno_periodo->execute();
    $todos_pareceres_do_aluno_no_periodo = $stmt_pareceres_aluno_periodo->fetchAll(PDO::FETCH_ASSOC);

    // Verifica o status de finalização do parecer geral e pega os textos finais do primeiro parecer finalizado
    foreach ($todos_pareceres_do_aluno_no_periodo as $p) {
        if ($p['status'] === 'finalizado_coordenador') {
            $is_finalizado_geral = true;
            $intervencoes_salvas = $p['intervencoes'];
            $resultado_final_salvo = $p['resultado_final'];

            // FIX: Create DateTime object from the correct database format (YYYY-MM-DD HH:MM:SS)
            // Use createFromFormat if there's any doubt about the exact input format from DB.
            // However, for TIMESTAMP, new DateTime() constructor should usually work.
            $date_from_db_string = $p['data_criacao']; // This should be 'YYYY-MM-DD HH:MM:SS'
            try {
                $data_geracao_parecer_final_obj = new DateTime($date_from_db_string);
            } catch (Exception $e) {
                // Fallback for unexpected date format, log or set to null/invalid
                error_log("Failed to parse data_criacao from DB: {$date_from_db_string} - " . $e->getMessage());
                $data_geracao_parecer_final_obj = null; // Indicate invalid date
            }
            break; // Stop after finding the first finalized parecer
        }
    }

} catch (PDOException $e) {
    die("Erro ao carregar pareceres individuais: " . $e->getMessage());
}

// --- Apuração dos Votos Gerais a partir de TODOS os pareceres individuais ---
$contadores_gerais = [
    'comportamento' => ['colaborativo' => 0, 'agressivo' => 0, 'retraido' => 0, 'proativo' => 0],
    'participacao_grupo' => ['ativamente' => 0, 'pouco' => 0],
    'respeito_regras' => ['sim' => 0, 'nao' => 0],
    'postura_atividades' => ['seguranca' => 0, 'inseguranca' => 0, 'autonomia' => 0, 'dependencia' => 0, 'neutra' => 0],
    'postura_desafios' => ['resiliencia' => 0, 'frustracao' => 0, 'flexibilidade' => 0, 'aceitacao' => 0]
];

// Adiciona os novos campos de panorama geral aos contadores
$contadores_gerais['disposicao_aula'] = ['facilidade' => 0, 'dificuldade' => 0, 'interesse' => 0, 'desinteresse' => 0];
$contadores_gerais['desempenho_geral'] = ['acima' => 0, 'dentro' => 0, 'abaixo' => 0];


foreach ($todos_pareceres_do_aluno_no_periodo as $parecer_individual) {
    foreach ($contadores_gerais as $campo => $opcoes) {
        if (isset($parecer_individual[$campo]) && array_key_exists($parecer_individual[$campo], $contadores_gerais[$campo])) {
            $contadores_gerais[$campo][$parecer_individual[$campo]]++;
        }
    }
}

$votos_apurados_gerais = [];
foreach ($contadores_gerais as $campo => $votos) {
    $votos_apurados_gerais[$campo] = getVencedor($votos);
}


// Exibe feedback se houver mensagem na sessão
if (isset($_SESSION['feedback_message'])) {
    $message_type = $_SESSION['feedback_message']['type'];
    $message_text = $_SESSION['feedback_message']['text'];
    echo "<div class='feedback-message-container'><div class='feedback-message {$message_type}'>" . htmlspecialchars($message_text) . "</div></div>";
    unset($_SESSION['feedback_message']); // Limpa a mensagem após exibir
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parecer Consolidado</title>
    <!-- Plugins CSS -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/mdi/css/materialdesignicons.min.css"); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/vendors/css/vendor.bundle.base.css"); ?>">
    <!-- Layout styles -->
    <link rel="stylesheet" href="<?php echo getAssetUrl("assets/css/style.css"); ?>">
    <link rel="shortcut icon" href="<?php echo getAssetUrl("assets/images/favicon.png"); ?>">
    <style>
        body {
            background-color: #f4f7f6;
        }

        .container-scroller {
            min-height: 100vh;
        }

        .content-wrapper {
            padding: 2rem 1rem;
        }

        .card-parecer {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .card-header-custom h2 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .card-header-custom p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        .info-section {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 5px solid #2575fc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .info-item {
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: #555;
        }

        .info-item strong {
            color: #333;
            font-weight: 600;
        }

        .section-title {
            display: flex;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
        }

        .section-title i {
            margin-right: 10px;
            color: #2575fc;
            font-size: 1.5rem;
        }

        .vote-card {
            background-color: #fff;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }

        .vote-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .vote-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 0.5rem;
            display: block;
        }

        .vote-winner {
            font-size: 1.1rem;
            color: #2575fc;
            font-weight: 700;
        }

        .vote-empate {
            color: #dc3545;
        }

        .vote-details {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.5rem;
        }

        .observation-card {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .prof-name {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .obs-text {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.6;
        }

        .form-select-custom {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ddd;
            width: 100%;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .form-select-custom:focus {
            border-color: #2575fc;
            box-shadow: 0 0 0 0.2rem rgba(37, 117, 252, 0.25);
            outline: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .status-finalizado {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-aberto {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .btn-action {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-finalize {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            border: none;
            color: white;
        }

        .btn-finalize:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 239, 125, 0.4);
        }

        .btn-pdf {
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            border: none;
            color: white;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 75, 43, 0.4);
        }

        .feedback-alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <div class="content-wrapper">
            <div class="container">
                <div class="card card-parecer">
                    <div class="card-header-custom">
                        <h2><i class="mdi mdi-file-document-box-check-outline"></i> Parecer Consolidado</h2>
                        <p>Visualização e finalização do parecer pedagógico global</p>

                        <?php if ($is_finalizado_geral): ?>
                            <div class="status-badge status-finalizado">
                                <i class="mdi mdi-check-circle"></i> FINALIZADO em
                                <?php echo ($data_geracao_parecer_final_obj instanceof DateTime) ? $data_geracao_parecer_final_obj->format('d/m/Y H:i') : 'Data Inválida'; ?>
                            </div>
                        <?php else: ?>
                            <div class="status-badge status-aberto">
                                <i class="mdi mdi-clock-outline"></i> EM ABERTO
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['feedback_message'])):
                            $msg = $_SESSION['feedback_message'];
                            $alertClass = $msg['type'] == 'success' ? 'alert-success' : 'alert-danger';
                            ?>
                            <div class="alert <?php echo $alertClass; ?> feedback-alert">
                                <?php echo htmlspecialchars($msg['text']); ?>
                            </div>
                            <?php unset($_SESSION['feedback_message']); ?>
                        <?php endif; ?>

                        <div class="info-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item"><i class="mdi mdi-account"></i> <strong>Aluno:</strong>
                                        <?php echo htmlspecialchars($aluno_info['nome_aluno']); ?></div>
                                    <div class="info-item"><i class="mdi mdi-school"></i> <strong>Turma:</strong>
                                        <?php echo htmlspecialchars($aluno_info['nome_turma'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item"><i class="mdi mdi-calendar"></i> <strong>Ano Letivo:</strong>
                                        <?php echo htmlspecialchars($aluno_info['ano_letivo'] ?? 'N/A'); ?></div>
                                    <div class="info-item"><i class="mdi mdi-calendar-range"></i>
                                        <strong>Período:</strong> <?php echo htmlspecialchars($periodo); ?></div>
                                </div>
                            </div>
                        </div>

                        <form
                            action="ver_parecer.php?id_aluno=<?php echo htmlspecialchars($id_aluno); ?>&periodo=<?php echo urlencode($periodo); ?>"
                            method="POST">
                            <input type="hidden" name="id_aluno" value="<?php echo htmlspecialchars($id_aluno); ?>">
                            <input type="hidden" name="periodo" value="<?php echo htmlspecialchars($periodo); ?>">
                            <input type="hidden" name="finalizar_parecer_global" value="1">

                            <div class="section-title">
                                <i class="mdi mdi-vote"></i> Apuração dos Votos Gerais
                            </div>

                            <?php if (empty($todos_pareceres_do_aluno_no_periodo)): ?>
                                <div class="alert alert-warning text-center">Nenhum parecer individual encontrado para este
                                    aluno e período.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($contadores_gerais as $campo => $votos): ?>
                                        <div class="col-md-6">
                                            <div class="vote-card">
                                                <span
                                                    class="vote-label"><?php echo ucwords(str_replace('_', ' ', $campo)); ?></span>
                                                <?php
                                                $vencedor_atual = getVencedor($votos);
                                                if (strpos($vencedor_atual, 'EMPATE') === 0 && !$is_finalizado_geral): ?>
                                                    <span class="vote-winner vote-empate"><i class="mdi mdi-alert-circle"></i>
                                                        <?php echo htmlspecialchars($vencedor_atual); ?></span>
                                                    <div class="mt-2">
                                                        <small class="text-danger">Resolva o empate:</small>
                                                        <select name="final_<?php echo htmlspecialchars($campo); ?>"
                                                            class="form-control form-control-sm mt-1" <?php echo $is_finalizado_geral ? 'disabled' : ''; ?>>
                                                            <option value="">Selecione...</option>
                                                            <?php
                                                            $opcoes_empate = explode(', ', str_replace('EMPATE: ', '', $vencedor_atual));
                                                            foreach ($opcoes_empate as $opcao_empate): ?>
                                                                <option value="<?php echo htmlspecialchars(trim($opcao_empate)); ?>">
                                                                    <?php echo htmlspecialchars(ucfirst(trim($opcao_empate))); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="vote-winner">
                                                        <?php echo htmlspecialchars(ucfirst($vencedor_atual)); ?>
                                                    </span>
                                                    <?php if ($is_finalizado_geral && strpos($vencedor_atual, 'EMPATE') === 0): ?>
                                                        <div class="mt-1 text-muted small">
                                                            <i class="mdi mdi-check"></i> Decisão Final:
                                                            <?php
                                                            $temp_consolidacao = json_decode($resultado_final_salvo ?? '', true);
                                                            echo htmlspecialchars(ucfirst($temp_consolidacao[$campo] ?? 'Não definida'));
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div class="vote-details">
                                                    <?php
                                                    $votos_detalhe = [];
                                                    foreach ($votos as $opcao => $contagem) {
                                                        if ($contagem > 0) {
                                                            $votos_detalhe[] = ucfirst($opcao) . ": " . $contagem;
                                                        }
                                                    }
                                                    echo implode(" | ", $votos_detalhe ?: ['Nenhum voto']);
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="section-title">
                                <i class="mdi mdi-comment-text-multiple"></i> Observações dos Professores
                            </div>

                            <?php if (empty($todos_pareceres_do_aluno_no_periodo)): ?>
                                <p class="text-muted text-center">Nenhuma avaliação encontrada.</p>
                            <?php else: ?>
                                <?php foreach ($todos_pareceres_do_aluno_no_periodo as $parecer_individual): ?>
                                    <div class="observation-card">
                                        <span class="prof-name"><i class="mdi mdi-account-tie"></i>
                                            <?php echo htmlspecialchars($parecer_individual['nome_professor']); ?> <small
                                                class="text-muted">(Unidade:
                                                <?php echo htmlspecialchars($parecer_individual['unidade']); ?>°)</small></span>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <p class="mb-1"><small><strong>Disposição:</strong>
                                                        <?php echo htmlspecialchars(ucfirst($parecer_individual['disposicao_aula'] ?: 'N/A')); ?></small>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1"><small><strong>Desempenho:</strong>
                                                        <?php echo htmlspecialchars(ucfirst($parecer_individual['desempenho_geral'] ?: 'N/A')); ?></small>
                                                </p>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <p class="obs-text mb-0">
                                            <?php echo nl2br(htmlspecialchars($parecer_individual['obs_geral_professor'] ?: 'Nenhuma observação registrada.')); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="section-title">
                                <i class="mdi mdi-lightbulb-on"></i> Intervenções Pedagógicas
                            </div>
                            <select name="intervencoes_option" class="form-select-custom" required <?php echo $is_finalizado_geral ? 'disabled' : ''; ?>>
                                <?php foreach ($intervencoes_options as $key => $text): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($intervencoes_salvas) && $intervencoes_salvas == $text) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($text); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="section-title">
                                <i class="mdi mdi-gavel"></i> Parecer Conclusivo
                            </div>
                            <select name="resultado_final_option" class="form-select-custom" required <?php echo $is_finalizado_geral ? 'disabled' : ''; ?>>
                                <?php foreach ($resultado_final_options as $key => $text): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($resultado_final_salvo) && $resultado_final_salvo == $text) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($text); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($is_finalizado_geral): ?>
                                <p class="text-muted small mt-2"><i class="mdi mdi-lock"></i> O texto completo foi gerado e
                                    salvo com base na opção selecionada.</p>
                            <?php endif; ?>

                            <div class="text-center mt-5 mb-4">
                                <?php if (!$is_finalizado_geral && isset($_SESSION['usuario_id']) && ($_SESSION['tipo'] == 'coordenador' || $_SESSION['tipo'] == 'secretaria')): ?>
                                    <button type="submit" class="btn btn-action btn-finalize">
                                        <i class="mdi mdi-check-all"></i> Finalizar Parecer Global
                                    </button>
                                <?php elseif ($is_finalizado_geral): ?>
                                    <a href="boletim/gerar_pdf_parecer.php?id_aluno=<?php echo htmlspecialchars($id_aluno); ?>&periodo=<?php echo urlencode($periodo); ?>"
                                        target="_blank" class="btn btn-action btn-pdf">
                                        <i class="mdi mdi-file-pdf"></i> Baixar PDF
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-secondary">Você não tem permissão para finalizar este parecer.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo getAssetUrl("assets/vendors/js/vendor.bundle.base.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/off-canvas.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/hoverable-collapse.js"); ?>"></script>
    <script src="<?php echo getAssetUrl("assets/js/misc.js"); ?>"></script>
</body>

</html>