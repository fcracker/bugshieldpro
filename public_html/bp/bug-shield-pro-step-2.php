<?php
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/form.class.php");
include_once("../lib/country.class.php");

global $cfg;

$con = connect_database();

$field = new umField();

$field->fieldID = 8;    //define country filed ID 

$field->get_field_options();

$t = new tracker;
$tracker = $t->get_data();

$client_country = MY_COUNTRY;

if(isset($tracker["country"])) {
  $client_country = $tracker["country"];
}

$_SESSION['datetimer'] = NULL;
//$t->set_data($tracker);
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
	<div style="background:#000; width:100%; height:100%; position:fixed; z-index:998; opacity:0.6; top:0; bottom:0; display:none;" class="loader-bg"></div>
	<img src="../assets/images/ajax-loader.gif" style="position:fixed; z-index:999; top:0; bottom:0; left:0; right:0; margin-top:auto; margin-bottom:auto; margin-left:auto; margin-right:auto; display:none;" class="loader-gif">
	<div class="top">
		<div class="container">
			<div class="top-logo">
				<a href="#"><img src="../assets/images/bugshieldpro-logo.png" alt=""/></a>
			</div>
		</div>
	</div>
	<div class="section" id="block-section8">
		<div class="container">
			<h2 class="lato-black text-center order-steps">To proceed to the order page please enter your details below: </h2>
			<h4 class="lato-reg text-center" style="color:#7a7a7a;">Home Size &gt; <span class="lato-bold active-pick">Pick Your Kit</span> &gt; Checkout</h4>
		
		</div>
	</div>
	<div class="section text-center" id="block-section9">
		<div class="container">
        
        <style>
		.pick-kit {text-decoration:none;}
		.ordr-form-styled {border:#FFF solid 1px !important; width:100% !important; margin-left:0px; margin-bottom:20px; padding-top:20px; padding-bottom:15px;}
		.ordr-form-styled input[type=text], .ordr-form-styled input[type=email] {width:200px !important; padding:5px 10px !important; height:30px !important;}
		.ordr-form-styled select {width:220px !important; padding:5px 5px !important; height:40px !important;}
		
		@media (max-width:768px) {
			
			.ordr-form-styled {width:398px !important; margin-top:-50px;}
			
			.ordr-form-styled .form-field {margin-bottom:-5px !important;}
			.ordr-form-styled input[type=text], .ordr-form-styled input[type=email], .ordr-form-styled select {margin-bottom:10px !important;}
			
			.ordr-form-styled input[type=text], .ordr-form-styled input[type=email], .form-field:nth-child(4) input[type=text] {width:200px !important;}
			.ordr-form-styled .form-field {padding-left:70px;}
			
			.ordr-form-styled .form-field label {margin-bottom:5px;}
			
			.form-field input[type=text], .form-field input[type=email] {
				-webkit-border-radius: 0px;
				-moz-border-radius: 0px;
				border-radius: 0px;
			}
		}
		
		@media (max-width:480px) {
			.ordr-form-styled {width:278px !important; margin-top:-50px;}
			.ordr-form-styled input[type=text], .ordr-form-styled input[type=email], .form-field:nth-child(4) input[type=text] {width:200px !important;}
			
			.ordr-form-styled .form-field {padding-left:30px;}
		}
		</style>
        <?php if ($_SESSION['alreadyordered'] != NULL) { echo '<div style="color:#fff; padding-bottom:10px;">'.$_SESSION['alreadyordered'].'</div>'; $_SESSION['alreadyordered'] = NULL; } ?>
        <div class="ordr-form ordr-form-styled">
            <form action='bug-shield-pro-step-3.php' id="bugshieldproform" method="POST">
			<input type="hidden" id="postid" name="id">
            <div class="form-field">
                <label for="crdnumber">First name: </label>
                <input type='text' id='first_name' name='first_name' value='<?php echo $tracker['first_name'];?>' required />
                
                <label for="crdnumber">Last name: </label>
                <input type='text' id='last_name' name='last_name' value='<?php echo $tracker['last_name'];?>' required />
            </div>

            <div class="form-field">
                <label for="crdnumber">Address: </label>
                <input type='text' id='address' name='address' value='<?php echo $tracker['address'];?>' required />
                
                <label for="crdnumber">City: </label>
                <input type='text' id='city' name='city' value='<?php echo $tracker['city'];?>' required />
            </div>

            <div class="form-field">
                <label for="crdnumber" id="state-label">Province: </label>
                <input type='text' id='state' name='state' value='<?php echo $tracker['state'];?>' required />
                
                <label for="crdnumber">Country: </label>
                <select id='country' name='country' required onChange="countryChange(this.value);">
                <?php
                
                $indexes = array(
                    "US" => 0,
                    "CA" => 1
                );
                $current_index = 2;
                $countries = array();
                
                foreach($field->fieldOptions as $country) {
                    if (in_array($country->defaultCaption, array(
                        "US",
                        "CA"
                    ))) {
                        $countries[$indexes[$country->defaultCaption]] = $country;
                    }
                    else {
                        $countries[$current_index] = $country;
                        $current_index++;
                    }
                }
                
                for ($j = 0; $j < count($countries); $j++) {
                    echo '<option value="' . $countries[$j]->defaultCaption . '"' . (($client_country == $countries[$j]->defaultCaption) ? "selected" : "") . '>' . htmlspecialchars($countries[$j]->caption) . '</option>';
                }
                ?>
                </select>
            </div>

            <div class="form-field">
                <label for="crdnumber" id="zip-label">Postal code: </label>
                <input type='text' id='zip' name='zip' value='<?php echo $tracker['zip'];?>' required style="width:200px !important;" />
                
                <label for="crdnumber">Email: </label>
                <input type='email' id='email' name='email' value='<?php echo $tracker['email'];?>' required />
            </div>

            <div class="form-field">
                <label for="crdnumber">Phone: </label>
                <input type='text' id='phone' name='phone' value='<?php echo $tracker['phone'];?>' required />
            </div>
            <input type="submit" name="submit" id="submit" style="display:none;">
            </form>
        </div>

		<div class="kt-ct">
			<ul class="kit-list">
				
                <li>
                    <a href="#" id="pick-1" class="pick-kit">
                        <div class="kit-pick">
                            <h4 class="kit-pick-titl">Basic Bed Bug Kit</h4>
                            <div class="kit-pick-img"><img src="../assets/images/kit-pick1.png" alt="" class="" /></div>
                            <span class="pickbtn">Pick This Kit</span>
                            <p class="kit-pick-desc">3x 24oz BugShieldPro bottle</p>
                            <p class="kit-pick-pric">$33.32/each <span class="saveat">SAVE 12%</span></p>
                            <p class="kit-pick-pric">$99.96/total</p>
                            <h6 class="kit-pick-range">Enough to treaat single <br/>bedroom infestations</h6>
                        </div>
                	</a>
				</li>

				<li>
                <a href="#" id="pick-3" class="pick-kit">
				<div class="kit-pick">
					<h4 class="kit-pick-titl">Two Bottles</h4>
					<div class="kit-pick-img"><img src="../assets/images/kit-pick3.png" alt="" class="" /></div>
					<span class="pickbtn">Pick This Kit</span>
					<p class="kit-pick-desc">2x 24oz BugShieldPro bottle</p>
                    <p class="kit-pick-pric">$34.97/each <span class="saveat">SAVE 8%</span></p>
					<p class="kit-pick-pric">$69.94/total</p>
					<h6 class="kit-pick-range">Meant for minor infestations.</h6>
				</div>
                </a>
				</li>
                
                <li>
                <a href="#" id="pick-2" class="pick-kit">
                    <div class="kit-pick">
                        <h4 class="kit-pick-titl">Ultimate Bed Bug Kit</h4>
                        <div class="kit-pick-img"><img src="../assets/images/kit-pick2.png" alt="" class="" /></div>
                        <span class="pickbtn">Pick This Kit</span>
                        <p class="kit-pick-desc">6x 24oz BugShieldPro bottle</p>
                        <p class="kit-pick-pric">$19.99/each  <span class="saveat">SAVE 49%</span></p>
                        <p class="kit-pick-pric">$119.94/total</p>
                        <h6 class="kit-pick-range">This kit is enough to <br />destroy even the toughest <br />bed bug infestations</h6>
                    </div>
                </a>
				</li>
                
				<li>
                <a href="#" id="pick-4" class="pick-kit">
                    <div class="kit-pick">
                        <h4 class="kit-pick-titl">Bed Bug Kit Plus</h4>
                        <div class="kit-pick-img"><img src="../assets/images/kit-pick4.png" alt="" class="" /></div>
                        <span class="pickbtn">Pick This Kit</span>
                        <p class="kit-pick-desc">2x 24 oz bottle, <br/>1x 32 oz detergent</p>
                        <p class="kit-pick-pric">$33.97/bottle, $37.95/detergent</p>
                        <p class="kit-pick-pric">$105.89/total</p>
                        <h6 class="kit-pick-range">Enough to treat single <br />bed infestations. Spray and <br />wash the bed bugs away!</h6>
                    </div>
                </a>
				</li>
                
				<li>
                <a href="#" id="pick-5" class="pick-kit">
                    <div class="kit-pick">
                        <h4 class="kit-pick-titl">One Bottle</h4>
                        <div class="kit-pick-img"><img src="../assets/images/kit-pick5.png" alt="" class="" /></div>
                        <span class="pickbtn">Pick This Kit</span>
                        <p class="kit-pick-desc">1x 24oz BugShieldPro bottle</p>
                        <p class="kit-pick-pric">$36.95</p>
                        <h6 class="kit-pick-range">Enough to treat single <br/>bedroom infestations</h6>
                    </div>
                </a>
				</li>
                
			</ul>
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
	function isValidEmailAddress(emailAddress) {
		var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
		return pattern.test(emailAddress);
	};
	
	function countryChange(val) {
		if (val == 'HK') {
			$('#state-label').html('District: ');
			$('#zip-label').html('Postal code: '); 
		} else if (val == 'US') {
			$('#state-label').html('State: ');
			$('#zip-label').html('Zip: '); 
		} else {
			$('#state-label').html('Province: ');
			$('#zip-label').html('Postal code: ');
		}
	}
	
    $(document).ready(function() {
		
		if ($('#country').val() == 'US') {
			$('#state-label').html('State: ');
			$('#zip-label').html('Zip: ');
		} 
			
		if ($('#country').val() == 'HK') {
			$('#state-label').html('District: ');
			$('#zip-label').html('Postal code: '); 
		}
		
    	$('.kit-list .pick-kit').click(function() {
			if ($('#first_name').val() != '' && $('#last_name').val() != '' && $('#address').val() != '' && $('#city').val() != '' && $('#state').val() != '' && $('#zip').val() != '' && $('#phone').val() != '' && $('#email').val() != '') {
				
				email = $('#email').val();
				
				if (isValidEmailAddress(email)) {
					$('.loader-bg').css('display', 'block');
					$('.loader-gif').css('display', 'block');
				}
			}
			
			$('#postid').val($(this).attr('id'));
			$('#submit').trigger('click');
		});
    });
    </script>
	</body>
</html>