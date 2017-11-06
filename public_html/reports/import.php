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
		
		"qty",
		"user_id",
		"shipment_date",
		"firstname",
		"lastname",
		"address",
		"country",
		"city",
		"state",
		"zip",
		"tracking_number"
		);
		
		//check the number of columns is ok
		if(count($header_line) == count($headers)) {
			echo "OK!<br />";ob_flush();flush();
			} else {
			echo "Failed! Check format. The columns should be, in this order: Quantity, Order #, Shipment Date, First Name, Last Name, Address, Country, City, State, Post Code,Tracking#";die();
		}
		
		//got so far, start importing
		$order_object = new order;
		$orders = array();
		
		while(($row = fgetcsv($file)) !== FALSE) {
			$order = array();
			foreach($headers as $key=>$head) {
				if(isset($row[$key])) {
					
					$order[$head] = $row[$key];
					
				}
			}
			
			
			
			
			if(strlen($order['user_id']))  {
			
			
			if(strlen($order['shipment_date']) && strlen($order['tracking_number'])) {
						
						$order['shipment_date'] = date("Y-m-d",strtotime($order['shipment_date']));
						$order['status'] = 'shipped';					
						
					} else {
					$order['shipment_date'] = '';
					$order['status'] = 'not shipped';	
				}
			
			$orders[] = $order;
			
			
			}
		}
		
		if(!count($orders)) {
			echo "Failed! No orders could be processed from the file!";die();
		}
		
		foreach($orders as $order) {
			
			echo "Updating order #".$order["user_id"]." ... ";ob_flush();flush();	
			//print_r($order);die();
			$order_object->update($order["user_id"],$order);
			echo "Done!<br />";ob_flush();flush();
			echo "<script>window.parent.import_callback();</script>";ob_flush();flush();
			
		}
		
		echo "Click <a href='#' onclick='window.parent.location.href=\"orders.php\"'>here</a> to reload the page<br />";ob_flush();flush();
		
		fclose($file);
		
	}	