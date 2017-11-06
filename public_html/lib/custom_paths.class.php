<?php

class custom_path {
	
	public $config,$name,$folder;
	
	function __construct($config) {
		$this->config = $config;
	}
	
	public function add_path($data) {
		$sql = "insert into ".$this->config['database']['prefix']."custom_paths SET path_name='".mysql_real_escape_string($data['path_name'])."', path_folder='".mysql_real_escape_string($data['path_folder'])."'";
		
		mysql_query($sql);
		
		$this->update_exits_for_path(mysql_insert_id(),$data['path_exits']);
	}
	
	public function edit_path($path_id,$data) {
	
		$sql = "update ".$this->config['database']['prefix']."custom_paths SET path_name='".mysql_real_escape_string($data['path_name'])."', path_folder='".mysql_real_escape_string($data['path_folder'])."' WHERE custom_path_id=".intval($path_id);
		
		mysql_query($sql);
		$this->update_exits_for_path($path_id,$data['path_exits']);
	
	}
	
	function update_exits_for_path($path_id,$exits) {		
		mysql_query("delete from ".$this->config['database']['prefix']."path_exit WHERE custom_path_id=".intval($path_id));
		if(is_array($exits) && count($exits)) {
		$sql = "insert into ".$this->config['database']['prefix']."path_exit(custom_path_id,exit_id) VALUES ";
			foreach($exits as $key=>$exit) {
				if($key>0) {
					$sql.=",";
				}
				$sql.="(".intval($path_id).",".intval($exit).")";
			}
			mysql_query($sql);
		}
	}
	
	public function delete_path($path_id) {
		$sql = "delete from ".$this->config['database']['prefix']."custom_paths WHERE custom_path_id=".intval($path_id);
		
		mysql_query($sql);
	}
	
	public function get_path_by_id($path_id) {
		$sql = "select * from ".$this->config['database']['prefix']."custom_paths where custom_path_id=".intval($path_id);	
		$return = array();
		
		$result = mysql_query($sql);
		
		if($result && mysql_num_rows($result)) {
			$return = mysql_fetch_assoc($result);
		}
		
		return $return;
	}
	
	public function get_path_by_folder($folder) {
	
		$sql = "select * from ".$this->config['database']['prefix']."custom_paths where path_folder=".$folder;	
		$return = array();
		
		$result = mysql_query($sql);
		
		if($result && mysql_num_rows($result)) {
			$return = mysql_fetch_assoc($result);
			
			$return['exits'] = $this->get_exits_for_path($return['custom_path_id']);
			
		}
		
		return $return;
	
	}
	
	public function get_exits_for_path($path_id) {
		$sql = "select e.exit_id from ".$this->config['database']['prefix']."exits e inner join ".$this->config['database']['prefix']."path_exit pe on pe.exit_id = e.exit_id where pe.custom_path_id=".intval($path_id);
		
		$return = array();
		
		$result = mysql_query($sql);
		
		
			while($r = mysql_fetch_assoc($result)) {
				$return[] = $r['exit_id'];
			}
		
		
		return $return;
		
	}
	
	public function get_paths() {
		$sql = "select * from ".$this->config['database']['prefix']."custom_paths";		
		
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