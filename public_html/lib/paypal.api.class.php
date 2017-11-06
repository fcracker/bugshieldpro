<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of paypal
 *
 * @author Chang Myong Chol
 * @since 2010-10-26
 */
class umPaypal {
    //put your code here
    function getData(){
        global $cfg;
        $sql = "SELECT ID, ApiUserName, ApiUserPassword, ApiSignature, ApiEndPoint, Activate FROM ".$cfg['database']['prefix']."paypal_api";
        $reAry = array();
        $rst = mysql_query($sql);
        if(mysql_num_rows($rst)){
            while($row=mysql_fetch_array($rst, MYSQL_NUM)){
                $tempAry = array();
                $tempAry['ID'] = $row[0];
                $tempAry['username'] = $row[1];
                $tempAry['password'] = $row[2];
                $tempAry['signature'] = $row[3];
                $tempAry['endpoint'] = $row[4];
                $tempAry['activate'] = $row[5];
                $reAry[] = $tempAry;
            }
        }
        return $reAry;
    }

    function setData($data){
        global $cfg;
        if (!isset ($data['activate'])) $data['activate'] = 0;
            $sql = "INSERT INTO ".$cfg['database']['prefix']."paypal_api ";
            $sql .= "(ApiUserName, ApiUserPassword, ApiSignature, ApiEndPoint, Activate) VALUES ";
            $sql .= "('".$data['username']."','".$data['userpassword']."','".$data['signature']."','".$data['endpoint']."','".$data['activate']."')";
            mysql_query($sql);
	}

    function updateData($data){
        global $cfg;
        $sql = "UPDATE ".$cfg['database']['prefix']."paypal_api ";
        $sql .= "SET ApiUserName='".$data['username']."', ApiUserPassword='".$data['userpassword']."', ApiSignature='".$data['signature']."', ApiEndPoint='".$data['endpoint']."' ";
        $sql .= "WHERE ID='".$data['id']."' ";
        mysql_query($sql);
    }

    function deleteData($data){
        global $cfg;
        $sql = "DELETE FROM ".$cfg['database']['prefix']."paypal_api ";
        $sql .= "WHERE ID='".$data['id']."' ";
        mysql_query($sql);
    }

    function setActive($id){
        global $cfg;
        $sql = "Update ".$cfg['database']['prefix']."paypal_api Set Activate='0'";
        mysql_query($sql);
        $query = "Update ".$cfg['database']['prefix']."paypal_api Set Activate='1' Where ID=$id";
        mysql_query($query);
    }
}
?>
