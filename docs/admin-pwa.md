# SØLID Admin — painel PWA integrado

## Arquitetura e fluxo

O projeto usa PHP com PDO/MySQL (MariaDB no XAMPP), HTML, CSS e JavaScript, sem framework. O catálogo vem de `products`, o carrinho do navegador e o checkout chama `api/orders.php`. A sessão PHP identifica o usuário e `users.is_admin` autoriza o administrador no servidor.

O novo painel `/admin/` consulta **a mesma tabela `orders`**. O checkout continua validando preços, variações e estoque no servidor. Pedidos do site recebem origem `site` e pagamento `pending`; finalizar a compra nunca aprova o pagamento. Pedidos manuais usam origem `whatsapp`, reservam estoque, aceitam vários produtos do catálogo, preços negociados, desconto e frete. A edição aplica apenas a diferença de estoque. Itens guardam nome, descrição, imagem, variações e valores no momento da venda.

Não há importação de conversas do WhatsApp. O administrador cadastra essas vendas pelo formulário. Produtos precisam existir no catálogo; podem ser cadastrados em Gerenciar loja. Não existe integração de pagamentos: conferir o recebimento é responsabilidade do administrador; a confirmação manual fica auditada.

## Iniciar no XAMPP

1. Inicie Apache e MySQL no XAMPP. O PHP precisa de `pdo_mysql`, `mbstring` e, para os testes HTTP, `curl`.
2. Use o banco existente da loja. Para uma instalação nova, siga `docs/xampp-mysql.md` e importe o esquema de usuários antes da configuração administrativa.
3. Para outro banco, copie `config/database.example.php` para **`config/database.local.php`**, ignorado pelo Git. Preencha host, nome, usuário e senha. Alternativamente use `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` no ambiente do servidor. Sem configuração, o padrão local é `127.0.0.1`, banco `solid`, usuário `root`, senha vazia.
4. Com o banco existente, execute a migração antes de abrir o painel ou aceitar novos checkouts:

```powershell
C:\xampp\php\php.exe -r 'require "config/conexao.php"; database()->exec(file_get_contents("database/sql/admin-pwa.sql"));'
```

Faça backup antes de aplicar em produção. A migração adiciona campos e a tabela `order_history`, preserva pedidos, converte status legados `paid`/`completed` e mantém pagamento dos antigos pedidos enviados. Pode ser repetida; os novos pedidos têm histórico e não são reinterpretados como pagamentos legados.

5. Abra http://localhost/SOLID/admin/ e faça login na conta administrativa da loja. O painel antigo continua disponível para produtos, conteúdo, avaliações, clientes e segurança; suas abas de visão geral/pedidos direcionam para o novo painel.

## Primeiro administrador

O script existente foi atualizado para também executar a migração do PWA. Execute no terminal, após configurar o esquema base do banco:

```powershell
$adminEmail = Read-Host 'E-mail do administrador'
$adminSecret = Read-Host 'Senha temporária (12 a 72 caracteres)' -AsSecureString
$adminCredential = [System.Net.NetworkCredential]::new('', $adminSecret)
@{email=$adminEmail;password=$adminCredential.Password} | ConvertTo-Json -Compress | C:\xampp\php\php.exe scripts/setup-admin.php
Remove-Variable adminSecret,adminCredential
```

Se o e-mail já existir, a conta é promovida e a senha atual é preservada. Uma conta nova exige troca da senha no primeiro acesso. Nunca promova administradores por endpoints públicos.

## Instalar

Entre no painel, abra **Configurações → Instalar SØLID Admin**. Quando o navegador emite `beforeinstallprompt`, o botão abre o instalador. Caso contrário, a tela apresenta as instruções do navegador. No Safari/iOS: Compartilhar → Adicionar à Tela de Início. Compatibilidade depende do dispositivo.

Em produção é necessário HTTPS; localhost é aceito para desenvolvimento. Um celular acessando o IP HTTP do computador não recebe as mesmas permissões de instalação/notificação de localhost. Publique em HTTPS para testar no celular.

O manifest usa `id`, `start_url` e `scope` relativos a `/admin/`, `display: standalone`, ícones PNG 192 e 512 e cores da marca. Só páginas administrativas autenticadas referenciam o manifest e mostram o botão. O Service Worker está em `/admin/sw.js` com escopo exclusivo `/admin/`. Nenhuma página pública foi alterada para instalação.

## Pedidos e faturamento

Fluxo: Pendente → Confirmado → Em preparação → Pronto para envio → Enviado → Entregue. É possível cancelar antes do envio, com confirmação e devolução única do estoque. Cancelamento não realiza reembolso. Pedidos enviados/entregues/cancelados não permitem edição manual.

Pagamento tem status separado: Pendente, Pago, Falhou e Reembolsado. O faturamento soma somente pedidos pagos e não cancelados. Indicadores e lista seguem os filtros de data, busca, status e origem. Dia/mês usam o relógio e o fuso configurados no MySQL. O CSV exporta a lista filtrada, limitada a 500 pedidos; o painel antigo mantém a exportação integral. Valores no CSV são protegidos contra fórmulas.

O pedido do checkout é gravado antes do redirecionamento de confirmação via WhatsApp. Na próxima consulta do painel ele aparece como Site. O painel consulta a API a cada 10 segundos quando visível e imediatamente quando volta a ficar visível. Falhas de conexão exibem mensagem e são tentadas novamente; pedidos não são enviados offline.

## Notificações e limitações

Há avisos dentro do painel com número, cliente, total e origem. A autorização em Configurações habilita também notificações do sistema em navegadores compatíveis, com o painel aberto e Service Worker ativo. Os pedidos já existentes na primeira consulta não geram avisos retroativos. Não há push com o aplicativo fechado: isso requer serviço de envio, assinatura e configuração de credenciais, que não foram fornecidos. Não há aprovação automática ou webhook de pagamento.

O Service Worker usa **rede exclusivamente**, sem Cache Storage: páginas autenticadas, respostas da API e dados pessoais nunca são armazenados por ele. Sem rede, apenas uma mensagem estática é exibida. O aplicativo instalado continua exigindo autenticação. As APIs conferem a permissão a cada chamada; alterações exigem CSRF e revisões para evitar sobrescrever uma edição concorrente. O histórico identifica administrador, horário e mudanças. É preciso conexão para operar.

A credencial de produção anteriormente versionada foi removida de `config/conexao.php`. Configure o segredo fora do código público e **troque a senha antiga no provedor**, pois removê-la do arquivo não a remove do histórico Git. Em produção, desative `display_errors`, use cookies Secure via configuração PHP/HTTPS e mantenha `config/`, `database/`, `scripts/` e logs fora do acesso público ou bloqueados no servidor.

## Verificação executada

```powershell
C:\xampp\php\php.exe scripts/test-orders.php
C:\xampp\php\php.exe scripts/test-admin-pwa.php
node --check admin/admin.js
node --check admin/sw.js
```

Os testes criam dados explicitamente temporários e removem contas, pedidos, produtos e eventos ao finalizar. Execute em desenvolvimento. O teste HTTP usa Apache em `http://localhost/SOLID/` e o banco configurado.

Cobertura: preços do checkout no servidor, cupom, estoque, duplicidade, compra do site aparecendo na API administrativa, origem, pagamento pendente, login, acesso anônimo e permissão revogada, CSRF inválido, criação e edição WhatsApp, frete/desconto/total, conflitos de revisão, alterações de status, histórico, filtros, cancelamento confirmado e devolução única de estoque. Sintaxe PHP e JavaScript também conferidas.

**Validação pendente:** a ferramenta de navegador não iniciou neste ambiente. Layout visual, navegação por teclado/leitor de tela, responsividade real, instalação Android/iOS e notificações do dispositivo precisam de teste em navegador/dispositivo. O CSS inclui layouts de celular/tablet/desktop; campos têm rótulos, avisos usam regiões vivas e detalhes usam diálogo nativo, mas isso não substitui testes de acessibilidade.

## Arquivos

Criados: `admin/index.php`, `admin/api.php`, `admin/admin.js`, `admin/admin.css`, `admin/manifest.json`, `admin/sw.js`, `admin/icon-192.png`, `admin/icon-512.png`, `lib/admin-orders.php`, `database/sql/admin-pwa.sql`, `config/database.example.php`, `scripts/test-admin-pwa.php`, `docs/admin-pwa.md`.

Modificados: `.gitignore`, `config/conexao.php`, `lib/orders.php`, `lib/admin-sales.php`, `pages/admin.php`, `scripts/setup-admin.php`, `scripts/test-orders.php` e `README.md`.
