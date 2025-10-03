<?php
/**
 * gerar_boletins_turma.php
 *
 * Gera um arquivo ZIP contendo os boletins em PDF para todos os alunos de uma turma específica.
 *
 * Lógica Atualizada:
 * - Utiliza a consulta SQL consolidada para lidar com dados de notas fragmentados.
 * - Separa a exibição de disciplinas regulares (notas numéricas) e extracurriculares (conceitos).
 * - Gera um PDF individual para cada aluno e os compacta para download.
 *
 * Parâmetro GET esperado:
 * - turma_id: O ID numérico da turma.
 */

// --- CONFIGURAÇÃO E DEPENDÊNCIAS ---
require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php';
require('../../partials/db.php'); // O caminho do seu arquivo de conexão pode ser diferente

use setasign\Fpdi\Fpdi;

// --- FUNÇÃO AUXILIAR PARA CONCEITOS ---

/**
 * Converte uma nota numérica em um conceito (letra).
 * @param float|string|null $media A média numérica a ser convertida.
 * @return string O conceito correspondente ('A', 'B', 'C', 'D', 'F') ou '--' se a nota não for válida.
 */
function getConcept($media) {
    if ($media === null || !is_numeric($media) || $media == 0) {
        return '--';
    }
    if ($media >= 9) return 'A';
    if ($media >= 8) return 'B';
    if ($media >= 7) return 'C';
    if ($media >= 6) return 'D';
    return 'F';
}


// --- LÓGICA PRINCIPAL ---

// 1. Captura e validação do parâmetro de entrada
$turma_id = filter_input(INPUT_GET, 'turma_id', FILTER_VALIDATE_INT);

if (!$turma_id) {
    die("ID da turma não especificado ou inválido.");
}

// 2. Busca informações da turma e a lista de alunos
try {
    $stmt_turma_info = $pdo->prepare("SELECT nome, ano_letivo FROM turmas WHERE id = ?");
    $stmt_turma_info->execute([$turma_id]);
    $turma_info = $stmt_turma_info->fetch(PDO::FETCH_ASSOC);

    if (!$turma_info) {
        die("Turma não encontrada.");
    }

    $stmt_alunos = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = ? ORDER BY nome ASC");
    $stmt_alunos->execute([$turma_id]);
    $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

    if (empty($alunos)) {
        die("Nenhum aluno encontrado para a turma especificada.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados da turma ou alunos: " . $e->getMessage());
}


// 3. Cria um diretório temporário para os PDFs individuais
$tempDir = sys_get_temp_dir() . '/boletins_' . uniqid();
if (!mkdir($tempDir, 0777, true)) {
    die('Falha ao criar o diretório temporário.');
}


// --- LOOP DE GERAÇÃO DE PDFS ---
// Gera um boletim para cada aluno da turma

foreach ($alunos as $aluno) {
    $aluno_id = $aluno['id'];

    // a. Busca as notas do aluno com a consulta SQL consolidada
    try {
        $stmt_notas = $pdo->prepare("
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
        $stmt_notas->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
        $stmt_notas->execute();
        $notas = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Pula este aluno se houver erro, mas continua o processo
        error_log("Erro ao buscar notas para o aluno ID {$aluno_id}: " . $e->getMessage());
        continue;
    }

    // b. Cria a instância do PDF para o aluno atual
    $pdf = new Fpdi();
    $pdf->AddPage();

    // c. Carrega o template
    $templatePath = 'template_boletim.pdf'; // Certifique-se que o template está no caminho correto
    if (!file_exists($templatePath)) {
        die("Erro fatal: Template PDF '{$templatePath}' não encontrado.");
    }
    $pdf->setSourceFile($templatePath);
    $templateId = $pdf->importPage(1);
    $pdf->useTemplate($templateId, 0, 0, 210, 297);

    // d. Preenche o cabeçalho do boletim
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(27, 47);
    $pdf->Cell(0, 10, mb_convert_encoding($aluno['nome'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetXY(152, 47);
    $pdf->Cell(0, 10, mb_convert_encoding($turma_info['nome'], 'ISO-8859-1', 'UTF-8'), 0, 1);

    // e. Define as listas de disciplinas
    $disciplinas_regulares = [
        'Matemática', 'Português', 'Ciências', 'Ensino Religioso', 'História',
        'Geografia', 'Artes', 'Inglês', 'Ed. Física', 'Filosofia'
    ];
    $disciplinas_extracurriculares = ['Musica', 'Socioemocional', 'Robotica', 'Educacao Financeira'];

    // f. Preenche a tabela de Disciplinas Regulares (NOTAS NUMÉRICAS)
    $y_pos_regulares = 89.5;
    $altura_linha = 10.2;
    $linha_atual = 0;

    foreach ($disciplinas_regulares as $disciplina_nome) {
        $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome));
        
        $medias_existentes = array_filter([
            $nota_encontrada['media_1'] ?? null, $nota_encontrada['media_2'] ?? null,
            $nota_encontrada['media_3'] ?? null, $nota_encontrada['media_4'] ?? null
        ], 'is_numeric');

        $media_anual = count($medias_existentes) > 0 ? array_sum($medias_existentes) / count($medias_existentes) : '--';
        
        $y_pos = $y_pos_regulares + ($linha_atual * $altura_linha);

        $pdf->SetXY(55, $y_pos);
        $pdf->Cell(0, 5, isset($nota_encontrada['media_1']) ? number_format($nota_encontrada['media_1'], 1) : '---', 0, 1);
        $pdf->SetXY(87, $y_pos);
        $pdf->Cell(0, 5, isset($nota_encontrada['media_2']) ? number_format($nota_encontrada['media_2'], 1) : '---', 0, 1);
        $pdf->SetXY(120, $y_pos);
        $pdf->Cell(0, 5, isset($nota_encontrada['media_3']) ? number_format($nota_encontrada['media_3'], 1) : '---', 0, 1);
        $pdf->SetXY(153, $y_pos);
        $pdf->Cell(0, 5, isset($nota_encontrada['media_4']) ? number_format($nota_encontrada['media_4'], 1) : '---', 0, 1);
        $pdf->SetXY(186, $y_pos);
        $pdf->Cell(0, 5, is_numeric($media_anual) ? number_format($media_anual, 1) : '--', 0, 1);
        
        $linha_atual++;
    }

    // g. Preenche a tabela de Disciplinas Extracurriculares (CONCEITOS)
    $y_pos_extracurricular = 211.5; // Posição Y inicial da tabela de extracurriculares no template

    foreach ($disciplinas_extracurriculares as $disciplina_nome) {
        $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome));
        
        $conceito_1 = getConcept($nota_encontrada['media_1'] ?? null);
        $conceito_2 = getConcept($nota_encontrada['media_2'] ?? null);
        $conceito_3 = getConcept($nota_encontrada['media_3'] ?? null);
        $conceito_4 = getConcept($nota_encontrada['media_4'] ?? null);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(57, $y_pos_extracurricular);
        $pdf->Cell(15, 11, $conceito_1, 0, 0, 'C');
        $pdf->SetXY(88, $y_pos_extracurricular);
        $pdf->Cell(15, 11, $conceito_2, 0, 0, 'C');
        $pdf->SetXY(121, $y_pos_extracurricular);
        $pdf->Cell(15, 11, $conceito_3, 0, 0, 'C');
        $pdf->SetXY(155, $y_pos_extracurricular);
        $pdf->Cell(15, 11, $conceito_4, 0, 0, 'C');
        
        $y_pos_extracurricular += 10; // Incrementa a posição Y para a próxima disciplina extracurricular
    }

    // h. Salva o PDF do aluno no diretório temporário
    $safe_aluno_nome = preg_replace('/[^A-Za-z0-9_]/', '_', $aluno['nome']);
    $pdfPath = $tempDir . "/boletim_{$safe_aluno_nome}.pdf";
    $pdf->Output('F', $pdfPath);
}

// --- COMPACTAÇÃO E DOWNLOAD ---

// 4. Compacta todos os PDFs gerados em um arquivo ZIP
$zipPath = $tempDir . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = glob($tempDir . '/*.pdf');
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
} else {
    die("Falha ao criar o arquivo ZIP.");
}

// 5. Fornecer o arquivo ZIP para download
if (file_exists($zipPath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="boletins_' . preg_replace('/[^A-Za-z0-9_]/', '_', $turma_info['nome']) . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($zipPath);
}

// 6. Limpa os arquivos temporários do servidor
$files = glob($tempDir . '/*.pdf');
foreach($files as $file){
    if(is_file($file)) {
        unlink($file);
    }
}
rmdir($tempDir);
unlink($zipPath);

exit;