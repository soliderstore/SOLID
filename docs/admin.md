# Administração da loja

Acesse `/pages/login.php` com a conta administradora. O login direciona essa conta para `/pages/admin.php`. Clientes comuns não têm acesso ao painel, mesmo alterando o armazenamento do navegador ou enviando formulários diretamente.

No ambiente local, `cw42164@gmail.com` é administrador e mantém sua senha anterior.

## Painel

- Produtos: criar, editar nome, descrição, categoria, SKU, preço, estoque, tamanhos, cores e imagens; destacar na página inicial ou ocultar da loja.
- Imagens: selecione várias fotos diretamente do computador. O servidor aceita JPG, PNG, WebP e GIF, verifica o conteúdo e salva os arquivos com nomes aleatórios em `assets/images/uploads/`. Ao editar, marque as fotos que deseja manter. Os limites de envio do PHP aparecem no painel. Faça backup dessa pasta junto com o banco.
- Conteúdo: escolha página, área e novo texto, imagem ou destino de link. Existem três linhas vazias para novas personalizações; depois de salvar, novas linhas ficam disponíveis. Marque “Restaurar original” para desfazer uma alteração.
- Avisos: mensagem exibida nas páginas públicas; campo vazio remove o aviso.
- Atividade: alterações administrativas e novos cadastros.
- Clientes: consulta das 100 contas mais recentes e preferência de novidades.
- Segurança: troca de senha, confirmando a senha atual.

Avisos do painel e da loja não enviam e-mails automaticamente. A inscrição de novidades continua usando a integração Brevo existente. Os novos pedidos são registrados no banco antes de abrir o WhatsApp. O painel oferece filtros, detalhes, exportação CSV, confirmação manual de pagamento, envio, conclusão e cancelamento. Estoque é reservado no registro e devolvido uma única vez ao cancelar. O frete continua a combinar. Não há confirmação automática de pagamento, reembolso bancário ou importação das conversas antigas do WhatsApp.

## Instalar em outro ambiente

1. Configure o banco em `config/conexao.php` e importe o esquema inicial adequado em `database/sql/`.
2. Execute `scripts/setup-admin.php` pelo PHP CLI, passando um objeto JSON pela entrada padrão com `email` e `password` (12 a 72 caracteres). Nunca coloque a senha nos argumentos da linha de comando ou em arquivos versionados.
3. O script aplica `database/sql/admin-panel.sql`, importa os produtos iniciais se o catálogo estiver vazio e concede a permissão ao e-mail informado.
4. Se a conta já existir, a senha é preservada. Se for nova, a senha enviada é temporária e precisa ser trocada no primeiro acesso ao painel.

O provisionamento é exclusivo do terminal; o script recusa acesso por HTTP. A aplicação pública nunca aceita a permissão de administrador enviada no cadastro. Publique também `database/data/content-areas.json`, `database/data/products.json`, as APIs novas, o painel e os arquivos atualizados das páginas.

Antes de publicar em um servidor existente, faça backup do banco. As personalizações e o catálogo são persistidos em MySQL; não basta copiar os arquivos para transferir alterações feitas pelo painel.

## Validação realizada

- Sintaxe PHP e JavaScript dos arquivos alterados.
- Acesso administrativo, rejeição de visitante e de conta comum.
- Rejeição de envio sem token CSRF válido.
- Gravação de produto e atualização do catálogo público.
- Persistência e exposição pública de conteúdo e avisos.
- Catálogo público conferido no navegador.

Dados criados para verificação foram removidos, e os avisos e textos originais foram restaurados.

## Vendas e pedidos

A conta administradora acessa o painel ao abrir Minha conta. O menu público mostra Admin para essa conta. A visão geral soma apenas pedidos pagos, enviados ou concluídos como vendas confirmadas; pedidos pendentes e cancelados ficam fora desse valor. Clientes e pedidos podem ser exportados em CSV.

Aplique `database/sql/orders.sql` em instalações existentes. O script `setup-admin.php` também aplica essa estrutura. Para verificar regras de preços, cupom, idempotência, estoque, transições e cancelamento, execute `php scripts/test-orders.php` em um ambiente de teste. Ele remove seus próprios registros ao terminar.

## Prévia e exclusão

As novas fotos aparecem em miniaturas antes de salvar. É possível acrescentar fotos em seleções sucessivas e remover fotos selecionadas. Na edição, abra Apagar produto, marque a confirmação e envie o formulário para excluir o registro. Pedidos já registrados preservam os dados dos itens. A exclusão não apaga os arquivos de imagem do disco, pois podem ter outras referências.

## Ordem das fotos

Use Anterior e Próxima nas miniaturas para mudar a posição de fotos atuais ou novas. Usar como capa move a foto ao início da lista. O servidor valida a ordem e a grava junto com o produto ao salvar. Fotos desmarcadas não entram na sequência salva.

## Avaliações de compradores

Aplique `database/sql/product-reviews.sql` em instalações existentes; o setup também o aplica. Conclua o pedido em Vendas e pedidos. Na aba Avaliações, informe o e-mail de login e o produto para liberar o feedback. A API exige tanto um pedido concluído contendo o produto quanto uma permissão ativa para a conta/produto. Visitantes podem consultar avaliações, mas não publicar. Uma conta tem uma avaliação por produto e pode editá-la enquanto liberada. Bloquear novos envios preserva comentários anteriores.

A página do produto usa notas reais da tabela de avaliações e inclui carrossel com setas, miniaturas, teclado e toque.
