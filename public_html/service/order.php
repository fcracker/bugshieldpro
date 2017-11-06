<?php

/*
 * init web page
 */
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/PayBackEnd.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../templates/" . $cfg['language'] . "/default.html"; // load template

include_once("../lib/user.class.php");

include_once("../lib/rebill_cycle.class.php");
include_once("../lib/order.class.php");

$con = connect_database();

if (!isset($_POST['pageSize']))
    $_POST['pageSize'] = 10; // set default page size
if (!isset($_POST['orderBy']))
    $_POST['orderBy'] = "UserID DESC"; // set default order
// allow input groupID with get method
if (!isset($_POST['groupID']) && isset($_GET['groupID']))
    $_POST['groupID'] = $_GET['groupID'];

$user = new umUser();

$user->get_session();

$allowGroups = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0) {
    $page->blocks['title'] = "Orders Information";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "service/order.php");
}

if ($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)) {
    $tempUser = new umUser();
    if (isset($_POST['ajax'])) {
//		$tempUser->userID = $_POST['userID'];
//		$tempUser->shipped = $_POST['shipped'];
//		$tempUser->update_memo_user("ship");
        return;
    }
    $resultMessage = "";
    if (isset($_POST['operation']) && is_numeric($_POST['operation'])) {
        $tempUser->userID = $_POST['operation'];
        $tempUser->memo = $_POST['changeMemo'];
        $tempUser->shippedFrom = $_POST['txt_ship_from'];
        $tempUser->shippedTo = $_POST['txt_ship_to'];
        $tempUser->update_memo_user();
    }
    if (isset($_POST['tran_id']) && $_POST['tran_id'] != "") {
        require_once '../lib/PayFrontEnd.php';
        $payObj = new PayFrontEnd();
        $ret_ary = $payObj->doRefund($_POST['tran_id']);
    }
    $query = $_POST;

    $query['is_order'] = true;

    $searchResult = $tempUser->search_orders($query);

    $page->blocks['content'] = list_users($searchResult, $resultMessage);
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "service/order.php");
}

/*
 * construct and print page
 */
$page->construct_page();  // construct html page
$page->output_page();   // output page

close_database($con);

function list_users($searchResult, $resultMessage = "") {
    global $cfg;
    global $lang;
    global $menuActiveIndex;
    global $user;
    // load all groups
    $result = new umResult();
    $tempGroup = new umGroup();
    $result = $tempGroup->search_groups(NULL);

    $pay = new PayBackEnd();

    $html = "";
//	$html .= require_once("styles.php");
    // javascript
    $html .= '
	<link rel="stylesheet" type="text/css" href="' . $cfg['site']['folder'] . 'styles/jquery-ui-1.8.8.css"  />
	<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-ui.min-1.8.8.js"></script>
  <script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/service-order.js"></script>
	<script language="javascript">
		var tmpMemoHtml, tmpMemo, tmpShippedHtml, tmpShipped, tmpEditHtml, tmpEdit;
		function nextPage(page){
			document.mainForm.page.value = page + 1;
			document.mainForm.submit();
		}
		function prevPage(page){
			document.mainForm.page.value = page - 1;
			document.mainForm.submit();
		}
		function changePageSize(){
			document.mainForm.submit();
		}
		function sort(orderBy){
			document.mainForm.orderBy.value = orderBy;
			document.mainForm.submit();
		}
		function searchRecords(){
			document.mainForm.submit();
		}
		function nl2br(s){
			return s.replace(/\\n/g,"<BR>");
		}
		function br2nl(s){
			return s.replace(/<BR>/g,"\\n");
		}
		
		function memoEdit(row, Id){
			var memo = $(".memo");
			if (typeof(tmpMemo) != "undefined"){
				tmpMemo.innerHTML = nl2br(tmpMemoHtml);
				tmpShipped.innerHTML = nl2br(tmpShippedHtml);
				tmpEdit.innerHTML = tmpEditHtml;
			}
			
			tmpMemoHtml = br2nl(memo[row].innerHTML);
			tmpMemo = memo[row];
			
			var htmlstr = memo[row].innerHTML;
			
			memo[row].innerHTML = \'<textarea id="changeMemo" name="changeMemo" style="width:95%" rows="3">\' + htmlstr + \'</textarea>\';
			
			var shipped = $(".shipped");
			tmpShippedHtml = shipped[row].innerHTML;
			
			var shippedDate = tmpShippedHtml.replace(/(<br\/?\s*>)|(&nbsp;)/g, "").split("~");

			shipped[row].innerHTML = \'<div style="width: 45px; float: left; text-align:right;">' . $lang['field']['from'] . '</div><input type="text" size="12" readonly id="txt_ship_from" name="txt_ship_from">&nbsp;<a><img src="' . $cfg['site']['folder'] . 'images/calendar.gif" border="0" id="tfsDateTrigger" align="absmiddle"></a><br><div style="width: 45px; float: left;text-align:right">' . $lang['field']['to'] . '</div><input type="text" size="12" readonly id="txt_ship_to" name="txt_ship_to">&nbsp;<a><img src="' . $cfg['site']['folder'] . 'images/calendar.gif" border="0" id="ttsDateTrigger" align="absmiddle"></a>\';
			
			$("#txt_ship_from").val(shippedDate[0]);
			$("#txt_ship_to").val(shippedDate[1]);
			
			//Shipped Date Input  
			Calendar.setup(
				{
					inputField: "txt_ship_from",
					ifFormat: "%Y-%m-%d",
					showsTime: false,
					button: "tfsDateTrigger"
				}
			);
			Calendar.setup(
				{
					inputField: "txt_ship_to",
					ifFormat: "%Y-%m-%d",
					showsTime: false,
					button: "ttsDateTrigger"
				}
			);
			
			var medit = $(".memoEdit").eq(row).get(0);
			tmpEditHtml = medit.innerHTML;
  			tmpEdit = medit;
			medit.innerHTML = \'[<a href="javascript:submitListForm(&quot;\' + Id + \'&quot;)">Ok</a>]<br/>[<a href="javascript:discardOperation();">Cancel</a>]\';
			$("#changeMemo").attr("source", htmlstr).focus().select();
		 }
				 
		function submitListForm(operation) {
			document.mainForm.operation.value = operation;
			document.mainForm.submit();
		}
		function discardOperation() {
			document.mainForm.submit();
		}
		
		function cancelSearch() {
			$(".searchRow").find(":text").val("");
			document.mainForm.submit();
		}
		
		function goRefund(trans_id){
                    senddata = "method=check_schedule&trans_id="+trans_id;
                    refundable_amount=0;
                    $.ajax({
                        type:   "POST",
                        url:    "refundbyadmin.php",
                        dataType: "json",
                        data:   senddata,
                        success: function(json){
                            refundable_amount = json.refundable_amount;
                            if(refundable_amount != 0){
                                if(typeof(json.recurring)!="undefined"){
                                    msg = "<p>This transaction is on recurring refund</p>";
                                    msg += "<p>Cycle: "+json.cycle+json.period+"(s)</p>";
                                    msg += "<p>recurring amount: $"+json.amount+"</p>";
                                    $("#dialog_charge").dialog("open");
                                    $("#dialog_charge").attr("innerHTML",msg);
                                }else{
                                    gRefundable_amount = refundable_amount;
                                    gTrans_id = trans_id;
                                    $("#span_refundable_amount").html(refundable_amount);
                                    $("#dialog_refund").dialog("open");
                                }
                            }else{
                                msg = "<p>The total amount of this transaction is refunded already.</p>";
                                $("#dialog_charge").dialog("open");
                                $("#dialog_charge").attr("innerHTML",msg);
                            }
                        }
                    });
		}
                function dorefund(){
                    senddata = "trans_id="+gTrans_id+"&";
                    if($("input:radio:checked").attr("rel") == 0){
                        senddata += "method=full";
                    }else if($("input:radio:checked").attr("rel") == 1){
                        if($("input[name=refund_amount]").val() == ""){alert("Insert amouont value");return;}
                        senddata += "method=part&amount="+$("input[name=refund_amount]").val();
                    }else{
                        if($("input[name=ref_sche_amount]").val() == ""){alert("Insert amount value");return;}
                        if($("input[name=ref_sche_cycle]").val() == ""){alert("Insert cycle value");return;}
                        senddata += "method=recurring&amount="+$("input[name=ref_sche_amount]").val();
                        senddata += "&refund_cycle="+$("input[name=ref_sche_cycle]").val();
                        senddata += "&refund_period="+$("select[name=ref_sche_period]").val();
                    }
                    $.ajax({
                        type:   "POST",
                        url:    "refundbyadmin.php",
                        data:   senddata,
                        success: function(msg){
                            $("#dialog_refund").attr("innerHTML",msg);
                        }
                    });
                }

                function viewRefund(trans_id){
                    senddata = "method=view_history&trans_id="+trans_id;
                    $.ajax({
                        type:   "POST",
                        url:    "refundbyadmin.php",
                        data:   senddata,
                        success: function(msg){
                            $("#dialog_charge").dialog("open");
                            $("#dialog_charge").attr("innerHTML",msg);
                        }
                    });
                }

                function chargebyadmin(user_id){
                    var msg = "";
                    msg += "<center><br/>";
                    msg += "<label>Charge Amount: </label>";
                    msg += "<input id=\"charg_amount\" />";
                    msg += "<input id=\"chargeduserid\" value=\""+user_id+"\" style=\"display:none\" />";
                    msg += "<input type=\"button\" id=\"charg_button\" onclick=\"docharge()\" value=\"Go Charge\" />";
                    msg += "<img id=\"waitgif\" src=\"' . $cfg['site']['folder'] . 'images/wait.gif\" style=\"display:none\"/>";
                    msg += "<br/><br/><div class=\"notice_board\" style=\"display:none\"></div>";
                    msg += "</center>";
                    jQuery("#dialog_charge").dialog("open");
                    jQuery("#dialog_charge").attr("innerHTML",msg);
                }
                var reload_flag = false;
                var trans_id = "";
                var gRefundable_amount = "";
                function docharge(){
                    if($("#charg_amount").val() == "" || $("#charg_amount").val() <= 0 ){
                        alert("please enter the amount");
                        return false;
                    }
                    $("#waitgif").fadeIn("fast");
                    senddata = "user_id="+ $("#chargeduserid").val() + "&charge_amount=" + $("#charg_amount").val();
                    $.ajax({
                        type:   "POST",
                        url:    "chargebyadmin.php",
                        data:   senddata,
                        success: function(msg){
                            //console.log(msg);
                            if(msg != "OK"){
                                $(".notice_board").html(msg);
                                reload_flag = true;
                            }else{
                                msg = "Payment successed!";
                                $(".notice_board").html(msg);
                            }
                            $("#waitgif").fadeOut("fast",function(){
                                $(".notice_board").fadeIn("fast");
                            });
                        }
                    });
                }
		function show_charge_detail(weight, total){
			var msg = "";
			msg += "<table>";
			msg += "<tr><td><b>Weight</b></td><td>: "+weight+"</td></tr>";
			msg += "<tr><td><b>Total</b></td><td>: "+total+"</td></tr>";
			msg += "</table>";
			
			jQuery("#dialog_charge").dialog("open");
                        jQuery("#dialog_charge").attr("innerHTML",msg);
		}
    
    function return_country(iso,el){
      jQuery.get("../ajax_iso_countries.php?code="+iso,
      function(data){jQuery("#country"+el).text(data);});
      return false;
    }
		
		$(document).ready(function() {
			$("#dialog_charge").dialog({
                            modal:true,
                            autoOpen:false,
                            resizable:false,
                            hide:"hide",
                            width:400,
                            height:200,
                            buttons: {
                                "Close": function() {
                                    jQuery(this).dialog("close");
                                    if(reload_flag)location.reload();
                                }
                            }
                        });
			$("#dialog_refund").dialog({
                            modal:true,
                            autoOpen:false,
                            resizable:false,
                            hide:"hide",
                            width:400,
                            height:300,
                            buttons: {
                                "Refund": function() {
                                    dorefund();
                                },
                                "Close": function() {
                                    jQuery(this).dialog("close");
                                    if(reload_flag)location.reload();
                                }
                            }
                        }).find("input:radio").click(function() {
                           var rel = $(this).attr("rel");
                           $("input[rel], select[rel]").not(":radio").attr("disabled", true);
                           $("[rel=" + rel + "]").not(":radio").attr("disabled", false);
                        }).eq(0).click();
		
                /**New Added**/
                $("#dialog_fraud").dialog({
                    modal: true,
                    autoOpen: false,
                    resizable: false,
                    hide: "hide",
                    width: 800,
                    height: 600,
                    buttons: {
                        "Save": function() {
                            saveNote();
                        },
                        "Close": function () {
                            jQuery(this).dialog("close");
                        }
                    }
                });

                $(".note_info").each(function () {
                        $(this).click(function (evt) {
                            evt.preventDefault();
                            var oid = $(this).attr("rel");
                            var uid = $(this).attr("data-uid");
                            var me = $(this);
                            $.post(
                                    "../reports/ajax_handler.php",
                                    {action: "note_info", order: oid, uid: uid},
                                    function (data) {
                                        if (data.status == "OK") {
                                            $("#dialog_fraud")
                                                    .html("<textarea id=\"note_info_box\" style=\"width: 99%;height: 99%;\">"+data.html + "</textarea>")
                                                    .dialog("option", "title", "Note Info for order #" + uid)
                                                    .dialog("open");
                                            $("#dialog_fraud").data("uid",uid);
                                        } else {
                                            alert(data.error);
                                        }
                                    },
                                    "json"
                                    );
                        });
                    });                
                
                function saveNote(){
                    var uid = $("#dialog_fraud").data("uid");
                    var note = $("#note_info_box").val().trim();
                    $.post(
                        "editnotes.php",
                        {user_id:uid,notes:note},
                        function(data) {
                            var td = $("a.note_info[rel="+uid+"]").parents("td");
                            var span = $(td).find("span.note_content")
                            var a = $("a.note_info[rel="+uid+"]");
                            if(!note){
                                $(span).html("");
                                $(a).html("[add]");
                            }else{
                               if(note.length>41){
                                note = note.substr(0,41) + "...";
                               }
                               $(span).html(note);
                               $(a).html(" more");
                            }
                            jQuery($("#dialog_fraud")).dialog("close");
                        }
                    );
                }
                
                $("#dialog_bank").dialog({
                    modal: true,
                    autoOpen: false,
                    resizable: false,
                    hide: "hide",
                    width: 800,
                    height: 600,
                    buttons: {
                        "Close": function () {
                            jQuery(this).dialog("close");
                        }
                    }
                });
                
                $(".bank_info").each(function () {
                        $(this).click(function (evt) {
                            evt.preventDefault();
                            var oid = $(this).attr("rel");
                            var uid = $(this).attr("data-uid");
                            var me = $(this);
                            $.post(
                                    "../reports/ajax_handler.php",
                                    {action: "bank_info", order: oid, uid: uid},
                                    function (data) {
                                        if (data.status == "OK") {
                                            $("#dialog_bank")
                                                    .html(data.html)
                                                    .dialog("option", "title", "Bank Info for order #" + uid)
                                                    .dialog("open");

                                        } else {
                                            alert(data.error);
                                        }
                                    },
                                    "json"
                                    );
                        });
                    });
                
                $(".flag_fraudulent").each(function () {
                    $(this).click(function (evt) {
                        evt.preventDefault();
                        var oid = $(this).attr("rel");
                        var uid = $(this).attr("data-uid");
                        var flagFraudulent =+ $(this).attr("data-fraudulent");
                        var newFlag = (flagFraudulent+1) % 2;
                        var trElement = $(this).parents("table").find("tr[data-uid="+uid+"]");
                        var aElement = $(this);            
                        $.post(
                                "../reports/ajax_handler.php",
                                {action: "flag_as_fraudulent",uid: uid, fraudulent_flag:newFlag},
                                function (data) {
                                    if (data.status == "OK") {
                                        if(newFlag == 1){
                                            $(trElement).addClass("yellow-flag");
                                            $(aElement).html("Unflag Fraudulent");
                                            $(aElement).attr("data-fraudulent", newFlag);
                                        }else{
                                            $(trElement).removeClass("yellow-flag");
                                            $(aElement).html("Flag as Fraudulent");
                                            $(aElement).attr("data-fraudulent", newFlag);
                                        }
                                    } else {
                                        alert(data.error);
                                    }
                                },
                                "json"
                                );
                    });
                });
						
		
    
    $(".rem_rebill").each(function(){
    
      $(this).click(function(evt){
      
        evt.preventDefault();
        
        var thislink = $(this);
        
        var rebillid = thislink.attr("rel");
        
        if(confirm("Are you sure you want to cancel this rebill ?")) {
          $.post(
            "ajax_rebill.php",
            {action:"cancel",rid:rebillid},
            function(data) {
              if(data=="Rebill removed") {
                thislink.remove();
              }
              alert(data);
            }
          );
        }
      
      });
    
    });
		
						
		});
	</script>';
    $html .="<style>";
    $html .="label{float:left;width:100px;vertical-align:bottom}";
    $html .=".radio{vertical-align:bottom}";
    $html .="input,select{vertical-align:middle}";
    $html .="</style>";

    if ($resultMessage != "") {
        $html .= "<div class=\"resultDiv\">\n";
        $html .= "<img src=\"" . $cfg['site']['folder'] . "images/incoming.gif\" align=\"absmiddle\"> \n";
        $html .= $resultMessage;
        $html .= "</div>\n";
    }
    $html .="<div id=\"dialog_charge\" title=\"Dialog Box\"></div>";
    $html .="<div id=\"dialog_actions\" title=\"Actions\"></div>";
    $html .="<div id=\"dialog_refund\" title=\"Refund Dialog Box\" style=\"display:none\">";
    $html .="<p>Note : refund amount should be equal or small than $<span id=\"span_refundable_amount\"></span>";
    $html .="<p><label><input type=\"radio\" class=\"radio\" rel=\"0\" name=\"refund_method\"/>Full</label></p><br/>";
    $html .="<p><label><input type=\"radio\" class=\"radio\" rel=\"1\" name=\"refund_method\"/>Part</label>";
    $html .="amount:<input name=\"refund_amount\" rel=\"1\" style=\"width:70px\" /></p>";
    $html .="<p><label><input type=\"radio\" class=\"radio\" rel=\"2\" name=\"refund_method\"/>Recurring</label>";
    $html .="amount:<input name=\"ref_sche_amount\" rel=\"2\" style=\"width:70px\" />";
    $html .="&nbsp;&nbsp;&nbsp;<input name=\"ref_sche_cycle\" rel=\"2\" style=\"width:30px\" value=\"1\" />&nbsp;";
    $html .="<select name=\"ref_sche_period\" rel=\"2\">";
    $html .="<option value=\"day\">day</option>";
    $html .="<option value=\"week\">week</option>";
    $html .="<option value=\"month\">month</option>";
    $html .="</select></p><br/>";
    $html .="</div>";
    $html .="<div class='result'></div>";
    // title
    $html .= "<div class=\"listContent\">\n";
    $html .= "	<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $html .= "		<tr>\n";
    $html .= "			<td class=\"titleCell\">\n";
    $html .= "				Orders Information (Registerd Users)";
    $html .= "			</td>\n";
    $html .= "		</tr>\n";
    $html .= "	</table>\n";

    // page navigation
    $html .= "<p>\n";
    $html .= "<form action=\"" . sess_url($cfg['site']['folder'] . "service/order.php") . "\" method=\"post\" name=\"mainForm\">\n";

    $html .= "<input type=\"hidden\" name=\"orderBy\" value=\"" . $searchResult->orderBy . "\">\n";
    $html .= "<input type=\"hidden\" name=\"operation\" value=\"-\">\n";
    $html .= "<input type=\"hidden\" name=\"actionGroupID\" value=\"\">\n";

    $html .= "<input type=\"hidden\" id=\"tran_id\" name=\"tran_id\" value=\"\">\n";

    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">\n";
    $html .= "	<tr>\n";
    $html .= "		<td class=\"pageNav\">\n";

    $pageBlock = "\n";
    if ($searchResult->page == 1) {
        $pageBlock .= "<img src=\"" . $cfg['site']['folder'] . "images/pager_arrow_left_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
    } else {
        $pageBlock .= "<a onClick=\"javascript: prevPage(" . $searchResult->page . ")\"><img src=\"" . $cfg['site']['folder'] . "images/pager_arrow_left.gif\" align=\"absmiddle\" border=\"0\"></a> \n";
    }
    $pageBlock .= "<input type=\"text\" name=\"page\" value=\"" . $searchResult->page . "\" size=\"3\"> \n";
    if ($searchResult->page == $searchResult->totalPages) {
        $pageBlock .= "<img src=\"" . $cfg['site']['folder'] . "images/pager_arrow_right_off.gif\" align=\"absmiddle\" border=\"0\"> \n";
    } else {
        $pageBlock .= "<a href=\"javascript: void nextPage(" . $searchResult->page . ")\"><img src=\"" . $cfg['site']['folder'] . "images/pager_arrow_right.gif\" align=\"absmiddle\" border=\"0\"></a> \n";
    }

    $pageSizeBlock = "\n";
    $pageSizeBlock .= "<select name=\"pageSize\" onChange=\"changePageSize()\">\n";
    $optionSize = array(10, 20, 50, 100);
    for ($i = 0; $i < count($optionSize); $i++) {
        if ($optionSize[$i] == $searchResult->pageSize) {
            $pageSizeBlock .= "<option value=\"" . $optionSize[$i] . "\" selected>" . $optionSize[$i] . "</option>\n";
        } else {
            $pageSizeBlock .= "<option value=\"" . $optionSize[$i] . "\">" . $optionSize[$i] . "</option>\n";
        }
    }
    $pageSizeBlock .= "</select>\n";

    $html .= sprintf($lang['text']['pageNavigation'], $pageBlock, $searchResult->totalPages, $pageSizeBlock, $searchResult->total);

    $html .= "</td>\n";
    $html .= "</tr>\n";
    $html .= "</table>\n";
    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\" id=\"listTable\" name=\"listTable\">\n";
    // caption and sort
    $html .= '<thead>';
    $html .= "	<tr class=\"captionRow\">\n";
    $html .= "		<td width=\"2%\">No</td>\n";

    $html .= "		<td width=\"5%\">Rebills</td>\n";

    $html .= "		<td width=\"3%\">Affiliate</td>\n";

    $html .= "		<td width=\"4%\">\n";
    $html .= "			Order<br/>Number \n";
    $html .= "			<a href=\"javascript: void sort('u.userID ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.userID DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"6%\">\n";
    $html .= "			Pay<br/>Date\n";
    $html .= "			<a href=\"javascript: void sort('mh.hDate ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('mh.hDate DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";
    $html .= "		<td width=\"6%\">\n";
    $html .= "			Amount \n";
    $html .= "			<a href=\"javascript: void sort('mh.hAmount ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('mh.hAmount DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";
    /*
      $html .= "		<td width=\"5%\">\n";
      $html .= "			Refund \n";
      $html .= "			<a href=\"javascript: void sort('mh.refunded_date ASC')\"><img src=\"".$cfg['site']['folder'] ."images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['asc']."\" title=\"".$lang['text']['asc']."\"></a> \n";
      $html .= "			<a href=\"javascript: void sort('mh.refunded_date DESC')\"><img src=\"".$cfg['site']['folder'] ."images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"".$lang['text']['desc']."\" title=\"".$lang['text']['desc']."\"></a>\n";
      $html .= "		</td>\n"; */

    $html .= "		<td width=\"5%\">\n";
    $html .= "			First<br/>Name \n";
    $html .= "			<a href=\"javascript: void sort('u.firstname ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.firstname DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";
    $html .= "		<td width=\"5%\">\n";

    $html .= "			Last<br/>Name \n";
    $html .= "			<a href=\"javascript: void sort('u.lastname ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.lastname DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"5%\">\n";
    $html .= "			Email \n";
    $html .= "			<a href=\"javascript: void sort('u.EmailAddress ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.EmailAddress DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";



    $html .= "		<td width=\"5%\">\n";
    $html .= "			Card<br/>Number \n";
    $html .= "			<a href=\"javascript: void sort('u.cardnumber ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.cardnumber DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";
    $html .= "		<td width=\"5%\">\n";
    $html .= "			Phone # \n";
    $html .= "			<a href=\"javascript: void sort('u.phone ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.phone DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"4%\">\n";
    $html .= "			Country \n";
    $html .= "			<a href=\"javascript: void sort('u.country ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.country DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"4%\">\n";
    $html .= "			State \n";
    $html .= "			<a href=\"javascript: void sort('u.state ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.state DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"4%\">\n";
    $html .= "			City \n";
    $html .= "			<a href=\"javascript: void sort('u.city ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.city DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"6%\">\n";
    $html .= "			Address \n";
    $html .= "			<a href=\"javascript: void sort('u.address ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.address DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";
    $html .= "		<td width=\"6%\">\n";
    $html .= "			Postal<br/>Code \n";
    $html .= "			<a href=\"javascript: void sort('u.postalcode ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.postalcode DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";


    $html .= "		<td width=\"5%\">Notes</td>\n";



    $html .= "		<td width=\"5%\">\n";
    $html .= "			Tracking Number\n";
    $html .= "			<a href=\"javascript: void sort('u.Memo ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.Memo DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";



    $html .= "		<td width=\"5%\">\n";
    $html .= "			Shipped\n";
    $html .= "			<a href=\"javascript: void sort('u.shipped_from ASC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortasc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['asc'] . "\" title=\"" . $lang['text']['asc'] . "\"></a> \n";
    $html .= "			<a href=\"javascript: void sort('u.shipped_from DESC')\"><img src=\"" . $cfg['site']['folder'] . "images/sortdesc.gif\" border=\"0\" align=\"absmiddle\" alt=\"" . $lang['text']['desc'] . "\" title=\"" . $lang['text']['desc'] . "\"></a>\n";
    $html .= "		</td>\n";

    $html .= "		<td width=\"5%\">Bank</td>\n";

    $html .= "		<td width=\"5%\" class=\"last\">" . $lang['text']['action'] . "</td>\n";
    $html .= "          <td></td>\n";
    $html .= "	</tr>\n";

    //search condictions
    $html .= "<tr class=\"searchRow\">\n";
    $html .= "	<td>&nbsp;</td>\n";

    $html .= "	<td>&nbsp;</td>\n";

    //affiliate
    $html .= "	<td>\n";
    $html .= "		<input type=\"text\" name=\"affiliateID\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['affiliateID']) . "\">\n";
    $html .= "	</td>\n";

    //ordernumber
    $html .= "	<td>\n";
    $html .= "		<input type=\"text\" name=\"fromID\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['fromID']) . "\">\n";
    $html .= "		<input type=\"text\" name=\"toID\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['toID']) . "\">\n";
    $html .= "	</td>\n";

    //payment 
    $html .= "	<td>\n";
    $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">" . $lang['field']['from'] . "</div>";
    $html .= "		<input type=\"text\" size=\"12\" name=\"fromPayDate\" value=\"" . htmlspecialchars($searchResult->query['fromPayDate']) . "\" readonly id=\"fpDate\">\n";
//	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fpDateTrigger\" align=\"absmiddle\"></a>";
    $html .= "		<br>\n";
    $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">" . $lang['field']['to'] . "</div>";
    $html .= "		<input type=\"text\" size=\"12\" name=\"toPayDate\" value=\"" . htmlspecialchars($searchResult->query['toPayDate']) . "\" readonly id=\"tpDate\">\n";
//	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tpDateTrigger\" align=\"absmiddle\"></a>";	
    $html .= "	</td>\n";

    $html .= "	<td>\n";
    $html .= "		<select id='pay_amount' name='pay_amount'><option value=''>&nbsp;</option>";
    $amount = explode(",", $cfg['prices']['bronze']);
    for ($i = 0; $i < count($amount); $i++) {
        $html .= " 		<option value='" . $amount[$i] . "' style='background-color:aliceblue;'" . ($searchResult->query['pay_amount'] == $amount[$i] ? " selected" : "") . ">" . $amount[$i] . "</option>";
    }
    $amount = explode(",", $cfg['prices']['gold']);
    for ($i = 0; $i < count($amount); $i++) {
        $html .= " 		<option value='" . $amount[$i] . "' style='background-color:#efefef;'" . ($searchResult->query['pay_amount'] == $amount[$i] ? " selected" : "") . ">" . $amount[$i] . "</option>";
    }
    $html .= "		</select>";
    $html .= "	</td>\n";

    /*
      $html .= "	<td>\n";
      $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">".$lang['field']['from']."</div>";
      $html .= "		<input type=\"text\" size=\"12\" name=\"fromRefundDate\" value=\"".htmlspecialchars($searchResult->query['fromRefundDate'])."\" readonly id=\"frDate\">\n";
      //	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"frDateTrigger\" align=\"absmiddle\"></a>";
      $html .= "		<br>\n";
      $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">".$lang['field']['to']."</div>";
      $html .= "		<input type=\"text\" size=\"12\" name=\"toRefundDate\" value=\"".htmlspecialchars($searchResult->query['toRefundDate'])."\" readonly id=\"trDate\">\n";
      //	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"trDateTrigger\" align=\"absmiddle\"></a>";
      $html .= "	</td>\n";
     */


    // firstname
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"firstName\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['firstName']) . "\"><br>";
    $html .= "	</td>\n";
    //lastname
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"lastName\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['lastName']) . "\"><br>";
    $html .= "	</td>\n";

    //email
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"EmailAddress\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['EmailAddress']) . "\"><br>";
    $html .= "	</td>\n";

    //cardnumber
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"cardNumber\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['cardNumber']) . "\"><br>";
    $html .= "	</td>\n";
    //cardname
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"phone\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['phone']) . "\"><br>";
    $html .= "	</td>\n";

    //address

    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"country\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['country']) . "\"><br>";
    $html .= "	</td>\n";

    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"state\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['state']) . "\"><br>";
    $html .= "	</td>\n";
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"city\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['city']) . "\"><br>";
    $html .= "	</td>\n";
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"address\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['address']) . "\"><br>";
    $html .= "	</td>\n";
    $html .= "	<td>";
    $html .= "		<input type=\"text\" name=\"postalcode\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['postalcode']) . "\"><br>";
    $html .= "	</td>\n";

    //note
    $html .= "<td>&nbsp;</td>\n";

    //memo
    $html .= "	<td>\n";
    $html .= "		<input type=\"text\" name=\"note\" style=\"width: 95%\" value=\"" . htmlspecialchars($searchResult->query['note']) . "\"><br>";
    $html .= "	</td>\n";
    //Shipped
    $html .= "	<td>\n";
    $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">" . $lang['field']['from'] . "</div>";
    $html .= "		<input type=\"text\" size=\"12\" name=\"fromShippedDate\" value=\"" . htmlspecialchars($searchResult->query['fromShippedDate']) . "\" readonly id=\"fsDate\">\n";
//	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fsDateTrigger\" align=\"absmiddle\"></a>";
    $html .= "		<br>\n";
    $html .= "		<div style=\"width: 45px; float: left; text-align:right;\">" . $lang['field']['to'] . "</div>";
    $html .= "		<input type=\"text\" size=\"12\" name=\"toShippedDate\" value=\"" . htmlspecialchars($searchResult->query['toShippedDate']) . "\" readonly id=\"tsDate\">\n";
//	$html .= "		<a><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tsDateTrigger\" align=\"absmiddle\"></a>";
    $html .= "	</td>\n";

    $html .= "<td>&nbsp;</td>\n";

    //Search 
    $html .= "	<td class=\"last\" valign=\"top\">\n";
//	$html .= "		[ <a href=\"javascript: searchRecords();\">Search</a> ]<br/>[ <a href=\"javascript: cancelSearch();\">Cancel</a> ]";
    $html .= "		<input type='submit' value='Search' class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" />
					<br/>
					<input type='button' onclick=\"cancelSearch();\" value=\"Cancel \" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" />";
    $html .= "	</td>\n";
    $html .= "<td>&nbsp;</td>\n";
    $html .= "</tr>\n";
    $html .= "</thead>\n";

    // list data
    $html .= "<tbody >";
    $listCount = count($searchResult->list);

    if ($listCount) {
        for ($i = 0; $i < count($searchResult->list); $i++) {
            $objUser = $searchResult->list[$i]; //echo "<!-- xx: ".$objUser->paymentData." -->\n";
            $pay_data = explode(";", $objUser->paymentData);
            $pssc_data = $objUser->get_pssc_data();
            if (count($pay_data) > 1)
                $rowspan_str = " rowspan='" . count($pay_data) . "'";
            else
                $rowspan_str = "";




            $bank_used_row = $pay->get_last_used_bank($objUser->EmailAddress);
            $bank_used = $bank_used_row != null ? $bank_used_row->BankName : "N/A";
            $bank_used = str_replace("Remote", "Rem.", str_replace(".com", "", $bank_used));

            $is_check = (strlen($objUser->routing_number) && strlen($objUser->account_number) && strlen($objUser->name_on_check));

            if ($is_check) {
                $bank_used.=" (Check)";
            }

            $add_check_class = $is_check ? " is-check" : "";

            $className = $objUser->fraudulent_flag == 1 ? " yellow-flag" : "";

            if ($i % 2 == 0) {
                $tr_string = "<tr class=\"dataRow1{$add_check_class} {$className}\" data-uid=\"{$objUser->userID}\">\n";
            } else {
                $tr_string = "<tr class=\"dataRow2{$add_check_class} $className\" data-uid=\"{$objUser->userID}\">\n";
            }

            $html .= $tr_string;

            $html .= "<td align=\"center\"$rowspan_str>" . (($searchResult->page - 1) * $searchResult->pageSize + $i + 1) . "</td>\n";


            $user_rebills = rebill_cycle::user_rebills($objUser->userID);

            $rebill_color = "black";
            $rebill_string = "NONE";
            if (count($user_rebills)) {
                $rebill_string = "";
                if (count($user_rebills) > 1) {
                    $rebill_color = "red";
                }

                foreach ($user_rebills as $kk => $ur) {

                    if (!in_array($ur->period, array(60, 365))) {
                        $rebill_color = "red";
                    }
                    $rebill_string .= ($kk > 0 ? ' <br /> ' : '') . '$' . $ur->amount . ' / ' . ($ur->period == 365 ? 'year' : $ur->period . ' days');
                }
            }

            $html .= "<td$rowspan_str><span style='color:" . $rebill_color . "'><strong>" . $rebill_string . "</strong></span></td>\n";



            $html .= "<td$rowspan_str>" . (($objUser->hasoffers_aff_id > 0) ? $objUser->hasoffers_aff_id : "N/A") . "</td>\n";



            $html .= "<td$rowspan_str><a class='actions' href='#'>" . $objUser->userID . "</a></td>\n";

            $temp_paydata = payment_data($pay_data[0]);
            $html .= "<td>" . $temp_paydata[1] . "</td>\n";
            $html .= "<td>" . $temp_paydata[2] . "</td>\n";
            //$html .= "<td>".$temp_paydata[3]."</td>\n";


            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->firstname) . "</td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->lastname) . "</td>\n";


            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->EmailAddress) . "</td>\n";

            $html .= "<td$rowspan_str>&nbsp;" . $objUser->cardNumber . "<br />exp: " . date("m-Y", strtotime($objUser->expirationDate)) . "</td>\n";
//			$html .= "<td$rowspan_str>&nbsp;".str_repeat("*",strlen($objUser->cardName))."</td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . $objUser->phone . "</td>\n";

            $html .= "<td$rowspan_str>&nbsp;<a href='#' id='country" . $objUser->userID . "' onclick='return return_country(\"" . $objUser->country . "\"," . $objUser->userID . ")'>" . $objUser->country . "</a></td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->state) . "</td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->city) . "</td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->address) . "</td>\n";
            $html .= "<td$rowspan_str>&nbsp;" . htmlspecialchars($objUser->postalcode) . "</td>\n";

//			$html .= "<td>&nbsp;".date($lang['timeFormat'], strtotime($objUser->createTime))."</td>\n";

            $note = $objUser->notes;
            $html.= "<td $rowspan_str><span class=\"note_content\">" . substr($note, 0, 41) . (strlen($note) > 41 ? "..." : "") . (strlen($note) > 0 ? "</span><a class='note_info' rel='" . $objUser->userID . "' data-uid='" . $objUser->userID . "' href='#'> more</a>" : "</span><a class='note_info' rel='" . $objUser->userID . "' data-uid='" . $objUser->userID . "' href='#'>[add]</a>") . "</td>";

            //$html .= "<td$rowspan_str><div class=\"notes\"><span id='notes_" . $objUser->userID . "'>" . $objUser->notes . "</span> <br /><a href='#' class='edit_notes' rel='" . $objUser->userID . "'>[edit]</a> <span style='display:none;'><a href='#' class='save_notes' rel='" . $objUser->userID . "'>[save]</a> | <a href='#' class='cancel_notes' rel='" . $objUser->userID . "'>[cancel]</a></span> </div></td>\n";
            //$html .= "<td$rowspan_str><div class=\"memo\">".$objUser->memo."</div></td>\n";
            $html .= "<td$rowspan_str><div class=\"memo\">" . implode(",<br />", order::get_all_tracking_numbers($objUser->userID)) . "</div></td>\n";


            $shipped_str = "<div class=\"shipped\">";
            if ($objUser->shippedFrom != '0000-00-00')
                $shipped_str .= $objUser->shippedFrom . "<br />";
            $shipped_str .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;~";
            if ($objUser->shippedTo != '0000-00-00')
                $shipped_str .= "<br />" . $objUser->shippedTo;
            $shipped_str .= "</div>";
            $html .= "<td$rowspan_str>" . $shipped_str . "</td>\n";

            //$html .= "<td$rowspan_str>" . $bank_used . "</td>\n";

            $html .= "<td$rowspan_str>$bank_used<a class='bank_info' rel='" . $objUser->userID . "' data-uid='" . $objUser->userID . "' href='#'> [bank history]</a></td>";

            $html .= "<td $rowspan_str>";
            $html .= "<span class=\"memoEdit\" style='vertical-align:center'>[ <a href=\"javascript: void memoEdit($i, {$objUser->userID})\">" . $lang['text']['edit'] . "</a> ]</span>";
            $html .= "&nbsp;&nbsp;<a style='vertical-align:center'><img src=\"" . $cfg['site']['folder'] . "images/charge_detail.gif\" border=\"0\" align=\"absmiddle\" onclick=\"show_charge_detail('" . $pssc_data['pssc_weight'] . "','" . $pssc_data['pssc_total'] . "')\"></a>";
            //$html .= "<br/>[ <a href=\"javascript: void chargebyadmin('{$objUser->userID}')\" >Charge</a> ]\n";
            // echo "<!-- rebills: ".print_r($user_rebills,1)." -->\n";

            if (count($user_rebills)) {
                foreach ($user_rebills as $ur) {
                    $html .= "<br/>[ <a title='" . $ur->description . "' href=\"#\" class='rem_rebill' rel='" . $ur->id . "' >Remove " . ($ur->period == 365 ? " yearly" : $ur->period . " days") . " rebill($" . $ur->amount . ")</a> ]\n";
                }
            }

            $html .= "\n</td>\n";

            $html .= "<td class=\"last\" $rowspan_str><a class='flag_fraudulent' rel='" . $objUser->userID . "' data-uid='" . $objUser->userID . "' data-fraudulent='" . $objUser->fraudulent_flag . "' href='#'>" . ($objUser->fraudulent_flag == 0 ? "Flag as Fraudulent" : "Unflag Fraudulent") . "</a></td>";

            $html .= "</tr>\n";

            for ($j = 1; $j < count($pay_data); $j++) {
                $temp_paydata = payment_data($pay_data[$j]);
                $html .= $tr_string;
                $html .= "<td>" . $temp_paydata[1] . "</td>\n";
                $html .= "<td>" . $temp_paydata[2] . "</td>\n";
                //$html .= "<td>".$temp_paydata[3]."</td>\n";
                $html .= "</tr>\n";
            }
        }
    } else {
        $html .= '<tr><td align="center" style="color: red; font-weight: bold;"  colspan="12">No Result</td></tr>';
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";

    $html .= "</form>";
    ////
    //Order Date
    $html .= "<script type=\"text/javascript\">";
    // Pay Date
    $html .= "	Calendar.setup(";
    $html .= "		{";
    $html .= "			inputField: \"fpDate\",";
    $html .= "			ifFormat: \"%Y-%m-%d\",";
    $html .= "			showsTime: false,";
    $html .= "			button: \"fpDate\"";
    $html .= "		}";
    $html .= "	);";
    $html .= "	Calendar.setup(";
    $html .= "		{";
    $html .= "			inputField: \"tpDate\",";
    $html .= "			ifFormat: \"%Y-%m-%d\",";
    $html .= "			showsTime: false,";
    $html .= "			button: \"tpDate\"";
    $html .= "		}";
    $html .= "	);";
    // Refund Date
    /* $html .= "	Calendar.setup(";
      $html .= "		{";
      $html .= "			inputField: \"frDate\",";
      $html .= "			ifFormat: \"%Y-%m-%d\",";
      $html .= "			showsTime: false,";
      $html .= "			button: \"frDate\"";
      $html .= "		}";
      $html .= "	);";
      $html .= "	Calendar.setup(";
      $html .= "		{";
      $html .= "			inputField: \"trDate\",";
      $html .= "			ifFormat: \"%Y-%m-%d\",";
      $html .= "			showsTime: false,";
      $html .= "			button: \"trDate\"";
      $html .= "		}";
      $html .= "	);"; */
    //Shipped Date
    $html .= "	Calendar.setup(";
    $html .= "		{";
    $html .= "			inputField: \"fsDate\",";
    $html .= "			ifFormat: \"%Y-%m-%d\",";
    $html .= "			showsTime: false,";
    $html .= "			button: \"fsDate\"";
    $html .= "		}";
    $html .= "	);";
    $html .= "	Calendar.setup(";
    $html .= "		{";
    $html .= "			inputField: \"tsDate\",";
    $html .= "			ifFormat: \"%Y-%m-%d\",";
    $html .= "			showsTime: false,";
    $html .= "			button: \"tsDate\"";
    $html .= "		}";
    $html .= "	);";


    $html .= "</script>";
    $html .= "</p>\n";
    $html .= "</div>\n";
    $html .="<div id='dialog_fraud' title='Fraud Info'></div>";
    $html .="<div id='dialog_bank' title='Bank Info'></div>";
    return $html;
}

function changeCardNumber($num) {
    if (strlen($num) > 8) {
        $tmp = substr($num, 0, 4);
//		for ($i = 4; $i < strlen($num) - 4; $i++){
        $tmp .= "**";
//		}
        $tmp .= substr($num, strlen($num) - 4);
        return $tmp;
    }
}

function payment_data($payment) {
    global $lang;
    global $cfg;

    $temp_paydata = array_merge(explode(",", $payment), array("", "", ""));

    //add the description, if available
    $transaction_description = "N/A desc."; //default
    if (isset($temp_paydata[3]) && strlen($temp_paydata[3])) {
        $transaction_description = $temp_paydata[3];
    }

    if (isset($temp_paydata[4]) && strlen($temp_paydata[4])) {
        //this is a refund
        $transaction_description = '<span style="color:red;">REFUND</span><br />' . $temp_paydata[4] . (strlen($transaction_description) ? '<br />' : '') . $transaction_description;
    }

    if ($temp_paydata[1] != '' && $temp_paydata[1] != '0000-00-00 00:00:00')
        $temp_paydata[1] = date($lang['timeFormat'], strtotime($temp_paydata[1]));
    else
        $temp_paydata[1] = "&nbsp;";

    if ($temp_paydata[3] != '' && $temp_paydata[3] != '0000-00-00 00:00:00') {
        $temp_paydata[3] = "<br/>[<a href=\"javascript:viewRefund('" . $temp_paydata[0] . "')\">Refund history</a>]";
        //$temp_paydata[3] = "&nbsp;";
    }

    if (trim($temp_paydata[0]) == "") {
        $temp_paydata[3] = "&nbsp;";
    } else {
        /* $temp_paydata[3] = "[<a href=\"javascript:goRefund('".$temp_paydata[0]."')\">Refund Options</a>]" . $temp_paydata[3]; */
        $temp_paydata[3] = "&nbsp;";
    }



	//if description is still N/A try getting description from config
	/*if ($transaction_description == 'N/A desc.') {
		
		if ($temp_paydata[2] == $cfg['laundrykit_price_pick_1'])
			$transaction_description = 'Rebill - ' . $cfg['laundrykit_text_pick_1'];
		
		else if ($temp_paydata[2] == $cfg['laundrykit_price_pick_2'])
			$transaction_description = 'Rebill - ' . $cfg['laundrykit_price_pick_2'];
		
		else if ($temp_paydata[2] == $cfg['laundrykit_price_pick_3'])
			$transaction_description = 'Rebill - ' . $cfg['laundrykit_price_pick_3'];
		
		else if ($temp_paydata[2] == $cfg['luxuriousmattress_price'])
			$transaction_description = 'Rebill - ' . $cfg['luxuriousmattress_text'];
			
		else if ($temp_paydata[2] == $cfg['luxuriousmattress_price'])
			$transaction_description = 'Rebill - ' . $cfg['luxuriousmattress_text'];
			
		else if ($temp_paydata[2] == $cfg['upsell_1_1_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_1_1_description'];
			
		else if ($temp_paydata[2] == $cfg['upsell_1_50_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_1_50_description'];
			
		else if ($temp_paydata[2] == $cfg['upsell_1_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_1_description'];
			
		else if ($temp_paydata[2] == $cfg['upsell_2_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_2_description'];
			
		else if ($temp_paydata[2] == $cfg['upsell_3_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_3_description'];
			
		else if ($temp_paydata[2] == $cfg['upsell_4_price'])
			$transaction_description = 'Rebill - ' . $cfg['upsell_4_description'];
			
	}*/


    $temp_paydata[2] = $transaction_description . "<br/>$" . $temp_paydata[2];

    /*
      $amount = explode(",", $cfg['prices']['bronze']);
      if(in_array($temp_paydata[2], $amount)){
      $temp_paydata[2] = "Bronze<br/>$".$temp_paydata[2];
      }else{
      $amount = explode(",", $cfg['prices']['gold']);
      if(in_array($temp_paydata[2], $amount)){
      $temp_paydata[2] = "Gold<br/>$".$temp_paydata[2];
      }else{
      $temp_paydata[2] = "Other<br/>$".$temp_paydata[2];
      }
      } */
    return $temp_paydata;
}
