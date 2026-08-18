# Publicação no InfinityFree

1. Crie um banco MySQL no painel do InfinityFree.
2. No phpMyAdmin do banco criado, selecione o banco e importe o arquivo `database/sql/infinityfree-users.sql`.
3. O `config/conexao.php` já possui a conexão do InfinityFree e alterna automaticamente entre hospedagem e XAMPP local. Confirme no painel se o nome do banco é `if0_42686991_solid`.
4. Envie todos os arquivos do projeto para a pasta `htdocs` da hospedagem.
5. Acesse o domínio pelo arquivo `index.php`.

O arquivo `.htaccess` já redireciona as páginas antigas `.html` para as versões `.php`.

O domínio `solid.free.je` e a conta de hospedagem não são credenciais MySQL. Para concluir a conexão, copie na área **MySQL Databases** do InfinityFree: host MySQL, nome do banco, usuário e senha.

Importante: não publique as credenciais reais em repositórios públicos. Se o código estiver no GitHub, mantenha `config/conexao.php` fora do repositório ou use uma cópia com dados fictícios.
