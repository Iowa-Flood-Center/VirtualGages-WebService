<?php
  error_reporting(E_ALL | E_STRICT );
  ini_set("display_errors", 1);

  require_once("Settings.php");
  
  class DataAccess{
    private static $data_server_url = NULL;
    const REALTIME_FILES_FOLDER = "realtime";
    const FORECAST_FILES_FOLDER = "forecast";

    // TODO - STAY - changed
    // 
    // $file_name: 
    // RETURN:
    public static function get_realtime_file_content($file_name){
      // build target data file URL and perform HTTP request
      $base_url = DataAccess::_get_base_url();
      if(is_null($base_url)){
        echo("TODO - base url is null.");
        return(NULL);
      }
      $base_url .= DataAccess::REALTIME_FILES_FOLDER."/";
      $fi_url = DataAccess::_build_url($base_url, $file_name);
      return(DataAccess::_http_request($fi_url));
    }
    
    // TODO - STAY - changed
    //
    // $file_direction: 
    // RETURN: Boolean.
    public static function check_realtime_file_exists($file_name){
      $file_content = DataAccess::get_realtime_file_content($file_name);
      return(($file_content == "" ? FALSE : TRUE));
    }

    // TODO - STAY - changed
    //
    // $folder_direction:
    // $runset_id: 
    // $file_ext:
    // RETURN:
    public static function list_realtime_folder_content($file_ext,
                                                        $echo_url=FALSE){
      // build target data folder URL and perform HTTP request
      $base_url = DataAccess::_get_base_url();
      if(is_null($base_url)) return(NULL);
      // $base_url .= DataAccess::REALTIME_FILES_FOLDER."/";
      $fd_url = DataAccess::_build_url($base_url, DataAccess::REALTIME_FILES_FOLDER);
      if($echo_url) echo($fd_url."<br />");
      $fd_html = DataAccess::_http_request($fd_url);
      return(DataAccess::_get_file_list($fd_html, $file_ext));
    }
    
    // TODO - STAY - changed
    //
    // 
    // $file_direction: can be both a relative file path or an array of directories/filename.
    // RETURN:
    public static function get_forecast_file_content($file_name){
      // build target data file URL and perform HTTP request
      $base_url = DataAccess::_get_base_url();
      if(is_null($base_url)) return(NULL);
      $base_url .= DataAccess::FORECAST_FILES_FOLDER."/";
      $fi_url = DataAccess::_build_url($base_url, $file_name);
      return(DataAccess::_http_request($fi_url));
    }

    // TODO - STAY - changed
    //
    // $file_direction: 
    // RETURN: Boolean.
    public static function check_forecast_file_exists($file_name){
      $file_content = DataAccess::get_forecast_file_content($file_name);
      return(($file_content == "" ? FALSE : TRUE));
    }
    
    // ////////////////////////// PRIV ////////////////////////////// //

    // TODO - STAY - changed
    //
    // $runset_id:
    // RETURN:
    private static function _get_base_url(){
      if(!DataAccess::_load_settings_if_needed()) return(NULL);
      return(DataAccess::$data_server_url);
    }
    
    // TODO - STAY - change inside
    // Changes content of $data_server_url parameter if it is null
    // RETURN: Boolean. TRUE if able to load, FALSE otherwise
    private static function _load_settings_if_needed(){
      if(!is_null(DataAccess::$data_server_url)) return(true);
      $url = Settings::get("raw_data_url");
      if(is_null($url)) return(false);
      DataAccess::$data_server_url = $url;
      return(true);
    }
    
    // TODO - STAY?
    // 
    // $base_url: 
    // $file_direction: 
    // RETURN: 
    private static function _build_url($base_url, $file_direction){
      if(is_string($file_direction)){
        return($base_url.$file_direction);
      }elseif(is_array($file_direction)){
        return ($base_url.implode($file_direction, "/"));
      } else {
        return(NULL);
      }
    }
    
    // TODO - STAY
    // Searches for all files with an extension in an HTML response.
    // Basically it text mines the response, assuming an Apache server.
    // $html_response: 
    // $file_ext: 
    // RETURN: 
    private static function _get_file_list($html_response, $file_ext){
      $regex = '/href=".*'.$file_ext.'"/';
      preg_match_all($regex, $html_response, $matches);
      $matches = $matches[0];
      for($i = 0; $i < count($matches); $i++){
        $matches[$i] = str_replace('href="', '', $matches[$i]);
        $matches[$i] = str_replace('"', '', $matches[$i]);
        if(DataAccess::_endsWith($matches[$i], '/')){
		  $matches[$i] = substr($matches[$i], 0, -1);
		}
		$matches[$i] = explode('/', $matches[$i]);
        $matches[$i] = end($matches[$i]);
      }
      // if listing directories, remove the first one (parent dir)
      if($file_ext == "\/"){
        $matches = array_splice($matches, 1, count($matches)-1);
      }
      return($matches);
    }
    
    // TODO - STAY
    // Just perform an HTTPS REQUEST ignoring the lack of certificate
    // $url: String. Final URL of the file to be read
    // RETURN: String with the file content if able to access it or NULL otherwise
    private static function _http_request($url){
      $context = array(
        "ssl"=>array(
          "verify_peer"=>false,
          "verify_peer_name"=>false,
        ),
      );  
      $context = stream_context_create($context);
      $content = @file_get_contents($url, false, $context);
      return($content);
    }
    
    // TODO - STAY?
    // return tru if $str ends with $sub
    private static function _endsWith($str, $sub) {
      return(substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub);
    }
  }
  
  // TODO: remove this test
  
  // chdir("..");
  
  // echo(DataAccess::get_realtime_file_content("1532285100_dot_stages.json"));
  // echo("<br /><br />");
  // echo(DataAccess::check_realtime_file_exists("1532285100_dot_stages.json"));
  // echo("<br /><br />");
  // echo(DataAccess::check_realtime_file_exists("1532285100_dot_stagesa.json"));
  // echo("<br /><br />");
  // print_r(DataAccess::list_realtime_folder_content(".json", $echo_url=TRUE));
  // echo("<br /><br />");
  // echo(DataAccess::get_forecast_file_content("fc254frkmrmsdaifchrrr_dot_stages.json"));
  // echo("<br /><br />");
  // echo(DataAccess::check_forecast_file_exists("fc254frkmrmsdaifchrrr_dot_stagesa.json"));
  // echo("<br /><br />");
  // echo(DataAccess::check_forecast_file_exists("fc254frkmrmsdaifchrrr_dot_stages.json"));
  
  // echo("<br /><br />END.");
?>
