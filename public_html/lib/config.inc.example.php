<?php
#ini_set('display_errors', '0');     # don't show any errors...
#error_reporting(E_WARNING | E_ERROR);  # ...but do log them

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'session.php');
//get the tracker tool as well
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tracker.class.php');

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper_functions.php');


define('HOST_PROTOCOL',		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
define('HOST_PORT',			$_SERVER['SERVER_PORT']);
define('HOST_PORT_ALIAS',	(HOST_PROTOCOL == 'https' && HOST_PORT == 443) || (HOST_PROTOCOL == 'http' && HOST_PORT == 80) ? "" : HOST_PORT);
define('HOST_DOMAIN',		$_SERVER['HTTP_HOST']);
define('HOST_ROOT_DIR',		'');
define('HOST_DOMAIN_URL',	HOST_PROTOCOL . "://" . HOST_DOMAIN . (HOST_PORT_ALIAS == '' ? '' : (":" . HOST_PORT_ALIAS)));
define('HOST_ROOT_URL',		HOST_DOMAIN_URL . HOST_ROOT_DIR);
define('CURRENT_URL',		HOST_DOMAIN_URL . $_SERVER['REQUEST_URI'] );
define('ROOT_DIR',              dirname(__FILE__)."/../");
define('LOG_DIR',               dirname(__FILE__)."/../../bglogs/");

$cfg['runconfig'] = 'dev';

$cfg['site']['name'] = 'Bedroom Guardian';
$cfg['site']['autoEnable'] = 1;
$cfg['site']['openSignUp'] = 1;
$cfg['site']['requireVerification'] = 0;

$cfg['site']['url_protocoless'] = '//bg.dev';
$cfg['site']['url'] = 'http://bg.dev';
$cfg['site']['url_ssl'] = 'http://bg.dev';

$cfg['site_mobile']['url'] = 'http://bg.dev';
$cfg['site_mobile']['url_ssl'] = 'http://bg.dev';

$cfg['site']['root'] = $_SERVER['DOCUMENT_ROOT'];
$cfg['site']['folder'] = '/';
$cfg['site']['cookieDomain'] = '';
$cfg['site']['cookiePrefix'] = 'mem_';
$cfg['site']['cookieToken'] = '739e1f65a9b83e064f3067514d60da80';
$cfg['site']['adminGroupIDs']['0'] = 1;

$cfg['site']['trainingAreaTemplateFolder'] = $cfg['site']['root'] . $cfg['site']['folder'] . "templates/training_area/";
$cfg['site']['trainingAreaTemplateUrl'] = $cfg['site']['url'] . $cfg['site']['folder'] . "templates/training_area/";

define('SITE_ROOT_HTTP', $cfg['site']['url'] . $cfg['site']['folder']);
define('SITE_ROOT_HTTPS', str_replace('http://', 'https://', SITE_ROOT_HTTP));

$cfg['site']['SESS_URL_CLASS_NAME'] = "session_link";

$cfg['site']['MyProfile'] = 'My Profile';

$cfg['language'] = 'eng';
$cfg['languages']['0']['id'] = 'eng';
$cfg['languages']['0']['display'] = 'English';

//$cfg['email']['systemEmail'] = 'hotmoney819@gmail.com';
$cfg['email']['systemEmail'] = 'vlad.2hex.toma@gmail.com';
$cfg['email']['sendmail'] = 1;
$cfg['email']['HTML'] = 1;
$cfg['email']['smtp'] = '';
$cfg['email']['port'] = 25;
$cfg['email']['auth'] = 1;
$cfg['email']['user'] = '';
$cfg['email']['password'] = '';

$cfg['database']['server'] = 'localhost';
$cfg['database']['port'] = 3306;

$cfg['database']['dbName'] = 'bedroomguardian';
$cfg['database']['forumDBName'] = 'none'; 
$cfg['database']['user'] = 'bedroomguardian';
$cfg['database']['password'] = 'bedroomguardianpass';




$cfg['database']['prefix'] = 'mem_';
$cfg['database']['backupFolder'] = $cfg['site']['root'].$cfg['site']['folder']."admin/backup";

$cfg['group']['adminTemplateAccessGroupIds'] = '1,5,6,7,9,10';
$cfg['group']['superAdmin'] = 10;
$cfg['group']['admin'] = 1;
$cfg['group']['bronze'] = 2;
$cfg['group']['silver'] = 3;
$cfg['group']['gold'] = 4;
$cfg['group']['fullfillment'] = 5;
$cfg['group']['coachingfloor'] = 6;
$cfg['group']['customerservice'] = 7;

$cfg['paypal']['API_USERNAME']='###';
$cfg['paypal']['API_PASSWORD']='###';
$cfg['paypal']['API_SIGNATURE']='###';
$cfg['paypal']['SUBJECT']='';

$cfg['prices']['bronze'] = "97,59.94,39.94,24.94,36.95";
$cfg['prices']['gold'] = "49,157,297,49.99,30,98";

$cfg['pssc']['publishid'] = "ONLI";

$cfg['pages']['rollback_limit'] = 5;

$cfg['maxmind_key'] = '9xRNTbQEfEeV';//admin@bedroomguardian.com
$cfg['maxmind_fraud_request_type'] = 'premium';//standard or premium

$cfg['mailchimp_api_key'] = '915a2ab86f0b028a0c50c385844c81c4-us7';
$cfg['mailchimp_buyers_list'] = '9fc2e98840';
$cfg['mailchimp_partial_list'] = '31713fee09';


$cfg['bundle_sales'] = false;

$cfg['product_price'] = 9.95;
$cfg['product_price_rebill'] = 19.90;
$cfg['product_price_rebill_text'] = "ninteen dollars ninety";
$cfg['shipping_price_non_us'] = 12.95;


$cfg['shipping_price'] = 0; //US DEFAULT


$cfg['year_upsell_price'] = 74;
$cfg['stinkstopper_upsell_price'] = 24.95;
$cfg['stinkstopper_upsell_qty'] = 3;
$cfg['couch_upsell'] = 8.95;
$cfg['travel_upsell'] = 8.85;
$cfg['shipping_upsell'] = 5.95;
$cfg['rebill_period'] = 60;//days
$cfg['path3_default_qty'] = 3;

$cfg['yearly_qty'] = 6;

$cfg['disable_paypal'] = false;

// Place here merchants that will return a 'failed' response, by ID - separated by commas
// Something like: 31, 36
$cfg['soft_disable_merchants'] = '-1,-2';

// array to enable the retrying of rebills per merchant
$cfg['enable_rebill_retry'] = true;

// how many months to retry
$cfg['rebill_retry'] = 6;


require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'merchant_preselect.php');

$cfg['enable_payment'] = false;