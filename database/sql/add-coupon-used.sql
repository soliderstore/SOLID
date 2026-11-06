-- Execute uma vez em bancos antigos que ainda não têm esta coluna.
ALTER TABLE users ADD COLUMN coupon_used TINYINT(1) NOT NULL DEFAULT 0 AFTER newsletter;
