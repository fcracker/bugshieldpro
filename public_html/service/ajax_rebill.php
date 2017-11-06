<?php
include_once("../lib/security.inc.php");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/rebill_cycle.class.php");

$con = connect_database();

if(!isset($_POST["action"])) die("No action defined");

switch($_POST["action"]) {
  case "cancel":
              if(!isset($_POST["rid"])) die("Not all parameters defined!");
              
              $reb = new rebill_cycle;
              if($reb->remove_by_id($_POST["rid"])) {die("Rebill removed");} else {die("There was an issue removing rebill. Please contact administrator");}         

                  //die("Rebill removed");
              
              break;
  default:
          die("Undefined action!");
          break;
}