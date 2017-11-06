<?php
include_once("./lib/config.inc.php");
require_once("./lib/database.inc.php");
require_once("./lib/usertemp.class.php");
$db = connect_database();
  global $cfg;
	
	$t = new tracker;
	$tracker = $t->get_data();
  
  if(!isset($tracker["email"])) {
		header("Location:index.php");die();
	} 
  
  $is_avs_reject = isset($_GET['avs']) && intval($_GET['avs']>0);
	
//include_once("footage.php");
$email = urldecode($_GET['email']);
//get the error
$r = mysql_query("select * from declined_cards where user='".mysql_real_escape_string($email)."' order by declined_id desc limit 1");
$has_error = false;
if(mysql_num_rows($r)) {
	$data = mysql_fetch_object($r);
	$has_error = true;
}
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

<link rel="stylesheet" href="assets/stylesheets/style.css">
<link rel="stylesheet" type="text/css" href="assets/stylesheets/responsive.css">
<link rel="stylesheet" type="text/css" href="assets/stylesheets/responsive2.css">

<link rel="stylesheet" href="assets/stylesheets/normalize.css">
<link rel="stylesheet" href="assets/stylesheets/demo.css">
<!-- Pushy CSS -->
<link rel="stylesheet" href="assets/stylesheets/pushy.css">
<!--[if lt IE 9]>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
<![endif]-->
</head>
<body>
	<nav class="pushy pushy-left" data-focus="#first-link">
        <div class="pushy-content">
			<div class="mob-main-navigation">
				<ul>
					<li><a href="/#block-section2">ABOUT</a></li>
					<li><a href="/#bspro-spry">WHY BugShield<span class="bspro">Pro</span></a></li>
					<li><a href="/#block-section3">TESTED</a></li>
					<li><a href="/#block-section5">VIDEO PROOF</a></li>
					<li><a href="/#customer-reviews">REVIEWS</a></li>
					<li><a href="bug-shield-pro-step-2.php">ORDER</a></li>
				</ul>
			</div>
		</div>
	</nav>
	<div class="site-overlay"></div>
	<div class="top">
		<div class="container">
			<div class="menu-btn-shadow">
				<button class="menu-btn">&#9776;</button>
			</div>
			<div class="top-logo">
				<a href="/"><img src="assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
			<div class="main-navigation">
				<ul>
					<li><a href="/#block-section2">ABOUT</a></li>
					<li><a href="/#bspro-spry">WHY BugShield<span class="bspro">Pro</span></a></li>
					<li><a href="/#block-section3">TESTED</a></li>
					<li><a href="/#block-section5">VIDEO PROOF</a></li>
					<li><a href="/#customer-reviews">REVIEWS</a></li>
					<li><a href="bug-shield-pro-step-2.php">ORDER</a></li>
				</ul>
			</div>
		</div>
	</div>
    
    
    

    <div class="container" style="background:#004c97; padding:20px 50px;">
        
        <h1 style="color:#FFF;">Sorry! Transaction error</h1>
        
        <?php if($has_error):?>
            <div style="width:800px;background-color:#CCC;font-size:16px;padding:10px;border:1px solid #000;line-height:25px;margin:10px 0 50px 50px;">
                
            Error Code: <strong><?php echo $data->declined_id;?></strong>
            <br />
            User Email: <strong><?php echo $data->user;?></strong>
            <br />
            Error Content: <strong><?php echo $data->error;?></strong>
            <br />
            Timestamp: <strong><?php echo $data->when;?></strong>
        <br />
        <?php if($is_avs_reject):?>
          <strong>
            Please call our customer service at 1-855-543-0054 for further assistance with your order.
          </strong>
        <?php endif;?>
            
            </div>		
            
            
            
            <?php else:?>
            
                <div style="background-color:#CCC;color:#ff0000;font-size:18px;font-weight:bold;padding:10px;border:1px solid #000;line-height:25px;width:500px;margin:10px 0 50px 0;">
            
            We could not find any error associated with your account. Please contact our support service providing more details regarding this error.
            
            </div>
            
            <?php endif;?>
            
            
            <div style="width:800px;margin:10px 0 50px 50px;">
            
        <?php 
        $reenter = "landingpage_p2";
        $cancel = "index_p2";
        
        if(supersession("ismobile")!=false) {
          $reenter = $cfg['site_mobile']['url']."/index";
          $cancel = $cfg['site_mobile']['url']."/index";
        }
        
        ?>
        
                <div style="float:left;margin-right:20px;">
                <form action="<?php echo $reenter;?>.php" method="POST">
                                <input type="hidden" name="email" value="<?php echo htmlentities(strip_tags($email));?>" />					
                    <input type="submit" style="padding:10px;font-size:20px;" name="retry" value="Re-enter your data" />
                </form>
                </div>
                        
            
    
            <div style="float:left;margin-right:20px; color:#FFF; margin-top:10px;">
            <strong> OR </strong>
            </div>
            
            <button style="padding:10px;font-size:20px;" onclick="location.href='<?php echo $cancel;?>.php'">Cancel and return to the home page</button>
            </div>
        
    </div>
    
    
    
    

	<div class="footer-section lato-reg">
		<div class="container">
			<div class="col-4">
				<div class="footer-widget">
					
					<h3 class="fwid-titl">Bug Shield <span class="bspro">Pro</span></h3>
					
					<ul>
						<li><a href="/">Home</a></li>
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
    <!-- jQuery -->
    <script src="assets/javascripts/jquery.min.js"></script>
    <script src="assets/javascripts/pushy.min.js"></script>
	</body>
</html>