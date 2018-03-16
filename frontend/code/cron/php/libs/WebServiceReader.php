<?php
  
  class WebServiceReader {
    private static $url_state = NULL;
    private static $url_forecast_frame = NULL;
    private static $column_div = NULL;
    private static $timestamp_idx = NULL;

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
     * $model_id: forecast model id
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
     * Read settings file and fill static variables
     */
    private function read_settings(){
      WebServiceReader::$url_state = Settings::get("url_state");
      WebServiceReader::$url_forecast_frame = Settings::get("url_forecast_frame");
      WebServiceReader::$timestamp_idx = Settings::get("timestamp_index");
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
  }
  
?>
