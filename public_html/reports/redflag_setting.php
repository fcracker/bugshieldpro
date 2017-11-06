<?php

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/page.class.php");
include_once("../lib/menu.class.php");
include_once("../lib/conversions.php");
include_once("../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../templates/" . $cfg['language'] . "/default.html"; // load template

include_once("../lib/user.class.php");

include_once("../lib/order.class.php");

include_once("../lib/antifraud.class.php");
include_once("../lib/antifraud.redflag.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();
$redflagRules = new antifraud_reflag();
$emails = $redflagRules->getRulesByCategory('email');
$enalbeInitialOrder = $redflagRules->getSingleRuleByCategory('initial_order');
if (!$enalbeInitialOrder) {
    $enalbeInitialOrder = 0;
}

$affiliateIdRules = $redflagRules->getRulesByCategory('affiliate_id');




$page->blocks['title'] = "Anti Fraud Service";
$page->blocks['menu'] = get_menu($menuActiveIndex);
$page->blocks['folder'] = $cfg['site']['folder'];
$page->blocks['selectLanguage'] = $page->build_language_form();
$page->blocks['content'] = list_rule_page($emails, $enalbeInitialOrder, $affiliateIdRules);
$page->construct_page();  // construct html page
$page->output_page();   // output page

function list_rule_page($emails, $enalbeInitialOrder, $affiliateIdRules) {
    //echo '<pre>'.print_r($rule_data,1).'</pre>';
    global $cfg;
    $html = "";

    $html.= '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-1.8.0.min.js"></script>';
    $html.= '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/reports/admin_rule.js"></script>';

    $html .= "<div class=\"listContent\">\n";

    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $html .= "  <tr>\n";
    $html .= "      <td class=\"titleCell\">Red Flag Rules</td>\n";
    $html .= "      <td align=\"right\">\n";
    $html .= "          <input onclick='location.href=\"anti-fraud.php?aff_id=" . ($affiliate_id) . "\"' type=\"button\" value=\"Back To Anti Fraud List\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";
    $html .= "      </td>\n";
    $html .= "  </tr>\n";
    $html .= "</table>\n";

    /*     * ****------------Email-----------************** */
    $condition_tpl.= "<div class='emails_wrapper' style='display:Xnone;' data-groupwrapper='0'>";
    $condition_tpl.= "  <div class='email_list_item'>";
    $condition_tpl.= "      <input type=\"email\" class='email' name='emails[]'/>";
    $condition_tpl.= "      <a class='remove_link' data-group='0' href='#'>[REMOVE]</a>\n";
    $condition_tpl.= "  </div>";
    $condition_tpl.= "</div>";

    $html.= str_replace(":Xnone", ":none", $condition_tpl);

    $html .= "<div class=\"formDiv\">";
    $html .= "<form name=\"mainform\" id='mainform' action=\"redflag_setting_save.php?category=email\" method=\"post\" class=\"formLayer\">";
    $html .= "<fieldset>";
    $html .= "<legend>Red Flag Emails</legend>";

    $html .= "<label>Email List</label><br/>";

    $html .= "<div id='emails_wrapper'>";

    if (count($emails) == 0) {
        $html.= str_replace(":Xnone", "", $condition_tpl);
    } else {
        foreach ($emails as $email) {
            $emailfield_tpl = "<div class='emails_wrapper' style='display:Xnone;' data-groupwrapper='0'>";
            $emailfield_tpl.= "  <div class='email_list_item'>";
            $emailfield_tpl.= "      <input type=\"email\" class='email' name='emails[]'/ readonly value=\"{$email}\">";
            $emailfield_tpl.= "      <a class='remove_link' data-group='0' href='#'>[REMOVE]</a>\n";
            $emailfield_tpl.= "  </div>";
            $emailfield_tpl.= "</div>";
            $html .= $emailfield_tpl;
        }
    }

    $html .= "</div>";

    $html.= "<div class='emails_add_or'><a href='#'>[Add Email]</a></div>";

    $html .= "<br />";

    $html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
    $html .= "<br />";


    $html .= "</fieldset>";
    $html .= "</form>";
    $html .= "</div>\n";

    /*     * ****------------Inital Order-----------************** */
    $html .= "<div class=\"formDiv\">";
    $html .= "<form name=\"mainform\" id='mainform' action=\"redflag_setting_save.php?category=initial_order\" method=\"post\" class=\"formLayer\">";
    $html .= "<fieldset>";
    $html .= "<legend>Inital Order</legend>";

    $html .= "<div id='setting_initalorder_wrapper'>";

    $html .= "<input type=\"checkbox\"/ name=\"initial_order\" id=\"initial_order\" value=\"1\" " . ($enalbeInitialOrder ? "checked" : "") . ">";
    $html .= "<label for=\"initial_order\" style=\"display: inline;float: none;margin-left: 7px;\">Red flagging orders that are only 9.95 dollars and have an affiliate ID attached</label><br/>";

    $html .= "</div>";
    $html .= "<br />";
    $html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
    $html .= "<br />";


    $html .= "</fieldset>";
    $html .= "</form>";
    $html .= "</div>\n";


    /*     * ****------------Red flag affiliate IDs-----------************** */
    $condition_tpl = "<div class='affiliate_wrapper' style='display:Xnone;' data-groupwrapper='0'>";
    $condition_tpl.= "  <div class='affiliate_list_item'>";
    $condition_tpl.= "      <input type=\"text\" class='affiliate_id' name='affiliate_id[]' placeholder=\"affiliate id\" required/>";
    $condition_tpl.= "      <select class='condition_object' name='rule_type[]'>";
    $condition_tpl.= "          <option data-type='text' value='initial_order'>Percentage of inital orders</option>";
    $condition_tpl.= "          <option data-type='text' value='initial_order_wihout_upsell'>Percentage of inital orders with no upsell</option>";
    $condition_tpl.= "          <option data-type='text' value='initial_order_wihout_upsell_995'>Percentage of initial orders with no upsells and with value 9.95</option>";
    $condition_tpl.= "      </select>";
    $condition_tpl.= "      <input type=\"text\" class='percentage_value' name='percentage_value[]' placeholder=\"Percentage\"/>";

    $condition_tpl.= "      <a class='remove_link' data-group='0' href='#'>[REMOVE]</a>\n";
    $condition_tpl.= "  </div>";
    $condition_tpl.= "</div>";

    $html.= str_replace(":Xnone", ":none", $condition_tpl);

    $html .= "<div class=\"formDiv\">";
    $html .= "<form name=\"mainform\" id='mainform' action=\"redflag_setting_save.php?category=affiliate_id\" method=\"post\" class=\"formLayer\">";
    $html .= "<fieldset>";
    $html .= "<legend>Red flag affiliate IDs</legend>";

    $html .= "<label>Affiliate Rules</label><br/>";

    $html .= "<div id='affiliates_wrapper'>";

    if (count($affiliateIdRules) == 0) {
        $html.= str_replace(":Xnone", "", $condition_tpl);
    } else {
        foreach ($affiliateIdRules as $affiliateIdRule) {
            $row = json_decode($affiliateIdRule);
            $affiliate_tpl = "<div class='affiliate_wrapper' style='display:Xnone;' data-groupwrapper='0'>";
            $affiliate_tpl.= "  <div class='affiliate_list_item'>";
            $affiliate_tpl.= "      <input type=\"text\" class='affiliate_id' name='affiliate_id[]' placeholder=\"affiliate id\" required value=\"{$row->affiliate_id}\"/>";
            $affiliate_tpl.= "      <select class='condition_object' name='rule_type[]'>";
            $affiliate_tpl.= "          <option data-type='text' value='initial_order' " . ($row->rule_type == 'initial_order' ? 'selected' : '') . ">Percentage of inital orders</option>";
            $affiliate_tpl.= "          <option data-type='text' value='initial_order_wihout_upsell' " . ($row->rule_type == 'initial_order_wihout_upsell' ? 'selected' : '') . ">Percentage of inital orders with no upsell</option>";
            $affiliate_tpl.= "          <option data-type='text' value='initial_order_wihout_upsell_995' " . ($row->rule_type == 'initial_order_wihout_upsell_995' ? 'selected' : '') . ">Percentage of initial orders with no upsells and with value 9.95</option>";
            $affiliate_tpl.= "      </select>";
            $affiliate_tpl.= "      <input type=\"text\" class='percentage_value' name='percentage_value[]' placeholder=\"Percentage\" value=\"{$row->percentage_value}\"/>";

            $affiliate_tpl.= "      <a class='remove_link' data-group='0' href='#'>[REMOVE]</a>\n";
            $affiliate_tpl.= "  </div>";
            $affiliate_tpl.= "</div>";
            $html .= $affiliate_tpl;
        }
    }

    $html .= "</div>";

    $html.= "<div class='affiliate_add_or'><a href='#'>[Add Rule]</a></div>";

    $html .= "<br />";

    $html .= "<input type=\"submit\" name=\"submitBtn\" value=\"Save\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\">";
    $html .= "<br />";


    $html .= "</fieldset>";
    $html .= "</form>";
    $html .= "</div>\n";




    $html .= "</div>\n";

    return $html;
}
