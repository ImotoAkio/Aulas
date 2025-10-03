<?php
/**
 * GERADOR DE BOLETIM ESCOLAR EM PDF - VERSÃO ALUNO
 * 
 * Este script replica exatamente o mesmo processo usado pela secretaria,
 * mas adaptado para o acesso do aluno logado.
 * 
 * Utiliza o mesmo template PDF e as mesmas coordenadas de posicionamento.
 */

session_start();
include('../secretaria/partials/db.php');

// Verificar se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'aluno') {
    header('Location: ../../login.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Incluir as mesmas bibliotecas usadas pela secretaria
require('../fpdf/fpdf.php');
require('../fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

/**
 * Converte uma nota numérica em um conceito (letra) - EXATAMENTE como na secretaria
 */
function getConcept($media) {
    if ($media === null || !is_numeric($media) || $media == 0) {
        return '--';
    }
    if ($media >= 9) {
        return 'A';
    } elseif ($media >= 8) {
        return 'B';
    } elseif ($media >= 7) {
        return 'C';
    } elseif ($media >= 6) {
        return 'D';
    } else {
        return 'F';
    }
}

// Buscar dados do aluno - EXATAMENTE como na secretaria
try {
    // 1. Busca as informações básicas do aluno e sua turma
    $stmt = $pdo->prepare("SELECT a.nome AS aluno, t.nome AS turma FROM alunos a JOIN turmas t ON a.turma_id = t.id WHERE a.id = :aluno_id");
    $stmt->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt->execute();
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        die("Aluno não encontrado no banco de dados.");
    }

    // 2. Busca TODAS as notas do aluno para TODAS as disciplinas - EXATAMENTE como na secretaria
    $stmt = $pdo->prepare("
SELECT
    d.nome AS disciplina,
    MAX(n.media_1) AS media_1,
    MAX(n.media_2) AS media_2,
    MAX(n.media_3) AS media_3,
    MAX(n.media_4) AS media_4
FROM
    disciplinas d
LEFT JOIN
    notas n ON d.id = n.disciplina_id AND n.aluno_id = :aluno_id
GROUP BY
    d.id, d.nome
ORDER BY
    d.id
    ");
    $stmt->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt->execute();
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para o boletim: " . $e->getMessage());
    die("Erro ao carregar dados do boletim. Por favor, contate o suporte técnico.");
}

// GERAÇÃO DO DOCUMENTO PDF - EXATAMENTE como na secretaria

// 1. Inicialização do PDF
$pdf = new Fpdi();
$pdf->AddPage();

// 2. Carregamento do Template - MESMO template da secretaria
$templatePath = '../secretaria/boletim/template_boletim.pdf';
if (!file_exists($templatePath)) {
    die("Erro fatal: Template PDF do boletim ('template_boletim.pdf') não encontrado.");
}
$pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);
$pdf->useTemplate($templateId, 0, 0, 210, 297);

// 3. Preenchimento do Cabeçalho - MESMAS coordenadas da secretaria
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(27, 47);
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno['aluno']}", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(152, 47);
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno['turma']}", 'ISO-8859-1', 'UTF-8'), 0, 1);

// 4. Preenchimento da Tabela de Disciplinas Regulares - EXATAMENTE como na secretaria
$disciplinas_regulares = [
    'Matemática', 'Português', 'Ciências', 'Ensino Religioso', 'História',
    'Geografia', 'Artes', 'Inglês', 'Ed. Física', 'Filosofia'
];

// Coordenadas e dimensões da tabela - MESMAS da secretaria
$linha_y_inicial_regulares = 89.5;
$altura_linha_regulares = 10.2;
$linha_atual_regulares = 0;

foreach ($disciplinas_regulares as $disciplina_nome_template) {
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));
    
    $medias_existentes = array_filter([
        $nota_encontrada['media_1'] ?? null, 
        $nota_encontrada['media_2'] ?? null, 
        $nota_encontrada['media_3'] ?? null, 
        $nota_encontrada['media_4'] ?? null
    ], 'is_numeric');

    $media_anual_numerica = count($medias_existentes) > 0 ? array_sum($medias_existentes) / count($medias_existentes) : '--';

    $y_pos = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares);

    // MESMAS coordenadas X da secretaria para cada coluna
    $pdf->SetXY(55, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_1']) ? number_format($nota_encontrada['media_1'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(87, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_2']) ? number_format($nota_encontrada['media_2'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(120, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_3']) ? number_format($nota_encontrada['media_3'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(153, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_4']) ? number_format($nota_encontrada['media_4'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);

    $pdf->SetXY(186, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(is_numeric($media_anual_numerica) ? number_format($media_anual_numerica, 1) : '--', 'ISO-8859-1', 'UTF-8'), 0, 1);

    $linha_atual_regulares++;
}

// 5. Preenchimento da Tabela de Disciplinas Extracurriculares - EXATAMENTE como na secretaria
$disciplinas_extracurriculares = ['Musica', 'Educacao Financeira', 'Robotica', 'Socioemocional'];

$y_inicial_extracurricular = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares) + 18;

if (($y_inicial_extracurricular + 30) > 280) {
    $pdf->AddPage();
    $pdf->useTemplate($templateId, 0, 0, 210, 297);
    $y_inicial_extracurricular = 20;
}

$y_pos_extracurricular_current = $y_inicial_extracurricular;

foreach ($disciplinas_extracurriculares as $disciplina_nome_template) {
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));
    
    $conceito_1 = getConcept($nota_encontrada['media_1'] ?? null);
    $conceito_2 = getConcept($nota_encontrada['media_2'] ?? null);
    $conceito_3 = getConcept($nota_encontrada['media_3'] ?? null);
    $conceito_4 = getConcept($nota_encontrada['media_4'] ?? null);

    // MESMAS coordenadas X da secretaria para conceitos
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(57, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_1, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(88, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_2, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(121, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_3, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(155, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_4, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    
    $y_pos_extracurricular_current += 11 - 1; 
}

// FINALIZAÇÃO - EXATAMENTE como na secretaria
$pdf->Output("I", mb_convert_encoding("boletim_{$aluno['aluno']}.pdf", 'ISO-8859-1', 'UTF-8'));
exit;
?>