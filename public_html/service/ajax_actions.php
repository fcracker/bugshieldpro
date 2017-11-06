<?php
include_once("../lib/security.inc.php");
include_once("../lib/config.inc.php");
include_once("../lib/database.inc.php");
include_once("../lib/rebill_cycle.class.php");
include_once("../lib/order.class.php");
require_once("../lib/user.class.php");
require_once("../lib/antifraud.class.php");
require_once("../lib/antifraud.helper.php");

include_once("../lib/form.class.php");
include_once("../lib/country.class.php");

require_once("../lib/PayFrontEnd.php");
require_once("../lib/PayBackEnd.php");
require_once("../paypal/dobundlepayment.php");

$con = connect_database();

global $cfg;

if(!isset($_POST["action"])) die("No action defined");
if(!isset($_POST["id"])) die("Not all parameters defined!");

$id = intval($_POST["id"]);
if($id<=0) die("Invalid ID!");

$backend = new PayBackEnd;
$uzer = new umUser;
$user_data = $uzer->get_user_info_by_id($id);

$rc = new rebill_cycle;

switch($_POST["action"]) {
  case "getactions":
              
              
              //get the current user rebills
              $possible_periods = $rc->get_possible_rebill_periods();
              
              //yearly rebills
              $rebills = rebill_cycle::user_rebills_for_period($id,365);
              
              foreach($possible_periods as $pp) {
              
                $rebills = array_merge($rebills,rebill_cycle::user_rebills_for_period($id,$pp));
              
              }
			        
              
              
              ?>
              <h2>Possible Actions</h2>
              <button id="addunits" onclick="toggle_preaction('addunits')">Add Units</button>
              <br />
              <div id="addunits-wrapper" class="action-wrapper">
                 
                 <input class="actions-input param" type="text" name="addunits_qty" id="addunits_qty" value="1" /> 
                 Units X 
                 <input class="actions-input param" type="text" name="addunits_price" id="addunits_price" value="<?php echo $cfg['product_price'];?>" /> 
                 Dollars/Unit
                 <br />
                 <br />
                 Rebill Price Per Unit: 
                 <input class="actions-input param" type="text" name="addunits_rebillprice" id="addunits_rebillprice" value="<?php echo $cfg['product_price_rebill'];?>" /> Dollars (you can put $0 here, if no rebill is wanted)  
                 
                  
                <br />
                
                Rebill Period:
                <select class="actions-input param" name="addunits_rebillperiod" id="addunits_rebillperiod">
                  <?php foreach($possible_periods as $pp):?>
                    <option value="<?php echo $pp;?>"><?php echo $pp;?> days</option>
                  <?php endforeach;?>
                </select>
                
                <br />
                
                <button onclick="trigger_action('addunits',<?php echo $id;?>)">Execute</button>
              </div>
              <br />
			  
			  <button id="arbitrarycharge" onclick="toggle_preaction('arbitrarycharge')">Arbitrary Charge</button>
              <br />
              <div id="arbitrarycharge-wrapper" class="action-wrapper">
                 
                 $<input class="actions-input param" type="text" name="arbitrarycharge_amount" id="arbitrarycharge_amount" value="0" /> 
                 <br />
				 Description:
				 <br />
                 <textarea class="actions-input param-textarea" name="arbitrarycharge_description" id="arbitrarycharge_description" style="width:300px;height:150px;"></textarea> 
                 
                 <br />
                 <br />                
                
                <button onclick="trigger_action('arbitrarycharge',<?php echo $id;?>)">Execute</button>
              </div>
              <br />
			  
			  
			  
              <button onclick="toggle_preaction('addfreeunits')">Add 'FREE' Units</button>
              <br />
              <div id="addfreeunits-wrapper" class="action-wrapper">
                
                  <input class="actions-input param" type="text" name="addfreeunits_qty" id="addfreeunits_qty" value="1" /> 
                 Units
                 <br />
                 <br />
                 Rebill Price Per Unit: 
                 <input class="actions-input param" type="text" name="addfreeunits_rebillprice" id="addfreeunits_rebillprice" value="0" /> Dollars (you can leave it $0, if no rebill is wanted)                
                  
                <br /> 

                Rebill Period:
                <select class="actions-input param" name="addfreeunits_rebillperiod" id="addfreeunits_rebillperiod">
                  <?php foreach($possible_periods as $pp):?>
                    <option value="<?php echo $pp;?>"><?php echo $pp;?> days</option>
                  <?php endforeach;?>
                </select>
                
                <br />
               
                <button onclick="trigger_action('addfreeunits',<?php echo $id;?>)">Execute</button>
                
              </div>  
              <br />
              <button onclick="toggle_preaction('removeunits')">Remove Units/Upsells/Refund Options</button>        
              <br />
              <div id="removeunits-wrapper" class="action-wrapper">
                
                Remove <input class="actions-input param" type="text" name="removeunits_qty" id="removeunits_qty" value="0" /> Units
                <br />
                Refund $<input class="actions-input param" type="text" name="removeunits_refund" id="removeunits_refund" value="0" /> from one of the below transactions:
                <br />
				<div style="clear:both;">
                  <ul class="rebill-list">
					<li>
                        <input class="actions-input param-radio" type="radio" id="removeunits_transid_0" name="removeunits_transid" value="0" checked />
                        <label for="removeunits_transid_0">
                        None
                        </label>
                      </li>
				<?php
					$history = $backend->getAllPayHistory($user_data["email"]);
					foreach($history as $h):
				?>
				
					<li>
                        <input class="actions-input param-radio" type="radio" id="removeunits_transid_<?php echo $h->hKey;?>" name="removeunits_transid" value="<?php echo $h->hKey;?>" />
                        <label for="removeunits_transid_<?php echo $h->hKey;?>">
                        $<?php echo $h->hAmount;?> from <?php echo $h->hDate;?> <?php echo strlen($h->transaction_description) ? "( ".$h->transaction_description." )":"";?>
                        </label>
                      </li>				
				<?php endforeach;?>
				</ul>
				</div>
				
                <?php if(count($rebills)):?>
                  The user has rebills set. The current ones are listed below.
                  <br />
                  Select the one from which you would like to remove an amount:
                  <br />
                  <div style="clear:both;">
                  <ul class="rebill-list">
						<li>
                        <input class="actions-input param-radio" type="radio" id="removeunits_rebillid_0" name="removeunits_rebillid" value="0" checked />
                        <label for="removeunits_rebillid_0">
                        None
                        </label>
                      </li>
                    <?php foreach($rebills as $rebill):?>
                      <li>
                        <input class="actions-input param-radio" type="radio" id="removeunits_rebillid_<?php echo $rebill->id;?>" name="removeunits_rebillid" value="<?php echo $rebill->id;?>" />
                        <label for="removeunits_rebillid_<?php echo $rebill->id;?>">
                        $<?php echo $rebill->amount;?> -- <?php echo $rebill->description;?>
                        </label>
                      </li>
                    <?php endforeach;?>
                  </ul>
                  </div>
                  <br />
                  Amount to remove from selected rebill: $<input class="actions-input param" type="text" name="removeunits_rebillamount" id="removeunits_rebillamount" value="0" />
                <?php endif;?>
                
                <br />
                <button onclick="trigger_action('removeunits',<?php echo $id;?>)">Execute</button>
                
              </div>   

              <br />
              <button onclick="trigger_action('viewfraud',<?php echo $id;?>)">View Fraud Data</button>  
              
              
              <br />
               <br />
              
              <button onclick="toggle_preaction('editaddress')">Edit Shipping Address</button>  
              <br />
              <div id="editaddress-wrapper" class="action-wrapper">
                <br />
                <span class="action_label">Firstname:</span> <input class="actions-input-long param" type="text" name="editaddress_firstname" id="editaddress_firstname" value="<?=$user_data['firstname'];?>" />
                <br />
                
                <span class="action_label">Lastname:</span> <input class="actions-input-long param" type="text" name="editaddress_lastname" id="editaddress_lastname" value="<?=$user_data['lastname'];?>" />
                <br />
                
                <span class="action_label">Phone:</span> <input class="actions-input-long param" type="text" name="editaddress_phone" id="editaddress_phone" value="<?=$user_data['phone'];?>" />
                <br />
                
                 <span class="action_label">Country:</span> 
                 <select class="actions-input-long param" type="text" name="editaddress_country" id="editaddress_country">
                <?php
                  $field = new umField();

                  $field->fieldID = 8;		//define country filed ID 

                $field->get_field_options();
                
                //re-arrange field options

											$indexes = array("US"=>0,"CA"=>1);

											$current_index = 2;

											$countries = array();

											foreach($field->fieldOptions as $country) {

											if(in_array($country->defaultCaption,array("US","CA"))) {

												$countries[$indexes[$country->defaultCaption]] = $country;

												} else {

													$countries[$current_index] = $country;

													$current_index++;

												}

											}

										

											for($j = 0; $j < count($countries); $j++) {

												echo '<option value="' . $countries[$j]->defaultCaption. '"'.(($user_data['country']==$countries[$j]->defaultCaption) ? " selected":"").'>'.htmlspecialchars($countries[$j]->caption). '</option>';

											}
                ?>
                </select>
               
                <br />
                
                <span class="action_label">State:</span> <input class="actions-input-long param" type="text" name="editaddress_state" id="editaddress_state" value="<?=$user_data['state'];?>" />
                <br />
                
                <span class="action_label">City:</span> <input class="actions-input-long param" type="text" name="editaddress_city" id="editaddress_city" value="<?=$user_data['city'];?>" />
                <br />
                
                <span class="action_label">Address:</span> <input class="actions-input-long param" type="text" name="editaddress_address" id="editaddress_address" value="<?=$user_data['address'];?>" />
                <br />
                
                <span class="action_label">PostalCode:</span> <input class="actions-input-long param" type="text" name="editaddress_postalcode" id="editaddress_postalcode" value="<?=$user_data['postalcode'];?>" />
                <br />
                
                <br />
                <button onclick="trigger_action('editaddress',<?php echo $id;?>)">Update Address</button>
              </div>
              
              <?php
              
              die();
              
              break;
  case "editaddress":
              $address = array();
              $address['firstname'] = $_POST['firstname'];
              $address['lastname'] = $_POST['lastname'];
              $address['phone'] = $_POST['phone'];
              $address['country'] = $_POST['country'];
              $address['state'] = $_POST['state'];
              $address['city'] = $_POST['city'];
              $address['address'] = $_POST['address'];
              $address['postalcode'] = $_POST['postalcode'];
              if(strlen($address['firstname']) && strlen($address['lastname'])) {
              $uzer->update_address($address,$id);
              
              $order = new order;
              $last_order = $order->get_last_outstanding_order_for_user($id);
              
              $address['zip'] = $adress['postalcode'];
              unset($address['postalcode']);
              
              $o = $order->update_by_id($last_order->id,$address);
              
              ?>
              <h3>
              Addres Updated! Please refresh the page to see the updated data.
              </h3>
              <?php
              } else {
              ?>
                <h3>ERROR!</h3>
                Make sure the Firstname and lastname are set!
              <?php
              
              }
            break;
              
  case "addunits":
            //compute validity
            $qty = intval($_POST["qty"]);
            $price = floatval($_POST["price"]);
            $rebillprice = floatval($_POST["rebillprice"]);
            $rebillperiod = floatval($_POST["rebillperiod"]);
            
            if($qty<=0 || $price<=0 || $rebillprice<0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            ?>
              Are you sure you want to:
              <br />
              - bill user with $<?php echo ($qty*$price);?> (<?php echo $qty;?> units x $<?php echo $price;?> per unit)
              <br />
              <?php if($rebillprice>0):?>
              - add rebill value of $<?php echo ($qty*$rebillprice);?> (<?php echo $qty;?> units x $<?php echo $rebillprice;?> per unit) - every <?php echo $rebillperiod;?> days
              <?php else:?>
              - add NO rebill
              <?php endif;?>
              <br />
              
              <div id="addunitsdo-wrapper" class="action-wrapper">
              
                <input class="actions-input param" type="hidden" name="addunitsdo_qty" id="addunitsdo_qty" value="<?php echo $qty;?>" /> 
                <input class="actions-input param" type="hidden" name="addunitsdo_price" id="addunitsdo_price" value="<?php echo $price;?>" /> 
                <input class="actions-input param" type="hidden" name="addunitsdo_rebillprice" id="addunitsdo_rebillprice" value="<?php echo $rebillprice;?>" />
                <input class="actions-input param" type="hidden" name="addunitsdo_rebillperiod" id="addunitsdo_rebillperiod" value="<?php echo $rebillperiod;?>" />
                
              </div>
              
              <button onclick="trigger_action('addunitsdo',<?php echo $id;?>)">Yes, DO IT!</button>
                &nbsp;&nbsp;
              <button onclick="trigger_action('getactions',<?php echo $id;?>)">Cancel that</button>
              
            <?php            
            break;
			
			
	  case "arbitrarycharge":
            //compute validity
            $amount = floatval($_POST["amount"]);
            $description = $_POST["description"];
            
            if($amount<=0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            ?>
              Are you sure you want to:
              <br />
              - bill user with $<?php echo ($amount);?> <?php echo strlen($description) ? "( ".$description." )":"";?>             
              <br />
              
              <div id="arbitrarychargedo-wrapper" class="action-wrapper">
              
                <input class="actions-input param" type="hidden" name="arbitrarychargedo_amount" id="arbitrarychargedo_amount" value="<?php echo $amount;?>" /> 
                <input class="actions-input param" type="hidden" name="arbitrarychargedo_description" id="arbitrarychargedo_description" value="<?php echo $description;?>" />
                
              </div>
              
              <button onclick="trigger_action('arbitrarychargedo',<?php echo $id;?>)">Yes, DO IT!</button>
                &nbsp;&nbsp;
              <button onclick="trigger_action('getactions',<?php echo $id;?>)">Cancel that</button>
              
            <?php            
            break;
	
            
    case "addfreeunits":
            //compute validity
            $qty = intval($_POST["qty"]);
            $rebillprice = floatval($_POST["rebillprice"]);
            $rebillperiod = floatval($_POST["rebillperiod"]);
            
            if($qty<=0 || $rebillprice<0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            ?>
              Are you sure you want to:
              <br />
              - add to the user <?php echo ($qty);?> FREE units ?
              <br />
              <?php if($rebillprice>0):?>
              - add rebill value of $<?php echo ($qty*$rebillprice);?> (<?php echo $qty;?> units x $<?php echo $rebillprice;?> per unit) - every <?php echo $rebillperiod;?> days
              <?php else:?>
              - NO rebill added
              <?php endif;?>
              <br />
              
              <div id="addfreeunitsdo-wrapper" class="action-wrapper">
              
                <input class="actions-input param" type="hidden" name="addfreeunitsdo_qty" id="addfreeunitsdo_qty" value="<?php echo $qty;?>" /> 
                <input class="actions-input param" type="hidden" name="addfreeunitsdo_rebillprice" id="addfreeunitsdo_rebillprice" value="<?php echo $rebillprice;?>" />
                <input class="actions-input param" type="hidden" name="addfreeunitsdo_rebillperiod" id="addfreeunitsdo_rebillperiod" value="<?php echo $rebillperiod;?>" />
                
              </div>
              
              <button onclick="trigger_action('addfreeunitsdo',<?php echo $id;?>)">Yes, DO IT!</button>
                &nbsp;&nbsp;
              <button onclick="trigger_action('getactions',<?php echo $id;?>)">Cancel that</button>
              
            <?php            
            break;   

      case "removeunits":
      
            //compute validity
            $qty = intval($_POST["qty"]);
            $refund = floatval($_POST["refund"]);
			$trans_id = intval($_POST["transid"]);
			$rebillamount = floatval($_POST["rebillamount"]);
			$rebill_id = intval($_POST["rebillid"]);
            
            if($qty<=0 && ($refund<=0 && $trans_id==0) && ($rebill_id==0 && $rebillamount<=0) ) {
              ?>
                <h3>You should select something to remove(quantities, money or amount from a rebill). Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
			
			//other sanity checks
			if($trans_id>0 && $refund>0) {
				$refundable = $backend->getRefundable_amount_by_hKey($trans_id);
				if($refund > $refundable) {
				?>
				<h3> You set a higher amount to refund than the maximum for the selected transaction( you selected $<?php echo $refund;?> and the maximum is $<?php echo $refundable;?>) </h3>
				<button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
				<?php      
               die(); 
				}
			}
			
			if($rebill_id>0 && $rebillamount>0) {
				$refundable = rebill_cycle::get_rebill($rebill_id);
				if($rebillamount > $refundable->amount) {
				?>
				<h3> You set a higher amount to remove than the maximum for the selected rebill( you selected $<?php echo $rebillamount;?> and the maximum is $<?php echo $$refundable->amount;?>) </h3>
				<button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
				<?php      
               die(); 
				}
			}
            
            ?>
              Are you sure you want to:
              <br />
			  <?php if($qty>0):?>
              - remove <?php echo ($qty);?> units ? (if any order is still open)
			  <?php endif;?>
              <br />
              <?php if($refund>0 && $trans_id>0):?>
              - refund $<?php echo ($refund);?>
              <?php endif;?>
              <br />
			  <?php if($rebill_id>0 && $rebillamount>0):?>
              - remove $<?php echo ($rebillamount);?> from rebill
              <?php endif;?>
              <br />
              
              <div id="removeunitsdo-wrapper" class="action-wrapper">
              
                <input class="actions-input param" type="hidden" name="removeunitsdo_qty" id="removeunitsdo_qty" value="<?php echo $qty;?>" /> 
				
                <input class="actions-input param" type="hidden" name="removeunitsdo_refund" id="removeunitsdo_refund" value="<?php echo $refund;?>" /> 
				<input class="actions-input param" type="hidden" name="removeunitsdo_transid" id="removeunitsdo_transid" value="<?php echo $trans_id;?>" /> 
				
				<input class="actions-input param" type="hidden" name="removeunitsdo_rebillamount" id="removeunitsdo_rebillamount" value="<?php echo $rebillamount;?>" /> 
				<input class="actions-input param" type="hidden" name="removeunitsdo_rebillid" id="removeunitsdo_rebillid" value="<?php echo $rebill_id;?>" /> 
                
              </div>
              
              <button onclick="trigger_action('removeunitsdo',<?php echo $id;?>)">Yes, DO IT!</button>
                &nbsp;&nbsp;
              <button onclick="trigger_action('getactions',<?php echo $id;?>)">Cancel that</button>
              
            <?php            
            break;   
            
  case "addunitsdo":
            //compute validity
            $qty = intval($_POST["qty"]);
            $price = floatval($_POST["price"]);
            $rebillprice = floatval($_POST["rebillprice"]);
            $rebillperiod = floatval($_POST["rebillperiod"]);
            
            if($qty<=0 || $price<=0 || $rebillprice<0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            
            
            //ok, everything seems fine, let's do this
            
            $amount = $qty*$price;
            $rebill_amount = $qty*$rebillprice;
            $charge_description = $qty." items - Manually added at $".$price."/unit";
            $rebill_descriprion = $qty." items - Rebill Manually added at $".$rebillprice."/unit";
            
            //do the charge
            $user = new umUser();
            $u = $user->get_user_info_by_id($id);
            //process date			
            $u["exp_year"] = $u["expiration_year"];
            $u["exp_month"] = $u["expiration_month"];
            
            //make sure to include ip            
            $u["ip"] = $u["user_ip"];
              
            //description
            $u["transaction_description"] = $charge_description;           
            
            $res = do_bundle_payment($u,$amount);
            
            if($res['ACK'] == "Success"){
              $order = new order;	
              
              //fetch another order first, so we can get some data
              $all_orders = $order->get_specific_orders_by_user(array($id));
              $papvisitorid = (is_array($all_orders) && count($all_orders)) ? $all_orders[0]->PAPVisitorId : "";
              
              $last_order = $order->get_last_outstanding_order_for_user($id);
              
              if($last_order!==false) {
                //there is an outstanding order
                $order_data = array(
                  
                  "qty"				=> 	max(1,intval($last_order->qty + $qty)),                  
                  "description"		=>  $last_order->description." + ".$charge_description,
                
                );
                
                //update it
                $order->update_by_id($last_order->id,$order_data);
                
              } else {
                
                //need to create a new order
                
                
                
                $order_data = array(
					
                    "user_id"			=> 	$id,
                    "qty"				=> 	max(1,$qty),	
                    "total"				=>	$amount,
                    "firstname"     	=>  $u["firstname"],
                    "lastname"      	=>  $u["lastname"],
                    "address"      		=>  $u['address'],
                    "city"          	=>  $u['city'],
                    "state"         	=>  $u['state'],
                    "zip"           	=>  $u['postalcode'],
                    "country"       	=>  $u['country'],
                    "phone"       		=>  $u['phone'],
                    "email"       		=>  $u['email'],
                    "description"		=>  $charge_description, 
                    "status"			=> 	"not shipped",
                    "subid"				=>   $u["subid"],
                    "PAPVisitorId"		=>	$papvisitorid,
                    
                    );
                    
                  //run the insert
                  $oid = $order->create($order_data);	  
					
                
              }
              
              //ok, now take care of the rebill
              if($rebillprice>0) {
                $rebill_cycle = new rebill_cycle;
                //just add a new rebill
                $rbl = array("amount"=>$rebill_amount,"description"=>$rebill_descriprion,"period"=>$rebillperiod,"last_payment"=>date("Y-m-d H:i:s"),"user_id"=>$id,"PAPVisitorId"=>$papvisitorid,"qty"=>$qty);
						
                $rebill_cycle->create($rbl);
              }
              
              ?>
                <h2>Ok, the user was charged $<?php echo $amount;?> and a rebill of $<?php echo $rebill_amount;?> was set for every <?php echo $rebillperiod;?> days <h2>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
                
              <?php
              
            }
            ?>
              
            <?php
            break;
            
  case "addfreeunitsdo":
            //compute validity
            $qty = intval($_POST["qty"]);
            $rebillprice = floatval($_POST["rebillprice"]);
            $rebillperiod = floatval($_POST["rebillperiod"]);
            
            if($qty<=0 || $rebillprice<0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            
            
            //ok, everything seems fine, let's do this
            
            $new_order = false;
           
            $rebill_amount = $qty*$rebillprice;
            $charge_description = $qty." FREE UNITS";
            $rebill_descriprion = $qty." items - Rebill Manually added at $".$rebillprice."/unit(FREE UNITS INITIALLY)";
            
            $user = new umUser();
            $u = $user->get_user_info_by_id($id);
            //process date			
            $u["exp_year"] = $u["expiration_year"];
            $u["exp_month"] = $u["expiration_month"];
            
            //make sure to include ip            
            $u["ip"] = $u["user_ip"];
              
            //description
            $u["transaction_description"] = $charge_description;
                        
            
              $order = new order;	
              
              //fetch another order first, so we can get some data
              $all_orders = $order->get_specific_orders_by_user(array($id));
              $papvisitorid = (is_array($all_orders) && count($all_orders)) ? $all_orders[0]->PAPVisitorId : "";
              
              $last_order = $order->get_last_outstanding_order_for_user($id);
              
              if($last_order!==false) {
                //there is an outstanding order
                $order_data = array(
                  
                  "qty"				=> 	max(1,intval($last_order->qty + $qty)),                  
                  "description"		=>  $last_order->description." + ".$charge_description,
                
                );
                
                //update it
                $order->update_by_id($last_order->id,$order_data);
                
              } else {
                
                //need to create a new order
                
                $new_order = true;
                
                $order_data = array(
					
                    "user_id"			=> 	$id,
                    "qty"				=> 	max(1,$qty),	
                    "total"				=>	$amount,
                    "firstname"     	=>  $u["firstname"],
                    "lastname"      	=>  $u["lastname"],
                    "address"      		=>  $u['address'],
                    "city"          	=>  $u['city'],
                    "state"         	=>  $u['state'],
                    "zip"           	=>  $u['postalcode'],
                    "country"       	=>  $u['country'],
                    "phone"       		=>  $u['phone'],
                    "email"       		=>  $u['email'],
                    "description"		=>  $charge_description, 
                    "status"			=> 	"not shipped",
                    "subid"				=>   $u["subid"],
                    "PAPVisitorId"		=>	$papvisitorid,
                    
                    );
                    
                  //run the insert
                  $oid = $order->create($order_data);	  
					
                
              }
              
              //ok, now take care of the rebill
              if($rebillprice>0) {
                $rebill_cycle = new rebill_cycle;
                //just add a new rebill
                $rbl = array("amount"=>$rebill_amount,"description"=>$rebill_descriprion,"period"=>$rebillperiod,"last_payment"=>date("Y-m-d H:i:s"),"user_id"=>$id,"PAPVisitorId"=>$papvisitorid,"qty"=>$qty);
						
                $rebill_cycle->create($rbl);
              }
              
              ?>
              <h2>
              <?php if($new_order):?>
                Ok, we created a new order for <?php echo $qty;?> units
              <?php else:?>
                Ok, we updated the existing order with <?php echo $qty;?> units
              <?php endif;?>
              <?php if($rebill_amount>0):?>
                and we added a rebill of $<?php echo $rebill_amount;?> for every <?php echo $rebillperiod;?> days
              <?php endif;?>
              </h2>
                
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
                
              <?php
              
            
            ?>
              
            <?php
            break; 


    case "removeunitsdo":
            //compute validity
            $qty = intval($_POST["qty"]);
            $refund = floatval($_POST["refund"]);
			$trans_id = intval($_POST["transid"]);
			$rebillamount = floatval($_POST["rebillamount"]);
			$rebill_id = intval($_POST["rebillid"]);
            
            if($qty<=0 && ($refund<=0 && $trans_id==0) && ($rebill_id==0 && $rebillamount<=0) ) {
              ?>
                <h3>You should select something to remove(quantities, money or amount from a rebill). Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
			
			$order = new order;
			
			//other sanity checks
			if($trans_id>0 && $refund>0) {
				$refundable = $backend->getRefundable_amount_by_hKey($trans_id);
				if($refund > $refundable) {
				?>
				<h3> You set a higher amount to refund than the maximum for the selected transaction( you selected $<?php echo $refund;?> and the maximum is $<?php echo $refundable;?>) </h3>
				<button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
				<?php      
               die(); 
				}
			}
			
			if($rebill_id>0 && $rebillamount>0) {
				$refundable = rebill_cycle::get_rebill($rebill_id);
				//echo "<pre>".print_r($refundable,1)."</pre>";
				if($rebillamount > $refundable->amount) {
				?>
				<h3> You set a higher amount to remove than the maximum for the selected rebill( you selected $<?php echo $rebillamount;?> and the maximum is $<?php echo $refundable->amount;?>) </h3>
				<button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
				<?php      
               die(); 
				}
			}
            
            //ok, everything seems fine, let's do this
			
			//remove qty first
			if($qty > 0) {
			
				//do we have an outstanding order ?
				$last_order = $order->get_last_outstanding_order_for_user($id);
              
				  if($last_order!==false) {
					//there is an outstanding order
					$order_data = array(
					  
					  "qty"				=> 	$last_order->qty - $qty,                  
					  "description"		=>  $last_order->description." + removed ".$qty." units",
					
					);
          
          if($order_data["qty"]>0) {
					
					//update it
					$order->update_by_id($last_order->id,$order_data);
          ?>
          <h2> We removed <?php echo $qty;?> units from the last outstanding order </h2>
          <?php
          } else {
          $order->delete($last_order->id);
          ?>
          <h2> We removed the entire order, since the quantity you entered is bigger than the existing amount </h2>
          <?php
          }
					?>
					
					<?php
				  } else {
					?>
					<h2> There was no order to remove units from, so I did nothing! </h2>
					<?php
				  }
				  
			
			}
			
			//do we need to remove from a rebill ?
			if($rebillamount>0 && $rebill_id>0) {
				$rb = new rebill_cycle;
				$rb->remove_amount_from_rebill($rebill_id,$rebillamount);
				$rb
				?>
				<h2> We updated the rebill, and removed $<?php echo $rebillamount;?> from it </h2>
				<?php
			}
			
			//do we need to do a refund of a specific transaction ?
			if($refund>0 && $trans_id>0) {
				
				$payObj = new PayFrontEnd();
				$resArray = $payObj->doRefund($backend->get_transid_from_hKey($trans_id), $refund);
              if($resArray["ACK"] == "Success"):
              ?>
                <h2> Ok, the user was refunded $<?php echo $refund;?> <h2>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
                
              <?php else:?>
			  
			  <h2> The refund for $<?php echo $refund;?> failed, unfortunately. <h2>
			  <h3> The error was: <?php echo $resArray["L_LONGMESSAGE0"];?> </h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
			  
			  <?php endif;?>
			  
             <?php
			 
            }
			
            ?>
              
            <?php
            break;	



    case "arbitrarychargedo":
            //compute validity
            $amount = floatval($_POST["amount"]);
            $description = ($_POST["description"]);
            
            if($amount<=0) {
              ?>
                <h3>There was a problem with your quantities. Try again!</h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Re-enter data</button>
              <?php        
               die();   
            }
            
            
            
            //ok, everything seems fine, let's do this
            
            
            $charge_description = "Manually added".(strlen($description) ? " - ".$description:"");
            
            //do the charge
            $user = new umUser();
            $u = $user->get_user_info_by_id($id);
            //process date			
            $u["exp_year"] = $u["expiration_year"];
            $u["exp_month"] = $u["expiration_month"];
            
            //make sure to include ip            
            $u["ip"] = $u["user_ip"];
              
            //description
            $u["transaction_description"] = $charge_description;           
            
            $res = do_bundle_payment($u,$amount);
            
            if($res['ACK'] == "Success"){
              ?>
                <h2>Ok, the user was charged $<?php echo $amount;?> <h2>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
                
              <?php
              
            } else {
			?>
			
			<h2>We could not complete the transaction. The error was: <h2>
			<h3><?php echo $res["L_LONGMESSAGE0"];?></h3>
                <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
                &nbsp;&nbsp;
                OR Close this dialog
			<?php
			}
            ?>
              
            <?php
            break;				
  case 'viewfraud':
        $antifraud = new antifraud();
        $order = new order;	
        //$last_order = $order->get_last_outstanding_order_for_user($id);
		$last_order = $order->get_last_order_for_user($id);
        $data = $antifraud->check_order($last_order->id);
        $country_data = explode(";",$data->bin_country_match);
		
		$avs_response = display_fraud($data->avs_response,"avs"); 
		$cvv_response = display_fraud($data->cvv_response,"cvv");
		$correlation_local  = display_fraud($data->ip_location_correlation_local,"correlation_local"); 
		$correlation_external = display_fraud($data->ip_location_correlation_external, "correlation_external");
		$bin_country_match  = display_fraud($data->bin_country_match,"bin_country_match");
		$bin_prepaid_match  = display_fraud($data->bin_prepaid_match,"bin_prepaid_match");
		$ip_is_proxy  = display_fraud($data->ip_is_proxy,"ip_is_proxy"); 
		$is_email_high_risk  = display_fraud($data->is_email_high_risk,"is_email_high_risk");
		$is_address_high_risk  = display_fraud($data->is_address_high_risk,"is_address_high_risk"); 
		$risk_score  = display_fraud($data->risk_score,"risk_score"); 
		
        ?>
         <h2>Fraud Check Data<h2>
        AVS Response:  <span class="<?php echo $avs_response['class'];?>"><?php echo $avs_response['value'];?></span>
        <br />
        CVV (M/N):  <span class="<?php echo $cvv_response['class'];?>"><?php echo $cvv_response['value'];?></span> 
        <br />
        Location Correlation(Maxmind):<span class="<?php echo $correlation_external['class'];?>"><?php echo $correlation_external['value'];?></span>
        <br />
        Country Match: We have <span class="<?php echo $bin_country_match['class'];?>"><< <?php echo $country_data[2];?> >> ; MXMND says:  << <?php echo $country_data[0];?> >></span>
        <br />
        Bank: <?php echo $country_data[1];?>
        <br />
        Prepaid Card:<span class="<?php echo $bin_prepaid_match['class'];?>"><?php echo $bin_prepaid_match['value'];?></span>
        <br />
        Proxy Used:<span class="<?php echo $ip_is_proxy['class'];?>"><?php echo $ip_is_proxy['value'];?></span>
        <br />
        Maxmind High Risk email:<span class="<?php echo $is_email_high_risk['class'];?>"><?php echo $is_email_high_risk['value'];?></span>
        <br />
        Maxmind High Risk address:<span class="<?php echo $is_address_high_risk['class'];?>"><?php echo $is_address_high_risk['value'];?></span>
        <br />
        Maxmind Risk Score:<span class="<?php echo $risk_score['class'];?>"><?php echo $risk_score['value'];?></span>
        <br />
        
        
        <br />
        <button onclick="trigger_action('getactions',<?php echo $id;?>)">Return to actions lobby</button>
        <?php
        break;
  default:
          die("Undefined action!");
          break;
}