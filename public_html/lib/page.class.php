<?php
/*
 * Page Object (2007-10-31)
 * this object is for combining template and html blocks to
 * genarate a completed html page and output to browser
 */

class umPage{
	var $html = ""; // whole page html
	var $template = ""; // template file
	var $blocks = array(); // html blocks
	
	function construct_page(){
		$html = "";
		if(file_exists($this->template)) $html = file_get_contents($this->template);
		foreach($this->blocks as $tag => $code){
			$html = str_replace("<!--tag:".$tag."-->", $code, $html);
		}
		$this->html = $html;
		return $html;
	}
	
	function output_page(){
		print($this->html);
	}
	
	function get_language(){
		global $cfg;
		if(isset($_COOKIE[$cfg['site']['cookiePrefix']."language"])){
			$cfg['language'] = $_COOKIE[$cfg['site']['cookiePrefix']."language"];
		}
	}
	
	function set_language($langID){
		global $cfg;
		setcookie($cfg['site']['cookiePrefix']."language", $langID, time()+(60*60*24*365), '/', $cfg['site']['cookieDomain']);
		$cfg['language'] = $langID;
	}
	
	function customized_time($timeStr, $sourceTZ, $descTZ, $outputFormat = "Y-m-d H:i:s"){
		if($timeStr != NULL){
			$sourceTimeZone = new DateTimeZone($sourceTZ);
			$descTimeZone = new DateTimeZone($descTZ);
			$outputDateTime = new DateTime($timeStr, $sourceTimeZone);
			$outputDateTime->setTimezone($descTimeZone);
			return $outputDateTime->format($outputFormat);
		}else{
			return NULL;
		}
	}
	
	function build_language_form(){
		global $cfg;
		$html = "";
		$html .= "<form action=\"".$cfg['site']['folder']."set_language.php\" name=\"userSelectLanageForm\">";
		$html .= "<select name=\"langID\" onChange=\"document.userSelectLanageForm.submit();\">";
		for($i = 0; $i < count($cfg['languages']); $i++){
			if($cfg['language'] == $cfg['languages'][$i]['id']){
				$html .= "<option value=\"".$cfg['languages'][$i]['id']."\" selected>".$cfg['languages'][$i]['display']."</option>";
			}else{
				$html .= "<option value=\"".$cfg['languages'][$i]['id']."\">".$cfg['languages'][$i]['display']."</option>";
			}
		}
		$html .= "</select>";
		$html .= "</form>";
		return $html;
	}
}
?>