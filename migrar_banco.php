<?php
/**
 * SCRIPT DE MIGRAÇÃO DO BANCO DE DADOS
 * Este script detecta diferenças entre banco local e produção
 * e aplica apenas as mudanças necessárias
 */

require_once 'config/database.php';

class DatabaseMigrator {
    private $pdo;
    private $log = [];
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Executa migração completa
     */
    public function executarMigracao() {
        echo "<h2>🔄 MIGRAÇÃO DO BANCO DE DADOS</h2>";
        echo "<p>Iniciando processo de migração...</p>";
        
        try {
            // 1. Verificar estrutura atual
            $this->verificarEstrutura();
            
            // 2. Criar tabelas necessárias
            $this->criarTabelas();
            
            // 3. Adicionar colunas necessárias
            $this->adicionarColunas();
            
            // 4. Atualizar configurações
            $this->atualizarConfiguracoes();
            
            // 5. Mostrar resumo
            $this->mostrarResumo();
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erro na migração: " . $e->getMessage() . "</div>";
        }
    }
    
    /**
     * Verifica estrutura atual do banco
     */
    private function verificarEstrutura() {
        echo "<h3>🔍 Verificando estrutura atual...</h3>";
        
        $tabelas = [
            'alunos', 'turmas', 'usuarios', 'pre_cadastros_controle', 
            'configuracoes_sistema', 'mensalidades', 'pagamentos'
        ];
        
        foreach ($tabelas as $tabela) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$tabela'");
                $existe = $stmt->fetch();
                
                if ($existe) {
                    echo "✅ Tabela '$tabela' existe<br>";
                } else {
                    echo "⚠️ Tabela '$tabela' NÃO existe<br>";
                }
            } catch (Exception $e) {
                echo "❌ Erro ao verificar tabela '$tabela': " . $e->getMessage() . "<br>";
            }
        }
    }
    
    /**
     * Cria tabelas necessárias
     */
    private function criarTabelas() {
        echo "<h3>🏗️ Criando tabelas necessárias...</h3>";
        
        // Tabela pre_cadastros_controle
        $sql_pre_cadastros = "
        CREATE TABLE IF NOT EXISTS pre_cadastros_controle (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            aluno_id BIGINT UNSIGNED NOT NULL,
            codigo_pre_cadastro VARCHAR(32) NOT NULL UNIQUE,
            link_expiracao DATETIME NOT NULL,
            status ENUM('pendente', 'completo', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
            criado_por BIGINT UNSIGNED NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            dados_preenchidos_em DATETIME NULL,
            preenchido_por_responsavel BOOLEAN NOT NULL DEFAULT FALSE,
            observacoes TEXT NULL,
            UNIQUE KEY uk_aluno_pre_cadastro (aluno_id),
            KEY idx_codigo (codigo_pre_cadastro),
            KEY idx_status (status),
            KEY idx_expiracao (link_expiracao),
            CONSTRAINT fk_pre_cadastro_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            CONSTRAINT fk_pre_cadastro_usuario FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql_pre_cadastros);
            echo "✅ Tabela 'pre_cadastros_controle' criada/verificada<br>";
            $this->log[] = "Tabela pre_cadastros_controle criada/verificada";
        } catch (Exception $e) {
            echo "⚠️ Tabela 'pre_cadastros_controle': " . $e->getMessage() . "<br>";
        }
        
        // Tabela configuracoes_sistema
        $sql_configuracoes = "
        CREATE TABLE IF NOT EXISTS configuracoes_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(100) NOT NULL UNIQUE,
            valor TEXT NULL,
            descricao TEXT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_chave (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql_configuracoes);
            echo "✅ Tabela 'configuracoes_sistema' criada/verificada<br>";
            $this->log[] = "Tabela configuracoes_sistema criada/verificada";
        } catch (Exception $e) {
            echo "⚠️ Tabela 'configuracoes_sistema': " . $e->getMessage() . "<br>";
        }
        
        // Tabela mensalidades
        $sql_mensalidades = "
        CREATE TABLE IF NOT EXISTS mensalidades (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            aluno_id BIGINT UNSIGNED NOT NULL,
            competencia CHAR(7) NOT NULL,
            valor_original DECIMAL(10,2) NOT NULL,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            acrescimos DECIMAL(10,2) NOT NULL DEFAULT 0,
            valor_final DECIMAL(10,2) NOT NULL,
            vencimento DATE NOT NULL,
            status ENUM('gerada','enviada','paga','pendente','atrasada','cancelada') NOT NULL DEFAULT 'gerada',
            boleto_nosso_numero VARCHAR(50) NULL,
            pix_txid VARCHAR(70) NULL,
            gateway_charge_id VARCHAR(80) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_mensalidade (aluno_id, competencia),
            KEY idx_mensalidade_status (status),
            KEY idx_mensalidade_venc (vencimento),
            CONSTRAINT fk_mensalidade_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql_mensalidades);
            echo "✅ Tabela 'mensalidades' criada/verificada<br>";
            $this->log[] = "Tabela mensalidades criada/verificada";
        } catch (Exception $e) {
            echo "⚠️ Tabela 'mensalidades': " . $e->getMessage() . "<br>";
        }
    }
    
    /**
     * Adiciona colunas necessárias
     */
    private function adicionarColunas() {
        echo "<h3>📝 Adicionando colunas necessárias...</h3>";
        
        // Colunas na tabela alunos
        $colunas_alunos = [
            'nome_completo' => 'VARCHAR(255) NULL',
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
            'telefone1' => 'VARCHAR(20) NULL',
            'telefone2' => 'VARCHAR(20) NULL',
            'email' => 'VARCHAR(255) NULL',
            'alergias' => 'TEXT NULL',
            'medicamentos' => 'TEXT NULL',
            'observacoes_medicas' => 'TEXT NULL',
            'status_cadastro' => "ENUM('pre_cadastro', 'completo', 'aprovado') NOT NULL DEFAULT 'pre_cadastro'"
        ];
        
        foreach ($colunas_alunos as $coluna => $definicao) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM alunos LIKE '$coluna'");
                $existe = $stmt->fetch();
                
                if (!$existe) {
                    $sql = "ALTER TABLE alunos ADD COLUMN $coluna $definicao";
                    $this->pdo->exec($sql);
                    echo "✅ Coluna '$coluna' adicionada à tabela 'alunos'<br>";
                    $this->log[] = "Coluna $coluna adicionada à tabela alunos";
                } else {
                    echo "ℹ️ Coluna '$coluna' já existe na tabela 'alunos'<br>";
                }
            } catch (Exception $e) {
                echo "⚠️ Erro ao adicionar coluna '$coluna': " . $e->getMessage() . "<br>";
            }
        }
        
        // Colunas na tabela usuarios
        $colunas_usuarios = [
            'tipo' => "ENUM('professor', 'coordenador', 'secretaria', 'financeiro') NOT NULL DEFAULT 'professor'"
        ];
        
        foreach ($colunas_usuarios as $coluna => $definicao) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE '$coluna'");
                $existe = $stmt->fetch();
                
                if (!$existe) {
                    $sql = "ALTER TABLE usuarios ADD COLUMN $coluna $definicao";
                    $this->pdo->exec($sql);
                    echo "✅ Coluna '$coluna' adicionada à tabela 'usuarios'<br>";
                    $this->log[] = "Coluna $coluna adicionada à tabela usuarios";
                } else {
                    echo "ℹ️ Coluna '$coluna' já existe na tabela 'usuarios'<br>";
                }
            } catch (Exception $e) {
                echo "⚠️ Erro ao adicionar coluna '$coluna': " . $e->getMessage() . "<br>";
            }
        }
    }
    
    /**
     * Atualiza configurações do sistema
     */
    private function atualizarConfiguracoes() {
        echo "<h3>⚙️ Atualizando configurações...</h3>";
        
        $configuracoes = [
            [
                'chave' => 'webhook_url',
                'valor' => 'https://webhook.echo.dev.br/webhook/8cea05f1-e082-45ea-83ca-f80809af9cfd',
                'descricao' => 'URL do webhook para envio de dados JSON'
            ],
            [
                'chave' => 'webhook_aprovacao_url',
                'valor' => 'https://webhook.echo.dev.br/webhook/e8a2f4db-eefd-498e-9547-a0200442c108',
                'descricao' => 'URL do webhook para notificação de aprovação'
            ]
        ];
        
        foreach ($configuracoes as $config) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO configuracoes_sistema (chave, valor, descricao) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    valor = VALUES(valor), 
                    descricao = VALUES(descricao),
                    atualizado_em = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$config['chave'], $config['valor'], $config['descricao']]);
                echo "✅ Configuração '{$config['chave']}' atualizada<br>";
                $this->log[] = "Configuração {$config['chave']} atualizada";
            } catch (Exception $e) {
                echo "⚠️ Erro ao atualizar configuração '{$config['chave']}': " . $e->getMessage() . "<br>";
            }
        }
    }
    
    /**
     * Mostra resumo da migração
     */
    private function mostrarResumo() {
        echo "<h3>📊 Resumo da Migração</h3>";
        echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px;'>";
        echo "<h4>✅ Alterações Realizadas:</h4>";
        echo "<ul>";
        foreach ($this->log as $item) {
            echo "<li>$item</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        echo "<br><div style='background-color: #f0fff0; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🎯 Próximos Passos:</h4>";
        echo "<ol>";
        echo "<li>✅ Fazer upload dos arquivos PHP atualizados</li>";
        echo "<li>✅ Testar funcionalidades no ambiente de produção</li>";
        echo "<li>✅ Verificar se todos os webhooks estão funcionando</li>";
        echo "<li>✅ Configurar URLs de webhook específicas para produção</li>";
        echo "</ol>";
        echo "</div>";
    }
}

// Executar migração se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'migrar_banco.php') {
    $migrator = new DatabaseMigrator();
    $migrator->executarMigracao();
}
?>
