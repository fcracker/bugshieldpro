<?php



class umHTMLPage{

	var $pageID = 0;

	var $pageName = "";

	var $pageTitle = "";

	var $pageContent = "";
	
	var $isMenu_Show = "";

	

	function createPages(){

		global $cfg;

		$return = false;

		$sql = "INSERT INTO ".$cfg['database']['prefix']."pages ";

		$sql .= "(PageName, PageTitle, PageContent) VALUES ";

		$sql .= "('".$this->pageName."','".$this->pageTitle."','".$this->pageContent."')";

		if(mysql_query($sql)) $return = true;

		return $return;

	}

	

	function getPages(){

		global $cfg;

		$sql = "SELECT PageName, PageTitle, PageContent, is_menu_show FROM ";

		$sql .= $cfg['database']['prefix']."pages WHERE ";

		$sql .= "PageID='".$this->pageID."'";

		

		$rst = mysql_query($sql);

		if($row=mysql_fetch_array($rst, MYSQL_NUM)){

			$this->pageName = $row[0];

			$this->pageTitle = $row[1];

			$this->pageContent = $row[2];
			
			$this->isMenu_Show = $row[3];

		}

	}

}

?>