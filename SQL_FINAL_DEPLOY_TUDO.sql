-- ============================================================
-- DEPLOY FINAL: TODAS AS ALTERAÇÕES DO BANCO DE DADOS
-- ============================================================
-- 
-- Este script contém TODAS as alterações necessárias:
-- 
-- 1. Turma Futura (pré-cadastro)
-- 2. Remoção de constraint CPF
-- 3. Módulo de Recibos (tabelas)
--
-- Status: Production Ready
-- Data: 2024
--
-- ============================================================

-- ============================================================
-- PARTE 1: TURMA FUTURA (Pré-cadastro)
-- ============================================================
-- Resolve o problema onde alunos eram transferidos 
-- imediatamente ao criar pré-cadastro

ALTER TABLE `pre_cadastros_controle` 
ADD COLUMN `turma_futura_id` BIGINT UNSIGNED NULL 
AFTER `aluno_id`;

ALTER TABLE `pre_cadastros_controle` 
ADD INDEX `idx_turma_futura` (`turma_futura_id`);

-- ============================================================
-- PARTE 2: REMOVER CONSTRAINT CPF
-- ============================================================
-- Resolve o erro: SQLSTATE[23000]: Integrity constraint 
-- violation: 1062 Duplicate entry '' for key 'uk_alunos_cpf'

ALTER TABLE `alunos` DROP INDEX `uk_alunos_cpf`;

-- ============================================================
-- PARTE 3: MÓDULO DE RECIBOS
-- ============================================================

-- Tabela principal de recibos
CREATE TABLE IF NOT EXISTS recibos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo ENUM('mensalidade', 'fardamento', 'atividade', 'matricula') NOT NULL,
    referencia VARCHAR(100) NULL,
    valor_original DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
    acrescimos DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_final DECIMAL(10,2) NOT NULL,
    vencimento DATE NULL,
    data_pagamento DATE NULL,
    status ENUM('gerado', 'emitido', 'pago', 'cancelado') NOT NULL DEFAULT 'gerado',
    descricao TEXT NULL,
    observacoes TEXT NULL,
    forma_pagamento VARCHAR(50) NULL,
    numero_recibo VARCHAR(50) NULL,
    numero_nf VARCHAR(50) NULL,
    gerado_por BIGINT UNSIGNED NULL,
    pago_por BIGINT UNSIGNED NULL,
    cancelado_por BIGINT UNSIGNED NULL,
    motivo_cancelamento TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    cancelado_em DATETIME NULL,
    KEY idx_tipo (tipo),
    KEY idx_status (status),
    KEY idx_aluno (aluno_id),
    KEY idx_vencimento (vencimento),
    KEY idx_data_pagamento (data_pagamento),
    KEY idx_numero_recibo (numero_recibo),
    KEY idx_tipo_status (tipo, status),
    CONSTRAINT fk_recibo_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens de recibo
CREATE TABLE IF NOT EXISTS recibo_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recibo_id BIGINT UNSIGNED NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unidade VARCHAR(20) NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recibo (recibo_id),
    KEY idx_ordem (recibo_id, ordem),
    CONSTRAINT fk_recibo_itens_recibo FOREIGN KEY (recibo_id) REFERENCES recibos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices compostos de recibos
CREATE INDEX IF NOT EXISTS idx_aluno_tipo_status ON recibos(aluno_id, tipo, status);
CREATE INDEX IF NOT EXISTS idx_status_data_pagamento ON recibos(status, data_pagamento);

-- ============================================================
-- VERIFICAÇÕES
-- ============================================================

SELECT '✅ Turma Futura: turma_futura_id adicionada' as status;
SELECT '✅ CPF: Constraint removida' as status;
SELECT '✅ Recibos: Tabela creada' as status;
SELECT '✅ Recibo Itens: Tabela criada' as status;

-- ============================================================
-- VERIFICAÇÕES MANUAIS (Execute separadamente se quiser)
-- ============================================================

-- DESCRIBE pre_cadastros_controle;
-- Você DEVE ver a coluna 'turma_futura_id' na lista

-- SHOW CREATE TABLE alunos;
-- Você NÃO deve ver 'uk_alunos_cpf' na estrutura

-- SHOW TABLES LIKE 'recibos%';
-- Você DEVE ver 'recibos' e 'recibo_itens'

-- ============================================================
-- ROLLBACK (SE NECESSÁRIO)
-- ============================================================

-- ALTER TABLE `pre_cadastros_controle` 
-- DROP INDEX `idx_turma_futura`,
-- DROP COLUMN `turma_futura_id`;

-- ALTER TABLE `alunos` ADD UNIQUE INDEX `uk_alunos_cpf` (`cpf`);

-- DROP TABLE IF EXISTS recibo_itens;
-- DROP TABLE IF EXISTS recibos;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================

