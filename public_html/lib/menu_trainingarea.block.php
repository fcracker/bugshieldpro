<?php
/*
* This page constructs the main menu according to user session
* The PHP page using this function must include lib/user.class.php
* The function does not require database connection, all data are retrieved from session
*/

function get_menu($activeID = 1){
	global $cfg;
	global $lang;
	$user = new umUser();
	$user->get_session();
	$allowGroups = array();
	$allowGroups[] = 1; // group 1 is admin

	$html = "<ul id=\"navigation\">";
	// set menu item active
	if($user->userID == 0){
		$html .= "<li class=\"active\">";
		$html .= "<a href=\"".$cfg['site']['folder']."\">".$lang['menu']['myProfile']."</a>";
		$html .= "	            	   	<div class=\"sub\">";
		$html .= "			   				<ul>";
		// if the user has not login
		$html .= "			   					<li>";
		$html .= "									<a href=\"".$cfg['site']['folder']."\">".$lang['menu']['signUp']."</a>";
		$html .= "								</li>";
		$html .= "			   					<li>";
		$html .= "									<a href=\"".$cfg['site']['folder']."login.php\">".$lang['menu']['login']."</a>";
		$html .= "								</li>";
		$html .= "			   					<li>";
		$html .= "									<a href=\"".$cfg['site']['folder']."get_password.php\">".$lang['menu']['password']."</a>";
		$html .= "								</li>";
		if($cfg['site']['requireVerification']){
			$html .= "			   					<li>";
			$html .= "									<a href=\"".$cfg['site']['folder']."get_code.php\">".$lang['menu']['code']."</a>";
			$html .= "								</li>";
		}
		$html .= "		</ul>";
		$html .= "	</div>";
		$html .= "</li>";
	}else{
		$menu = new umMenu();
		$menu->get_groupID($user->userID);
		$menu->get_group_menus();
		if(count($menu->menus)){
			$preF2 = 0;
			for($i=0; $i<count($menu->menus); $i++){
				if($menu->menus[$i]['f2'] == 0){
					if($menu->menus[$i]['f1'] != 1){
						if($preF2>0){
							$html .= "		</ul>";
							$html .= "	</div>";
						}
						$html .= "</li>";
					}
					if($menu->menus[$i]['f1'] == $activeID)
						$html .= "	<li class=\"active\">";
					else 
						$html .= "	<li class=\"\">";
				    $html .= "			<a href=\"".($menu->menus[$i]['url']==""?"#none":$cfg['site']['folder'].$menu->menus[$i]['url'])."\">".$menu->menus[$i]['name']."</a>";
				}else{
					if($menu->menus[$i]['f2']==1){
						$html .= "		<div class=\"sub\">";
						$html .= "			<ul>";
					}
					$html .= "			   			<li>";
					$html .= "							<a href=\"".$cfg['site']['folder'].$menu->menus[$i]['url']."\">".$menu->menus[$i]['name']."</a>";
					$html .= "						</li>";
				}
				$preF2 = $menu->menus[$i]['f2'];
			}
			$html .= "		   		</ul>";
		}
		$menu = NULL;
	}
	$html .= "					</div>";
	$html .= "				</li>";
	$html .= "			</ul>";
	
	return $html;
}
?>