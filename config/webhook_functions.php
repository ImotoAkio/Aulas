<?php
/**
 * Funções auxiliares para webhooks
 */

/**
 * Envia dados para webhook de aprovação
 */
function enviarWebhookAprovacao($aluno_id, $pdo) {
    try {
        // Função para formatar telefone com código 55
        function formatarTelefoneCom55($telefone) {
            if (empty($telefone)) {
                return null;
            }
            
            // Remover todos os caracteres não numéricos
            $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
            
            // Se já tem código 55, retornar como está
            if (str_starts_with($telefoneLimpo, '55')) {
                return $telefoneLimpo;
            }
            
            // Se tem 11 dígitos (celular com DDD), adicionar 55
            if (strlen($telefoneLimpo) === 11) {
                return '55' . $telefoneLimpo;
            }
            
            // Se tem 10 dígitos (fixo com DDD), adicionar 55
            if (strlen($telefoneLimpo) === 10) {
                return '55' . $telefoneLimpo;
            }
            
            // Para outros casos, retornar como está
            return $telefoneLimpo;
        }
        
        // Buscar URL do webhook de aprovação
        $stmt = $pdo->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = 'webhook_aprovacao_url'");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado || empty($resultado['valor'])) {
            error_log("Webhook de aprovação não configurado");
            return false;
        }
        
        $webhook_url = $resultado['valor'];
        
        // Buscar dados completos do aluno
        $stmt = $pdo->prepare("
            SELECT a.*, t.nome as turma_nome, t.ano_letivo,
                   pc.criado_em, pc.link_expiracao, pc.observacoes,
                   u.nome as criado_por_nome
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            LEFT JOIN pre_cadastros_controle pc ON a.id = pc.aluno_id
            LEFT JOIN usuarios u ON pc.criado_por = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$aluno_id]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aluno) {
            error_log("Aluno não encontrado para webhook de aprovação: " . $aluno_id);
            return false;
        }
        
        // Preparar dados para envio
        $dados_webhook = [
            'evento' => 'pre_cadastro_aprovado',
            'timestamp' => date('Y-m-d H:i:s'),
            'aluno' => [
                'id' => (int)$aluno['id'],
                'nome' => $aluno['nome'] ?? null,
                'nome_completo' => $aluno['nome_completo'] ?? null,
                'cpf' => $aluno['cpf'] ?? null,
                'rg' => $aluno['rg'] ?? null,
                'data_nascimento' => $aluno['data_nascimento'] ?? null,
                'sexo' => $aluno['sexo'] ?? null,
                'endereco' => $aluno['endereco'] ?? null,
                'numero' => $aluno['numero'] ?? null,
                'complemento' => $aluno['complemento'] ?? null,
                'bairro' => $aluno['bairro'] ?? null,
                'cidade' => $aluno['cidade'] ?? null,
                'estado' => $aluno['estado'] ?? null,
                'cep' => $aluno['cep'] ?? null,
                'telefone1' => formatarTelefoneCom55($aluno['telefone1'] ?? null),
                'telefone2' => formatarTelefoneCom55($aluno['telefone2'] ?? null),
                'email' => $aluno['email'] ?? null,
                'naturalidade' => $aluno['naturalidade'] ?? null,
                'naturalidade_estado' => $aluno['naturalidade_estado'] ?? null,
                'nis' => $aluno['nis'] ?? null,
                'tipo_sanguineo' => $aluno['tipo_sanguineo'] ?? null,
                'fator_rh' => $aluno['fator_rh'] ?? null,
                'nome_mae' => $aluno['nome_mae'] ?? null,
                'cpf_mae' => $aluno['cpf_mae'] ?? null,
                'nome_pai' => $aluno['nome_pai'] ?? null,
                'cpf_pai' => $aluno['cpf_pai'] ?? null,
                'alergias' => $aluno['alergias'] ?? null,
                'medicamentos' => $aluno['medicamentos'] ?? null,
                'observacoes_medicas' => $aluno['observacoes_medicas'] ?? null,
                'nome_resp_legal' => $aluno['nome_resp_legal'] ?? null,
                'cpf_resp_legal' => $aluno['cpf_resp_legal'] ?? null,
                'profissao_resp_legal' => $aluno['profissao_resp_legal'] ?? null,
                'grau_parentesco_resp_legal' => $aluno['grau_parentesco_resp_legal'] ?? null,
                'local_trabalho_resp_legal' => $aluno['local_trabalho_resp_legal'] ?? null,
                'status_cadastro' => $aluno['status_cadastro'] ?? null
            ],
            'turma' => [
                'id' => (int)($aluno['turma_id'] ?? 0),
                'nome' => $aluno['turma_nome'] ?? null,
                'ano_letivo' => $aluno['ano_letivo'] ?? null
            ],
            'processo' => [
                'criado_por' => $aluno['criado_por_nome'] ?? null,
                'criado_em' => $aluno['criado_em'] ?? null,
                'aprovado_em' => date('Y-m-d H:i:s'),
                'link_expiracao' => $aluno['link_expiracao'] ?? null,
                'observacoes' => $aluno['observacoes'] ?? null
            ]
        ];
        
        // Enviar via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_webhook));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Sistema-Escolar/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Erro cURL webhook aprovação: " . $error);
            return false;
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            error_log("Webhook de aprovação enviado com sucesso para aluno ID: " . $aluno_id);
            return true;
        } else {
            error_log("Webhook de aprovação falhou. HTTP Code: " . $http_code . ", Response: " . $response);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erro ao enviar webhook de aprovação: " . $e->getMessage());
        return false;
    }
}
?>
