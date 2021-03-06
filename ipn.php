<?php
/* 
 * Bitpay IPN eshop gateway by Franco (nextime) Lanza
 *
 * based on:
 * PHP paypal IPN Integration Class Demonstration File
 *  4.16.2005 - Micah Carrick, email@micahcarrick.com
*/
/*
* default info
*/
global $wpdb,$wp_query,$wp_rewrite,$blog_id,$eshopoptions;
$detailstable=$wpdb->prefix.'eshop_orders';
$derror=__('There appears to have been an error, please contact the site admin','eshop');

//sanitise
include_once(WP_PLUGIN_DIR.'/eshop/cart-functions.php');
$_POST=sanitise_array($_POST);


/*
* reqd info for your gateway
*/
include_once (WP_PLUGIN_DIR.'/eshop-bitpay-mg/eshop-bitpay-mg.php');
// Setup class
require_once(WP_PLUGIN_DIR.'/eshop-bitpay-mg/bitpay-class.php');  // include the class file
$p = new bitpay_class;             // initiate an instance of the class

$p->bitpay_url = 'https://bitpay.com/checkout';     // bitpay url

/*
* reqd info /end
*/

$this_script = site_url();
global $wp_rewrite;
if($eshopoptions['checkout']!=''){
	$p->autoredirect=add_query_arg('eshopaction','redirect',get_permalink($eshopoptions['checkout']));
}else{
	die('<p>'.$derror.'</p>');
}

// if there is no action variable, set the default action of 'process'
if(!isset($wp_query->query_vars['eshopaction']))
	$eshopaction='process';
else
	$eshopaction=$wp_query->query_vars['eshopaction'];

switch ($eshopaction) {
    case 'redirect':
    	//auto-redirect bits
		header('Cache-Control: no-cache, no-store, must-revalidate'); //HTTP/1.1
		header('Expires: Sun, 01 Jul 2005 00:00:00 GMT');
		header('Pragma: no-cache'); //HTTP/1.0

		//enters all the data into the database
		/*
		* this works out eShop's security field
		*/
		$Cost=$_POST['amount'];
		if(isset($_POST['tax']))
			$Cost += $_POST['tax'];
		if(isset($_SESSION['shipping'.$blog_id]['tax'])) $Cost += $_SESSION['shipping'.$blog_id]['tax'];
		$theid=$eshopoptions['bitpay']['id'];
		$Cost=number_format($Cost,2);
		$checkid=md5($_POST['bitpayoption1'].$theid.'$'.$Cost);
		//debug
			//echo 'check: '.$_POST['bitpayoption1'].$theid.'$'.$Cost;
		//
		if(isset($_COOKIE['ap_id'])) $_POST['affiliate'] = $_COOKIE['ap_id'];
		orderhandle($_POST,$checkid);
		if(isset($_COOKIE['ap_id'])) unset($_POST['affiliate']);
		$p = new bitpay_class; 
		/*
		* more reqd info
		*/
		$p->bitpay_url = 'https://bitpay.com/checkout';     // bitpay url
		$echoit.=$p->eshop_submit_bitpay_post($_POST);
		break;
        
   case 'process':      // Process and order...
		// There should be no output at this point.  To process the POST data,
		// the submit_bitpay_post() function will output all the HTML tags which
		// contains a FORM which is submited instantaneously using the BODY onload
		// attribute.  In other words, don't echo or printf anything when you're
		// going to be calling the submit_bitpay_post() function.
		
		// This is where you would have your form validation  and all that jazz.
		// You would take your POST vars and load them into the class like below,
		// only using the POST values instead of constant string expressions.

		// For example, after ensureing all the POST variables from your custom
		// order form are valid, you might have:
		//
		// $p->add_field('first_name', $_POST['first_name']);
		// $p->add_field('last_name', $_POST['last_name']);
      
      /****** The order has already gone into the database at this point ******/
      
		//goes direct to this script as nothing needs showing on screen.
		if($eshopoptions['cart_success']!=''){
			$ilink=add_query_arg(array('eshopaction'=>'bitpayipn'),get_permalink($eshopoptions['cart_success']));
		}else{
			die('<p>'.$derror.'</p>');
		}
		$p->add_field('bitpayURL', $ilink);

		$p->add_field('shipping_1',eshopShipTaxAmt());
		$sttable=$wpdb->prefix.'eshop_states';
		$getstate=$eshopoptions['shipping_state'];
		if($eshopoptions['show_allstates'] != '1'){
			$stateList=$wpdb->get_results("SELECT id,code,stateName FROM $sttable WHERE list='$getstate' ORDER BY stateName",ARRAY_A);
		}else{
			$stateList=$wpdb->get_results("SELECT id,code,stateName,list FROM $sttable ORDER BY list,stateName",ARRAY_A);
		}
		foreach($stateList as $code => $value){
			$eshopstatelist[$value['id']]=$value['code'];
		}		
		foreach($_POST as $name=>$value){
			//have to do a discount code check here - otherwise things just don't work - but fine for free shipping codes
			if(strstr($name,'amount_')){
				if(isset($_SESSION['eshop_discount'.$blog_id]) && eshop_discount_codes_check()){
					$chkcode=valid_eshop_discount_code($_SESSION['eshop_discount'.$blog_id]);
					if($chkcode && apply_eshop_discount_code('discount')>0){
						$discount=apply_eshop_discount_code('discount')/100;
						$value = number_format(round($value-($value * $discount), 2),2);
						$vset='yes';
					}
				}
				if(is_discountable(calculate_total())!=0 && !isset($vset)){
					$discount=is_discountable(calculate_total())/100;
					$value = number_format(round($value-($value * $discount), 2),2);
				}
			}
			if(sizeof($stateList)>0 && ($name=='state' || $name=='ship_state')){
				if($value!='')
					$value=$eshopstatelist[$value];
			}
			$p->add_field($name, $value);
		}
		if($eshopoptions['status']!='live' && is_user_logged_in() &&  current_user_can('eShop_admin')||$eshopoptions['status']=='live'){
			$echoit .= $p->submit_bitpay_post(); // submit the fields to bitpay
    	}
      	break;
      	
   case 'bitpayipn':
   		/*
   		* the routine for when the merchant gateway sontacts your site to validate the order.
   		* may need altering to suit your gateway
   		*/
		$_SESSION = array();
		session_destroy();
		break;
}
?>
