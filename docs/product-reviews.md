# Fotos e avaliações de produtos

A página do produto permite deslizar as fotos no celular, arrastar com mouse, usar as setas e selecionar miniaturas. A aba Avaliações mostra a média real de todas as notas e os 50 comentários mais recentes. Não há avaliações fictícias.

## Instalar

Importe `database/sql/product-reviews.sql` no banco existente. O script cria as tabelas sem apagar dados. Envie também `api/reviews.php`, `assets/js/product-experience.js`, `assets/css/pages/product-feedback.css`, `assets/css/style.css` e `pages/product.php` para a hospedagem.

## Confirmar compras

O pedido é enviado pelo WhatsApp, sem confirmação automática de pagamento. Por isso, a loja precisa confirmar a compra no phpMyAdmin antes de liberar uma avaliação. Não basta clicar em comprar ou enviar uma mensagem.

Após conferir a compra, localize o ID do cliente na tabela `users` e o ID do produto em `assets/js/products.js`. Execute com os IDs reais:

```sql
INSERT INTO product_purchases (user_id, product_id)
VALUES (ID_DO_CLIENTE, ID_DO_PRODUTO);
```

Há apenas um vínculo por cliente e produto. Depois disso, o cliente conectado pode publicar ou editar sua própria nota e comentário na aba Avaliações. Todas as alterações são validadas no servidor, com proteção CSRF. O nome público é apenas o primeiro nome; e-mail e CPF não são exibidos.

Novos IDs de produto também devem ser incluídos na lista permitida em `api/reviews.php`.
