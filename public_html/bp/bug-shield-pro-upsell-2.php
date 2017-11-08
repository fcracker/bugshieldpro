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
<script src="../assets/javascripts/jquery.min.js"></script>
<script src="../assets/javascripts/jquery.countdown.min.js"></script>
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
			<div class="content_page hepa-fiterbg">
				<h4 class="limitime text-center">LIMITED TIME SPECIAL OFFER EXPIRES IN </h4>
				<div class="countlimit">
				<span id="clock"></span>
				<?php 
				if (!isset($_SESSION['datetimer'])) {
					$_SESSION['datetimer'] = date('Y/m/d h:i:s', strtotime('+1445 minutes'));
				}
				$_SESSION['datetimer'] = date('Y/m/d h:i:s', strtotime('+1441 minutes'));
				?>
				<script type="text/javascript">
				exec = true
				$('#clock').countdown('<?php echo $_SESSION['datetimer']; ?>', function(event) {
					
					if (event.strftime('%M') == '00' && event.strftime('%S') == '00') {
						$(this).html(event.strftime('00 min and 00 sec'));
						exec = false
					}
					
					if (exec)
						$(this).html(event.strftime('%M min and %S sec'));
					else
						$(this).html(event.strftime('00 min and 00 sec'));
				});
				</script>
					
				</div>
				<h1 class="page-head-titl text-center">
					Wait! Bed Bugs <span>LOVE</span> Couches too!
				</h1>
				
				<img src="../assets/images/couches-spray.png" alt="" class="imgright couchesspry" />
				
				<h3 class="page-sub-titl text-center pagesubtitl">
					Add an additional 
					BugShield<span class="bspro">Pro</span> unit for your 
					couch for just <span class="text-linethr">$10.95</span> <span class="codrd">$8.95</span></h3>
				
				<div class="clear"></div>
				
				<div class="btfrm">
					<div class="left-so-arr yatobtn"></div>
                    
                    	<form action="../add_offer.php?offer=2&amp;path=bp" method="POST">
                            <input type="hidden" name="quantity" value="1"> 
                            <a href="#" class="upsell-ato yato-btn" id="form-submit-button" onClick="document.forms[0].submit();">YES! Add to Order</a>
                        </form>
						<p class="nthnk innernoth"><a href="bug-shield-pro-upsell-3.php" class="notintr">No Thanks! I am not interested in completely eradicating bed bugs!</a></p>
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
		<?php include("../inc/zopim_chat.php"); ?>
	</body>
</html>