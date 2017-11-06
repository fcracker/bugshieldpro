<?php
class TemplateManager {
	const ERR_DUPLICATE_TEMPLATE 	= "Template already exists. Please install another template or rename it.";
	const ERR_PERMISSION			= "Permission Eror! Please try again."; 
	const ERR_INSTALL_FAILED		= "Installation failed.";
	const ERR_MANUALLY_DELETED		= "The template was deleted by an external reason. Please select another.";
	const ERR_DELETION_FAILED		= "Cannot delete the template. please retry later.";
	const ERR_ACTIVATION_FAILED		= "Cannot activate the template, please retry later.";
	const ERR_UNINSTALL_FAILED		= "Template you are about to uninstall is activated now, please deactivate to uninstall it.";
	
	private $resourceRoot;
	private $template;
	private $error;
	private $db;
	private $table;
	private $result;
	
	public $tempRoot;

	public function __construct($root = "") {
		global $cfg;
		
		$this->db = connect_database();
		$this->resourceRoot = $root != "" ? $root : $cfg['site']['trainingAreaTemplateFolder'];
		$this->tempRoot = dirname( $this->resourceRoot ) . DIRECTORY_SEPARATOR . "_temp" . DIRECTORY_SEPARATOR;
		$this->table = "`" . $cfg['database']['prefix'] . "training_area_templates" . "`";
	}
	
	public function __destruct() {
		mysql_close($this->db);
	}
	
	public function setTemplate($tmplName) {
		$this->template = $tmplName;
	}
	
	public function getActivatedTemplate() {
		$query = "SELECT * FROM " . $this->table . " WHERE activated='y' LIMIT 1";
		$this->_query($query, $this->db);
		
		return mysql_num_rows($this->result) ? mysql_fetch_assoc($this->result) : FALSE;
	}
	
	public function getTemplateList() {
		$query = "SELECT * FROM " . $this->table;
		$this->_query($query, $this->db);
		
		return mysql_num_rows($this->result) ? $this->result : FALSE;
	}
	
	public function add($tmplPkg) {
		$unzipped = FileSystem::unzip($tmplPkg, $this->tempRoot);
		
		if($unzipped) {	//after successful unzip
			FileSystem::deleteFile($tmplPkg);
			$tdata = $this->_readStructure();
			if ($this->_templateExists($tdata["name"])) {
				$this->setError(self::ERR_DUPLICATE_TEMPLATE);
				return FALSE;
			}
			@FileSystem::copyDirectory($this->tempRoot, $this->resourceRoot . $tdata["name"]);
			$this->_addToDB($tdata["name"], $tdata["thumbnail"]);
			@FileSystem::removeDirectory($this->tempRoot, TRUE);
			return TRUE;
		} else {
			$this->setError(self::ERR_INSTALL_FAILED);
			return FALSE;
		}
	}
	
	public function uninstall($tmplName = "") {
		if($tmplName == "") $tmplName = $this->template;
		if($this->_isActivatedTemplate($tmplName)) {
			$this->setError(self::ERR_UNINSTALL_FAILED);
			return FALSE;
		} else {
			if(!$this->_deleteFromSource($tmplName)) {
				$this->setError(self::ERR_DELETION_FAILED);
				return FALSE;
			} else {
				$this->_deleteFromDB($tmplName);
				return TRUE;
			}
		}
	}
	
	public function activate($tmplName = "") {
		if($tmplName == "") $tmplName = $this->template;
		
		$a = $this->getActivatedTemplate();
		if($a["name"] == $tmplName) return TRUE;
		$query = "UPDATE " . $this->table . " SET activated='y' WHERE name=\"" . $tmplName . "\"";
		$this->_query($query);
		
		if ($this->result) {
			$this->_deactivate($a["name"]);
			return TRUE;
		} else {
			$this->setError(self::ERR_ACTIVATION_FAILED);
			return FALSE;
		}
	}
	
	public function getError() {
		$error = $this->error;
		$this->setError("");
		return $error;
	}
	
	private function _isActivatedTemplate($tmplName) {
		$activated = $this->getActivatedTemplate();
		
		return $tmplName == $activated["name"];
	}
	
	private function _readStructure() {
		$str = file_get_contents($this->tempRoot . "template.xml");
		
		$s = xml2array($str);
		return $s["template"];
	}
	
	
	private function _templateExists($tmplName) {
		$query = "SELECT * FROM " . $this->table . " WHERE name=\"" . $tmplName . "\" LIMIT 1";
		$this->_query($query);

		if($this->result && mysql_num_rows($this->result)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	private function _deactivate($tmplName) {
		$query = "UPDATE " . $this->table . " SET activated='n' WHERE name=\"" . $tmplName . "\"";
		$this->_query($query);
	}
	
	private function setError($error = "") {
		$this->error = $error;
	}
	
	private function _addToDB($name, $thumbnail) {
		if ($this->_templateExists($name)) {
			$this->setError(self::ERR_DUPLICATE_TEMPLATE);
			return FALSE;
		} else {
			$query = "INSERT INTO " . $this->table . " VALUES(\"" . mysql_escape_string($name) . "\", \"" . mysql_escape_string($thumbnail) . "\", 'n')";
			mysql_query($query, $this->db);
			return TRUE;
		}
		
	}
	
	private function _deleteFromSource($tmplName) {
		return FileSystem::removeDirectory($this->resourceRoot . $tmplName);
	}

	private function _deleteFromDB($tmplName) {
		$query = "DELETE FROM " . $this->table . " WHERE name=\"" . $tmplName . "\"";
		$this->_query($query);
		
		return $this->result ? TRUE : FALSE;
	}
		
	private function _query($query) {
		//echo "<p>" . $query . "</p>";
		if($this->result) @mysql_free_result($this->result);
		$this->result = mysql_query($query, $this->db);
		return $this->result;
	}
}
?>