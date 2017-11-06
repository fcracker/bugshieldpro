<?php 
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

$con = connect_database();
/*
 * create content blocks
 * page is built in this part
 */
if(!isset($_POST['protectType']) && isset($_GET['protectType'])) $_POST['protectType'] = $_GET['protectType'];
$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Export Users to CSV";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/export.php");
}
if($user->userID != 0 && $user->get_user()){
	$page->blocks['content'] = exportPage($user->check_groups($allowGroups));		
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/export.php");
}
/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function exportPage($is_admin){
	global $cfg;
	global $lang;
	global $user;
	$html = "";
  
  
     //get the countries
  $country = new umCountry();
  $field = new umField();
  $field->fieldID = 8;		//define country filed ID 
  $field->get_field_options();
  
  $country_select = "<select data-placeholder='Choose a country' name='countries[]' multiple id='country_select'>";
  for($j = 0; $j < count($field->fieldOptions); $j++) {
               $sel = "";               
              $country_select.='<option value="' . $field->fieldOptions[$j]->defaultCaption. '" '.$sel.'>'.htmlspecialchars($field->fieldOptions[$j]->caption). '</option>';
   }
    $country_select.= "</select>";
    
    
  $html.= '<link rel="stylesheet" href="'.$cfg['site']['folder'].'js/chosen/chosen.css" />';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.4.2.min.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/chosen/chosen.jquery.js"></script>';
  $html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/pssc_export.js"></script>';  
	
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Export Users to CSV";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	$html .= "<br>";
	
	// page navigation
	$html .= "<p align='center'>\n";
	$html .= "
		<form action=\"".sess_url("exportcsv.php")."\" method=\"POST\" target=\"_blank\">
			<table>
				<tr>
					<td>From Create Date</td>
					<td>&nbsp;&nbsp;&nbsp;:&nbsp;
						<input type=\"text\" size=\"15\" name=\"fromCreateDate\" value=\"".(date("Y-m-d"))."\" readonly id=\"fcDate\">
						<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"fcDateTrigger\" align=\"absmiddle\"></a>
					</td>
				</tr>
				<tr>
					<td>To Create Date</td>
					<td>&nbsp;&nbsp;&nbsp;:&nbsp;
						<input type=\"text\" size=\"15\" name=\"toCreateDate\" value=\"".(date("Y-m-d"))."\" readonly id=\"tcDate\">
						<a href=\"#\"><img src=\"".$cfg['site']['folder']."images/calendar.gif\" border=\"0\" id=\"tcDateTrigger\" align=\"absmiddle\"></a>
					</td>
				</tr>";
	if($is_admin){
		$html .= "
				<tr>
					<td>Data Format </td>
					<td>&nbsp;&nbsp;&nbsp;:&nbsp;<input type=\"checkbox\" name=\"txt_encrypt\" id=\"txt_encrypt\" value=\"yes\" /><label for=\"txt_encrypt\">Encrypt</label></td>
				</tr>";
	}else{
		$html .= "<tr style=\"display:none\"><td colspan=\"2\"><input type=\"text\" name=\"txt_encrypt\" value=\"no\" /></td></tr>";
	}
  
  $html .= "
				<tr>
					<td>Specific Countries </td>
					<td>".$country_select."</td>
          </tr>";
  
	$html .= "
				
				
				<tr>
					<td>Exclude Users with no CC</td>
					<td>&nbsp;&nbsp;&nbsp;:&nbsp;<input type=\"checkbox\" name=\"exclude\" id=\"exclude\" value=\"yes\" checked=\"true\"/><label for=\"exclude\">Exclude</label></td>
				</tr>
				
				<tr>
					<td></td>
					<td align='right'><input type=\"submit\" name=\"submitBtn\" value=\"Submit\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></td>
				</tr>
			</table>
		</form>
	";
	$html .= "</p>\n";
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