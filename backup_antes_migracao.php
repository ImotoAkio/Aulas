<?php
/**
 * SCRIPT DE BACKUP AUTOMÁTICO
 * Execute este script ANTES de qualquer migração
 */

echo "<h2>💾 BACKUP AUTOMÁTICO DO BANCO</h2>";

// Configurações do banco (ajuste conforme sua hospedagem)
$config_backup = [
    'host' => 'localhost',
    'usuario' => 'seu_usuario',
    'senha' => 'sua_senha',
    'banco' => 'seu_banco',
    'diretorio_backup' => './backups/'
];

echo "<h3>📝 Instruções para Backup:</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>🔧 MÉTODO 1: Via Painel da Hospedagem (RECOMENDADO)</h4>";
echo "<ol>";
echo "<li>Acesse o painel de controle da sua hospedagem</li>";
echo "<li>Vá em <strong>'Backup do Banco de Dados'</strong> ou <strong>'phpMyAdmin'</strong></li>";
echo "<li>Selecione seu banco de dados</li>";
echo "<li>Clique em <strong>'Exportar'</strong></li>";
echo "<li>Escolha formato <strong>'SQL'</strong></li>";
echo "<li>Marque <strong>'Adicionar DROP TABLE'</strong></li>";
echo "<li>Marque <strong>'Adicionar CREATE TABLE'</strong></li>";
echo "<li>Clique em <strong>'Executar'</strong></li>";
echo "<li>Baixe o arquivo .sql e guarde em local seguro</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<h4>🔧 MÉTODO 2: Via Linha de Comando</h4>";
echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "# Comando mysqldump (ajuste os parâmetros)\n";
echo "mysqldump -h {$config_backup['host']} -u {$config_backup['usuario']} -p{$config_backup['senha']} {$config_backup['banco']} > backup_" . date('Y-m-d_H-i-s') . ".sql\n\n";
echo "# Ou sem senha no comando (mais seguro)\n";
echo "mysqldump -h {$config_backup['host']} -u {$config_backup['usuario']} -p {$config_backup['banco']} > backup_" . date('Y-m-d_H-i-s') . ".sql";
echo "</pre>";
echo "</div>";

echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
echo "<h4>⚠️ IMPORTANTE:</h4>";
echo "<ul>";
echo "<li><strong>NUNCA</strong> execute migração sem backup</li>";
echo "<li>Guarde o backup em local seguro</li>";
echo "<li>Teste o backup antes de continuar</li>";
echo "<li>Anote a data/hora do backup</li>";
echo "</ul>";
echo "</div>";

echo "<br><hr>";
echo "<h3>📁 Backup dos Arquivos PHP</h3>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>📤 Via Painel da Hospedagem:</h4>";
echo "<ol>";
echo "<li>Acesse <strong>'Gerenciador de Arquivos'</strong></li>";
echo "<li>Navegue até a pasta da aplicação</li>";
echo "<li>Selecione todos os arquivos PHP</li>";
echo "<li>Clique em <strong>'Compactar'</strong> ou <strong>'ZIP'</strong></li>";
echo "<li>Baixe o arquivo ZIP</li>";
echo "<li>Guarde em local seguro</li>";
echo "</ol>";
echo "</div>";

echo "<br><div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Checklist de Backup:</h4>";
echo "<ul>";
echo "<li>✅ Backup do banco de dados (.sql)</li>";
echo "<li>✅ Backup dos arquivos PHP (.zip)</li>";
echo "<li>✅ Backup guardado em local seguro</li>";
echo "<li>✅ Data/hora do backup anotada</li>";
echo "</ul>";
echo "</div>";

echo "<br><div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>🎯 Próximo Passo:</h4>";
echo "<p>Após fazer o backup completo, você pode prosseguir com a migração.</p>";
echo "<p><strong>Continue para o próximo passo quando estiver pronto!</strong></p>";
echo "</div>";
?>
