<?php
  
  class WebServiceReader {
    private static $url_state = NULL;
    private static $url_forecast_frame = NULL;
    private static $column_div = NULL;
    private static $ifis_id_idx = NULL;
    private static $timestamp_idx = NULL;
    private static $water_elev_idx = NULL;
    private static $discharge_idx = NULL;
    private static $alert_idx = NULL;

    /**
     * Load URL if needed, retrive data, parse it, get max timestamp
     * RETURN: Integer if retrived any data, NULL otherwise
     */
    public function get_last_state_timestamp(){
      if(is_null(WebServiceReader::$url_state))
        WebServiceReader::read_settings();
      $raw_data = WebServiceReader::retrieve_raw_data(NULL);
      $all_timestamps = WebServiceReader::get_timeseries($raw_data);
      return(is_null($all_timestamps) ? NULL : max($all_timestamps));
    }
    
    /**
     * Load URL if needed, retrive data, parse it, get min timestamp
     * $model_id: String. Forecast model id
     * RETURN: Integer if retrived any data, NULL otherwise
     */
    public function get_first_forecast_timestamp($model_id){
      if(is_null(WebServiceReader::$url_forecast_frame))
        WebServiceReader::read_settings();
      $raw_data = WebServiceReader::retrieve_raw_data($model_id);
      $all_timestamps = WebServiceReader::get_timeseries($raw_data);
      return(is_null($all_timestamps) ? NULL : min($all_timestamps));
    }
	
    /**
     * 
     * $model_id: String. Forecast model id
     * RETURN: Dictionary in the form of {ifis_id: {time, peak_value, flag}}
     */
    public function get_forecast_peaks($model_id){
      if(is_null(WebServiceReader::$url_forecast_frame))
        WebServiceReader::read_settings();
      $raw_data = WebServiceReader::retrieve_raw_data($model_id);
      return(WebServiceReader::get_peaks($raw_data));
    }
	
	//************************ PRIVATE METHODS ***********************//

    /**
     * Read settings file and fill static variables
     */
    private function read_settings(){
      WebServiceReader::$url_state = Settings::get("url_state");
      WebServiceReader::$url_forecast_frame = Settings::get("url_forecast_frame");
      WebServiceReader::$ifis_id_idx = Settings::get("ifis_id_index");
      WebServiceReader::$timestamp_idx = Settings::get("timestamp_index");
      WebServiceReader::$water_elev_idx = Settings::get("waterelev_index");
      WebServiceReader::$discharge_idx = Settings::get("discharge_index");
      WebServiceReader::$alert_idx = Settings::get("alert_index");
      WebServiceReader::$column_div = Settings::get("vg_webservice_div");
    }
	
    /**
     * Retrieve raw data from the webservice
     * $model_id: Model id of the forecast. If null, return only state.
     * RETURN: String.
     */
    private function retrieve_raw_data($model_id){
      if(is_null($model_id))
        $url = WebServiceReader::$url_state;
      else
        $url = WebServiceReader::$url_forecast_frame.$model_id;
      echo("Acessing:".$url.PHP_EOL);
      return(file_get_contents($url));
    }
	
   /**
    * 
    * $ws_raw_data: String.
    * RETURN: Integer if retrived any data, NULL otherwise.
    */
   private function get_timeseries($ws_raw_data){
      // basic check - not null
      if (is_null($ws_raw_data)){
        echo("Web service return is null.".PHP_EOL);
        return(NULL);
      }
      
      // basi check - not empty
      $num_chars = strlen($ws_raw_data);
      if ($num_chars==0){
        echo("Web service return is empty.".PHP_EOL);
        return(NULL);
      }
	  
      // split it
      $all_lines = explode(PHP_EOL, $ws_raw_data);
      echo("Processing ".$num_chars." characters in ");
      echo(sizeof($all_lines)." lines.".PHP_EOL);
	  
      // extract column
      $all_timestamps = array();
      $div = WebServiceReader::$column_div;
      $idx = WebServiceReader::$timestamp_idx;
      foreach($all_lines as $cur_csv_line){
        $split_line = explode($div, $cur_csv_line);
        if(count($split_line) < ($idx+1)) continue;
        $cur_timestamp = $split_line[WebServiceReader::$timestamp_idx];
        if(!is_numeric($cur_timestamp)) continue;
		array_push($all_timestamps, intval($cur_timestamp));
      }
	  
      // return
      return($all_timestamps);
    }
	
    /**
     * 
     * $csv_raw_data:
     * RETURN:
     */
    private function get_peaks($ws_raw_data){
      $ret_dict = array();
      
      // basic check - not null
      if (is_null($ws_raw_data)){
        echo("Web service return is null.".PHP_EOL);
        return($ret_dict);
      }
      
      // basic check - not empty
      $num_chars = strlen($ws_raw_data);
      if ($num_chars==0){
        echo("Web service return is empty.".PHP_EOL);
        return(NULL);
      }
      
      // split it
      $all_lines = explode(PHP_EOL, $ws_raw_data);
      echo("Processing ".$num_chars." characters in ");
      echo(sizeof($all_lines)." lines.".PHP_EOL);

      // extract column
      $all_timestamps = array();
      $div = WebServiceReader::$column_div;
      $i_idx = WebServiceReader::$ifis_id_idx;
      $t_idx = WebServiceReader::$timestamp_idx;
      $e_idx = WebServiceReader::$water_elev_idx;
      $d_idx = WebServiceReader::$discharge_idx;
      $a_idx = WebServiceReader::$alert_idx;
      $header = true;
      
      foreach($all_lines as $cur_csv_line){
        $split_line = explode($div, $cur_csv_line);
        if(count($split_line) < ($a_idx+1)) continue;
        
        if($header){
          $header = false;
          continue;
        }
        
        $cur_ifis_id = intval($split_line[$i_idx]);
        $cur_timestamp = intval($split_line[$t_idx]);
        $cur_water_elv = floatval($split_line[$e_idx]);
        $cur_discharge = floatval($split_line[$d_idx]);
        $cur_alert = trim(str_replace("'", "", $split_line[$a_idx]));
        
        if(!is_numeric($cur_timestamp)) continue;
        
        if(!array_key_exists($cur_ifis_id, $ret_dict)){
          $ret_dict[$cur_ifis_id] = array(
            "timestamp" => $cur_timestamp,
            "water_elev" => $cur_water_elv,
            "discharge" => $cur_discharge,
            "alert" => $cur_alert);
		} else {
          if($cur_water_elv > $ret_dict[$cur_ifis_id]["water_elev"]){
            $ret_dict[$cur_ifis_id]["timestamp"] = $cur_timestamp;
            $ret_dict[$cur_ifis_id]["water_elev"] = $cur_water_elv;
            $ret_dict[$cur_ifis_id]["discharge"] = $cur_discharge;
            $ret_dict[$cur_ifis_id]["alert"] = $cur_alert;
          }
        }
      }
      
      return($ret_dict);
    }
  }
  
?>
