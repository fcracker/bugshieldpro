<?php

$d = file_get_contents("../../udata.txt");
//echo $d;
$a = unserialize($d);

foreach($a as $user) {


if($user["result"]=="DECLINED"){

//check if the user exists

}

}