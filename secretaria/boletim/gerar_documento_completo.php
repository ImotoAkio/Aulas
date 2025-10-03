<?php
/**
 * gerar_documento_final.php - Versão Definitiva e Monolítica
 *
 * Este script executa todas as etapas em uma única sequência para garantir
 * que os dados sejam buscados ANTES da geração do PDF, resolvendo o erro.
 */

// --- 1. CONFIGURAÇÃO INICIAL ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use setasign\Fpdi\Fpdi;

// --- 2. CAPTURA E VALIDAÇÃO DE PARÂMETROS ---
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);
$periodo = filter_input(INPUT_GET, 'periodo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$aluno_id || empty($periodo)) {
    die("Erro Crítico: Parâmetros 'aluno_id' e 'periodo' são obrigatórios na URL.");
}

// --- 3. FUNÇÕES AUXILIARES ---
function getConcept($media) {
    if (!is_numeric($media)) return '--';
    if ($media >= 9) return 'A';
    if ($media >= 8) return 'B';
    if ($media >= 7) return 'C';
    if ($media >= 6) return 'D';
    return 'F';
}

function getVencedorPDF($votos_campo) {
    if (empty($votos_campo) || array_sum($votos_campo) === 0) return "N/A";
    arsort($votos_campo);
    $max_votos = reset($votos_campo);
    $vencedores = [];
    foreach ($votos_campo as $opcao => $contagem) {
        if ($contagem === $max_votos) $vencedores[] = trim($opcao);
    }
    if (count($vencedores) > 1) return "EMPATE";
    return trim(reset($vencedores));
}

// --- 4. EXECUÇÃO PRINCIPAL ---
try {
    // --- ETAPA 4.1: BUSCAR TODOS OS DADOS PRIMEIRO ---

    // Query 1: Busca informações do aluno
    $stmt_aluno = $pdo->prepare("SELECT a.nome AS nome_aluno, t.nome AS nome_turma FROM alunos a JOIN turmas t ON a.turma_id = t.id WHERE a.id = :aluno_id");
    $stmt_aluno->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt_aluno->execute();
    $aluno_info = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

    if (!$aluno_info) {
        die("Aluno com ID {$aluno_id} não foi encontrado no banco de dados.");
    }

    // Query 2: Busca as notas para o boletim
    $stmt_notas = $pdo->prepare("
        SELECT d.nome AS disciplina,
               MAX(CASE WHEN n.unidade = 1 THEN n.media_1 END) AS media_1,
               MAX(CASE WHEN n.unidade = 2 THEN n.media_1 END) AS media_2,
               MAX(CASE WHEN n.unidade = 3 THEN n.media_1 END) AS media_3,
               MAX(CASE WHEN n.unidade = 4 THEN n.media_1 END) AS media_4
        FROM disciplinas d
        LEFT JOIN notas n ON d.id = n.disciplina_id AND n.aluno_id = :aluno_id
        GROUP BY d.id, d.nome ORDER BY FIELD(d.nome, 'Matemática', 'Português', 'Ciências', 'Ensino Religioso', 'História', 'Geografia', 'Artes', 'Inglês', 'Ed. Física', 'Filosofia')
    ");
    $stmt_notas->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt_notas->execute();
    $notas = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);

    // Query 3: Busca os dados para o parecer
    $stmt_pareceres = $pdo->prepare("SELECT p.*, u.nome AS nome_professor FROM pareceres p JOIN usuarios u ON p.id_professor_designado = u.id WHERE p.id_aluno = :id_aluno AND p.periodo = :periodo ORDER BY u.nome");
    $stmt_pareceres->bindParam(':id_aluno', $aluno_id, PDO::PARAM_INT);
    $stmt_pareceres->bindParam(':periodo', $periodo, PDO::PARAM_STR);
    $stmt_pareceres->execute();
    $todos_pareceres = $stmt_pareceres->fetchAll(PDO::FETCH_ASSOC);

    // --- ETAPA 4.2: PROCESSAR DADOS (APURAÇÃO DE VOTOS) ---
    $contadores_gerais = ['disposicao_aula' => [], 'desempenho_geral' => [], 'comportamento' => [], 'participacao_grupo' => [], 'respeito_regras' => [], 'postura_atividades' => [], 'postura_desafios' => []];
    $intervencoes_salvas = '';
    $resultado_final_salvo = '';
    $unidade_parecer_principal = '';

    foreach ($todos_pareceres as $p) {
        foreach ($contadores_gerais as $campo => $opcoes) {
            if (!empty($p[$campo])) $contadores_gerais[$campo][] = $p[$campo];
        }
        if ($p['status'] === 'finalizado_coordenador') {
            $intervencoes_salvas = $p['intervencoes'];
            $resultado_final_salvo = $p['resultado_final'];
            $unidade_parecer_principal = $p['unidade'];
        }
    }
    
    $votos_apurados_gerais = [];
    foreach($contadores_gerais as $campo => $votos) {
        $votos_apurados_gerais[$campo] = getVencedorPDF(array_count_values($votos));
    }

    // --- ETAPA 4.3: INICIAR A GERAÇÃO DO PDF (AGORA QUE TEMOS TODOS OS DADOS) ---
    $pdf = new Fpdi();
    
    // ---- PÁGINA 1: BOLETIM ----
    $pdf->AddPage('P', 'A4');
    $templateBoletimPath = 'template_boletim.pdf'; 
    if (!file_exists($templateBoletimPath)) die("Erro fatal: Template do boletim ('template_boletim.pdf') não encontrado.");
    
    $pdf->setSourceFile($templateBoletimPath);
    $tplIdBoletim = $pdf->importPage(1);
    $pdf->useTemplate($tplIdBoletim, 0, 0, 210, 297);
    
    // Cole a lógica de preenchimento do boletim aqui...
    // (A mesma lógica que isolamos antes)

    // ---- PÁGINA 2: PARECER ----
    $pdf->AddPage('P', 'A4');
    $templateParecerPath = 'teste.pdf'; 
    if (!file_exists($templateParecerPath)) die("Erro fatal: Template do parecer ('teste.pdf') não encontrado.");

    $pdf->setSourceFile($templateParecerPath);
    $tplIdParecer = $pdf->importPage(1);
    $pdf->useTemplate($tplIdParecer, 0, 0, 210, 297);

    // Cole a lógica de preenchimento do parecer aqui...
    // (A mesma lógica que isolamos antes, preenchendo os "X"s e textos)

    // --- ETAPA 4.4: FINALIZAR E ENVIAR O PDF ---
    $nome_arquivo_final = "documento_" . str_replace(' ', '_', $aluno_info['nome_aluno']) . ".pdf";
    $pdf->Output("I", mb_convert_encoding($nome_arquivo_final, 'ISO-8859-1', 'UTF-8'));
    exit;

} catch (PDOException $e) {
    die("ERRO DE BANCO DE DADOS: " . $e->getMessage());
}
?>