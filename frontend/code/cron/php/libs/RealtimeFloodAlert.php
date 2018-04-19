<?php

  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
  
  date_default_timezone_set('America/Chicago');
  
  require_once("PHPMailer/Exception.php");
  require_once("PHPMailer/PHPMailer.php");
  require_once("PHPMailer/SMTP.php");
  require_once("libs/Settings.php");
  require_once("libs/SettingsAlertsFloods.php");
  require_once("libs/WebServiceReader.php");
  require_once("libs/SitesDescriptionCSVReader.php");
  
  class RealtimeFloodAlert{
	  
    /**
     * Check all defined models and alert all users
     * RETURN: none
     */
    public function check_all_models(){
		
      $thresholds_dct = RealtimeFloodAlert::load_thresholds();
      
      // check current state
      //RealtimeFloodAlert::check_state($thresholds_dict);
      
      // check all forecasts
      $all_models = Settings::get("forecast_models");
      foreach($all_models as $cur_model){
        echo("Checking forecast for: ".$cur_model."\n");
        RealtimeFloodAlert::check_forecast($thresholds_dct, $cur_model);
      }
      
      echo("Loaded thresholds.\n");
    }
    
    //************************ PRIVATE METHODS ***********************//
    
    /**
     * Reads thresholds file and returns its content
     * RETURN: Dictionary of information
     */
    private static function load_thresholds(){
      $file_name = "/anci/dot_floodthresholds.json";
      $folder_path = Settings::get("raw_data_folder_path");
      $file_path = $folder_path.$file_name;
      $file_data = file_get_contents($file_path);
      return(json_decode($file_data, true));
    }
    
    /**
     * Reads web service and alerts if past data excedess thresholds
     * RETURN: none.
     */
    private static function check_state($now_timestamp){
      echo("# ### STATE ################################### #".PHP_EOL);
      $l_t = WebServiceReader::get_last_state_timestamp();
      RealtimeAlert::evaluate_time(NULL, $l_t, $now_timestamp);
    }
    
    /**
     * 
     * $thresholds_dct:
     * $model_id:
     * RETURN: none.
     */
    private static function check_forecast($thresholds_dct, $model_id){
      echo("# ### FORECAST : ".$model_id." ################ #".PHP_EOL);
      $fore_peaks = WebServiceReader::get_forecast_peaks($model_id);
      $dict_order = RealtimeFloodAlert::order_by_alert($fore_peaks);
      foreach($dict_order as $level => $all_ifis_id){
        echo($level." -> ".json_encode($all_ifis_id).PHP_EOL);
        // 1- get receivers for this level
        $receivers = RealtimeFloodAlert::get_contacted($level, $model_id);
        if(count($receivers) <= 0)
          continue;
        
        // 2- create message
        $msg = RealtimeFloodAlert::create_message($level, 
                                                  $fore_peaks,
                                                  $dict_order);
        if(is_null($msg))
          continue;
        
        // 3- send created message for all receivers
        RealtimeFloodAlert::communicate($receivers, $model_id, $msg);
		
		echo("Got here.".PHP_EOL);
      }
      
	  echo("Did ".$model_id.PHP_EOL);
	  
      // echo(json_encode($fore_peaks, JSON_PRETTY_PRINT));
      // RealtimeFloodAlert::evaluate_time($model_id, $f_t, $now_timestamp);
    }

    /**
     * 
     * $thresholds_dct:
     * RETURN:
     */
    private static function order_by_alert($thresholds_dct){
      $ret_dic = array();
      foreach(Settings::get("threshold_labels") as $idx => $label){
        $ret_dic[$idx] = array();
	  }
      foreach($thresholds_dct as $ifis_id => $values){
        $alert_value = RealtimeFloodAlert::convert_threshold_label_to_value($values["alert"]);
        array_push($ret_dic[$alert_value], $ifis_id);
      }
      return($ret_dic);
    }
    
    /**
     * 
     * $label:
     * RETURN:
     */
    private static function convert_threshold_label_to_value($label){
      $value = array_search($label, Settings::get("threshold_labels"));
      return($value ? $value : 0);
    }
	
	/**
	 *
	 * $value
	 * RETURN:
	 */
	private static function convert_threshold_value_to_label($value){
      return(Settings::get("threshold_labels")[$value]);
    }
    
    /**
     * 
     * $alert_level: Integer. Alert level.
     * RETURN: Array of strings with the emails to be contacted.
     */
    private static function get_contacted($alert_level, $model_id){
      $ret_array = array();
      
      // TODO - implement it
	  try{
	    $temp_dict = SettingsAlertsFloods::get("alert_floods");
		$temp_dict = $temp_dict["forecast"];
		if(!array_key_exists($model_id, $temp_dict)){
		  // echo("Not found: '".$model_id."' in ".json_encode($temp_dict).PHP_EOL);
          return($ret_array);
		}
	    $temp_dict = $temp_dict[$model_id];
		$alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
	    if(!array_key_exists($alert_label, $temp_dict)){
		  // echo("Not found: '".$alert_label."' in ".json_encode($temp_dict).PHP_EOL);
          return($ret_array);
		}
		$ret_array = $temp_dict[$alert_label];
      }catch(Exception $e){
		// echo("Error: ".$e.PHP_EOL);
	  }
      
      return($ret_array);
    }
    
    /**
     * 
	 * $alert_level: 
	 * $fore_peaks: 
	 * $alert_groups: 
	 * RETURN: 
     */
    private static function create_message($alert_level, 
	                                       $fore_peaks,
                                           $alert_groups){
      $ret_msg = "";
	  $counted = 0;
	  
	  $alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
	  $ret_msg .= "Virtual gage sites that will extrapolate '".$alert_label."' level:\n";
      foreach($alert_groups[$alert_level] as $fore_site){
        $fore_site_desc = SitesDescriptionCSVReader::get_desc_of_ifis($fore_site);
        $fore_peak = $fore_peaks[$fore_site];
		$fore_peak_date = new DateTime();
		$fore_peak_date->setTimestamp($fore_peak['timestamp']);
		$fore_peak_date = $fore_peak_date->format('Y-m-d, H:i:s');
		
        $ret_msg .= "- ".$fore_site_desc.": ";
		$ret_msg .= " will peak at ".$fore_peak['water_elev']." ft";
		$ret_msg .= " on ".$fore_peak_date.".\n";
		$counted += 1;
      }

      return($counted > 0 ? $ret_msg : null);
    }

    /**
	 * Submits e-mails with given messages to certain addresses.
	 * $receivers: Arrays of Strings. All emails addresses to receive a msg.
	 * $model_id: String. Id of the model that triggered the email call.
	 * $message: String. Raw content to be sent. Should not have HTML.
	 * RETURN: Boolean. True if email was sent, False otherwise.
     */	
    private static function communicate($receivers, $model_id, $message){
      
	  # get definitions
	  $from_host = SettingsAlertsFloods::get("smtp_host");
	  $from_port = SettingsAlertsFloods::get("smtp_port");
	  $from_mail = SettingsAlertsFloods::get("smtp_from_mail");
	  $from_pass = SettingsAlertsFloods::get("smtp_from_pass");
      $from_name = SettingsAlertsFloods::get("smtp_from_name");
	  
	  # define title
	  $title = "Virtual Gages flood alert: model ".$model_id;
	  
	  foreach($receivers as $receiver){
		$mail = new PHPMailer(true);
        try {
          //Server settings
          $mail->SMTPDebug = 0;
          $mail->isSMTP();
          $mail->Host = $from_host;
          $mail->SMTPAuth = true;
          $mail->Username = $from_mail;
          $mail->Password = $from_pass;
          $mail->SMTPSecure = 'tls';
          $mail->Port = intval($from_port);

          //Recipient
          $mail->setFrom($from_mail, $from_name);
          $mail->addAddress($receiver);
          $mail->addReplyTo($from_mail, $from_name);

          //Content
          $mail->isHTML(true);
          $mail->Subject = $title;
          $mail->Body = nl2br($message);
          $mail->AltBody = $message;

          $mail->send();
          echo("Sent mail to ".$receiver.PHP_EOL);
          return(true);
        } catch (Exception $e) {
          echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
          return(false);
        }
	  }
	  return;
    }
  }
  
?>
