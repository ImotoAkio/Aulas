# 🏫 Sistema de Gestão Escolar - Colégio Rosa de Sharom

## 📋 Descrição

Sistema completo de gestão escolar desenvolvido em PHP, MySQL e Bootstrap para o Colégio Rosa de Sharom. O sistema permite o gerenciamento de alunos, professores, secretários e oferece um portal específico para alunos acompanharem seu desempenho acadêmico.

## ✨ Funcionalidades

### 👥 **Área da Secretaria**
- **Gestão de Alunos**
  - Cadastro completo com dados pessoais, acadêmicos e de saúde
  - Formulário em 3 etapas para facilitar o cadastro
  - Edição de dados existentes
  - Listagem com filtros e busca
  - Geração de declarações de vínculo escolar

- **Gestão de Professores**
  - Cadastro com disciplinas e turmas associadas
  - Formulário em 2 etapas
  - Associação automática com disciplinas e turmas

- **Gestão de Secretários**
  - Cadastro de novos secretários
  - Controle de acesso ao sistema

- **Documentos**
  - Geração de declarações personalizadas
  - Sistema de pareceres pedagógicos
  - Controle de notas e boletins

### 👨‍🏫 **Área do Professor**
- Dashboard personalizado
- Gestão de notas por disciplina
- Criação de pareceres pedagógicos
- Visualização de turmas e alunos

### 👨‍🎓 **Portal do Aluno**
- **Dashboard Personalizado**
  - Resumo acadêmico
  - Informações da turma
  - Links rápidos para funcionalidades

- **Meus Dados** (`perfil.php`)
  - Visualização e edição de dados pessoais
  - Informações de contato, endereço e saúde
  - Dados escolares em modo somente leitura
  - Validação e máscaras automáticas

- **Alterar Senha** (`alterar_senha.php`)
  - Alteração da senha padrão (CRS2025)
  - Validação em tempo real
  - Dicas de segurança

- **Pareceres Pedagógicos** (`pareceres.php`)
  - Listagem de pareceres por disciplina
  - Filtros por disciplina e unidade
  - Visualização em modal
  - Funcionalidade de impressão

- **Boletim Escolar** (`boletim.php`)
  - Resumo visual com estatísticas
  - Gráfico interativo de desempenho
  - Tabela completa de notas por unidade
  - Cálculo automático de médias
  - Funcionalidades de PDF e impressão

- **Histórico Escolar** (`historico.php`)
  - Histórico completo organizado por ano
  - Resumo acadêmico geral
  - Tabelas detalhadas por ano letivo
  - Cálculo de médias por ano
  - Funcionalidades de exportação

- **Notas** (`notas.php`)
  - Visualização detalhada de notas
  - Filtros por disciplina e unidade

- **Declarações** (`declaracoes.php`)
  - Geração de declarações personalizadas
  - Múltiplos tipos de documentos

## 🛠️ Tecnologias Utilizadas

### **Backend**
- **PHP 7.4+** - Linguagem principal
- **MySQL/MariaDB** - Banco de dados
- **PDO** - Conexão com banco de dados
- **Sessions** - Controle de autenticação

### **Frontend**
- **HTML5** - Estrutura
- **CSS3** - Estilização
- **Bootstrap 4** - Framework CSS
- **JavaScript** - Interatividade
- **jQuery** - Manipulação DOM
- **Chart.js** - Gráficos
- **Select2** - Dropdowns avançados

### **Bibliotecas e Plugins**
- **Material Design Icons** - Ícones
- **Themify Icons** - Ícones adicionais
- **Font Awesome** - Ícones
- **Chart.js** - Visualização de dados

## 📁 Estrutura do Projeto

```
📦 Sistema de Gestão Escolar
├── 📁 assets/
│   ├── 📁 css/
│   ├── 📁 js/
│   ├── 📁 vendors/
│   └── 📁 images/
├── 📁 secretaria/
│   ├── 📁 cad/
│   │   ├── aluno.php
│   │   ├── professor.php
│   │   ├── salvar_aluno.php
│   │   └── ...
│   ├── 📁 partials/
│   │   ├── _navbar.php
│   │   ├── _sidebar.php
│   │   ├── _footer.php
│   │   └── db.php
│   └── index.php
├── 📁 professor/
│   ├── 📁 partials/
│   └── index.php
├── 📁 aluno/
│   ├── 📁 partials/
│   ├── index.php
│   ├── perfil.php
│   ├── alterar_senha.php
│   ├── pareceres.php
│   ├── boletim.php
│   ├── historico.php
│   ├── notas.php
│   └── declaracoes.php
├── 📁 login.php
└── 📄 README.md
```

## 🚀 Instalação

### **Pré-requisitos**
- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.2+
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL

### **Passos para Instalação**

1. **Clone o repositório**
   ```bash
   git clone [URL_DO_REPOSITORIO]
   cd sistema-gestao-escolar
   ```

2. **Configure o banco de dados**
   - Crie um banco de dados MySQL
   - Importe o arquivo SQL fornecido
   - Configure as credenciais em `secretaria/partials/db.php`

3. **Configure o servidor web**
   - Aponte o DocumentRoot para a pasta do projeto
   - Certifique-se que o mod_rewrite está habilitado (Apache)

4. **Permissões de arquivo**
   ```bash
   chmod 755 -R /caminho/do/projeto
   chmod 777 -R /caminho/do/projeto/uploads
   ```

5. **Acesse o sistema**
   - URL: `http://localhost/sistema-gestao-escolar`
   - Use as credenciais padrão fornecidas

## 🔐 Credenciais Padrão

### **Secretaria**
- **Email**: admin@colegio.com
- **Senha**: admin123

### **Professor**
- **Email**: professor@colegio.com
- **Senha**: professor123

### **Aluno**
- **CPF**: [CPF do aluno cadastrado]
- **Senha**: CRS2025

## 📊 Banco de Dados

### **Tabelas Principais**
- `usuarios` - Usuários do sistema (professores, secretários)
- `alunos` - Dados dos alunos
- `turmas` - Turmas e anos letivos
- `disciplinas` - Disciplinas do currículo
- `notas` - Notas dos alunos
- `pareceres` - Pareceres pedagógicos
- `professores_disciplinas` - Relacionamento professor-disciplina
- `professores_turmas` - Relacionamento professor-turma

## 🔧 Configuração

### **Arquivo de Configuração**
```php
// secretaria/partials/db.php
$host = 'localhost';
$dbname = 'nome_do_banco';
$username = 'usuario_banco';
$password = 'senha_banco';
```

### **Configurações de Sessão**
```php
// Configurações recomendadas
session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
```

## 🎨 Personalização

### **Cores e Branding**
- Logo: `assets/images/logo.png`
- Cores principais: Azul (#667eea), Verde (#28a745), Vermelho (#dc3545)
- Nome da escola: "Colégio Rosa de Sharom"

### **CSS Customizado**
- Arquivo principal: `assets/css/style.css`
- Responsividade incluída
- Temas personalizáveis

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:
- ✅ Desktop (1920x1080+)
- ✅ Tablet (768px+)
- ✅ Mobile (320px+)

## 🔒 Segurança

### **Medidas Implementadas**
- ✅ Validação de sessões
- ✅ Sanitização de dados
- ✅ Prepared Statements (PDO)
- ✅ Controle de acesso por tipo de usuário
- ✅ Validação de formulários
- ✅ Proteção contra XSS
- ✅ Senhas criptografadas

### **Recomendações de Segurança**
- Use HTTPS em produção
- Configure firewall adequado
- Faça backups regulares
- Mantenha o PHP atualizado
- Monitore logs de acesso

## 🚀 Funcionalidades Futuras

### **Planejadas**
- [ ] Sistema de notificações
- [ ] Calendário acadêmico
- [ ] Geração de PDF avançada
- [ ] API REST para integração
- [ ] Sistema de mensagens
- [ ] Relatórios avançados
- [ ] Dashboard com gráficos
- [ ] Sistema de frequência

### **Melhorias Técnicas**
- [ ] Migração para PHP 8+
- [ ] Implementação de cache
- [ ] Otimização de queries
- [ ] Sistema de logs
- [ ] Testes automatizados

## 📞 Suporte

### **Contato**
- **Email**: suporte@colegiorosadesharom.com
- **Telefone**: (XX) XXXX-XXXX
- **Horário**: Segunda a Sexta, 8h às 18h

### **Documentação**
- Manual do usuário disponível em `/docs/`
- Vídeos tutoriais em `/videos/`
- FAQ em `/faq/`

## 📄 Licença

Este projeto é desenvolvido especificamente para o Colégio Rosa de Sharom.
Todos os direitos reservados.

---

## 🎯 Status do Projeto

### **✅ Concluído**
- Sistema de login multi-usuário
- Portal da secretaria completo
- Portal do professor funcional
- Portal do aluno com 5 funcionalidades principais
- Sistema de declarações
- Gestão de notas e pareceres

### **🔄 Em Desenvolvimento**
- Melhorias na interface
- Otimizações de performance
- Novas funcionalidades

### **📋 Próximos Passos**
- Implementação de geração de PDF
- Sistema de notificações
- Calendário acadêmico
- Relatórios avançados

---

**Desenvolvido com ❤️ para o Colégio Rosa de Sharom**
