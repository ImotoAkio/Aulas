# Configuração de Banco de Dados - Sistema Unificado

## Arquivo de Configuração Centralizada

O sistema agora utiliza um arquivo de configuração centralizada localizado em:
```
config/database.php
```

## Como Alterar as Configurações do Banco

Para fazer deploy da aplicação ou alterar as configurações do banco de dados, edite **apenas** o arquivo `config/database.php`:

```php
// Configurações do banco de dados
define('DB_HOST', 'localhost');           // Host do banco
define('DB_NAME', 'u894209272_planos_aula'); // Nome do banco
define('DB_USER', 'root');                // Usuário
define('DB_PASS', '');                    // Senha
define('DB_CHARSET', 'utf8');             // Charset
```

## Arquivos Atualizados

Todos os arquivos de conexão foram unificados para usar a configuração centralizada:

- `db.php` (raiz)
- `secretaria/partials/db.php`
- `professor/partials/db.php`

## Vantagens

✅ **Deploy Simplificado**: Alterar apenas um arquivo para configurar o banco  
✅ **Manutenção Centralizada**: Todas as configurações em um local  
✅ **Consistência**: Mesmas configurações em todo o sistema  
✅ **Segurança**: Configurações isoladas em arquivo específico  

## Como Usar

Os arquivos PHP continuam funcionando normalmente, apenas incluindo:
```php
include 'db.php'; // ou o caminho apropriado
```

A variável `$pdo` estará disponível automaticamente com todas as configurações aplicadas.
