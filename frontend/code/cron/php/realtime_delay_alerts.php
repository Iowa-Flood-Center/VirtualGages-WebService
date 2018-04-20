<?php
  
  require_once("libs/RealtimeDelayAlert.php");
  
  RealtimeAlert::check_all_models();
  
  echo("# ### DONE ######################################## #".PHP_EOL);
  exit(0);
?>
