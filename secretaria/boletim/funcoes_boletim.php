<?php
// Note que este arquivo não produz nenhuma saída. Ele apenas define uma função.

function gerarPaginaBoletim(&$pdf, $pdo, $aluno_id, $aluno_info, $notas) {
    // Adiciona a página e o template do boletim
    $pdf->AddPage('P', 'A4');
    $templateBoletimPath = 'template_boletim.pdf'; // Caminho relativo ao script mestre
    if (!file_exists($templateBoletimPath)) {
        die("Erro: Template do boletim ('template_boletim.pdf') não encontrado.");
    }
    $pdf->setSourceFile($templateBoletimPath);
    $tplIdBoletim = $pdf->importPage(1);
    $pdf->useTemplate($tplIdBoletim, 0, 0, 210, 297);

    // Preenche o cabeçalho
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(27, 47);
    $pdf->Cell(0, 10, mb_convert_encoding(" {$aluno_info['nome_aluno']}", 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetXY(152, 47);
    $pdf->Cell(0, 10, mb_convert_encoding(" {$aluno_info['nome_turma']}", 'ISO-8859-1', 'UTF-8'), 0, 1);

    // *** COLE AQUI TODA A LÓGICA DE PREENCHIMENTO DAS TABELAS DO BOLETIM ***
    // ------------------------------------------------------------------
// 1. PREENCHIMENTO DO CABEÇALHO DO ALUNO
// ------------------------------------------------------------------
$pdf->SetFont('Arial', '', 12);
$pdf->SetXY(27, 47); // Posição no template para nome do aluno
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno_info['nome_aluno']}", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(152, 47); // Posição no template para nome da turma
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno_info['nome_turma']}", 'ISO-8859-1', 'UTF-8'), 0, 1);


// ------------------------------------------------------------------
// 2. LOOP PARA DISCIPLINAS REGULARES
// ------------------------------------------------------------------
$disciplinas_regulares = [
    'Matemática', 'Português', 'Ciências', 'Ensino Religioso', 'História', 
    'Geografia', 'Artes', 'Inglês', 'Ed. Física', 'Filosofia'
]; 

// Definição das coordenadas da tabela no template
$linha_y_inicial_regulares = 87;
$altura_linha_regulares = 10.2;
$x_col_media1_regulares = 55;
$x_col_media2_regulares = 87; 
$x_col_media3_regulares = 120; 
$x_col_media4_regulares = 153; 
$x_col_media_anual_regulares = 186;

$linha_atual_regulares = 0;

foreach ($disciplinas_regulares as $disciplina_nome_template) {
    // Encontra a nota correspondente no array de notas
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));
    
    // Calcula a média anual e o conceito
    $medias_existentes = array_filter([
        $nota_encontrada['media_1'] ?? null, 
        $nota_encontrada['media_2'] ?? null, 
        $nota_encontrada['media_3'] ?? null, 
        $nota_encontrada['media_4'] ?? null
    ], 'is_numeric');
    
    $media_anual = count($medias_existentes) ? number_format(array_sum($medias_existentes) / count($medias_existentes), 1) : '--';
    $conceito_anual = getConcept($media_anual);

    // Calcula a posição Y da linha atual
    $y_pos = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares); 

    // Posiciona e escreve cada nota na sua respectiva coluna
    $pdf->SetXY($x_col_media1_regulares, $y_pos); 
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_1']) ? number_format($nota_encontrada['media_1'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY($x_col_media2_regulares, $y_pos); 
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_2']) ? number_format($nota_encontrada['media_2'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY($x_col_media3_regulares, $y_pos); 
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_3']) ? number_format($nota_encontrada['media_3'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY($x_col_media4_regulares, $y_pos); 
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_4']) ? number_format($nota_encontrada['media_4'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    // Escreve a Média Anual com conceito
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($x_col_media_anual_regulares, $y_pos); 
    $pdf->Cell(0, 5, mb_convert_encoding("{$media_anual} ({$conceito_anual})", 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $linha_atual_regulares++;
}


// ------------------------------------------------------------------
// 3. LOOP PARA DISCIPLINAS EXTRACURRICULARES
// ------------------------------------------------------------------
$disciplinas_extracurriculares = [
    'Musica', 'Socioemocional', 'Robotica', 'Educacao Financeira'
];

$y_inicial_extracurricular = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares) + 15;

// Desenha o título e o cabeçalho da nova tabela
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(20, $y_inicial_extracurricular);
$pdf->Cell(0, 7, mb_convert_encoding('Disciplinas Extracurriculares', 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX(20);
$pdf->Cell(50, 7, mb_convert_encoding('Disciplina', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('1ª Un.', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('2ª Un.', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('3ª Un.', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('4ª Un.', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(30, 7, mb_convert_encoding('Média (Conceito)', 'ISO-8859-1', 'UTF-8'), 1, 1);
$pdf->SetFont('Arial', '', 10);

$altura_linha_extracurricular = 7;

foreach ($disciplinas_extracurriculares as $disciplina_nome_template) {
    // Encontra a nota correspondente
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));

    // Calcula a média e o conceito
    $medias_existentes = array_filter([
        $nota_encontrada['media_1'] ?? null, 
        $nota_encontrada['media_2'] ?? null, 
        $nota_encontrada['media_3'] ?? null, 
        $nota_encontrada['media_4'] ?? null
    ], 'is_numeric');

    $media_anual = count($medias_existentes) ? number_format(array_sum($medias_existentes) / count($medias_existentes), 1) : '--';
    $conceito_anual = getConcept($media_anual);

    // Desenha a linha da tabela
    $pdf->SetX(20);
    $pdf->Cell(50, $altura_linha_extracurricular, mb_convert_encoding($disciplina_nome_template, 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell(20, $altura_linha_extracurricular, mb_convert_encoding(isset($nota_encontrada['media_1']) ? number_format($nota_encontrada['media_1'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(20, $altura_linha_extracurricular, mb_convert_encoding(isset($nota_encontrada['media_2']) ? number_format($nota_encontrada['media_2'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(20, $altura_linha_extracurricular, mb_convert_encoding(isset($nota_encontrada['media_3']) ? number_format($nota_encontrada['media_3'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(20, $altura_linha_extracurricular, mb_convert_encoding(isset($nota_encontrada['media_4']) ? number_format($nota_encontrada['media_4'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(30, $altura_linha_extracurricular, mb_convert_encoding("{$media_anual} ({$conceito_anual})", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}

    // (Os loops foreach para disciplinas regulares e extracurriculares)
    
    // IMPORTANTE: Não há "$pdf->Output()" aqui. A função apenas desenha no objeto PDF.
}
?>