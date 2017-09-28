<?php
function connect(){
  global $CONFIG;
  global $database;
  $database = new mysqli($CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['database']);
  if($CONFIG['connection']->connect_error)
    die("Connection Failed: " . $CONFIG['connection']->connect_error);
}
?>
