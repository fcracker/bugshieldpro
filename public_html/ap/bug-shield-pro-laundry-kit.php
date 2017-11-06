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
	<div style="background:#000; width:100%; height:100%; position:fixed; z-index:998; opacity:0.6; top:0; bottom:0; display:none;" class="loader-bg"></div>
	<img src="../assets/images/ajax-loader.gif" style="position:fixed; z-index:999; top:0; bottom:0; left:0; right:0; margin-top:auto; margin-bottom:auto; margin-left:auto; margin-right:auto; display:none;" class="loader-gif">
	<div class="top">
		<div class="container">
			<div class="top-logo">
				<a href="#"><img src="../assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
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
			<div class="content_page laundry-kt">
				<h1 class="page-head-titl">
					People Nationwide Agree: <br/>
					<span>BugShieldPro Works </span> Wonders
				</h1>
				
				<h3 class="page-sub-titl">
					DO YOU WANT TO USE A UNIQUE ARSENAL <br />
					WE DEVELOPED TO PROTECT YOUR CLOTHES <br />
					&amp; LINEN FROM BED BUGS? ...AND SEE EXACTLY <br />
					HOW YOU CAN STEP UP AND COMPLETELY <br />
					ELIMINATE YOUR INFESTATION?
				</h3>
				
				<div class="laundkit-steps">
				
					<ul>
						
						<li> Payment Information</li>
						
						<li>Claim Discounts Offer 1/2</li>
						
						<li>Order Confirmation</li>
					
					
					</ul>
				
				
				</div>
				
				 <img src="../assets/images/duo-lk-v2.png" alt="" class="imgright" /> 
				
				
				<p>If you answered to any of these questions "YES" then you have to read this special offer... Did you know even if you completely kill off bed bugs using our spray they might still be hiding on your clothes and linen just waiting to REINFEST your home? When bed bugs infest an environment and have started spreading further then the mattress and bed frame, there is a tendency to find them in closets and dressers, living and hatching eggs in the bedding, linen and in the folds of clothing. </p>

				<p>As part of the comprehensive approach, it is imperative to clean and launder all the clothing in your home, this needs to be done in a manner that kills the adults and eggs, without leaving a trace of even one left alive, as that will cause the infestation to continue and spread. To combat that we have developed a unique and extremely effective laundry detergent used by thousands of hospitals, hotels and people like you nationwide. Any items added here come at NO ADDITIONAL shipping costs! </p>
				
				<div class="trio-kit">
				
					<div class="laun-kit">

							<div class="laun-kit-titl">
								Laundry Kit
							</div>
							
							<div class="inner-laun-kit">
							
								<img src="../assets/images/laundry-kit-1.png" alt="" />
								
								<div class="laun-kit-pric">
									Price $99.96
								</div>
								
								<div class="laund-kit-set">
									3 month supply of BugShield<span class="bspro">Pro</span> detergent
								</div>
								
								<div class="laun-kit-pric-ea">
									$33.32/each <span>SAVE 15%</span>
								</div>
								
                                <form id="form1" action="../add_offer.php?offer=1&amp;laundrykit=1&amp;pid=1&amp;path=ap" method="POST">
                                    <input type="hidden" name="quantity" value="1"> 
                                    <a href="#" class="addbtn" onClick="document.getElementById('form1').submit();">Add To Cart</a>
                                </form>
								
							
							</div>
					
					</div>
					
					<div class="laun-kit">
					
							<div class="laun-kit-titl">
								Ultimate Laundry Kit
							</div>
							
							<div class="inner-laun-kit">
							
								<img src="../assets/images/laundry-kit-2.png" alt="" />
								
								<div class="laun-kit-pric">
									Price $113.84
								</div>
								
								<div class="laund-kit-set">
									4 month supply of BugShield<span class="bspro">Pro</span> detergent
								</div>
								
								<div class="laun-kit-pric-ea">
									Only $28.46/each  <span>SAVE 25%</span>
								</div>
								
								<form id="form2" action="../add_offer.php?offer=1&amp;laundrykit=1&amp;pid=2&amp;path=ap" method="POST">
                                    <input type="hidden" name="quantity" value="1"> 
                                    <a href="#" class="addbtn" onClick="document.getElementById('form2').submit();">Add To Cart</a>
                                </form>
							
							</div>
					
					</div>
					
					<div class="laun-kit">
					
							<div class="laun-kit-titl">
								Basic Laundry Kit
							</div>
							
							<div class="inner-laun-kit">
							
								<img src="../assets/images/laundry-kit-3.png" alt="" />
								
								<div class="laun-kit-pric">
									Price $69.94
								</div>
								
								<div class="laund-kit-set">
									2 month supply of BugShield<span class="bspro">Pro</span> detergent
								</div>
								
								<div class="laun-kit-pric-ea">
									$34.97/each 
								</div>
								
								<form id="form3" action="../add_offer.php?offer=1&amp;laundrykit=1&amp;pid=3&amp;path=ap" method="POST">
                                    <input type="hidden" name="quantity" value="1"> 
                                    <a href="#" class="addbtn" onClick="document.getElementById('form3').submit();">Add To Cart</a>
                                </form>
							
							</div>
					
					</div>
				
				</div>
				
				<div class="btm-text">
				<p class="text-center"><a href="bug-shield-pro-luxurious-mattress.php" class="notintr">No Thanks! I am not interested in completely eradicating bed bugs!</a></p>
				</div>
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
    	$('.addbtn, .notintr').click(function() {
			$('.loader-bg').css('display', 'block');
			$('.loader-gif').css('display', 'block');
		});
    });
    </script>
	</body>
</html>