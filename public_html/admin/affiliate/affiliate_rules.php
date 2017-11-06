<?php
include_once("../../lib/config.inc.php");
include_once("../../lib/database.inc.php");
include_once("../../lib/page.class.php");
include_once("../../lib/menu.class.php");
include_once("../../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../../languages/".$cfg['language'].".php"); // load language file
$page->template = "../../templates/".$cfg['language']."/default.html"; // load template
include_once("../../lib/user.class.php");
include_once("../../lib/affiliate.class.php");

global $cfg;
$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];


	$page->blocks['title'] = "Affiliate Rules";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();

/*
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = "Email Campaigns";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/email_campaigns.php");
}
*/

$affiliate_id = isset($_GET['aff_id']) ? intval($_GET['aff_id']) : 0;
if($affiliate_id<0) {
	$affiliate_id = 0;
}

$affiliate_class = new affiliate($cfg);

$affiliate_rules = $affiliate_class->get_affiliate_rules($affiliate_id);

$page->blocks['content'] = list_affiliate_rules_page($affiliate_id,$affiliate_rules);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function list_affiliate_rules_page($affiliate_id,$affiliates_rules) {
	
	global $cfg;
	$html = "";
	
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';

	$html .= "<div class=\"listContent\">\n";
	
		$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
		$html .= "<tr>\n";
		$html .= "<td class=\"titleCell\">Manage ".($affiliate_id==0 ? "General ":"")."Rules".($affiliate_id>0 ? " for Affiliate ".$affiliate_id:"")."</td>\n";
		$html .= "<td align=\"right\">\n";
		$html .= "<input onclick='location.href=\"list.php\"' type=\"button\" value=\"Affiliate List\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";	
		$html .= "<input onclick='location.href=\"rule.php?aff_id=".$affiliate_id."\"' type=\"button\" value=\"Add Rule\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";	
		$html .= "</td>\n";
		$html .= "</tr>\n";
		$html .= "</table>\n";
		
		$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"3%\">Rule ID</td>";
	$html .= "<td width=\"3%\">Affiliate ID</td>";;	
	$html .= "<td width=\"10%\">Suppression %</td>";	
	$html .= "<td width=\"10%\">Action</td>";
	$html .= "</tr>\n";
  
	foreach($affiliates_rules as $i=>$rule){
	
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}	
		
    
		$html .= "<td>".$rule["affiliate_rule_id"]."</td>";
		$html .= "<td>".$rule["affiliate_id"]."</td>";
		$html .= "<td>".$rule["suppression_percentage"]."%</td>";			
		$html .= "<td>
			<a class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='rule.php?rule_id=".$rule["affiliate_rule_id"]."'>[Edit]</a>		
				&nbsp;&nbsp;
			<a class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='del_rule.php?rule_id=".$rule["affiliate_rule_id"]."'>[Delete]</a>		
			</td>";   
		$html .= "</tr>\n";
	}
	
	$html .= "</table>\n"; 
		
		
	
	$html .= "</div>\n";
	
	return $html;
	
}