<?php
include_once("../lib/security.inc.php");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/user.class.php");
$con = connect_database();
$user = new umUser();
if (isset($_POST['user_id']) && $_POST['user_id'] > 0) {
    $user->userID = ($_POST['user_id']);
} else {
    die("ILLEGAL ACCESS!");
}
$data = $user->get_user_info_by_id($user->userID);
if ($data == FALSE) die("ILLEGAL ACCESS");



$notes = $_POST["notes"];

$user->notes = $notes;

$user->update_notes_user();

die(db_escape_characters($notes));



?>
