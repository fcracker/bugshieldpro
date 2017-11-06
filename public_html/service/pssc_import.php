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

define ('PSSC_ONLI', $cfg['pssc']['publishid']);

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
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/pssc_import.php");
}

if($user->userID != 0 && $user->get_user() && $user->check_groups($allowGroups)){
	$errorMessage = "";
	if (isset($_POST['action'])){
		if($_POST['action']=='agree'){
			agreecsv();
		}else{
			$errorMessage = importcsv($errorAry);
			$page->blocks['content'] = importPage($errorMessage, $errorAry);
		}
	} else {
		$page->blocks['content'] = importPage();		
	}
}else{
	redirect($cfg['site']['folder']."login1.php?url=".$cfg['site']['folder']."service/pssc_import.php");
}


/*
 * construct and print page
 */
$page->construct_page(); // construct html page
$page->output_page(); // output page

function importPage($errorMessage = "", $error=array()){
	global $cfg;
	global $lang;
	$html = "";
	$html = "
		<script type='text/javascript'>
			function fn_select(flag){
				var obj = document.getElementsByName('chk_index[]');
				for(var i=0; i<obj.length; i++){
					obj[i].checked = flag;
				}
			}
			
			function fn_agree(){
				var flag = false;
				var obj = document.getElementsByName('chk_index[]');
				for(var i=0; i<obj.length; i++){
					if(obj[i].checked){ flag=true; break; }
				}
				if(flag){
					if(!confirm('Are you sure?')) return false;
					document.main_form.submit();
				}else{
					alert('No select data');
					return false;
				}
			}
			
			function fn_cancel(){
				window.open('".$cfg['site']['folder']."service/pssc_import.php', '_self');
			}
		</script>
	";
	// title
	$html .= "<div class=\"listContent\">\n";
	$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
	$html .= "<tr>\n";
	$html .= "<td class=\"titleCell\">\n";
	$html .= "Import Orders from CSV";
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
	if(count($error)){
		$html .= "
			<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">
				<tr>
					<td>
						Incorrect publisher ID detected. Import anyway?
					</td>
					<td align='right'>
						<input type='button' onclick='fn_agree()' value='  Yes  ' class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" />
						<input type='button' onclick='fn_cancel()' value='  No  ' class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" />
					</td>
				</tr>
			</table>
			<form name='main_form' method='post'>
				<input type=\"hidden\" name=\"action\" value=\"agree\" />
				<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">
					<tr class=\"captionRow\">
						<td><input name='chk_all' type='checkbox' onclick='fn_select(this.checked)' /></td>
						<td>Invoice<br/>Number</td>
						<td>Date</td>
						<td>PublishID</td>
						<td>Tracking<br/>Number</td>
						<td>Carrier<br/>Description</td>
						<td>Weight</td>
						<td>ShipCharge</td>
						<td>TotalCharge</td>
					</tr>";
			for($i=0; $i<count($error); $i++){
				$data = array_merge($error[$i], array("","","","","","","","",""));
				$html .= "
					<tr class=\"dataRow".($i%2+1)."\">
						<td><input type='checkbox' name='chk_index[]' value='".$i."' /></td>
						<td>".$data[2]."</td>
						<td>".$data[0]."</td>
						<td>".$data[1]."</td>
						<td>".$data[3]."</td>
						<td>".$data[4]."</td>
						<td>".$data[5]."</td>
						<td>".$data[6]."</td>
						<td>".$data[7]."</td>
						<input type='hidden' name='pssc_data[$i][]' value='".$data[0]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".PSSC_ONLI."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[2]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[3]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[4]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[5]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[6]."' />
						<input type='hidden' name='pssc_data[$i][]' value='".$data[7]."' />
					</tr>";
			}
		$html .= "		
				</table>
			</form>	
		";
	}else{
		$html .= "
			<form enctype=\"multipart/form-data\" method=\"POST\">
				<input type=\"hidden\" name=\"action\" value=\"upload\" />
				<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"100000\" />
				<table>
					<tr>
						<td>Upload the csv file:</td> 
						<td><input name=\"up_file\" type=\"file\" /></td>
						<td><input type=\"submit\" name=\"submit\" value=\"Upload It\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" /></td>
					</tr>
				</table>
			</form>
		";
	}
	$html .= "</p>\n";
	$html .= "</div>\n";
	return $html;
}


function importcsv(&$errorAry){
	global $cfg;
	
    $filename = $cfg['database']['backupFolder']."/".basename($_FILES['up_file']['name']);
    if(strtolower(substr($_FILES['up_file']['name'], -3))!='csv'){
    	return "You must csv file.";
    }
    $errorAry = array(); 
   	if(move_uploaded_file($_FILES['up_file']['tmp_name'], $filename)) {
		$fp = fopen ($filename,"r"); 
	    $size = filesize($filename)+1; 
	    while ($data = fgetcsv($fp, $size, ",")) { 
	    	if(!setPsscData($data)) $errorAry[] = $data;
	    } 
	    fclose ($fp);
	    if(!count($errorAry)){
		    echo "<script>alert('Completed');</script>";
		    redirect($cfg['site']['folder']."service/pssc_import.php");
	    }
    } 
    else{
       return "Problem in uploading";
    }
}

function agreecsv(){
	global $cfg;
	$selIndex = $_POST['chk_index'];
	for($i=0; $i<count($selIndex); $i++){
		setPsscData($_POST['pssc_data'][$selIndex[$i]]);
	}
	echo "<script>alert('Completed');</script>";
    redirect($cfg['site']['folder']."service/pssc_import.php");
}

function setPsscData($data){
	global $cfg;
	$invoiceNumber = preg_split("/\D/", $data[2]);
	$invoiceNumber = (int) $invoiceNumber[0];
	
	if(strtoupper($data[1])!=PSSC_ONLI) return false;
	
	$pssc_date = preg_split("/\D/", $data[0]);
	$pssc_date = $pssc_date[2]."-".$pssc_date[0]."-".$pssc_date[1];
	
	$sql = "UPDATE ".$cfg['database']['prefix']."user
			SET `Memo`='".$data[3]."'
			WHERE `UserID`='".$invoiceNumber."'";
	mysql_query($sql);
	
	$sql = "UPDATE ".$cfg['database']['prefix']."user_psscdata 
			SET
				`pssc_date`='".$pssc_date."',
				`pssc_trackingnumber`='".$data[3]."',
				`pssc_carriered`='".$data[4]."',
				`pssc_weight`='".$data[5]."',
				`pssc_ship`='".$data[6]."',
				`pssc_total`='".$data[7]."'
			WHERE `UserID`='".$invoiceNumber."'";
	mysql_query($sql);
	return true;
}
close_database($con);