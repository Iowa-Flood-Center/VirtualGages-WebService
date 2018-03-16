<?php
  
  require_once("libs/Settings.php");
  require_once("libs/WebServiceReader.php");
  require_once("libs/RealtimeAlert.php");
  
  RealtimeAlert::check_all_models();
  
  echo("Done.\n");
  exit(0);
?>
