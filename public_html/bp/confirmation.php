<?php
include_once("../lib/config.inc.php");
//_state_redirect();
include_once("../lib/database.inc.php");
include_once("../lib/form.class.php");
include_once("../lib/country.class.php");
include_once("../lib/usertemp.class.php");

$_SESSION['datetimer'] = NULL;

$con = connect_database();

$t = new tracker;
$tracker = $t->get_data();

if(!isset($tracker["tempuser_id"]) && !isset($tracker["user_id"])) {
	header("Location:index.php");die();
}

//add a cookie to make sure we redirect this user in the future
supersession("a3cl", md5(time()), time() + 3600 * 24 * 365, '/');

//check if we need to track the upsell in third party
$upsell_price = $tracker["upsell_price"];

$total = $cfg['shipping_price_bp'];
//$original_qty = $tracker['original_qty'];
$original_qty = $tracker["productquantitypackage"];

//echo "<pre>".print_r($tracker, 1)."</pre>";
//$total = $original_qty * $cfg['unit_price'];
$total = $cfg['unit_price'];

$t->clear_data();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>BugShieldPRO</title>
<meta name="description" content="BugShieldPRO">
<meta name="author" content="SMD">

<link rel="stylesheet" href="../assets/stylesheets/style.css">
<link rel="stylesheet" type="text/css" href="../assets/stylesheets/responsive.css">
<link rel="stylesheet" type="text/css" href="../assets/stylesheets/responsive2.css">

<link rel="stylesheet" href="../assets/stylesheets/normalize.css">
<link rel="stylesheet" href="../assets/stylesheets/demo.css">
<!-- Pushy CSS -->
<link rel="stylesheet" href="../assets/stylesheets/pushy.css">
<!--[if lt IE 9]>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
<![endif]-->
</head>
<body>
	<div class="top">
		<div class="container">
			<div class="top-logo">
				<a href="#"><img src="images/bugshieldpro-logo.png" alt=""/></a>
			</div>
		</div>
	</div>
	<div class="section" id="block-section11">
		<div class="container">
			<div class="content_page hepa-fiterbg">
			
				<img src="images/bb-calendar.png" alt="" class="imgright bbcalen" />
			
				<h1 class="page-head-titl">Thank you for your order.</h1>
				
				<h2 class="page-sub-titl">Your Order Number Is: <?php echo str_pad($tracker['order_id'], 11, '0', STR_PAD_LEFT); ?></h2>
				
				<p>If you have any questions please contact us at:1-855-543-0054</p>
                
                <style>
				.table-orders {width:100%;}
					.table-orders th {background:#fff; color:#004c97; padding:10px;}
						.table-orders .item-lists {font-size:14px; text-align:center;}
							.table-orders .item-lists td {padding-top:10px; padding-bottom:10px;}
							.table-orders .item-lists strong {font-weight:normal;}
							.table-orders .item-lists td:last-child {padding-right:10px;}
							.table-orders tr:nth-child(even) {background:#80a6cb; color:#FFF;}
				
				@media (max-width:480px) {
					.table-orders {margin-left:-15px;}
					.table-orders .item-lists {font-size:12px !important;}
						.table-orders th {padding:5px !important; font-size:14px;}
				}
				</style>
                
                <table class="table-orders">
               		<tr>
                        <th>QTY</th>
                        <th>Item Description</th>
                        <th>Price</th>
                    </tr>
                    
                    <tr class="item-lists">
                    	<td><?php echo $original_qty; ?></td>
                    	<td><?php echo $cfg['product_text_pick_'.$_SESSION['productid']]; ?></td>
                    	<td align="right">$<?php echo $original_qty * $cfg['product_price_pick_'.$_SESSION['productid']]; ?></td>
                    </tr>
                    
                    <?php 
					$subtotal = $original_qty * $cfg['product_price_pick_'.$_SESSION['productid']];
					?>
                    
                    <?php if(isset($tracker['has_laundrykit'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['laundrykit_text_pick_'.$tracker["laundrykit_id"]]; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['laundrykit_price_pick_'.$tracker["laundrykit_id"]], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['laundrykit_price_pick_'.$tracker["laundrykit_id"]]; ?>
                    <?php } ?>
                    
                    <?php if(isset($tracker['has_luxuriousmattress'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['luxuriousmattress_text']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['luxuriousmattress_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['luxuriousmattress_price']; ?>
                    <?php } ?>
                    
                    <?php if(isset($tracker['has_upsell_1'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_1_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_1_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_1_price']; ?>
                    <?php } ?>

                    <?php if(isset($tracker['has_upsell_1_1'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_1_1_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_1_1_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_1_1_price']; ?>
                    <?php } ?>
                    
                    <?php if(isset($tracker['has_upsell_1_50'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_1_50_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_1_50_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_1_50_price']; ?>
                    <?php } ?>

                    <?php if(isset($tracker['has_upsell_2'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_2_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_2_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_2_price']; ?>
                    <?php } ?>
                    
                    <?php if(isset($tracker['has_upsell_3'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_3_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_3_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_3_price']; ?>
                    <?php } ?>
                    
                    <?php if(isset($tracker['has_upsell_4'])) { ?>
                    <tr class="item-lists">
                    	<td>1</td>
                    	<td><?php echo $cfg['upsell_4_description']; ?></td>
                    	<td align="right">$<?php echo number_format($cfg['upsell_4_price'], 2); ?></td>
                    </tr>
                    <?php $subtotal = $subtotal + $cfg['upsell_4_price']; ?>
                    <?php } ?>
                    
                    <tr style="background:#2061a3;">
                    	<td colspan="3" align="right" style=" padding-top:10px; padding-bottom:10px; padding-right:10px; color:#FFF; font-size:15px;">Subtotal $<?php echo $subtotal; ?></td>
                    </tr>
                    <tr style="background:#bfbfbf;">
                    	<td colspan="3" align="right" style=" padding-top:10px; padding-bottom:10px; padding-right:10px; color:#FFF; font-size:15px;">Shipping & handling $<?php echo number_format($cfg['shipping_price_bp'], 2); ?></td>
                    </tr>
                    <tr style="background:#ff7c00;">
                    	<td colspan="3" align="right" style=" padding-top:10px; padding-bottom:10px; padding-right:10px; color:#FFF;">Total $<?php echo $subtotal + $cfg['shipping_price_bp']; ?></td>
                    </tr>
                    
               </table>
				
				
				<div class="clear"></div>
				
				</div>
			

	</div>
	</div>
	<div class="footer-section lato-reg">
		<div class="container">
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">Bug Shield <span class="bspro">Pro</span></h3>
					
					<ul>
						<li><a href="/bp">Home</a></li>
						<li><a href="/bp#block-section5">How It Works</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">ABOUT</h3>
				
					<ul>
						<li><a href="/bp/contact.php">Contact</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					<h3 class="fwid-titl">INFORMATION</h3>
				
					<ul>
						<li><a href="/bp/terms-and-conditions.php">Terms and Conditions</a></li>
						<li><a href="/bp/privacy-policy.php">Privacy Policy</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				
				<div class="fwidgt-cont">
					<p>Copyright &copy; 2017 Bug Shield <span class="bspro">Pro</span>. All Rights Reserved.</p>
					<p>Customer Service Number:<br/>1-855-543-0054</p>
				</div>
				
			</div>
		</div>
	</div>
	<?php include("../inc/zopim_chat.php"); ?>
	</body>
</html>