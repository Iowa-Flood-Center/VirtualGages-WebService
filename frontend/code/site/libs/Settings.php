<?php

  class Settings{
    const SETTINGS_FILE_NAME = "../../../conf/settings.json";
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

      // change folder
      $old_folder_path = getcwd();
      chdir(dirname(__FILE__));

      // read and parse file
      if (!file_exists(Settings::SETTINGS_FILE_NAME)){
        echo("File not found: ".Settings::SETTINGS_FILE_NAME."\n");
        echo("  At:".getcwd()."\n");
        echo("File:".__FILE__."\n");
        exit(1);
      }
      $file_text = file_get_contents(Settings::SETTINGS_FILE_NAME);
      Settings::$attributes = json_decode($file_text, true);
      
      // get back to original folder
      chdir($old_folder_path);

      // basic check
      if(is_null(Settings::$attributes)){
        echo("Failed reading file".PHP_EOL);
        exit(1);
      }
    }
	
  }

?>
