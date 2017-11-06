<?php

class umMenu{
	var $groupID = 0;
	var $menus = array();
	
	function set_group_menus(){
		global $cfg;
		$return = false;
		if($this->groupID!=0){		
			$sql = "DELETE FROM ".$cfg['database']['prefix']."menu_group_mapping ";
			$sql .= "WHERE GroupID='".$this->groupID."'";			
			mysql_query($sql);
			
			for($i=0; $i<count($this->menus); $i++){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."menu_group_mapping ";
				$sql .= "(GroupID,MenuID) VALUES ";
				$sql .= "('".$this->groupID."','".$this->menus[$i]."')";
				mysql_query($sql);
			}
			
			$return = true;
		}
		return $return;
	}
	
	function get_main_menus(){		
		global $cfg;
		$sql1 = "SELECT nor, name, f1, f2, flag, value FROM ";
		$sql1 .= $cfg['database']['prefix']."menu ";
		if(!in_array($this->groupID, $cfg['site']['adminGroupIDs'])){
			$sql1 .= " WHERE value not like 'admin%' ";
		}else{
			$sql1 .= " WHERE 1 ";
		}
		$sql = $sql1 . "ORDER BY f1,f2,flag ";
		$result = mysql_query($sql);
		$menus = array();
		if(mysql_num_rows($result)){
			while($row=mysql_fetch_array($result, MYSQL_NUM)){
				$sql = $sql1 . " AND flag=1 AND f1='" .$row[2] . "'";	
				$rst = mysql_query($sql);
				if(mysql_num_rows($rst)){
					$menu = array();
					$menu["key"] = $row[0];
					$menu["name"] = $row[1];
					$menu["f1"] = $row[2];
					$menu["f2"] = $row[3];
					$menu["flag"] = $row[4];
					$menu["url"] = $row[5];
					$menus[] = $menu;
				}
				mysql_free_result($rst);
			}
		}
		return $menus;
	}
	
	function get_group_menus(){		
		global $cfg;
		//$this->groupID  = 1;
		$sql = "SELECT m.nor, m.name, m.f1, m.f2, m.flag, m.value ";
		$sql .= "FROM " . $cfg['database']['prefix']."menu AS m INNER JOIN ".$cfg['database']['prefix']."menu_group_mapping AS g ";
		$sql .= "ON m.nor=g.MenuID ";
		$sql .= "WHERE g.GroupID='".$this->groupID."' ";
		$sql .= "ORDER BY m.f1,m.f2,m.flag";
		
		$result = mysql_query($sql);
		$this->menus = array();
		if(mysql_num_rows($result)){
			while($row=mysql_fetch_array($result, MYSQL_NUM)){
				$menu = array();
				$menu["key"] = $row[0];
				$menu["name"] = $row[1];
				$menu["f1"] = $row[2];
				$menu["f2"] = $row[3];
				$menu["flag"] = $row[4];
				$menu["url"] = $row[5];
				$this->menus[] = $menu;
			}
		}
	}
	
	function get_groupID($userID){	
		global $cfg;
		$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."user_group_mapping WHERE UserID='$userID'";
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		$this->groupID = $row[0];
	}
	
	function getMenuArray() {
		$this->get_group_menus();
		$return = array();
		for($i=0; $i<count($this->menus); $i++){
			if($this->menus[$i]['f2'] == 0){
				if($this->menus[$i]['flag'] == 1) {
					$return[$this->menus[$i]["name"]] = $this->menus[$i]["url"];
				} else {
					$return[$this->menus[$i]["name"]] = array();
					$currNode = &$return[$this->menus[$i]["name"]];
				}
			}else{
				$currNode[$this->menus[$i]["name"]] = $this->menus[$i]["url"];
			}
		}

		return $return;

	}
	
	function get_menuKey($menuURL){
		global $cfg;
		$sql = "SELECT nor FROM ".$cfg['database']['prefix']."menu WHERE value='".$menuURL."'";
		$row = single_query_assoc($sql);
		if(count($row)) return $row['nor'];
		return false;
	}
	
	function get_GroupIDs($menuID){
		global $cfg;
		$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."menu_group_mapping WHERE MenuID='".$menuID."'";
		$rst = mysql_query($sql);
		$return  = array();
		if(mysql_num_rows($rst)){
			while($row=mysql_fetch_array($rst))
				$return[] = $row[0];
		}
		return $return;
	}
	
	function insert_Menu($menuName, $menuURL){
		global $cfg;
		$sql = "SELECT MAX(f1) as f1 FROM ".$cfg['database']['prefix']."menu";
		$row = single_query_assoc($sql);
		if(count($row)) $f1 = $row['f1']+1;
		else $f1 = 1;
		
		$sql = "INSERT INTO ".$cfg['database']['prefix']."menu SET 
				name='".$menuName."',
				value='".$menuURL."',
				f1='".$f1."', f2=0, flag=1";
		mysql_query($sql);
		return mysql_insert_id();
	}
	
	function update_Menu($menuID, $menuName){
		global $cfg;
		$sql = "UPDATE ".$cfg['database']['prefix']."menu SET name='$menuName' WHERE nor='$menuID'";
		mysql_query($sql);
	}
	
	function set_pageMenu($menuID, $GROUP){
		global $cfg;
		if($menuID==0) return false;
		$this->delete_groupMapping($menuID);
		
		for($i=0; $i<count($GROUP); $i++){
			$sql = "INSERT INTO ".$cfg['database']['prefix']."menu_group_mapping ";
			$sql .= "(GroupID,MenuID) VALUES ";
			$sql .= "('".$GROUP[$i]."','".$menuID."')";
			mysql_query($sql);
		}
		return true;
	}
	
	function delete_groupMapping($menuID){
		global $cfg;
		$sql = "DELETE FROM ".$cfg['database']['prefix']."menu_group_mapping ";
		$sql .= "WHERE MenuID='".$menuID."'";			
		mysql_query($sql);
	}
	
	function delete_menu($menuID){
		global $cfg;
		$sql = "SELECT f1 FROM ".$cfg['database']['prefix']."menu WHERE nor='".$menuID."'";
		$row = single_query_assoc($sql);
		if(count($row)) $f1 = $row['f1'];
		else return false;
		
		$sql = "DELETE FROM ".$cfg['database']['prefix']."menu WHERE nor='".$menuID."'";
		mysql_query($sql);
		
		$sql = "UPDATE ".$cfg['database']['prefix']."menu SET f1=f1-1 WHERE f1>".$f1;
		mysql_query($sql);
	}
}


function get_menuActiveIndex($userID, $URL, $menuType="script", $id=""){
	global $cfg;
	$return = 0;
	$URL = str_replace($cfg['site']['url'], '', $URL);
	if(trim($cfg['site']['folder'])!="" && trim($cfg['site']['folder'])!="/" && trim($cfg['site']['folder'])!="//") 
		$URL = str_replace($cfg['site']['folder'], '', $URL);

	$pattern = explode('?', $URL);
	if(count($pattern)>1){
		$URL = $pattern[0];
	}
	if($menuType!="script") $URL.= "?".$menuType."ID=".$id;
	while(true){
		if(substr($URL, 0, 1) == "/") $URL = substr($URL, 1);
		else break;
	}
	$sql = "SELECT f1
			FROM ".$cfg['database']['prefix']."menu
			WHERE nor
				IN (
					SELECT g.MenuID
					FROM ".$cfg['database']['prefix']."menu_group_mapping AS g
					INNER JOIN ".$cfg['database']['prefix']."user_group_mapping AS u ON g.GroupID = u.GroupID
					WHERE u.UserID = '".$userID."'
				)
			AND value LIKE '%".$URL."%'";
	
//	echo $sql;
	$rst = mysql_query($sql);
	if($row=mysql_fetch_array($rst, MYSQL_NUM)){
		$return = $row[0];
	}
	return $return;
}

function export(){
	echo "<script>alert('ok')</script>";
}
?>
