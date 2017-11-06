<?php
include_once("../lib/security.inc.php");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/user.class.php");
require_once("../lib/PayFrontEnd.php");

$con = connect_database();
$trans_id = @$_POST["trans_id"];
if($trans_id == "")die("ILLEGAL ACCESS");
$payObj = new PayFrontEnd();
switch ($_POST["method"]){
    case "full" :
        $resArray = $payObj->doRefund($trans_id);
        if($resArray["ACK"] == "Success"){
            echo "Refund was approved!";
        }else{
            echo $resArray["L_LONGMESSAGE0"];
        }
        break;
    case "part" :
        $resArray = $payObj->doRefund($trans_id, $_POST["amount"]);
        if($resArray["ACK"] == "Success"){
            echo "Refund was approved!";
        }else{
            echo $resArray["L_LONGMESSAGE0"];
        }
        break;
    case "recurring" :
        $_POST["status"] = "active";
        $_POST["next_refund_date"] = date("Y-m-d H:i:s");
        $payObj->refundObj->set_refund_schedule($_POST);
        $resArray = $payObj->doRefund($trans_id,$_POST["amount"]);
        $refundable_amount = $payObj->backEndObj->getRefundable_amount($trans_id);
        $payObj->refundObj->set_next_date_schedule($trans_id,$refundable_amount);
        if($resArray["ACK"] == "Success"){
            echo "Refund was approved!";
        }else{
            echo $resArray["L_LONGMESSAGE0"];
        }
        break;
    case "view_history" :
        $rows = $payObj->refundObj->get_refund_history($trans_id);
        if(count($rows)){
            $return_str = "<table width=370px cellspacing=0 cellpadding=0 ><tr bgcolor=\"#DDDDDD\" height=25px><th width=200px>refunded_date</th><th>retunded_amount</th></tr>";
            foreach ($rows as $row){
                $return_str .= "<tr height=25px>";
                $return_str .= "<td align=center>".$row["refund_date"]."</td>";
                $return_str .= "<td align=center>$".$row["refund_amount"]."</td>";
                $return_str .= "</tr>";
            }
            $return_str .= "</table>";
        }else{
            $return_str = "<p>no data!</p>";
        }
        echo $return_str;
        break;
    case "check_schedule" :
        $refundable_amount = $payObj->backEndObj->getRefundable_amount($trans_id);
        $json = '{"refundable_amount":"'.$refundable_amount.'"';
        if($refundable_amount != 0){
            $row = $payObj->refundObj->get_refund_schedule($trans_id);
            if(count($row)){
                $json .= ',"recurring":"true"';
                $json .= ',"cycle":"'.$row["refund_cycle"].'"';
                $json .= ',"period":"'.$row["refund_period"].'"';
                $json .= ',"amount":"'.$row["amount"].'"';
                $json .= ',"next_refund_date":"'.$row["next_refund_date"].'"';
                $json .= ',"status":"'.$row["status"].'"';
            }
        }
        $json .= "}";
        echo $json;
        break;
    default :
}

?>
