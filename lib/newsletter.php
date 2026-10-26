<?php
declare(strict_types=1);

function newsletterConfig(): array
{
    $config = require __DIR__ . '/../config/newsletter.example.php';
    $local = __DIR__ . '/../config/newsletter.local.php';
    if (is_file($local)) $config = array_replace($config, require $local);
    $config['api_key'] = getenv('BREVO_API_KEY') ?: $config['api_key'];
    return $config;
}

function brevoRequest(string $path, array $payload): array
{
    $config = newsletterConfig();
    if (!$config['api_key'] || !$config['list_id'] || !$config['doi_template_id']) {
        throw new RuntimeException('O envio de novidades ainda está sendo configurado. Tente novamente mais tarde.');
    }
    if (!function_exists('curl_init')) throw new RuntimeException('O serviço de e-mail está indisponível. Tente novamente mais tarde.');
    $curl = curl_init('https://api.brevo.com/v3/' . $path);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'api-key: ' . $config['api_key']],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
    ]);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($result === false || $status < 200 || $status >= 300) {
        // Não expor respostas do provedor, contatos ou credenciais ao visitante.
        throw new RuntimeException('Não foi possível concluir a inscrição. Tente novamente mais tarde.');
    }
    return json_decode($result, true) ?: [];
}

function newsletterSubscribe(string $email): void
{
    $config = newsletterConfig();
    brevoRequest('contacts/doubleOptinConfirmation', [
        'email' => $email,
        'includeListIds' => [(int)$config['list_id']],
        'templateId' => (int)$config['doi_template_id'],
        'redirectionUrl' => rtrim($config['site_url'], '/') . '/?newsletter=confirmed',
    ]);
}

function newsletterRemove(string $email): void
{
    $config = newsletterConfig();
    if (!$config['api_key'] || !$config['list_id']) return;
    brevoRequest('contacts/lists/' . (int)$config['list_id'] . '/contacts/remove', ['emails' => [$email]]);
}

function newsletterAccountSubscribe(array $user, array $previous): string
{
    if (empty($user['newsletter'])) return '';
    if (!empty($previous['newsletter']) && $previous['email'] === $user['email']) return '';
    try {
        newsletterSubscribe($user['email']);
        return 'Confira seu e-mail e confirme a inscrição para receber roupas novas e descontos.';
    } catch (RuntimeException $error) {
        return 'Sua conta foi salva, mas a inscrição de novidades está pendente. Tente inscrever seu e-mail pelo formulário de novidades mais tarde.';
    }
}
