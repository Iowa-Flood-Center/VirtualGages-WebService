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
    
    //************************* PUBLIC METHODS ***********************//
    
    /**
     * Check all defined models and alert all users (past/forecast)
     * RETURN: none
     */
    public function check_all_models_past_fore(){
        
      $thresholds_dct = RealtimeFloodAlert::load_thresholds();
      
      // check current state
      RealtimeFloodAlert::check_past($thresholds_dct);
      
      // check all forecasts
      $all_models = Settings::get("forecast_models");
      foreach($all_models as $cur_model){
        echo("Checking forecast for: ".$cur_model."\n");
        RealtimeFloodAlert::check_forecast($thresholds_dct, $cur_model);
      }
    }

    /**
     * Check all defined models and alert all users (state/forecast)
     *
     */
    public function check_all_models_state_fore(){
      // check all forecasts
      $all_models = Settings::get("forecast_models");
      $all_alerted_models = SettingsAlertsFloods::get("alert_floods");
      $all_alerted_models = array_keys($all_alerted_models["forecast"]);
      $fore_models = array_intersect ($all_models, $all_alerted_models);
      foreach($fore_models as $cur_model){
        echo("Checking forecast for: ".$cur_model.PHP_EOL);
        RealtimeFloodAlert::check_state_forecast($cur_model);
      }
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
     * 
     * RETURN:
     */
    private static function check_state_forecast($model_id){
      echo("# ### STATE AND FORECAST ###################### #".PHP_EOL);
      $state_fore_peaks = WebServiceReader::get_state_fore_peaks($model_id);
      $dict_order = RealtimeFloodAlert::order_by_alert($state_fore_peaks);
      foreach($dict_order as $level => $all_ifis_id){
        echo("Flood level ".$level.": ".json_encode($all_ifis_id).PHP_EOL);
        
        // 0- basic check
        if(count($all_ifis_id) == 0){
          echo(" Skipping. No sites with this flags.".PHP_EOL);
          continue;
        }
        
        // 1- get receivers for this level
        $receivers = RealtimeFloodAlert::get_contacted($level, NULL);
        if(count($receivers) <= 0){
          echo(" No receivers for level ".$level.PHP_EOL);
          continue;
        }
      
        // 2- create message
        $msg = RealtimeFloodAlert::create_state_fore_message(
          $level, $state_fore_peaks, $dict_order);
        
        // 3 - check if there is something to be reported
        if(is_null($msg))
          continue;
      
        // 4- send created message for all receivers
        RealtimeFloodAlert::communicate($receivers, $model_id, $msg);
      }
    }

    /**
     * Reads web service and alerts if past data excedess thresholds
     * RETURN: none.
     */
    private static function check_past($now_timestamp){
      echo("# ### PAST #################################### #".PHP_EOL);
      $past_peaks = WebServiceReader::get_past_peaks();
      echo("Got heres.".PHP_EOL);
      $dict_order = RealtimeFloodAlert::order_by_alert($past_peaks);
      echo("Order by alert: got ".count(array_keys($dict_order))." keys.".PHP_EOL);
      foreach($dict_order as $level => $all_ifis_id){
        echo($level." -> ".json_encode($all_ifis_id).PHP_EOL);
        
        // 1- get receivers for this level
        $receivers = RealtimeFloodAlert::get_contacted($level, NULL);
        if(count($receivers) <= 0)
          continue;
      
        // 2- create message
        $msg = RealtimeFloodAlert::create_message($level, 
                                                  $past_peaks,
                                                  $dict_order, 
												  "past");
        if(is_null($msg))
          continue;
        
        // 3- send created message for all receivers
        RealtimeFloodAlert::communicate($receivers, NULL, $msg);
        
        echo("Got here.".PHP_EOL);
      }
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
                                                  $dict_order,
												  "fore");
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
     * RETURN: Dictionary. {"Flood":[ifis_id_1, ifis_id_2, ...], "Moderate":[ifis_id_3...]}
     */
    private static function order_by_alert($thresholds_dct){
      $ret_dic = array();
      
      // start receiving variable
      foreach(Settings::get("threshold_labels") as $idx => $label)
        $ret_dic[$idx] = array();
        
      foreach($thresholds_dct as $ifis_id => $values){
        if(!array_key_exists("past", $values)){
          // separate if there is only one source (past or forecast)
          $alert_value = RealtimeFloodAlert::convert_threshold_label_to_value($values["alert"]);
          array_push($ret_dic[$alert_value], $ifis_id);
        } else {
          // separate past peak
          $s_values = $values["past"];
          $alert_value = RealtimeFloodAlert::convert_threshold_label_to_value($s_values["alert"]);
          array_push($ret_dic[$alert_value], $ifis_id);

          // separate forecast peak
          $f_values = $values["forecast"];
          $alert_value = RealtimeFloodAlert::convert_threshold_label_to_value($f_values["alert"]);
          if(in_array($ifis_id, $ret_dic[$alert_value]))
            continue;
          array_push($ret_dic[$alert_value], $ifis_id);
        }
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
     * $model_id: String. The model id if it is a forecast or NULL if it is for the past.
     * RETURN: Array of strings with the emails to be contacted.
     */
    private static function get_contacted($alert_level, $model_id){
      $ret_array = array();
      
      try{
        $temp_dict = SettingsAlertsFloods::get("alert_floods");
        
        if(!is_null($model_id)){
          $temp_dict = $temp_dict["forecast"];
          if(!array_key_exists($model_id, $temp_dict)){
            echo("Not found: '".$model_id."' in ".json_encode($temp_dict).PHP_EOL);
            return($ret_array);
          }
          $temp_dict = $temp_dict[$model_id];
        } else {
          $temp_dict = $temp_dict["state"];
        }
        
        
        $alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
        if(!array_key_exists($alert_label, $temp_dict)){
          // echo("Not found: '".$alert_label."' in ".json_encode($temp_dict).PHP_EOL);
          return($ret_array);
        }
        $ret_array = $temp_dict[$alert_label];
      }catch(Exception $e){
        echo("Error: ".$e.PHP_EOL);
      }
      
      return($ret_array);
    }
    
    /**
     * 
     * $alert_level: 
     * $fore_peaks: 
     * $alert_groups:
     * $time_flag: expects 'past' or 'fore'
     * RETURN: 
     */
    private static function create_message($alert_level, 
                                           $fore_peaks,
                                           $alert_groups,
										   $time_flag){
      $ret_msg = "";
      $counted = 0;
	  
	  $verb_exceed = ($time_flag == "past" ? "exceeded" : "will exceed");
	  $verb_peak = ($time_flag == "past" ? "have peaked" : "will peak");
      
      $alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
      $ret_msg .= "Virtual gage sites that ".$verb_exceed." '".$alert_label."' level:\n";
      foreach($alert_groups[$alert_level] as $fore_site){
        $fore_site_desc = SitesDescriptionCSVReader::get_desc_of_ifis($fore_site);
        $fore_peak = $fore_peaks[$fore_site];
        $fore_peak_date = new DateTime();
        $fore_peak_date->setTimestamp($fore_peak['timestamp']);
        $fore_peak_date = $fore_peak_date->format('Y-m-d, H:i:s');
        
        $ret_msg .= "- ".$fore_site_desc.": ";
        $ret_msg .= $verb_peak." at ".$fore_peak['water_elev']." ft";
        $ret_msg .= " on ".$fore_peak_date.".\n";
        $counted += 1;
      }

      return($counted > 0 ? $ret_msg : null);
    }
    
    private static function create_state_fore_message(
        $alert_level, $state_fore_peaks, $alert_groups){
      $ret_msg = "";
      $counted = 0;
      
      $alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
      $ret_msg .= "Virtual gage sites exceeding '".strtoupper($alert_label)."' level:".PHP_EOL;
      foreach($alert_groups[$alert_level] as $fore_site){
        // get all the info
        $fore_site_desc = SitesDescriptionCSVReader::get_desc_of_ifis($fore_site);
        $fore_peaks = $state_fore_peaks[$fore_site];
        $s_flag_label = $fore_peaks['past']['alert'];
        $s_flag_value = RealtimeFloodAlert::convert_threshold_label_to_value($s_flag_label);
        $s_peak_wlvl = $fore_peaks['past']['water_elev'];
        $f_flag_label = $fore_peaks['forecast']['alert'];
        $f_flag_value = RealtimeFloodAlert::convert_threshold_label_to_value($f_flag_label);
        $f_peak_wlvl = $fore_peaks['forecast']['water_elev'];
        $f_peak_time = $fore_peaks['forecast']['timestamp'];
        
        // build beginning of message line
        $ret_msg .= "- ".$fore_site_desc." now in ".strtoupper($s_flag_label)." ";
        
        if($s_flag_value < $f_flag_value){
          // case 1: an alert will come
          $fore_peak_date = new DateTime();
          $fore_peak_date->setTimestamp($f_peak_time);
          $fore_peak_date = $fore_peak_date->format('Y-m-d, H:i:s');
          $ret_msg .= "and will peak to ".strtoupper($f_flag_label)." at ".$fore_peak_date;
        } elseif($s_peak_wlvl >= $f_peak_wlvl) {
          // case 2: an alert is on, but we have a negative trend
          $ret_msg .= "but it has a recession trend";
        } elseif(($s_flag_value == $f_flag_value)&&($s_peak_wlvl < $f_peak_time)) {
          // case 3: an alert is on and will rise, but will keep in the same flag
          $fore_peak_date = new DateTime();
          $fore_peak_date->setTimestamp($f_peak_time);
          $fore_peak_date = $fore_peak_date->format('Y-m-d, H:i:s');
          $ret_msg .= "and will peak at ".$fore_peak_date.", but keeping as ".strtoupper($f_flag_label);
        } else {
          $ret_msg .= "(no description available)".PHP_EOL;
          continue;
        } 
        $ret_msg .= ".".PHP_EOL;
        $counted += 1;
      }

      echo($ret_msg);
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
      if (is_null($model_id))
        $title = "Virtual Gages flood alert: past";
      else
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
