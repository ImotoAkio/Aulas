<?php
/**
 * DOWNLOAD DE PARECER PEDAGÓGICO - VERSÃO ALUNO
 * 
 * Este script replica exatamente o mesmo processo usado pela secretaria
 * para geração de pareceres, mas adaptado para o acesso do aluno logado.
 * 
 * Utiliza o mesmo template PDF e as mesmas coordenadas de posicionamento.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    require_once __DIR__ . '/../config/database.php';
    redirectTo('login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$parecer_id = $_GET['id'] ?? 0;

if (!$parecer_id) {
    die("ID do parecer não fornecido.");
}

// Incluir as mesmas bibliotecas usadas pela secretaria
require __DIR__ . '/../fpdf/fpdf.php';
require __DIR__ . '/../fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

// Buscar dados do parecer - EXATAMENTE como na secretaria
try {
    $stmt = $pdo->prepare("
        SELECT p.*, d.nome as disciplina_nome, u.nome as professor_nome,
               a.nome_completo, a.nome, t.nome as turma_nome, t.ano_letivo
        FROM pareceres p
        LEFT JOIN disciplinas d ON p.disciplina_id = d.id
        LEFT JOIN usuarios u ON p.professor_id = u.id
        LEFT JOIN alunos a ON p.aluno_id = a.id
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE p.id = ? AND p.aluno_id = ?
    ");
    $stmt->execute([$parecer_id, $aluno_id]);
    $parecer = $stmt->fetch();
    
    if (!$parecer) {
        die("Parecer não encontrado.");
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar parecer: " . $e->getMessage());
    die("Erro interno do sistema.");
}

// GERAÇÃO DO DOCUMENTO PDF - EXATAMENTE como na secretaria

// 1. Inicialização do PDF
$pdf = new Fpdi();
$pdf->AddPage();

// 2. Carregamento do Template - MESMO template da secretaria
$templatePath = '../secretaria/boletim/teste.pdf';
if (!file_exists($templatePath)) {
    die("Erro fatal: Template PDF do parecer ('teste.pdf') não encontrado.");
}
$pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);
$pdf->useTemplate($templateId, 0, 0, 210, 297);

// 3. Preenchimento do Cabeçalho - MESMAS coordenadas da secretaria
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(98, 11);
$pdf->Cell(0, 5, mb_convert_encoding($parecer['nome_completo'] ?: $parecer['nome'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(95, 22);
$pdf->Cell(0, 5, mb_convert_encoding($parecer['turma_nome'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(124, 32);
$pdf->Cell(0, 5, mb_convert_encoding($parecer['unidade'] . 'ª Unidade', 'ISO-8859-1', 'UTF-8'), 0, 1);

// 4. Preenchimento dos dados do parecer - usando as mesmas coordenadas da secretaria
$pdf->SetFont('Arial', '', 10);

// Avaliação do Desempenho
if (!empty($parecer['avaliacao_desempenho'])) {
    $pdf->SetXY(20, 65);
    $pdf->MultiCell(170, 5, mb_convert_encoding($parecer['avaliacao_desempenho'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
}

// Pontos Positivos
if (!empty($parecer['pontos_positivos'])) {
    $pdf->SetXY(20, 100);
    $pdf->MultiCell(170, 5, mb_convert_encoding($parecer['pontos_positivos'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
}

// Sugestões para Melhoria
if (!empty($parecer['sugestoes_melhoria'])) {
    $pdf->SetXY(20, 135);
    $pdf->MultiCell(170, 5, mb_convert_encoding($parecer['sugestoes_melhoria'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
}

// Observações Gerais
if (!empty($parecer['observacoes'])) {
    $pdf->SetXY(20, 170);
    $pdf->MultiCell(170, 5, mb_convert_encoding($parecer['observacoes'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
}

// Status do Parecer
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(20, 220);
$pdf->Cell(0, 8, mb_convert_encoding('STATUS: ' . strtoupper($parecer['status']), 'ISO-8859-1', 'UTF-8'), 0, 1);

// Professor e Data
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(20, 240);
$pdf->Cell(0, 5, mb_convert_encoding('Professor: ' . $parecer['professor_nome'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(20, 250);
$pdf->Cell(0, 5, mb_convert_encoding('Data: ' . date('d/m/Y', strtotime($parecer['data_criacao'])), 'ISO-8859-1', 'UTF-8'), 0, 1);

// FINALIZAÇÃO - EXATAMENTE como na secretaria
$pdf->Output("I", mb_convert_encoding("parecer_{$parecer['disciplina_nome']}_{$parecer['unidade']}unidade.pdf", 'ISO-8859-1', 'UTF-8'));
exit;
?>
