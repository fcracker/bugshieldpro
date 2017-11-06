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


if (!$user->userID) {
    redirect($cfg['site']['folder'] . "login1.php?url=" . $cfg['site']['folder'] . "reports/anti-fraud.php");
}

$postData = $_POST;

if (isset($postData['start'])) {
    //Recheck Data
    $order = new order;
    $antifraud = new antifraud;
    $start = $postData['start'];
    $step = $postData['step'];
    $res = mysql_query("SELECT id, order_id FROM `antifraud` WHERE 1  ORDER BY id DESC LIMIT {$start},{$step}");
    if (!mysql_num_rows($res)) {
        die("1");
    }
    while ($row = mysql_fetch_object($res)) {
        $order_id = $row->order_id;
        try {
            $antifraud->check_order($order_id, true);
        } catch (Exception $ex) {
            
        }
    }
    die("1");
} else {
    
}

$totalSql = "select COUNT(id) as k  FROM `antifraud` WHERE 1";
$result = mysql_query($totalSql);
$row = mysql_fetch_object($result);
$total = $row->k;
?>
<html lang = "en">
    <head>
        <title>Progress Bar</title>
        <script type='text/javascript' src='/js/jquery-1.4.2.min.js'></script>
        <script type='text/javascript' src='/js/reports/recheck_all.js'></script>
    </head>
    <body>
        <!--Progress bar holder -->        
        <div style="text-align:center">
            <div id = "progress" style = "width:500px;border:1px solid #ccc;margin: 0px auto;margin-top: 53px;"></div>
            <!--Progress information -->
            <div id = "information" style = "width:500px;text-align:left;margin: 0px auto;"></div>
        </div>
        <input type="hidden" id="total" value="<?php echo $total ?>">        
    </body>
</html>