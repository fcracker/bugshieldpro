<?php 
/*
 * init web page
 */
header("Cache-Control:no-cache,must-revalidate");

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.editor.php");
include_once("../lib/menu.class.php");

include_once("../lib/country.class.php");
include_once("../lib/form.class.php");

include_once("../lib/menu.block.php"); // load menu function

$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/".$cfg['language'].".php"); // load language file
$page->template = "../templates/".$cfg['language']."/default.html"; // load template

include_once("../lib/user.class.php");
include_once("../lib/protect.class.php");

define ('PSSC_ONLI', $cfg['pssc']['publishid']);
define ('DEFAULT_CARRIERCODE', 'M03');

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
if(!isset($_POST['protectType']) && isset($_GET['protectType'])) $_POST['protectType'] = $_GET['protectType'];
$user = new umUser();
$user->get_session();

$exclude = isset($_POST['exclude']);

if($exclude) {$set_exclude = true;} else {if(isset($_POST['rangetype'])) {$set_exclude = false;} else {$set_exclude = true;}}

$countries = array();

if(isset($_POST["countries"]) && is_array($_POST["countries"]) && count($_POST["countries"])) {

$countries = $_POST["countries"];

}

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	if(isset($_POST['rangetype'])){
		$sql = "SELECT U.*, P.pssc_trackingnumber FROM 
				".$cfg['database']['prefix']."user AS U  LEFT JOIN
				".$cfg['database']['prefix']."user_psscdata AS P ON U.UserID=P.UserID
				WHERE 1";
		$_POST['fromOrderNumber'] = (int) $_POST['fromOrderNumber'];
		if($_POST['fromOrderNumber']==0) $_POST['fromOrderNumber']="";

		$_POST['toOrderNumber'] = (int) $_POST['toOrderNumber'];
		if($_POST['toOrderNumber']==0) $_POST['toOrderNumber']="";
		
		if($_POST['fromOrderNumber']!='') $sql .= " AND U.`UserID`>=".$_POST['fromOrderNumber'];
		if($_POST['toOrderNumber']!='')	 $sql .= " AND U.`UserID`<=".$_POST['toOrderNumber'];
		if($_POST['fromCreateDate']!='') $sql .= " AND U.`CreateTime`>='".$_POST['fromCreateDate']." 0:0:0'";
		if($_POST['toCreateDate']!='')	 $sql .= " AND U.`CreateTime`<='".$_POST['toCreateDate']." 23:59:59'";
		
		if($exclude) {
			$sql.=" AND LENGTH(U.cardnumber) > 0";
		}
    
    if(count($countries)) {
    
      $cn_list = "";
      foreach($countries as $key=>$cnt) {
        
        $cn_list.="'".$cnt."'";
        
        if($key<(count($countries)-1)){
          $cn_list.=",";
        }

      }
    
      $sql.= " AND U.country IN(".$cn_list.")";
    }
		
		$sql .= " GROUP BY U.UserID";
		$data = multi_query_assoc($sql);
	}else{
		$_POST['fromOrderNumber']="";
		$_POST['toOrderNumber']="";
		$_POST['fromCreateDate']=date('Y-n-j');
		$_POST['toCreateDate']=date('Y-n-j');
		$_POST['sel_pssc_f1']=1;
		$data = false;
	}
	
	
	$page->blocks['title'] = "Export Users to CSV";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/pssc_export.php");
}
if($user->userID != 0 && $user->get_user()){
	$page->blocks['content'] = exportPage($data,$set_exclude);		
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/pssc_export.php");
}
/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function exportPage($data,$set_exclude = true){
	global $cfg;
	global $lang;
	global $user;
  
  $countries = array();


if(isset($_POST["countries"]) && is_array($_POST["countries"]) && count($_POST["countries"])) {
$countries = $_POST["countries"];
}
  
   //get the countries
  $country = new umCountry();
  $field = new umField();
  $field->fieldID = 8;		//define country filed ID 
  $field->get_field_options();
  
  $country_select = "<select data-placeholder='Choose a country' name='countries[]' multiple id='country_select'>";
  for($j = 0; $j < count($field->fieldOptions); $j++) {
               $sel = in_array($field->fieldOptions[$j]->defaultCaption,$countries) ? " selected":"";
               
              $country_select.='<option value="' . $field->fieldOptions[$j]->defaultCaption. '" '.$sel.'>'.htmlspecialchars($field->fieldOptions[$j]->caption). '</option>';
   }
    $country_select.= "</select>";
  
	
	$sql = "SELECT * FROM ".$cfg['database']['prefix']."pssc_master WHERE f2=0 ORDER BY f1";
	$psscs = multi_query_assoc($sql);
	
	$html = "";
  
  $html.= '<link rel="stylesheet" href="'.$cfg['site']['folder'].'js/chosen/chosen.css" />';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.4.2.min.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/chosen/chosen.jquery.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/pssc_export.js"></script>';
	
	$html .= "
		<style type=\"text/css\">
			table td { empty-cells: show; }
			select { font-size: 11px; vertical-align: middle; }
			input { vertical-align: middle; }
			#o_range, #d_range { display: none; }
		</style>
		<script type='text/javascript'>
			function fn_select(flag) {
				var obj = document.getElementsByName('unshipped[]');
				for(var i=0; i<obj.length; i++){
					obj[i].checked = flag;
				}
			}
			
			function fn_disable(elem) {
				var children = elem.getElementsByTagName('input');
				
				for (var i = 0; i < children.length; i++) {
					children[i].value = '';
				}
				
				children[0].focus();
			}
			
			function enableElem(elem) {
				fn_disable(elem, false);
			}
			
			function disableElem(elem) {
				fn_disable(elem, true);
			}
			
			function fn_rangetype_change(rangetype) {
				document.getElementById('rangetype').value = rangetype;
				if (rangetype == '1') {
					document.getElementById('o_range').style.display = 'none';
					document.getElementById('d_range').style.display = 'inline';
					disableElem(document.getElementById('o_range'));
				} else if (rangetype == '2') {
					document.getElementById('o_range').style.display = 'inline';
					document.getElementById('d_range').style.display = 'none';
					disableElem(document.getElementById('d_range'));
				} else {
					document.getElementById('o_range').style.display = 'none';
					document.getElementById('d_range').style.display = 'none';
					disableElem(document.getElementById('o_range'));
					disableElem(document.getElementById('d_range'));
				}
			}
			
			function fn_submit() {
				var rangetype = document.getElementById('rangetype').value;
				if (rangetype == '') {
					alert('Please select a range type.');
				}
				
				document.getElementById('pssc_form').submit();
			}
			
			window.onload = function() {
				fn_rangetype_change('" . (isset($_POST['rangetype']) ? $_POST['rangetype'] : '') . "');
			}
		</script>";
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Export to CSV (PSSC)";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	$html .= "<br>";
	
	// page navigation
	$html .= "<p align='center'>\n";
	$html .= "
		<form method=\"post\" id=\"pssc_form\">
			<table>
				<tr>
					<td width='100'>Select a PSSC:</td>
					<td><select name='sel_pssc_f1'>";
	foreach($psscs as $pssc){
		$html .= "<option value='".$pssc['f1']."'".($_POST['sel_pssc_f1']==$pssc['f1']?" selected":"").">".$pssc['name']."</option>";
	}
	$html .= "</select></td><td></td>
				</tr>
				<tr>
					<td>List by: </td>
					<td width='420'>
						<select id='rangetype' name='rangetype' onchange='fn_rangetype_change(this.value)' name='rangetype'><option value=\"\">Choose&hellip;</option><option value=\"1\">Date Range</option><option value=\"2\">Order Number Range</option></select>
						<span id='o_range'><input type=\"text\" size=\"15\" name=\"fromOrderNumber\" value=\"".$_POST['fromOrderNumber']."\">&nbsp;~&nbsp;
					<input type=\"text\" size=\"15\" name=\"toOrderNumber\" value=\"".$_POST['toOrderNumber']."\"></span>
						<span id='d_range'><input type=\"text\" size=\"15\" name=\"fromCreateDate\" value=\"".$_POST['fromCreateDate']."\" readonly id=\"fcDate\"><a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fcDateTrigger\" align=\"absmiddle\"></a> ~ <input type=\"text\" size=\"15\" name=\"toCreateDate\" value=\"".$_POST['toCreateDate']."\" readonly id=\"tcDate\"><a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tcDateTrigger\" align=\"absmiddle\"></span>					
					</td>
					<td></td>
					</tr>
          
          
          <tr>  
             <td>Select countries to list/export <br />(none means all)</td> 
            <td>
              <div style='display:block;'>
                ".$country_select."
              </div>
            </td>
            <td></td>
          </tr>
          
          
					<tr>
					
					<td>Exclude Users with no CC(set ex: ".$set_exclude.")</td>
					<td>&nbsp;&nbsp;&nbsp;:&nbsp;<input type=\"checkbox\" name=\"exclude\" id=\"exclude\" value=\"yes\" ".($set_exclude==true ? "checked=\"true\"":"")."/><label for=\"exclude\">Exclude</label></td>
					
					<td align='right'><input type='button' onclick='fn_submit();' value=\"List Orders\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" /></td>
				</tr>
			</table>
		</form>
	";
	$html .= "</p>\n";
	if($data === false){
		
	}else{
		if(count($data)){
			$unshipped = "";	$ushippedI = 0;
			$shipped = "";		$shippedI = 0;
			$carriercode = "";
			$sql = "SELECT DISTINCT(carr_code) 
					FROM ".$cfg['database']['prefix']."carriercodes 
					WHERE pssc_id IN (SELECT nor FROM ".$cfg['database']['prefix']."pssc_master WHERE f1='".$_POST['sel_pssc_f1']."')
					ORDER BY carr_code";
			$rows = multi_query_assoc($sql);
			foreach($rows as $row){
				$carriercode .= "
					<option value='".$row['carr_code']."'".($row['carr_code']==DEFAULT_CARRIERCODE?" selected":"").">".$row['carr_code']."</option>";
			}
			foreach($data as $row){
				if(trim($row['pssc_trackingnumber']) != ""){
					$flag=true;
					$rowI = $ushippedI;
					$ushippedI++;
				}else{
					$flag=false;
					$rowI = $shippedI;
					$shippedI++;	
				}
				$tempRow = "
					<tr class='dataRow".($rowI%2+1)."'>
						<td>".( $flag ? 
							"<input type='hidden' name='shipped[]' value='".$row['UserID']."' />" :
							"<input type='checkbox' name='unshipped[]' value='".$row['UserID']."' />")."
						</td>
						<td>".$row['UserID']."</td>
						<td>".$row['firstname']." ".$row['lastname']."</td>
						<td>".$row['address']."</td>
						<td>".$row['city']."</td>
						<td>".($row['state'] ? $row['state'] : 'United States')."</td>
						<td>".$row['postalcode']."</td>
						<td>".$row['country']."</td>
						<td>".$row['Memo']."</td>
						<td>
							<select name='carriedcode[".$row['UserID']."]'>
							".$carriercode."
							</select>
						</td>
					</tr>	
				"; 
				if($flag) $unshipped.= $tempRow; else $shipped.= $tempRow;
				
			}
			if($unshipped == "")	$unshipped = "<tr><td colspan='9'>No Data.</td></tr>";
			if($shipped == "") 		$shipped = "<tr><td colspan='9'>No Data.</td></tr>";
			
			$trCaption = "
						<td style='width:8%;'>Order Number</td>
						<td style='width:12%;'>Order Name</td>
						<td style='width:auto;'>Address</td>
						<td style='width:13%;'>City</td>
						<td style='width:7%;'>State</td>
						<td style='width:7%;'>Postal Code</td>
						<td style='width:8%;'>Country</td>
						<td style='width:15%;'>TrackingNumber</td>
						<td style='width:80px;'>CarrierCode</td>";
			$html .= "
			<p align='center'>
			<form name=\"orders_form\" action=\"".sess_url("pssc_exportcsv.php")."\" method=\"post\" target=\"_blank\">";
			$html .= "
				<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" style=\"margin-top:30px;\" class=\"titleTable\">
					<tr>
						<td class=\"titleCell\">
							UnShipped Orders List
						</td>
						<td align='right'>
							<input type='submit' value='Download .CSV' class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onclick=\"\" />
						</td>
					</tr>
				</table>
				<table style=\"width:100%;\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">
					<tr class=\"captionRow\">
						<td style='width:20px;'>&nbsp;</td>
						".$trCaption."
					</tr>
					".$unshipped."
				</table>";
			
			$html .= "
				<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" style=\"margin-top:30px;\" class=\"titleTable\">
					<tr>
						<td class=\"titleCell\">
							Shipped Orders List
						</td>
					</tr>
				</table>
				<table style=\"width:100%;\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">
					<tr class=\"captionRow\">
						<td style='width:20px'><input name='chk_all' type='checkbox' onclick='fn_select(this.checked)' /></td>
						".$trCaption."
					</tr>
					".$shipped."
				</table>
			</form>
			</p>";
		}else{
			$html .= "<p align='center'>No Data.</p>";
		}
	}
	$html .= "</div>\n";
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
	</script>";
	
	return $html;
	
}
close_database($con);