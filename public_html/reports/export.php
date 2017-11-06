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


$line_sep = "\n\r";
$csv_data = "";

//header
$csv_data.= "Type,Quantity,Order #,Order Date,First Name,Last Name,Address,Country,City,State,Postal Code".$line_sep;

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

foreach($data2 as $d) {
	
    
  
	$csv_data.= $d->type.",";//type
	$csv_data.= $d->qty.",";//qty
	$csv_data.= $d->user_id.",";//order no
	$csv_data.= $d->date.",";//order date
	$csv_data.= $d->firstname.",";//first name
	$csv_data.= $d->lastname.",";//last name
	$csv_data.= $d->address.",";//address
	$csv_data.= $d->country.",";//country
	$csv_data.= $d->city.",";//city
	$csv_data.= $d->state.",";//state
	$csv_data.= $d->zip."";//zip	
	$csv_data.= $line_sep;//separator
	
	
	}
	
//set proper headers
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="orders-export-'.date("Ymd-H-i").'.csv"');

echo $csv_data;
die();













