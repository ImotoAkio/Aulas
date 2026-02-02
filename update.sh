#!/bin/bash

# Configurações
DB_USER="root"
DB_PASS="Akio2604*" # Preencha ou use .env
DB_NAME="sistema_rosa"
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Iniciando atualização..."

# 1. Criar diretório de backup
mkdir -p $BACKUP_DIR

# 2. Backup do Banco de Dados
echo "📦 Fazendo backup do banco de dados..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/db_backup_$DATE.sql"

# 3. Backup dos Arquivos
echo "📦 Fazendo backup dos arquivos..."
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" . --exclude='./backups' --exclude='./.git'

# 4. Atualizar Código (Via Git)
echo "⬇️  Baixando atualizações..."
git pull origin main
# OU se for upload manual, pule esta etapa

# 5. Rodar Migração de Banco de Dados
echo "🗄️  Atualizando estrutura do banco..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME < SQL_FINAL_DEPLOY_TUDO.sql

# 6. Ajustar Permissões (Se necessário)
echo "🔒 Ajustando permissões..."
# Exemplo: chown -R www-data:www-data .

echo "✅ Atualização concluída com sucesso!"