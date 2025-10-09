<?php
/**
 * COMPARADOR DE ESTRUTURA DE BANCO
 * Compara banco local vs produção e gera SQL de sincronização
 */

require_once 'config/database.php';

class DatabaseComparator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Compara estrutura completa
     */
    public function compararEstrutura() {
        echo "<h2>🔍 COMPARAÇÃO DE ESTRUTURA DO BANCO</h2>";
        
        // Estrutura esperada (baseada no código atual)
        $estrutura_esperada = $this->getEstruturaEsperada();
        
        // Estrutura atual
        $estrutura_atual = $this->getEstruturaAtual();
        
        // Comparar e gerar SQL
        $this->gerarSQLSincronizacao($estrutura_esperada, $estrutura_atual);
    }
    
    /**
     * Retorna estrutura esperada
     */
    private function getEstruturaEsperada() {
        return [
            'tabelas' => [
                'alunos' => [
                    'colunas' => [
                        'id' => 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
                        'nome' => 'VARCHAR(255) NOT NULL',
                        'nome_completo' => 'VARCHAR(255) NULL',
                        'cpf' => 'VARCHAR(14) NULL',
                        'rg' => 'VARCHAR(20) NULL',
                        'data_nascimento' => 'DATE NULL',
                        'sexo' => "ENUM('M','F') NULL",
                        'endereco' => 'VARCHAR(255) NULL',
                        'numero' => 'VARCHAR(10) NULL',
                        'complemento' => 'VARCHAR(100) NULL',
                        'bairro' => 'VARCHAR(100) NULL',
                        'cidade' => 'VARCHAR(100) NULL',
                        'estado' => 'VARCHAR(2) NULL',
                        'cep' => 'VARCHAR(10) NULL',
                        'telefone1' => 'VARCHAR(20) NULL',
                        'telefone2' => 'VARCHAR(20) NULL',
                        'email' => 'VARCHAR(255) NULL',
                        'naturalidade' => 'VARCHAR(100) NULL',
                        'naturalidade_estado' => 'VARCHAR(2) NULL',
                        'nis' => 'VARCHAR(20) NULL',
                        'tipo_sanguineo' => 'VARCHAR(5) NULL',
                        'fator_rh' => 'VARCHAR(3) NULL',
                        'nome_mae' => 'VARCHAR(255) NULL',
                        'cpf_mae' => 'VARCHAR(14) NULL',
                        'nome_pai' => 'VARCHAR(255) NULL',
                        'cpf_pai' => 'VARCHAR(14) NULL',
                        'nome_resp_legal' => 'VARCHAR(255) NULL',
                        'cpf_resp_legal' => 'VARCHAR(14) NULL',
                        'profissao_resp_legal' => 'VARCHAR(100) NULL',
                        'grau_parentesco_resp_legal' => 'VARCHAR(50) NULL',
                        'local_trabalho_resp_legal' => 'VARCHAR(255) NULL',
                        'alergias' => 'TEXT NULL',
                        'medicamentos' => 'TEXT NULL',
                        'observacoes_medicas' => 'TEXT NULL',
                        'turma_id' => 'BIGINT UNSIGNED NULL',
                        'status_cadastro' => "ENUM('pre_cadastro', 'completo', 'aprovado') NOT NULL DEFAULT 'pre_cadastro'"
                    ]
                ],
                'usuarios' => [
                    'colunas' => [
                        'id' => 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
                        'nome' => 'VARCHAR(255) NOT NULL',
                        'email' => 'VARCHAR(255) NOT NULL UNIQUE',
                        'senha' => 'VARCHAR(255) NOT NULL',
                        'tipo' => "ENUM('professor', 'coordenador', 'secretaria', 'financeiro') NOT NULL DEFAULT 'professor'"
                    ]
                ],
                'pre_cadastros_controle' => [
                    'colunas' => [
                        'id' => 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
                        'aluno_id' => 'BIGINT UNSIGNED NOT NULL',
                        'codigo_pre_cadastro' => 'VARCHAR(32) NOT NULL UNIQUE',
                        'link_expiracao' => 'DATETIME NOT NULL',
                        'status' => "ENUM('pendente', 'completo', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente'",
                        'criado_por' => 'BIGINT UNSIGNED NOT NULL',
                        'criado_em' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                        'dados_preenchidos_em' => 'DATETIME NULL',
                        'preenchido_por_responsavel' => 'BOOLEAN NOT NULL DEFAULT FALSE',
                        'observacoes' => 'TEXT NULL'
                    ]
                ],
                'configuracoes_sistema' => [
                    'colunas' => [
                        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
                        'chave' => 'VARCHAR(100) NOT NULL UNIQUE',
                        'valor' => 'TEXT NULL',
                        'descricao' => 'TEXT NULL',
                        'criado_em' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                        'atualizado_em' => 'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP'
                    ]
                ],
                'mensalidades' => [
                    'colunas' => [
                        'id' => 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
                        'aluno_id' => 'BIGINT UNSIGNED NOT NULL',
                        'competencia' => 'CHAR(7) NOT NULL',
                        'valor_original' => 'DECIMAL(10,2) NOT NULL',
                        'desconto' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
                        'acrescimos' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
                        'valor_final' => 'DECIMAL(10,2) NOT NULL',
                        'vencimento' => 'DATE NOT NULL',
                        'status' => "ENUM('gerada','enviada','paga','pendente','atrasada','cancelada') NOT NULL DEFAULT 'gerada'",
                        'boleto_nosso_numero' => 'VARCHAR(50) NULL',
                        'pix_txid' => 'VARCHAR(70) NULL',
                        'gateway_charge_id' => 'VARCHAR(80) NULL',
                        'criado_em' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                        'atualizado_em' => 'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP'
                    ]
                ]
            ]
        ];
        
        return $estrutura_esperada;
    }
    
    /**
     * Retorna estrutura atual do banco
     */
    private function getEstruturaAtual() {
        $estrutura = ['tabelas' => []];
        
        // Buscar todas as tabelas
        $stmt = $this->pdo->query("SHOW TABLES");
        $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tabelas as $tabela) {
            $estrutura['tabelas'][$tabela] = ['colunas' => []];
            
            // Buscar colunas de cada tabela
            $stmt = $this->pdo->query("DESCRIBE `$tabela`");
            $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($colunas as $coluna) {
                $tipo = $coluna['Type'];
                if ($coluna['Null'] === 'NO') {
                    $tipo .= ' NOT NULL';
                }
                if ($coluna['Key'] === 'PRI') {
                    $tipo .= ' PRIMARY KEY';
                }
                if ($coluna['Extra'] === 'auto_increment') {
                    $tipo .= ' AUTO_INCREMENT';
                }
                if ($coluna['Default'] !== null) {
                    $tipo .= " DEFAULT '{$coluna['Default']}'";
                }
                
                $estrutura['tabelas'][$tabela]['colunas'][$coluna['Field']] = $tipo;
            }
        }
        
        return $estrutura;
    }
    
    /**
     * Gera SQL de sincronização
     */
    private function gerarSQLSincronizacao($esperada, $atual) {
        echo "<h3>📝 SQL de Sincronização Gerado:</h3>";
        
        $sql = [];
        
        // Verificar tabelas
        foreach ($esperada['tabelas'] as $tabela => $info) {
            if (!isset($atual['tabelas'][$tabela])) {
                // Tabela não existe, criar
                $sql[] = $this->gerarSQLCriarTabela($tabela, $info);
            } else {
                // Tabela existe, verificar colunas
                $sql_colunas = $this->gerarSQLAdicionarColunas($tabela, $info['colunas'], $atual['tabelas'][$tabela]['colunas']);
                $sql = array_merge($sql, $sql_colunas);
            }
        }
        
        // Mostrar SQL
        echo "<textarea style='width: 100%; height: 300px; font-family: monospace;'>";
        echo "-- SQL DE SINCRONIZAÇÃO DO BANCO DE DADOS\n";
        echo "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($sql as $comando) {
            echo $comando . ";\n";
        }
        
        echo "</textarea>";
        
        echo "<br><div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ IMPORTANTE:</h4>";
        echo "<ol>";
        echo "<li>Faça backup do banco antes de executar este SQL</li>";
        echo "<li>Teste primeiro em ambiente de desenvolvimento</li>";
        echo "<li>Execute comando por comando se necessário</li>";
        echo "<li>Verifique se não há dados importantes que serão perdidos</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    /**
     * Gera SQL para criar tabela
     */
    private function gerarSQLCriarTabela($tabela, $info) {
        $sql = "CREATE TABLE IF NOT EXISTS `$tabela` (\n";
        
        $colunas = [];
        foreach ($info['colunas'] as $coluna => $definicao) {
            $colunas[] = "  `$coluna` $definicao";
        }
        
        $sql .= implode(",\n", $colunas);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        return $sql;
    }
    
    /**
     * Gera SQL para adicionar colunas
     */
    private function gerarSQLAdicionarColunas($tabela, $colunas_esperadas, $colunas_atuais) {
        $sql = [];
        
        foreach ($colunas_esperadas as $coluna => $definicao) {
            if (!isset($colunas_atuais[$coluna])) {
                $sql[] = "ALTER TABLE `$tabela` ADD COLUMN `$coluna` $definicao";
            }
        }
        
        return $sql;
    }
}

// Executar comparação se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'comparar_banco.php') {
    $comparator = new DatabaseComparator();
    $comparator->compararEstrutura();
}
?>
