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
/*
 * create content blocks
 * page is built in this part
 */
$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];
$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if($menuActiveIndex>0){
	$page->blocks['title'] = $lang['title']['backupDatabase'];
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/manage_menu.php");
	
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$resultMessage = "";
	if(isset($_POST['operation'])){
		if($_POST['operation'] == 'create'){
			create_backup();
			$resultMessage = $lang['text']['performActionSuccessfully'];
		}else{
			recover($_POST['filename']);
			$resultMessage = $lang['text']['performActionSuccessfully'];
		}
	}
	$page->blocks['content'] = list_backup_files(get_file_list(), $resultMessage);	
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/backup_database.php");
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

function get_file_list(){
	global $cfg;
	$list = array();
	
	if ($handle = opendir($cfg['database']['backupFolder'])){
		while (false !== ($file = readdir($handle))) {
			if(!is_dir($cfg['database']['backupFolder']."/".$file) && strrpos($file, '.sql')) $list[] = $file;
		}
	}
	rsort($list);
	return $list;
}

function list_backup_files($list, $resultMessage = ""){
	global $cfg;
	global $lang;
	
	$html = "";
	$html .= "<script language=\"javascript\">";
	$html .= "function createBackup(){";
	$html .= "if(confirm('".$lang['text']['confirmCreateBackup']."')){";
	$html .= "document.actionForm.operation.value='create';";
	$html .= "document.actionForm.submit();";
	$html .= "disablePage();";
	$html .= "}";
	$html .= "}";
	$html .= "function recover(fn){";
	$html .= "if(confirm('".$lang['text']['confirmRecovery']."')){";
	$html .= "document.actionForm.operation.value='recover';";
	$html .= "document.actionForm.filename.value=fn;";
	$html .= "document.actionForm.submit();";
	$html .= "disablePage();";
	$html .= "}";
	$html .= "}";
	$html .= "</script>";

	if($resultMessage != ""){
		$html .= "<div class=\"resultDiv\">\n";
		$html .= "<img src=\"".$cfg['site']['folder']."images/incoming.gif\" align=\"absmiddle\"> \n";
		$html .= $resultMessage;
		$html .= "</div>\n";
	}

	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= $lang['title']['backupDatabase'];
	$html .= "</td>\n";
	$html .= "<td align=\"right\">\n";
	$html .= "<input type=\"button\" value=\"".$lang['buttonCaption']['createBackup']."\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" onClick=\"createBackup()\">\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";	

	$html .= "<p>";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
	$html .= "<tr class=\"actionsRow\">\n";
	$html .= "<td width=\"80%\">\n";
	$html .= "&nbsp;\n";
	$html .= "</td>\n";
	$html .= "<td width=\"20%\">\n";
	$html .= "&nbsp;\n";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	for($i = 0; $i < count($list); $i++){
		if($i % 2 == 0){
			$html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
		}else{
			$html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
		}
		$html .= "<td>".$list[$i]."</td>\n";
		$html .= "<td class=\"last\">";
		$html .= "[ <a href=\"#\" onClick=\"recover('".$list[$i]."')\">".$lang['text']['recover']."</a> ]";
		$html .= " ";
		$html .= "[ <a href=\"".sess_url($cfg['site']['folder']."admin/download_backup_file.php?fileName=".$list[$i])."\">".$lang['text']['download']."</a> ]";
		$html .= "</td>\n";
		$html .= "</tr>\n";
	}
	$html .= "</table>\n";
	$html .= "<form name=\"actionForm\" action=\"".sess_url($cfg['site']['folder']."admin/backup_database.php")."\" method=\"post\">";
	$html .= "<input type=\"hidden\" name=\"operation\" value=\"\">";
	$html .= "<input type=\"hidden\" name=\"filename\" value=\"\">";
	$html .= "</form>";
	$html .= "</p>";
	$html .= "</div>\n";
	
	return $html;
}

function create_backup(){
	global $cfg;
	$bindir = "";
	$result = mysql_query("show variables");
	while($row = mysql_fetch_row($result)) 
		if($row[0] == "basedir")
			$bindir = $row[1]."bin/";
	$backupFile = $cfg['database']['backupFolder']."/backup_".date("Y-m-d-H-i-s").".sql";
	if(DIRECTORY_SEPARATOR == "\\") $backupFile = str_replace("/", "\\", $backupFile);
	$command = $bindir ."mysqldump  -h ".$cfg['database']['server']." -u ".$cfg['database']['user']." -p ".$cfg['database']['password']." ".$cfg['database']['dbName']." > ".$backupFile;
//	$command = "mysqldump --opt --host=".$cfg['database']['server']." --user=".$cfg['database']['user']." --password=".$cfg['database']['password']." ".$cfg['database']['dbName']." > ".$backupFile;
//	echo ($command);
	system($command);
//	passthru($command);
	
}

function recover($filename){
	global $cfg;
	
	$backupFile = $cfg['database']['backupFolder']."/".$filename;
	if(DIRECTORY_SEPARATOR == "\\") $backupFile = str_replace("/", "\\", $backupFile);
	$command = "mysql --host=".$cfg['database']['server']." --user=".$cfg['database']['user']." --password=".$cfg['database']['password']." ".$cfg['database']['dbName']." < ".$backupFile;
	system($command);

}
?>