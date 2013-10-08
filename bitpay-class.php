<?php
if ('bitpay-class.php' == basename($_SERVER['SCRIPT_FILENAME']))
     die ('<h2>Direct File Access Prohibited</h2>');
     
/*******************************************************************************
 *                      PHP bitpay IPN Integration Class
 *******************************************************************************
 *      Author:     Rich Pedley
 *      Based on: Paypal class
 *      
 *      To submit an order to bitpay, have your order form POST to a file with:
 *
 *          $p = new bitpay_class;
 *          $p->add_field('business', 'somebody@domain.com');
 *          $p->add_field('first_name', $_POST['first_name']);
 *          ... (add all your fields in the same manor)
 *          $p->submit_bitpay_post();
 *
 *      To process an IPN, have your IPN processing file contain:
 *
 *          $p = new bitpay_class;
 *          if ($p->validate_ipn()) {
 *          ... (IPN is verified.  Details are in the ipn_data() array)
 *          }
 * 
 *******************************************************************************
*/

class bitpay_class {
    
   var $last_error;                 // holds the last error encountered
   var $ipn_response;               // holds the IPN response from paypal   
   var $ipn_data = array();         // array contains the POST values for IPN
   var $fields = array();           // array holds the fields to submit to paypal
   
   function bitpay_class() {
       
      // initialization constructor.  Called when class is created.
      $this->last_error = '';
      $this->ipn_response = '';
    
   }
   
   function add_field($field, $value) {
      
      // adds a key=>value pair to the fields array, which is what will be 
      // sent to bitpay as POST variables.  If the value is already in the 
      // array, it will be overwritten.
      
      $this->fields["$field"] = $value;
   }

   function submit_bitpay_post() {
      // The user will briefly see a message on the screen that reads:
      // "Please wait, your order is being processed..." and then immediately
      // is redirected to bitpay.
      $echo= "<form method=\"post\" class=\"eshop eshop-confirm\" action=\"".$this->autoredirect."\"><div>\n";
	/*
	*
	* Grab the standard data
	*
	*/
      foreach ($this->fields as $name => $value) {
			$pos = strpos($name, 'amount');
			if ($pos === false) {
			   $echo.= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
			}else{
				$echo .= eshopTaxCartFields($name,$value);
      	    }
      }
      	/*
	  	* Changes the standard text of the redirect page.
		*/
      $refid=uniqid(rand());
      $echo .= "<input type=\"hidden\" name=\"bitpayoption1\" value=\"$refid\" />\n";
      $echo.='<label for="ppsubmit" class="finalize"><small>'.__('<strong>Note:</strong> Submit to finalize order at bitpay.','eshop').'</small><br />
      <input class="button submit2" type="submit" id="ppsubmit" name="ppsubmit" value="'.__('Proceed to Checkout &raquo;','eshop').'" /></label>';
	  $echo.="</div></form>\n";
      
      return $echo;
   }
	function eshop_submit_bitpay_post($myPOST) {
      // The user will briefly see a message on the screen that reads:
      // "Please wait, your order is being processed..." and then immediately
      // is redirected to bitpay.
      
      	/*
	  	*
	  	* Grab the standard data, but adjust for your payment gateway as below
	  	* remember most fields will actually be hidden for POSTing to your gateway
	  	*
		*/
      
      global $eshopoptions, $blog_id;
      $bitpay = $eshopoptions['bitpay'];
		$echortn='<div id="process">
         <p><strong>'.__('Please wait, your order is being processed&#8230;','eshop').'</strong></p>
	     <p>'. __('If you are not automatically redirected to bitpay, please use the <em>Proceed to bitpay</em> button.','eshop').'</p>
         <form method="post" id="eshopgatbitpay" class="eshop" action="'.$this->bitpay_url.'">
          <p>';
		$replace = array("&#039;","'", "\"","&quot;","&amp;","&");
		$bitpay = $eshopoptions['bitpay']; 
		
		/* your changes would replace this section: start*/
		
		$Cost=$myPOST['amount'];
		if(isset($myPOST['tax']))
			$Cost += $myPOST['tax'];
		if(isset($_SESSION['shipping'.$blog_id]['tax'])) $Cost += $_SESSION['shipping'.$blog_id]['tax'];
      $Cost = number_format($Cost, 2, '.', '');
      $refid=$myPOST['bitpayoption1'];

      //print_r($eshopoptions);
      //print_r(get_permalink($eshopoptions['cart_success']));
      //print_r($bitpay);
      //print_r($myPOST);
      $echortn.='
         <input type="hidden" name="action" value="checkout" />
         <input type="hidden" name="posData" value="'.$refid.'" />
         <input type="hidden" name="data" value="bT9VyKJ2tFjCk8jRwbs4A+ejt/+z+D6+1aS+xEapgmvtruoh/eISH9Qb9tIzejeSN39iQKsA8DZ8YH8xbXFN7u9FhqfdY5fDJYjLGe40Ij/nQ/455SjZ4p05dtA16gZMCQRwH7fS/xNUHQtQ+jvgXobwbYnRUNEkLxuJdYbv/yHZwAk5u1YgVFgZoHMO2D8zRZuDRnM9jhS59epYjM9gA128i5ylUzcTOIVl/8vQNsI=" />
         <input name="price" type="hidden" placeholder="Amount" value="'.$Cost.'" />
         <input name="currency" type="hidden" value="'.$bitpay['currency'].'" />
         <input type="hidden" name="buyerEmail" value="'.$bitpay['email'].'" />
         <input class="button" type="submit" id="bitpaysubmit" name="submit" value="'. __('Proceed to bitpay &raquo;','eshop').'" /></p>
	     </form>
	  </div>';
	  	/* your changes would replace this section :end	*/
		return $echortn;
   }   
   function validate_ipn() {
      // generate the post string from the _POST vars aswell as load the
      // _POST vars into an arry so we can play with them from the calling
      // script.
      foreach ($_REQUEST as $field=>$value) { 
         $this->ipn_data["$field"] = $value;
      }
     
   }
}  
?>
