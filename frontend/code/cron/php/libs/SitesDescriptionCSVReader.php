<?php 
  
  class SitesDescriptionCSVReader {
    protected static $file_content = NULL;
	
	public static function get_desc_of_ifis($ifis_id){
      
	  if(is_null(static::$file_content))
        static::read_file();
	  
	  foreach(static::$file_content as $cur_line){
        $split_line = explode(Settings::get("sites_file_div"), $cur_line);
		$cur_ifis_id = intval($split_line[Settings::get("sites_file_ifis_id_index")]);
		$cur_desc = trim($split_line[Settings::get("sites_file_desc_index")]);
		if($ifis_id == $cur_ifis_id)
          return($cur_desc);
	  }
	  
	  return(null);
	}
	
	private static function read_file(){
      // read and parse file
      $file_text = file_get_contents(Settings::get("sites_file_path"));
      static::$file_content = explode("\n", $file_text);
      
      // basic check
      if(is_null(static::$file_content)){
        echo("Failed reading file".PHP_EOL);
        exit(1);
      }
    }
  }
  
?>