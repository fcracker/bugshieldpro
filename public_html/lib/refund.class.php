<?php
class Refund{
    public function set_refund_history($trans_id, $amount){
        global $cfg;
        $sql = "INSERT INTO ".$cfg['database']['prefix']."refund_history SET
                  trans_id='".$trans_id."'
                  ,refund_amount='".$amount."'";
        mysql_query($sql);
    }

    public function set_refund_schedule($param){
        global $cfg;
        $sql = "INSERT INTO ".$cfg['database']['prefix']."refund_schedule SET
                    trans_id='".$param["trans_id"]."'
                    ,refund_period='".$param["refund_period"]."'
                    ,refund_cycle='".$param["refund_cycle"]."'
                    ,amount='".$param["amount"]."'
                    ,next_refund_date='".$param["next_refund_date"]."'
                    ,status='".$param["status"]."'
                ";
        mysql_query($sql);
    }

    public function set_next_date_schedule($trans_id,$refundable_amount){
        global $cfg;
        $data = $this->get_refund_schedule($trans_id);
        $next_date = $this->date_add($data["next_refund_date"], $data["refund_period"], $data["refund_cycle"]);
        if($refundable_amount == 0){
            $insert_str = " , status = 'final'";
        }
        $sql = "UPDATE ".$cfg['database']['prefix']."refund_schedule SET next_refund_date='$next_date' $insert_str WHERE trans_id='$trans_id'";
        return mysql_query($sql);
    }
    
    private function date_add($givendate,$unit="day",$cycle=0){
        $cd = strtotime($givendate);
        if($unit == "day"){
            $day = $cycle;
            $month = 0;
        }else if($unit == "week"){
            $day = 7 * $cycle;
            $month = 0;
        }else if($unit == "month"){
            $day = 0;
            $month = $cycle;
        }else{
            return $cd;
        }
        $newdate = date('Y-m-d h:i:s', mktime(date('h',$cd),    date('i',$cd), date('s',$cd), date('m',$cd)+$month,  date('d',$cd)+$day, date('Y',$cd)));
        return $newdate;
    }

    public function change_refund_schedule_status($trans_id, $status="final"){
        global $cfg;
        $sql = "UPDATE ".$cfg['database']['prefix']."refund_schedule SET
                    status='$status'
                WHERE trans_id='$trans_id'";
        mysql_query($sql);
    }

    public function get_refund_history($trans_id){
        global $cfg;
        $sql = "SELECT * FROM ".$cfg['database']['prefix']."refund_history WHERE trans_id='$trans_id' ORDER BY refund_date";
        return multi_query_assoc($sql);
    }

    public function get_refund_schedule($trans_id){
        global $cfg;
        $sql = "SELECT * FROM ".$cfg['database']['prefix']."refund_schedule WHERE trans_id='$trans_id'";
        return single_query_assoc($sql);
    }
}
?>
