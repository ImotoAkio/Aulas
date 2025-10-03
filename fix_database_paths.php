<?php
/**
 * Script para corrigir caminhos incorretos para config/database.php
 */

echo "🔧 Corrigindo caminhos para config/database.php...\n\n";

// Lista de arquivos que precisam ser corrigidos
$files_to_fix = [
    // Arquivos na raiz (não precisam de correção)
    // 'index.php', 'login.php', etc.
    
    // Arquivos em aluno/ (precisam de ../config/database.php)
    'aluno/alterar_senha.php',
    'aluno/index.php',
    'aluno/perfil.php',
    'aluno/pareceres.php',
    'aluno/notas.php',
    'aluno/gerar_pdf_boletim.php',
    'aluno/gerar_declaracao.php',
    'aluno/download_parecer.php',
    'aluno/declaracoes.php',
    'aluno/boletim.php',
    
    // Arquivos em professor/ (precisam de ../config/database.php)
    'professor/index.php',
    'professor/ver_notas.php',
    'professor/planos_revisao.php',
    'professor/planos.php',
    'professor/parecer.php',
    'professor/notas.php',
    'professor/editar_plano.php',
    'professor/avaliar_aluno.php',
    'professor/salvar_parecer_professor.php',
    'professor/salvar_parecer.php',
    'professor/logout.php',
    
    // Arquivos em secretaria/ (precisam de ../config/database.php)
    'secretaria/index.php',
    'secretaria/visualizar_plano.php',
    'secretaria/planos.php',
    'secretaria/parecer.php',
    'secretaria/notas.php',
    'secretaria/ver_parecer.php',
    'secretaria/logout.php',
    
    // Arquivos em secretaria/cad/ (precisam de ../../config/database.php)
    'secretaria/cad/turmas.php',
    'secretaria/cad/sucesso_cadastro_secretario.php',
    'secretaria/cad/sucesso_cadastro_professor.php',
    'secretaria/cad/sucesso_cadastro.php',
    'secretaria/cad/sincronizar_nomes.php',
    'secretaria/cad/secretario.php',
    'secretaria/cad/professor.php',
    'secretaria/cad/listar_alunos.php',
    'secretaria/cad/excluir_turma.php',
    'secretaria/cad/excluir_aluno.php',
    'secretaria/cad/editar_turma.php',
    'secretaria/cad/editar_aluno.php',
    'secretaria/cad/disciplinas.php',
    'secretaria/cad/adicionar_turma.php',
    'secretaria/cad/salvar_secretario.php',
    'secretaria/cad/salvar_professor.php',
    'secretaria/cad/salvar_disciplina.php',
    'secretaria/cad/salvar_aluno_simples.php',
    'secretaria/cad/excluir_disciplina.php',
    'secretaria/cad/editar_professor.php',
    'secretaria/cad/buscar_professor.php',
    'secretaria/cad/atualizar_tabela_usuarios.php',
    'secretaria/cad/atualizar_tabela_alunos.php',
    'secretaria/cad/aluno.php',
    
    // Arquivos em secretaria/boletim/ (precisam de ../../config/database.php)
    'secretaria/boletim/gerar_pdf_parecer.php',
    'secretaria/boletim/gerar_documento_completo.php',
    'secretaria/boletim/gerar_boletim.php',
    
    // Arquivos em secretaria/declaracoes/ (precisam de ../../config/database.php)
    'secretaria/declaracoes/gerar_declaracao_vinculo.php',
    'secretaria/declaracoes/aluno.php',
];

$fixed_count = 0;

foreach ($files_to_fix as $file) {
    if (!file_exists($file)) {
        echo "❌ Arquivo não encontrado: $file\n";
        continue;
    }
    
    echo "🔍 Verificando: $file\n";
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Determinar o caminho correto baseado na localização do arquivo
    if (strpos($file, 'secretaria/cad/') === 0 || strpos($file, 'secretaria/boletim/') === 0 || strpos($file, 'secretaria/declaracoes/') === 0) {
        // Arquivos em subdiretórios da secretaria precisam de ../../config/database.php
        $correct_path = '__DIR__ . \'/../../config/database.php\'';
    } else {
        // Arquivos em diretórios diretos precisam de ../config/database.php
        $correct_path = '__DIR__ . \'/../config/database.php\'';
    }
    
    // Substituir caminhos incorretos
    $patterns = [
        '/require_once __DIR__ \. \'\/\.\.\/config\/database\.php\';/' => "require_once $correct_path;",
        '/require_once __DIR__ \. \'\/\.\.\/\.\.\/config\/database\.php\';/' => "require_once $correct_path;",
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Verificar se houve mudanças
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "✅ Corrigido: $file\n";
        $fixed_count++;
    } else {
        echo "ℹ️  Nenhuma correção necessária: $file\n";
    }
}

echo "\n🎉 Correção concluída!\n";
echo "📊 Arquivos corrigidos: $fixed_count\n";
?>
