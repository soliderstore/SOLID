<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../lib/registration-validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Use POST.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !in_array($data['field'] ?? '', ['cpf', 'email'], true) || !is_string($data['value'] ?? null)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Informe o campo e o valor para validar.']);
    exit;
}
$field = $data['field'];
$value = trim($data['value']);
$error = $field === 'cpf' ? registrationCpfError($value) : registrationEmailError($value);
echo json_encode([
    'ok' => true, 'field' => $field, 'valid' => $error === null,
    'message' => $error ?? ($field === 'cpf'
        ? ($value === '' ? '' : 'Dígitos do CPF válidos.')
        : 'Formato do e-mail válido.'),
], JSON_UNESCAPED_UNICODE);
