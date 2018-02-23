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
	<div class="section" id="block-section11">
    	<?php 
		error_reporting(0);
		session_start();
		if ($_SESSION['ACK_ERROR'] != NULL) echo '<center><div style="color:#fff">'.$_SESSION['ACK_ERROR'].'</div></center>';
		$_SESSION['ACK_ERROR'] = NULL;
		?>
		<div class="container">
			<div class="content_page hepa-fiterbg">
			
				<h2 class="lato-black wait-titl">Wait! Limited Time Special!</h2>
			
				<img src="../assets/images/bb-calendar.png" alt="" class="imgright bbcalen" />
			
				
				
				<h3 class="page-sub-titl text-center">For only <span class="text-linethr">$74</span> <span class="codrd">$37</span> Protect Your Bed For 6 Months
					For Less Than $0.20 Cents Per Day Using A New Premium Formulation Not Available With Regular Bug Shield Pro! </h3>
				<div class="tim-spec">
				
					<ul>
						
						<li><span>24/7</span> Protection</li>
						
						<li><span>6</span> Months</li>
						
						<li><span>0</span> Bed Bugs! </li>
					
					
					</ul>
				
				
				</div>
				
				<div class="clear"></div>
				
				<div class="btfrm">
					<div class="left-so-arr"></div>
                    	<form action="../add_offer.php?offer=15&amp;upselltype=50&amp;path=ap" method="POST">
                            <input type="hidden" name="quantity" value="1"> 
                            <a href="#" class="upsell-ato" id="form-submit-button" onClick="document.forms[0].submit();">Add to my Order<small>A one time price of $37.00 will be added to your order.</small></a>
                        </form>
						
						
				</div>
				<p class="nthnk"><a href="bug-shield-pro-upsell-2.php" class="notintr">No Thanks! I am not interested in completely eradicating bed bugs!</a></p>
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