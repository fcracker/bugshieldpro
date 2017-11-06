<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/conversions.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");

include_once("../lib/order.class.php");

include_once("../lib/PayBackEnd.php");

include_once("export_helper.php");

global $cfg;

$con = connect_database();

$user = new umUser();
$user->get_session();

$order = new order;

//get min/max
$min_max = $order->get_min_max_dates();
$min = $min_max["min"];
$max = $min_max["max"];

$export_params = array(

	"status"	=> "not shipped",
	"from_date"	=> $min,
	"to_date"	=> $max,


);//the export params


//merge params
if(count($_POST)) {
	
	foreach($_POST as $key=>$value) {
		//is it a valid param ?
		if(array_key_exists($key,$export_params)) {
			$export_params[$key] = $value;
		}
	}
	
}

//export can be made by IDs as well, and that has priority over other filters
$selected_export = false;
if(isset($_POST['orderids'])) {
	if(is_array($_POST['orderids']) && count($_POST['orderids'])) {
		$order_ids_to_export = $_POST['orderids'];
		$selected_export = true;
	}
	}



if(!$selected_export) {
$data = $order->get_orders_by_status_date_restricted($export_params["status"],$export_params["from_date"],$export_params["to_date"]);
}
else {
//clean up
$order_ids = array();
foreach($order_ids_to_export as $oid) {
		if(!in_array(intval($oid),$order_ids))
			$order_ids[] = intval($oid);
	}
	
$data = $order->get_specific_orders_by_user($order_ids);
}


$line_sep = "\r\n";
$csv_data = "";

//header
$csv_data.= '"Order ID","Quantity","Description","Type"'.$line_sep;

//used in order to make sure orders are only listed once
$data2 = array();

foreach($data as $d) {
  if(array_key_exists($d->user_id,$data2)) {
      if(strtotime($data2[$d->user_id]->date) < strtotime($d->date)) {
        $data2[$d->user_id] = $d;
      }
    } else {
      $data2[$d->user_id] = $d;
    }
}

$pay = new PayBackEnd();

foreach($data2 as $d) { 
	
  //check if the order is from a rebill or from an initial sale
  
  //get payment history
  $payment_history = $pay->getAllPayHistory($d->email);

   //$user_data = $umUser->get_user_info_by_id($d->user_id);
   
   $is_rebill_text = "N/A";
   
   foreach($payment_history as $ph) {
	
	if(date("dmY",$d->date) == date("dmY",$ph->hDate)) {
		if($ph->is_rebill || $ph->is_yearly_rebill) {
			$is_rebill_text = "R";
		} else {
			$is_rebill_text = "N";
		}
	}	
   }
   
   
	
  $csv_data.= '"'.$d->user_id.'",';//order no
  $csv_data.= '"'.$d->qty.'",';//order qty
  $csv_data.= '"'.$d->description.'",';//description
  $csv_data.= '"'.$is_rebill_text.'"';//type 

	$csv_data.= $line_sep;//separator
	
	
	}
//set proper headers
//die($csv_data);
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="staging-export-orders-'.date("Ymd-H-i").'.csv"');

echo $csv_data;
die();













