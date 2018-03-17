<?php

  class Settings{
    const SETTINGS_FILE_NAME = "../../conf/settings.json";
	private static $attributes = NULL;
    
    /**
     * 
     * $attribute_id:
     * RETURN: Value if attribute exists, NULL otherwise.
     */
    public static function get($attribute_id){
      if(is_null(Settings::$attributes))
        Settings::read_file();
      if(array_key_exists($attribute_id, Settings::$attributes))
        return(Settings::$attributes[$attribute_id]);
      else
        return(NULL);
    }
    
    /**
     * Read settings file and parse it
     * RETURN: none.
     */
    private static function read_file(){
      // read and parse file
      $file_text = file_get_contents(Settings::SETTINGS_FILE_NAME);
      Settings::$attributes = json_decode($file_text, true);
      
      // basic check
      if(is_null(Settings::$attributes)){
        echo("Failed reading file".PHP_EOL);
        exit(1);
      }
    }
	
  }

?>