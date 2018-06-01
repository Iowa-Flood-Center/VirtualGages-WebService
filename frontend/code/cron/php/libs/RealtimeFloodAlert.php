<?php
  
  date_default_timezone_set('America/Chicago');
  
  require_once("libs/Settings.php");
  require_once("libs/SettingsAlertsFloods.php");
  require_once("libs/WebServiceReader.php");
  require_once("libs/SitesDescriptionCSVReader.php");
  require_once("libs/MailSender.php");
  
  class RealtimeFloodAlert{
    
    //************************* PUBLIC METHODS ***********************//
    
    /**
     * Check all defined models and alert all users (past/forecast)
     * RETURN: none
     */
    public function alert_past_floods(){
        
      $thresholds_dct = RealtimeFloodAlert::load_thresholds();
      
      // check current state
      RealtimeFloodAlert::check_past($thresholds_dct);
    }

    /**
     * Check all defined models and alert all users (state/forecast)
     *
     */
    public function check_all_models_state_fore(){
      // check all forecasts
      $all_models = Settings::get("forecast_models");
      $all_alerted_models = SettingsAlertsFloods::get("alert_state_fore");
      $all_alerted_models = array_keys($all_alerted_models);
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
        $receivers = RealtimeFloodAlert::get_contacted_state_fore($level, $model_id);
        if(count($receivers) <= 0){
          echo(" Skipping. No receivers for this level.".PHP_EOL);
          continue;
        }
		echo(" Receivers for ".$level.", ".$model_id.": ".json_encode($receivers).PHP_EOL);
      
        // 2- create message
        $msg = RealtimeFloodAlert::create_state_fore_message(
          $level, $state_fore_peaks, $dict_order);
        
        // 3 - check if there is something to be reported
        if(is_null($msg))
          continue;
      
        // 4- send created message for all receivers
		$title = "Virtual Gages alert: ".strtoupper($level)." level by model '".$model_id;
        MailSender::communicate($receivers, $title, $msg);
      }
    }

    /**
     * Reads web service and alerts if past data excedess thresholds
     * RETURN: none.
     */
    private static function check_past(){
      echo("# ### PAST #################################### #".PHP_EOL);
      
	  // get all past data for last 10 days and all potential contacts
	  $ws_raw_data = WebServiceReader::retrieve_raw_data(NULL);
	  $all_contacted = RealtimeFloodAlert::get_all_contacted_past();
	  
	  // define current timestamp
	  $cur_timestamp = time();
	  
	  // 
	  foreach($all_contacted as $flood_label => $time_receiver_dict){
		foreach($time_receiver_dict as $past_time => $past_receivers){
          // find sites fitting time and label constraints
          $min_timestamp = $cur_timestamp - (intval($past_time) * 60 * 60);
		  $exceed_sites = WebServiceReader::extract_exceeding_sites($ws_raw_data,
		                                                            $flood_label,
																	$min_timestamp,
																	$cur_timestamp);
          // define title, message and send e-mails if needed
		  if(is_null($exceed_sites) || (count($exceed_sites) == 0)){
            echo(" No sites exceeding level '".$flood_label."' in the last ".$past_time." hours.".PHP_EOL);
            continue;
          }
          $title = "Virtual Gages flood report: '".$flood_label."' level in the past ".$past_time." hours";
		  $msg = RealtimeFloodAlert::create_past_message($flood_label, 
                                                         $past_time,
                                                         $exceed_sites);
          MailSender::communicate($past_receivers, $title, $msg);
		}
      }
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
    
	private static function get_all_contacted_past(){
      return(SettingsAlertsFloods::get("alert_past"));
	}
		
    /**
     * 
     * $alert_level: Integer. Alert level.
     * $model_id: String. The model id if it is a forecast or NULL if it is for the past.
     * RETURN: Array of strings with the emails to be contacted.
     */
    private static function get_contacted_state_fore($alert_level, $model_id){
      $alert_label = RealtimeFloodAlert::convert_threshold_value_to_label($alert_level);
	  $temp_dict = SettingsAlertsFloods::get("alert_state_fore");
      
      try{
        if(!array_key_exists($alert_label, $temp_dict[$model_id])) return(array());
		$ret_array = $temp_dict[$model_id][$alert_label];
		
		print("Contacted for '".$alert_label."': ".json_encode($ret_array).PHP_EOL);
		
      }catch(Exception $e){
        echo("Error: ".$e.PHP_EOL);
      }
      
      return($ret_array);
    }

    /**
	 *
	 * $flood_label:
	 * $past_time:
	 * $exceed_sites:
	 * RETURN:
	 */
    private static function create_past_message($flood_label, 
                                                $past_time,
                                                $exceed_sites){
      $ret_msg = "Virtual gage sites exceeding '".strtoupper($flood_label)."' level in the last ".$past_time." hours.".PHP_EOL;
	  
      foreach($exceed_sites as $exceed_site){
        $fore_site_desc = SitesDescriptionCSVReader::get_desc_of_ifis($exceed_site);
		$ret_msg .= "- ".$fore_site_desc;
		$ret_msg .= "(<a href='http://s-iihr50.iihr.uiowa.edu/ifis/sc/test1/virtualgages_webservice/dst/frontend/code/site/virtualgage_graph.html?forecast_id=fc254ifc01qpf&ifis_id=".$exceed_site."'>see</a>)";
		$ret_msg .= PHP_EOL;
      }
	  
	  return($ret_msg);
    }

	/**
	 *
	 * $alert_level:
     * $state_fore_peaks:
     * $alert_groups:
	 */
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
        
		$show_link = true;
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
		  $show_link = false;
          continue;
        }
		if($show_link){
          $ret_msg .= "(<a href='http://s-iihr50.iihr.uiowa.edu/ifis/sc/test1/virtualgages_webservice/dst/frontend/code/site/virtualgage_graph.html?forecast_id=fc254ifc01qpf&ifis_id=".$fore_site."'>see</a>)";
		}
        $ret_msg .= ".".PHP_EOL;
        $counted += 1;
      }

      // echo($ret_msg);
      return($counted > 0 ? $ret_msg : null);
    }

  }
  
?>
