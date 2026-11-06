# Login com Google

O botão nas telas de login e cadastro abre o Google com `prompt=select_account`, para a pessoa escolher uma das contas conectadas ao navegador ou entrar com outra. O site não tem acesso à lista de contas: essa tela é controlada pelo Google.

## Ativação

1. No [Google Cloud Console](https://console.cloud.google.com/), configure o Google Auth Platform (marca, público e clientes). Crie um cliente OAuth do tipo **Aplicativo da Web**. Se estiver em teste, adicione os usuários de teste.
2. Cadastre exatamente esta URI de redirecionamento para o XAMPP: `http://localhost/SOLID/api/google.php?callback=1`. Para o site publicado, adicione a URL equivalente com o domínio real e HTTPS.
3. Copie `config/google.example.php` para `config/google.local.php`. Preencha `client_id`, `client_secret` e `redirect_uri` com os dados desse cliente. O arquivo local está ignorado pelo Git; envie-o separadamente à hospedagem. Nunca coloque o segredo no JavaScript.
4. Importe `database/sql/google-login.sql` no banco já usado pelo site. Esse script adiciona a tabela de vínculo sem alterar os usuários existentes.
5. Ative a extensão cURL do PHP e mantenha a validação TLS com certificados atualizados. A hospedagem precisa permitir requisições HTTPS aos endpoints do Google.

## Comportamento e verificação

- Sem configuração, o botão informa que o serviço ainda não está disponível; o login por senha continua funcionando.
- O servidor usa código de autorização com PKCE, estado aleatório de uso único (validade de 10 minutos) e consulta o perfil diretamente no Google por HTTPS. O identificador estável `sub` é salvo no banco; tokens não são guardados no navegador.
- Novos usuários são cadastrados com nome e e-mail verificado. Telefone e endereço podem ser preenchidos em Minha conta ou no checkout. Não são inscritos automaticamente na newsletter.
- Contas existentes de Gmail/Workspace com e-mail verificado são vinculadas no primeiro acesso. Para e-mail de outro provedor já cadastrado, o usuário deve entrar com a senha existente.
- Teste a escolha entre duas contas conectadas, cadastro novo, retorno de usuário existente, cancelamento e `login.php?next=checkout` (deve preservar o carrinho e retornar ao checkout).

Referência: [OpenID Connect do Google](https://developers.google.com/identity/openid-connect/openid-connect).
