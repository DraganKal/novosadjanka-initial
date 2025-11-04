<?php

/**
 * This is email script based on PHPMailer library.
 */

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/SMTP.php';

/* ===================================================== */
/* SMTP Email Settings: Please change this variables     */
/* Make sure you done Gmail Account changes as described */
/* here: https://mailtrap.io/blog/phpmailer-gmail/       */

$from_email				= "YOUREMAIL@gmail.com";
$from_email_password	= "emailPassword";
$from_email_name		= "Your Name";
$to_email				= "RECEIVER_EMAIL@gmail.com";
$to_email_name			= "Receiver Name";

/* ===================================================== */

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) 
		&& preg_match('/@.+\./', $email);
}

$email_status = false;
$email_status_message = '';

if( !empty($_POST) ){

	// PHP validation
	if( !empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['number']) && !empty($_POST['message']) && isValidEmail($_POST['email']) ){
		
		/**
		 * Send email via smtp
		 * To send email via your Gmail account, please follow steps as given bere:
		 * https://mailtrap.io/blog/phpmailer-gmail/
		 */
		
		 /*

		$email_body = '<h2>Contact Form Data</h2><table style="border: 1px solid #b5b5b5; padding: 5px;">
			<tr>
				<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>Name:</strong> </td>
				<td style="border: 1px solid #b5b5b5; padding: 5px;">'.$_POST['your-name'].'</td>
			</tr>

			<tr>
				<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>Email:</strong> </td>
				<td style="border: 1px solid #b5b5b5; padding: 5px;">'.$_POST['your-email'].'</td>
			</tr>

			<tr>
				<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>Number:</strong> </td>
				<td style="border: 1px solid #b5b5b5; padding: 5px;">'.$_POST['your-number'].'</td>
			</tr>

			<tr>
				<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>Department:</strong> </td>
				<td style="border: 1px solid #b5b5b5; padding: 5px;">'.$_POST['department'].'</td>
			</tr>

			<tr>
				<td style="border: 1px solid #b5b5b5; padding: 5px;"><strong>Message:</strong> </td>
				<td style="border: 1px solid #b5b5b5; padding: 5px;">'.nl2br($_POST['message']).'</td>
			</tr>
		</table>';

		//Create a new PHPMailer instance
		$mail = new PHPMailer();
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();
		//Enable SMTP debugging
		//SMTP::DEBUG_OFF = off (for production use)
		//SMTP::DEBUG_CLIENT = client messages
		//SMTP::DEBUG_SERVER = client and server messages
		//$mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->SMTPDebug = SMTP::DEBUG_OFF;

		//Set the hostname of the mail server
		$mail->Host = 'smtp.gmail.com';
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = 587;
		//We don't need to set this as it's the default value
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "tls";
		$mail->Username   = $from_email;
		$mail->Password   = $from_email_password; // Please see here: https://mailtrap.io/blog/phpmailer-gmail/
		//Set who the message is to be sent from
		$mail->setFrom( $from_email, $from_email_name);
		//Set an alternative reply-to address
		$mail->addReplyTo($from_email, $from_email_name);
		//Set who the message is to be sent to
		$mail->addAddress( $to_email, $to_email_name);
		//Set the subject line
		$mail->Subject = 'Contact Form Data';
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML( $email_body );
		//Replace the plain text body with one created manually
		//$mail->AltBody = 'This is a plain-text message body';

		*/

		// Temp
		sleep(3);
		
		//send the message, check for errors
		//if (!$mail->send()) { // Disabled as this is demo only
		if ( 1 == 2) {
			$email_status			= 'error';
			$email_status_message	= '<div class="alert alert-danger" role="alert">Cannot send email: ' . $mail->ErrorInfo . '</div>';
			//echo ;
		} else {
			$email_status			= 'success';
			$email_status_message	= '<div class="alert alert-success" role="alert">Thank for filling the form. <br> Our team will contact you soon !!! </div>';
		}

	} else {

		die('<p>Please fill all form data and try again. Please go back and try again.</p>');

	}




	if( $email_status !== false ){

		if( !empty($_POST['doing-via-ajax']) && $_POST['doing-via-ajax'] == 'yes' ){ // ajax message

			if( $email_status == 'success' ){
				echo $email_status_message;
			} else {
				echo $email_status_message;
			}

		} else {  // Normal HTML message
			
			echo $email_status_message;

			if( $email_status == 'error' ){
				echo '<h3>Please go back and submit the form again !!!</h3>';
			}

		}
			

	}



} else {

	die('<p>Please go to Contact page and fill the contact form.</p>');

}



