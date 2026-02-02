<?php
/**
 * gerar_pdf.php
 *
 * Gera um PDF de recibo utilizando FPDF (2 páginas: cliente e colégio)
 */

// Verificar autenticação
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

// Incluir biblioteca FPDF
require __DIR__ . '/../../fpdf/fpdf.php';

// Obter ID do recibo
$recibo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validar parâmetro
if (!$recibo_id) {
    die("Erro: Parâmetro 'id' ausente ou inválido.");
}

// Obter conexão com o banco de dados
$pdo = getConnection();

try {
    // Buscar dados completos do recibo
    $stmt = $pdo->prepare("
        SELECT 
            r.*, a.nome as aluno_nome, a.nome_completo as aluno_nome_completo, 
            a.cpf as aluno_cpf, a.data_nascimento, a.nome_resp_legal, a.cpf_resp_legal,
            t.nome as turma_nome, t.ano_letivo
        FROM recibos r
        JOIN alunos a ON a.id = r.aluno_id
        LEFT JOIN turmas t ON t.id = a.turma_id
        WHERE r.id = ?
    ");
    $stmt->execute([$recibo_id]);
    $recibo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recibo) {
        die("Erro: Recibo não encontrado.");
    }
    
    // Buscar itens do recibo
    $stmtItens = $pdo->prepare("
        SELECT * FROM recibo_itens 
        WHERE recibo_id = ? 
        ORDER BY ordem ASC
    ");
    $stmtItens->execute([$recibo_id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do recibo: " . $e->getMessage());
    die("Erro ao carregar dados do recibo. Por favor, contate o suporte técnico.");
}

// FUNÇÕES AUXILIARES

/**
 * Limpa e formata CPF
 */
function formatarCPF($cpf) {
    if (empty($cpf)) return '';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

/**
 * Formata data para dd/mm/aaaa
 */
function formatarData($data) {
    if (empty($data)) return '';
    try {
        return date('d/m/Y', strtotime($data));
    } catch (Exception $e) {
        return $data;
    }
}

/**
 * Converte número para extenso
 */
function numeroExtenso($valor) {
    $singular = ["", "", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
    $plural = ["", "", "mil", "milhões", "bilhões", "trilhões", "quatrilhões"];
    
    $c = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    $d = ["", "", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $d10 = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"];
    $u = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];
    
    $rt = '';
    $z = 0;
    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    for ($i = 0; $i < count($inteiro); $i++) {
        for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++) {
            $inteiro[$i] = "0" . $inteiro[$i];
        }
    }
    
    $fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
    for ($i = 0; $i < count($inteiro); $i++) {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";
        
        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $t = count($inteiro) - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ($valor == "000") $z++;
        elseif ($z > 0) $z--;
        if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) $r .= (($z > 1) ? " de " : "") . $plural[$t];
        if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
    }
    
    $plural = count($inteiro) - 2;
    if (!$plural) {
        $rt .= " reais";
    } else {
        if ($plural == 1) $rt .= " real";
        else $rt .= " reais";
    }
    
    if ($inteiro[count($inteiro) - 1] > 0) {
        $v = $inteiro[count($inteiro) - 1];
        $rc = (($v > 100) && ($v < 200)) ? "cento" : $c[$v[0]];
        $rd = ($v[1] < 2) ? "" : $d[$v[1]];
        $ru = ($v > 0) ? (($v[1] == 1) ? $d10[$v[2]] : $u[$v[2]]) : "";
        
        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $rt .= $r ? " e " . $r . " centavos" : "";
    }
    
    return strtoupper($rt);
}

/**
 * Converte texto para ISO-8859-1 (necessário para FPDF)
 */
function toISO($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

/**
 * Função para renderizar página do recibo
 */
function renderizarPaginaRecibo($pdf, $recibo, $itens, $logoPath, $via = 'CLIENTE') {
    // Cabecalho (logo e dados do colégio)
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 85, 10, 30); // Logo menor (30mm em vez de 40mm)
        $pdf->Ln(32);
    } else {
        $pdf->Ln(15);
    }
    
    // Cabeçalho - Título do Colégio
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 5, toISO('COLÉGIO ROSA DE SHAROM'), 0, 1, 'C');
    $pdf->Ln(1);
    
    // Informações do Colégio
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 3, toISO('CNPJ: 49.000.772/0001-12'), 0, 1, 'C');
    $pdf->Cell(0, 3, toISO('Código MEC/INEP: 26.168-294'), 0, 1, 'C');
    $pdf->Ln(1);
    $pdf->Cell(0, 3, toISO('Av. 01, nº 86, Quati II, Petrolina – PE'), 0, 1, 'C');
    $pdf->Cell(0, 3, toISO('Contato: (87) 98837-5103 | rosasharom@gmail.com'), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Linha decorativa
    $pdf->SetLineWidth(0.8);
    $pdf->SetDrawColor(70, 70, 70);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(4);
    
    // Título RECIBO com fundo
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, toISO('RECIBO'), 0, 1, 'C', true);
    
    $pdf->Ln(3);
    
    // Número do recibo - destaque
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(50, 50, 150);
    $pdf->Cell(0, 6, toISO('Nº ' . ($recibo['numero_recibo'] ?? 'REC-' . str_pad($recibo['id'], 6, '0', STR_PAD_LEFT))), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Via (cliente ou colégio)
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, toISO('VIA: ' . $via), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Corpo do recibo - Recebemos de
    $pdf->SetFont('Arial', '', 11);
    $texto = 'Recebemos de ' . $recibo['nome_resp_legal'] . ' a importância de R$ ' . number_format($recibo['valor_final'], 2, ',', '.');
    $pdf->MultiCell(0, 7, toISO($texto), 0, 'J');
    $pdf->Ln(2);
    
    // Valor por extenso com destaque
    $valor_extenso = numeroExtenso($recibo['valor_final']);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(50, 50, 150);
    $pdf->MultiCell(0, 7, toISO(strtoupper($valor_extenso)), 0, 'J');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Referente a com ícone visual
    $pdf->SetDrawColor(100, 150, 200);
    $pdf->SetLineWidth(0.5);
    $pdf->SetFillColor(245, 250, 255);
    $pdf->Rect(15, $pdf->GetY(), 165, 8, 'FD');
    
    $pdf->SetFont('Arial', '', 10);
    $tipo_nomes = [
        'mensalidade' => 'Mensalidade',
        'fardamento' => 'Fardamento',
        'atividade' => 'Atividade Escolar',
        'matricula' => 'Matrícula'
    ];
    $tipo_nome = $tipo_nomes[$recibo['tipo']] ?? $recibo['tipo'];
    $ref = '';
    if ($recibo['referencia']) {
        $ref = ' - ' . $recibo['referencia'];
    }
    if ($recibo['descricao']) {
        $ref .= ': ' . $recibo['descricao'];
    }
    $texto_ref = '  Referente a: ' . $tipo_nome . $ref;
    $pdf->Text(20, $pdf->GetY() + 6, toISO($texto_ref));
    $pdf->Ln(10);
    
        // Tabela de itens (se houver)
    if (count($itens) > 0) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 6, toISO('Detalhamento dos Itens'), 0, 1);
        $pdf->Ln(2);
        
        // Cabeçalho da tabela estilizado
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(70, 120, 200);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(90, 7, toISO('Item'), 1, 0, 'L', true);
        $pdf->Cell(25, 7, toISO('Qtd'), 1, 0, 'C', true);
        $pdf->Cell(35, 7, toISO('Unitário'), 1, 0, 'R', true);
        $pdf->Cell(0, 7, toISO('Total'), 1, 1, 'R', true);
        
        // Linhas dos itens com zebra
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 8);
        $zebra = false;
        foreach ($itens as $item) {
            if ($zebra) {
                $pdf->SetFillColor(248, 248, 248);
                $fill = true;
            } else {
                $fill = false;
            }
            $zebra = !$zebra;
            
            $descricao = toISO($item['descricao']);
            if (strlen($descricao) > 50) {
                $descricao = substr($descricao, 0, 47) . '...';
            }
            
            $pdf->Cell(90, 6, $descricao, 1, 0, 'L', $fill);
            $pdf->Cell(25, 6, number_format($item['quantidade'], 0, ',', '.'), 1, 0, 'C', $fill);
            $pdf->Cell(35, 6, 'R$ ' . number_format($item['valor_unitario'], 2, ',', '.'), 1, 0, 'R', $fill);
            $pdf->Cell(0, 6, 'R$ ' . number_format($item['valor_total'], 2, ',', '.'), 1, 1, 'R', $fill);
        }
        
        $pdf->Ln(3);
    }
    
    // Dados do aluno em box destacado
    $pdf->Ln(2);
    $y_aluno = $pdf->GetY();
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetFillColor(250, 250, 250);
    $pdf->Rect(15, $y_aluno, 165, 15, 'FD');
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text(20, $y_aluno + 7, toISO('Aluno: ' . $recibo['aluno_nome']));
    
    if ($recibo['turma_nome']) {
        $pdf->Text(20, $y_aluno + 13, toISO('Turma: ' . $recibo['turma_nome'] . ($recibo['ano_letivo'] ? ' - ' . $recibo['ano_letivo'] : '')));
    }
    $pdf->Ln(15);
    
    // Data e assinatura
    $pdf->Ln(8);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, toISO('Petrolina, ' . date('d') . ' de ' . getMesPorExtenso(date('m')) . ' de ' . date('Y')), 0, 1);
    $pdf->Ln(12);
    
    // Assinatura
    $pdf->SetDrawColor(100, 100, 100);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY(), 170, $pdf->GetY());
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, toISO('Secretaria Escolar'), 0, 1, 'C');
    $pdf->Cell(0, 6, toISO('Colégio Rosa de Sharom'), 0, 1, 'C');
    
    // Observações (apenas na via do colégio)
    if ($via === 'COLÉGIO' && $recibo['observacoes']) {
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, toISO('Observações: ' . $recibo['observacoes']), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
    }
}

function getMesPorExtenso($mes) {
    $meses = [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
        '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
        '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
        '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ];
    return $meses[$mes] ?? $mes;
}

// GERAÇÃO DO DOCUMENTO PDF

try {
    // Inicialização do PDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    
    // Logo path
    $logoPath = __DIR__ . '/../../assets/images/logo.png';
    
    // PÁGINA 1: VIA DO CLIENTE
    $pdf->AddPage();
    renderizarPaginaRecibo($pdf, $recibo, $itens, $logoPath, 'CLIENTE');
    
    // PÁGINA 2: VIA DO COLÉGIO
    $pdf->AddPage();
    renderizarPaginaRecibo($pdf, $recibo, $itens, $logoPath, 'COLÉGIO');
    
    // Output do PDF
    $pdf->Output('I', 'Recibo_' . ($recibo['numero_recibo'] ?? $recibo['id']) . '.pdf');
    
} catch (Exception $e) {
    error_log("Erro ao gerar PDF: " . $e->getMessage());
    die("Erro ao gerar o PDF do recibo. Por favor, contate o suporte técnico.");
}
?>
