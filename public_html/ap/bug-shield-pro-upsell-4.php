<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>BugShieldPRO</title>
<meta name="description" content="BugShieldPRO">
<meta name="author" content="SMD">

<link rel="stylesheet" type="text/css" href="../assets/stylesheets/style.css?v=2017101600">
<link rel="stylesheet" type="text/css" href="../assets/stylesheets/responsive.css?v=2017101600">
<link rel="stylesheet" type="text/css" href="../assets/stylesheets/responsive2.css?v=2017101600">
<link rel="stylesheet" type="text/css" href="../assets/stylesheets/ap.css?v=2017110700">

<link rel="stylesheet" href="../assets/stylesheets/normalize.css">
<link rel="stylesheet" href="../assets/stylesheets/demo.css">
<!-- Pushy CSS -->
<link rel="stylesheet" href="../assets/stylesheets/pushy.css">
<!--[if lt IE 9]>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
<![endif]-->
</head>
<body>
	<div style="background:#000; width:100%; height:100%; position:fixed; z-index:998; opacity:0.6; top:0; bottom:0; display:none;" class="loader-bg"></div>
	<img src="../assets/images/ajax-loader.gif" style="position:fixed; z-index:999; top:0; bottom:0; left:0; right:0; margin-top:auto; margin-bottom:auto; margin-left:auto; margin-right:auto; display:none;" class="loader-gif">
	<div class="top">
		<div class="container">
			<div class="top-logo">
				<a href="#"><img src="../assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
			<a class='customer-care' href='tel:+18555430054'>Customer care 1-855-543-0054</a>
		</div>
	</div>
	<div class="section upsell-4" id="block-section11">
    	<?php 
		error_reporting(0);
		session_start();
		if ($_SESSION['ACK_ERROR'] != NULL) echo '<center><div style="color:#fff">'.$_SESSION['ACK_ERROR'].'</div></center>';
		$_SESSION['ACK_ERROR'] = NULL;
		?>
		<div class="container">
			<div class="content_page hepa-fiterbg">
				
			
				<h1 class="page-head-titl pht text-center">
					One <span>final</span> step that you must confirm...
				</h1>

				<h3 class="page-sub-titl text-center">
					STOP Those Bed Bugs FAST With Our Enhanced Rush Fulfillment, Which Guarantees Your Order 
Is Shipped Today And Arrives In Just Days!</h3>

<h3 class="page-sub-titl text-center">Click The Yes! Add To Order Button. It's Just <span class="codrd">$5.95</span></h3> 				

				
				<div class="colm-3">
					<img src="../assets/images/truck.png" alt="" class="imgcenter" />
				</div>
				<div class="colm-3">
					<img src="../assets/images/dbox.png" alt="" class="imgcenter" />
				</div>
				<div class="colm-3">
					<img src="../assets/images/sprayd.png" alt="" class="imgcenter" />
				</div>
				
					
				<div class="clear"></div>
				
				<div class="btfrm">
					<div class="left-so-arr yatobtn"></div>
						<form action="../add_offer.php?offer=4&amp;path=ap" method="POST">
                            <input type="hidden" name="quantity" value="1"> 
                            <a href="#" class="upsell-ato yato-btn" id="form-submit-button" onClick="document.forms[0].submit();">YES! Add to Order</a>
                        </form>
				</div>
				<p class="nthnk"><a href="confirmation.php" class="notintr">No Thanks! I am not interested in completely eradicating bed bugs!</a></p>
			
			</div>
			

	</div>
	</div>
	<div class="footer-section lato-reg">
		<div class="container">
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">Bug Shield <span class="bspro">Pro</span></h3>
					
					<ul>
						<li><a href="/ap">Home</a></li>
						<li><a href="/ap#block-section5">How It Works</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">ABOUT</h3>
				
					<ul>
						<li><a href="/ap/contact.php">Contact</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					<h3 class="fwid-titl">INFORMATION</h3>
				
					<ul>
						<li><a href="/ap/terms-and-conditions.php">Terms and Conditions</a></li>
						<li><a href="/ap/privacy-policy.php">Privacy Policy</a></li>
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
    
	<script type="text/javascript">
    $(document).ready(function() {
    	$('#form-submit-button, .notintr').click(function() {
			$('.loader-bg').css('display', 'block');
			$('.loader-gif').css('display', 'block');
		});
    });
    </script>
	</body>
</html>