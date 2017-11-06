<?php
include_once ("../lib/config.inc.php");
include_once ("../lib/database.inc.php");
include_once ("../lib/form.class.php");
include_once ("../lib/country.class.php");

global $cfg;
	
$con = connect_database();
$field = new umField();
$field->fieldID = 8; //define country filed ID
$field->get_field_options();
$t = new tracker;
$tracker = $t->get_data();
$data = array(); //received data
$needed = array(
	"first_name",
	"last_name",
	"email",
	"phone",
	"address",
	"country",
	"city",
	"state",
	"zip"
);
$data_received = true;
$error_fields = array();

foreach($_POST as $key => $value) {
	if (in_array($key, $needed)) {
		if (is_array($value)) {
			$value = $value[0];
		}

		$tracker[$key] = htmlentities(strip_tags($value));
	}
}

foreach($needed as $need) {
	if (isset($tracker[$need]) && !empty($tracker[$need])) {
		$data[$need] = htmlentities(strip_tags($tracker[$need]));
	}
	else {
		$data_received = false;
		$error_fields[] = ucfirst($need);
	}
}

if (!$data_received) {
	$tracker['error_message'] = "The following fields were not filled in: " . implode(", ", $error_fields);
	$t->set_data($tracker);
	header("Location:bug-shield-pro-step-2.php");
	die();
}

$error_message = isset($tracker['error_message']) ? $tracker['error_message'] : "";
unset($tracker["error_message"]);
$t->set_data($tracker);


$postID = str_replace('pick-', '', $_POST['id']);
if (!isset($postID)) {
	header('location:bug-shield-pro-step-2.php');
} else {
	$exec = true;
	if ($postID == 1) $exec = false;
	else if ($postID == 2) $exec = false;
	else if ($postID == 3) $exec = false;
	else if ($postID == 4) $exec = false;
	else if ($postID == 5) $exec = false;
	
	if ($exec) {
		header('location:bug-shield-pro-step-2.php');
	}
}

$_SESSION['productid'] = $postID;
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
			<!--<div class="menu-btn-shadow">
				<button class="menu-btn">&#9776;</button>
			</div>-->
			<div class="top-logo">
				<a href="#"><img src="../assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
		</div>
	</div>
	<div class="section" id="block-section10">
        <div class="container">
            <h2 class="lato-black text-center order-steps">Please Enter Your Payment Details To Complete Your Order</h2>
            <h4 class="lato-reg text-center" style="color:#7a7a7a;">Home Size &gt; Pick Your Kit &gt; <span class="lato-bold active-pick">Checkout</span></h4>
        </div>
    </div>
	<div class="section text-center" id="block-section11">
		<div class="container">
		<div class="step-container">
			<p><img src="../assets/images/are-bed-bugs-now-crawling-into-your-beds.jpg" alt=""/></p>
		<div class="content-col-6">
			<div class="prd-order">
				<div class="prd-order-cont">
				<table>
					<tr>
						<th>Product</th>
						<th>Description</th>
						<th>Qty</th>
						<th>Price</th>
					</tr>
					<tr>
						<td><img src="../assets/images/kit-pick<?php echo $postID; ?>.png" alt=""/></td>
						<td>
                        	<?php echo $cfg['product_text_pick_'.$postID]; ?>
                        </td>
						<td>
						<div class="content-half chtop">
                        <select name="quantity" onChange="compute(this.value);">
                        	<?php for ($x = 1; $x <= 20; $x++) { ?>
                            	<option value="<?php echo $x; ?>"><?php echo $x; ?></option>
                            <?php } ?>
                        </select>
                        </div>
						<div class="content-half chbot"><strong>Subtotal:<br/>Shipping:</strong></div>
						</td>
						<td>
                        	<div class="content-half chtop"><strong>$<?php echo $cfg['product_price_pick_'.$postID]; ?></strong></div>
                            <div class="content-half chbot"><strong>$<span id="sub_total"><?php echo $cfg['product_price_pick_'.$postID]; ?></span></strong><br/><strong>$<?php echo $cfg['shipping_price_bp']; ?></strong></div></td>
					</tr>
					<tr>
						<td colspan="3">Total:</td>
						<td id="total_amount">$<?php echo $cfg['product_price_pick_'.$postID] + $cfg['shipping_price_bp']; ?></td>
					</tr>
				</table>
				</div>
				<div class="text-charges">
				
				<div class="send-today"></div>
				
				<img src="../assets/images/bugshieldpro-moneyback-step3.png" alt="" class="imgcenter">
				
				<!-- 
				
				<img src="../assets/images/moneyback.png" alt="" class="mbimg imgright" />
				
				<h3>Money Back Guarantee &amp; Terms</h3>
				
				
				<p>BugShield<span class="bspro">Pro</span> - Bed Bug Prevention Program. When you order today, you'll get our rush delivery and save 50%! YOUR INITIAL ORDER WILL COST A DISCOUNTED RATE OF $46.27. STARTING 60 DAYS FROM YOUR ORDER DATE, YOU'LL RECEIVE A NEW 60-DAY SUPPLY OF BUGSHIELDPRO EVERY 60 DAYS AT THE GUARANTEED LOW PRICE OF JUST $19.90 MULTIPLIED BY THE NUMBER OF UNITS ORDERED TODAY, which will 
conveniently be charged to the card you provide today unless you call to cancel. If you use a debit card, this recurring payment will be automatically deducted from the card's 
associated bank account. There is no commitment and no minimum to buy.</p>
				<p>To customize this program or future shipments and charges, call customer service during regular business hours which are 9 am to 5 pm Pacific Standard Time. Every BugShieldPro order comes with our 90-day Money Back Guarantee.</p>
				<p>You may call 1-855-543-0054 within 90 days to obtain a refund.</p> -->
				</div>
			</div>
		</div>
		<div class="content-col-4">
			
			<div class="ordr-form">
			
				<h3>FILL UP THE FORM TO <span>GET YOUR ORDER NOW!</span></h3>
				
				<p><img src="../assets/images/security.jpg" alt="" class="imgcenter" /></p>

				<form action='../payment.php' method="POST">
                	
                    <input type="hidden" id="qty" name="quantity" value="1">
                    <input type="hidden" name="price" value="<?php echo $cfg['product_price_pick_'.$postID]; ?>">
 
					<div class="form-field">
						<label class="wacc">We accept:</label> <img src="../assets/images/wacc.jpg" alt="" />
					</div>

					<div class="form-field">
						<label for="crdnumber">Card Number: </label> <input type='text' id='cc_number' name='cardnumber' autocomplete='off' required />
					</div>
					<div class="form-field">
						<label for="expdate">Expiration Date: </label> 
                        <select id='cc_expiration_month' name='expiration_month' required>
                            <option>01</option>
                            <option>02</option>
                            <option>03</option>
                            <option>04</option>
                            <option>05</option>
                            <option>06</option>
                            <option>07</option>
                            <option>08</option>
                            <option>09</option>
                            <option>10</option>
                            <option>11</option>
                            <option>12</option>
                        </select>
                        <select id='cc_expiration_year' name='expiration_year' required>
                        <?php for($i=0;$i<=17;$i++):?>
                        <option value="<?php echo (intval(date("Y"))+$i);?>"><?php echo (intval(date("Y"))+$i);?></option>
                        <?php endfor;?>
                    </select>
					</div>
					<div class="form-field">
						<label for="secode">Security Code: </label> <input type='text' id='cvv' name='cvv' required style="width:75px;" /> <img src="../assets/images/sc.jpg" alt="" style="width:36px;"/>
					</div>
					
					<label for="ycheck" class="yescheck"><input id="ycheck" type="checkbox" name="yes" value="yes" required> I am over 18 yrs of age and agree to the <a href="../terms-of-service.php">Terms of
Service</a> &amp; <a href="../privacy-policy.php">Privacy Policy</a>. I also understand that my initial order will be billed at a special discounted rate of $46.27 and I will have a refill mailed to me every 30 days, and charged $19.90 each time, until I decide to cancel.
					</label>
					
					<div class="form-field">
						<input type="submit" value="submit" />
					</div>
					
					<p><img src="../assets/images/secscan.png" class="imgcenter" alt=""/></p>
					
					<p class="text-center">Your card will be billed as *BugShieldPro*</p>
					
					
					<div class="btmgradient"></div>
				</form>
			
			</div>
			
		</div>
		</div>
		
		<div class="mobiguar" style="display:none;">
		<div class="text-charges">
				
				<div class="send-today"></div>
				
				
				
				<img src="../assets/images/bugshieldpro-moneyback-step3.png" alt="" class="imgcenter">
				
				<!-- <img src="../assets/images/moneyback.png" alt="" class="mbimg imgright">
				
				<h3>Money Back Guarantee &amp; Terms</h3>
				
				
				<p>BugShield<span class="bspro">Pro</span> - Bed Bug Prevention Program. When you order today, you'll get our rush delivery and save 50%! YOUR INITIAL ORDER WILL COST A DISCOUNTED RATE OF $46.27. STARTING 60 DAYS FROM YOUR ORDER DATE, YOU'LL RECEIVE A NEW 60-DAY SUPPLY OF BUGSHIELDPRO EVERY 60 DAYS AT THE GUARANTEED LOW PRICE OF JUST $19.90 MULTIPLIED BY THE NUMBER OF UNITS ORDERED TODAY, which will 
conveniently be charged to the card you provide today unless you call to cancel. If you use a debit card, this recurring payment will be automatically deducted from the card's 
associated bank account. There is no commitment and no minimum to buy.</p>
				<p>To customize this program or future shipments and charges, call customer service during regular business hours which are 9 am to 5 pm Pacific Standard Time. Every BugShieldPro order comes with our 90-day Money Back Guarantee.</p>
				<p>You may call 1-123-456-7890 within 90 days to obtain a refund.</p> -->
				</div>
		</div>
	</div>
	</div>
	<div class="section" id="block-section12">
		<div class="container">
					<div class="keepbed">
					
						<h1><span>Keep Bed Bugs Out</span> <br/>
						of your Home for Good</h1>
					
						<div class="keepbed-satis"></div>
					</div>
		</div>
	</div>
	<div class="footer-section lato-reg">
		<div class="container">
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">Bug Shield <span class="bspro">Pro</span></h3>
					
					<ul>
						<li><a href="../bug-shield-pro.php">Home</a></li>
						<li><a href="../bug-shield-pro.php#block-section5">How It Works</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">ABOUT</h3>
				
					<ul>
						<li><a href="../contact.php">Contact</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					<h3 class="fwid-titl">INFORMATION</h3>
				
					<ul>
						<li><a href="../terms-of-service.php">Terms of Service</a></li>
						<li><a href="../privacy-policy.php">Privacy Policy</a></li>
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
    <!-- jQuery -->
    <script src="../assets/javascripts/jquery.min.js"></script>
    <script src="../assets/javascripts/pushy.min.js"></script>
    
    <script type="text/javascript">
    function compute(quantity) {
		var product_price = <?php echo $cfg['product_price_pick_'.$postID]; ?>;
        var product_shipping = <?php echo $cfg['shipping_price_bp']; ?>;
        
        sub_total = (product_price * quantity);
        total_amount = (product_price * quantity) + product_shipping;
    
        $('#qty').val(quantity);
        $('#sub_total').html(sub_total.toFixed(2));
        $('#total_amount').html(total_amount.toFixed(2));
    }
    </script>
	</body>
</html>