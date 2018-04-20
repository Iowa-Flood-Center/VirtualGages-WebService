<?php

  class Settings{
    protected static $SETTINGS_FILE_NAME = "../../../conf/settings.json";
    protected static $attributes = NULL;
    
    /**
     * 
     * $attribute_id:
     * RETURN: Value if attribute exists, NULL otherwise.
     */
    public static function get($attribute_id){
      if(is_null(static::$attributes))
        static::read_file();
      if(array_key_exists($attribute_id, static::$attributes))
        return(static::$attributes[$attribute_id]);
      else
        return(NULL);
    }
    
    /**
     * Read settings file and parse it
     * RETURN: none.
     */
    private static function read_file(){
      // read and parse file
      $file_text = file_get_contents(static::$SETTINGS_FILE_NAME);
      static::$attributes = json_decode($file_text, true);
      
      // basic check
      if(is_null(static::$attributes)){
        echo("Failed reading file".PHP_EOL);
        exit(1);
      }
    }
  }

?>
