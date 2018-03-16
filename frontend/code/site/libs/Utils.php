<?php
  
  class Utils{
	
	/**
	 * Simple function to get GET arguments without warnings
	 * $arg_name : String.
	 * RETURN : Value from argument if it was provided, 'null' otherwise
	 */
	public static function get_arg($arg_name){
      return(isset($_GET[$arg_name]) ? $_GET[$arg_name] : null);
	}
	
	/**
	 * Rounds a timestamp hourly. It means: if given 14:26, gets 14:00.
	 * $real_timestamp :
	 * RETURN :
	 */
	public static function round_timestamp($real_timestamp){
		$real_minutes = date("i", $real_timestamp);
		$real_seconds = date("s", $real_timestamp);
		
		if($real_minutes < 30)
			$delta_time = -(($real_minutes * 60)+$real_seconds);
		else
			$delta_time = ((60-$real_minutes)*60) - $real_seconds;
		
		return($real_timestamp + $delta_time);
	}
	
  }

?>