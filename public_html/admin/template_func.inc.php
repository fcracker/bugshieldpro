<?php
function escape_linefeed($str) {
	$str = str_replace("\\", "\\\\", $str);
	$str = str_replace("\"", "\\\"", $str);
	$str = str_replace("\r", "\\r", $str);
	$str = str_replace("\n", "\\n", $str);
	
	return $str;
}
?>