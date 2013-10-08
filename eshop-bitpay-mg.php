<?php
/*
Plugin Name: eShop bitpay MG
Plugin URI: https://github.com/nextime/bitpay-eshop-mg
Description: BitPay Merchant Gatway for eShop
Version: 0.0.1
Author: Franco Lanza
Author URI: http://www.nexlab.it

    Copyright 2012 Franco Lanza  (email : franco@unixmedia.it)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
register_activation_hook(__FILE__,'eshopbitpay_activate');
function eshopbitpay_activate(){
	/*
	* Activation routines
	*/
	global $wpdb;
	$opts=get_option('active_plugins');
	$eshopthere=false;
	foreach($opts as $opt){
		if($opt=='eshop/eshop.php')
			$eshopthere=true;
	}
	if($eshopthere==false){
		deactivate_plugins('eshop-bitpay-mg.php'); //Deactivate ourself
		wp_die(__('ERROR! eShop is not active.','eshop')); 
	}
	/*
	* insert email template for use with this merchant gateway, if 151 is changed, then ipn.php needs amending as well 
	*/
	$table = $wpdb->prefix ."eshop_emails";
	$esubject=__('Your order from ','eshop').get_bloginfo('name');
	$wpdb->query("INSERT INTO ".$table." (id,emailType,emailSubject) VALUES ('151','".__('Automatic bitpay email','eshop')."','$esubject')"); 
	
}
add_action('eshop_setting_merchant_load','eshopmgpage');
function eshopmgpage($thist){

	/*
	* adding the meta box for this gateway
	*/
	add_meta_box('eshop-m-bitpay', __('bitpay','eshop'), 'bitpay_box', $thist->pagehook, 'normal', 'core');
}

function bitpay_box($eshopoptions) {
	/*
	* the meta box content, obviously you have to set up the required fields for your gateway here
	*/
	if(isset($eshopoptions['bitpay'])){
		$eshopbitpay = $eshopoptions['bitpay']; 
	}else{
		$eshopbitpay['email']='';
		$eshopbitpay['apiKey']='';
      $eshopbitpay['currency']='BTC';
	}
	//add the image
	$eshopmerchantimgpath=WP_PLUGIN_DIR.'/eshop-bitpay-mg/bitpay.png';
	$eshopmerchantimgurl=WP_PLUGIN_URL.'/eshop-bitpay-mg/bitpay.png';
	$dims[3]='';
	if(file_exists($eshopmerchantimgpath))
	$dims=getimagesize($eshopmerchantimgpath);
	echo '<fieldset>';
	echo '<p class="eshopgatbitpay"><img src="'.$eshopmerchantimgurl.'" '.$dims[3].' alt="bitpay" title="bitpay" /></p>'."\n";
?>
	<p class="cbox"><input id="eshop_methodbitpay" name="eshop_method[]" type="checkbox" value="bitpay"<?php if(in_array('bitpay',(array)$eshopoptions['method'])) echo ' checked="checked"'; ?> /><label for="eshop_methodbitpay" class="eshopmethod"><?php _e('Accept payment by bitpay','eshop'); ?></label></p>
	<label for="eshop_bitpayemail"><?php _e('Email address for notifications','eshop'); ?></label><input id="eshop_bitpayemail" name="bitpay[email]" type="text" value="<?php echo $eshopbitpay['email']; ?>" size="30" maxlength="50" /><br />
	<label for="eshop_bitpayapiKey"><?php _e('bitpay button encrypted data','eshop'); ?></label><input id="eshop_bitpayapiKey" name="bitpay[apiKey]" type="text" value="<?php echo $eshopbitpay['apiKey']; ?>" size="200" maxlength="5000" /><br />
   <label for="eshop_bitpaycurrency"><?php _e('Currency','eshop'); ?></label><input id="eshop_bitpaycurrency" name="bitpay[currency]" type="text" value="<?php echo $eshopbitpay['currency']; ?>" size="60" /><br />
	</fieldset>
<?php
}

add_filter('eshop_setting_merchant_save','bitpaysave',10,2);
function bitpaysave($eshopoptions,$posted){
	/*
	* save routine for the fields you added above
	*/
	global $wpdb;
	$bitpaypost['email']=$wpdb->escape($posted['bitpay']['email']);
	$bitpaypost['apiKey']=$wpdb->escape($posted['bitpay']['apiKey']);
	$bitpaypost['currency']=$wpdb->escape($posted['bitpay']['currency']);

	$eshopoptions['bitpay']=$bitpaypost;
	return $eshopoptions;
}

add_action('eshop_include_mg_ipn','eshopbitpay');
function eshopbitpay($eshopaction){

	/*
	* adding the necessary link for the instant payment notification of your gateway
	*/
	if($eshopaction=='bitpayipn'){
		include_once WP_PLUGIN_DIR.'/eshop-bitpay-mg/ipn.php';
	}
}

add_filter('eshop_merchant_img_bitpay','bitpayimg');
function bitpayimg($array){
	/*
	* adding the image for this gateway, for use on the front end of the site
	*/
	$array['path']=WP_PLUGIN_DIR.'/eshop-bitpay-mg/bitpay.png';
	$array['url']=WP_PLUGIN_URL.'/eshop-bitpay-mg/bitpay.png';
	return $array;
}
add_filter('eshop_mg_inc_path','bitpaypath',10,2);
function bitpaypath($path,$paymentmethod){
	/*
	* adding another necessary link for the instant payment notification of your gateway
	*/
	if($paymentmethod=='bitpay')
		return WP_PLUGIN_DIR.'/eshop-bitpay-mg/ipn.php';
	return $path;
}
add_filter('eshop_mg_inc_idx_path','bitpayidxpath',10,2);
function bitpayidxpath($path,$paymentmethod){
	/*
	* adding the necessary link to the class for this gateway
	*/
	if($paymentmethod=='bitpay')
		return WP_PLUGIN_DIR.'/eshop-bitpay-mg/bitpay-class.php';
	return $path;
}
//message on fail.
add_filter('eshop_show_success', 'eshop_bitpay_return_fail',10,3);
function eshop_bitpay_return_fail($echo, $eshopaction, $postit){
	/*
	* failed payment, you can add in details for this, will need tweaking for your gateway
	*/
	//these are the successful codes, all others fail
	$bitpayrescodes=array('00','08','10','11','16');
	if($eshopaction=='bitpayipn'){
		if($postit['bitpayTrxnStatus']=='False' && !in_array($postit['bitpayresponseCode'],$bitpayrescodes))
			$echo .= '<p>There was a problem with your order, please contact admin@ ... quoting Error Code '.$postit['bitpayresponseCode']."</p>\n";
	}
	return $echo;
}
?>
