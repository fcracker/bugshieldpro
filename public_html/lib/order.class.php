<?php
class order
{
	public function create($data)
	{
		$sql = "INSERT INTO mem_order SET ";
		foreach($data as $key => $value) {
			$sql.= "`" . $key . "` = '" . $value . "',";
		}

		$sql.= "date=NOW(),last_update=NOW()";
		mysql_query($sql);
		return mysql_insert_id();
	}

	public function create_with_no_date_date($data)
	{
		$sql = "INSERT INTO mem_order SET ";
		$parts = array();
		foreach($data as $key => $value) {
			$parts[] = "`" . $key . "` = '" . $value . "'";
		}

		$sql.= implode(",", $parts);
		mysql_query($sql);
		return mysql_insert_id();
	}

	public function update($userid, $data)
	{
		$sql = "UPDATE mem_order SET ";
		foreach($data as $key => $value) {
			$sql.= "`" . $key . "` = '" . $value . "',";
		}

		$sql.= "last_update=NOW() WHERE user_id=" . intval($userid);
		mysql_query($sql);
	}

	public function update_unshipped($userid, $data)
	{
		$sql = "UPDATE mem_order SET ";
		foreach($data as $key => $value) {
			if (strlen($value)) {
				$sql.= "`" . $key . "` = '" . $value . "',";
			}
		}

		$sql.= "last_update=NOW() WHERE status='not shipped' AND user_id=" . intval($userid);
		mysql_query($sql);
	}

	public function delete($oid)
	{
		$sql = "DELETE from mem_order where id=" . $oid;
		return mysql_query($sql);
	}

	public function update_by_id($id, $data)
	{
		$sql = "UPDATE mem_order SET ";
		foreach($data as $key => $value) {
			$sql.= "`" . $key . "` = '" . $value . "',";
		}

		$sql.= "last_update=NOW() WHERE id=" . intval($id);
		mysql_query($sql);
		$d = $this->get_specific_orders(array(
			$id
		));
		return $d[0];
	}

	public function has_tracking_number($user_id)
	{
		$sql = "select tracking_number from mem_order where user_id='" . intval($user_id) . "' limit 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		if (strlen($row->tracking_number)) return true;
		return false;
	}

	public function has_tracking_number_by_id($id)
	{
		$sql = "select tracking_number from mem_order where id='" . intval($id) . "' limit 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		if (strlen($row->tracking_number)) return true;
		return false;
	}

	public function get_tracking_number($user_id)
	{
		$sql = "select tracking_number from mem_order where user_id='" . intval($user_id) . "' limit 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		return $row->tracking_number;
	}

	static public function get_all_tracking_numbers($user_id)
	{
		$sql = "select tracking_number from mem_order where user_id='" . intval($user_id) . "'";
		$result = mysql_query($sql);
		$tn = array();
		while ($row = mysql_fetch_object($result)) {
			$tn[] = $row->tracking_number;
		}

		return $tn;
	}

	public function get_tracking_number_by_id($id)
	{
		$sql = "select tracking_number from mem_order where id='" . intval($id) . "' limit 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		return $row->tracking_number;
	}

	public function get_orders_by_status($status = "not shipped")
	{
		$sql = "select * from mem_order where status='" . mysql_real_escape_string($status) . "' order by date asc";
		$result = mysql_query($sql);
		$data = array();
		while ($row = mysql_fetch_object($result)) {
			$data[] = $row;
		}

		return $data;
	}

	public function get_orders_by_status_date_restricted($status = "not shipped", $from = "", $to = "", $orderby = "date", $order = "asc")
	{
		$sql = "select * from mem_order where status='" . mysql_real_escape_string($status) . "'";
		if (strlen($from)) {
			$sql.= " and date>='" . $from . "'";
		}

		if (strlen($to)) {
			$sql.= " and date<='" . $to . " 23:59:59'"; //make sure it covers the entire day
		}

		// order

		$sql.= " order by " . $orderby . " " . $order;
		$result = mysql_query($sql);
		$data = array();
		while ($row = mysql_fetch_object($result)) {
			$data[] = $row;
		}

		return $data;
	}

	public function get_orders_date_restricted($from = "", $to = "", $page = 0, $per_page = 10)
	{
		$sql = "select * from mem_order where 1=1";
		if (strlen($from)) {
			$sql.= " and date>='" . $from . "'";
		}

		if (strlen($to)) {
			$sql.= " and date<='" . $to . " 23:59:59'"; //make sure it covers the entire day
		}

		// order

		$sql.= " order by date asc limit " . ($page * $per_page) . "," . $per_page;
		$result = mysql_query($sql);
		$data = array();
		while ($row = mysql_fetch_object($result)) {
			$data[] = $row;
		}

		return $data;
	}

	public function get_total_qtys_date_restricted($from = "", $to = "")
	{
		$sql = "select SUM(qty) as total from mem_order where 1=1";
		if (strlen($from)) {
			$sql.= " and date>='" . $from . "'";
		}

		if (strlen($to)) {
			$sql.= " and date<='" . $to . " 23:59:59'"; //make sure it covers the entire day
		}

		$sql.= " and status='shipped'";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		return intval($row->total);
	}

	public function get_specific_orders($data)
	{
		$return = array();
		if (is_array($data) && count($data)) {
			$sql = "select * from mem_order where id IN (" . implode(",", $data) . ")";
			$result = mysql_query($sql);
			while ($row = mysql_fetch_object($result)) {
				$return[] = $row;
			}
		}

		return $return;
	}

	public function get_specific_orders_by_user($data)
	{
		$return = array();
		if (is_array($data) && count($data)) {
			$sql = "select * from mem_order where user_id IN (" . implode(",", $data) . ")";
			$result = mysql_query($sql);
			while ($row = mysql_fetch_object($result)) {
				$return[] = $row;
			}
		}

		return $return;
	}

	public function get_last_outstanding_order_for_user($userid)
	{
		if (is_numeric($userid) && $userid > 0) {
			$sql = "select * from mem_order where user_id=" . $userid . " and status='not shipped' order by date desc limit 1";
			$result = mysql_query($sql);
			if (mysql_num_rows($result)) {
				$row = mysql_fetch_object($result);
				return $row;
			}
		}

		return false;
	}

	public function get_last_order_for_user($userid)
	{
		if (is_numeric($userid) && $userid > 0) {
			$sql = "select * from mem_order where user_id=" . $userid . " order by date desc limit 1";
			$result = mysql_query($sql);
			if (mysql_num_rows($result)) {
				$row = mysql_fetch_object($result);
				return $row;
			}
		}

		return false;
	}

	public function get_min_max_dates($as_dates = true)
	{
		$sql = "SELECT MIN(date) as min,MAX(date) as max FROM `mem_order` WHERE 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		$ret = array(
			"min" => $as_dates ? date("Y-m-d", strtotime($row->min)) : $row->min,
			"max" => $as_dates ? date("Y-m-d", strtotime($row->max)) : $row->max,
		);
		return $ret;
	}

	public function getBankName($order_id)
	{
		$sql = "SELECT
                    b.BankName as bankname
                FROM `mem_order` AS o 
                LEFT JOIN mem_merchant_history AS h ON o.raw_response=h.raw_response 
                LEFT JOIN mem_merchant AS b ON b.BankID=h.BankID 
                WHERE 1";
		$sql.= " AND o.id={$order_id}";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		return $row->bankname;
	}

	public function getBankHistory($user_id)
	{
		$sql = "SELECT
                    b.BankName as bankname,
                    h.hDate as hdate,
                    h.hAmount as amount
                FROM `mem_order` AS o 
                LEFT JOIN mem_merchant_history AS h ON o.raw_response=h.raw_response 
                LEFT JOIN mem_merchant AS b ON b.BankID=h.BankID 
                WHERE o.user_id = {$user_id} ORDER BY h.hDate ASC";
		$result = mysql_query($sql);
		$return = array();
		while ($row = mysql_fetch_object($result)) {
			$return[] = $row;
		}

		return $return;
	}

	public function getNote($user_id)
	{
		$sql = "select notes as note from mem_user where UserID='" . intval($user_id) . "' limit 1";
		$result = mysql_query($sql);
		$row = mysql_fetch_object($result);
		return $row->note;
	}

	public function flagAsfraudulent($orderId, $flag)
	{
		if ($flag) {
			$this->update_by_id($orderId, array(
				"fraudulent_flag" => $flag
			));
		}
		else {
			$this->update_by_id($orderId, array(
				"fraudulent_flag" => 0,
				"fraudulent_investigated" => 0
			));
		}

		return $flag;
	}
}
