<?php

  require_once("libs/RealtimeFloodAlert.php");
  
  RealtimeFloodAlert::alert_past_floods();

  echo("# ### DONE ######################################## #".PHP_EOL);
  exit(0);
?>
