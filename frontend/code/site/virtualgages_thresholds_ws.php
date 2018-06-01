<?php
  header('Content-Type: application/json');

  require_once("libs/Utils.php");
  require_once("libs/Settings.php");
  require_once("libs/WSDefinitions.php");
  
  // get argument
  $show_link_id = Utils::get_arg(WSDefinitions::IFIS_ID_ONLY);
  
  // define and read thresholds file
  $thresholds_fi_name = "dot_floodthresholds.json";
  $thresholds_fd_path = Settings::get("raw_data_folder_path")."/anci/";
  $thresholds_fi_path = $thresholds_fd_path.$thresholds_fi_name;
  $thresholds_text = file_get_contents($thresholds_fi_path);
  $thresholds_dict = json_decode($thresholds_text, true);
  
  // this can be used... or not
  $thesholds_list = array();
  
  // gather threshold(s)
  foreach($thresholds_dict["a_list"] as $cur_threshold_dict){
	if(!is_null($show_link_id)){
      if($cur_threshold_dict["link_id"] == $show_link_id){
        echo(json_encode($cur_threshold_dict));
	    exit(0);
      }
	} else {
      array_push($thesholds_list, $cur_threshold_dict);
	}
  }
  
  // if got here, show the list
  echo(json_encode($thesholds_list));
?>