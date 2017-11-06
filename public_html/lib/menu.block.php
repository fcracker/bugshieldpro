<?php
/*
* This page constructs the main menu according to user session
* The PHP page using this function must include lib/user.class.php
* The function does not require database connection, all data are retrieved from session
*/

function getmenuArray() {
	global $cfg;
	global $lang;
	$return = 	array(
					$lang['menu']['myProfile'] => array(
						$lang['menu']['signUp']=>'',
						$lang['menu']['login']=>'login.php',
						$lang['menu']['password']=>'get_password.php'
					)
				);
	
	if($cfg['site']['requireVerification']){
		$return[$lang['menu']['myProfile']][$lang['menu']['code']] = $cfg['site']['url'] . $cfg['site']['folder'] . 'get_code.php';
	}
	
	return $return;
}
function get_menu($activeID = 1){
	global $cfg;
	global $lang;
	$user = new umUser();
	$user->get_session();
	$allowGroups = array();
	$allowGroups[] = 1; // group 1 is admin
	
	$html = "";
	$html .= "	<div class=\"wrapper\">";
	$html .= "		<div class=\"nav-wrapper\">";
	$html .= "			<div class=\"menuNav\">";
	$html .= "				<ul id=\"navigation\">";
	// set menu item active
	if($user->userID == 0){
		$html .= "			   		<li class=\"active\">";
		$html .= "						<a href=\"".sess_url($cfg['site']['folder'])."\">";
		$html .= "							<span class=\"menu-left\"></span>";
		$html .= "							<span class=\"menu-mid\">".$lang['menu']['myProfile']."</span>";
		$html .= "							<span class=\"menu-right\"></span>";
		$html .= "						</a>";
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
		$html .= "									<a href=\"".sess_url($cfg['site']['folder'].'get_password.php')."\">".$lang['menu']['password']."</a>";
		$html .= "								</li>";
		if($cfg['site']['requireVerification']){
			$html .= "			   					<li>";
			$html .= "									<a href=\"".sess_url($cfg['site']['folder']."get_code.php") . "\">".$lang['menu']['code']."</a>";
			$html .= "								</li>";
		}
		$html .= "			   				</ul>";
		$html .= "			   				<div class=\"btm-bg\"></div>";
		$html .= "			   			</div>";
		$html .= "					</li>";
	}else{
		$menu = new umMenu();
		$menu->get_groupID($user->userID);
		$menu->get_group_menus();
		//print_r($menu->menus);
		if(count($menu->menus)){
			$preF2 = 0;
			for($i=0; $i<count($menu->menus); $i++){
				if($menu->menus[$i]['f2'] == 0){
					if($menu->menus[$i]['f1'] != 1){
						if($preF2>0){
							$html .= "		</ul>";
							$html .= "		<div class=\"btm-bg\"></div>";
							$html .= "	</div>";
						}
						$html .= "</li>";
					}
					if($menu->menus[$i]['f1'] == $activeID)
						$html .= "	<li class=\"active\">";
					else 
						$html .= "	<li class=\"\">";
					if($menu->menus[$i]['name']==$cfg['site']['MyProfile']) $menu->menus[$i]['url']="profile.php";
				    $html .= "			<a href=\"".($menu->menus[$i]['url']==""?"#none":sess_url($cfg['site']['folder'].$menu->menus[$i]['url']))."\">";
				    $html .= "		    	<span class=\"menu-left\"></span>";
				    $html .= "		    	<span class=\"menu-mid\">".$menu->menus[$i]['name']."</span>";
					$html .= "				<span class=\"menu-right\"></span>";
					$html .= "			</a>";
				}else{
					if($preF2==0){
						$html .= "		<div class=\"sub\">";
						$html .= "			<ul>";
					}
					$html .= "			   			<li>";
					$html .= "							<a href=\"".sess_url($cfg['site']['folder'].$menu->menus[$i]['url'])."\">".$menu->menus[$i]['name']."</a>";
					$html .= "						</li>";
				}
				$preF2 = $menu->menus[$i]['f2'];
			}
		}
		$menu = NULL;
		if($preF2!=0){
			$html .= "					</ul>";
			$html .= "		<div class=\"btm-bg\"></div>";
			$html .= "					</div>";
		}
		$html .= "				</li>";
	}
	

	$html .= "			</ul>";
	$html .= "		</div>";
	$html .= "	</div>";
	$html .= "	</div>";
	return $html;
}

?>