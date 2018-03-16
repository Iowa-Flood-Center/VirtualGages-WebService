<?php

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

  require 'PHPMailer/Exception.php';
  require 'PHPMailer/PHPMailer.php';
  require 'PHPMailer/SMTP.php';

  class RealtimeAlert{
    
    private static $users_threshold = NULL;
    
    /**
     * Check all models and notify all users.
     * RETURN: none.
     */
    public function check_all_models(){
      $now_timestamp = time();
      
      // check current state
      RealtimeAlert::check_state($now_timestamp);
      
      // check all forecasts
      $all_models = Settings::get("forecast_models");
      foreach($all_models as $cur_model){
        RealtimeAlert::check_forecast($cur_model);
      }
    }
    
    /**
     * 
     * RETURN: none.
     */
    private static function check_state($now_timestamp){
      echo("Loaded ok\n");
      echo("# ### STATE ################################### #".PHP_EOL);
      $last_timestamp = WebServiceReader::get_last_state_timestamp();
      
      RealtimeAlert::evaluate_time(NULL, 
                                   $last_timestamp, 
                                   $now_timestamp);
    }

    /**
     * 
     * $model_id:
     * RETURN: none.
     */
    private static function check_forecast($model_id){
      echo("# ### FORECAST : ".$model_id." ################ #".PHP_EOL);
    }
    
    /**
     * Checks if a reference timestamp needs a contact and contact it
     * $model_id: Model id evaluated
     * $ref_timestamp: Reference timestamp for the model
     * $now_timestamp: 
     * RETURN: none.
     */
    private static function evaluate_time($model_id, 
                                          $ref_timestamp,
                                          $now_timestamp){

      // get delta time and basic check it
      $delta_time = ($now_timestamp - $ref_timestamp)/60;   # in minutes
      
      $color_idx = RealtimeAlert::define_color($delta_time);
      $color_lbl = Settings::get("alerts_labels")[$color_idx];
      $all_contacted = RealtimeAlert::get_contacted($color_idx);
      foreach($all_contacted as $cur_contacted){
          RealtimeAlert::communicate($model_id, $delta_time, 
                                     $cur_contacted, $color_lbl);
      }
    }
    
    /**
     * 
     * $delta_time: Integer. Delta time in minutes.
     * RETURN: 
     */
    private static function define_color($delta_time){
      if ($delta_time < 0) return(0);
      
      // get time thresholds and check it
      $alert_min = Settings::get("alerts_minutes");
      if (is_null($alert_min)){
        echo("No attribute 'alerts_minutes' in settings file.".PHP_EOL);
        quit(1);
      } elseif (!is_array($alert_min)) {
        echo("Attribute 'alerts_minutes' is not and array.".PHP_EOL);
        quit(1);
      }
      
      // find current color
      $color_idx = 0;
      foreach($alert_min as $cur_idx=>$cur_dt){
        if ($cur_dt > $delta_time ){
          $color_idx = $cur_idx - 1;
          break;
        } else
          $color_idx = $cur_idx;
      }
      return($color_idx);
    }
    
    /**
     * 
     * $color_situation: Integer.
     * RETURN: Array of strings. All emails to be contacted.
     */
    private static function get_contacted($color_situation){
      if(is_null(RealtimeAlert::$users_threshold))
        RealtimeAlert::set_users_thresholds();
      $ret_array = array();
      foreach(RealtimeAlert::$users_threshold as $cur_mail=>$cur_color){
        if($color_situation >= $cur_color)
          array_push($ret_array, $cur_mail);
      }
      return($ret_array);
    }

    /**
     * 
     * RETURN:
     */
    private static function set_users_thresholds(){
      $dict = array();
      $all_colors = Settings::get("alerts_labels");
      foreach(Settings::get("receivers") as $mail=>$color){
        $color_idx = array_search($color, $all_colors);
        $dict[$mail] = $color_idx;
      }
      RealtimeAlert::$users_threshold = $dict;
    }

    /**
     * Sends an alert email.
     * RETURN: none.
     */
    private static function communicate($model_id, 
                                        $delta_time,
                                        $contact,
                                        $color_label){
      
      // define variables
      $dt = intval($delta_time);
      $from_mail = Settings::get("smtp_from_mail");
      $from_name = Settings::get("smtp_from_name");
      $model_label = (is_null($model_id) ? "state": $model_id);
      
      // define title and message
      $title = Settings::get("smtp_from_name").": ";
      $title .= strtoupper($color_label);
      $title .= " level alert for ".$model_label;
      $message_html = "Model <strong>".$model_label."</strong> is ";
      $message_html .= "delaied <strong>".$dt."</strong> minutes.";
      $message_altn = "Model '".$model_label."' is delaied ".$dt;
      $message_altn .= " minutes.";
      
      $mail = new PHPMailer(true);
      try {
        //Server settings
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $from_mail;
        $mail->Password = Settings::get("smtp_from_pass");
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        //Recipient
        $mail->setFrom($from_mail, $from_name);
        $mail->addAddress($contact);
        $mail->addReplyTo($from_mail, $from_name);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = $message_html;
        $mail->AltBody = $message_altn;

        $mail->send();
        return(true);
      } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
        return(false);
      }
    }
  }

?>
