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
include_once("../../lib/custom_paths.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];


	$page->blocks['title'] = "Custom Paths";
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


$custom_path = new custom_path($cfg);

$paths = $custom_path->get_paths();




$page->blocks['content'] = paths_page($paths);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function paths_page($paths) {
	
	global $cfg;
	$html = "";
	
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/email_campaigns.js"></script>';

	$html .= "<div class=\"listContent\">\n";
	
		$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
		$html .= "<tr>\n";
		$html .= "<td class=\"titleCell\">Manage Custom Paths</td>\n";
		$html .= "<td align=\"right\">\n";
		$html .= "<input onclick='location.href=\"edit.php\"' type=\"button\" value=\"Add Path\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";		
		$html .= "</td>\n";
		$html .= "</tr>\n";
		$html .= "</table>\n";
		
		$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<tr class=\"captionRow\">\n";
	$html .= "<td width=\"3%\">Path ID</td>";
	$html .= "<td width=\"20%\">Path Name</td>";
	$html .= "<td width=\"10%\">Path Folder</td>";
	$html .= "<td width=\"10%\">Action</td>";
	$html .= "</tr>\n";
  
	foreach($paths as $i=>$path){
	
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}	
		
    
    
		$html .= "<td>".$path["custom_path_id"]."</td>";
		$html .= "<td>".$path["path_name"]."</td>";
		$html .= "<td>".$path["path_folder"]."</td>";		
		$html .= "<td>
			<a class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='edit.php?path_id=".$path["custom_path_id"]."'>[Edit]</a>
			&nbsp;&nbsp;
			<a class=\"btn\" onclick='return confirm(\"Are you sure?\")' onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='delete.php?path_id=".$path["custom_path_id"]."'>[Delete]</a>
			</td>";
   
		$html .= "</tr>\n";
	}
	
	$html .= "</table>\n"; 
	
		
		
	
	$html .= "</div>\n";
	
	
	return $html;
}