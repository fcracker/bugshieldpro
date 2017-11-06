<?php

class umPrice{
	
	function getPrice(){
		global $cfg;
		$sql = "SELECT spKey, Price, ShoppingPrice FROM ".$cfg['database']['prefix']."shopping_price ORDER BY ShoppingPrice DESC";
		$reAry = array();
		$rst = mysql_query($sql);
		if(mysql_num_rows($rst)){
			while($row=mysql_fetch_array($rst, MYSQL_NUM)){
				$tempAry = array();
				$tempAry['Key'] = $row[0];
				$tempAry['Price'] = $row[1];
				$tempAry['ShoppingPrice'] = $row[2];
				$reAry[] = $tempAry;	
			}
		}
		return $reAry;
	}
	
	function getNextPrice($ShoppingPrice=0){
		global $cfg;
		$sql = "SELECT Price, ShoppingPrice FROM ".$cfg['database']['prefix']."shopping_price ";
		if($ShoppingPrice != 0) $sql .= "WHERE ShoppingPrice='".$ShoppingPrice."' ";
		$sql .= "ORDER BY ShoppingPrice DESC LIMIT 0,1";
		$reAry = array();
		$rst = mysql_query($sql);
		if(mysql_num_rows($rst)){
			$row = mysql_fetch_array($rst, MYSQL_NUM);
			$reAry['Price'] = $row[0];
			$reAry['ShopingPrice'] = $row[1];
		}
		return $reAry;
	}
	
	function setPrice($Price, $ShoppingPrice){
		global $cfg;
		$sql = "INSERT INTO ".$cfg['database']['prefix']."shopping_price ";
		$sql .= "(Price, ShoppingPrice) VALUES ";
		$sql .= "('".$Price."','".$ShoppingPrice."')";
		mysql_query($sql);
	}
	
	function updatePrice($key, $Price, $ShoppingPrice){
		global $cfg;
		$sql = "UPDATE ".$cfg['database']['prefix']."shopping_price ";
		$sql .= "SET Price='".$Price."', ShoppingPrice='".$ShoppingPrice."' ";
		$sql .= "WHERE spKey='".$key."' ";
		mysql_query($sql);
	}
	
	function deletePrice($key){
		global $cfg;
		$sql = "DELETE FROM ".$cfg['database']['prefix']."shopping_price ";
		$sql .= "WHERE spKey='".$key."' ";
		mysql_query($sql);
	}
}

?>