<?php
// funcoes_parecer.php

/**
 * Esta função apura o resultado de uma votação entre várias opções.
 * É uma função auxiliar para o parecer.
 * @param array $votos_campo Array associativo com as opções e suas contagens.
 * @return string A opção vencedora ou uma string indicando empate/sem votos.
 */
function getVencedorParecer($votos_campo) {
    if (empty($votos_campo) || array_sum($votos_campo) === 0) {
        return "N/A";
    }
    arsort($votos_campo); // Ordena do maior para o menor
    $max_votos = reset($votos_campo);
    $vencedores = [];
    foreach ($votos_campo as $opcao => $contagem) {
        if ($contagem === $max_votos) {
            $vencedores[] = trim($opcao);
        }
    }
    if (count($vencedores) > 1) {
        return "EMPATE";
    }
    return trim(reset($vencedores));
}

/**
 * Gera a página do parecer pedagógico em um objeto PDF existente.
 * * @param Fpdi &$pdf O objeto PDF mestre, passado por referência.
 * @param PDO $pdo A conexão com o banco de dados.
 * @param int $aluno_id O ID do aluno.
 * @param string $periodo O período do parecer a ser gerado.
 * @param array $aluno_info Array com 'nome_aluno' e 'nome_turma', já buscado.
 */
function gerarPaginaParecer(&$pdf, $pdo, $aluno_id, $periodo, $aluno_info) {
    // Adiciona uma nova página ao documento PDF existente
    $pdf->AddPage('P', 'A4');

    // 1. BUSCA DE DADOS ESPECÍFICOS DO PARECER
    $stmt_pareceres = $pdo->prepare(
        "SELECT p.*, u.nome AS nome_professor
         FROM pareceres p
         JOIN usuarios u ON p.id_professor_designado = u.id
         WHERE p.id_aluno = :id_aluno AND p.periodo = :periodo
         ORDER BY u.nome"
    );
    $stmt_pareceres->execute([':aluno_id' => $aluno_id, ':periodo' => $periodo]);
    $todos_pareceres = $stmt_pareceres->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. APURAÇÃO DOS VOTOS E PROCESSAMENTO DOS DADOS
    $contadores_gerais = [
        'disposicao_aula'    => ['facilidade' => 0, 'dificuldade' => 0, 'interesse' => 0, 'desinteresse' => 0],
        'desempenho_geral'   => ['acima' => 0, 'dentro' => 0, 'abaixo' => 0],
        'comportamento'      => ['colaborativo' => 0, 'agressivo' => 0, 'retraido' => 0, 'proativo' => 0],
        'participacao_grupo' => ['ativamente' => 0, 'pouco' => 0],
        'respeito_regras'    => ['sim' => 0, 'nao' => 0],
        'postura_atividades' => ['seguranca' => 0, 'inseguranca' => 0, 'autonomia' => 0, 'dependencia' => 0, 'neutra' => 0],
        'postura_desafios'   => ['resiliencia' => 0, 'frustracao' => 0, 'flexibilidade' => 0, 'aceitacao' => 0]
    ];
    $intervencoes_salvas = '';
    $resultado_final_salvo = '';
    $unidade_parecer_principal = '';

    foreach ($todos_pareceres as $p) {
        foreach ($contadores_gerais as $campo => $opcoes) {
            if (isset($p[$campo]) && array_key_exists($p[$campo], $contadores_gerais[$campo])) {
                $contadores_gerais[$campo][$p[$campo]]++;
            }
        }
        if ($p['status'] === 'finalizado_coordenador') {
            $intervencoes_salvas = $p['intervencoes'];
            $resultado_final_salvo = $p['resultado_final'];
            $unidade_parecer_principal = $p['unidade'];
        }
    }

    $votos_apurados_gerais = [];
    foreach ($contadores_gerais as $campo => $votos) {
        $votos_apurados_gerais[$campo] = getVencedorParecer($votos);
    }

    // 3. CARREGAMENTO E PREENCHIMENTO DO TEMPLATE PDF
    $templateParecerPath = 'teste.pdf'; // Verifique se o caminho está correto
    if (!file_exists($templateParecerPath)) {
        die("Erro: Template do parecer ('teste.pdf') não encontrado.");
    }
    $pdf->setSourceFile($templateParecerPath);
    $tplIdParecer = $pdf->importPage(1);
    $pdf->useTemplate($tplIdParecer, 0, 0, 210, 297);

    // Preenchimento do Cabeçalho do Parecer
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(98, 11); $pdf->Cell(0, 5, mb_convert_encoding($aluno_info['nome_aluno'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetXY(95, 22); $pdf->Cell(0, 5, mb_convert_encoding($aluno_info['nome_turma'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->SetXY(124, 32); $pdf->Cell(0, 5, mb_convert_encoding(($unidade_parecer_principal ? $unidade_parecer_principal . 'ª Unidade' : $periodo), 'ISO-8859-1', 'UTF-8'), 0, 1);

    // Preenchimento dos "X"s com base nos votos
    $pdf->SetFont('Arial', 'B', 12);
    // (A lógica completa de if/else para marcar os 'X's com $pdf->Text(X, Y, 'X') vai aqui)
    // ...

    // Preenchimento dos Textos Longos
    // (Observações individuais dos professores, se desejar)
    $pdf->SetY(65); // Posição inicial para o primeiro bloco de observação
    // ... (Loop para imprimir observações individuais) ...

    // Preenchimento dos Campos Finais
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(111, 220);
    $pdf->MultiCell(90, 5, mb_convert_encoding($intervencoes_salvas ?: 'Nenhuma intervenção registrada.', 'ISO-8859-1', 'UTF-8'), 0, 'J');
    
    // Lógica para extrair e preencher o parecer conclusivo
    $texto_conclusivo_apenas = $resultado_final_salvo;
    $marker_start = "Parecer Conclusivo da Secretaria/Coordenação:\n";
    $pos_start = strrpos($resultado_final_salvo, $marker_start);
    if ($pos_start !== false) {
        $texto_conclusivo_apenas = trim(substr($resultado_final_salvo, $pos_start + strlen($marker_start)));
    }
    $pdf->SetXY(10, 143);
    $pdf->MultiCell(90, 6, mb_convert_encoding($texto_conclusivo_apenas ?: 'Nenhum resultado final registrado.', 'ISO-8859-1', 'UTF-8'), 0, 'J');

    // IMPORTANTE: Esta função não chama $pdf->Output(). Ela apenas modifica o objeto $pdf.
}
?>