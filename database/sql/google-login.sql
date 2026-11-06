-- Execute no mesmo banco da tabela users antes de ativar o login Google.
CREATE TABLE IF NOT EXISTS google_accounts (
    google_sub VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_google_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
