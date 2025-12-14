<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// ==================================================================
// KONFIGURACIJA EMAILA - MOLIMO POPUNITE OVE PODATKE
// ==================================================================
$smtp_host = 'smtp.gmail.com';          // SMTP server (npr. smtp.gmail.com)
$smtp_username = 'prijave@novosadjanka.rs'; // Vaša email adresa za slanje
$smtp_password = 'vasa_lozinka';        // Lozinka email naloga ili App Password
$smtp_port = 587;                       // Port (587 za TLS, 465 za SSL)
$smtp_secure = PHPMailer::ENCRYPTION_STARTTLS; // Enkripcija

$from_email = 'prijave@novosadjanka.rs'; // Email sa kojeg se šalje
$from_name = 'Novosadjanka';             // Ime pošiljaoca

$admin_email = 'prijave@novosadjanka.rs'; // Email na koji stižu obaveštenja o prijavama
// ==================================================================

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ime = $_POST['ime'] ?? '';
    $prezime = $_POST['prezime'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $biografija = $_POST['biografija'] ?? '';
    
    $clanstvo = $_POST['clanstvo'] ?? '';
    $program = $_POST['program'] ?? '';
    $paket_radionica = $_POST['paket_radionica'] ?? '';
    
    // Obrada nizova (checkbox-ovi)
    $radionice = isset($_POST['radionica']) ? implode(", ", $_POST['radionica']) : '';
    $coaching = isset($_POST['coaching']) ? implode(", ", $_POST['coaching']) : '';
    $webshop = isset($_POST['webshop']) ? implode(", ", $_POST['webshop']) : '';

    // ==================================================================
    // 1. EMAIL ZA ADMINA (Svi podaci)
    // ==================================================================
    $admin_body = "<h2>Nova prijava sa sajta</h2>";
    $admin_body .= "<table style='border-collapse: collapse; width: 100%; max-width: 600px;'>";
    $admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Ime:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>$ime</td></tr>";
    $admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Prezime:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>$prezime</td></tr>";
    $admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>$email</td></tr>";
    $admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Telefon:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>$telefon</td></tr>";
    $admin_body .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Biografija:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . nl2br($biografija) . "</td></tr>";
    $admin_body .= "</table>";
    
    $admin_body .= "<h3>Odabrane usluge:</h3>";
    $admin_body .= "<ul>";
    if($clanstvo) $admin_body .= "<li><strong>Članstvo:</strong> $clanstvo</li>";
    if($program) $admin_body .= "<li><strong>Program:</strong> $program</li>";
    if($paket_radionica) $admin_body .= "<li><strong>Paket radionica:</strong> $paket_radionica</li>";
    if($radionice) $admin_body .= "<li><strong>Radionice:</strong> $radionice</li>";
    if($coaching) $admin_body .= "<li><strong>Coaching:</strong> $coaching</li>";
    if($webshop) $admin_body .= "<li><strong>Webshop:</strong> $webshop</li>";
    $admin_body .= "</ul>";

    // ==================================================================
    // 2. EMAIL ZA KORISNIKA (Potvrda)
    // ==================================================================
    $user_body = "<div style='font-family: Arial, sans-serif; color: #333;'>";
    $user_body .= "<p>Poštovana/i $ime,</p>";
    $user_body .= "<p>Zahvaljujemo Vam na prijavi!</p>";
    $user_body .= "<p>U roku od 48h biće Vam dostavljen dodatni email sa instrukcijama za uplatu, kao i svim relevantnim informacijama u vezi sa daljom realizacijom.</p>";
    $user_body .= "<br><p>Srdačan pozdrav,<br><strong>Novosadjanka Tim</strong></p>";
    $user_body .= "</div>";

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port       = $smtp_port;
        $mail->CharSet    = 'UTF-8';

        // --------------------------------------------------
        // Slanje Admin Email-a
        // --------------------------------------------------
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($admin_email);
        $mail->isHTML(true);
        $mail->Subject = 'Nova prijava - Novosadjanka';
        $mail->Body    = $admin_body;
        $mail->send();

        // --------------------------------------------------
        // Slanje User Email-a
        // --------------------------------------------------
        $mail->clearAddresses(); // Očisti prethodne primaoce
        $mail->addAddress($email);
        $mail->Subject = 'Potvrda prijave - Novosadjanka';
        $mail->Body    = $user_body;
        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'Prijave su uspešno poslate.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Greška prilikom slanja emaila: {$mail->ErrorInfo}"]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
