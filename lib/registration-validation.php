<?php
declare(strict_types=1);

function registrationCpfError(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null; // CPF continua opcional.
    if (!preg_match('/^(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})$/D', $value)) {
        return 'Informe um CPF com 11 dígitos.';
    }
    $cpf = preg_replace('/\D/', '', $value);
    if (preg_match('/^([0-9])\1{10}$/D', $cpf)) return 'CPF inválido. Confira os números.';
    for ($length = 9; $length <= 10; $length++) {
        $sum = 0;
        for ($i = 0; $i < $length; $i++) $sum += (int)$cpf[$i] * ($length + 1 - $i);
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;
        if ($digit !== (int)$cpf[$length]) return 'CPF inválido. Confira os números.';
    }
    return null;
}

function registrationEmailError(string $value): ?string
{
    $email = strtolower(trim($value));
    if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Informe um e-mail válido com até 50 caracteres, como nome@gmail.com.';
    }
    $domain = substr(strrchr($email, '@'), 1);
    if (!str_contains($domain, '.') || str_ends_with($domain, '.')) {
        return 'Informe o domínio completo do e-mail, como gmail.com.';
    }
    return null;
}
