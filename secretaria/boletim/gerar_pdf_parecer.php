<?php
/**
 * gerar_pdf_parecer.php
 *
 * Este script gera um PDF do parecer pedagógico consolidado para um aluno e período,
 * utilizando a biblioteca FPDF e FPDI para preencher um template PDF.
 *
 * Parâmetros GET esperados:
 * - id_aluno: ID do aluno para gerar o PDF.
 * - periodo: Período do parecer.
 *
 * Requer o template PDF (`template_parecer.pdf`) no caminho especificado.
 */

// --- CONFIGURAÇÃO DE ERROS PARA DEPURACAO (REMOVER EM PRODUCAO) ---
// Comente ou remova as linhas abaixo em ambiente de produção para evitar que avisos quebrem o PDF.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- FIM DA CONFIGURACAO DE ERROS ---

// Inclui a biblioteca FPDF. Adapte o caminho se necessário.
require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php'; // Biblioteca FPDI para usar templates PDF
require_once __DIR__ . '/../config/database.php';

use setasign\Fpdi\Fpdi;

$id_aluno = filter_input(INPUT_GET, 'id_aluno', FILTER_VALIDATE_INT);
$periodo = filter_input(INPUT_GET, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Valida os parâmetros GET
if (!$id_aluno || empty($periodo)) {
    die("Parâmetros 'id_aluno' ou 'periodo' ausentes para gerar o PDF.");
}

/**
 * Função para apurar o vencedor de um voto ENUM (necessária aqui para ser self-contained).
 * Retorna a opção mais votada ou "EMPATE: opção1, opção2" se houver empate.
 * @param array $votos_campo Array associativo com as opções e suas contagens de votos.
 * @return string A opção vencedora ou a string de empate.
 */
function getVencedorPDF($votos_campo) {
    if (empty($votos_campo) || array_sum($votos_campo) === 0) {
        return "N/A (Sem votos)";
    }
    arsort($votos_campo); // Ordena do maior para o menor

    $max_votos = reset($votos_campo); // Pega a contagem máxima de votos
    $vencedores = [];
    foreach ($votos_campo as $opcao => $contagem) {
        if ($contagem === $max_votos) {
            $vencedores[] = trim($opcao); // Garante que a opção vencedora não tenha espaços
        }
    }

    if (count($vencedores) > 1) {
        return "EMPATE: " . implode(", ", $vencedores);
    }
    return trim(reset($vencedores)); // Garante que a opção vencedora não tenha espaços
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
$unidade_parecer_principal = ''; // Para exibir a unidade do parecer principal no cabeçalho
$data_geracao_parecer_final_obj = null; // Variável para armazenar o objeto DateTime

try {
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

    // Pega os textos de intervenções e resultado final do *primeiro* parecer finalizado
    // E a data de geração/finalização para exibir no PDF.
    foreach ($todos_pareceres_do_aluno_no_periodo as $p) {
        if ($p['status'] === 'finalizado_coordenador') {
            $intervencoes_salvas = $p['intervencoes'];
            $resultado_final_salvo = $p['resultado_final'];
            $unidade_parecer_principal = $p['unidade']; // Pega a unidade do parecer finalizado
            
            // Tenta criar objeto DateTime a partir da string do DB
            $date_from_db_string = $p['data_criacao']; 
            try {
                $data_geracao_parecer_final_obj = new DateTime($date_from_db_string);
            } catch (Exception $e) {
                error_log("Failed to parse data_criacao from DB for PDF: {$date_from_db_string} - " . $e->getMessage());
                $data_geracao_parecer_final_obj = null; // Fallback
            }
            break; // Sai do loop após encontrar o primeiro parecer finalizado
        }
    }

} catch (PDOException $e) {
    die("Erro ao carregar pareceres individuais para PDF: " . $e->getMessage());
}

// --- Apuração dos Votos Gerais a partir de TODOS os pareceres individuais ---
// Campos de votação geral para apuração
// Esta seção de contadores é mantida aqui para que 'getVencedorPDF' possa ser chamada
// para as observações dos professores (mesmo que os X's não sejam mais desenhados).
$contadores_gerais = [
    'disposicao_aula' => ['facilidade' => 0, 'dificuldade' => 0, 'interesse' => 0, 'desinteresse' => 0],
    'desempenho_geral' => ['acima' => 0, 'dentro' => 0, 'abaixo' => 0],
    'comportamento' => ['colaborativo' => 0, 'agressivo' => 0, 'retraido' => 0, 'proativo' => 0],
    'participacao_grupo' => ['ativamente' => 0, 'pouco' => 0],
    'respeito_regras' => ['sim' => 0, 'nao' => 0],
    'postura_atividades' => ['seguranca' => 0, 'inseguranca' => 0, 'autonomia' => 0, 'dependencia' => 0, 'neutra' => 0],
    'postura_desafios' => ['resiliencia' => 0, 'frustracao' => 0, 'flexibilidade' => 0, 'aceitacao' => 0]
];

foreach ($todos_pareceres_do_aluno_no_periodo as $parecer_individual) {
    foreach ($contadores_gerais as $campo => $opcoes) {
        if (isset($parecer_individual[$campo]) && array_key_exists($parecer_individual[$campo], $contadores_gerais[$campo])) {
            $contadores_gerais[$campo][$parecer_individual[$campo]]++;
        }
    }
}

$votos_apurados_gerais = [];
foreach ($contadores_gerais as $campo => $votos) {
    $votos_apurados_gerais[$campo] = getVencedorPDF($votos);
}

// -----------------------------------------------------
// Geração do PDF com FPDF e FPDI
// -----------------------------------------------------

$pdf = new Fpdi();
$pdf->AddPage();

// Carregar o template PDF
$templatePath = 'teste.pdf'; // Caminho para o template PDF (ASSUMINDO A MESMA PASTA)
// Se o seu template estiver em uma pasta acima (ex: em /aulas/), mude para:
// $templatePath = '../../template_parecer.pdf';
// Verifique o caminho correto para seu template. Ex: '/var/www/html/templates/template_parecer.pdf'
if (!file_exists($templatePath)) {
    die("Erro fatal: Template PDF não encontrado em {$templatePath}. Verifique o caminho do arquivo e as permissões de leitura.");
}
$pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);
$pdf->useTemplate($templateId, 0, 0, 210, 297); // Ajustar ao tamanho A4

// Configurar fonte padrão
$pdf->SetFont('Arial', '', 10); // Fonte padrão Arial, tamanho 10

// --- EXEMPLO DE LINHAS DE DEPURACAO (DESCOMENTE PARA USAR) ---
// Para usar as linhas de depuração, descomente as linhas abaixo.
// Elas desenharão caixas e linhas vermelhas nas posições especificadas.
// Use essas cações para ajustar as coordenadas X, Y, largura e altura.
// Após ajustar, comente as linhas novamente.
// $pdf->SetDrawColor(255, 0, 0); // Define a cor vermelha para depuração
// $pdf->Rect(98, 11, 100, 5, 'D'); // Retângulo para "ALUNO: Nome do Aluno"
// $pdf->Rect(95, 22, 50, 5, 'D'); // Retângulo para "SÉRIE: Turma do Aluno"
// $pdf->Rect(125, 32, 50, 5, 'D'); // Retângulo para "PERÍODO AVALIADO"
// $pdf->Rect(158, 52, 30, 5, 'D'); // Retângulo para "UNIDADE"
// $pdf->Rect(150, 58, 40, 5, 'D'); // Retângulo para "Finalizado em:"
// $pdf->Rect(10, 84, 5, 5, 'D'); // Retângulo para o X de "facilidade"
// $pdf->Rect(10, 90, 5, 5, 'D'); // Retângulo para "dificuldade"
// $pdf->Rect(10, 94, 5, 5, 'D'); // Retângulo para "interesse"
// $pdf->Rect(10, 100, 5, 5, 'D'); // Retângulo para "desinteresse"
// $pdf->Rect(56, 120, 5, 5, 'D'); // Retângulo para o X de "acima" (Desempenho Geral)
// $pdf->Rect(56, 127, 5, 5, 'D'); // Retângulo para o X de "dentro" (Desempenho Geral)
// $pdf->Rect(56, 136, 5, 5, 'D'); // Retângulo para o X de "abaixo" (Desempenho Geral)
// $pdf->Rect(114.5, 90, 5, 5, 'D'); // Retângulo para o X de "colaborativo"
// $pdf->Rect(114.5, 95, 5, 5, 'D'); // Retângulo para o X de "agressivo"
// $pdf->Rect(114.5, 100, 5, 5, 'D'); // Retângulo para o X de "retraido"
// $pdf->Rect(114.5, 106, 5, 5, 'D'); // Retângulo para o X de "proativo"
// $pdf->Rect(131, 113, 5, 5, 'D'); // Retângulo para "ativamente"
// $pdf->Rect(165.5, 113, 5, 5, 'D'); // Retângulo para "pouco"
// $pdf->Rect(178.7, 118.5, 5, 5, 'D'); // Retângulo para "sim" (Respeito as Regras)
// $pdf->Rect(115, 124, 5, 5, 'D'); // Retângulo para "nao" (Respeito as Regras)
// $pdf->Rect(115, 158.5, 5, 5, 'D'); // Retângulo para "seguranca" (Postura atividades)
// $pdf->Rect(115, 164, 5, 5, 'D'); // Retângulo para "inseguranca" (Postura atividades)
// $pdf->Rect(115, 169.5, 5, 5, 'D'); // Retângulo para "autonomia" (Postura atividades)
// $pdf->Rect(115, 174.5, 5, 5, 'D'); // Retângulo para "dependencia" (Postura atividades)
// $pdf->Rect(115, 179.5, 5, 5, 'D'); // Retângulo para "neutra" (Postura atividades)
// $pdf->Rect(115, 190, 5, 5, 'D'); // Retângulo para "resiliencia" (Postura desafios)
// $pdf->Rect(115, 196, 5, 5, 'D'); // Retângulo para "frustracao" (Postura desafios)
// $pdf->Rect(115, 201, 5, 5, 'D'); // Retângulo para "flexibilidade" (Postura desafios)
// $pdf->Rect(115, 206.5, 5, 5, 'D'); // Retângulo para "aceitacao" (Postura desafios)
// $pdf->Rect(111, 220, 90, 5, 'D'); // Retângulo para Intervenções Pedagógicas
// $pdf->Rect(10, 150, 120, 10, 'D'); // Retângulo para Resultado Final
// $pdf->SetDrawColor(0, 0, 0); // Volta para a cor preta


// --- Preencher Informações do Aluno no Cabeçalho ---
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(98, 11); // ALUNO: Nome do Aluno (NOVA POSIÇÃO)
$pdf->Cell(0, 5, mb_convert_encoding($aluno_info['nome_aluno'], 'ISO-8859-1', 'UTF-8'), 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(95, 22); // SÉRIE: Turma do Aluno (NOVA POSIÇÃO)
$pdf->Cell(0, 5, mb_convert_encoding(($aluno_info['nome_turma'] ?? 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);


// UNIDADE: Exibir a unidade do parecer principal se disponível
        $pdf->SetFont('Arial', '', 12); 

$pdf->SetXY(124, 32); // Posição para UNIDADE (MANTIDA)
$pdf->Cell(0, 5, mb_convert_encoding(($unidade_parecer_principal ? $unidade_parecer_principal . '°' : 'N/A'), 'ISO-8859-1', 'UTF-8'), 0, 1);


// Data de Finalização do Parecer


// --- Seção 1: Aspectos Cognitivos e Acadêmicos (Compilado dos Professores) ---
// REMOVIDO: Texto introdutório "Este parecer apresenta uma descrição individual..."
// O conteúdo desta seção será preenchido pelas observações dos professores.

// Adicionar o texto compilado das observações gerais dos professores
$y_current_for_obs = $pdf->GetY(); // Pega a posição Y atual (após o cabeçalho, se nada foi escrito)
// Ajuste a posição Y inicial para as observações, pois o texto introdutório foi removido.
// Estime uma posição Y onde o bloco de observações deve começar após o cabeçalho.
$pdf->SetY(65); // NOVA POSIÇÃO INICIAL PARA OBSERVAÇÕES APÓS CABEÇALHO (AJUSTE CONFORME TEMPLATE)
$y_current_for_obs = $pdf->GetY();


if (!empty($todos_pareceres_do_aluno_no_periodo)) {
    foreach ($todos_pareceres_do_aluno_no_periodo as $parecer_individual) {
        // Verifica se o texto excederá o limite de página antes de escrever
        // Se a próxima escrita for muito próxima do rodapé, adicione uma nova página
        if (($y_current_for_obs + 20) > 280) { // 20 é uma margem de segurança + altura da célula
            $pdf->AddPage();
            $pdf->useTemplate($templateId, 0, 0, 210, 297); // Adiciona o template na nova página
            $y_current_for_obs = 20; // Reseta o Y para o topo da nova página
            $pdf->SetY($y_current_for_obs);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 5, mb_convert_encoding('Continuação das Observações', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
            $pdf->Ln(5);
            $y_current_for_obs = $pdf->GetY();
        }

        $pdf->SetFont('Arial', 'B', 9); // Negrito para o nome do professor
        $pdf->SetXY(10, 200);
        $pdf->MultiCell(170, 4, mb_convert_encoding('Professor: ' . $parecer_individual['nome_professor'] . ' (Unidade: ' . ($parecer_individual['unidade']) . '°)', 'ISO-8859-1', 'UTF-8'), 0, 'J');
        $y_current_for_obs = $pdf->GetY(); // Atualiza Y

$pdf->SetFont('Arial', '', 12); // Fonte normal para observações
        $pdf->SetXY(10, $y_current_for_obs);
        
        // --- INÍCIO DA CORREÇÃO ---
        // 1. Decodifica o texto do parecer do professor que vem do banco
        $obs_decodificada = html_entity_decode($parecer_individual['obs_geral_professor'] ?: 'Nenhuma observação.', ENT_QUOTES, 'UTF-8');
        
        // 2. Monta a string de impressão com o texto já limpo
        $text_to_print = 'Observações Gerais: ' . $obs_decodificada . "\n\n";

        // 3. Converte a string final para o PDF
        $pdf->MultiCell(90, 4, mb_convert_encoding($text_to_print, 'ISO-8859-1', 'UTF-8'), 0, 'J');
        // --- FIM DA CORREÇÃO ---
        
        $y_current_for_obs = $pdf->GetY(); // Atualiza Y após a MultiCell
    }
} else {
    $pdf->SetX(20);
    $pdf->MultiCell(170, 5, mb_convert_encoding('Nenhuma avaliação de professor encontrada para este aluno e período.', 'ISO-8859-1', 'UTF-8'), 0, 'J');
    $y_current_for_obs = $pdf->GetY();
}
$pdf->Ln(5);


// --- Seção 1 (continuação no template): Aspectos Cognitivos e Acadêmicos (CONTINUAÇÃO DOS X's) ---
// Disposição na minha aula - NOVAS COORDENADAS
// facilidade (x10, y84), dificuldade (x10, y90), interesse (x10, y94), desinteresse (x10, y100)
$x_disposicao = 10.5; // Ajuste fino para o X, se necessário

// Facilidade
$pdf->SetXY($x_disposicao, 86.5);
if ($votos_apurados_gerais['disposicao_aula'] == 'facilidade') $pdf->Text($x_disposicao, 86.5, 'X');

// Dificuldade
$pdf->SetXY($x_disposicao, 92); 
if ($votos_apurados_gerais['disposicao_aula'] == 'dificuldade') $pdf->Text($x_disposicao, 92, 'X');

// Interesse
$pdf->SetXY($x_disposicao, 94);
if ($votos_apurados_gerais['disposicao_aula'] == 'interesse') $pdf->Text($x_disposicao, 94, 'X');

// Desinteresse
$pdf->SetXY($x_disposicao, 102.5);
if ($votos_apurados_gerais['disposicao_aula'] == 'desinteresse') $pdf->Text($x_disposicao, 102.5, 'X');

if (strpos($votos_apurados_gerais['disposicao_aula'], 'EMPATE') === 0) {
    // Estas mensagens de empate podem precisar de posicionamento diferente se as caixas de seleção estiverem muito próximas
    // Posicionar abaixo do último 'X' da seção de disposição
    $pdf->SetXY(20, 100 + 4); // Y do último X + offset
    $pdf->SetFont('Arial', 'B', 7); 
    $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Disposição na Aula. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}

// Desempenho Geral - NOVAS COORDENADAS
// acima (x56,y120), dentro (x56,y127), abaixo (x56,y136)
$x_desempenho_geral = 58;

// Acima do esperado
$pdf->SetXY($x_desempenho_geral, 123);
if ($votos_apurados_gerais['desempenho_geral'] == 'acima') $pdf->Text($x_desempenho_geral, 123, 'X');

// Dentro do esperado
$pdf->SetXY($x_desempenho_geral, 130);
if ($votos_apurados_gerais['desempenho_geral'] == 'dentro') $pdf->Text($x_desempenho_geral, 130, 'X');

// Abaixo do esperado
$pdf->SetXY($x_desempenho_geral, 136);
if ($votos_apurados_gerais['desempenho_geral'] == 'abaixo') $pdf->Text($x_desempenho_geral, 136, 'X');

if (strpos($votos_apurados_gerais['desempenho_geral'], 'EMPATE') === 0) {
    $pdf->SetXY(20, 136 + 4); // Y do último X + offset
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Desempenho Geral. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// Comportamento - NOVAS COORDENADAS
// colaborativo (114.5, 90), agressivo (114.5, 95), retraido (114.5, 100), proativo (114.5, 106)
$x_comportamento = 114.5; 

// Colaborativo
$pdf->SetXY($x_comportamento, 90); 
if ($votos_apurados_gerais['comportamento'] == 'colaborativo') $pdf->Text($x_comportamento, 90, 'X');

// Agressivo
$pdf->SetXY($x_comportamento, 95); 
if ($votos_apurados_gerais['comportamento'] == 'agressivo') $pdf->Text($x_comportamento, 95, 'X');

// Retraído
$pdf->SetXY($x_comportamento, 100); 
if ($votos_apurados_gerais['comportamento'] == 'retraido') $pdf->Text($x_comportamento, 100, 'X');

// Proativo
$pdf->SetXY($x_comportamento, 106); 
if ($votos_apurados_gerais['comportamento'] == 'proativo') $pdf->Text($x_comportamento, 106, 'X');

// Se for empate, pode-se adicionar uma nota abaixo do bloco de checkboxes de Comportamento
if (strpos($votos_apurados_gerais['comportamento'], 'EMPATE') === 0) {
    // Ajustar Y para não sobrescrever
    $pdf->SetXY(20, 106 + 4); // Y do último X + offset
    $pdf->SetFont('Arial', 'B', 7); 
    $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Comportamento. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// Participação em grupo - As posições precisam ser reajustadas para o template
// Exemplo de posições a serem medidas no template: ativamente (~53, Y_base_participacao), pouco (~87.5, Y_base_participacao)
$y_participacao = 113; // Estime a linha Y para a seção Participação no template
$x_participacao_ativamente = 131;
$x_participacao_pouco = 165.5;

$pdf->SetXY($x_participacao_ativamente, $y_participacao); 
if ($votos_apurados_gerais['participacao_grupo'] == 'ativamente') $pdf->Text($x_participacao_ativamente, $y_participacao, 'X');
$pdf->SetXY($x_participacao_pouco, $y_participacao); 
if ($votos_apurados_gerais['participacao_grupo'] == 'pouco') $pdf->Text($x_participacao_pouco, $y_participacao, 'X');
if (strpos($votos_apurados_gerais['participacao_grupo'], 'EMPATE') === 0) {
    $pdf->SetXY(20, $y_participacao + 4); $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Participação em Grupo. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// Respeito às regras - NOVAS COORDENADAS E LÓGICA DE EMPATE
// y=118.5 e x=178.7 (sim); y=124 x=115 (nao); caso seja empate considere que respeita as regras
$x_respeito_sim = 178.7;
$y_respeito_sim = 118.5;
$x_respeito_nao = 115;
$y_respeito_nao = 124;

// Check if 'sim' is the winner OR if it's an EMPATE (then treat as 'sim')
if ($votos_apurados_gerais['respeito_regras'] == 'sim' || strpos($votos_apurados_gerais['respeito_regras'], 'EMPATE') === 0) {
    $pdf->SetXY($x_respeito_sim, $y_respeito_sim);
    $pdf->Text($x_respeito_sim, $y_respeito_sim, 'X');
}
// Check if 'nao' is the winner
elseif ($votos_apurados_gerais['respeito_regras'] == 'nao') {
    $pdf->SetXY($x_respeito_nao, $y_respeito_nao);
    $pdf->Text($x_respeito_nao, $y_respeito_nao, 'X');
}
// If it's an EMPATE, the previous 'if' would have handled it, so this block might be redundant but keeping for clarity on what gets displayed.
if (strpos($votos_apurados_gerais['respeito_regras'], 'EMPATE') === 0) {
    // Positioning for "Obs: EMPATE" text - it should be positioned carefully not to overlap.
    // Let's use a Y that is clearly below the options. Maybe around 128-130.
    $pdf->SetXY(20, 128); // Adjusted Y to prevent overlap
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Respeito às Regras. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// Aspectos Emocionais - Postura geral nas atividades - NOVAS COORDENADAS
// segurança (x=115, y=158.5), insegurança (x=115, y=164), autonomia (x=115, y=169.5), dependencia (x=115, y=174.5), neutra (x=115, y=179.5) - INFERIDO NEUTRA
$x_postura_ativ = 115; 

// Segurança
$pdf->SetXY($x_postura_ativ, 158.5); 
if ($votos_apurados_gerais['postura_atividades'] == 'seguranca') $pdf->Text($x_postura_ativ, 158.5, 'X');

// Insegurança
$pdf->SetXY($x_postura_ativ, 164); 
if ($votos_apurados_gerais['postura_atividades'] == 'inseguranca') $pdf->Text($x_postura_ativ, 164, 'X');

// Autonomia
$pdf->SetXY($x_postura_ativ, 169.5); 
if ($votos_apurados_gerais['postura_atividades'] == 'autonomia') $pdf->Text($x_postura_ativ, 169.5, 'X');

// Dependência
$pdf->SetXY($x_postura_ativ, 174.5); 
if ($votos_apurados_gerais['postura_atividades'] == 'dependencia') $pdf->Text($x_postura_ativ, 174.5, 'X');

// Neutra (Inferido com base no padrão de 5.5mm entre as opções anteriores)
$pdf->SetXY($x_postura_ativ, 179.5); 
if ($votos_apurados_gerais['postura_atividades'] == 'neutra') $pdf->Text($x_postura_ativ, 179.5, 'X');

if (strpos($votos_apurados_gerais['postura_atividades'], 'EMPATE') === 0) {
    $pdf->SetXY(20, $pdf->GetY() + 4); $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Postura nas Atividades. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// Postura diante de desafios - As posições precisam ser reajustadas
// Exemplo de posições: resiliencia (~53, Y_base_desafios), frustracao (~87.5, Y_base_desafios), etc.
$y_postura_desafios = 190; // Estime a linha Y para a seção Postura diante de desafios - NOVA POSIÇÃO INFERIDA
$x_postura_resiliencia = 115;
$x_postura_frustracao = 115;
$x_postura_flexibilidade = 115;
$x_postura_aceitacao = 115;

$pdf->SetXY($x_postura_resiliencia, 190); 
if ($votos_apurados_gerais['postura_desafios'] == 'resiliencia') $pdf->Text($x_postura_resiliencia, 190, 'X');

$pdf->SetXY($x_postura_frustracao, 196); 
if ($votos_apurados_gerais['postura_desafios'] == 'frustracao') $pdf->Text($x_postura_frustracao, 196, 'X');

$pdf->SetXY($x_postura_flexibilidade, 201); 
if ($votos_apurados_gerais['postura_desafios'] == 'flexibilidade') $pdf->Text($x_postura_flexibilidade, 201, 'X');

$pdf->SetXY($x_postura_aceitacao, 206.5); // Y inferido
if ($votos_apurados_gerais['postura_desafios'] == 'aceitacao') $pdf->Text($x_postura_aceitacao, 206.5, 'X');
if (strpos($votos_apurados_gerais['postura_desafios'], 'EMPATE') === 0) {
    $pdf->SetXY(20, $pdf->GetY() + 4); $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(0, 5, mb_convert_encoding('Obs: EMPATE em Postura diante de Desafios. Ver Parecer Conclusivo.', 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 10); // Reseta a fonte
}


// --- Campos de Texto Final: Intervenções e Resultado Final ---
// Intervenções Pedagógicas
$pdf->SetXY(111, 220); // Posição para Intervenções (Estimativa)
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(90, 5, mb_convert_encoding($intervencoes_salvas ?: 'Nenhuma intervenção registrada.', 'ISO-8859-1', 'UTF-8'), 0, 'J');


// Resultado Final / Parecer Conclusivo
$pdf->SetXY(10, 143); // Posição para Resultado Final (Estimativa)
$pdf->SetFont('Arial', '', 12);

// --- Lógica para extrair APENAS a parte do Parecer Conclusivo da Secretaria/Coordenação ---
$texto_conclusivo_apenas = $resultado_final_salvo;
$marker_start = "Parecer Conclusivo da Secretaria/Coordenação:\n"; // O marcador que indica o início da seção desejada

$pos_start = strrpos($resultado_final_salvo, $marker_start); // Encontra a ÚLTIMA ocorrência do marcador

if ($pos_start !== false) {
    // Extrai o texto que vem depois do marcador
    $texto_conclusivo_apenas = substr($resultado_final_salvo, $pos_start + strlen($marker_start));
    // Remove espaços em branco extras no início e fim
    $texto_conclusivo_apenas = trim($texto_conclusivo_apenas);
} else {
    // Se o marcador não for encontrado, significa que o formato do texto pode ter mudado
    // ou é um dado antigo. Nesse caso, para evitar erro, $texto_conclusivo_apenas 
    // mantém o valor original de $resultado_final_salvo, ou você pode definir um fallback
    // como 'Nenhum parecer conclusivo final encontrado no formato esperado.'
}

$pdf->MultiCell(90, 6, mb_convert_encoding($texto_conclusivo_apenas ?: 'Nenhum resultado final registrado.', 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln(5);


// Salvar ou exibir o PDF
$pdf->Output("I", mb_convert_encoding("parecer_{$aluno_info['nome_aluno']}_{$periodo}.pdf", 'ISO-8859-1', 'UTF-8'));
exit;

