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


	$page->blocks['title'] = "Manage Rule";
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
$rule_id = isset($_GET['rule_id']) ? intval($_GET['rule_id']) : 0;
if($rule_id<0) {$rule_id = 0;}

$affiliate_id = isset($_GET['aff_id']) ? intval($_GET['aff_id']) : 0;
if($affiliate_id<0) {$affiliate_id = 0;}

$affiliate_class = new affiliate($cfg);


if(isset($_POST['condition_object']) && is_array($_POST['condition_object'])) {
	
	$group_meta_data = explode(';',$_POST['groups']);
	
	//echo '<pre>'.print_r($_POST,1).'</pre>';
	
	$groups = array();
	
	$current_index = 0;
	
	for($i=0;$i<$group_meta_data[0];$i++) {
	
		$groups[$i] = array();
		
		for($j=$current_index;$j<($current_index+$group_meta_data[$i+1]);$j++) {
			
			$condition = $_POST["condition_object"][$j];
			$groups[$i][] = array(
				
				"condition"		=>	$condition,
				"comparation"	=>	$_POST[$condition."_comparation"][$j],
				"value"			=>	$_POST[$condition."_condition"][$j],
				
			);
			
		}
		
		$current_index+=$group_meta_data[$i+1];
		
	}
	
	$data = array(
		"affiliate_id"				=>	$affiliate_id,
		"rule_data"					=>	serialize($groups),
		"suppression_percentage"	=>	$_POST['suppression_percentage'],
	);
	
	if($rule_id > 0) {
		$affiliate_class->update_affiliate_rule($rule_id,$data);
	} else {
		$rule_id = $affiliate_class->set_affiliate_rule($affiliate_id,$data);
	}
	
	//echo '<pre>'.print_r($groups,1).'</pre>';
	
	//die();
	
}


$rule_data = $rule_id > 0 ? $affiliate_class->get_rule($rule_id) : null;

$page->blocks['content'] = list_rule_page($rule_data,$affiliate_id);	

/*
 * construct and print page
 */
$page->construct_page(); 	// construct html page
$page->output_page(); 		// output page

function list_rule_page($rule_data,$affiliate_id) {
	//echo '<pre>'.print_r($rule_data,1).'</pre>';
	global $cfg;
	$new = is_null($rule_data);
	$html = "";
	
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/jquery-1.8.0.min.js"></script>';
	$html.= '<script type="text/javascript" src="'.$cfg['site']['folder'].'js/admin_rule.js"></script>';

	$html .= "<div class=\"listContent\">\n";
	
		$html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
		$html .= "<tr>\n";
		$html .= "<td class=\"titleCell\">".($new ? "New ":"").($affiliate_id==0 ? "General ":"")."Rule".($affiliate_id>0 ? " for Affiliate ".$affiliate_id:"")."</td>\n";
		$html .= "<td align=\"right\">\n";
		$html .= "<input onclick='location.href=\"affiliate_rules.php?aff_id=".($affiliate_id)."\"' type=\"button\" value=\"Back To Rule List\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";	
		$html .= "</td>\n";
		$html .= "</tr>\n";
		$html .= "</table>\n";
		
		$condition_tpl.= "<div class='condition_wrapper' style='display:Xnone;' data-groupwrapper='0'>";
			$condition_tpl.= "<div class='condition_rule'>";
				$condition_tpl.="<select class='condition_object' name='condition_object[]'>";
					$condition_tpl.= "<option data-type='text' value='location'>Location</option>";
					$condition_tpl.= "<option data-type='date' value='date'>Current Date</option>";
					$condition_tpl.= "<option data-type='date' value='first_seen'>Affiliate First Seen Date</option>";				
					$condition_tpl.= "<option data-type='date' value='last_seen'>Affiliate Last Seen</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.="<select class='comparation_select location_comparation' name='location_comparation[]'>";
					$condition_tpl.= "<option value='is'>IS</option>";
					$condition_tpl.= "<option value='is_not'>IS NOT</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.="<select class='comparation_select date_comparation' name='date_comparation[]' style='display:none;'>";
					$condition_tpl.= "<option value='equal'>Equal To</option>";
					$condition_tpl.= "<option value='bigger_than'>Bigger Than</option>";				
					$condition_tpl.= "<option value='less_than'>Less Than</option>";							
					$condition_tpl.= "<option value='x_days_after'>X Days After</option>";					
					$condition_tpl.= "<option value='x_days_before'>X Days Before</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.="<select class='comparation_select first_seen_comparation' name='first_seen_comparation[]' style='display:none;'>";
					$condition_tpl.= "<option value='equal'>Equal To</option>";
					$condition_tpl.= "<option value='bigger_than'>Bigger Than</option>";				
					$condition_tpl.= "<option value='less_than'>Less Than</option>";							
					$condition_tpl.= "<option value='x_days_after'>X Days After</option>";					
					$condition_tpl.= "<option value='x_days_before'>X Days Before</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.="<select class='comparation_select last_seen_comparation' name='last_seen_comparation[]' style='display:none;'>";
					$condition_tpl.= "<option value='equal'>Equal To</option>";
					$condition_tpl.= "<option value='bigger_than'>Bigger Than</option>";				
					$condition_tpl.= "<option value='less_than'>Less Than</option>";							
					$condition_tpl.= "<option value='x_days_after'>X Days After</option>";					
					$condition_tpl.= "<option value='x_days_before'>X Days Before</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.="<select class='condition_value location_condition' name='location_condition[]'>";
					$condition_tpl.= "<option value='initial'>Initial Sale</option>";
					$condition_tpl.= "<option value='exit'>Exit Sale</option>";
					//$condition_tpl.= "<option value='stats'>Stats Page</option>";
				$condition_tpl.="</select>";
				
				$condition_tpl.= "<input class='condition_value date_condition' name='date_condition[]' type='text' style='display:none;'/>";
				$condition_tpl.= "<input class='condition_value first_seen_condition' name='first_seen_condition[]' type='text' style='display:none;'/>";
				$condition_tpl.= "<input class='condition_value last_seen_condition' name='last_seen_condition[]' type='text' style='display:none;'/>";
				
				$condition_tpl.= "<a class='and_link' data-group='0' href='#'>[AND]</a>\n";
				$condition_tpl.= "<a class='remove_link' data-group='0' style='display:none;' href='#'>[REMOVE]</a>\n";
			$condition_tpl .= "</div>";
			
			$condition_tpl.= "<br /><span class='or_span'>OR</or>\n";
			
		$condition_tpl .= "</div>";
		
		$html.= str_replace(":Xnone",":none",$condition_tpl);
		
		$html .= "<div class=\"formDiv\">";
			$html .= "<form name=\"mainform\" id='mainform' action=\"rule.php?rule_id=".($new ? "-1":$rule_data['affiliate_rule_id'])."&aff_id=".$affiliate_id."&menuIndex=".$menuActiveIndex."\" method=\"post\" class=\"formLayer\">";
			$html .= "<fieldset>";
			$html .= "<legend>".(!$new ? "Edit":"Create")." Rule</legend>";
			
				$html .= "<label>Conditions</label>";
				
				$html .= "<div id='conditions_wrapper'>";
					$html.= "<input type='hidden' value='1' name='groups' id='groups' />";
					$html.= "<h3>Apply this rule if</h3>";
					
					if($new) {
					$html.= str_replace(":Xnone",":block",$condition_tpl);
					} else {
						$data = unserialize($rule_data['rule_data']);
						//echo '<pre>'.print_r($data,1).'</pre>';
						foreach($data as $key=>$group) {
						
							$html.= "<div class='condition_wrapper' style='display:block;' data-groupwrapper='".$key."'>";
								foreach($group as $k=>$rule) {
								
									$html.= "<div class='condition_rule'>";
										$html.="<select class='condition_object' name='condition_object[]'>";
											$html.= "<option data-type='text' value='location'".($rule["condition"]=="location" ? " selected":"").">Location</option>";
											$html.= "<option data-type='date' value='date'".($rule["condition"]=="date" ? " selected":"").">Current Date</option>";
											$html.= "<option  data-type='date'value='first_seen'".($rule["condition"]=="first_seen" ? " selected":"").">Affiliate First Seen Date</option>";				
											$html.= "<option data-type='date' value='last_seen'".($rule["condition"]=="last_seen" ? " selected":"").">Affiliate Last Seen</option>";
										$html.="</select>";
										
										$html.="<select class='comparation_select location_comparation' name='location_comparation[]' style='display:".($rule["condition"]=="location" ? "inline":"none")."'>";
											$html.= "<option value='is'".($rule["comparation"]=="is" ? " selected":"").">IS</option>";
											$html.= "<option value='is_not'".($rule["comparation"]=="is_not" ? " selected":"").">IS NOT</option>";
										$html.="</select>";
										
										$html.="<select class='comparation_select date_comparation' name='date_comparation[]' style='display:".($rule["condition"]=="date" ? "inline":"none")."'>";
											$html.= "<option value='equal'".($rule["comparation"]=="equal" ? " selected":"").">Equal To</option>";
											$html.= "<option value='bigger_than'".($rule["comparation"]=="bigger_than" ? " selected":"").">Bigger Than</option>";				
											$html.= "<option value='less_than'".($rule["comparation"]=="less_than" ? " selected":"").">Less Than</option>";							
											$html.= "<option value='x_days_after'".($rule["comparation"]=="x_days_after" ? " selected":"").">X Days After</option>";					
											$html.= "<option value='x_days_before'".($rule["comparation"]=="x_days_before" ? " selected":"").">X Days Before</option>";
										$html.="</select>";
										
										$html.="<select class='comparation_select first_seen_comparation' name='first_seen_comparation[]' style='display:".($rule["condition"]=="first_seen" ? "inline":"none")."'>";
											$html.= "<option value='equal'".($rule["comparation"]=="equal" ? " selected":"").">Equal To</option>";
											$html.= "<option value='bigger_than'".($rule["comparation"]=="bigger_than" ? " selected":"").">Bigger Than</option>";				
											$html.= "<option value='less_than'".($rule["comparation"]=="less_than" ? " selected":"").">Less Than</option>";							
											$html.= "<option value='x_days_after'".($rule["comparation"]=="x_days_after" ? " selected":"").">X Days After</option>";					
											$html.= "<option value='x_days_before'".($rule["comparation"]=="x_days_before" ? " selected":"").">X Days Before</option>";
										$html.="</select>";
										
										$html.="<select class='comparation_select last_seen_comparation' name='last_seen_comparation[]' style='display:".($rule["condition"]=="last_seen" ? "inline":"none")."'>";
											$html.= "<option value='equal'".($rule["comparation"]=="equal" ? " selected":"").">Equal To</option>";
											$html.= "<option value='bigger_than'".($rule["comparation"]=="bigger_than" ? " selected":"").">Bigger Than</option>";				
											$html.= "<option value='less_than'".($rule["comparation"]=="less_than" ? " selected":"").">Less Than</option>";							
											$html.= "<option value='x_days_after'".($rule["comparation"]=="x_days_after" ? " selected":"").">X Days After</option>";					
											$html.= "<option value='x_days_before'".($rule["comparation"]=="x_days_before" ? " selected":"").">X Days Before</option>";
										$html.="</select>";
										
										$html.="<select class='condition_value location_condition' name='location_condition[]' style='display:".($rule["condition"]=="location" ? "inline":"none")."'>";
											$html.= "<option value='initial'".($rule["value"]=="intial" ? " selected":"").">Initial Sale</option>";
											$html.= "<option value='exit'".($rule["value"]=="exit" ? " selected":"").">Exit Sale</option>";
											//$html.= "<option value='stats'".($rule["value"]=="stats" ? " selected":"").">Stats Page</option>";
										$html.="</select>";
										
										$html.= "<input class='".($rule["condition"]=="date" ? "has_calendar ":"")."condition_value date_condition' name='date_condition[]' type='text' style='display:".($rule["condition"]=="date" ? "inline":"none")."' value='".($rule["condition"]=="date" ? $rule["value"] : "")."' id='inputid".$key.rand(1,100000)."'/>";
										$html.= "<input class='".($rule["condition"]=="first_seen" ? "has_calendar ":"")."condition_value first_seen_condition' name='first_seen_condition[]' type='text' style='display:".($rule["condition"]=="first_seen" ? "inline":"none")."' value='".($rule["condition"]=="first_seen" ? $rule["value"] : "")."' id='inputid".$key.rand(1,100000)."'/>";
										$html.= "<input class='".($rule["condition"]=="last_seen" ? "has_calendar ":"")."condition_value last_seen_condition' name='last_seen_condition[]' type='text' style='display:".($rule["condition"]=="last_seen" ? "inline":"none")."' value='".($rule["condition"]=="last_seen" ? $rule["value"] : "")."' id='inputid".$key.rand(1,100000)."'/>";
										
										$html.= "<a class='and_link' data-group='".$key."' href='#'>[AND]</a>\n";
										if($k>0 || $key > 0) { 										
											$html.= "<a class='remove_link' data-group='".$key."'  href='#'>[REMOVE]</a>\n";
										}
									$html .= "</div>";
								
								}
								
								$html.= "<br /><span class='or_span'>OR</or>\n";
								
							$html .= "</div>";
						
						
						}
						
						
						
						
					}
					
				$html .= "</div>";
				
				
				$html .= "<br />";
				
				$html.= "<div class='conditions_add_or'><a href='#'>[ADD A NEW RULE GROUP]</a></div>";
				
				$html .= "<br />";
				
				$html .= "<label>Suppression %</label>";
				$html .= "<input type='text' name='suppression_percentage' value='".($new ? "":$rule_data['suppression_percentage'])."' />\n";
				$html .= "<br />";
				
				$html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
				$html .= "<br />";
				$html .= "<br />";
				
			$html .= "</fieldset>";
			$html .= "</form>";
		$html .= "</div>\n";
		
		
	
	$html .= "</div>\n";
	
	return $html;
	
}