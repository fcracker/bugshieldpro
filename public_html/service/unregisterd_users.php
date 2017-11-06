<?php
/*
 * init web page
 */
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");

$con = connect_database();

$message = "";

//check if we need to delete
if(is_array($_POST["del_unreg"]) && count($_POST["del_unreg"])){
		$counter = 0;
		foreach($_POST["del_unreg"] as $del){
			$parts = explode("::",$del);
			
			@mysql_query("delete from ".$cfg['database']['prefix']."user_temp where Email='".$parts[0]."' and registered_time='".$parts[1]."' limit 1");
				
				$counter++;
		}
			$message.= "Deleted ".$counter." out of ".count($_POST["del_unreg"])." requested";
}

if(!isset($_POST['pageSize'])) $_POST['pageSize'] = 10; // set default page size
if(!isset($_POST['orderBy'])) $_POST['orderBy'] = "UserID DESC"; // set default order
// allow input groupID with get method
if(!isset($_POST['groupID']) && isset($_GET['groupID'])) $_POST['groupID'] = $_GET['groupID'];

$user = new umUser();

$user->get_session();

$allowGroups = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Orders Information";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/unregisterd_users.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$page->blocks['content'] = list_users($message);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/unregisterd_users.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

close_database($con);

function list_users($message){
	global $cfg;
	global $lang;
	global $user;
	// load all groups

	
	$html = "";
	// javascript
	$html .= '
	<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.4.2.min.js"></script>';
	
	$html.="<script language='javascript'>";	
		$html.="function toggle_del(xx) {var c = \$(xx).attr('checked'); \$('.unregistered_users_cb').each(function(){\$(this).attr('checked',c);});}";
	$html.="</script>";
	
	
	if(strlen($message)){
		$html.="<div class='resultDiv'>";
			$html.="<img align='absmiddle' src='/images/incoming.gif'>";
			$html.=$message;
		$html.="</div>";	
	}
	
	// title
	$html .= "<div class=\"listContent\">\n";
	
	// page navigation
	$html.= "<form name='unreg_users' action='' method='POST'>";
	$html .= "<p>\n";

	////
	$html .= "	<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" style=\"margin-top:30px;\" class=\"titleTable\">\n";
	$html .= "		<tr>\n";
	$html .= "			<td class=\"titleCell\">\n";
	$html .= "				Orders Information (Unregisterd Users)";
	$html .= "			</td>\n";
	$html .= "			<td align=\"right\">\n";
	$html .= "<input type=\"submit\" value=\"Delete Selected\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick='return confirm(\"Are you sure?\")' />";
	$html .= "			</td>\n";
	$html .= "		</tr>\n";
	$html .= "	</table>\n";
	
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "	<tr class=\"captionRow\">\n";
	$html .= "		<td width=\"2%\"><input type='checkbox' name='un_del_trigger' id='del_trigger' onclick='toggle_del(this)' /></td>\n";
	$html .= "		<td width=\"2%\">No</td>\n";
	$html .= "		<td width=\"8%\">Email</td>\n";
	$html .= "		<td width=\"7%\">First Name</td>\n";
	$html .= "		<td width=\"7%\">Last Name</td>\n";
	$html .= "		<td width=\"8%\">Card Number</td>\n";
	$html .= "		<td width=\"6%\">Name on Card</td>\n";
	$html .= "		<td width=\"8%\">Order Date</td>\n";
	$html .= "		<td width=\"6%\">Country</td>\n";
	$html .= "		<td width=\"10%\">State</td>\n";
	$html .= "		<td width=\"12%\">City</td>\n";
	$html .= "		<td width=\"14%\">Address</td>\n";
	$html .= "		<td width=\"7%\">Action</td>\n";
	$html .= "	</tr>\n";
	
	$sql = "SELECT DISTINCT  t.* FROM ".$cfg['database']['prefix']."user_temp t LEFT JOIN mem_merchant_history m on m.user_email=t.Email WHERE m.transaction_id<>'offline-firstsale' ORDER BY t.registered_time DESC";
  //$sql = "SELECT t.* FROM ".$cfg['database']['prefix']."user_temp t";
	$rst = mysql_query($sql);
	if(mysql_num_rows($rst)) {
		$i = 0;
		while($row=mysql_fetch_array($rst)){
			if ($i % 2 == 0) {
				$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
			} else {
				$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
			}
			
			$cardNumber = 	$row['cardnumber'] == "" ? "" : $user->Cipher->decrypt($row['cardnumber']);
			$cardNumber = 	substr($cardNumber, 0, 4) . substr(str_repeat("*", 8),0,3) . substr($cardNumber, -4);
			
			$cardname 	= 	$row['cardname']==""?"":$user->Cipher->decrypt($row['cardname']);
			$cardname 	= 	substr(str_repeat("*",strlen($cardname)),0, 3);
			
			$html .= "		<td><input type='checkbox' class='unregistered_users_cb' name='del_unreg[]' value='".$row['Email']."::".$row['registered_time']."' /></td>\n";
			$html .= "		<td>".(1+$i)."</td>\n";
			$html .= "		<td>".$row['Email']."</td>\n";
			$html .= "		<td>".$row['firstname']."</td>\n";
			$html .= "		<td>".$row['lastname']."</td>\n";
			$html .= "		<td>".$cardNumber."</td>\n";
			$html .= "		<td>".$cardname."</td>\n";
			$html .= "		<td>".date($lang['timeFormat'], strtotime($row['registered_time']))."</td>\n";
			$html .= "		<td>".$row['country']."</td>\n";
			$html .= "		<td>".$row['state']."</td>\n";
			$html .= "		<td>".$row['city']."</td>\n";
			$html .= "		<td>".$row['address']."</td>\n";
			$html .= "		<td>[ <a href=\"".sess_url($cfg['site']['url'].$cfg['site']['folder']."service/manually_register.php?email=".$row['Email'])."\">Register</a> ]</td>\n";
			$html .= "	</tr>\n";
			$i++;
		}
	}else{
		$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">
					<td colspan='11'>No data.</td>	
				</tr>
					";
	}
	$html .= "	</table>\n";
	$html .= "</p>\n";
	$html .= "</form>";
	$html .= "</div>\n";
	return $html;
}

function changeCardNumber($num){
	if (strlen($num) > 8){
		$tmp = substr($num, 0, 4);
		for ($i = 4;$i < strlen($num) - 4; $i++){
			$tmp .= "*";
		}
		$tmp .= substr($num, strlen($num) - 4, 4);
		return $tmp;
	}	
}