<?php
  header('Content-type: text/plain');
  date_default_timezone_set('America/Chicago');

  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  require_once("libs/Utils.php");
  require_once("libs/Settings.php");
  require_once("libs/WSDefinitions.php");
  require_once("libs/VirtualGagesWS.php");
  

  // define time constrains
  $min_timestamp = VirtualGagesWS::define_min_timestamp_constraint();
  $max_timestamp = VirtualGagesWS::define_max_timestamp_constraint();

  // get arguments
  $show =          Utils::get_arg(WSDefinitions::SHOW_ID);
  $forecast =      Utils::get_arg(WSDefinitions::FORECAST_ID);
  $only_forecast = Utils::get_arg(WSDefinitions::ONLY_FORECAST);
  $ifis_id_only =  Utils::get_arg(WSDefinitions::IFIS_ID_ONLY);
  $show_link_id =  Utils::get_arg(WSDefinitions::LINKID_ID);

  // print header
  echo("'ifis_id', 'timestamp', 'date_time', 'water_elevation', 'discharge', 'alert', 'flag'");
  if($show == WSDefinitions::SHOW_REAL_ID){
    echo(", 'data_timestamp', 'data_date_time', 'obs_value'");
    $glue_dict_ref = null;
    $glue_dict = null;
  } else {
    $glue_dict_ref = array();
    $glue_dict = array();
  }
  if($show_link_id == WSDefinitions::SHOW_LINKID_ID){ echo(", 'link_id'"); }
  echo("\n");
  
  // define paths
  $forecast_folder_path = Settings::get("input_folder_path")."forecast_last/";
  $realtime_folder_path = Settings::get("input_folder_path")."realtime_history/";
  $thresholds_file_path = Settings::get("raw_data_folder_path")."/anci/dot_floodthresholds.json";

  // show last 10 days
  if($only_forecast != 'yes'){
    $all_timestamps = VirtualGagesWS::get_all_available_state_timestamps($realtime_folder_path);
    foreach($all_timestamps as $cur_timestamp){
      
      // check if timestamp is in the constraint
      if(($cur_timestamp < $min_timestamp)||($cur_timestamp > $max_timestamp)){
        continue;
      }
      
      $realtime_file_path = $realtime_folder_path;
      $realtime_file_path .= $cur_timestamp;
      $realtime_file_path .= WSDefinitions::JSON_FILE_SUFIX;
      
      
      // read files
      $realtime_dict = json_decode(file_get_contents($realtime_file_path), true);
      $thresholds_dict = json_decode(file_get_contents($thresholds_file_path), true);
      
      $flag_added = false;
      foreach($realtime_dict as $cur_ifis_id => $cur_pair){
        
        // check if exclusive link id
        if(($ifis_id_only != null)&&($cur_ifis_id != $ifis_id_only)){
          continue;
        }
        
        $flag_added = false;
        $cur_ifis_id_str = (string)$cur_ifis_id;
        foreach($thresholds_dict["a_list"] as $cur_threshold_dict){
          if ($cur_threshold_dict["link_id"] == $cur_ifis_id_str){
            
            if (($show != WSDefinitions::SHOW_REAL_ID) && (isset($cur_pair["obs_stage"]))){
              $cur_stage = number_format($cur_pair["obs_stage"], 2, '.', '');
              $glue_dict_ref[$cur_ifis_id] = $cur_stage;
            } else {
              $cur_stage = number_format($cur_pair["stage"], 2, '.', '');
            }
            $cur_discharge = number_format($cur_pair["discharge"], 2, '.', '');
            
            // rounding
            $cur_rounded_ts = Utils::round_timestamp($cur_timestamp);
            $cur_rounded_dt = date(WSDefinitions::HUMAN_DATE_FORMAT_OUT, $cur_rounded_ts);
            
            // define alert flag
            if ($cur_threshold_dict["action"] > 0){
              $temp_action = $cur_threshold_dict["action"];
            } else {
              $temp_action = $cur_threshold_dict["flood"];
            }
            if ($cur_stage < $temp_action){
              $cur_state_flag = "'No Flood'";
            } elseif ($cur_stage < $cur_threshold_dict["flood"]){
              $cur_state_flag = "'Action'";
            } elseif ($cur_stage < $cur_threshold_dict["moderate"]){
              $cur_state_flag = "'Flood'";
            } elseif ($cur_stage < $cur_threshold_dict["major"]){
              $cur_state_flag = "'Moderate'";
            } else {
              $cur_state_flag = "'Major'";
            }
            
            // show main data
            echo(implode(", ", array($cur_ifis_id, $cur_rounded_ts, $cur_rounded_dt,
                         $cur_stage, $cur_discharge, $cur_state_flag, "'past'")));
                         
            // show real data if needed
            if($show == WSDefinitions::SHOW_REAL_ID){
              if(isset($cur_pair["obs_stage"])){ 
			    $obs_stage=number_format($cur_pair["obs_stage"], 2, '.', '');
	          } else {
				$obs_stage="-1.00";
			  }
              echo(", ".$cur_timestamp);
			  echo(", ".date(WSDefinitions::HUMAN_DATE_FORMAT_OUT, $cur_timestamp));
			  echo(", ".$obs_stage.""); 
            }
            
            // show link id if needed
            if($show_link_id == WSDefinitions::SHOW_LINKID_ID){
              if(isset($cur_pair["link_id"])){
                echo(", ".$cur_pair["link_id"]);
              } else {
                echo(", -1");
              }
            }
            
            echo("\n");
            $flag_added = true;
            break;
          }
        }
        if(!$flag_added){
          echo($cur_ifis_id.", 0, 0, 0, 0, 0\n");
        }
      }
    }
  }

  // show forecast
  if(!is_null($forecast)){
    // basic check for file existence
    $forecast_file_path = $forecast_folder_path;
    $forecast_file_path .= $forecast.WSDefinitions::JSON_FILE_SUFIX;
    $thresholds_dict = file_get_contents($thresholds_file_path);
    $thresholds_dict = json_decode($thresholds_dict, true);
	
    if(file_exists($forecast_file_path)){
      $realtime_dict = file_get_contents($forecast_file_path);
      $realtime_dict = json_decode($realtime_dict, true);
      foreach($realtime_dict as $cur_ifis_id => $cur_vector){
        
		
        // check if exclusive link id
        if(($ifis_id_only != null)&&($cur_ifis_id != $ifis_id_only)){
          continue;
        }
        
        // finds the thresholds dictionary
        $found_thr_dict = false;
        $cur_ifis_id_str = (string)$cur_ifis_id;
        foreach($thresholds_dict["a_list"] as $cur_threshold_dict){
          if ($cur_threshold_dict["link_id"] == $cur_ifis_id_str){
            $found_thr_dict = true;
            break;
          }
        }
        
        // define delta glue
        if ((!is_null($glue_dict_ref))&&(isset($glue_dict_ref[$cur_ifis_id]))) {
          $delta_glue = $glue_dict_ref[$cur_ifis_id] - $cur_vector[0]["stage"];
        } else {
          $delta_glue = 0;
        }
		
        // prints it
        foreach($cur_vector as $cur_fore_dict){
          
          $cur_raw_ts = $cur_fore_dict["timestamp"];
          
          // check if timestamp is in the time constraint
          if(($cur_raw_ts < $min_timestamp)||($cur_raw_ts > $max_timestamp)){
            continue;
          }
          
          $cur_rounded_dt = date(WSDefinitions::HUMAN_DATE_FORMAT_OUT, $cur_raw_ts);
          $cur_stage = number_format($cur_fore_dict["stage"] + $delta_glue, 2, '.', '');
          $cur_discharge = number_format($cur_fore_dict["discharge"], 2, '.', '');
          $cur_state_flag = VirtualGagesWS::define_stage_flag($cur_threshold_dict, $cur_stage);
          echo(implode(", ", array($cur_ifis_id, $cur_raw_ts, $cur_rounded_dt,
                       $cur_stage, $cur_discharge, $cur_state_flag, "'forecast'")));
          
          // plot extra commas
          if($show == WSDefinitions::SHOW_REAL_ID){ echo(", -1, '', -1.00"); }
                       
          // show link id if needed
          if($show_link_id == WSDefinitions::SHOW_LINKID_ID){
            if(isset($cur_fore_dict["link_id"])){
              echo(", ".$cur_fore_dict["link_id"]);
            } else {
              echo(", -1");
            }
          }
          echo("\n");
        }
      }
    }
  }
  
  
?>
