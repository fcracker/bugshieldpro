<?php
/*
 * init web page
 */

//session_start();
//$_SESSION['result'] = array();
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
include_once("../lib/PayBackEnd.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];




$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_merchant.php");
}

$merchant = new PayBackEnd();
if(!isset($_POST['merchant_type'])) $_POST['merchant_type']='1';
if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	
	if(isset($_GET['p'])){
		$merchant->bankID = $_GET["BankID"];
		if($_GET['p']=="up") $merchant->exchange_tier($_GET['tier'], -1);
		if($_GET['p']=="down") $merchant->exchange_tier($_GET['tier'], 1);
		if($_GET['p']=="del") $merchant->delete_merchant($_GET['tier']);
    if($_GET['p']=="activate") $merchant->reinstate_merchant();
    if($_GET['p']=="reldel") $merchant->really_delete_merchant();
		$merchant->bankID = 0;
	}
	
	$merchantResult = $merchant->get_merchant($_POST['merchant_type']);
  
  $type = $_POST['merchant_type']=='1' ? 'active':'inactive';
  
	$page->blocks['content'] = list_merchants($merchantResult,$type);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_merchant.php");
}
/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

//if(isset($_GET['amount'])){
//	$row = $merchant->getGateway($_GET['amount'], '');
//	if(!isset($_SESSION['result'])) $_SESSION['result'] = array();
//	if(count($row)) $_SESSION['result'][] = $row;
//	$data = $_SESSION['result'];
//	$BankIDs = array(0,0,0,0,0,0);
//	$echo = "<table border='1px'>
//			<tr>
//				<th>#</th>
//				<th>BankID</th>
//				<th>gatewayType</th>
//				<th>username</th>
//				<th>password</th>
//				<th>signature</th>
//			</tr>
//			";
//	for($i=0; $i<count($data); $i++){
//		if($data[$i][2]==0) continue;
//		$echo .= "
//			<tr>
//				<td>".($i+1)."</td>
//				<td>".$data[$i][2]."</td>
//				<td>".$data[$i][0]."</td>
//				<td>".$data[$i][1]['username']."</td>
//				<td>".$data[$i][1]['password']."</td>
//				<td>".$data[$i][1]['signature']."</td>
//			</tr>
//			";
//		$BankIDs[$data[$i][2]]++;
//	}
//	$echo .= "</table>";
//	echo "<table border='1px'>
//			<tr>
//				<th>BankID</th>
//				<th>Count's</th>
//			</tr>
//			";
//	for($i=1; $i<count($BankIDs); $i++){
//		echo "
//			<tr>
//				<th>$i</th>
//				<th>".$BankIDs[$i]."</th>
//			</tr>
//			";
//	}
//	echo  "</table>";
//	echo "<br />";
//	echo $echo;
//	if(!count($row)){
//		echo "<b>The End</b>";
//	}else{
//		$param = array('bankid'=>$row[2], 'transactionid'=>'', 'userid'=>'mother@gmail.com', 'methodtype'=>'', 'amount'=>$_GET['amount']);
//		$merchant->setHistory($param);
//	}
//}

close_database($con);

/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/

function list_merchants($merchantResult,$type='active'){
	global $cfg;
	global $lang;
	global $menuActiveIndex;
	$html = "";
	
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">Manage Merchant</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<form name='merchant_form' method='post'>\n";
	$html .= "<select name='merchant_type' onchange='javascript:merchant_form.submit()' valign='bottom'><option value='1' ".($_POST['merchant_type']=='1'?"selected":"").">Active</option><option value='0' ".($_POST['merchant_type']=='0'?"selected":"").">Inactive</option></select>\n";
	$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Add Merchant\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"location.href='".sess_url("merchant_detail.php?menuIndex=".$menuActiveIndex)."'\">\n";
	$html .= "</form>\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	
	
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"3%\">Tier</td>";
	$html .= "<td width=\"20%\">Gateway Name</td>";
	$html .= "<td width=\"10%\">Monthly Limit($)</td>";
	$html .= "<td width=\"10%\">Payment Period</td>";
	$html .= "<td width=\"5%\">Percent(%)</td>";
	$html .= "<td width=\"8%\">Gateway Type</td>";
	$html .= "<td width=\"13%\">Gateway ID</td>";
	//$html .= "<td width=\"10%\">Gateway Key</td>";
	//$html .= "<td width=\"9%\">Gateway Sign</td>";
  $html .= "<td width=\"14%\">Spent(".date("M").")</td>";
	$html .= "<td width=\"10%\">Action</td>";
	$html .= "</tr>\n";
  
  $backend = new PayBackEnd();
  
	// list data
	$count = count($merchantResult);
	for($i=0; $i < $count; $i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}	
		
		
    //get monthly sum
    $monthly_sum = $backend->getMontlySpent($merchantResult[$i]['BankID'],$merchantResult[$i]['payment_period']);
    $spent_cap = ($merchantResult[$i]['cap_per_month']/1000);
    $spent_sum = sprintf("%0.2f",($monthly_sum/1000));
    if($merchantResult[$i]['cap_per_month']!=0)
      $spent_percentage = sprintf("%0.2f",($monthly_sum * 100)/$merchantResult[$i]['cap_per_month']);
     else
      $spent_percentage = 0;
    $spent = "\$".$spent_sum."k / \$".$spent_cap."k (".$spent_percentage."%)";
    
    
		$html .= "<td>".$merchantResult[$i]['tier']."</td>";
		$html .= "<td>".$merchantResult[$i]['BankName']."</td>";
		$html .= "<td>$".$merchantResult[$i]['cap_per_month']."</td>";
		$html .= "<td>".($merchantResult[$i]['payment_period']==0?"Daily":"Monthly")."</td>";
		$html .= "<td>".$merchantResult[$i]['persent']."</td>";
		$html .= "<td>".$merchantResult[$i]['gatewayType']."</td>";
		$html .= "<td>".$merchantResult[$i]['gatewayID']."</td>";
		//$html .= "<td>".$merchantResult[$i]['gatewayKey']."</td>";
		//$html .= "<td>".$merchantResult[$i]['gatewaySign']."</td>";
    $html .= "<td>".$spent."</td>";
		$html .= "<td>";
//		if($i==0)
//			$html .= "&nbsp[&nbsp;<a href='#'>Up</a>&nbsp;]&nbsp;";
//		else
//			$html .= "&nbsp[&nbsp;<a href='manage_merchant.php?p=up&tier=".$merchantResult[$i]['tier']."&BankID=".$merchantResult[$i]['BankID']."'>Up</a>&nbsp;]&nbsp;";
//		
//		if($count == ($i+1))
//			$html .= "&nbsp[&nbsp;<a href='#'>Down</a>&nbsp;]&nbsp;";
//		else
//			$html .= "&nbsp[&nbsp;<a href='manage_merchant.php?p=down&tier=".$merchantResult[$i]['tier']."&BankID=".$merchantResult[$i]['BankID']."'>Down</a>&nbsp;]&nbsp;";
		$html .= "&nbsp[&nbsp;<a href='".sess_url("merchant_detail.php?menuIndex=".$menuActiveIndex."&BankID=".$merchantResult[$i]['BankID']).($type=="inactive" ? "&inactive=1":"")."'>Edit</a>&nbsp;]&nbsp;";
    
    if($type=="active") {
		$html .= "&nbsp[&nbsp;<a href='".sess_url("manage_merchant.php?p=del&tier=".$merchantResult[$i]['tier']."&BankID=".$merchantResult[$i]['BankID'])."'>inactive</a>&nbsp;]&nbsp;";
    } else {
      $html .= "&nbsp[&nbsp;<a href='".sess_url("manage_merchant.php?p=activate&BankID=".$merchantResult[$i]['BankID'])."'>active</a>&nbsp;]&nbsp;";
      
      $html .= "&nbsp[&nbsp;<a onclick='return confirm(\"Are you sure? This cannot be undone.\");' href='".sess_url("manage_merchant.php?p=reldel&BankID=".$merchantResult[$i]['BankID'])."'>delete</a>&nbsp;]&nbsp;";
    }
    
		$html .= "</td>";
		$html .= "</tr>\n";
	}
	
	$html .= "</table>\n";
  
  $html.="<br /><a href='merchant_load_balance.php' class='btn'>Manage Merchant Load Balance</a>\n";
  $html.="<br /><a href='rebill_retry_settings.php' class='btn'>Rebill Retry Settings</a>\n";
  $html.="<br /><a href='pre_authorization_settings.php' class='btn'>Pre-Authorization Config</a>\n";
  
	$html .= "</div>\n";
	return $html;
}
?>