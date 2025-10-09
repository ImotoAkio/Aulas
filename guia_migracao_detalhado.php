<?php
/**
 * GUIA DETALHADO - MIGRAÇÃO CONTROLADA
 * Passo a passo completo para migração segura
 */

echo "<h2>🚀 MIGRAÇÃO CONTROLADA - GUIA DETALHADO</h2>";

echo "<div style='background-color: #d1ecf1; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h3>📋 RESUMO DO PROCESSO:</h3>";
echo "<ol>";
echo "<li><strong>1. Backup:</strong> Fazer backup completo do banco e arquivos</li>";
echo "<li><strong>2. Upload:</strong> Fazer upload do script migrar_banco.php</li>";
echo "<li><strong>3. Execução:</strong> Executar migração via navegador</li>";
echo "<li><strong>4. Verificação:</strong> Verificar se migração foi bem-sucedida</li>";
echo "<li><strong>5. Upload:</strong> Fazer upload dos arquivos PHP atualizados</li>";
echo "<li><strong>6. Configuração:</strong> Configurar URLs de webhook</li>";
echo "<li><strong>7. Teste:</strong> Testar todas as funcionalidades</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔧 PASSO 1: UPLOAD DO SCRIPT DE MIGRAÇÃO</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>📤 Via Painel da Hospedagem:</h4>";
echo "<ol>";
echo "<li>Acesse o <strong>'Gerenciador de Arquivos'</strong> da hospedagem</li>";
echo "<li>Navegue até a pasta raiz da aplicação (onde está o index.php)</li>";
echo "<li>Faça upload do arquivo <code>migrar_banco.php</code></li>";
echo "<li>Verifique se o arquivo foi enviado corretamente</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<h4>📤 Via FTP:</h4>";
echo "<ol>";
echo "<li>Conecte-se via FTP usando FileZilla ou similar</li>";
echo "<li>Navegue até a pasta raiz da aplicação</li>";
echo "<li>Arraste o arquivo <code>migrar_banracao.php</code> para a pasta</li>";
echo "<li>Verifique se o upload foi concluído</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔧 PASSO 2: EXECUÇÃO DA MIGRAÇÃO</h3>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>🌐 Via Navegador:</h4>";
echo "<ol>";
echo "<li>Abra seu navegador</li>";
echo "<li>Acesse: <code>https://app.colegiorosadesharom.com.br/migrar_banco.php</code></li>";
echo "<li>Aguarde o script executar (pode demorar alguns minutos)</li>";
echo "<li>Leia toda a saída na tela</li>";
echo "<li>Anote qualquer erro ou aviso</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px;'>";
echo "<h4>⚠️ O QUE ESPERAR:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Sucesso:</strong> Mensagens verdes indicando criação/atualização</li>";
echo "<li>⚠️ <strong>Avisos:</strong> Mensagens amarelas (normal se já existir)</li>";
echo "<li>❌ <strong>Erros:</strong> Mensagens vermelhas (pare e investigue)</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔧 PASSO 3: VERIFICAÇÃO DA MIGRAÇÃO</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Checklist de Verificação:</h4>";
echo "<ul>";
echo "<li>✅ Script executou sem erros fatais</li>";
echo "<li>✅ Tabelas foram criadas/atualizadas</li>";
echo "<li>✅ Colunas foram adicionadas</li>";
echo "<li>✅ Configurações foram inseridas</li>";
echo "<li>✅ Resumo da migração foi exibido</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔧 PASSO 4: UPLOAD DOS ARQUIVOS ATUALIZADOS</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>📤 Arquivos para Upload:</h4>";
echo "<ul>";
echo "<li>✅ Todos os arquivos PHP atualizados</li>";
echo "<li>✅ Pasta <code>config/</code> completa</li>";
echo "<li>✅ Pasta <code>secretaria/</code> completa</li>";
echo "<li>✅ Pasta <code>financeiro/</code> completa</li>";
echo "<li>✅ Pasta <code>aluno/</code> completa</li>";
echo "<li>✅ Pasta <code>professor/</code> completa</li>";
echo "<li>❌ <strong>NÃO</strong> fazer upload dos scripts de migração</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔧 PASSO 5: CONFIGURAÇÃO PÓS-DEPLOY</h3>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>⚙️ URLs de Webhook:</h4>";
echo "<ol>";
echo "<li>Acesse: <code>https://app.colegiorosadesharom.com.br/secretaria/configuracoes.php</code></li>";
echo "<li>Configure a URL do webhook de aprovação</li>";
echo "<li>Acesse: <code>https://app.colegiorosadesharom.com.br/financeiro/configuracoes.php</code></li>";
echo "<li>Configure a URL do webhook de aprovação</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔧 PASSO 6: TESTES FINAIS</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>🧪 Checklist de Testes:</h4>";
echo "<ul>";
echo "<li>✅ Login funciona normalmente</li>";
echo "<li>✅ Secretaria pode criar pré-cadastros</li>";
echo "<li>✅ Financeiro pode criar pré-cadastros</li>";
echo "<li>✅ Aprovação de pré-cadastros funciona</li>";
echo "<li>✅ Webhooks são enviados corretamente</li>";
echo "<li>✅ Todas as funcionalidades estão operacionais</li>";
echo "</ul>";
echo "</div>";

echo "<br><hr>";
echo "<div style='background-color: #f8d7da; padding: 20px; border-radius: 5px;'>";
echo "<h3>🚨 EM CASO DE PROBLEMAS:</h3>";
echo "<ol>";
echo "<li><strong>NÃO ENTRE EM PÂNICO</strong></li>";
echo "<li>Use o backup para restaurar o banco</li>";
echo "<li>Restaure os arquivos PHP do backup</li>";
echo "<li>Investigue o problema localmente</li>";
echo "<li>Corrija e tente novamente</li>";
echo "</ol>";
echo "</div>";

echo "<br><div style='background-color: #d1ecf1; padding: 20px; border-radius: 5px;'>";
echo "<h3>📞 SUPORTE:</h3>";
echo "<p>Se precisar de ajuda durante o processo, mantenha:</p>";
echo "<ul>";
echo "<li>📸 Screenshots dos erros</li>";
echo "<li>📝 Logs de erro</li>";
echo "<li>📋 Descrição detalhada do problema</li>";
echo "</ul>";
echo "</div>";
?>
