<?php

include_once("../../lib/config.inc.php");
include_once("../../lib/database.inc.php");
include_once("../../lib/page.class.php");
include_once("../../lib/menu.class.php");
include_once("../../lib/menu.block.php"); // load menu function
$page = new umPage(); // create page object
$page->get_language(); // get language id from client site
include_once("../../languages/" . $cfg['language'] . ".php"); // load language file
$page->template = "../../templates/" . $cfg['language'] . "/default.html"; // load template
include_once("../../lib/user.class.php");
include_once("../../lib/affiliate.class.php");

global $cfg;
$con = connect_database();

$user = new umUser();
$user->get_session();

$allowGroups = $cfg['site']['adminGroupIDs'];

$menuActiveIndex = get_menuActiveIndex($user->userID, $_SERVER['PHP_SELF']);
if ($menuActiveIndex > 0) {
    $page->blocks['title'] = "Affiliates";
    $page->blocks['menu'] = get_menu($menuActiveIndex);
    $page->blocks['folder'] = $cfg['site']['folder'];
    $page->blocks['selectLanguage'] = $page->build_language_form();
} else {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "admin/affiliate/list.php");
}

$affiliate_class = new affiliate($cfg);

$affiliates = $affiliate_class->get_all_affiliates();

$page->blocks['content'] = list_affiliate_page($affiliates);

/*
 * construct and print page
 */
$page->construct_page();  // construct html page
$page->output_page();   // output page

function list_affiliate_page($affiliates) {

    global $cfg;
    $html = "";

    $html.= '<script type="text/javascript" src="' . $cfg['site']['folder'] . 'js/jquery-1.8.0.min.js"></script>';
    $html .= '
	<script language="javascript">		
		$(document).ready(function() {
                    $(".notes a.edit_notes").each(function(){		
                            $(this).click(function(evt){
                                evt.preventDefault();
                                var id = $(this).attr("rel");
                                var notes_content = $("#notes_" + id).text();
                                var center = $(this);
                                $("#notes_" + id).hide();

                                if($("#notes_edit_" + id).length) {
                                        $("#notes_edit_" + id).show();

                                } else {

                                center.parent().append("<textarea style=\"min-width:300px;min-height: 50px;\" id=\'notes_edit_"+id+"\'>"+notes_content+"</textarea>");

                                }				
                                center.next("span").show();
                                center.hide();
                            });
                    });

                    $(".notes a.cancel_notes").each(function(){		
                            $(this).click(function(evt){
                                evt.preventDefault();
                                var id = $(this).attr("rel");				
                                $("#notes_" + id).show();
                                $("#notes_edit_" + id).hide();
                                $(this).parent().hide().prev("a").show();				
                            });		
                    });	



                    $(".notes a.save_notes").each(function(){		
                        $(this).click(function(evt){			
                            evt.preventDefault();
                            var id = $(this).attr("rel");	
                            var new_text = $("#notes_edit_" + id).val();
                            var center = $(this);
                            $.post(
                            "/../../reports/ajax_handler.php",
                            {action: "editnote_affid", affid: id, note:new_text},
                            function(data) {
                                    $("#notes_" + id).show().text(new_text);
                                    $("#notes_edit_" + id).hide();
                                    center.parent().hide().prev("a").show();	

                            } 

                            );
                        });		
                    });	
		});
	</script>';

    $html .= "<div class=\"listContent\">\n";

    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"titleTable\">\n";
    $html .= "<tr>\n";
    $html .= "<td class=\"titleCell\">Manage Affiliates</td>\n";
    $html .= "<td align=\"right\">\n";
    $html .= "<input onclick='location.href=\"list.php\"' type=\"button\" value=\"Affiliate List\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";
    $html .= "<input onclick='location.href=\"affiliate_rules.php\"' type=\"button\" value=\"General Rules\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";
    $html .= "<input onclick='location.href=\"stats.php\"' type=\"button\" value=\"Affiliate Stats\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" >\n";
    $html .= "</td>\n";
    $html .= "</tr>\n";
    $html .= "</table>\n";

    $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" class=\"listTable\">\n";
    $html .= "<tr class=\"captionRow\">\n";
    $html .= "<td width=\"3%\">Affiliate ID</td>";
    $html .= "<td width=\"20%\">First Seen</td>";
    $html .= "<td width=\"10%\">Last Seen</td>";
    $html .= "<td width=\"10%\">Sale Count</td>";
    $html .= "<td width=\"10%\">Note</td>";
    $html .= "<td width=\"10%\">Action</td>";
    $html .= "</tr>\n";

    foreach ($affiliates as $i => $affiliate) {

        if ($i % 2 == 0) {
            $html .= "<tr class=\"dataRow1\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow1'\">\n";
        } else {
            $html .= "<tr class=\"dataRow2\" onmouseover=\"this.className='heightDataRow'\" onmouseout=\"this.className='dataRow2'\">\n";
        }




        $html .= "<td>" . $affiliate["aff_id"] . "</td>";
        $html .= "<td>" . $affiliate["first_seen"] . "</td>";
        $html .= "<td>" . $affiliate["last_seen"] . "</td>";
        $html .= "<td>" . $affiliate["sale_count"] . "</td>";
        $html.= "<td>
                    <div class=\"notes\"><span id='notes_{$affiliate["aff_id"]}'>{$affiliate['note']}</span><a href='#' class='edit_notes' rel='{$affiliate["aff_id"]}'>[edit]</a> 
                    <span style='display:none;'><a href='#' class='save_notes' rel='{$affiliate["aff_id"]}'>[save]</a> | <a href='#' class='cancel_notes' rel='{$affiliate["aff_id"]}'>[cancel]
                    </a><br/>
                    </span
                    </div>
                  </td>\n";
        $html .= "<td>
			<a class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='affiliate_rules.php?aff_id=" . $affiliate["aff_id"] . "'>[Rules]</a>			
                            &nbsp;
                        <a class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" href='stats.php?aff_id=" . $affiliate["aff_id"] . "'>[Stats]</a>	
			</td>";
        $html .= "</tr>\n";
    }

    $html .= "</table>\n";



    $html .= "</div>\n";

    return $html;
}
