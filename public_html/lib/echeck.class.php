<?php
include_once 'PayBackEnd.php';

class echeck
{

private $data;//holds relevant data to payment
private $backEndObj;


public function __construct(){
   $this->backEndObj = new PayBackEnd();
}

public function set_data($data) {
  $this->data = $data;
}


public function pay() {

  //do a call with the provided data
  
  
  //THIS IS A TEST
  $call_result = array("result"=>"OK","message"=>"Transfer done");
  
  //set history
  /*
   $param = array(
                    "bankid"  =>  $this->bank_id,
                    "transactionid"  =>  $trans_id,
                    "userid"  =>  $user_id,
                    "methodtype"  =>  "direct",
                    "amount"  =>  $amount,
                    "processor_id" => $processor_id
            );
    $this->backEndObj->setHistory($param);
    */
  
  return $call_result;

}



}