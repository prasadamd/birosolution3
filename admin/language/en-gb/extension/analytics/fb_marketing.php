<?php
// Heading
$_['heading_title']    	= 'Facebook Marketing';

// Tabs
$_['text_pixel_configuration'] 		= 'FB Pixel Configuration';
$_['text_marketing_api'] 			= 'FB Marketing';
$_['text_advanced_matching_data'] 	= 'Advanced Matching Data';
$_['text_about'] 					= 'About';


// Text
$_['text_extension']    = 'Extensions';
$_['text_module']      	= 'Modules';
$_['text_success']     	= 'Success: You have modified Facebook Marketing module!';
$_['text_edit']       	= 'Edit Facebook Marketing module';

$_['text_status'] 		= 'Status';
$_['text_name'] 		= 'Name';
$_['text_path'] 		= 'Path';
$_['text_value'] 		= 'Value';
$_['text_match'] 		= 'Match';

$_['text_ajax_save']	= 'Clicking the button will instantly save the new status';

$_['text_activated']    = 'Event activated';
$_['text_deactivated']  = 'Event deactivated';
$_['text_adv_match_activated']    = 'Advanced Matching Data Type activated';
$_['text_adv_match_deactivated']    = 'Advanced Matching Data Type deactivated';

// Placeholder
$_['placeholder_path'] 		= 'Path for the event eg. \'?route=product/manufacturer&manufacturer_id=5\'';
$_['placeholder_value'] 			= 'Set a value for this event';

// Entry
$_['entry_status']     			= 'Status';
$_['entry_pixel_id']     		= 'Facebook Pixel ID';
$_['entry_product_catalog_id']  = 'Product Catalog ID';
$_['entry_events']  			= 'Pixel Events';
$_['entry_custom_events']  		= 'Custom Pixel Events';
$_['entry_advanced_matching']  	= 'Advanced Matching';
$_['entry_values_vat_inc']  	= 'Values VAT inclusive';
$_['entry_manual_only_mode']  	= 'Manual Only Mode';
$_['entry_pid']  				= 'Pixel Product ID';
$_['entry_product_feed']  		= 'Product Feed';


// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Facebook Marketing module!';

// Help
$_['help_pixel_id']				= 'Login to your <a href=\"https://www.facebook.com/ads/manager/pixel/facebook_pixel/\" target=\"_blank\"><u>Facebook Ads Management</u></a> account and select the Pixel ID, in the upper right corner. Paste the Pixel ID into this field.';
$_['help_product_catalog_id'] 	= 'The Product Catalog ID is required for Dynamic Product Ads';
$_['help_advanced_matching'] 	= 'The Facebook pixel has an advanced matching feature that enables you to send your customer data through the pixel to match more website actions with Facebook users. <a href=\"https://developers.facebook.com/docs/facebook-pixel/pixel-with-ads/conversion-tracking#advanced_match\" target=\"_blank\"><u>More</u></a>';
$_['help_values_vat_inc']		= 'Pixel values are VAT inclusive';
$_['help_manual_only_mode']		= 'Facebook Pixel is able to send button click data (\'SubscribedButtonClick event\') and page metadata (\'Microdata event\') from your website. Enable Manual Only Mode to disable sending this additional data.';
$_['help_pid']					= 'Select which product attribute will be used for the pixel parameter <i>content_ids</i>.';
$_['help_product_feed']			= '';
$_['help_custom_events']		= '<p>Input for <b>Path</b> allows different formats in relation to the <b>Match</b> setting. <strong class="text-warning">It should never include the website base domain, nor \'index.php\'.</strong> <ul><li><b>Match</b> set to <b  class="text-primary">Route</b>: the matching algorithm will only check the URI <i>query</i> part eg. <i>?route=folder/controller</i>.<br /> It is allowed to input the route <b class="text-danger">without <i>?route=</i></b>, so for <b><i>?route=folder/controller</i></b> simply input <b class="text-primary"><i>folder/controller</i></b> . <br/>If there are other $_GET parameters in the URL (eg. <i>&tracking_id=x458eae458</i>), these will be ignored. <b class="text-success">Recommended for most purposes.</b></li><br/><li><b>Match</b> set to <b class="text-primary">Strict</b>: input can be an <b>SEO URL Alias keyword</b> or the URI <i>query</i> part eg. <b><i>?route=folder/controller</i></b>.<br/> Input must include the route <b class="text-danger">with <i>?route=</i></b>.<br/>Other $_GET parameters can be included here as well: eg. <b class="text-primary"><i>?route=checkout/paymentcomplete&tracking_id=x548eae458&status=complete</i></b></li><br /><li><b>Match</b> set to <b class="text-primary">Ends with</b>: recommended input should be a <b>single unique (SEO URL Alias) keyword</b>. Only produces a match when the keyword is found at the end of the URI path, after the last forward slash. $_GET parameters are discarded:<br />eg. Path is set to <b class="text-primary">leather-jacket-black</b> will <b class="text-success">produce a match</b> for <i>www.yourwebshop.com/mens/clothing/jackets/<b class="text-primary">leather-jacket-black</b>?tracking=1584456&p_name=leather-jacket</i> <br/>www.yourwebshop.com/mens/clothing/jackets?tracking=1584456&p_name=<b class="text-primary">leather-jacket-black</b> <b class="text-warning">will NOT produce a match</b>.</i>  .</li><br ?><li><b>Match</b> set to <b class="text-primary">Contains</b>: recommended input should be a <b>single unique (SEO URL Alias) keyword</b>. Whenever this keyword is found in a URL, a match is produced and the custom event is triggered. Example: the input for Path is set to <b class="text-primary">clothing</b> then the event will be triggered on:<br/><i>www.yourwebshop.com/mens/<u>clothing</u>/jackets/leather-jacket-black</i> <b>AND</b><br/><i>www.yourwebshop.com/womens/<u>clothing</u>/lingerie</i> <b>AND</b><br/><i>www.yourwebshop.com/<u>clothing</u></i>.<br/><b class="text-warning">Use with caution!</b></li></ul></p><p><b class="text-primary">Value</b> is either a <b>token</b> or a <b>float value</b>.</p><p><b class="text-primary">Tokens:</b> <ul><li><b>[order_total]</b> : the order total retrieved from the last confirmed order</li><li><b>[order_subtotal]</b> :  the order subtotal (no taxes, shipping, fees or discounts) retrieved from the last confirmed order</li><li><b>[cart_total]</b> : the actual cart total</li><li><b>[cart_subtotal]</b> : the cart subtotal</li><li><b>[product_price]</b> : the product price </li></ul></p><p>Tokens only work on relevant pages, for instance <b>[product_price]</b> is not relevant on an information or landing page. In that case the pixel event value parameter will be set to zero (0.00).</p>';
