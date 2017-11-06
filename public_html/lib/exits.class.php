<?php

class custom_exit {
	
	public $config,
	$popup_text,
	$unit_price,
	$discount_page_text;
	
	function __construct($config) {
		$this->config = $config;
	}
	
	public function add_exit($data) {
		$sql = "insert into ".$this->config['database']['prefix']."exits SET popup_text='".mysql_real_escape_string($data['popup_text'])."', unit_price='".mysql_real_escape_string($data['unit_price'])."', discount_page_text='".mysql_real_escape_string($data['discount_page_text'])."'";
		
		mysql_query($sql);
	}
	
	public function edit_exit($exit_id,$data) {
	
		$sql = "update ".$this->config['database']['prefix']."exits SET popup_text='".mysql_real_escape_string($data['popup_text'])."', unit_price='".mysql_real_escape_string($data['unit_price'])."', discount_page_text='".mysql_real_escape_string($data['discount_page_text'])."' WHERE exit_id=".intval($exit_id);
		
		mysql_query($sql);
		
	
	}
	
	public function delete_exit($exit_id) {
		$sql = "delete from ".$this->config['database']['prefix']."exits WHERE exit_id=".intval($path_id);
		
		mysql_query($sql);
	}
	
	public function get_exit_by_id($exit_id) {
		$sql = "select * from ".$this->config['database']['prefix']."exits where exit_id=".intval($exit_id);	
		$return = array();
		
		$result = mysql_query($sql);
		
		if($result && mysql_num_rows($result)) {
			$return = mysql_fetch_assoc($result);
		}
		
		return $return;
	}
	
	public function get_exits() {
		$sql = "select * from ".$this->config['database']['prefix']."exits";		
		
		$result = array();
		
		$r = mysql_query($sql);
		
		if($r && mysql_num_rows($r)) {
			while($row=mysql_fetch_assoc($r)) {
				$result[] = $row;
			}
		}
		
		return $result;
	}
	
	
}