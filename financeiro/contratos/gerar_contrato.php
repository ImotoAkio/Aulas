<?php
/**
 * gerar_contrato.php
 *
 * Este script gera um PDF de contrato de matrícula utilizando a biblioteca FPDF e FPDI
 * para preencher um template PDF com os dados do aluno e responsável.
 *
 * Parâmetros GET esperados:
 * - aluno_id: ID do aluno para gerar o contrato.
 */

// Configuração de erros (remover em produção)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Verificar autenticação
session_start();
require_once '../../config/database.php';

// Verificar se o usuário está logado e é financeiro
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'financeiro') {
    redirectTo('login.php');
}

// Incluir bibliotecas FPDF e FPDI
require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

// Obter ID do aluno
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);

// Validar parâmetro
if (!$aluno_id) {
    die("Erro: Parâmetro 'aluno_id' ausente ou inválido.");
}

// Obter conexão com o banco de dados
$pdo = getConnection();

try {
    // Buscar dados completos do aluno e responsável
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.nome, a.nome_completo, a.cpf, a.rg, a.data_nascimento, a.sexo,
            a.nome_resp_legal, a.cpf_resp_legal, a.rg as rg_resp_legal,
            a.profissao_resp_legal, a.grau_parentesco_resp_legal, a.local_trabalho_resp_legal,
            a.endereco, a.numero, a.complemento, a.bairro, a.cidade, a.estado, a.cep,
            a.telefone1, a.telefone2, a.email,
            a.status_cadastro,
            t.nome as turma_nome, t.ano_letivo,
            pc.turma_futura_id,
            tf.nome as turma_futura_nome, tf.ano_letivo as turma_futura_ano_letivo
        FROM alunos a
        LEFT JOIN turmas t ON a.turma_id = t.id
        LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
        LEFT JOIN turmas tf ON pc.turma_futura_id = tf.id
        WHERE a.id = ?
    ");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        die("Erro: Aluno não encontrado.");
    }
    
    // Verificar se o aluno tem cadastro completo
    if (!in_array($aluno['status_cadastro'], ['completo', 'aprovado'])) {
        die("Erro: Este aluno não possui cadastro completo. Não é possível gerar contrato.");
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do aluno: " . $e->getMessage());
    die("Erro ao carregar dados do contrato. Por favor, contate o suporte técnico.");
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
 * Limpa e formata telefone
 */
function formatarTelefone($telefone) {
    if (empty($telefone)) return '';
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) == 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    return $telefone;
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
 * Converte texto para ISO-8859-1 (necessário para FPDF)
 */
function toISO($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

// GERAÇÃO DO DOCUMENTO PDF

try {
    // 1. Carregamento do Template
    $templatePath = __DIR__ . '/template_contrato.pdf';
    if (!file_exists($templatePath)) {
        die("Erro fatal: Template PDF do contrato não encontrado em: " . $templatePath);
    }
    
    // 2. Inicialização do PDF
    $pdf = new Fpdi();
    
    // 3. Carregar template e obter número de páginas
    $templatePageCount = $pdf->setSourceFile($templatePath);
    
    // 4. IMPORTAR TODAS AS PÁGINAS DO TEMPLATE
    for ($pageNo = 1; $pageNo <= $templatePageCount; $pageNo++) {
        $pdf->AddPage();
        $tplId = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplId, 0, 0, 210, 297);
        
        // Se for a primeira página, preencher os dados
        if ($pageNo == 1) {
    
    // ==========================================
    // 🐛 DEPURAÇÃO VISUAL - DESCOMENTE PARA ATIVAR
    // ==========================================
    // Este código desenha retângulos vermelhos para você visualizar
    // onde os campos estão sendo posicionados.
    // Após ajustar, COMENTE estas linhas novamente!
    
    /*
    $pdf->SetDrawColor(255, 0, 0); // Cor vermelha para os retângulos
    
    // Retângulos dos campos principais (AJUSTE ESTAS COORDENADAS)
    $pdf->Rect(10, 70, 180, 8, 'D');  // Nome do Responsável
    $pdf->Rect(10, 80, 50, 8, 'D');   // CPF do Responsável
    $pdf->Rect(60, 80, 50, 8, 'D');   // Profissão do Responsável
    $pdf->Rect(110, 80, 50, 8, 'D');  // RG do Responsável
    $pdf->Rect(10, 88, 50, 8, 'D');   // Telefone
    $pdf->Rect(60, 88, 120, 8, 'D');  // Email
    $pdf->Rect(10, 96, 180, 8, 'D');  // Endereço completo
    $pdf->Rect(10, 104, 60, 8, 'D');  // Bairro
    $pdf->Rect(10, 112, 100, 8, 'D'); // Cidade/Estado
    $pdf->Rect(10, 135, 180, 8, 'D'); // Nome do Aluno
    $pdf->Rect(10, 145, 100, 8, 'D'); // CPF do Aluno
    $pdf->Rect(10, 155, 180, 8, 'D'); // Turma/Ano Letivo
    */
    
    // 3. Configurar fonte
    $pdf->SetFont('Arial', '', 10);
    
    // 4. DADOS DO RESPONSÁVEL LEGAL
    // Nome do Responsável
    if (!empty($aluno['nome_resp_legal'])) {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(26, 92.5);
        $pdf->Cell(0, 10, toISO($aluno['nome_resp_legal']), 0, 1);
    }
    
    // CPF, Profissão, RG
    $linha_cpf = 97;
    if (!empty($aluno['cpf_resp_legal'])) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(25, $linha_cpf);
        $pdf->Cell(0, 10, toISO(formatarCPF($aluno['cpf_resp_legal'])), 0, 1);
    }
    
    if (!empty($aluno['profissao_resp_legal'])) {
        $pdf->SetXY(85, $linha_cpf);
        $pdf->Cell(0, 10, toISO($aluno['profissao_resp_legal']), 0, 1);
    }
    
    if (!empty($aluno['rg_resp_legal'])) {
        $pdf->SetXY(150, $linha_cpf);
        $pdf->Cell(0, 10, toISO($aluno['rg_resp_legal']), 0, 1);
    }
    
    // Telefone e Email
    $linha_contato = 101;
    if (!empty($aluno['telefone1'])) {
        $pdf->SetXY(25, $linha_contato);
        $pdf->Cell(0, 10, toISO(formatarTelefone($aluno['telefone1'])), 0, 1);
    }
    
    if (!empty($aluno['email'])) {
        $pdf->SetXY(60, $linha_contato);
        $pdf->Cell(0, 10, toISO($aluno['email']), 0, 1);
    }
    
    // Endereço e Número (separados conforme template)
    $linha_endereco = 105;
    // Endereço (rua, avenida, etc)
    if (!empty($aluno['endereco'])) {
        $pdf->SetXY(34, $linha_endereco);
        $pdf->Cell(0, 10, toISO($aluno['endereco']), 0, 1);
    }
    
    // Número (se houver campo separado no template, ajuste a coordenada X aqui)
    // Por enquanto, vou manter na mesma linha mas em posição diferente
    // Você pode ajustar o X para onde está o campo "nº" no template
    if (!empty($aluno['numero'])) {
        $pdf->SetXY(110, $linha_endereco); // Ajuste este X conforme template
        $pdf->Cell(0, 10, toISO($aluno['numero']), 0, 1);
    }
    
    // Complemento (se houver campo separado)
    if (!empty($aluno['complemento'])) {
        $pdf->SetXY(145, $linha_endereco); // Ajuste este X conforme template
        $pdf->Cell(0, 10, toISO($aluno['complemento']), 0, 1);
    }
    
    // Bairro
    if (!empty($aluno['bairro'])) {
        $pdf->SetXY(26, 109.3);
        $pdf->Cell(0, 10, toISO($aluno['bairro']), 0, 1);
    }
    
    // Cidade/Estado
    $cidade_estado = trim(
        ($aluno['cidade'] ?? '') . 
        (!empty($aluno['estado']) ? '/' . $aluno['estado'] : '')
    );
    if (!empty($cidade_estado)) {
        $pdf->SetXY(95, 109.3);
        $pdf->Cell(0, 10, toISO($cidade_estado), 0, 1);
    }
    
    // 5. DADOS DO ALUNO
    // Nome do Aluno
    $nome_aluno_completo = !empty($aluno['nome_completo']) ? $aluno['nome_completo'] : $aluno['nome'];
    if (!empty($nome_aluno_completo)) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(78, 113);
        $pdf->Cell(0, 10, toISO($nome_aluno_completo), 0, 1);
    }
    
    // CPF do Aluno
    if (!empty($aluno['cpf'])) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(145, 117.2);
        $pdf->Cell(0, 10, toISO(formatarCPF($aluno['cpf'])), 0, 1);
    }
    
    // 6. TURMA PARA MATRÍCULA (Turma Futura do Pré-cadastro ou Turma Atual)
    // Priorizar turma futura se existir no pré-cadastro
    if (!empty($aluno['turma_futura_nome'])) {
        $turma_completa = trim(
            $aluno['turma_futura_nome'] .
            ($aluno['turma_futura_ano_letivo'] ? ' - ' . $aluno['turma_futura_ano_letivo'] : '')
        );
    } else {
        // Se não tem turma futura, usar turma atual
        $turma_completa = trim(
            ($aluno['turma_nome'] ?? 'Não definida') .
            ($aluno['ano_letivo'] ? ' - ' . $aluno['ano_letivo'] : '')
        );
    }
    $pdf->SetXY(17, 117.2);
    $pdf->Cell(0, 10, toISO($turma_completa), 0, 1);
    
        } // Fim do if ($pageNo == 1)
    } // Fim do loop de páginas
    
    // 8. OUTPUT DO PDF
    $pdf->Output('I', 'Contrato_Matricula_' . $aluno['nome'] . '.pdf');
    
} catch (Exception $e) {
    error_log("Erro ao gerar contrato: " . $e->getMessage());
    die("Erro ao gerar o PDF do contrato. Por favor, contate o suporte técnico.");
}

?>

