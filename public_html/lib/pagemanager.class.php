<?php
/*
* This file contains classes related to users and groups
*/
@ini_set("magic_quotes_gpc", "on");

class PageManager{
	var $pageid;
	var $table;
	
	
	function PageManager() {
		global $cfg;
		$this->table = $cfg['database']['prefix']."pages";
	}
		
	function getPageContent(){		
		$sql = "SELECT * FROM " . $this->table . " WHERE PageID='" . $this->pageid . "'";;
		
		$result = mysql_query($sql);
		if($fields = mysql_fetch_assoc($result)){
			$return = $fields;
		} else {
			$return = false;
		}
		mysql_free_result( $result );
		
		return $return;
	}
	
	function createPage($data) {		$sql = "INSERT INTO " . $this->table . " SET				PageName=\"" . mysql_escape_string($data["PageName"]) . "\",				PageTitle=\"" . mysql_escape_string($data["PageTitle"]) . "\",				PageContent=\"" . mysql_escape_string($data["PageContent"]) . "\",				is_menu_show=\"" . (isset($data['chk_menu_show'])?'y':'n') . "\"";
		if ( mysql_query ( $sql ) )
			return true;		else 			return false;	}
	
	function updatePage($data) {
		//we should first save the current version, in case we want to rollback
		$this->saveVersion($data);
	$sql = "UPDATE " . $this->table . " SET				PageName=\"" . mysql_escape_string($data["PageName"]) . "\",				PageTitle=\"" . mysql_escape_string($data["PageTitle"]) . "\",				PageContent=\"" . mysql_escape_string($data["PageContent"]) . "\",				is_menu_show=\"" . (isset($data['chk_menu_show'])?'y':'n') . "\"				WHERE PageID='" . $this->pageid . "'";
		if ( mysql_query ( $sql ) )
			return true;			
		else 
			return false;
	}
	
	function saveVersion($post) {
		global $cfg;
		$max = $cfg['pages']['rollback_limit'];
	
		//get the data first
		$data = $this->getPageContent();
			
		//check how many versions we already have, in order to keep it in the bounds set
		$sql = "select COUNT(*) as cnt,MIN(saved_on) as mini from mem_page_history WHERE pageID=".intval($this->pageid);
		
		
		
		$res = mysql_query($sql);
		$row = mysql_fetch_object($res);
		
		if($row->cnt >= $max) {
			//we first delete the oldest entry
			mysql_query("delete from mem_page_history where pageID=".$this->pageid." and saved_on='".$row->mini."' limit 1");
		} 
		
		//check the groups
		if(isset($post['SelectGroup'])) {
			$groups = implode(";",$post['SelectGroup']);
		}
		
		//we insert
			$sql = "INSERT INTO mem_page_history SET PageName=\"" . mysql_escape_string($data["PageName"]) . "\",PageTitle=\"" . mysql_escape_string($data["PageTitle"]) . "\",			PageContent=\"" . mysql_escape_string($data["PageContent"]) . "\",is_menu_show=\"" . $data['is_menu_show']. "\",pageID=".$this->pageid.",saved_on=NOW(),groups=\"".$groups."\"";		
		
			
			if ( mysql_query ( $sql ) )
			return true;			
		else 
			return false;
		
	}
	
	function getPageHistory($pageid) {
	$sql = "SELECT * from mem_page_history WHERE pageID=".intval($pageid)." ORDER BY saved_on DESC";
	
	
	$result = mysql_query($sql);
	
	$return = array();
	
	if(mysql_num_rows($result)){
		while($r = mysql_fetch_object($result))
			$return[]=$r;
	}
	
	return $return;
	}
	
	
	
	function deletePage() {
		if(is_array($this->pageid)) {
			for ( $i = 0; $i < sizeof( $this->pageid ); $i++ )
				$this->_deletePage($this->pageid[$i]);
		} else {
			$this->_deletePage($this->pageid);
		}
	}
	
	function _deletePage($pageid) {
		$sql = "DELETE FROM " . $this->table . " WHERE PageID='$pageid'";
		mysql_query($sql);
	}
	
	function searchPages($query){
		$result = new stdClass();
		
		// get page and page size
		if(isset($query['page'])){
			if(!is_numeric($query['page'])) $query['page'] = 1;
		}
		
		if(isset($query['pageSize'])){
			if(!is_numeric($query['pageSize'])) $query['pageSize'] = 10;
		}else{
			$query['pageSize'] = 0;
		}		
		// count result
		$sql = "SELECT COUNT(*) FROM ". $this->table;

		$result = mysql_query($sql);
		$fields = mysql_fetch_array($result, MYSQL_NUM);
		
		mysql_free_result($result);
		
		$return->total = $fields[0];
		if($query['pageSize'] == 0) $query['pageSize'] = $return->total; // if no page size assigned, try to return all
		if($query['pageSize'] == 0) $query['pageSize'] = 10; // if no record and no page size assigned, set page size to 10, it does not matter anyway
		
		// calucalate pages
		if(isset($query['page'])) $return->page = $query['page'];
		else $return->page = 1;
		if(isset($query['pageSize'])) $return->pageSize = $query['pageSize'];
		$return->totalPages = intval($return->total / $return->pageSize);
		if($return->total % $return->pageSize) $return->totalPages++;
		if($return->page > $return->totalPages) $return->page = $return->totalPages;
		if($return->page < 1) $return->page = 1;

		// search for result
		$sql = "SELECT PageID, PageName, PageTitle, is_menu_show FROM " . $this->table;

		if (isset($query['orderBy'])){
			if(strlen($query['orderBy']) > 0) $return->orderBy = $query['orderBy'];
		}
		
		$offset = $return->pageSize * ($return->page - 1);
		$sql .= " ORDER BY ".$return->orderBy." LIMIT ".$offset.", ".$return->pageSize;
		
		// execute sql and parse result
		$result = mysql_query($sql);

		$list = array();
		
		while ( $list[] = mysql_fetch_object($result)) {}
		
		array_pop($list); // because of unexpected insertion of one "false" array 
		mysql_free_result($result);
		$return->query = $query;
		$return->list = $list;
		return $return;
	}
}
?>