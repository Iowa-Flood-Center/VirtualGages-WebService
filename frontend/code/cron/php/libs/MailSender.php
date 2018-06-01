<?php

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
  
  require_once("PHPMailer/Exception.php");
  require_once("PHPMailer/PHPMailer.php");
  require_once("PHPMailer/SMTP.php");
  require_once("libs/SettingsAlertsFloods.php");

  class MailSender {

	/**
     * Submits e-mails with given messages to certain addresses.
     * $receivers: Arrays of Strings. All emails addresses to receive a msg.
     * $title: String. Email title.
     * $message: String. Raw content to be sent. Should not have HTML.
     * RETURN: Boolean. True if all emails were sent, False otherwise.
     */ 
    public static function communicate($receivers, $title, $message){
	  $all_good = true;
	  
      # get definitions
      $from_host = SettingsAlertsFloods::get("smtp_host");
      $from_port = SettingsAlertsFloods::get("smtp_port");
      $from_mail = SettingsAlertsFloods::get("smtp_from_mail");
      $from_pass = SettingsAlertsFloods::get("smtp_from_pass");
      $from_name = SettingsAlertsFloods::get("smtp_from_name");	
	  
	  # build email sender
	  $mail = new PHPMailer(true);
	  try {
		// server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $from_host;
        $mail->SMTPAuth = true;
        $mail->Username = $from_mail;
        $mail->Password = $from_pass;
        $mail->SMTPSecure = 'tls';
        $mail->Port = intval($from_port);
		
		// sender settings
		$mail->setFrom($from_mail, $from_name);
		$mail->addReplyTo($from_mail, $from_name);
		
		// content settings
		$mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = nl2br($message);
        $mail->AltBody = $message;
      } catch (Exception $e) {
        echo ' Message could not be built. Mailer Error: ', $mail->ErrorInfo;
        $all_good = false;
		return($all_good);
      }
	  
	  # send each infdividual email
	  foreach($receivers as $receiver){
        try {
          $mail->clearAddresses();
          $mail->addAddress($receiver);
          $mail->send();
          echo(" Sent mail to ".$receiver.PHP_EOL);
        } catch (Exception $e) {
          echo(" Message could not be sent. Mailer Error: '". $mail->ErrorInfo .PHP_EOL);
          $all_good = false;
        }
      }
      return($all_good);
    }
	
  }

?>