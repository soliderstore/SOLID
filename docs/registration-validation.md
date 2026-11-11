# Validação no cadastro

O endpoint `POST api/validate-registration.php` recebe JSON com `field` (`cpf` ou `email`) e `value`. Não depende do MySQL nem envia os dados para serviços externos.

- CPF opcional: valida formato, 11 dígitos, sequências repetidas e os dois dígitos verificadores. Não consulta a Receita Federal, situação cadastral ou titularidade.
- E-mail: valida formato, domínio completo e limite de 50 caracteres. Gmail e outros provedores são aceitos. Não confirma existência da caixa postal nem titularidade; isso exige confirmação por e-mail ou autenticação com o provedor.
- O formulário consulta a API após uma pausa na digitação e ao sair do campo. O cadastro também repete a validação no servidor, mesmo se o JavaScript for desativado.
- E-mails já cadastrados continuam sendo recusados pela API de conta.

Para publicar, envie `api/validate-registration.php`, `lib/registration-validation.php`, `api/account.php`, `assets/js/account.js`, `assets/css/pages/account.css` e `pages/register.php`. Não é necessária alteração no banco.
