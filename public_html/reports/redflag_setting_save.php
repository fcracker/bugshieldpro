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
if ($_GET['category'] == "email") {
    $emails = $_POST['emails'];
    $pre_emails = $redflagRules->getRulesByCategory("email");
    $newEmails = array_diff($emails, $pre_emails);

    if (count($newEmails)) {
        foreach ($newEmails as $newEmail) {
            if ($newEmail) {
                $redflagRules->addRule("email", $newEmail);
            }
        }
    }
    $deletedEmails = array_diff($pre_emails, $emails);
    if (count($deletedEmails)) {
        foreach ($deletedEmails as $deletedEmail) {
            $redflagRules->deleteRule("email", $deletedEmail);
        }
    }
} else if ($_GET['category'] == "initial_order") {
    $enable_initial_order = $_POST['initial_order'] ? (int) $_POST['initial_order'] : 0;
    $preValue = $redflagRules->getSingleRuleByCategory("initial_order");
    if ($preValue != $enable_initial_order) {
        if (is_null($preValue)) {
            $redflagRules->addRule("initial_order", $enable_initial_order);
        } else {
            $id = $redflagRules->getIdbyValue("initial_order", $preValue);
            $redflagRules->update_by_id($id, array("value" => $enable_initial_order));
        }
    }
} else if ($_GET['category'] == "affiliate_id") {
    $rows = array();
    $affiliate_ids = $_POST['affiliate_id'];
    $rule_types = $_POST['rule_type'];
    $percentage_values = $_POST['percentage_value'];
    for ($i = 0; $i <= count($affiliate_ids); $i++) {
        $rows[] = array('affiliate_id' => $affiliate_ids[$i], 'rule_type' => $rule_types[$i], 'percentage_value' => $percentage_values[$i]);
    }
    $redflagRules->deleteRuleByCategory('affiliate_id');

    if (count($rows)) {
        foreach ($rows as $row) {
            if ($row['affiliate_id'] && $row['rule_type']) {
                $redflagRules->addRule("affiliate_id", json_encode($row));
            }
        }
    }
}

header("Location: redflag_setting.php");
die();
