<?php

class antifraud_reflag {

    public function create($data) {

        $sql = "INSERT INTO antifraud_redflag_rules SET ";

        foreach ($data as $key => $value) {

            $sql.= "`" . $key . "` = '" . $value . "',";
        }

        $sql.= "date=NOW(),last_update=NOW()";

        mysql_query($sql);

        return mysql_insert_id();
    }

    public function addRule($key, $value) {
        return $this->create(array('category' => $key, 'value' => $value));
    }

    public function delete($oid) {
        $sql = "DELETE from antifraud_redflag_rules where id=" . $oid;
        return mysql_query($sql);
    }

    public function deleteRule($key, $value) {
        $rowId = $this->getIdbyValue($key, $value);
        return $this->delete($rowId);
    }

    public function deleteRuleByCategory($key) {
        $sql = "DELETE from antifraud_redflag_rules where category='" . $key . "'";
        return mysql_query($sql);
    }

    public function update_by_id($id, $data) {

        $sql = "UPDATE antifraud_redflag_rules SET ";

        foreach ($data as $key => $value) {

            $sql.= "`" . $key . "` = '" . $value . "',";
        }

        $sql.= "last_update=NOW() WHERE id=" . intval($id);

        return mysql_query($sql);
    }

    public function getIdbyValue($key, $value) {
        $sql = "select id from antifraud_redflag_rules where category='" . $key . "' AND value='" . $value . "'";
        $result = mysql_query($sql);

        $row = mysql_fetch_object($result);
        return $row->id;
    }

    public function getRulesByCategory($key) {
        $sql = "select * from antifraud_redflag_rules where category='" . $key . "'";
        $result = mysql_query($sql);
        $tn = array();
        while ($row = mysql_fetch_object($result)) {
            $tn[] = $row->value;
        }
        return $tn;
    }

    public function getSingleRuleByCategory($key) {
        $sql = "select * from antifraud_redflag_rules where category='" . $key . "'";
        $result = mysql_query($sql);

        $row = mysql_fetch_object($result);
        return $row->value;
    }

}
