<?php
define("FORM_TITLE_LENGTH_MIN", 1);
define("FORM_TITLE_LENGTH_MAX", 250);
define("FIELD_NAME_LENGTH_MIN", 1);
define("FIELD_NAME_LENGTH_MAX", 250);
define("INPUT_FORMAT_LENGTH_MAX", 250);
define("OPTION_CAPTION_LENGTH_MIN", 1);
define("OPTION_CAPTION_LENGTH_MAX", 250);
define("FORM_SEPCIAL_LENGTH_MAX", 250);
define("FORM_REDIRECT_LENGTH_MIN", 1);
define("FORM_REDIRECT_LENGTH_MAX", 250);

class umForm{
	var $formID = 0;
	var $defaultFormTitle = "";
	var $formTitle = "";
	var $formTitles = array();
	var $formType = -1; // 0 = data form; 1 = special form
	var $formSpecial = "";
	var $accessGroups = array();
	var $assignToGroups = array();
	var $removeFromGroups = array();
	var $redirTo = "";
	var $fields = array();
	
	function get_form(){
		global $cfg;
		$return = false;
		
		if(!is_numeric($this->formID)) $this->formID = 0;
		
		$sql = "SELECT FormID, FormTitle, FormType, Script, RedirURL ";
		$sql .= "FROM ".$cfg['database']['prefix']."form WHERE FormID=".$this->formID;
		$result = mysql_query($sql);
		if($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$this->formID = $fields[0];
			$this->defaultFormTitle = $fields[1];
			$this->formType = $fields[2];
			$this->formSpecial = $fields[3];
			$this->redirTo = $fields[4];
		}else{
			$this->formID = 0;
		}
		mysql_free_result($result);
		
		if($this->formID > 0){
			$sql = "SELECT LangID, FormTitle FROM ".$cfg['database']['prefix']."form_title WHERE FormID=".$this->formID;
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$formTitle['langID'] = $fields[0];
				$formTitle['caption'] = $fields[1];
				$this->formTitles[] = $formTitle;
			}
			mysql_free_result($result);
			$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."form_access_groups WHERE FormID=".$this->formID." ORDER BY GroupID";
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$group = new umGroup();
				$group->groupID = $fields[0];
				$this->accessGroups[] = $group;
			}
			mysql_free_result($result);
			$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."form_assign_groups WHERE FormID=".$this->formID." ORDER BY GroupID";
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$group = new umGroup();
				$group->groupID = $fields[0];
				$this->assignToGroups[] = $group;
			}
			mysql_free_result($result);
			$sql = "SELECT GroupID FROM ".$cfg['database']['prefix']."form_remove_groups WHERE FormID=".$this->formID." ORDER BY GroupID";
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$group = new umGroup();
				$group->groupID = $fields[0];
				$this->removeFromGroups[] = $group;
			}
			mysql_free_result($result);
			$return = true;
		}
		
		return $return;
	}
	
	function create_form(){
		global $cfg;
		$return = false;
		
		$sql = "INSERT INTO ".$cfg['database']['prefix']."form ";
		$sql .= "(FormTitle, FormType, Script, RedirURL) VALUES (";
		$sql .= "'".db_escape_characters($this->defaultFormTitle)."', ";
		$sql .= $this->formType.", ";
		$sql .= "'".db_escape_characters($this->formSpecial)."', ";
		$sql .= "'".db_escape_characters($this->redirTo)."')";
		
		if(mysql_query($sql)){
			$this->formID = mysql_insert_id();
			if($this->formID > 0){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."form_title ";
				$sql .= "(FormID, LangID, FormTitle) VALUES ";
				for($i = 0; $i < count($this->formTitles); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->formID.", '".$this->formTitles[$i]['langID']."', '".db_escape_characters($this->formTitles[$i]['caption'])."') ";
				}
				if(mysql_query($sql)){
					$return = true;
					if(count($this->accessGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_access_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->accessGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->accessGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
					if(count($this->assignToGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_assign_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->assignToGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->assignToGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
					if(count($this->removeFromGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_remove_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->removeFromGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->removeFromGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
				}
			}
		}
		
		return $return;
		
	}

	function update_form(){
		global $cfg;
		$return = false;

		if(!is_numeric($this->formID)) $this->formID = 0;

		$sql = "UPDATE ".$cfg['database']['prefix']."form SET ";
		$sql .= "FormTitle='".db_escape_characters($this->defaultFormTitle)."', ";
		$sql .= "FormType=".$this->formType.", ";
		$sql .= "Script='".db_escape_characters($this->formSpecial)."', ";
		$sql .= "RedirURL='".db_escape_characters($this->redirTo)."' ";
		$sql .= "WHERE FormID=".$this->formID;
		if(mysql_query($sql)){
			$sql = "DELETE FROM ".$cfg['database']['prefix']."form_title WHERE FormID=".$this->formID;
			if(mysql_query($sql)){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."form_title ";
				$sql .= "(FormID, LangID, FormTitle) VALUES ";
				for($i = 0; $i < count($this->formTitles); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->formID.", '".$this->formTitles[$i]['langID']."', '".db_escape_characters($this->formTitles[$i]['caption'])."') ";
				}
				if(mysql_query($sql)){
					$return = true;
					$sql = "DELETE FROM ".$cfg['database']['prefix']."form_access_groups WHERE FormID=".$this->formID;
					mysql_query($sql);
					if(count($this->accessGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_access_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->accessGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->accessGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
					$sql = "DELETE FROM ".$cfg['database']['prefix']."form_assign_groups WHERE FormID=".$this->formID;
					mysql_query($sql);
					if(count($this->assignToGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_assign_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->assignToGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->assignToGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
					
					$sql = "DELETE FROM ".$cfg['database']['prefix']."form_remove_groups WHERE FormID=".$this->formID;
					mysql_query($sql);
					if(count($this->removeFromGroups) > 0){
						$sql = "INSERT INTO ".$cfg['database']['prefix']."form_remove_groups (FormID, GroupID) VALUES ";
						for($i = 0; $i < count($this->removeFromGroups); $i++){
							if($i != 0) $sql .= ", ";
							$sql .= "(".$this->formID.", ".$this->removeFromGroups[$i]->groupID.")";
						}
						if(!mysql_query($sql)) $return = false;
					}
				}
			}
		}
		return $return;
	}

	function search_forms($query){
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
		if(isset($query['fromID'])){
			if(is_numeric($query['fromID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FormID >= ".$query['fromID'];
			}else{
				$query['fromID'] = "";
			}
		}else{
			$query['fromID'] = "";
		}
		if(isset($query['toID'])){
			if(is_numeric($query['toID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FormID <= ".$query['toID'];
			}else{
				$query['toID'] = "";
			}
		}else{
			$query['toID'] = "";
		}
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
					$c .= " f.FormTitle LIKE '%".db_escape_characters($keywords[$i])."%'";
				}
				$c .= " )";
			}
		}else{
			$query['keywords'] = "";
		}
		if(isset($query['formType']) && $query['formType'] >= 0){
			if(is_numeric($query['formType'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FormType = ".$query['formType'];
			}
		}else{
			$query['formType'] = -1;
		}
		if(isset($query['groupID'])){
			if(is_numeric($query['groupID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " a.GroupID = ".$query['groupID']." AND a.FormID=f.FormID";
			}
		}else{
			$query['groupID'] = "-";
		}
	
		// count result
		$sql = "SELECT COUNT(*) ";
		$sql .= "FROM ".$cfg['database']['prefix']."form f";
		if(isset($query['groupID'])){
			if(is_numeric($query['groupID'])){
				$sql .= ", ".$cfg['database']['prefix']."form_access_groups a";
			}
		}
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
		$sql = "SELECT f.FormID, f.FormTitle, f.FormType, f.Script, f.RedirURL ";
		$sql .= "FROM ".$cfg['database']['prefix']."form f";
		if(isset($query['groupID'])){
			if(is_numeric($query['groupID'])){
				$sql .= ", ".$cfg['database']['prefix']."form_access_groups a";
			}
		}
		$sql .= $c;
		$return->orderBy = "FormID";
		if(isset($query['orderBy'])){
			if(strlen($query['orderBy']) > 0) $return->orderBy = $query['orderBy'];
		}
		$offset = $return->pageSize * ($return->page - 1);
		$sql .= " ORDER BY ".$return->orderBy." LIMIT ".$offset.", ".$return->pageSize;
		
		// execute sql and parse result
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$form = new umForm();
			$form->formID = $fields[0];
			$form->defaultFormTitle = $fields[1];
			$form->formType = $fields[2];
			$form->formSpecial = $fields[3];
			$form->redirTo = $fields[4];
			$return->list[] = $form;
		}
		mysql_free_result($result);
		
		$return->query = $query;
		return $return;
	}

	function attach_field($field){
		global $cfg;
		$return = false;
		
		$sql = "DELETE FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID." AND FieldID=".$field->fieldID;
		if(mysql_query($sql)){
			$sql = "INSERT INTO ".$cfg['database']['prefix']."form_field_mapping (FormID, FieldID, Sort) VALUES (";
			$sql .= $this->formID.", ";
			$sql .= $field->fieldID.", ";
			$sql .= $field->sort.")";
			if(mysql_query($sql)) $return = true;
		}
		
		return $return;
	}
	
	function get_field($field){
		global $cfg;
		$returnField = $field;
		
		if(!is_numeric($this->formID)) $this->formID = 0;
		if(!is_numeric($returnField->fieldID)) $returnField->fieldID = 0;
		
		$sql = "SELECT FieldID, Sort FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID." AND FieldID=".$returnField->fieldID;
		$result = mysql_query($sql);
		if($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$returnField->fieldID = $fields[0];
			$returnField->get_field();
			$returnField->sort = $fields[1];
		}else{
			$returnField->fieldID = 0;
		}
		mysql_free_result($result);
		
		return $returnField;
	}

	function remove_fields($fieldIDs){
		global $cfg;
		$return = false;
		
		if(count($fieldIDs) > 0){
			$sql = "DELETE FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID." AND FieldID IN (";
			for($i = 0; $i < count($fieldIDs); $i++){
				if($i != 0) $sql .= ", ";
				$sql .= $fieldIDs[$i];
			}
			$sql .= ")";
			if(mysql_query($sql)) $return = true;
		}
		
		return $return;
		
	}
	
	function get_forms_by_user($userID){
		global $cfg;
		$forms = array();
		
		if(!is_numeric($userID)) $userID = 0;

		$sql = "SELECT DISTINCT f.FormID, f.FormTitle, f.FormType, f.Script, f.RedirURL, ft.FormTitle ";
		$sql .= "FROM ".$cfg['database']['prefix']."form f, ".$cfg['database']['prefix']."form_title ft, ";
		$sql .= $cfg['database']['prefix']."form_access_groups fg, ".$cfg['database']['prefix']."user_group_mapping m ";
		$sql .= "WHERE m.UserID=".$userID." AND m.GroupID=fg.GroupID AND fg.FormID=f.FormID AND f.FormID=ft.FormID AND ft.LangID='".$cfg['language']."' ";
		$sql .= "ORDER BY ft.FormTitle";
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$form = new umForm();
			$form->formID = $fields[0];
			$form->defaultFormTitle = $fields[1];
			$form->formType = $fields[2];
			$form->formSpecial = $fields[3];
			$form->redirTo = $fields[4];
			$form->formTitle = $fields[5];
			$forms[] = $form;
		}
		mysql_free_result($result);
		
		return $forms;
	}
	
	function load_form($userID){
		global $cfg;
		$return = false;
		
		if(!is_numeric($userID)) $userID = 0;
		if(!is_numeric($this->formID)) $this->formID = 0;
		
		if($this->get_form()){
			// get form title in user language
			for($i = 0; $i < count($this->formTitles); $i++){
				if($this->formTitles[$i]['langID'] == $cfg['language']) $this->formTitle = $this->formTitles[$i]['caption'];
			}
			if($this->formType == 0){
				// get fields and values
				$sql = "SELECT f.FieldID, f.FieldName, f.FieldType, f.IsRequired, f.Format, f.MinLength, f.MaxLength, fn.FieldName, ";
				$sql .= "vn.Value, vs.Value, vo.Value, vd.Value, vt.Value ";
				$sql .= "FROM (".$cfg['database']['prefix']."form_field_mapping m, ".$cfg['database']['prefix']."field f, ";
				$sql .= $cfg['database']['prefix']."field_name fn) ";
				$sql .= "LEFT JOIN ".$cfg['database']['prefix']."field_value_number vn ON (vn.FieldID=f.FieldID AND vn.UserID=".$userID.") ";
				$sql .= "LEFT JOIN ".$cfg['database']['prefix']."field_value_string vs ON (vs.FieldID=f.FieldID AND vs.UserID=".$userID.") ";
				$sql .= "LEFT JOIN ".$cfg['database']['prefix']."field_value_option vo ON (vo.FieldID=f.FieldID AND vo.UserID=".$userID.") ";
				$sql .= "LEFT JOIN ".$cfg['database']['prefix']."field_value_datetime vd ON (vd.FieldID=f.FieldID AND vd.UserID=".$userID.") ";
				$sql .= "LEFT JOIN ".$cfg['database']['prefix']."field_value_text vt ON (vt.FieldID=f.FieldID AND vt.UserID=".$userID.") ";
				$sql .= "WHERE m.FormID=".$this->formID." AND f.FieldID=m.FieldID AND fn.FieldID=f.FieldID AND fn.LangID='".$cfg['language']."' ";
				$sql .= "ORDER BY m.Sort";
				$result = mysql_query($sql);
				$lastFieldID = 0;
				while($fields = mysql_fetch_array($result, MYSQL_NUM)){
					if($lastFieldID !=  $fields[0]){
						$field = new umField();
						$field->fieldID = $fields[0];
						$field->defaultFieldName = $fields[1];
						$field->fieldType = $fields[2];
						$field->isRequired = $fields[3];
						$field->format = $fields[4];
						$field->minLength = $fields[5];
						$field->maxLength = $fields[6];
						$field->fieldName = $fields[7];
						if($field->fieldType == 0) $field->value = $fields[8];
						if($field->fieldType == 1) $field->value = $fields[9];
						if($field->fieldType == 2 || $field->fieldType == 3 || $field->fieldType == 4){
							$field->value = $fields[10];
							// get field options
							$field->get_field_options();
						}
						if($field->fieldType == 5){
							$dateTime = explode(" ", $fields[11]);
							$field->value = $dateTime[0];
						}
						if($field->fieldType == 6) $field->value = $fields[11];
						if($field->fieldType == 7) $field->value = $fields[12];
						$this->fields[] = $field;
					}else{
						$max = count($this->fields) - 1;
						$this->fields[$max]->value .= ",".$fields[10];
					}
					$lastFieldID = $fields[0];
					$return = true;
				}
				mysql_free_result($result);
			}else{
				$return = true;
			}
		}else{
			$this->formID = 0;
		}
		
		return $return;
	}
	
	function submit_form($userID){
		global $cfg;

		if(!is_numeric($userID)) $userID = 0;
		if(!is_numeric($this->formID)) $this->formID = 0;

		// remove existing data
		$sqln = "DELETE FROM ".$cfg['database']['prefix']."field_value_number WHERE UserID=".$userID." AND FieldID IN ";
		$sqln .= "(SELECT FieldID FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID.")";
		mysql_query($sqln);
		$sqls = "DELETE FROM ".$cfg['database']['prefix']."field_value_string WHERE UserID=".$userID." AND FieldID IN ";
		$sqls .= "(SELECT FieldID FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID.")";
		mysql_query($sqls);
		$sqlo = "DELETE FROM ".$cfg['database']['prefix']."field_value_option WHERE UserID=".$userID." AND FieldID IN ";
		$sqlo .= "(SELECT FieldID FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID.")";
		mysql_query($sqlo);
		$sqld = "DELETE FROM ".$cfg['database']['prefix']."field_value_datetime WHERE UserID=".$userID." AND FieldID IN ";
		$sqld .= "(SELECT FieldID FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID.")";
		mysql_query($sqld);
		$sqlt = "DELETE FROM ".$cfg['database']['prefix']."field_value_text WHERE UserID=".$userID." AND FieldID IN ";
		$sqlt .= "(SELECT FieldID FROM ".$cfg['database']['prefix']."form_field_mapping WHERE FormID=".$this->formID.")";
		mysql_query($sqlt);
		// insert submission data
		$sqln = "INSERT INTO ".$cfg['database']['prefix']."field_value_number (FieldID, UserID, Value) VALUES ";
		$runSQLN = false;
		$sqls = "INSERT INTO ".$cfg['database']['prefix']."field_value_string (FieldID, UserID, Value) VALUES ";
		$runSQLS = false;
		$sqlo = "INSERT INTO ".$cfg['database']['prefix']."field_value_option (FieldID, UserID, Value) VALUES ";
		$runSQLO = false;
		$sqld = "INSERT INTO ".$cfg['database']['prefix']."field_value_datetime (FieldID, UserID, Value) VALUES ";
		$runSQLD = false;
		$sqlt = "INSERT INTO ".$cfg['database']['prefix']."field_value_text (FieldID, UserID, Value) VALUES ";
		$runSQLT = false;
		for($i = 0; $i < count($this->fields); $i++){
			$field = $this->fields[$i];
			if($field->fieldType == 0){
				if($runSQLN) $sqln .= ",";
				$runSQLN = true;
				$sqln .= "(".$field->fieldID.", ".$userID.", ".$field->value.")";
			}
			if($field->fieldType == 1){
				if($runSQLS) $sqls .= ",";
				$runSQLS = true;
				$sqls .= "(".$field->fieldID.", ".$userID.", '".db_escape_characters($field->value)."')";
			}
			if($field->fieldType == 2){
				for($j = 0; $j < count($field->value); $j++){
					if($runSQLO) $sqlo .= ",";
					$runSQLO = true;
					$sqlo .= "(".$field->fieldID.", ".$userID.", ".$field->value[$j].")";
				}
			}
			if($field->fieldType == 3 || $field->fieldType == 4){
				if($runSQLO) $sqlo .= ",";
				$runSQLO = true;
				$sqlo .= "(".$field->fieldID.", ".$userID.", ".$field->value.")";
			}
			if($field->fieldType == 5 || $field->fieldType == 6){
				if($runSQLD) $sqld .= ",";
				$runSQLD = true;
				$sqld .= "(".$field->fieldID.", ".$userID.", '".$field->value."')";
			}
			if($field->fieldType == 7){
				if($runSQLT) $sqlt .= ",";
				$runSQLT = true;
				$sqlt .= "(".$field->fieldID.", ".$userID.", '".db_escape_characters($field->value)."')";
			}
		}
		if($runSQLN) mysql_query($sqln);
		if($runSQLS) mysql_query($sqls);
		if($runSQLO) mysql_query($sqlo);
		if($runSQLD) mysql_query($sqld);
		if($runSQLT) mysql_query($sqlt);
	}
}

class umField{
	var $fieldID = 0;
	var $defaultFieldName = "";
	var $fieldName = "";
	var $fieldNames = array();
	var $fieldType = -1;
	var $isRequired = false;
	var $format = "";
	var $minLength = 0;
	var $maxLength = 0;
	var $fieldOptions = array();
	var $value = "";
	var $sort = 0;
	
	function get_field(){
		global $cfg;
		$return = false;
		
		if(!is_numeric($this->fieldID)) $this->fieldID = 0;

		$sql = "SELECT FieldID, FieldName, FieldType, IsRequired, Format, MinLength, MaxLength ";
		$sql .= "FROM ".$cfg['database']['prefix']."field WHERE FieldID=".$this->fieldID;
		$result = mysql_query($sql);
		if($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$this->fieldID = $fields[0];
			$this->defaultFieldName = $fields[1];
			$this->fieldType = $fields[2];
			$this->isRequired = $fields[3];
			$this->format = $fields[4];
			$this->minLength = $fields[5];
			$this->maxLength = $fields[6];
		}else{
			$this->fieldID = 0;
		}
		mysql_free_result($result);
		
		if($this->fieldID > 0){
			$sql = "SELECT LangID, FieldName FROM ".$cfg['database']['prefix']."field_name WHERE FieldID=".$this->fieldID;
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$fieldName['langID'] = $fields[0];
				$fieldName['caption'] = $fields[1];
				$this->fieldNames[] = $fieldName;
			}
			mysql_free_result($result);
			$return = true;
		}
		
		return $return;
	}
	
	function create_field(){
		global $cfg;
		$return = false;

		$sql = "INSERT INTO ".$cfg['database']['prefix']."field ";
		$sql .= "(FieldName, FieldType, IsRequired, Format, MinLength, MaxLength) VALUES (";
		$sql .= "'".db_escape_characters($this->defaultFieldName)."', ";
		$sql .= $this->fieldType.", ";
		$sql .= $this->isRequired.", ";
		$sql .= "'".db_escape_characters($this->format)."', ";
		$sql .= $this->minLength.", ";
		$sql .= $this->maxLength.")";
		
		if(mysql_query($sql)){
			$this->fieldID = mysql_insert_id();
			if($this->fieldID > 0){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."field_name ";
				$sql .= "(FieldID, LangID, FieldName) VALUES ";
				for($i = 0; $i < count($this->fieldNames); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->fieldID.", '".$this->fieldNames[$i]['langID']."', '".db_escape_characters($this->fieldNames[$i]['caption'])."') ";
				}
				if(mysql_query($sql)) $return = true;
			}
		}
		
		return $return;
	}
	
	function update_field(){
		global $cfg;
		$return = false;

		if(!is_numeric($this->fieldID)) $this->fieldID = 0;

		$sql = "UPDATE ".$cfg['database']['prefix']."field SET ";
		$sql .= "FieldName='".db_escape_characters($this->defaultFieldName)."', ";
		$sql .= "FieldType=".$this->fieldType.", ";
		$sql .= "IsRequired=".$this->isRequired.", ";
		$sql .= "Format='".db_escape_characters($this->format)."', ";
		$sql .= "MinLength=".$this->minLength.", ";
		$sql .= "MaxLength=".$this->maxLength." ";
		$sql .= "WHERE fieldID=".$this->fieldID;
		if(mysql_query($sql)){
			$sql = "DELETE FROM ".$cfg['database']['prefix']."field_name WHERE FieldID=".$this->fieldID;
			if(mysql_query($sql)){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."field_name ";
				$sql .= "(FieldID, LangID, FieldName) VALUES ";
				for($i = 0; $i < count($this->fieldNames); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->fieldID.", '".$this->fieldNames[$i]['langID']."', '".db_escape_characters($this->fieldNames[$i]['caption'])."') ";
				}
				if(mysql_query($sql)) $return = true;
			}
		}

		return $return;
	}

	function search_fields($query){
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

		$formID = 0;
		if(isset($query['formID'])){
			if(is_numeric($query['formID']) && $query['formID'] > 0){
				$formID = $query['formID'];
			}
		}
		$query['formID'] = $formID;

		
		// construction conditions
		$c = "";
		if(isset($query['fromID'])){
			if(is_numeric($query['fromID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FieldID >= ".$query['fromID'];
			}else{
				$query['fromID'] = "";
			}
		}else{
			$query['fromID'] = "";
		}
		if(isset($query['toID'])){
			if(is_numeric($query['toID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FieldID <= ".$query['toID'];
			}else{
				$query['toID'] = "";
			}
		}else{
			$query['toID'] = "";
		}
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
					$c .= " f.FieldName LIKE '%".db_escape_characters($keywords[$i])."%'";
				}
				$c .= " )";
			}
		}else{
			$query['keywords'] = "";
		}
		if(isset($query['fieldType'])){
		if(is_numeric($query['fieldType']) && $query['fieldType'] >= 0){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.FieldType = ".$query['fieldType'];
			}
		}else{
			$query['fieldType'] = -1;
		}
		if(isset($query['isRequired'])){
			if(is_numeric($query['isRequired'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " f.IsRequired = ".$query['isRequired'];
			}
		}else{
			$query['isRequired'] = "-";
		}
		if($formID > 0){
			if($c == ""){
				$c .= " WHERE";
			}else{
				$c .= " AND";
			}
			$c .= " m.FormID = ".$query['formID']." AND m.FieldID=f.FieldID";
		}
		
		// count result
		$sql = "SELECT COUNT(*) ";
		$sql .= "FROM ".$cfg['database']['prefix']."field f";
		if($formID > 0){
			$sql .= ", ".$cfg['database']['prefix']."form_field_mapping m";
		}
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
		$sql = "SELECT f.FieldID, f.FieldName, f.FieldType, f.IsRequired, f.Format, f.MinLength, f.MaxLength ";
		if($formID > 0){
			$sql .= ", m.Sort ";
		}
		$sql .= "FROM ".$cfg['database']['prefix']."field f";
		if($formID > 0){
			$sql .= ", ".$cfg['database']['prefix']."form_field_mapping m";
		}
		$sql .= $c;
		$return->orderBy = "f.FieldID DESC";
		if($formID > 0){
			$return->orderBy = "m.Sort";
		}
		if(isset($query['orderBy'])){
			if(strlen($query['orderBy']) > 0) $return->orderBy = $query['orderBy'];
		}
		$offset = $return->pageSize * ($return->page - 1);
		$sql .= " ORDER BY ".$return->orderBy." LIMIT ".$offset.", ".$return->pageSize;
		
		// execute sql and parse result
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$field = new umField();
			$field->fieldID = $fields[0];
			$field->defaultFieldName = $fields[1];
			$field->fieldType = $fields[2];
			$field->isRequired = $fields[3];
			$field->format = $fields[4];
			$field->minLength = $fields[5];
			$field->maxLength = $fields[6];
			if($formID > 0){
				$field->sort = $fields[7];
			}
			$return->list[] = $field;
		}
		mysql_free_result($result);
		
		$return->query = $query;
		return $return;
	}

	function get_field_options(){
		global $cfg;
		$return = false;
		
		if(!is_numeric($this->fieldID)) $this->fieldID = 0;

		$sql = "SELECT o.OptionID, o.FieldID, o.Caption, o.Sort, o.Status, oc.Caption ";
		$sql .= "FROM ".$cfg['database']['prefix']."option o, ".$cfg['database']['prefix']."option_caption oc ";
		$sql .= "WHERE o.OptionID=oc.OptionID AND oc.LangID='".$cfg['language']."' AND o.FieldID=".$this->fieldID." AND o.Status=1 ";
		$sql .= "ORDER BY o.Sort, oc.Caption";
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$option = new umFieldOption();
			$option->optionID = $fields[0];
			$option->fieldID = $fields[1];
			$option->defaultCaption = $fields[2];
			$option->sort = $fields[3];
			$option->status = $fields[4];
			$option->caption = $fields[5];
			$this->fieldOptions[] = $option;
			$return = true;
		}
		mysql_free_result($result);
		
		
		return $return;
	}
}

class umFieldOption{
	var $optionID = 0;
	var $fieldID = 0;
	var $defaultCaption = "";
	var $caption = "";
	var $captions = array();
	var $sort = 0;
	var $status = 0;

	function get_option(){
		global $cfg;
		$return = false;
		
		if(!is_numeric($this->optionID)) $this->optionID = 0;

		$sql = "SELECT OptionID, FieldID, Caption, Sort, Status ";
		$sql .= "FROM ".$cfg['database']['prefix']."option WHERE OptionID=".$this->optionID;
		$result = mysql_query($sql);
		if($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$this->optionID = $fields[0];
			$this->fieldID = $fields[1];
			$this->defaultCaption = $fields[2];
			$this->sort = $fields[3];
			$this->status = $fields[4];
		}else{
			$this->optionID = 0;
		}
		mysql_free_result($result);
		
		if($this->optionID > 0){
			$sql = "SELECT LangID, Caption FROM ".$cfg['database']['prefix']."option_caption WHERE OptionID=".$this->optionID;
			$result = mysql_query($sql);
			while($fields = mysql_fetch_array($result, MYSQL_NUM)){
				$caption['langID'] = $fields[0];
				$caption['caption'] = $fields[1];
				$this->captions[] = $caption;
			}
			mysql_free_result($result);
			$return = true;
		}
		
		return $return;
	}

	function create_option(){
		global $cfg;
		$return = false;
		
		$sql = "INSERT INTO ".$cfg['database']['prefix']."option ";
		$sql .= "(FieldID, Caption, Sort, Status) VALUES (";
		$sql .= $this->fieldID.", ";
		$sql .= "'".db_escape_characters($this->defaultCaption)."', ";
		$sql .= $this->sort.", ";
		$sql .= $this->status.")";
		
		if(mysql_query($sql)){
			$this->optionID = mysql_insert_id();
			if($this->optionID > 0){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."option_caption ";
				$sql .= "(optionID, LangID, Caption) VALUES ";
				for($i = 0; $i < count($this->captions); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->optionID.", '".$this->captions[$i]['langID']."', '".db_escape_characters($this->captions[$i]['caption'])."') ";
				}
				if(mysql_query($sql)) $return = true;
			}
		}
		
		return $return;
	}

	function update_option(){
		global $cfg;
		$return = false;

		if(!is_numeric($this->optionID)) $this->optionID = 0;

		$sql = "UPDATE ".$cfg['database']['prefix']."option SET ";
		$sql .= "Caption='".db_escape_characters($this->defaultCaption)."', ";
		$sql .= "Sort=".$this->sort.", ";
		$sql .= "Status=".$this->status." ";
		$sql .= "WHERE OptionID=".$this->optionID;
		if(mysql_query($sql)){
			$sql = "DELETE FROM ".$cfg['database']['prefix']."option_caption WHERE OptionID=".$this->optionID;
			if(mysql_query($sql)){
				$sql = "INSERT INTO ".$cfg['database']['prefix']."option_caption ";
				$sql .= "(OptionID, LangID, Caption) VALUES ";
				for($i = 0; $i < count($this->captions); $i++){
					if($i != 0) $sql .= ", ";
					$sql .= "(".$this->optionID.", '".$this->captions[$i]['langID']."', '".db_escape_characters($this->captions[$i]['caption'])."') ";
				}
				if(mysql_query($sql)) $return = true;
			}
		}

		return $return;
	}

	function search_options($query){
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
		if(isset($query['fromID'])){
			if(is_numeric($query['fromID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " OptionID >= ".$query['fromID'];
			}else{
				$query['fromID'] = "";
			}
		}else{
			$query['fromID'] = "";
		}
		if(isset($query['toID'])){
			if(is_numeric($query['toID'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " OptionID <= ".$query['toID'];
			}else{
				$query['toID'] = "";
			}
		}else{
			$query['toID'] = "";
		}
		if(isset($query['fieldID'])){
			if(is_numeric($query['fieldID']) && $query['fieldID'] != 0){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " FieldID = ".$query['fieldID'];
			}
		}else{
			$query['fieldID'] = "0";
		}
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
					$c .= " Caption LIKE '%".db_escape_characters($keywords[$i])."%'";
				}
				$c .= " )";
			}
		}else{
			$query['keywords'] = "";
		}
		if(isset($query['status'])){
			if(is_numeric($query['status'])){
				if($c == ""){
					$c .= " WHERE";
				}else{
					$c .= " AND";
				}
				$c .= " Status = ".$query['status'];
			}
		}else{
			$query['status'] = "-";
		}
		
		
		// count result
		$sql = "SELECT COUNT(*) ";
		$sql .= "FROM ".$cfg['database']['prefix']."option".$c;
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
		$sql = "SELECT OptionID, FieldID, Caption, Sort, Status ";
		$sql .= "FROM ".$cfg['database']['prefix']."option".$c;
		$return->orderBy = "Sort";
		if(isset($query['orderBy'])){
			if(strlen($query['orderBy']) > 0) $return->orderBy = $query['orderBy'];
		}
		$offset = $return->pageSize * ($return->page - 1);
		$sql .= " ORDER BY ".$return->orderBy." LIMIT ".$offset.", ".$return->pageSize;
		
		// execute sql and parse result
		$result = mysql_query($sql);
		while($fields = mysql_fetch_array($result, MYSQL_NUM)){
			$option = new umFieldOption();
			$option->optionID = $fields[0];
			$option->fieldID = $fields[1];
			$option->defaultCaption = $fields[2];
			$option->sort = $fields[3];
			$option->status = $fields[4];
			$return->list[] = $option;
		}
		mysql_free_result($result);
		
		$return->query = $query;
		return $return;
	}

	function change_options_status($status, $optionIDs){
		global $cfg;
		$return = false;

		for($i = 0; $i < count($optionIDs); $i++){
			if(!is_numeric($optionIDs[$i])) $optionIDs[$i] = 0;
		}

		$sql = "UPDATE ".$cfg['database']['prefix']."option SET ";
		$sql .= "Status=".$status." ";
		$sql .= "WHERE OptionID IN (";
		for($i = 0; $i < count($optionIDs); $i++){
			if($i != 0) $sql .= ", ";
			$sql .= $optionIDs[$i];
		}
		$sql .= ")";
		if(mysql_query($sql)){
			$return = true;
		}
		return $return;	
	}
	
}

$_fieldType = array(
	0 => 'number',
	1 => 'string',
	2 => 'checkbox',
	3 => 'radio',
	4 => 'select',
	5 => 'date',
	6 => 'dateTime',
	7 => 'textBox'
);

//$_formType = array(
//	0 => 'Data Form',
//	1 => 'Script Form'
//)
$_formType = array(
	0 => 'Data Form'
)

?>