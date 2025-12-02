<?php
require_once '../../config/database.php';
require_once '../../fpdf/fpdf.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['coordenador', 'secretaria'])) {
    die('Acesso negado.');
}

$aluno_id = (int) ($_GET['id'] ?? 0);
if ($aluno_id <= 0) {
    die('ID do aluno inválido.');
}

$pdo = getConnection();

// Buscar dados do aluno
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome, t.ano_letivo
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        die('Aluno não encontrado.');
    }
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

class PDF extends FPDF
{
    function Header()
    {
        // Título Principal
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, mb_convert_encoding('FICHA CADASTRAL DO ALUNO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Subtítulo / Escola (Placeholder)
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, mb_convert_encoding('Sistema de Gestão Escolar', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Linha divisória
        $this->SetLineWidth(0.5);
        $this->Line(10, 28, 200, 28);
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-25);

        // Espaço para assinaturas
        $this->SetFont('Arial', '', 8);
        $this->Cell(60, 0, '', 'T', 0, 'C'); // Linha assinatura 1
        $this->Cell(70, 0, '', 0, 0, 'C');   // Espaço
        $this->Cell(60, 0, '', 'T', 1, 'C'); // Linha assinatura 2

        $this->Ln(2);
        $this->Cell(60, 4, mb_convert_encoding('Assinatura do Responsável', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        $this->Cell(70, 4, '', 0, 0, 'C');
        $this->Cell(60, 4, mb_convert_encoding('Secretaria / Coordenação', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Rodapé padrão
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i'), 0, 0, 'R');
    }

    function SectionTitle($label)
    {
        $this->Ln(4);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230); // Cinza claro
        $this->SetDrawColor(200, 200, 200);
        $this->Cell(0, 8, mb_convert_encoding('  ' . strtoupper($label), 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', true);
        $this->Ln(2);
    }

    function InfoRow($label1, $value1, $label2 = null, $value2 = null)
    {
        $this->SetFont('Arial', 'B', 9);

        // Coluna 1
        $this->Cell(35, 6, mb_convert_encoding($label1 . ':', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);

        // Se tiver segunda coluna, limita a largura da primeira
        $width1 = $label2 ? 60 : 155;
        $this->Cell($width1, 6, mb_convert_encoding($value1, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

        // Coluna 2 (Opcional)
        if ($label2) {
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(35, 6, mb_convert_encoding($label2 . ':', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
            $this->SetFont('Arial', '', 9);
            $this->Cell(60, 6, mb_convert_encoding($value2, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        }

        $this->Ln(6);
        // Linha pontilhada suave para guiar leitura
        $this->SetDrawColor(240, 240, 240);
        $this->Line($this->GetX(), $this->GetY(), 200, $this->GetY());
    }

    function InfoBlock($label, $value)
    {
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(35, 6, mb_convert_encoding($label . ':', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->MultiCell(0, 6, mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8'));
        $this->Ln(1);
    }
}

// Formatação de dados
function formatarData($data)
{
    if (!$data || $data === '0000-00-00')
        return 'Não informado';
    return date('d/m/Y', strtotime($data));
}

function formatarCPF($cpf)
{
    if (!$cpf)
        return 'Não informado';
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatarTelefone($telefone)
{
    if (!$telefone)
        return 'Não informado';
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
    } elseif (strlen($telefone) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }
    return $telefone;
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Espaço para foto 3x4 (Simulado)
$pdf->Rect(165, 35, 30, 40); // x, y, w, h
$pdf->SetXY(165, 53);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 4, 'FOTO 3x4', 0, 0, 'C');
$pdf->SetXY(10, 35); // Retorna cursor

// Dados Pessoais
$pdf->SectionTitle('Dados Pessoais');
// Ajustar largura para não bater na foto
$pdf->SetRightMargin(50);
$pdf->InfoRow('Nome', $aluno['nome']);
$pdf->InfoRow('Data Nasc.', formatarData($aluno['data_nascimento']));
$pdf->InfoRow('CPF', formatarCPF($aluno['cpf']));
$pdf->InfoRow('RG', $aluno['rg'] ?? 'Não informado');
$pdf->InfoRow('Sexo', $aluno['sexo'] ?? 'Não informado');
$pdf->SetRightMargin(10); // Restaura margem
$pdf->Ln(5); // Espaço extra após a área da foto

// Continuar com layout normal (2 colunas quando possível)
$pdf->InfoRow('Naturalidade', ($aluno['naturalidade'] ?? 'Não inf.') . ' - ' . ($aluno['naturalidade_estado'] ?? ''), 'NIS', $aluno['nis'] ?? 'Não inf.');
$pdf->InfoRow('Tipo Sanguíneo', $aluno['tipo_sanguineo'] ?? 'Não inf.', 'Fator RH', $aluno['fator_rh'] ?? 'Não inf.');

// Dados Escolares
$pdf->SectionTitle('Dados Escolares');
$pdf->InfoRow('Turma', $aluno['turma_nome'] ?? 'Não definida', 'Ano Letivo', $aluno['ano_letivo'] ?? 'Não inf.');
$pdf->InfoRow('Status', ucfirst($aluno['status_cadastro']), 'Cód. Pré-cad.', $aluno['codigo_pre_cadastro'] ?? 'Não gerado');

// Informações dos Pais
$pdf->SectionTitle('Filiação');
$pdf->InfoRow('Mãe', $aluno['nome_mae'] ?? 'Não informado', 'CPF Mãe', formatarCPF($aluno['cpf_mae']));
$pdf->InfoRow('Pai', $aluno['nome_pai'] ?? 'Não informado', 'CPF Pai', formatarCPF($aluno['cpf_pai']));

// Responsável Legal
$pdf->SectionTitle('Responsável Legal');
$pdf->InfoRow('Nome', $aluno['nome_responsavel'] ?? 'Não informado', 'CPF', formatarCPF($aluno['cpf_responsavel'] ?? ''));
$pdf->InfoRow('Telefone', formatarTelefone($aluno['telefone_responsavel'] ?? ''), 'Email', $aluno['email_responsavel'] ?? 'Não informado');
$pdf->InfoRow('Profissão', $aluno['profissao_responsavel'] ?? 'Não informado', 'Local Trab.', $aluno['local_trabalho_responsavel'] ?? 'Não informado');

// Endereço
if (!empty($aluno['endereco'])) {
    $pdf->SectionTitle('Endereço');
    $endereco_completo = $aluno['endereco'];
    if (!empty($aluno['numero']))
        $endereco_completo .= ', ' . $aluno['numero'];
    if (!empty($aluno['complemento']))
        $endereco_completo .= ' - ' . $aluno['complemento'];
    if (!empty($aluno['bairro']))
        $endereco_completo .= ' - Bairro: ' . $aluno['bairro'];
    if (!empty($aluno['cidade']))
        $endereco_completo .= ' - ' . $aluno['cidade'];
    if (!empty($aluno['estado']))
        $endereco_completo .= '/' . $aluno['estado'];
    if (!empty($aluno['cep']))
        $endereco_completo .= ' - CEP: ' . $aluno['cep'];

    $pdf->InfoBlock('Endereço', $endereco_completo);
}

// Informações Médicas
$pdf->SectionTitle('Informações Médicas');
$pdf->InfoBlock('Obs. Médicas', $aluno['observacoes_medicas'] ?? 'Nenhuma observação médica registrada');
$pdf->InfoBlock('Medicamentos', $aluno['medicamentos'] ?? 'Nenhum medicamento registrado');
if (!empty($aluno['alergias'])) {
    $pdf->InfoBlock('Alergias', $aluno['alergias']);
}

// Informações Adicionais
$pdf->SectionTitle('Informações Adicionais');
$pdf->InfoBlock('Observações', $aluno['observacoes'] ?? 'Nenhuma observação registrada');

$pdf->Output('I', 'Ficha_Cadastral_' . preg_replace('/[^a-zA-Z0-9]/', '_', $aluno['nome']) . '.pdf');
