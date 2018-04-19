<?php

  class VirtualGagesWS{
    
	
	/**
	 *
	 * RETURN : Integer value if possible to define it, NULL otherwise
	 */
	public static function define_min_timestamp_constraint(){
		$min_timestamp_given = Utils::get_arg(WSDefinitions::MIN_TIMESTAMP_ID);
		$min_datetime_given =  Utils::get_arg(WSDefinitions::MIN_DATETIME_ID);
		
		if(is_null($min_timestamp_given) && is_null($min_datetime_given)){
			// if none is given, get current time - 10 days
			return(time() - (WSDefinitions::BACK_DAYS_DEFAULT * 24 * 60 * 60));
		} elseif (is_null($min_timestamp_given) && !is_null($min_datetime_given)){
			// convert to timestamp and return it
			$tmp = strptime($min_datetime_given, WSDefinitions::HUMAN_DATE_FORMAT_ARG);
			return(mktime(0, 0, 0, $tmp['tm_mon']+1, $tmp['tm_mday'], $tmp['tm_year']+1900));
		} elseif (is_null($min_datetime_given) && !is_null($min_timestamp_given)){
			// just check if timestamp is a number and return it if possible
			if(is_numeric($min_timestamp_given)){
				return(intval($min_timestamp_given));
			} else {
				echo("not int");
				return(null);
			}
		} else {
			echo("both sets");
			return(null);
		}
	}
	
	/**
	 *
	 * RETURN : Integer value if possible to define it, NULL otherwise
	 */
	public static function define_max_timestamp_constraint(){
		$max_timestamp_given = Utils::get_arg(WSDefinitions::MAX_TIMESTAMP_ID);
		$max_datetime_given =  Utils::get_arg(WSDefinitions::MAX_DATETIME_ID);
		
		if(is_null($max_timestamp_given) && is_null($max_datetime_given)){
			// if none is given, get current time
			return(INF);
		} elseif (is_null($max_timestamp_given) && !is_null($max_datetime_given)){
			// convert to timestamp and return it
			$tmp = strptime($max_datetime_given, WSDefinitions::HUMAN_DATE_FORMAT_ARG);
			return(mktime(0, 0, 0, $tmp['tm_mon']+1, $tmp['tm_mday'], $tmp['tm_year']+1900));
		} elseif (is_null($max_datetime_given) && !is_null($max_timestamp_given)){
			// just check if timestamp is a number and return it if possible
			if(is_numeric($max_timestamp_given)){
				return(intval($max_timestamp_given));
			} else {
				return(null);
			}
		} else {
			return(null);
		}
	}
	
	/**
	 *
	 * RETURN : Array with integer timestamps
	 */
	public static function get_all_available_state_timestamps($realtime_folder_path){
		$return_array = array();
		$all_files_in_folder = scandir($realtime_folder_path);
		foreach ($all_files_in_folder as $cur_file_path){
			$splitted_file_name = explode("_", basename($cur_file_path));
			if(is_numeric($splitted_file_name[0])){
				$return_array[] = intval($splitted_file_name[0]);
			}
		}
		sort($return_array);
		return($return_array);
	}
	
	/**
     *
     * $sub_threshold_dict :
     * $disch_value :
     * RETURN :
     */
    public static function define_stage_flag($sub_threshold_dict, $stage_value){
      if ($sub_threshold_dict["action"] > 0){
        $temp_action = $sub_threshold_dict["action"];
      } else {
        $temp_action = $sub_threshold_dict["flood"];
      }
      if ($stage_value < $temp_action){
        $cur_state_flag = "'No Flood'";
      } elseif ($stage_value < $sub_threshold_dict["flood"]){
        $cur_state_flag = "'Action'";
      } elseif ($stage_value < $sub_threshold_dict["moderate"]){
        $cur_state_flag = "'Flood'";
      } elseif ($stage_value < $sub_threshold_dict["major"]){
        $cur_state_flag = "'Moderate'";
      } else {
        $cur_state_flag = "'Major'";
      }
      return($cur_state_flag);
    }
	
  }

?>
