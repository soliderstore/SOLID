# Novidades por e-mail

Integração preparada para a API Brevo. Não foi ativada: faltam conta, chave, lista e modelo de confirmação configurados no provedor. Nenhum e-mail foi enviado nesta implementação.

1. Crie a conta Brevo e verifique o remetente s0lidestor@gmail.com no painel. A aceitação de um remetente Gmail e os requisitos de autenticação dependem do provedor; se solicitado, use um domínio próprio autenticado.
2. Crie uma lista exclusiva para roupas novas e descontos da SØLID. Anote o ID.
3. Crie e ative um modelo de confirmação dupla de inscrição (DOI), com o remetente validado. O botão de confirmação deve usar a variável de DOI exigida pela Brevo, `{{ params.DOIurl }}`. Anote o ID do modelo.
4. Copie `config/newsletter.example.php` para `config/newsletter.local.php`. Preencha `api_key`, `list_id`, `doi_template_id` e confirme `site_url`. O arquivo local está ignorado no Git. A chave também pode vir de BREVO_API_KEY no servidor. Não compartilhe senha de Gmail nem coloque a chave no JavaScript.
5. Envie os arquivos à hospedagem, incluindo o arquivo local de configuração por canal privado. O PHP precisa de cURL e acesso HTTPS à API Brevo. Teste primeiro com um e-mail seu.
6. A inscrição na Home, loja e coleções envia uma confirmação por e-mail. Só após confirmar o endereço entra na lista. A opção no cadastro e uma nova adesão no perfil também iniciam essa confirmação. Salvar uma conta não falha se o serviço de inscrição estiver indisponível: o perfil exibe a pendência. Para tentar novamente, use o formulário de novidades.
7. Para clientes antigos que já marcaram a opção, ainda é necessário confirmar o e-mail pelo formulário; não há importação automática de toda a base.
8. O cancelamento pode ser feito pelo link da campanha Brevo. Desmarcar a opção no perfil remove o endereço da lista. Se o provedor estiver configurado e essa remoção falhar, a alteração do perfil é interrompida para permitir nova tentativa. O cancelamento diretamente na Brevo não modifica o checkbox no banco local; a supressão do provedor continua sendo respeitada.

## Campanhas

O script `php scripts/newsletter-campaign.php "Roupas novas na SØLID" "Conheça nossos lançamentos."` cria um rascunho com o visual da loja para a lista configurada. A revisão e o envio são feitos no painel Brevo, com o limite do plano da conta.

Não há disparo automático por alteração de arquivos ou cadastro de produto nesta etapa. O catálogo atual é um arquivo JavaScript e não tem painel de publicação. Após ativar e testar o serviço de e-mail, a automação de lançamentos precisa ser ligada a um fluxo de publicação de produtos para evitar campanhas repetidas ou avisos sobre alterações técnicas do site.

Referências: https://developers.brevo.com/reference/create-doi-contact e https://developers.brevo.com/reference/create-email-campaign

## Preços

Os dois moletons do catálogo custam R$ 129,99. Produtos já salvos no carrinho e favoritos são atualizados pelo catálogo na abertura dessas páginas. Custo informado: R$ 40 de peça + R$ 40 de DTF = R$ 80; diferença de R$ 49,99 antes de mão de obra, embalagem, impostos e outras despesas. Se aplicado o cupom de 10%, a diferença cai para aproximadamente R$ 36,99 por peça.
