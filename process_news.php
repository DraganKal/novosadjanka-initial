<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------
// .env loader
// ------------------------------------------------------
function loadEnv(string $path): array {
    if (!file_exists($path)) return [];

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // skini navodnike ako postoje
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }

        $env[$key] = $val;
    }

    return $env;
}

$env = loadEnv(__DIR__ . '/.env');

function envGet(array $env, string $key, string $default = ''): string {
    return $env[$key] ?? $default;
}

// ------------------------------------------------------
// Helpers
// ------------------------------------------------------
function jsonError(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonError(405, 'Method not allowed');
}

// ------------------------------------------------------
// Read & validate input
// ------------------------------------------------------
$emailRaw = trim((string)($_POST['email'] ?? ''));

$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
if (!$email) {
    jsonError(400, 'Neispravan email.');
}

// ------------------------------------------------------
// SMTP config from .env
// ------------------------------------------------------
$smtp_host = envGet($env, 'MAIL_HOST');
$smtp_port = (int) envGet($env, 'MAIL_PORT', '587');
$smtp_secure_raw = strtolower(envGet($env, 'MAIL_SECURE', 'tls'));

$smtp_username = envGet($env, 'MAIL_USERNAME');
$smtp_password = envGet($env, 'MAIL_PASSWORD');

$from_email   = envGet($env, 'MAIL_FROM', $smtp_username);
$from_name    = envGet($env, 'MAIL_FROM_NAME', 'Novosadjanka');
$admin_email  = envGet($env, 'MAIL_ADMIN', $smtp_username);

if ($smtp_host === '' || $smtp_username === '' || $smtp_password === '') {
    jsonError(500, 'SMTP konfiguracija nije kompletna. Proveri .env (MAIL_HOST/MAIL_USERNAME/MAIL_PASSWORD).');
}

$smtp_secure = null;
if ($smtp_secure_raw === 'tls') {
    $smtp_secure = PHPMailer::ENCRYPTION_STARTTLS;
} elseif ($smtp_secure_raw === 'ssl') {
    $smtp_secure = PHPMailer::ENCRYPTION_SMTPS;
} else {
    $smtp_secure = false;
}

// ------------------------------------------------------
// Build email body - SAMO ADMIN (ne šalje se korisniku)
// ------------------------------------------------------
$admin_body = "<h2>Nova prijava za newsletter</h2>";
$admin_body .= "<table style='border-collapse: collapse; width: 100%; max-width: 600px;'>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . h($email) . "</td></tr>";
$admin_body .= "</table>";

// ------------------------------------------------------
// Send email - SAMO ADMIN
// ------------------------------------------------------
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';

    if ($smtp_secure !== false) {
        $mail->SMTPSecure = $smtp_secure;
    }

    // Admin email
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($admin_email);
    $mail->isHTML(true);
    $mail->Subject = 'Nova prijava za newsletter';
    $mail->Body = $admin_body;
    $mail->AltBody = strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $admin_body));
    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'Uspešno ste se prijavili za newsletter!'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    jsonError(500, 'Greška prilikom slanja emaila: ' . $mail->ErrorInfo);
}
