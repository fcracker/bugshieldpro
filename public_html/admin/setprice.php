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
	$page->blocks['title'] = "Set Price";
	$page->blocks['menu'] = get_menu($menuActiveIndex);
	$page->blocks['folder'] = $cfg['site']['folder'];
	$page->blocks['selectLanguage'] = $page->build_language_form();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/setprice.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
    if (isset ($_POST['submit'])){
		$query = "UPDATE ".$cfg['database']['prefix']."price ";
		$query .= "SET Year='".$_POST['year']."', Month='".$_POST['month']."' ";
		mysql_query($query);
    }
    $page->blocks['content'] = show_menuEditor();
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."admin/setprice.php");
}

/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function show_menuEditor($errorMessage = ""){
	global $cfg;
	global $lang;
	$html = "";
	// title
	$html .= "<div class=\"listContent\">\n";

	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Set Price";
	$html .= "</td>\n";
	$html .= "</tr>\n";
	$html .= "</table>\n";
	if($errorMessage != ""){
		$html .= "<ul id=\"errorMessage\">".$lang['text']['errorsFoundList'].$errorMessage."</ul>";
	}else{
		$html .= "<br>";
	}
        $query = "Select * From ".$cfg['database']['prefix']."price";
        $result = mysql_query($query);
        $year = 0;
        $month = 0;
        if(mysql_num_rows($result)){
            while($row=mysql_fetch_array($result, MYSQL_NUM)){
                $year = $row[0];
                $month = $row[1];
            }
        }
        $html .= "<form action=\"".sess_url($cfg['site']['folder']."admin/setprice.php")."\" method=\"post\">
                    <p>Price of gold membership for 1 year :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"year\" value=$year /></p>
                    <p>Price of gold membership for 6 months: <input type=\"text\" name=\"month\" value=$month /></p>
                    <input type=\"hidden\" name=\"submit\" value=\"submit\" />
                    <input type=\"submit\" value=\"Update\" />
                </form>";
	$html .= "</div>\n";
	return $html;
}
close_database($con);
?>