<?php
define("PROTECT_URL_LENGTH_MIN", 1);
define("PROTECT_URL_LENGTH_MAX", 250);
define("REDIRECT_URL_LENGTH_MIN", 1);
define("REDIRECT_URL_LENGTH_MAX", 250);


class umProtect{
	var $protectID = 0;
	var $protectType = 'F';
	var $protectURL = "";
	var $redirURL = "";
	var $memo = "";
	var $allowGroups = array();
	
	function get_protect(){
		global $cfg;
		$return = false;
		
		if(!is_numeric($this->protectID)) $this->protectID = 0;

		$sql = "SELECT ProtectID, ProtectType, ProtectURL, RedirURL, Memo ";
		$sql .= "FROM ".$cfg['database']['prefix']."protect ";
		if($this->protectID == 0){
			$sql .= "WHERE ProtectURL='".trim($this->protectURL)."'";
		}else{
			$sql .= "WHERE ProtectID=".$this->protectID;
		}
		
		$result = mysql_query($sql);
		if($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$this->protectID = $fields[0];
			$this->protectType = $fields[1];
			$this->protectURL = $fields[2];
			$this->redirURL = $fields[3];
			$this->memo = $fields[4];
			$return = true;
		}
		mysql_free_result($result);
		
		$sql = "SELECT g.GroupID, g.GroupTitle, g.DefaultGroup, g.Token, g.Status, g.Memo ";
		$sql .= "FROM ".$cfg['database']['prefix']."group g, ".$cfg['database']['prefix']."protect_group_mapping m ";
		$sql .= "WHERE m.GroupID = g.GroupID AND m.ProtectID=".$this->protectID." ORDER BY g.GroupID";
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$group = new umGroup();
			$group->groupID = $fields[0];
			$group->groupTitle = $fields[1];
			$group->defaultGroup= $fields[2];
			$group->token= $fields[3];
			$group->status = $fields[4];
			$group->memo = $fields[5];
			$this->allowGroups[] = $group;
		}
		mysql_free_result($result);
		
		return $return;
	}
	
	function create_protect(){
		global $cfg;
		$return = false;
		
		if($this->protectType == 'F'){
			/* create .htaccess file in protected folder */
			// load all groups
			$groups = new umResult();
			$tempGroup = new umGroup();
			$groups = $tempGroup->search_groups(NULL);			
			
			$htaccess = "";
			$htaccess .= "RewriteEngine On\n\n";
			for($i = 0; $i < count($groups->list); $i++){
				$selected = false;
				for($j = 0; $j < count($this->allowGroups); $j++){
					if($this->allowGroups[$j]->groupID == $groups->list[$i]->groupID) $selected = true;
				}
				//if($selected) $htaccess .= "RewriteCond %{HTTP_COOKIE} !^.*".$cfg['site']['cookiePrefix']."auth_groups=.*\\.".$groups->list[$i]->groupID."-".$groups->list[$i]->token."\\..*\n";
			}
			
			$href_base_mod = str_replace($cfg['site']['root'],"",$this->protectURL);			
			//figure out how deep we are
			$depth = substr_count($href_base_mod,"/");			
			$pre_path = str_repeat("../",$depth);			
			$htaccess .= "RewriteRule ^([^?]*) ".$pre_path."protect_router.php?protect=@@pid@@&route=$1 [L,QSA]\n";
			$htaccess = "\n###Protection Folder Begins###\n".$htaccess."\n###Protection Folder Ends###\n";
			
			$htaccessFile = $this->protectURL."/.htaccess";
			if(file_exists($htaccessFile)){
				$originalFile = file_get_contents($htaccessFile);
				$fileContents = array();
				$fileContents = explode("\n###Protection Folder Begins###\n", $originalFile);
				$htaccess = $fileContents[0].$htaccess;
				if(count($fileContents) > 1){
					$originalFile = $fileContents[1];
					$fileContents = array();
					$fileContents = explode("\n###Protection Folder Ends###\n", $originalFile);
					if(count($fileContents) > 1) $htaccess .= $fileContents[1];
				}
				
				
			}
			if($fh = fopen($htaccessFile, 'w')){
				fwrite($fh, $htaccess);
				fclose($fh);
				$return = true;
			}
			
		}else{
			/* create .htaccess file in root */
			$return = $this->_create_root_htaccess($this);
		}
		
		if($return){
			$return = false;
			
			$sql = "INSERT INTO ".$cfg['database']['prefix']."protect ";
			$sql .= "(ProtectType, ProtectURL, RedirURL, Memo) VALUES (";
			$sql .= "'".$this->protectType."', ";
			$sql .= "'".db_escape_characters($this->protectURL)."', ";
			$sql .= "'".db_escape_characters($this->redirURL)."', ";
			$sql .= "'".db_escape_characters($this->memo)."')";
			if(mysql_query($sql)){
				$this->protectID = mysql_insert_id();
				if($this->protectID != 0){
					if(count($this->allowGroups) != 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."protect_group_mapping ";
						$sql .= "(ProtectID, GroupID) VALUES ";
						for($i = 0; $i <count($this->allowGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->protectID.", ".$this->allowGroups[$i]->groupID.")";
						}
						if(mysql_query($sql)) $return = true;
						
						//ok, now re-do the htaccess file so you get the protect id in the router
						if($this->protectType == 'F') {
							//we are interested just in folders for this
							$originalFile = file_get_contents($htaccessFile);
							$text = str_replace("@@pid@@",$this->protectID,$originalFile);
							
							if($fh = fopen($htaccessFile, 'w')){
								fwrite($fh, $text);
								fclose($fh);
								$return = true;
							}
							
						}
						
					}else{
						$return = true;
					}
				}
			}
		}
		
		return $return;
	}
	
	function update_protect(){
		global $cfg;
		$return = false;
		
		if($this->protectType == 'F'){
			/* create .htaccess file in protected folder */
			// load all groups
			$groups = new umResult();
			$tempGroup = new umGroup();
			$groups = $tempGroup->search_groups(NULL);			
			
			$htaccess = "";
			$htaccess .= "RewriteEngine On\n\n";
			for($i = 0; $i < count($groups->list); $i++){
				$selected = false;
				for($j = 0; $j < count($this->allowGroups); $j++){
					if($this->allowGroups[$j]->groupID == $groups->list[$i]->groupID) $selected = true;
				}
				//if($selected) $htaccess .= "RewriteCond %{HTTP_COOKIE} !^.*".$cfg['site']['cookiePrefix']."auth_groups=.*\\.".$groups->list[$i]->groupID."-".$groups->list[$i]->token."\\..*\n";
			}
			
			$href_base_mod = str_replace($cfg['site']['root'],"",$this->protectURL);			
			//figure out how deep we are
			$depth = substr_count($href_base_mod,"/");			
			$pre_path = str_repeat("../",$depth);			
			$htaccess .= "RewriteRule ^([^?]*) ".$pre_path."protect_router.php?protect=".$this->protectID."&route=$1 [L,QSA]\n";
			$htaccess = "\n###Protection Folder Begins###\n".$htaccess."\n###Protection Folder Ends###\n";
			
			$htaccessFile = $this->protectURL."/.htaccess";
			if(file_exists($htaccessFile)){
				$originalFile = file_get_contents($htaccessFile);
				$fileContents = array();
				$fileContents = explode("\n###Protection Folder Begins###\n", $originalFile);
				$htaccess = $fileContents[0].$htaccess;
				if(count($fileContents) > 1){
					$originalFile = $fileContents[1];
					$fileContents = array();
					$fileContents = explode("\n###Protection Folder Ends###\n", $originalFile);
					if(count($fileContents) > 1) $htaccess .= $fileContents[1];
				}
				if($fh = fopen($htaccessFile, 'w')){
					fwrite($fh, $htaccess);
					fclose($fh);
					$return = true;
				}
			}			
			
		}else{
			/* create .htaccess file in root */
			$return = $this->_create_root_htaccess($this);
		}
		
		if($return){
			$return = false;
			
			if(!is_numeric($this->protectID)) $this->protectID = 0;

			$sql = "UPDATE ".$cfg['database']['prefix']."protect SET ";
			$sql .= "RedirURL='".db_escape_characters($this->redirURL)."', ";
			$sql .= "Memo='".db_escape_characters($this->memo)."' ";
			$sql .= "WHERE ProtectID=".$this->protectID;
			if(mysql_query($sql)){
				$sql = "DELETE FROM ".$cfg['database']['prefix']."protect_group_mapping WHERE ProtectID=".$this->protectID;
				if(mysql_query($sql)){
					if(count($this->allowGroups) != 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."protect_group_mapping ";
						$sql .= "(ProtectID, GroupID) VALUES ";
						for($i = 0; $i <count($this->allowGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->protectID.", ".$this->allowGroups[$i]->groupID.")";
						}
						if(mysql_query($sql)) $return = true;
						
						
					}else{
						$return = true;
					}
				}
			}
		}
		
		return $return;
	}
	
	
	function search_protect($query){
		global $cfg;
		
		$return = new umResult();

		// trim all inputs
		if(isset($query['selectedID'])) $query['selectedID'] = NULL;
		if(isset($query['operation'])) $query['operation'] = NULL;
		if($query != NULL){
			foreach($query as $name => $value)
				$query[$name] = trim($value);
		}
		
		// get page and page size
		if(isset($query['page'])){
			if(!is_numeric($query['page'])) $query['page'] = 1;
		}
		if(isset($query['pageSize'])){
			if(!is_numeric($query['pageSize'])) $query['pageSize'] = 10;
		}else{
			$query['pageSize'] = 0;
		}
		
		// construction conditions
		$c = "";
		if(isset($query['keywords'])){
			if(strlen($query['keywords']) > 0){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$keywords = explode(" ", $query['keywords']);
				$c .= " (";
				for($i = 0; $i < sizeof($keywords); $i++){
					if($i != 0) $c .= " OR";
					$c .= " ProtectURL LIKE '%".db_escape_characters($keywords[$i])."%'";
				}
				$c .= " )";
			}
		}else{
			$query['keywords'] = "";
		}
		if(isset($query['protectType'])){
			if($query['protectType'] == 'F' || $query['protectType'] == 'U'){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " ProtectType = '".$query['protectType']."'";
			}
		}else{
			$query['protectType'] = "-";
		}
	
		// count result
		$sql = "SELECT COUNT(*) ";
		$sql .= "FROM ".$cfg['database']['prefix']."protect";
		$sql .= $c;

		$result = mysql_query($sql);
		$fields = mysql_fetch_array($result, MYSQL_NUM);
		mysql_free_result($result);
		$return->total = $fields[0];
		if($query['pageSize'] == 0) $query['pageSize'] = $return->total; // if no page size assigned, try to return all
		if($query['pageSize'] == 0) $query['pageSize'] = 10; // if no record and no page size assigned, set page size to 10, it does not matter anyway
		
		// calucalate pages
		if(isset($query['page'])) $return->page = $query['page'];
		if(isset($query['pageSize'])) $return->pageSize = $query['pageSize'];
		$return->totalPages = intval($return->total / $return->pageSize);
		if($return->total % $return->pageSize) $return->totalPages++;
		if($return->page > $return->totalPages) $return->page = $return->totalPages;
		if($return->page < 1) $return->page = 1;

		// search for result
		$sql = "SELECT ProtectID, ProtectType, ProtectURL, RedirURL, Memo ";
		$sql .= "FROM ".$cfg['database']['prefix']."protect";
		$sql .= $c;
		$return->orderBy = "ProtectURL";
		if(isset($query['orderBy'])){
			if(strlen($query['orderBy']) > 0) $return->orderBy = $query['orderBy'];
		}
		$offset = $return->pageSize * ($return->page - 1);
		$sql .= " ORDER BY ".$return->orderBy." LIMIT ".$offset.", ".$return->pageSize;
		
		// execute sql and parse result
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$protect = new umProtect();
			$protect->protectID = $fields[0];
			$protect->protectType = $fields[1];
			$protect->protectURL = $fields[2];
			$protect->redirURL = $fields[3];
			$protect->memo = $fields[4];
			$return->list[] = $protect;
		}
		mysql_free_result($result);
		
		$return->query = $query;
		return $return;
	}
	
	function delete_protections($protectIDs){
		global $cfg;
		

		$protections = array();
		$rebuild = false;

		for($i = 0; $i < count($protectIDs); $i++){
			if(!is_numeric($protectIDs[$i])) $protectIDs[$i] = 0;
		}

		/* load selected protection records */
		$sql = "SELECT ProtectID, ProtectType, ProtectURL, RedirURL, Memo ";
		$sql .= "FROM ".$cfg['database']['prefix']."protect ";
		$sql .= "WHERE ProtectID IN (";
		for($i = 0; $i < count($protectIDs); $i++){
			if($i != 0) $sql .= ", ";
			$sql .= $protectIDs[$i];
		}
		$sql .= ")";
		$result = mysql_query($sql);
		$i = 0;
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$record = array();
			$record['obj'] = new umProtect();
			$record['obj']->protectID = $fields[0];
			$record['obj']->protectType = $fields[1];
			if($fields[1] == 'U') $rebuild = true;
			$record['obj']->protectURL = $fields[2];
			$record['obj']->redirURL = $fields[3];
			$record['obj']->memo = $fields[4];
			$record['result'] = false;
			$protections[] = $record;
		}
		mysql_free_result($result);

		if(count($protectIDs) == count($protections)){
			/* rebuild root htaccess */
			if($rebuild){

				// load all groups
				$groups = new umResult();
				$tempGroup = new umGroup();
				$groups = $tempGroup->search_groups(NULL);
		
				// load all protected url
				$protectItems = array();
				$sql = "SELECT p.ProtectID, p.ProtectType, p.ProtectURL, p.RedirURL, p.Memo, m.GroupID ";
				$sql .= "FROM ".$cfg['database']['prefix']."protect p, ".$cfg['database']['prefix']."protect_group_mapping m ";
				$sql .= "WHERE p.ProtectID=m.ProtectID AND p.ProtectType='U' AND p.ProtectID NOT IN (";
				for($i = 0; $i < count($protections); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= $protections[$i]['obj']->protectID;
				}
				$sql .= ") ";
				$sql .= "ORDER BY p.ProtectID, m.GroupID";
				$result = mysql_query($sql);
				$currentProtectID = 0;
				while($fields = mysql_fetch_array($result, MYSQL_NUM)){
					if($currentProtectID != $fields[0]){
						$protect = new umProtect();
						$protect->protectID = $fields[0];
						$protect->protectType = $fields[1];
						$protect->protectURL = $fields[2];
						$protect->redirURL = $fields[3];
						$protect->memo = $fields[4];
						$group = new umGroup();
						$group->groupID = $fields[5];
						$protect->allowGroups[] = $group;
						$protectItems[] = $protect;
						$currentProtectID = $protect->protectID;
					}else{
						$lastIndex = count($protectItems) - 1;
						$group = new umGroup();
						$group->groupID = $fields[5];
						$protectItems[$lastIndex]->allowGroups[] = $group;
					}
				}
				mysql_free_result($result);
		
				$htaccess = "";
				$htaccess .= "RewriteEngine On\n";
				$htaccess .= "RewriteBase /\n\n";
		
				for($p = 0; $p < count($protectItems); $p++){
					for($i = 0; $i < count($groups->list); $i++){
						$selected = false;
						for($j = 0; $j < count($protectItems[$p]->allowGroups); $j++){
							if($protectItems[$p]->allowGroups[$j]->groupID == $groups->list[$i]->groupID) $selected = true;
						}
						//if($selected) $htaccess .= "RewriteCond %{HTTP_COOKIE} !^.*".$cfg['site']['cookiePrefix']."auth_groups=.*\\.".$groups->list[$i]->groupID."-".$groups->list[$i]->token."\\..*\n";
					}
					$htaccess .= "RewriteRule ".$protectItems[$p]->protectURL." ".$protectItems[$p]->redirURL." [R,L]\n\n";
				}
				$htaccess = "\n###Protection Begins###\n".$htaccess."\n###Protection Ends###\n";
			
				$htaccessFile = $cfg['site']['root']."/.htaccess";
				if(file_exists($htaccessFile)){
					$originalFile = file_get_contents($htaccessFile);
					$fileContents = array();
					$fileContents = explode("\n###Protection Begins###\n", $originalFile);
					$htaccess = $fileContents[0].$htaccess;
					if(count($fileContents) > 1){
						$originalFile = $fileContents[1];
						$fileContents = array();
						$fileContents = explode("\n###Protection Ends###\n", $originalFile);
						if(count($fileContents) > 1) $htaccess .= $fileContents[1];
					}
					if($fh = fopen($htaccessFile, 'w')){
						fwrite($fh, $htaccess);
						fclose($fh);
						for($i = 0; $i < count($protections); $i++){
							if($protections[$i]['obj']->protectType == 'U') $protections[$i]['result'] = true;
						}
					}
				}
		
				
			}

			/* delete protect folders */
			for($i = 0; $i < count($protections); $i++){
				if($protections[$i]['obj']->protectType == 'F'){
					$htaccess = "\n###Protection Folder Begins###\n\n\n###Protection Folder Ends###\n";
					$htaccessFile = $protections[$i]['obj']->protectURL."/.htaccess";
					if(file_exists($htaccessFile)){
						$originalFile = file_get_contents($htaccessFile);
						$fileContents = array();
						$fileContents = explode("\n###Protection Folder Begins###\n", $originalFile);
						$htaccess = $fileContents[0].$htaccess;
						if(count($fileContents) > 1){
							$originalFile = $fileContents[1];
							$fileContents = array();
							$fileContents = explode("\n###Protection Folder Ends###\n", $originalFile);
							if(count($fileContents) > 1) $htaccess .= $fileContents[1];
						}
						if($fh = fopen($htaccessFile, 'w')){
							fwrite($fh, $htaccess);
							fclose($fh);
							$protections[$i]['result'] = true;
						}
					}
					
				}
			}
								
			/* remove records in database */
			$sql = "DELETE FROM ".$cfg['database']['prefix']."protect_group_mapping WHERE ProtectID IN (0";
			for($i = 0; $i < count($protections); $i++){
//				if($protections[$i]['result']) $sql .= ", ".$protections[$i]['obj']->protectID;
				$sql .= ", ".$protections[$i]['obj']->protectID;
			}
			$sql .= ")";
			
			if(mysql_query($sql)){
				$sql = "DELETE FROM ".$cfg['database']['prefix']."protect WHERE ProtectID IN (0";
				for($i = 0; $i < count($protections); $i++){
//					if($protections[$i]['result']) $sql .= ", ".$protections[$i]['obj']->protectID;
					$sql .= ", ".$protections[$i]['obj']->protectID;
				}
				$sql .= ")";
				mysql_query($sql);
			}
		}
		
		return $protections;
	}
	
	function _create_root_htaccess($newItem = NULL){
		global $cfg;
		$return = false;
		
		// load all groups
		$groups = new umResult();
		$tempGroup = new umGroup();
		$groups = $tempGroup->search_groups(NULL);
		
		// load all protected url
		$protectItems = array();
		$sql = "SELECT p.ProtectID, p.ProtectType, p.ProtectURL, p.RedirURL, p.Memo, m.GroupID ";
		$sql .= "FROM ".$cfg['database']['prefix']."protect p, ".$cfg['database']['prefix']."protect_group_mapping m ";
		$sql .= "WHERE p.ProtectID=m.ProtectID AND p.ProtectType='U' ";
		if($newItem != NULL) $sql .= "AND p.ProtectID<>".$newItem->protectID." ";
		$sql .= "ORDER BY p.ProtectID, m.GroupID";
		$result = mysql_query($sql);
		$currentProtectID = 0;
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			if($currentProtectID != $fields[0]){
				$protect = new umProtect();
				$protect->protectID = $fields[0];
				$protect->protectType = $fields[1];
				$protect->protectURL = $fields[2];
				$protect->redirURL = $fields[3];
				$protect->memo = $fields[4];
				$group = new umGroup();
				$group->groupID = $fields[5];
				$protect->allowGroups[] = $group;
				$protectItems[] = $protect;
				$currentProtectID = $protect->protectID;
			}else{
				$lastIndex = count($protectItems) - 1;
				$group = new umGroup();
				$group->groupID = $fields[5];
				$protectItems[$lastIndex]->allowGroups[] = $group;
			}
		}
		mysql_free_result($result);
		
		if($newItem != NULL){
			$protectItems[] = $newItem;
		}
		
		$htaccess = "";
		$htaccess .= "RewriteEngine On\n";
		$htaccess .= "RewriteBase /\n\n";
		
		for($p = 0; $p < count($protectItems); $p++){
			for($i = 0; $i < count($groups->list); $i++){
				$selected = false;
				for($j = 0; $j < count($protectItems[$p]->allowGroups); $j++){
					if($protectItems[$p]->allowGroups[$j]->groupID == $groups->list[$i]->groupID) $selected = true;
				}
				//if($selected) $htaccess .= "RewriteCond %{HTTP_COOKIE} !^.*".$cfg['site']['cookiePrefix']."auth_groups=.*\\.".$groups->list[$i]->groupID."-".$groups->list[$i]->token."\\..*\n";
			}
			$htaccess .= "RewriteRule ".$protectItems[$p]->protectURL." ".$protectItems[$p]->redirURL." [R,L]\n\n";
		}
		$htaccess = "\n###Protection Begins###\n".$htaccess."\n###Protection Ends###\n";
			
		$htaccessFile = $cfg['site']['root']."/.htaccess";
		if(file_exists($htaccessFile)){
			$originalFile = file_get_contents($htaccessFile);
			$fileContents = array();
			$fileContents = explode("\n###Protection Begins###\n", $originalFile);
			$htaccess = $fileContents[0].$htaccess;
			if(count($fileContents) > 1){
				$originalFile = $fileContents[1];
				$fileContents = array();
				$fileContents = explode("\n###Protection Ends###\n", $originalFile);
				if(count($fileContents) > 1) $htaccess .= $fileContents[1];
			}
			
			
		}
		if($fh = fopen($htaccessFile, 'w')){
			fwrite($fh, $htaccess);
			fclose($fh);
			$return = true;
		}
		
		
		return $return;
	}
}

?>