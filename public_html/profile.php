<?php
header("Cache-Control: no-cache, must-revalidate");
include_once ("./lib/config.inc.php");
include_once ("./lib/database.inc.php");
include_once ("./lib/menu.class.php");
include_once ("./lib/html_page.class.php");
include_once ("./lib/user.class.php");
include_once ("./lib/form.class.php");
include_once ("./languages/" . $cfg['language'] . ".php");

 // load language file

include_once ("./lib/menu.block.php");

$con = connect_database();
/*
* create content blocks
* page is built in this part
*/
$user = new umUser();

if ($user->get_session()) {
	$user->get_user(TRUE);
	if (isset($_POST['UpgradeGroup'])) {
		$user->belongToGroups[0]->change_users_group($user->userID, $_POST['UpgradeGroup']);
		$user->get_user(true);
	}

	$adminTemplateAccess = explode(",", $cfg['group']['adminTemplateAccessGroupIds']);
	if (in_array($user->belongToGroups[0]->groupID, $adminTemplateAccess)) {
		include_once ("./lib/page.class.php");

		$page = new umPage(); // create page object
		$page->get_language(); // get language id from client site
		$page->template = "./templates/" . $cfg['language'] . "/default.html"; // load template
		$page->blocks['title'] = $lang['title']['default'];
		$page->blocks['menu'] = get_menu(1);
		$page->blocks['folder'] = $cfg['site']['folder'];
		$page->blocks['selectLanguage'] = $page->build_language_form();
		$page->blocks['content'] = show_page();
		$page->construct_page(); // construct html page
		$page->output_page(); // output page
	}
	else {
		include_once ("./lib/pagerenderer.class.php");

		include_once ("./lib/templatemanager.class.php");

		include_once ("./languages/" . $cfg['language'] . ".php");

 // load language file

		$menu = new umMenu();
		$menu->get_groupID($user->userID);
		$menu->get_group_menus();
		$tm = new TemplateManager();
		$tmpl = $tm->getActivatedTemplate();
		$pr = new PageRenderer($tmpl["name"]);
		$pr->setMenu($menu->getMenuArray());
		$pr->setTitle($lang['title']['default']);
		$pr->setContent(show_page());
		$pr->render();
		$pr->display();
	}
}
else {
	redirect($cfg['site']['folder']);
}

function show_page() {
	global $lang;
	global $cfg;
	global $user;
	$html = "";
	$html.= "<div style=\"margin: 20px;\">";
	$html.= "<div style=\"width: 60%; float: center;\">";
	$html.= "<div class=\"rightTitle\">";
	$html.= "<img src=\"" . $cfg['site']['folder'] . "images/my_profile.gif\" align=\"absmiddle\"> ";
	$html.= "<b>" . $lang['menu']['myProfile'] . "</b>";
	$html.= "</div>";
	$html.= "<ul>";
	$html.= "<li>Name : " . $user->email . "</li>";
	$html.= "<li>Membership : " . $user->belongToGroups[0]->groupTitle . "</li>";
	$html.= "<li>Email : " . $user->emailAddress . "</li>";
	$html.= "</ul>";
	if (!$user->check_groups($cfg['site']['adminGroupIDs']) && $user->belongToGroups[0]->groupID != $cfg['group']['gold']) {
		$nextGroup = $user->belongToGroups[0]->get_next_group();
		if (count($nextGroup)) {
			$html.= "<form name=\"Upgrade\" action=\"" . sess_url($cfg['site']['folder'] . "update_group.php") . "\" method=\"post\" >";
			$html.= "<input name=\"UpgradeGroup\" type=\"hidden\" value=\"" . $nextGroup['groupID'] . "\">";
			$html.= "</form>";
		}
		else {
		}
	}

	$html.= "</div>";
	$html.= "</div>";
	return $html;
}

close_database($con);