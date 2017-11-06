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
	<div class="site-overlay"></div>
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
			<div class="content_page lux-matt">
			
				<h1 class="page-head-titl">
					It all started in your bed, <br/>
					it has to end there too...
				</h1>
				
				<h3 class="page-sub-titl">
					Uncover how a mattress cover can help you <br />
					get rid of a bed bug infestation
				</h3>
				
				<img src="../assets/images/bed-seal.png" alt="" class="imgright" />
			
				<strong><span class="codrd">WARNING:</span></strong> Bed bugs are hiding in your sleeping space!
				
				<p>You have taken steps to eradicate your bed bug infestation, however there are still bed bugs hiding in your house. They feast on your blood, so they will hide near you, near your bed. Most common places are under the box spring, your mattress or under your pillow.</p>

				<p>They might have laid eggs in your pillow and mattress covers and since spotting the eggs is nearly impossible it's imperative to completely replace all your covers. It is important to do that since only one bed bug egg will start a new infestation.</p>

				<p>An effective mattress encasement will seal all the bed bugs inside the mattress encasement, preventing them from biting you. This is the reason you need new covers, they will keep the bed bugs out.</p>

				<p>BugShieldPro luxurious mattress encasements enclose the entire bed and prevent bed bugs, dust mites and allergens from entering mattresses while also protecting from leaks and spills. Our top-of-line mattress covers are waterproof, bed bug bite-proof, machine washable, and feature locking zippers. </p>

				<p>Strapping it on your back like a backpack you will be a bed bug killing machine... it will suck every single egg, larvae or adult bed bug, trap them with a sealed filter and it won't let them out.</p>

				<p>Using it you will get bed bugs hidden deep in cracks and crevices you otherwise wouldn't be able to reach or spray.</p>
			
				<p><strong>Use a dedicated, comfortable, luxurious ANTI-BED-BUG mattress encasement</strong></p>
				
				<p>To combat bed bugs in the sleeping space and their eggs hiding in the covers, we have decided to develop an anti bed bug mattress encasement. It is water resistant and completely alergen proof. We have gone above and beyond to provide you with the ultimate bed bug resistant mattress encasement.</p>

				<p>But we have not lost focus on comfort. It is made out of premium linen and completely comfortable to sleep on.</p>

				<p>This is what people are saying about them:</p>

				<p>For a limited time we are offering the luxurious mattress encasements at 35% discount for ONLY: $57.95 per encasement as a one time fee.</p>
			
				<img src="../assets/images/bspro-reviews.png" alt="" class="imgcenter" />
				
				<div class="note-content">
					
					<p><strong><span class="codrd">NOTE:</span> This is a ONE TIME offer, you can NOT get this anywhere else, only on our website.</strong></p>
					
					<img src="../assets/images/guarantee-big.png" alt="" class="imgright guarbig" />
					
					<p>Even if you're skeptical the encasement is 100% bed bug resistant and has been proven to work over and over again.</p>

					<p>And the best part?</p>
					
					<p>You can get it for a special discounted price only this one time with a 15 day no questions asked money back guarantee.</p>

					<p>PS: Remember, this is a special discounted offer only available to our new members.</p>
				
				</div>
				
				<div class="btfrm">

                	<form action="../add_offer.php?offer=1&amp;luxuriousmattress=1&amp;path=ap" method="POST">
                        <input type="hidden" name="quantity" value="1"> 
                        
                        <div class="frm-field">
							<label>Select Mattress Size</label>
							<select>
								<option>California King</option>
							</select>
						</div>
                        
                        <div class="frm-field">
						<input type="submit" id="form-submit-button" value="Add to Cart" />
						</div>
                    </form>
						
					<div class="so-arr soarr2"></div>
				</div>
				
				<?php if (!isset($tracker["has_upsell_1"])) $plink = 'bug-shield-pro-upsell-1-50-off.php'; else $plink = 'bug-shield-pro-upsell-1-1.php'; ?>
				<p class="nthnk"><a href="<?php echo $plink; ?>" class="notintr">No Thanks! I am not interested in completely eradicating bed bugs!</a></p>
				
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