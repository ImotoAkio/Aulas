<?php
/**
 * GERADOR DE BOLETIM ESCOLAR EM PDF
 * * Finalidade:
 * Este script é responsável por gerar um boletim escolar em formato PDF para um aluno específico.
 * Ele busca os dados do aluno e suas notas no banco de dados, preenche um template PDF
 * pré-existente e exibe o resultado no navegador.
 *
 * Seções do Boletim:
 * 1. Cabeçalho com informações do aluno.
 * 2. Tabela de Disciplinas Regulares com notas numéricas por unidade e média anual.
 * 3. Tabela de Disciplinas Extracurriculares com notas em formato de conceito por unidade.
 *
 * Parâmetro GET Esperado:
 * - aluno_id: O ID numérico do aluno para o qual o boletim será gerado.
 * Exemplo de URL: .../gerar_boletim.php?aluno_id=42
 *
 * Dependências:
 * - Biblioteca FPDF: para a criação de PDF.
 * - Biblioteca FPDI: para usar um PDF existente como template.
 * - partials/db.php: arquivo que contém a conexão com o banco de dados ($pdo).
 */

// --- CONFIGURAÇÃO INICIAL E DEPENDÊNCIAS ---

// Estas linhas forçam a exibição de todos os erros do PHP. 
// São extremamente úteis para depuração, mas devem ser comentadas ou removidas
// quando o sistema estiver em produção para não expor detalhes técnicos aos usuários.
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Inclui as bibliotecas necessárias para a geração do PDF.
// 'require' é usado porque o script não pode funcionar sem elas.
require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php'; // Carrega a biblioteca para usar templates
require_once __DIR__ . '/../config/database.php';    // Inclui a conexão com o banco de dados ($pdo)

// Informa ao PHP que usaremos a classe 'Fpdi' do namespace 'setasign\Fpdi'.
// Isso nos permite escrever 'new Fpdi()' em vez do nome completo da classe.
use setasign\Fpdi\Fpdi;


// --- CAPTURA E VALIDAÇÃO DOS PARÂMETROS DE ENTRADA ---

// Pega o 'aluno_id' da URL (ex: ?aluno_id=42) de forma segura.
// FILTER_VALIDATE_INT garante que o valor seja um número inteiro.
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);

// Se o 'aluno_id' não for fornecido ou não for um número válido, o script para.
if (!$aluno_id) {
    die("Aluno não especificado. Por favor, forneça um 'aluno_id' na URL.");
}


// --- FUNÇÕES AUXILIARES ---

/**
 * Converte uma nota numérica em um conceito (letra).
 * @param float|string|null $media A média numérica a ser convertida.
 * @return string O conceito correspondente ('A', 'B', 'C', 'D', 'F') ou '--' se a nota não for válida.
 */
function getConcept($media) {
    // Se a média for nula, não for um número, OU for exatamente zero, retorne '--'.
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
    } else { // Somente notas válidas (ex: 1 a 5.9) serão 'F'
        return 'F';
    }
}


// --- LÓGICA DE BANCO DE DADOS ---

// O bloco try...catch é uma forma segura de executar operações de banco de dados.
// Se qualquer consulta falhar, o script para de forma controlada no bloco 'catch'.
try {
    // 1. Busca as informações básicas do aluno e sua turma.
    $stmt = $pdo->prepare("SELECT a.nome AS aluno, t.nome AS turma FROM alunos a JOIN turmas t ON a.turma_id = t.id WHERE a.id = :aluno_id");
    // bindParam é uma medida de segurança que previne SQL Injection.
    $stmt->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
    $stmt->execute();
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se a consulta não retornar nenhum aluno, o script para.
    if (!$aluno) {
        die("Aluno não encontrado no banco de dados.");
    }

    // 2. Busca TODAS as notas do aluno para TODAS as disciplinas.
    // Esta é a consulta correta e simplificada para a sua estrutura de dados.
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
    // fetchAll() pega todos os resultados da consulta e os armazena no array $notas.
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se qualquer operação dentro do 'try' falhar, este bloco é executado.
    error_log("Erro ao buscar dados para o boletim: " . $e->getMessage());
    die("Erro ao carregar dados do boletim. Por favor, contate o suporte técnico.");
}


// --- GERAÇÃO DO DOCUMENTO PDF ---

// 1. Inicialização do PDF
$pdf = new Fpdi(); // Cria o objeto principal para o PDF.
$pdf->AddPage();  // Adiciona a primeira (e única) página ao documento.

// 2. Carregamento do Template
$templatePath = 'template_boletim.pdf'; // O nome do seu arquivo de layout.
if (!file_exists($templatePath)) {
    die("Erro fatal: Template PDF do boletim ('template_boletim.pdf') não encontrado.");
}
$pdf->setSourceFile($templatePath);      // Define qual PDF será usado como base.
$templateId = $pdf->importPage(1);      // Importa a primeira página do template.
$pdf->useTemplate($templateId, 0, 0, 210, 297); // "Estampa" o template na página criada.

// 3. Preenchimento do Cabeçalho
$pdf->SetFont('Arial', '', 12); // Define a fonte para o texto que será escrito.
// SetXY move o "cursor" de escrita para uma coordenada (X, Y) específica na página.
$pdf->SetXY(27, 47);
// Cell() desenha uma "célula" de texto. mb_convert_encoding garante que acentos funcionem.
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno['aluno']}", 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetXY(152, 47);
$pdf->Cell(0, 10, mb_convert_encoding(" {$aluno['turma']}", 'ISO-8859-1', 'UTF-8'), 0, 1);


// 4. Preenchimento da Tabela de Disciplinas Regulares
$disciplinas_regulares = [
    'Matemática', 'Português', 'Ciências', 'Ensino Religioso', 'História',
    'Geografia', 'Artes', 'Inglês', 'Ed. Física', 'Filosofia'
];

// Coordenadas e dimensões da tabela, baseadas no seu template.
$linha_y_inicial_regulares = 89.5;
$altura_linha_regulares = 10.2;
$linha_atual_regulares = 0;

// Este loop passará por cada uma das disciplinas na lista $disciplinas_regulares.
foreach ($disciplinas_regulares as $disciplina_nome_template) {
    // Para cada disciplina (ex: 'Matemática'), ele procura no array $notas a linha correspondente.
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));
    
    // Coleta apenas as notas que existem (não são nulas) para calcular a média anual.
    $medias_existentes = array_filter([
        $nota_encontrada['media_1'] ?? null, 
        $nota_encontrada['media_2'] ?? null, 
        $nota_encontrada['media_3'] ?? null, 
        $nota_encontrada['media_4'] ?? null
    ], 'is_numeric');

    // Calcula a média anual. Se não houver notas, o resultado é '--'.
    $media_anual_numerica = count($medias_existentes) > 0 ? array_sum($medias_existentes) / count($medias_existentes) : '--';

    // Calcula a posição Y (vertical) da linha atual na tabela do PDF.
    $y_pos = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares);

    // Escreve as 4 notas numéricas nas suas respectivas colunas (X, Y).
    // number_format() garante que a nota tenha sempre uma casa decimal.
    $pdf->SetXY(55, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_1']) ? number_format($nota_encontrada['media_1'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(87, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_2']) ? number_format($nota_encontrada['media_2'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(120, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_3']) ? number_format($nota_encontrada['media_3'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);
    
    $pdf->SetXY(153, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(isset($nota_encontrada['media_4']) ? number_format($nota_encontrada['media_4'], 1) : '---', 'ISO-8859-1', 'UTF-8'), 0, 1);

    // Escreve a média anual final.
    $pdf->SetXY(186, $y_pos);
    $pdf->Cell(0, 5, mb_convert_encoding(is_numeric($media_anual_numerica) ? number_format($media_anual_numerica, 1) : '--', 'ISO-8859-1', 'UTF-8'), 0, 1);

    $linha_atual_regulares++; // Incrementa o contador para a próxima linha.
}


// 5. Preenchimento da Tabela de Disciplinas Extracurriculares
$disciplinas_extracurriculares = ['Musica', 'Educacao Financeira', 'Robotica', 'Socioemocional'];

// Calcula a posição Y inicial para esta nova tabela, com um espaçamento de 18mm.
$y_inicial_extracurricular = $linha_y_inicial_regulares + ($linha_atual_regulares * $altura_linha_regulares) + 18;

// Lógica para evitar que a tabela seja cortada no final da página.
if (($y_inicial_extracurricular + 30) > 280) {
    $pdf->AddPage();
    $pdf->useTemplate($templateId, 0, 0, 210, 297);
    $y_inicial_extracurricular = 20; // Se criar nova página, começa no topo.
}

$y_pos_extracurricular_current = $y_inicial_extracurricular;

// Este loop funciona de forma similar ao anterior, mas com uma finalidade diferente.
foreach ($disciplinas_extracurriculares as $disciplina_nome_template) {
    $nota_encontrada = current(array_filter($notas, fn($n) => $n['disciplina'] === $disciplina_nome_template));
    
    // A principal diferença: em vez de exibir a nota numérica, ele chama a função getConcept
    // para cada uma das quatro notas, transformando-as em letras (A, B, C, D, F).
    $conceito_1 = getConcept($nota_encontrada['media_1'] ?? null);
    $conceito_2 = getConcept($nota_encontrada['media_2'] ?? null);
    $conceito_3 = getConcept($nota_encontrada['media_3'] ?? null);
    $conceito_4 = getConcept($nota_encontrada['media_4'] ?? null);

    // Escreve os 4 conceitos nas suas respectivas colunas.
    $pdf->SetFont('Arial', 'B', 12); // Usa negrito para destacar os conceitos.
    $pdf->SetXY(57, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_1, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(88, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_2, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(121, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_3, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
    $pdf->SetXY(155, $y_pos_extracurricular_current);
    $pdf->Cell(15, 11, mb_convert_encoding($conceito_4, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    
    // Atualiza a posição Y para a próxima linha da tabela de extracurriculares.
    $y_pos_extracurricular_current += 11 - 1; 
}


// --- FINALIZAÇÃO ---

// Envia o PDF pronto para o navegador.
// "I" significa "Inline": tenta exibir o PDF diretamente na aba do navegador.
// "D" significaria "Download": forçaria o download do arquivo.
$pdf->Output("I", mb_convert_encoding("boletim_{$aluno['aluno']}.pdf", 'ISO-8859-1', 'UTF-8'));
exit; // Encerra o script para garantir que nada mais seja enviado.