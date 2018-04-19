<?php

  require_once("libs/RealtimeFloodAlert.php");
  
  RealtimeFloodAlert::check_all_models();

  echo("# ### DONE ######################################## #".PHP_EOL);
  exit(0);
?>
