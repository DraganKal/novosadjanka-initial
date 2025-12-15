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
// .env loader (bez dodatnih biblioteka)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(405, 'Method not allowed');
}

// ------------------------------------------------------
// Read & validate input
// ------------------------------------------------------
$ime        = trim((string)($_POST['ime'] ?? ''));
$prezime    = trim((string)($_POST['prezime'] ?? ''));
$emailRaw   = trim((string)($_POST['email'] ?? ''));
$telefon    = trim((string)($_POST['telefon'] ?? ''));
$biografija = trim((string)($_POST['biografija'] ?? ''));

$clanstvo        = trim((string)($_POST['clanstvo'] ?? ''));
$program         = trim((string)($_POST['program'] ?? ''));
$paket_radionica = trim((string)($_POST['paket_radionica'] ?? ''));

// checkbox arrays
$radionice = (isset($_POST['radionica']) && is_array($_POST['radionica']))
    ? implode(", ", array_map('trim', $_POST['radionica'])) : '';
$coaching  = (isset($_POST['coaching']) && is_array($_POST['coaching']))
    ? implode(", ", array_map('trim', $_POST['coaching'])) : '';
$webshop   = (isset($_POST['webshop']) && is_array($_POST['webshop']))
    ? implode(", ", array_map('trim', $_POST['webshop'])) : '';

$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
if (!$email) {
    jsonError(400, 'Neispravan email.');
}
if ($ime === '' || $prezime === '') {
    jsonError(400, 'Ime i prezime su obavezni.');
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
    // omogućava i "none" ako baš mora
    $smtp_secure = false;
}

// ------------------------------------------------------
// Build email bodies (sanitized)
// ------------------------------------------------------
$admin_body = "<h2>Nova prijava sa sajta</h2>";
$admin_body .= "<table style='border-collapse: collapse; width: 100%; max-width: 600px;'>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Ime:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . h($ime) . "</td></tr>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Prezime:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . h($prezime) . "</td></tr>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . h($email) . "</td></tr>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Telefon:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . h($telefon) . "</td></tr>";
$admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Biografija:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . nl2br(h($biografija)) . "</td></tr>";
$admin_body .= "</table>";

$admin_body .= "<h3>Odabrane usluge:</h3><ul>";
if ($clanstvo) $admin_body .= "<li><strong>Članstvo:</strong> " . h($clanstvo) . "</li>";
if ($program) $admin_body .= "<li><strong>Program:</strong> " . h($program) . "</li>";
if ($paket_radionica) $admin_body .= "<li><strong>Paket radionica:</strong> " . h($paket_radionica) . "</li>";
if ($radionice) $admin_body .= "<li><strong>Radionice:</strong> " . h($radionice) . "</li>";
if ($coaching) $admin_body .= "<li><strong>Coaching:</strong> " . h($coaching) . "</li>";
if ($webshop) $admin_body .= "<li><strong>Webshop:</strong> " . h($webshop) . "</li>";
$admin_body .= "</ul>";

// USER EMAIL (kao na slici)
$subject_user = 'Potvrda prijave - Novosađanka.rs';

// Kontakt podaci (menjaj ovde ako treba)
$contact_web  = 'www.novosadjanka.rs';
$contact_mail = 'info@novosadjanka.rs';
$contact_tel  = '+381659587100';

// HTML body: čisto, “mail-klijent friendly”, bez eksternih CSS-a
$user_body = "
<div style='margin:0;padding:0;background:#ffffff;'>
  <div style='font-family: Arial, Helvetica, sans-serif; color:#111; font-size:15px; line-height:1.6; max-width:760px; padding:24px 18px;'>

    <p style='margin:0 0 22px 0;'>Poštovana,</p>

    <p style='margin:0 0 18px 0;'>
      Zahvaljujemo Vam na prijavi za <strong>Članstvo/Program/Webshop</strong> u okviru zajednice
      <strong>Novosađanka.rs</strong>.
    </p>

    <p style='margin:0 0 16px 0;'>
      Obaveštavamo Vas da je Vaša prijava uspešno evidentirana.
    </p>

    <p style='margin:0 0 20px 0;'>
      <strong>U roku od 48h</strong> biće Vam upućen telefonski poziv od strane našeg tima i dostavljen
      dodatni email sa instrukcijama za uplatu, kao i svim relevantnim informacijama u vezi sa daljom realizacijom.
    </p>

    <p style='margin:0 0 10px 0;'>
      <em>*Ovo je automatski email, te Vas molimo da ne odgovarate na njega.</em>
    </p>

    <p style='margin:0 0 24px 0;'>
      Ukoliko imate dodatna pitanja možete nam se obratiti putem navedenih kontakt podataka.
    </p>

    <p style='margin:0 0 6px 0;'>S poštovanjem,</p>
    <p style='margin:0 0 14px 0;'>
      <strong>Tim Novosađanka.rs</strong> 🕊️
    </p>

    <div style='margin-top:8px;'>
      <div>Web: <a href='https://www.novosadjanka.rs' style='color:#1155cc;'>www.novosadjanka.rs</a></div>
      <div>Email: <a href='mailto:info@novosadjanka.rs' style='color:#1155cc;'>info@novosadjanka.rs</a></div>
      <div>Kontakt tel: <a href='tel:+381659587100' style='color:#1155cc;'>+381659587100</a></div>
    </div>

  </div>
</div>
";

// Plain text verzija (fallback)
$user_alt = "Naslov: {$subject_user}\n\n"
. "Poštovana,\n\n"
. "Zahvaljujemo Vam na prijavi za Članstvo/Program/Webshop u okviru zajednice Novosađanka.rs.\n\n"
. "Obaveštavamo Vas da je Vaša prijava uspešno evidentirana.\n"
. "U roku od 48h biće Vam upućen telefonski poziv od strane našeg tima i dostavljen dodatni email sa instrukcijama za uplatu, kao i svim relevantnim informacijama u vezi sa daljom realizacijom.\n\n"
. "*Ovo je automatski email, te Vas molimo da ne odgovarate na njega.\n"
. "Ukoliko imate dodatna pitanja možete nam se obratiti putem navedenih kontakt podataka.\n\n"
. "S poštovanjem,\n"
. "Tim Novosađanka.rs 🕊️\n"
. "Web: {$contact_web}\n"
. "Email: {$contact_mail}\n"
. "Kontakt tel: {$contact_tel}\n";

// ------------------------------------------------------
// Send emails
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

    // (opciono) za debug dok testiraš:
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    // $mail->Debugoutput = 'error_log';

    // Admin email
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($admin_email);
    $mail->isHTML(true);
    $mail->Subject = 'Nova prijava';
    $mail->Body = $admin_body;
    $mail->AltBody = strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $admin_body));
    $mail->send();

    // User email
    $mail->clearAddresses();
    $mail->addAddress($email);
    $mail->Subject = $subject_user;
    $mail->Body = $user_body;
    $mail->AltBody = $user_alt;
    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'Prijava je uspešno poslata.'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    jsonError(500, 'Greška prilikom slanja emaila: ' . $mail->ErrorInfo);
}
