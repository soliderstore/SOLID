-- Execute no banco existente. Resolva eventuais CPFs duplicados antes do ALTER.
SELECT REPLACE(REPLACE(REPLACE(TRIM(cpf), '.', ''), '-', ''), ' ', '') AS cpf_normalizado, COUNT(*) AS total
FROM users WHERE cpf IS NOT NULL AND TRIM(cpf) <> ''
GROUP BY cpf_normalizado HAVING COUNT(*) > 1;

ALTER TABLE users ADD COLUMN cpf_unique VARCHAR(20) GENERATED ALWAYS AS (NULLIF(REPLACE(REPLACE(REPLACE(TRIM(cpf), '.', ''), '-', ''), ' ', ''), '')) STORED,
ADD UNIQUE KEY users_cpf_unique (cpf_unique);
