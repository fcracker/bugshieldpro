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
class umUnsubscribe {
    //put your code here
    function getData($order = "order"){
        global $cfg;
        $sql = "SELECT email, requestdate, exportdate FROM ".$cfg['database']['prefix']."unsubscribe";
		if ($order == 'order'){
			$sql .= " Order By requestdate";
		} else {
			$sql .= " Order By exportdate";
		}
        $reAry = array();
        $rst = @mysql_query($sql);
        if(@mysql_num_rows($rst)){
            while($row=mysql_fetch_array($rst, MYSQL_NUM)){
                $tempAry = array();
                $tempAry['email'] = $row[0];
                $tempAry['requestdate'] = $row[1];
                $tempAry['exportdate'] = $row[2];
                $reAry[] = $tempAry;
            }
        }
        return $reAry;
    }
}
?>
