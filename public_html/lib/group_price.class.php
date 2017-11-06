<?php

class umGroupPrice{
	
	function getPrice(){
		global $cfg;
		$sql = "SELECT GroupID, Price, Upkeep FROM ".$cfg['database']['prefix']."group WHERE Memo>0 ORDER BY Memo";
		$reAry = array();
		$rst = mysql_query($sql);
		if(mysql_num_rows($rst)){
			while($row=mysql_fetch_array($rst, MYSQL_NUM)){
				$tempAry = array();
				$tempAry['ID'] = $row[0];
				$tempAry['Price'] = $row[1];
				$tempAry['Upkeep'] = $row[2];
				$reAry[] = $tempAry;	
			}
		}
		return $reAry;
	}
	
	function updatePrice($aryPrice){
		global $cfg;
		foreach ($aryPrice as $key=>$value){
			if (strpos($key, "-") !== FALSE){
				$keys = split("-", $key);
				$sql = "UPDATE ".$cfg['database']['prefix']."group ";
				$sql .= "SET ".$keys[0]."='".$value."'"." WHERE GroupID='".$keys[1]."'";
				mysql_query($sql);
			}
		}
	}
}
?>