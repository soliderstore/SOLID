<?php
// Execute somente no terminal: php scripts/newsletter-campaign.php assunto mensagem
// Cria um rascunho na Brevo; revise e envie pelo painel do provedor.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../lib/newsletter.php';
if (count($argv) !== 3) { fwrite(STDERR, "Uso: php scripts/newsletter-campaign.php assunto mensagem\n"); exit(1); }
$config = newsletterConfig();
$escape = fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$url = $escape(rtrim($config['site_url'], '/') . '/pages/shop.php');
$title = $escape($argv[1]);
$body = nl2br($escape($argv[2]));
$html = '<!DOCTYPE html><html lang="pt-BR"><meta charset="UTF-8"><body style="margin:0;background:#050505;color:#fff;font-family:Arial,sans-serif"><main style="max-width:560px;margin:auto;padding:40px 24px"><h1>SØLID</h1><h2>' . $title . '</h2><p style="line-height:1.7">' . $body . '</p><p><a style="background:#fff;color:#050505;padding:16px 24px;display:inline-block;border-radius:18px" href="' . $url . '">Ver a coleção</a></p><p style="font-size:12px;color:#b7b7b7">Você confirmou que deseja receber roupas novas e descontos da SØLID. <a style="color:#fff" href="{{ unsubscribe }}">Cancelar inscrição</a></p></main></body></html>';
try {
    $campaign = brevoRequest('emailCampaigns', [
        'name' => 'SØLID - ' . $argv[1] . ' - ' . date('Y-m-d H:i:s'),
        'sender' => ['name' => $config['sender_name'], 'email' => $config['sender_email']],
        'subject' => $argv[1], 'type' => 'classic', 'htmlContent' => $html,
        'recipients' => ['listIds' => [(int)$config['list_id']]],
    ]);
    echo 'Rascunho criado. ID: ' . ($campaign['id'] ?? '') . ". Revise no painel Brevo. Nenhum envio foi solicitado.\n";
} catch (RuntimeException $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
