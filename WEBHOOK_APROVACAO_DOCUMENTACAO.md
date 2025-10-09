# Webhook de Aprovação - Documentação

## Visão Geral
O sistema agora possui dois webhooks distintos:

1. **Webhook de Envio de Dados** (`webhook_url`): Enviado quando o botão "Enviar para WhatsApp" é clicado
2. **Webhook de Aprovação** (`webhook_aprovacao_url`): Enviado quando um pré-cadastro é aprovado

## Webhook de Aprovação

### Quando é Enviado
- Quando um pré-cadastro é aprovado pela secretaria ou financeiro
- Após a atualização do status do aluno para "aprovado" no banco de dados
- Apenas se a URL do webhook estiver configurada

### Estrutura do Payload

```json
{
  "evento": "pre_cadastro_aprovado",
  "timestamp": "2025-01-27 14:30:00",
  "aluno": {
    "id": 123,
    "nome": "João Silva",
    "nome_completo": "João da Silva Santos",
    "cpf": "123.456.789-00",
    "rg": "1234567",
    "data_nascimento": "2010-05-15",
    "sexo": "M",
    "endereco": "Rua das Flores",
    "numero": "123",
    "complemento": "Apto 45",
    "bairro": "Centro",
    "cidade": "Petrolina",
    "estado": "PE",
    "cep": "56300-000",
    "telefone1": "(87) 99999-9999",
    "telefone2": "(87) 88888-8888",
    "email": "joao@email.com",
    "naturalidade": "Petrolina",
    "naturalidade_estado": "PE",
    "nis": "12345678901",
    "tipo_sanguineo": "A",
    "fator_rh": "+",
    "nome_mae": "Maria da Silva",
    "cpf_mae": "987.654.321-00",
    "nome_pai": "José da Silva",
    "cpf_pai": "111.222.333-44",
    "alergias": "Nenhuma",
    "medicamentos": "Nenhum",
    "observacoes_medicas": "Nenhuma",
    "nome_resp_legal": "Maria da Silva",
    "cpf_resp_legal": "987.654.321-00",
    "profissao_resp_legal": "Professora",
    "grau_parentesco_resp_legal": "Mãe",
    "local_trabalho_resp_legal": "Escola Municipal",
    "status_cadastro": "aprovado"
  },
  "turma": {
    "id": 5,
    "nome": "6º Ano A",
    "ano_letivo": "2025"
  },
  "processo": {
    "criado_por": "Secretária Ana",
    "criado_em": "2025-01-20 10:00:00",
    "aprovado_em": "2025-01-27 14:30:00",
    "link_expiracao": "https://exemplo.com/pre-cadastro/abc123",
    "observacoes": "Cadastro completo e aprovado"
  }
}
```

### Headers HTTP
- `Content-Type: application/json`
- `User-Agent: Sistema-Escolar/1.0`

### Resposta Esperada
- **Status 200-299**: Sucesso
- **Outros status**: Falha (será logada)

### Configuração
1. Acesse **Configurações Avançadas** na secretaria ou financeiro
2. Preencha o campo **"URL do Webhook (Aprovação)"**
3. Salve as configurações
4. O webhook será enviado automaticamente nas próximas aprovações

### Logs
- Sucessos são logados como: `"Webhook de aprovação enviado com sucesso para aluno ID: 123"`
- Falhas são logadas com detalhes do erro HTTP e resposta

### Tratamento de Erros
- Se o webhook falhar, a aprovação ainda será processada
- O usuário verá uma mensagem indicando se o webhook foi enviado ou não
- Erros são logados para debugging

## Exemplo de Implementação do Receptor

```php
<?php
// webhook_receptor.php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data['evento'] === 'pre_cadastro_aprovado') {
    $aluno = $data['aluno'];
    $turma = $data['turma'];
    $processo = $data['processo'];
    
    // Processar aprovação
    echo json_encode(['status' => 'success', 'message' => 'Aprovação processada']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Evento não reconhecido']);
}
?>
```
