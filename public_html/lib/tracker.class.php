<?php

// keeps persistent user data between pages

class tracker
{
	public $user_identifier;

 // need to make this unique accross the system, and store it in the session so it can be retrieved

	public $data;

 // this is where the serialized data is kept

	public $table = "tracker";

 // where do we keep all this data

	public function __construct($user_identifier = "")
	{
		if (strlen($user_identifier)) {
			$this->user_identifier = $user_identifier;
		}
		else {

			// try and fetch from the session

			$this->get_user_identifier();
		}
	}

	public function get_user_identifier()
	{
		if (isset($_SESSION['user_identifier']) && strlen($_SESSION['user_identifier'])) {
			$this->user_identifier = (string)$_SESSION['user_identifier'];
		}
		else {

			// generate a brand new one ;)

			$this->user_identifier = md5($_SERVER['REMOTE_ADDR'] . date("dmYHis") . rand(1000, 9999));

			// save it, and let's go

			$_SESSION['user_identifier'] = $this->user_identifier;
		}

		return $this->user_identifier;
	}

	public function set_data($data, $serialize = true)
	{
		if (!strlen($this->user_identifier)) {
			return false;
		}

		// check if we need to insert or update

		$test_result = mysql_query("select user_identifier from " . $this->table . " where user_identifier='" . $this->user_identifier . "'");
		if (mysql_num_rows($test_result)) {
			$sql = "update " . $this->table . " set data='" . ($serialize ? serialize($data) : (string)$data) . "' where user_identifier='" . $this->user_identifier . "'";
		}
		else {
			$sql = "insert into " . $this->table . " set data='" . ($serialize ? serialize($data) : (string)$data) . "', user_identifier='" . $this->user_identifier . "'";
		}

		if (mysql_query($sql)) return true;
		return false;
	}

	public function get_data($unserialize = true)
	{
		if (!strlen($this->user_identifier)) {
			return $unserialize ? array() : "";
		}

		$result = mysql_query("select data from " . $this->table . " where user_identifier='" . $this->user_identifier . "' limit 1");
		if (!mysql_num_rows($result)) {
			return $unserialize ? array() : ""; //not a good identifier
		}

		$row = mysql_fetch_object($result);
		return $unserialize ? unserialize($row->data) : $row->data;
	}

	public function clear_data()
	{
		if (!strlen($this->user_identifier)) {
			return false;
		}

		if (mysql_query("delete from tracker where user_identifier='" . $this->user_identifier . "'")) {
			$this->user_identifier = "";

			// kill the session tracker

			if (isset($_SESSION['user_identifier']) && strlen($_SESSION['user_identifier'])) {
				unset($_SESSION['user_identifier']);
			}

			return true;
		}

		return false;
	}
}
