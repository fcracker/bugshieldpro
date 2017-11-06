<?php

include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");

include_once("../lib/user.class.php");

include_once("../lib/order.class.php");

include_once("../lib/antifraud.class.php");

include_once("../lib/inventory.class.php");

$con = connect_database();

$user = new umUser();
$user->get_session();

global $cfg;

$db = get_pdo_db(
        $cfg['database']['user'], $cfg['database']['password'], $cfg['database']['dbName']
);

$inventory = new Inventory($db, $cfg);

if (!isset($_POST['action'])) {

    die("error:no action");
}
$action = $_POST['action'];

switch ($action) {

    case 'save_tracking_no':

        if (!isset($_POST['order']) || !isset($_POST['tracking_no'])) {

            die("error:not enough data.retry?");
        }

        $order_id = intval($_POST['order']);
        $tracking_no = htmlentities(strip_tags($_POST['tracking_no']));

        $order = new order;

        if (!strlen($tracking_no)) {
            $tracking_no = "click to assign";
        }

        $o = $order->update_by_id($order_id, array("tracking_number" => $tracking_no));

        //update the user info (orders information) as well				
        $user = new umUser;
        $user->userID = $o->user_id;
        $user->get_user();

        //use memo just for tracking No
        $new_memo = strlen($user->memo) ? explode(",", $user->memo) : array();

        $trimmed_memo = array();

        foreach ($new_memo as $nm) {
            $trimmed_memo[] = trim($nm);
        }

        if (!in_array($tracking_no, $trimmed_memo) && $tracking_no != "click to assign") {
            $trimmed_memo[] = $tracking_no;
        }

        $user->memo = implode(" , ", $trimmed_memo);

        //run the update
        $user->update_user();

        die($tracking_no);

        break;

    case 'ship_order':

        if (!isset($_POST['order']) || !isset($_POST['date'])) {

            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        //check if the date is somewhat valid
        if (date("Y-m-d", strtotime($_POST['date'])) != $_POST['date']) {
            $response = array("status" => "ERR", "error" => "The date you set is not valid!");
            echo json_encode($response);
            die();
        }



        $order_id = intval($_POST['order']);

        $order = new order;
        if ($order->has_tracking_number_by_id($order_id)) {

            //destock
            $the_orders = $order->get_specific_orders(array($order_id));
            $the_order = $the_orders[0];
            $inventory->alterStock($the_order->qty, "-");

            $o = $order->update_by_id($order_id, array("status" => "shipped", "shipment_date" => $_POST['date']));

            //update the user info (orders information) as well

            $user = new umUser;
            $user->userID = $o->user_id;
            $user->get_user();

            //use memo just for tracking No

            $tracking_no = $order->get_tracking_number_by_id($order_id);

            $new_memo = strlen($user->memo) ? explode(",", $user->memo) : array();
            $trimmed_memo = array();

            foreach ($new_memo as $nm) {
                $trimmed_memo[] = trim($nm);
            }

            if (!in_array($tracking_no, $trimmed_memo) && $tracking_no != "click to assign") {
                $trimmed_memo[] = $tracking_no;
            }

            $user->memo = implode(" , ", $trimmed_memo);

            $user->shippedFrom = $_POST['date'];
            $user->shippedTo = $_POST['date'];

            $user->update_memo_user();



            $response = array("status" => "OK");
        } else {
            $response = array("status" => "ERR", "error" => "There is no tracking number defined! Please add one first.");
        }

        echo json_encode($response);
        die();

        break;

    case 'unship_order':

        if (!isset($_POST['order'])) {

            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $order_id = intval($_POST['order']);

        $order = new order;

        //restock
        $the_orders = $order->get_specific_orders(array($order_id));
        $the_order = $the_orders[0];
        $inventory->alterStock($the_order->qty, "+");

        $o = $order->update_by_id($order_id, array("status" => "not shipped"));


        //update the user info (orders information) as well

        $user = new umUser;
        $user->userID = $o->user_id;
        $user->get_user();

        $user->shippedFrom = "";
        $user->shippedTo = "";

        //$user->update_memo_user();


        $response = array("status" => "OK");

        echo json_encode($response);
        die();

        break;

    case 'recompute_local':

        $af = new antifraud;
        if (!isset($_POST['order']) || !isset($_POST['zip'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $order_id = intval($_POST['order']);
        $zip = $_POST['zip'];
        $data = $af->check_order($order_id);

        //get external determined coords (from IP)
        $extern_data = unserialize($data->external_result_raw);
        $extern_coords = array("lat" => $extern_data["ip_latitude"], "lng" => $extern_data["ip_longitude"]);

        //get internally determined coords (from DB)
        $zip_stub = strtoupper(substr($zip, 0, 3));
        $sql = "select lat,lng from canada_zip_lookup where first_three_zip='" . $zip_stub . "'";
        $result = mysql_query($sql);

        if (!mysql_num_rows($result)) {
            $response = array("status" => "ERR", "error" => "could not find internal coords for zip | " . $zip . " |");
            echo json_encode($response);
            die();
        }
        $internal_data = mysql_fetch_assoc($result);

        $intern_coords = array("lat" => $internal_data["lat"], "lng" => $internal_data["lng"]);

        //compute the distance
        $distance = $af->getGeoDistanceBetweenCords($extern_coords, $intern_coords);

        //save it
        $data->ip_location_correlation_local = $distance . " KM";
        $af->update($data);

        $response = array("status" => "OK", "c1" => $extern_coords["lat"] . ":" . $extern_coords["lng"], "c2" => $intern_coords["lat"] . ":" . $intern_coords["lng"], "distance" => $distance . " KM");

        echo json_encode($response);
        die();


        break;

    case "fraud_info":
        if (!isset($_POST['order'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }
        $af = new antifraud;
        $data = $af->check_order($_POST['order']);
        $country_data = explode(";", $data->bin_country_match);

        $html = '
        <h2>Fraud Check Data<h2>
        Order ID:' . $_POST['uid'] . '
        
        <br />
        AVS Response: ( ' . $data->avs_response . ' )
        <br />
        CVV (M/N): ( ' . $data->cvv_response . ' )
        <br />
        Location Correlation:' . $data->ip_location_correlation_external . ' KM
        <br />
        Country Match: We have << ' . $country_data[2] . ' >> ; MXMND says:  << ' . $country_data[0] . ' >>
        <br />
        Bank: ' . $country_data[1] . '
        <br />
        Prepaid Card:' . $data->bin_prepaid_match . '
        <br />
        Proxy Used:' . $data->ip_is_proxy . '
        <br />
        Maxmind High Risk email:' . $data->is_email_high_risk . '
        <br />
        Maxmind High Risk address:' . $data->is_address_high_risk . '
        <br />
        Maxmind Risk Score:' . $data->risk_score . '
      ';
        $response = array("status" => "OK", "html" => $html);

        echo json_encode($response);
        die();


        break;
    case 'adjust_affiliates':
        $response = array(
            'html' => 'Affiliate ID is required',
            'status' => 'FAIL'
        );
        if (isset($_POST['aff_id'])) {
            $orderCount = $_POST['value'];
            $fromDate = $_POST['from_date'];
            $toDate = $_POST['to_date'];
            $orderList = multi_query_assoc("SELECT id, user_id  FROM `mem_order` u WHERE (`hasoffers_aff_id` IS NULL OR `hasoffers_aff_id`='' OR `hasoffers_aff_id`='0') AND date BETWEEN '" . $fromDate . "' AND '" . $toDate . " 23:59:59'");
            if (count($orderList) < $orderCount) {
                $response['html'] = 'There are not enough orders between ' . $fromDate . ' and ' . $toDate . ' to add';
            } else {
                foreach ($orderList as $key => $orderObj) {
                    if ($key >= $orderCount) {
                        break;
                    }
                    $userObj = new umUser();
                    $userObj->userID = $orderObj['user_id'];
                    if ($userObj && $userObj->get_user(true)) {
                        $userObj->hasoffers_aff_id = $_POST['aff_id'];
                        $userObj->adjust_aff_id = 1;
                        $userObj->update_user();
                        $order = new order;
                        $order->update_by_id($orderObj['id'], array(
                            'hasoffers_aff_id' => $_POST['aff_id'],
                            'adjust_aff_id' => 1
                        ));
                    }
                }
                $response = array(
                    'status' => 'OK',
                    'html' => 'Update complete'
                );
            }
        }
        echo json_encode($response);
        die();
        break;
    case 'adjust_affiliates_undo':
        $response = array(
            'html' => 'Order ID is required',
            'status' => 'FAIL'
        );
        if (isset($_POST['aff_id'])) {
            multi_query_assoc("UPDATE `mem_order` SET `hasoffers_aff_id`=null,`adjust_aff_id`=null WHERE `id`=" . $_POST['aff_id']);
            $response = array(
                'status' => 'OK',
                'html' => 'Undo complete'
            );
        }
        echo json_encode($response);
        die();
        break;
    case 'bank_info':

        if (!isset($_POST['order'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $order = new order;
        $user_id = $_POST['order'];

        $history = $order->getBankHistory($user_id);
        $content = "";
        $i = 0;
        foreach ($history as $row) {
            if (!$row->amount) {
                continue;
            }
            $i++;
            if ($i > 2) {
                $i = $i % 2;
            }
            $content.="<tr class=\"dataRow{$i} drow\">
                            <td>{$row->bankname}</td>
                            <td>" . date("Y-m-d", strtotime($row->hdate)) . "</td>
                            <td>{$row->amount}</td>
                      </tr>";
        }
        $html = '<table width="100%" cellspacing="0" cellpadding="5" class="listTable">
                    <tr class="captionRow">
                        <td>Bank</td>
                        <td>Date</td>
                        <td>Amount</td>
                    </tr>
                    ' . $content . '
                </table>';
        $response = array("status" => "OK", "html" => $html);

        echo json_encode($response);
        die();

        break;
    case 'note_info':

        if (!isset($_POST['order'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $order = new order;
        $user_id = $_POST['uid'];

        $note = $order->getNote($user_id);

        $response = array("status" => "OK", "html" => $note);

        echo json_encode($response);
        die();

        break;
    case 'flag_as_fraudulent':

        if (!isset($_POST['uid'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $uid = $_POST['uid'];
        $fraudulent_flag = $_POST['fraudulent_flag'];
        if ($fraudulent_flag == 0) {
            $sql = "UPDATE `mem_user` SET `fraudulent_flag` = '0', `fraudulent_investigated` = '0' WHERE `UserID`={$uid}";
        } else if ($fraudulent_flag == 1) {
            $sql = "UPDATE `mem_user` SET `fraudulent_flag` = '1', `fraudulent_investigated` = '0' WHERE `UserID`={$uid}";
        }

        mysql_query($sql);

        $order = new order;
        $af = new antifraud;

        $orderList = multi_query_assoc("SELECT * FROM `mem_order` u WHERE user_id={$uid}");
        if (count($orderList) < $orderCount) {
            
        } else {
            foreach ($orderList as $key => $orderObj) {
                $data = $af->check_order($orderObj['id'], true);
            }
        }
        $response = array("status" => "OK", "html" => $fraudulent_flag);

        echo json_encode($response);
        die();

        break;
    case 'make_investigate_affid':

        if (!isset($_POST['affid'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }


        $affId = $_POST['affid'];
        $from_date = $_POST['from_date'];
        $to_date = $_POST['to_date'];

        $orders = multi_query_assoc("SELECT UserID FROM `mem_user` u WHERE  `hasoffers_aff_id`='" . $affId . "'");

        if (count($orders) > 0) {
            foreach ($orders as $order) {
                $orderId = $order['UserID'];
                $sql = "UPDATE `mem_user` SET `fraudulent_investigated` = fraudulent_flag WHERE UserID={$orderId}";

                mysql_query($sql);
            }
        }
        $response = array("status" => "OK");
        echo json_encode($response);
        die();
        break;
    case 'make_ignore_highlight':
        if (!isset($_POST['affid'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $affId = $_POST['affid'];
        $ignore = $_POST['ignoreflag'];
        $sql = "UPDATE `mem_affiliate` SET `ignore_highlight`= {$ignore} WHERE aff_id={$affId}";
        mysql_query($sql);

        $response = array("status" => "OK");
        echo json_encode($response);
        die();
        break;
    case 'editnote_affid':
        if (!isset($_POST['affid'])) {
            $response = array("status" => "ERR", "error" => "not enough data");
            echo json_encode($response);
            die();
        }

        $affId = $_POST['affid'];
        $note = $_POST['note'];
        $sql = "UPDATE `mem_affiliate` SET `note`= '{$note}' WHERE aff_id={$affId}";
        mysql_query($sql);

        $response = array("status" => "OK");
        echo json_encode($response);
        die();
        break;
    default:die("unknown action");
        break;
}