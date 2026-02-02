<?php
/**
 * gerar_pdf_parecer_lote.php
 *
 * Este script gera um único PDF contendo os pareceres pedagógicos de TODOS os alunos
 * de uma turma e unidade específicas.
 *
 * Parâmetros GET esperados:
 * - id_turma: ID da turma.
 * - unidade: Unidade (1, 2, 3, 4).
 */

require __DIR__ . '/../../fpdf/fpdf.php';
require __DIR__ . '/../../fpdi/src/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use setasign\Fpdi\Fpdi;

$id_turma = filter_input(INPUT_GET, 'id_turma', FILTER_VALIDATE_INT);
$unidade = filter_input(INPUT_GET, 'unidade', FILTER_SANITIZE_STRING);

if (!$id_turma || empty($unidade)) {
    die("Parâmetros 'id_turma' ou 'unidade' ausentes.");
}

// 1. Obter informações da Turma e Ano Letivo (Período)
try {
    $stmt_turma = $pdo->prepare("SELECT nome, ano_letivo FROM turmas WHERE id = :id_turma");
    $stmt_turma->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
    $stmt_turma->execute();
    $turma_info = $stmt_turma->fetch(PDO::FETCH_ASSOC);

    if (!$turma_info) {
        die("Turma não encontrada.");
    }
    $periodo = $turma_info['ano_letivo']; // O período é o ano letivo
    $nome_turma = $turma_info['nome'];

} catch (PDOException $e) {
    die("Erro ao carregar informações da turma: " . $e->getMessage());
}

// 2. Obter todos os alunos da turma
try {
    $stmt_alunos = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = :id_turma ORDER BY nome");
    $stmt_alunos->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
    $stmt_alunos->execute();
    $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

    if (empty($alunos)) {
        die("Nenhum aluno encontrado nesta turma.");
    }
} catch (PDOException $e) {
    die("Erro ao carregar alunos: " . $e->getMessage());
}

// Função auxiliar (mesma de gerar_pdf_parecer.php)
function getVencedorPDF($votos_campo)
{
    if (empty($votos_campo) || array_sum($votos_campo) === 0) {
        return "N/A (Sem votos)";
    }
    arsort($votos_campo);
    $max_votos = reset($votos_campo);
    $vencedores = [];
    foreach ($votos_campo as $opcao => $contagem) {
        if ($contagem === $max_votos) {
            $vencedores[] = trim($opcao);
        }
    }
    if (count($vencedores) > 1) {
        return "EMPATE: " . implode(", ", $vencedores);
    }
    return trim(reset($vencedores));
}

// Inicializar PDF
$pdf = new Fpdi();
$templatePath = 'teste.pdf'; // Mesmo template

if (!file_exists($templatePath)) {
    die("Erro fatal: Template PDF não encontrado em {$templatePath}.");
}

$pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);

// 3. Iterar sobre cada aluno e gerar o parecer
foreach ($alunos as $aluno) {
    $id_aluno = $aluno['id'];
    $nome_aluno = $aluno['nome'];

    // --- Lógica copiada e adaptada de gerar_pdf_parecer.php ---

    // Obter pareceres individuais
    $todos_pareceres_do_aluno_no_periodo = [];
    $intervencoes_salvas = '';
    $resultado_final_salvo = '';
    $unidade_parecer_principal = '';

    try {
        // Nota: Filtramos também pela UNIDADE selecionada para garantir que estamos pegando o parecer correto
        // Mas o script original filtrava apenas por periodo. Se a unidade for específica, devemos filtrar?
        // O pedido diz "baixar os pareceres pedagógicos... de a turma completa".
        // Geralmente o parecer é por unidade/período.
        // O script original usa 'periodo' (ano letivo).
        // Vamos manter a lógica de buscar por periodo, mas filtrar pela unidade se necessário?
        // O parecer.php cria pareceres com unidade.
        // O gerar_pdf_parecer.php original busca por periodo e id_aluno, e pega o primeiro finalizado.
        // Se houver múltiplas unidades no mesmo ano, isso pode ser um problema no script original.
        // Mas aqui o usuário selecionou uma UNIDADE específica no form.
        // Então vamos filtrar por UNIDADE também.

        $stmt_pareceres = $pdo->prepare(
            "SELECT p.*, u.nome AS nome_professor
             FROM pareceres p
             JOIN usuarios u ON p.id_professor_designado = u.id
             WHERE p.id_aluno = :id_aluno 
               AND p.periodo = :periodo
               AND p.unidade = :unidade
             ORDER BY u.nome"
        );
        $stmt_pareceres->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
        $stmt_pareceres->bindParam(':periodo', $periodo, PDO::PARAM_STR);
        $stmt_pareceres->bindParam(':unidade', $unidade, PDO::PARAM_STR);
        $stmt_pareceres->execute();
        $todos_pareceres_do_aluno_no_periodo = $stmt_pareceres->fetchAll(PDO::FETCH_ASSOC);

        // Se não houver pareceres para este aluno nesta unidade, pular ou gerar página em branco com aviso?
        // Vamos gerar a página com aviso se não houver dados, para manter a consistência da turma.

        foreach ($todos_pareceres_do_aluno_no_periodo as $p) {
            if ($p['status'] === 'finalizado_coordenador') {
                $intervencoes_salvas = $p['intervencoes'];
                $resultado_final_salvo = $p['resultado_final'];
                $unidade_parecer_principal = $p['unidade'];
                break;
            }
        }

    } catch (PDOException $e) {
        // Log erro e continua para o próximo aluno
        error_log("Erro ao carregar pareceres para aluno {$id_aluno}: " . $e->getMessage());
        continue;
    }

    // Apuração de votos
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

    // --- GERAÇÃO DA PÁGINA DO ALUNO ---
    $pdf->AddPage();
    $pdf->useTemplate($templateId, 0, 0, 210, 297);
    $pdf->SetFont('Arial', '', 10);

    // Cabeçalho
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(98, 11);
    $pdf->Cell(0, 5, mb_convert_encoding($nome_aluno, 'ISO-8859-1', 'UTF-8'), 0, 1);

    $pdf->SetXY(95, 22);
    $pdf->Cell(0, 5, mb_convert_encoding($nome_turma, 'ISO-8859-1', 'UTF-8'), 0, 1);

    $pdf->SetXY(124, 32);
    $pdf->Cell(0, 5, mb_convert_encoding($unidade . '°', 'ISO-8859-1', 'UTF-8'), 0, 1);

    // Observações
    $pdf->SetY(65);
    $y_current_for_obs = $pdf->GetY();

    if (!empty($todos_pareceres_do_aluno_no_periodo)) {
        foreach ($todos_pareceres_do_aluno_no_periodo as $parecer_individual) {
            if (($y_current_for_obs + 20) > 280) {
                $pdf->AddPage();
                $pdf->useTemplate($templateId, 0, 0, 210, 297);
                $y_current_for_obs = 20;
                $pdf->SetY($y_current_for_obs);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(0, 5, mb_convert_encoding('Continuação das Observações - ' . $nome_aluno, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                $pdf->Ln(5);
                $y_current_for_obs = $pdf->GetY();
            }

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetXY(10, 200); // Posição fixa para observações? Não, isso parece errado no original se tiver múltiplos.
            // No original: $pdf->SetXY(10, 200); estava fixo?
            // Revisitando o original:
            // $pdf->SetXY(10, 200); -> Isso sobrescreve sempre na mesma posição?
            // Ah, no original parecia ter um erro ou era intencional para uma área específica?
            // Espera, no original:
            // $pdf->SetXY(10, 200);
            // $pdf->MultiCell(...)
            // $y_current_for_obs = $pdf->GetY();
            // $pdf->SetXY(10, $y_current_for_obs);

            // Isso parece bugado no original se tiver mais de um professor.
            // Vou corrigir aqui para usar fluxo contínuo.

            $pdf->SetXY(10, $y_current_for_obs); // Usar Y dinâmico
            $pdf->MultiCell(170, 4, mb_convert_encoding('Professor: ' . $parecer_individual['nome_professor'], 'ISO-8859-1', 'UTF-8'), 0, 'J');

            $pdf->SetFont('Arial', '', 12);
            $obs_decodificada = html_entity_decode($parecer_individual['obs_geral_professor'] ?: 'Nenhuma observação.', ENT_QUOTES, 'UTF-8');
            $pdf->MultiCell(90, 4, mb_convert_encoding('Observações Gerais: ' . $obs_decodificada . "\n\n", 'ISO-8859-1', 'UTF-8'), 0, 'J');

            $y_current_for_obs = $pdf->GetY();
        }
    } else {
        $pdf->SetX(20);
        $pdf->MultiCell(170, 5, mb_convert_encoding('Nenhuma avaliação encontrada para este aluno nesta unidade.', 'ISO-8859-1', 'UTF-8'), 0, 'J');
    }

    // Preenchimento dos X's (Copiado e mantido igual)
    $x_disposicao = 10.5;
    $pdf->SetXY($x_disposicao, 86.5);
    if ($votos_apurados_gerais['disposicao_aula'] == 'facilidade')
        $pdf->Text($x_disposicao, 86.5, 'X');
    $pdf->SetXY($x_disposicao, 92);
    if ($votos_apurados_gerais['disposicao_aula'] == 'dificuldade')
        $pdf->Text($x_disposicao, 92, 'X');
    $pdf->SetXY($x_disposicao, 94);
    if ($votos_apurados_gerais['disposicao_aula'] == 'interesse')
        $pdf->Text($x_disposicao, 94, 'X');
    $pdf->SetXY($x_disposicao, 102.5);
    if ($votos_apurados_gerais['disposicao_aula'] == 'desinteresse')
        $pdf->Text($x_disposicao, 102.5, 'X');

    $x_desempenho_geral = 58;
    $pdf->SetXY($x_desempenho_geral, 123);
    if ($votos_apurados_gerais['desempenho_geral'] == 'acima')
        $pdf->Text($x_desempenho_geral, 123, 'X');
    $pdf->SetXY($x_desempenho_geral, 130);
    if ($votos_apurados_gerais['desempenho_geral'] == 'dentro')
        $pdf->Text($x_desempenho_geral, 130, 'X');
    $pdf->SetXY($x_desempenho_geral, 136);
    if ($votos_apurados_gerais['desempenho_geral'] == 'abaixo')
        $pdf->Text($x_desempenho_geral, 136, 'X');

    $x_comportamento = 114.5;
    $pdf->SetXY($x_comportamento, 90);
    if ($votos_apurados_gerais['comportamento'] == 'colaborativo')
        $pdf->Text($x_comportamento, 90, 'X');
    $pdf->SetXY($x_comportamento, 95);
    if ($votos_apurados_gerais['comportamento'] == 'agressivo')
        $pdf->Text($x_comportamento, 95, 'X');
    $pdf->SetXY($x_comportamento, 100);
    if ($votos_apurados_gerais['comportamento'] == 'retraido')
        $pdf->Text($x_comportamento, 100, 'X');
    $pdf->SetXY($x_comportamento, 106);
    if ($votos_apurados_gerais['comportamento'] == 'proativo')
        $pdf->Text($x_comportamento, 106, 'X');

    $y_participacao = 113;
    $x_participacao_ativamente = 131;
    $x_participacao_pouco = 165.5;
    $pdf->SetXY($x_participacao_ativamente, $y_participacao);
    if ($votos_apurados_gerais['participacao_grupo'] == 'ativamente')
        $pdf->Text($x_participacao_ativamente, $y_participacao, 'X');
    $pdf->SetXY($x_participacao_pouco, $y_participacao);
    if ($votos_apurados_gerais['participacao_grupo'] == 'pouco')
        $pdf->Text($x_participacao_pouco, $y_participacao, 'X');

    $x_respeito_sim = 178.7;
    $y_respeito_sim = 118.5;
    $x_respeito_nao = 115;
    $y_respeito_nao = 124;
    if ($votos_apurados_gerais['respeito_regras'] == 'sim' || strpos($votos_apurados_gerais['respeito_regras'], 'EMPATE') === 0) {
        $pdf->SetXY($x_respeito_sim, $y_respeito_sim);
        $pdf->Text($x_respeito_sim, $y_respeito_sim, 'X');
    } elseif ($votos_apurados_gerais['respeito_regras'] == 'nao') {
        $pdf->SetXY($x_respeito_nao, $y_respeito_nao);
        $pdf->Text($x_respeito_nao, $y_respeito_nao, 'X');
    }

    $x_postura_ativ = 115;
    $pdf->SetXY($x_postura_ativ, 158.5);
    if ($votos_apurados_gerais['postura_atividades'] == 'seguranca')
        $pdf->Text($x_postura_ativ, 158.5, 'X');
    $pdf->SetXY($x_postura_ativ, 164);
    if ($votos_apurados_gerais['postura_atividades'] == 'inseguranca')
        $pdf->Text($x_postura_ativ, 164, 'X');
    $pdf->SetXY($x_postura_ativ, 169.5);
    if ($votos_apurados_gerais['postura_atividades'] == 'autonomia')
        $pdf->Text($x_postura_ativ, 169.5, 'X');
    $pdf->SetXY($x_postura_ativ, 174.5);
    if ($votos_apurados_gerais['postura_atividades'] == 'dependencia')
        $pdf->Text($x_postura_ativ, 174.5, 'X');
    $pdf->SetXY($x_postura_ativ, 179.5);
    if ($votos_apurados_gerais['postura_atividades'] == 'neutra')
        $pdf->Text($x_postura_ativ, 179.5, 'X');

    $x_postura_resiliencia = 115;
    $x_postura_frustracao = 115;
    $x_postura_flexibilidade = 115;
    $x_postura_aceitacao = 115;
    $pdf->SetXY($x_postura_resiliencia, 190);
    if ($votos_apurados_gerais['postura_desafios'] == 'resiliencia')
        $pdf->Text($x_postura_resiliencia, 190, 'X');
    $pdf->SetXY($x_postura_frustracao, 196);
    if ($votos_apurados_gerais['postura_desafios'] == 'frustracao')
        $pdf->Text($x_postura_frustracao, 196, 'X');
    $pdf->SetXY($x_postura_flexibilidade, 201);
    if ($votos_apurados_gerais['postura_desafios'] == 'flexibilidade')
        $pdf->Text($x_postura_flexibilidade, 201, 'X');
    $pdf->SetXY($x_postura_aceitacao, 206.5);
    if ($votos_apurados_gerais['postura_desafios'] == 'aceitacao')
        $pdf->Text($x_postura_aceitacao, 206.5, 'X');

    // Intervenções
    $pdf->SetXY(111, 220);
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(90, 5, mb_convert_encoding($intervencoes_salvas ?: 'Nenhuma intervenção registrada.', 'ISO-8859-1', 'UTF-8'), 0, 'J');

    // Resultado Final
    $pdf->SetXY(10, 143);
    $pdf->SetFont('Arial', '', 12);
    $texto_conclusivo_apenas = $resultado_final_salvo;
    $marker_start = "Parecer Conclusivo da Secretaria/Coordenação:\n";
    $pos_start = strrpos($resultado_final_salvo, $marker_start);
    if ($pos_start !== false) {
        $texto_conclusivo_apenas = substr($resultado_final_salvo, $pos_start + strlen($marker_start));
        $texto_conclusivo_apenas = trim($texto_conclusivo_apenas);
    }
    $pdf->MultiCell(90, 6, mb_convert_encoding($texto_conclusivo_apenas ?: 'Nenhum resultado final registrado.', 'ISO-8859-1', 'UTF-8'), 0, 'J');

}

// Saída do PDF
$nome_arquivo = "pareceres_turma_{$nome_turma}_unidade_{$unidade}.pdf";
$pdf->Output("I", mb_convert_encoding($nome_arquivo, 'ISO-8859-1', 'UTF-8'));
