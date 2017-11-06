<?php
/*
 * init web page
 */
include_once("../lib/config.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");
include_once("../lib/database.inc.php");
require_once '../lib/PayFrontEnd.php';
$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
$user = new umUser();
$user->get_session();
$allowGroups = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);

if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['manageUsers'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/payment_history.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	if(isset($_POST['refundID']) && $_POST['refundID'] != ""){
        $payObj = new PayFrontEnd();
        $ret_ary = $payObj->doRefund($_POST['refundID']);
    }
    if(!isset($_POST['BankName'])){
		$_POST['order_number'] 		= '';
		$_POST['user_name'] 		= '';
		$_POST['address'] 			= '';
		$_POST['order_number'] 		= '';
		$_POST['card_number'] 		= '';
		$_POST['card_name'] 		= '';
		$_POST['fromCreateDate']	= '';
		$_POST['toCreateDate']	= '';
		$_POST['BankName'] 			= '';
		$_POST['UserEmail'] 		= '';
		$_POST['GatewayType'] 		= '';
		$_POST['ProcessorID'] 		= '';
		$_POST['fromPaymentDate'] 	= '';
		$_POST['toPaymentDate'] 	= '';
		$_POST['Amount'] 			= '';
		$_POST['TransactionID'] 	= '';
		$_POST['mothodtype'] 		= '';
	}

	$sql1 = "SELECT M.BankName, MH.user_email , M.gatewayType, MH.processer_id, MH.hDate, MH.transaction_id, MH.hAmount, MH.mothodtype, MH.refunded_date,
					U.UserID, CONCAT(U.firstname, ' ', U.lastname) AS user_name, U.cardnumber, U.cardname, U.address, U.CreateTime AS order_date
			FROM ".$cfg['database']['prefix']."merchant_history AS MH
				LEFT JOIN ".$cfg['database']['prefix']."merchant AS M ON MH.BankID=M.BankID
				LEFT JOIN ".$cfg['database']['prefix']."user AS U ON MH.user_email=U.EmailAddress
			WHERE 1";
	$sql2 = "SELECT COUNT(*) AS num, MH.hAmount
			FROM ".$cfg['database']['prefix']."merchant_history AS MH
				LEFT JOIN ".$cfg['database']['prefix']."merchant AS M ON MH.BankID=M.BankID
				LEFT JOIN ".$cfg['database']['prefix']."user AS U ON MH.user_email=U.EmailAddress
			WHERE 1 and refundable_amount != 0";
	$sql = "";
	if($_POST['order_number'] != '') 		$sql .= " AND U.UserID='".$_POST['order_number']."'";
	if($_POST['user_name'] != '') 			$sql .= " AND CONCAT(U.firstname, ' ', U.lastname) LIKE '%".$_POST['user_name']."%'";
	if($_POST['address'] != '') 			$sql .= " AND U.address LIKE '%".$_POST['address']."%'";
	if($_POST['card_number'] != '')			$sql .= " AND U.cardnumber LIKE '%".$_POST['card_number']."%'";
	if($_POST['card_name'] != '') 			$sql .= " AND U.cardname LIKE '%".$_POST['card_name']."%'";
	if($_POST['fromCreateDate'] != '')		$sql .= " AND U.CreateTime >='".$_POST['fromCreateDate']." 0:0:0'";
	if($_POST['toCreateDate'] != '') 		$sql .= " AND U.CreateTime <='".$_POST['toCreateDate']." 23:59:59'";
     
	
	if($_POST['BankName'] != '') 			$sql .= " AND M.BankName LIKE '%".$_POST['BankName']."%'";
	if($_POST['UserEmail'] != '') 			$sql .= " AND MH.user_email LIKE '%".$_POST['UserEmail']."%'";
	if($_POST['GatewayType'] != '') 		$sql .= " AND M.gatewayType='".$_POST['GatewayType']."'";
	if($_POST['fromPaymentDate'] != '')		$sql .= " AND MH.hDate >='".$_POST['fromPaymentDate']." 0:0:0'";
	if($_POST['toPaymentDate'] != '') 		$sql .= " AND MH.hDate <='".$_POST['toPaymentDate']." 23:59:59'";
	if($_POST['Amount'] != '') 				$sql .= " AND MH.hAmount ='".$_POST['Amount']."'";
	if($_POST['TransactionID'] != '') 		$sql .= " AND MH.transaction_id LIKE '%".$_POST['TransactionID']."%'";
	if($_POST['mothodtype'] != '') 			$sql .= " AND MH.mothodtype='".$_POST['mothodtype']."'";
	if($sql != "") $flag=true;
	else $flag=false;
	$sql1 .= $sql." ORDER BY MH.hDate DESC";
	$sql2 .= $sql." GROUP BY MH.hAmount ORDER BY MH.hAmount";
	
	$page->blocks['content'] = list_payment_history($sql1, $sql2, $flag);
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/payment_history.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

close_database($con);

/*
* ============================================== page complete here ==============================================
* The following functions construct content for this page
*/

function list_payment_history($sql, $sql1, $is_search){
	global $cfg;
	global $lang;
    global $ret_ary;
    $Cipher = new Cipher();
   
	// load all groups
	$html = "";
	$html .= "
	<script type=\"text/javascript\">
		function searchCancel(){
			document.searchForm.order_number.value = \"\";
			document.searchForm.user_name.value = \"\";
			document.searchForm.address.value = \"\";
			document.searchForm.card_number.value = \"\";
			document.searchForm.card_name.value = \"\";
			document.searchForm.fromCreateDate.value = \"\";
			document.searchForm.toCreateDate.value = \"\";

			document.searchForm.BankName.value = \"\";
			document.searchForm.UserEmail.value = \"\";
			document.searchForm.GatewayType.value = \"\";
			document.searchForm.fromPaymentDate.value = \"\";
			document.searchForm.toPaymentDate.value = \"\";
			document.searchForm.Amount.value = \"\";
			document.searchForm.TransactionID.value = \"\";
			document.searchForm.mothodtype.value = \"\";
			document.searchForm.submit();
		}
        function goRefund(tran_id){
            if(confirm('This is a full refund.\n Are you sure to refund?')){
                document.searchForm.refundID.value = tran_id;
                document.searchForm.submit();
            }
        }
        
        function goPage(page){
        	document.searchForm.page.value = page;
            document.searchForm.submit();
        }
	</script>";
	
	
	$html .= "<form method=\"post\" name=\"searchForm\">\n";
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">Payment History</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	
	
	//page navigation
	if(isset($_POST['page'])){
		$current = (int) $_POST['page'];
		if($current==0) $current = 1;
		
		$pageSize = (int) $_POST['pageSize'];
		if($pageSize==0) $current = 10;
	}else{
		$current = 1;
		$pageSize = 10;
	}
	
	$count_sql = "select COUNT(hKey) as k from merchant_history";
	$count_result = mysql_query($count_sql);
	$count_r = mysql_fetch_assoc($count_result);
	
	$records = $count_r['k']; 
	$total = ceil($records/$pageSize);
	if($current>$total) $current=$total;
	$from = ($current-1)*$pageSize;
	$sql .= " LIMIT ".$from.",".$pageSize;
	
	//sold
	$rows = multi_query_assoc($sql1);
	$Bronze_orders_amount = explode(",", $cfg['prices']['bronze']);
	$Gold_orders_amount = explode(",", $cfg['prices']['gold']);
	$Bronze_orders_sold = '';$Gold_orders_sold='';
	$Bronze_orders_sold_n = 0;$Gold_orders_sold_n=0;
	for($i=0; $i<count($rows); $i++){
		if(in_array($rows[$i]['hAmount'], $Bronze_orders_amount)){
			$Bronze_orders_sold .= ",&nbsp;&nbsp;<strong>".$rows[$i]['hAmount']."$ :</strong>".$rows[$i]['num'];
			$Bronze_orders_sold_n += $rows[$i]['num'];
		}
		if(in_array($rows[$i]['hAmount'], $Gold_orders_amount)){
			$Gold_orders_sold .= ($i==0?"":",&nbsp;&nbsp;")."<strong>".$rows[$i]['hAmount']."$ :</strong>".$rows[$i]['num'];
			$Gold_orders_sold_n += $rows[$i]['num'];
		}
	}
	if($Bronze_orders_sold!="") $Bronze_orders_sold = "<strong>All :</strong>".$Bronze_orders_sold_n.$Bronze_orders_sold;
	if($Gold_orders_sold!="") $Gold_orders_sold = "<strong>All :</strong>".$Gold_orders_sold_n.$Gold_orders_sold;
		
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">\n";
	$html .= "<tr>\n";
	$html .= "<td width=\"25%\"></td>\n
			<td width=\"25%\">
				<fieldset>
					<legend><strong>Bronze Orders Sold</strong></legend>
					".$Bronze_orders_sold."
				</fieldset>
			</td>\n";
	$html .= "<td width=\"25%\">
				<fieldset>
					<legend><strong>Gold Orders Sold</strong></legend>
					".$Gold_orders_sold."
				</fieldset>
			</td>\n
			
			<td width=\"25%\"></td>\n
			";
	$html .= "</tr>\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"pageNav\" colspan=\"3\">\n";
	$pageBlock = "\n";

	if($current == 1){
		$pageBlock .= "<img src=\"".$cfg['site']['folder']."images/pager_arrow_left_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
	}else{
		$pageBlock .= "<a href=\"#\" onClick=\"goPage(".($current-1).")\">
							<img src=\"".$cfg['site']['folder']."images/pager_arrow_left.gif\" align=\"absmiddle\" border=\"0\">
						</a> \n";
	}

	$pageBlock .= "<input type=\"text\" name=\"page\" value=\"".$current."\" size=\"3\"> \n";
	if($current == $total){
		$pageBlock .= "<img src=\"".$cfg['site']['folder']."images/pager_arrow_right_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
	}else{
		$pageBlock .= "<a href=\"#\" onClick=\"goPage(".($current+1).")\">
							<img src=\"".$cfg['site']['folder']."images/pager_arrow_right.gif\" align=\"absmiddle\" border=\"0\">
						</a> \n";
	}

	$pageSizeBlock = "\n";
	$pageSizeBlock .= "<select name=\"pageSize\" onChange=\"goPage(1)\">\n";

	$optionSize = array(10, 20, 50, 100);

	for($i = 0; $i < count($optionSize); $i++){
		if($optionSize[$i] == $pageSize){
			$pageSizeBlock .= "<option value=\"".$optionSize[$i]."\" selected>".$optionSize[$i]."</option>\n";
		}else{
			$pageSizeBlock .= "<option value=\"".$optionSize[$i]."\">".$optionSize[$i]."</option>\n";
		}
	}
	$pageSizeBlock .= "</select>\n";

	$html .= sprintf($lang['text']['pageNavigation'], $pageBlock, $total, $pageSizeBlock, $records);
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"submit\" value=\"Search\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"\">\n";
	if($is_search){
		$html .= "<input type=\"button\" value=\"Cancel\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"searchCancel();\">\n";
	}
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	
	
	
	
	// caption and sort
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "
			<tr class=\"captionRow\">
				<td width=\"3%\" align=\"center\">#</td>
				<td width=\"5%\" align=\"center\">Order<br/>Number</td>
				<td width=\"7%\" align=\"center\">UserName</td>
				<td width=\"10%\" align=\"center\">UserEmail</td>
				<td width=\"8%\" align=\"center\">Address</td>
				<td width=\"7%\" align=\"center\">CardNumber</td>
				<td width=\"5%\" align=\"center\">CardName</td>
				<td width=\"7%\" align=\"center\">Order Date</td>
				
				<td width=\"7%\" align=\"center\">Gateway Name</td>
				<td width=\"5%\" align=\"center\">Gateway Type </td>
				<td width=\"5%\" align=\"center\">Processor ID</td>
				<td width=\"7%\" align=\"center\">Payment Date </td>
				<td width=\"4%\" align=\"center\">Amount</td>
				<td width=\"10%\" align=\"center\">Transaction ID</td>
				<td width=\"4%\" align=\"center\">methodtype</td>
                <td width=\"6%\" align=\"center\">Refunded Date & Action</td>
			</tr>\n";
	
	//search condictions
	$html .= "
			<tr class=\"searchRow\">
				<td>&nbsp;</td>
				<td>
					<input type=\"text\" name=\"order_number\" style=\"width: 95%\" value=\"".$_POST['order_number']."\">
				</td>
				<td>
					<input type=\"text\" name=\"user_name\" style=\"width: 95%\" value=\"".$_POST['user_name']."\">
				</td>
				<td>
					<input type=\"text\" name=\"UserEmail\" style=\"width: 95%\" value=\"".$_POST['UserEmail']."\">
				</td>
				<td>
					<input type=\"text\" name=\"address\" style=\"width: 95%\" value=\"".$_POST['address']."\">
				</td>
				<td>
					<input type=\"text\" name=\"card_number\" style=\"width: 95%\" value=\"".$_POST['card_number']."\">
				</td>
				<td>
					<input type=\"text\" name=\"card_name\" style=\"width: 95%\" value=\"".$_POST['card_name']."\">
				</td>
				<td>
					<div style=\"width: 45px; float: left;\">From:</div>
					<input type=\"text\" size=\"10\" name=\"fromCreateDate\" value=\"".$_POST['fromCreateDate']."\" readonly id=\"fcDate\">
					<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fcDateTrigger\" align=\"absmiddle\"></a>
					<br>
					<div style=\"width: 45px; float: left;\">To:</div>
					<input type=\"text\" size=\"10\" name=\"toCreateDate\" value=\"".$_POST['toCreateDate']."\" readonly id=\"tcDate\">
					<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tcDateTrigger\" align=\"absmiddle\"></a>
				</td>
				
				<td>
					<input type=\"text\" name=\"BankName\" style=\"width: 95%\" value=\"".$_POST['BankName']."\">
				</td>
				
				<td>
					<select name=\"GatewayType\">
						<option value=''>&nbsp;</option>
						<option value='pp' ".($_POST['GatewayType']=='pp'?"selected":"").">pp&nbsp;</option>
						<option value='nmi' ".($_POST['GatewayType']=='nmi'?"selected":"").">nmi&nbsp;</option>
					</select>
				</td>
				<td>
					<input type=\"text\" name=\"ProcessorID\" style=\"width: 95%\" value=\"".$_POST['ProcessorID']."\">
				</td>
				<td>
					<div style=\"width: 45px; float: left;\">From:</div>
					<input type=\"text\" size=\"10\" name=\"fromPaymentDate\" value=\"".$_POST['fromPaymentDate']."\" readonly id=\"fpDate\">
					<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fpDateTrigger\" align=\"absmiddle\"></a>
					<br>
					<div style=\"width: 45px; float: left;\">To:</div>
					<input type=\"text\" size=\"10\" name=\"toPaymentDate\" value=\"".$_POST['toPaymentDate']."\" readonly id=\"tpDate\">
					<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tpDateTrigger\" align=\"absmiddle\"></a>
				</td>
				<td>
					<input type=\"text\" name=\"Amount\" style=\"width: 95%\" value=\"".$_POST['Amount']."\">
				</td>
				<td>
					<input type=\"text\" name=\"TransactionID\" style=\"width: 95%\" value=\"".$_POST['TransactionID']."\">
				</td>
				<td>
					<select name=\"mothodtype\">
						<option value=''>&nbsp;</option>
						<option value='direct' ".($_POST['mothodtype']=='direct'?"selected":"").">direct&nbsp;</option>
						<option value='checkout' ".($_POST['mothodtype']=='checkout'?"selected":"").">checkout&nbsp;</option>
					</select>
				</td>
                <td>
                    <input type=\"hidden\" name=\"refundID\" style=\"width: 95%\" value=\"\"/>
                    <input type=\"hidden\" name=\"refundType\" style=\"width: 95%\" value=\"\"/>
                </td>
			</tr>\n";
    $rows = multi_query_assoc($sql);


	
	for($i = 0; $i < count($rows); $i++){
		$row = $rows[$i];
	
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		
		$html .= "
			<td align=\"center\">".($from+$i+1)."</td>
			<td>".$row['UserID']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['user_name']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".htmlspecialchars($row['user_email'])."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['address']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".
      (is_null($row['cardnumber'])?
      "":
      substr($Cipher->decrypt($row['cardnumber']), 0, 4).str_repeat("*", 2).substr($Cipher->decrypt($row['cardnumber']), -4)
      )."</td>
			<td style='word-break: break-all; word-wrap: break-word;'> ".(is_null($row['cardname'])?"":$Cipher->decrypt($row['cardname']))."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['order_date']."</td>			
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['BankName']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['gatewayType']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['processer_id']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['hDate']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['hAmount']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['transaction_id']."</td>
			<td style='word-break: break-all; word-wrap: break-word;'>".$row['mothodtype']."</td>";
            if(is_null($row['refunded_date'])){
                $html .= "<td><a href='javascript:goRefund(\"". $row['transaction_id'] ."\")'>[ Refund ]</a>";
            }else{
                $html .= "<td>".$row['refunded_date']."</td>";
            }
		$html .= "</tr>\n";
	}
	if($i==0){
		$html .= "<tr><td colspan=\"15\" align=\"center\">No history records.</td></tr>\n";
	}

	$html .= "</table>\n";
	$html .= "</form>\n";
	$html .= "
	<script type=\"text/javascript\">
		Calendar.setup(
			{
			inputField: \"fcDate\",
			ifFormat: \"%Y-%m-%d\",
			showsTime: false,
			button: \"fcDateTrigger\"
			}
		);
		
		Calendar.setup(
			{
				inputField: \"tcDate\",
				ifFormat: \"%Y-%m-%d\",
				showsTime: false,
				button: \"tcDateTrigger\"
			}
		);
		Calendar.setup(
			{
			inputField: \"fpDate\",
			ifFormat: \"%Y-%m-%d\",
			showsTime: false,
			button: \"fpDateTrigger\"
			}
		);
		
		Calendar.setup(
			{
				inputField: \"tpDate\",
				ifFormat: \"%Y-%m-%d\",
				showsTime: false,
				button: \"tpDateTrigger\"
			}
		);
	</script>";
	$html .= "</p>\n";
	$html .= "</div>\n";
    if(isset($ret_ary)){
        if($ret_ary['ACK']=="Success"){
            $html .= "<script>";
            $html .= "alert('Refund Action Success!')";
            $html .= "</script>";
        }else{
            $html .= "<script>";
            $html .= "alert('Refund Action Faild!')";
            $html .= "</script>";
        }
    }
	return $html;
}