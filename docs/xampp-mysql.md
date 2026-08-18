# Banco de dados SØLID (XAMPP)

1. Abra o **XAMPP Control Panel** e inicie **Apache** e **MySQL**.
2. Acesse `http://localhost/phpmyadmin`.
3. Clique em **Importar** e selecione o arquivo `database/sql/solid.sql`.
4. Abra o projeto por `http://localhost/SOLID/`.

Para uso local, copie `config/conexao.xampp.example.php` como `config/conexao.php`:

- Banco: `solid`
- Usuário: `root`
- Senha: vazia

Se o seu MySQL usar outra senha, atualize a constante `DB_PASSWORD` em `config/conexao.php`.
