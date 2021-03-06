<?php 
include_once('../inc/func.php'); 
$geo = get_geo_data();
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
<?php include("../inc/google_pixel.php"); ?>
</head>
<body>
	<div class="top">
		<div class="container">
			<div class="top-logo">
				<a href="bug-shield-pro.php"><img src="assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
		</div>
	</div>
	<div class="section contact_page" id="block-section11">
		<div class="container">
			<div class="content_page ">
				
				<h1>Contact Us</h1>

				<p>Please relay any questions you may have pertaining our above stated policies to our Customer Service Department by calling 1-855-543-0054 (Monday through Friday 9 AM to 5 PM Pacific Standard Time) or sending a letter to:</p>
                
                FEEBUS ENTERPRISES, LLC<br>
                3524 SILVERSIDE ROAD SUITE 35B<br>
                WILMINGTON, DE 19810-4929<br>

				<p>Send product returns to:</p>

				<?php if ($geo['country'] != 'Thailand') { ?>
				Feebus Enterprises LLC <br>
                130 Central Avenue #V7<br>
                Dover, NH 03820<br>
                United States<br>
                <?php } else { ?>
                FEEBUS ENTERPRISES, LLC<br>
                3524 SILVERSIDE ROAD SUITE 35B<br>
                WILMINGTON, DE 19810-4929<br>
                <?php } ?>

			</div>
			

	</div>
	</div>
	<div class="footer-section lato-reg">
		<div class="container">
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">Bug Shield <span class="bspro">Pro</span></h3>
					
					<ul>
						<li><a href="bug-shield-pro.php">Home</a></li>
						<li><a href="#block-section5">How It Works</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">ABOUT</h3>
				
					<ul>
						<li><a href="contact.php">Contact</a></li>
					</ul>
				</div>
			</div>
			<div class="col-4">
				<div class="footer-widget">
					<h3 class="fwid-titl">INFORMATION</h3>
				
					<ul>
						<li><a href="terms-and-conditions.php">Terms and Conditions</a></li>
						<li><a href="privacy-policy.php">Privacy Policy</a></li>
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
	</body>
</html>