<?php
//die('disabled');
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
  
  include_once("../lib/inventory.class.php");
	
	$con = connect_database();
	
	$user = new umUser();
	$user->get_session();
  
  global $cfg;

  $db = get_pdo_db(
      $cfg['database']['user'],
      $cfg['database']['password'],
      $cfg['database']['dbName']
  );

  $inventory = new Inventory($db,$cfg);
	
	
	if(count($_FILES) && isset($_FILES['import_file']))  {
		
		echo "Checking file was uploaded ... ";ob_flush();flush();
		
		if($_FILES['import_file']['error']==0 && file_exists($_FILES['import_file']['tmp_name'])) {
			echo "OK!<br />";ob_flush();flush();
			} else {
			echo "Failed!";die();
		}
		
		//check it is csv
		echo "Checking file is CSV ... ";ob_flush();flush();
		if(end(explode('.', $_FILES['import_file']['name'])) !== "csv") {
			echo "Failed!";die();
		}
		echo "OK!<br />";ob_flush();flush();
		
		//check proper columns 
		echo "Opening file ... ";ob_flush();flush();
		$file = fopen($_FILES['import_file']['tmp_name'],'r');
		echo "OK!<br />";ob_flush();flush();
		
		echo "Checking headers ... ";ob_flush();flush();
		$header_line = fgetcsv($file);
		
		
		
		$headers = array(
		
		"Order Number",
    "Order Date",
    "Quantity",
    "Mail Class",
    "Tracking Type",
    "Length",
    "Width",
    "Height",
    "Weight",
    "First Name",
    "Last Name",
    "Address",
    "City",
    "State",
    "Postal Code",
    "Country",
    "Description",
    "Exempt",
    "Content",
    "Value",
    "Origin Country",
    "Tracking Number"
		);
    
    $actual_needs = array("Order Number","Tracking Number");
		
		//check the number of columns is ok
		if(count($header_line) == count($headers)) {
			echo "OK!<br />";ob_flush();flush();
			} else {
			echo "Failed! Check format. The columns should be, in this order:". '"Order Number","Order Date","Quantity","Mail Class","Tracking Type","Length","Width","Height","Weight","First Name","Last Name","Address","City","State","Postal Code"," Country","Description","Exempt","Content","Value","Origin Country","Tracking Number"';die();
		}
		
		//got so far, start importing
		$order_object = new order;
		$orders = array();
    $inventory_sum = 0;
		
		while(($row = fgetcsv($file)) !== FALSE) {
			$order = array();
      $order2 = array();
			foreach($headers as $key=>$head) {
				if(isset($row[$key]) && in_array($head,$actual_needs)) {
					
					$order[$head] = $row[$key];
					
				}
			}
			
			
			
			
			if(strlen($order['Order Number']))  {
			
			
        if(strlen($order['Tracking Number'])) {
              
              $order2['user_id'] = $order['Order Number'];
              $order2['shipment_date'] = date("Y-m-d");
              $order2['status'] = 'shipped';		
              $order2['tracking_number'] = $order['Tracking Number'];
              $orders[] = $order2;
			  
			  //get order qty
			  $last_user_order = $order_object->get_last_outstanding_order_for_user($order['Order Number']);
              
              //update inventory sum
              $inventory_sum+=intval($last_user_order->qty);
              
            } 
			
			}
		}
		
		if(!count($orders)) {
			echo "Failed! No orders could be processed from the file!";die();
		}
    
    //update inventory
    $inventory->alterStock($inventory_sum,"-");
    
       		//echo "<pre>".print_r($orders[0],1)."</pre>";die();
		foreach($orders as $order) {
			
			echo "Updating order #".$order["user_id"]." ... ";ob_flush();flush();	
			//print_r($order);die();
			$order_object->update_unshipped($order["user_id"],$order);
			echo "Done!<br />";ob_flush();flush();
			echo "<script>window.parent.import_callback();</script>";ob_flush();flush();
			
		}
    
    
		
		echo "Click <a href='#' onclick='window.parent.location.href=\"orders.php\"'>here</a> to reload the page<br />";ob_flush();flush();
		
		fclose($file);
		
	}	