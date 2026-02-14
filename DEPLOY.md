# Guia de Deploy Manual (VPS)

Este guia descreve como atualizar a aplicação PHP rodando diretamente n VPS ("na raça").

## Pré-requisitos
- Acesso SSH à VPS.
- Usuário com permissões de `sudo`.
- Git instalado (se for atualizar via git).
- Cliente MySQL instalado.

## Passo 1: Script de Atualização (`update_vps.sh`)

Crie um arquivo chamado `update_vps.sh` na raiz do projeto na VPS com o seguinte conteúdo:

```bash
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
git stash
git pull origin main
git stash pop
# Resolve conflitos automaticamente no database.php mantendo o local (se possível) ou avisa
# Se houver conflito, o stash pop avisa.

# OU se for upload manual, pule esta etapa

# 5. Rodar Migração de Banco de Dados
echo "🗄️  Atualizando estrutura do banco..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME < SQL_FINAL_DEPLOY_TUDO.sql

# 6. Ajustar Permissões (Se necessário)
echo "🔒 Ajustando permissões..."
# Exemplo: chown -R www-data:www-data .

echo "✅ Atualização concluída com sucesso!"
```

## Passo 2: Execução

1.  Dê permissão de execução:
    ```bash
    chmod +x update_vps.sh
    ```

2.  Edite as credenciais do banco no script:
    ```bash
    nano update_vps.sh
    ```

3.  Rode o script:
    ```bash
    ./update_vps.sh
    ```

## Passo 3: Verificação Manual

Após rodar o script, verifique:

1.  **Novas Tabelas**:
    Acesse o MySQL e rode:
    ```sql
    USE u894209272_planos_aula;
    SHOW TABLES LIKE 'recibos%';
    ```
    Você deve ver `recibos` e `recibo_itens`.

2.  **Pré-cadastro**:
    Tente editar um pré-cadastro e verifique se não há erros relacionados a "turma_futura_id".

3.  **Logs**:
    Verifique os logs do PHP/Apache/Nginx se houver tela branca.
    - Apache: `/var/log/apache2/error.log`
    - Nginx: `/var/log/nginx/error.log`
