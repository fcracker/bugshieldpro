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
	$page->blocks['title'] = "Import User from CSV";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/import.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$errorMessage = "";
	if (isset($_POST['submit'])){
		$errorMessage = importcsv();
		if ($errorMessage != "")
			$page->blocks['content'] = importPage($errorMessage);
	} else {
		$page->blocks['content'] = importPage();		
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/import.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function importPage($errorMessage = ""){
	global $cfg;
	global $lang;
	$html = "";
	
	$html .= "<script>
		
	</script>";
	
	// title
	$html .= "<div class=\"listContent\">\n";

	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Import User from CSV";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
	// page navigation
	$html .= "<p align='center'>\n";
	$html .= "
			<table>
				<tr>
					<td>
						<form enctype=\"multipart/form-data\" action=\"".sess_url("import.php")."\" method=\"POST\">
							<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"100000\" />
							Upload the file: <input name=\"up_file\" type=\"file\" /><br><br>
							<input type=\"radio\" name=\"opt\" value=\"Append\" checked>Append
							<input type=\"radio\" name=\"opt\" value=\"Replace\">Replace
							<input type=\"submit\" name=\"submit\" value=\"Upload It\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"/>
						</form>
					</td>
				</tr>
			</table>
	";
	$html .= "</p>\n";
	$html .= "</div>\n";
	return $html;
}

close_database($con);

function importcsv(){
	global $cfg;
	
	$target = $cfg['database']['backupFolder']."/" ;
    $target = $target.basename($_FILES['up_file']['name']);
   	if(move_uploaded_file($_FILES['up_file']['tmp_name'], $target)) {
		$CSVarray = get_csv($target); 
 		$CSVarray = makeINSERT($CSVarray, $cfg['site']['cookiePrefix']."user");
 		if ($_POST['opt']== "Replace"){
 			mysql_query("TRUNCATE TABLE '".$cfg['site']['cookiePrefix']."user'");
 		}
 		foreach ($CSVarray as $data){
			mysql_query($data);
			if (mysql_error())
				return mysql_error();
 		}
 		echo "<script>alert('Completed');window.history.back()</script>";
    } 
    else{
       return "Problem in uploading";
    }
}

function get_csv($filename, $delim =","){ 
    $row = 0; 
    $dump = array(); 
    $f = fopen ($filename,"r"); 
    $size = filesize($filename)+1; 
    while ($data = fgetcsv($f, $size, $delim)) { 
        $dump[$row] = $data; 
        $row++; 
    } 
    fclose ($f); 
    return $dump; 
} 

function makeINSERT($text, $table){ 
    $insert = array(); 
    $i = 0; 
    while (list($key, $val) = @each($text)){ 
		$insert[$i] = "INSERT into ".$table." VALUES('"; 
        $insert[$i] .= implode("','", $val); 
        $insert[$i] .= "');\n";
        str_replace("\\","", $insert[$i]); 
        $i++; 
    } 
    return $insert; 
} 
die();
?>