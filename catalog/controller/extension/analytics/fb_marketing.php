<?php
class ControllerExtensionAnalyticsFbMarketing extends Controller { 
    private $pixel_events = array(
    	'viewcontent'			=> 'ViewContent',
    	'search'				=> 'Search',
    	'addtocart'				=> 'AddToCart',
    	'addtowishlist'			=> 'AddToWishlist',
    	'initiatecheckout'		=> 'InitiateCheckout',
    	'addpaymentinfo'		=> 'AddPaymentInfo',
    	'purchase'				=> 'Purchase',
    	'lead'					=> 'Lead',
    	'completeregistration'	=> 'CompleteRegistration'
    );
    
    public function index() {
		$prefix = $this->prefix();
		
		$fb_data = $this->getSettingData();

		foreach ($fb_data as $key => $value) {
			$data_key = substr($key, strlen($prefix . 'fb_marketing_'));
			$$data_key = $value;
		}
		
		if (!empty($status) && !empty($pixel_id)) {
			$fb_pixel_code = "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','//connect.facebook.net/en_US/fbevents.js'); " . (!empty($manual_only_mode) ? "fbq('set', 'autoConfig', 'false', '%s');" : "" ) . " fbq('init', '%s'";
			
			if (!empty($advanced_matching) && !empty($advanced_matching_data)) {
				$fb_pixel_code .= $this->getAdvancedMatchingData($advanced_matching_data);
			}
			
			$fb_pixel_code .= ");";
			
			$fb_pixel_code_postfix = '</script>';
			
			$facebook_pixel = str_replace('%s', $pixel_id , $fb_pixel_code);
			
			$dynamic_event = !empty($this->request->post['dynamic_event']) ? $this->request->post['dynamic_event'] : '' ;
			
			// Events
			$event = $this->getEvent($events, $product_catalog_id, $dynamic_event);
			
			// Bring all parts together	
			$pixel = $facebook_pixel . $event . $fb_pixel_code_postfix;	
			
			return html_entity_decode($pixel, ENT_QUOTES, 'UTF-8');
			
		}
		
	}
	
	protected function getEvent($event_data, $product_catalog_id = '', $dynamic_event = '') {
		
		$event = '';
		
		if(!empty($dynamic_event)) {
			switch ($dynamic_event) {
				case ('CompleteRegistration') : 
					$route = 'account/success';	
					break;
			} 
		} else if(isset($this->request->get['route'])) {
			$route = $this->request->get['route'];
		} else {
			$route = '';
		}		
		
		// Mollie return page fix
		if (strpos($route, 'payment/mollie_') === 0) {
			$route = 'checkout/success';
			$this->session->data['last_order_id'] = $this->request->get['order_id'];
		}
		
		// Custom Events
		$custom_event = $this->customEvent();
		
		if(!empty($custom_event)) {
			return $custom_event;
		}

		switch ($route) {
			case 'checkout/success' : 
				if (!empty($event_data['purchase']['status']) && !empty($this->session->data['last_order_id'])) {
					$options = $this->getEventOptions($event_data, 'purchase', $product_catalog_id);
					$event = 'fbq(\'track\', \'Purchase\', {' . $options . '});';
				}
				break;
			case 'product/product':
				if (!empty($event_data['viewcontent']['status'])) {				
					$options = $this->getEventOptions($event_data, 'viewcontent', $product_catalog_id);
					$event = "fbq('track', 'ViewContent', {" . $options . "});";
				}
				break;
			case 'checkout/checkout';	
			case 'quickcheckout/checkout';
				if (!empty($event_data['initiatecheckout']['status'])) {				
					$options = $this->getEventOptions($event_data, 'initiatecheckout', $product_catalog_id);
					$event = "fbq('track', 'InitiateCheckout', {" . $options . "});";
				}
				break;
			case 'account/success';
			case 'affiliate/success';
				if (!empty($event_data['completeregistration']['status'])) {				
					$options = $this->getEventOptions($event_data, 'completeregistration', $product_catalog_id);
					$event = "fbq('track', 'CompleteRegistration', {" . $options . "});";
				}
				break;
			case 'product/search';
				if (!empty($event_data['search']['status'])) {				
					$options = $this->getEventOptions($event_data, 'search', $product_catalog_id);
					$event = 'fbq(\'track\', \'Search\', {' . $options . '});';
				}
				break;
			default :
				if (!empty($event_data['pageview']['status'])) {				
					$options = $this->getEventOptions($event_data, 'pageview', $product_catalog_id);
					$event = 'fbq(\'track\', \'PageView\', {'. $options . '});';	
				}
		}
		
		return $event;
	}
	
	protected function getEventOptions($event_data, $event, $product_catalog_id) {
		$prefix = $this->prefix();
		
		$vat_incl = $this->config->get($prefix . 'fb_marketing_values_vat_inc');
		$fb_currency = $this->config->get('config_currency');
		$pid = $this->config->get($prefix . 'fb_marketing_pid');
		
		$products = array();
		$product_count = 0;
		$options = '';
		
		$contents = array();
		
		switch ($event) {
			case 'purchase':
				
				if(!empty($this->session->data['last_order_id'])) {
					$order_id = $this->session->data['last_order_id'];
				}
				
				// var_dump($order_id);
				
				if (!empty($order_id)) {
					// Complete Checkout
					$query = $this->db->query("SELECT op.*, p.sku, p.upc, p.ean, p.jan, p.mpn, p.isbn FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "product` p ON op.product_id = p.product_id WHERE `order_id` = '" . (int)$order_id . "'");
					foreach($query->rows as $product) {
						$content = array();
						if(!empty($product[$pid])) {
							$products[] = '\'' . $product[$pid] . '\'';
							$content['id'] = $product[$pid];
						} else {
							$products[] = '\'' . $product['product_id'] . '\'';
							$content['id'] = $product['product_id'];
						}	
						
						$product_count += $product['quantity'];
						
						$content['quantity'] = $product['quantity'];
						
						if(!empty($vat_incl)) {
							$content['item_price'] = $this->formatValue($product['price'] + $product['tax']);
						} else {
							$content['item_price'] = $this->formatValue($product['price']);
						}
							
						$contents[] = $content;
					}
					
					$cart_products = implode(',' , $products);
					$order_value = 0;
					
					if(!empty($vat_incl)) {
						$query = $this->db->query("SELECT total FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
						if ($query->num_rows) {
							$order_value = $query->row['total'];
						}
					} else {
						$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int)$order_id . "'");
						if ($query->num_rows) {
							$totals = $query->rows;
						
							$sort_order = array();

							foreach ($totals as $key => $value) {
								$sort_order[$key] = $value['sort_order'];
							}
							
							array_multisort($sort_order, SORT_ASC, $totals);
							
							array_pop($totals);
							
							foreach ($totals as $total) {
								if ($total['code'] != 'tax') {
									$order_value = $order_value +  $total['value'];
								}
							}
						}
					}

					$options .= 'value: \'' . $this->formatValue($order_value) . '\'';
					$options .= ', currency: \'' . $fb_currency . '\'';
					$options .= ', num_items: \'' . $product_count . '\'';
					$options .= ', content_ids: [' . $cart_products . ']';
					$options .= ', content_type: \'product\'';
					
					if(!empty($contents)) {
						$contents_json = array();
						foreach ($contents as $v) {
							$contents_json[] = json_encode($v) ;
						}
						$options .= ', contents: [' . implode(',' , $contents_json) . ']';
					}
									
					$options .= !empty($product_catalog_id) ? ', product_catalog_id: \''. $product_catalog_id . '\'' : '';
					
					unset($order_id);
					
				}
				break;
				
			case 'viewcontent':
				// View Content
				$this->load->model('catalog/product');
				$product = $this->model_catalog_product->getProduct($this->request->get['product_id']);
				
				$value = 0;
				$content = array();
				
				$price = empty($product['special']) ? $product['price'] : $product['special'] ;
				if(!empty($vat_incl)) {
					$value  = $this->tax->calculate($price, $product['tax_class_id']);
				} else {
					$value = $price;
				}
				
				if(!empty($product[$pid])) {
					$content['id'] = $product[$pid];
				} else {
					$content['id'] = $product['product_id'];
				}
				
				$content['quantity'] = $product['minimum'];
				$content['item_price'] = $value;
			
				$options .= 'value: \'' . $this->formatValue($value)  . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
				
				if(!empty($product[$pid])) {
					$options .= ', content_ids: [\'' . $product[$pid] . '\']';
				} else {
					$options .= ', content_ids: [\'' . $product['product_id'] . '\']';
				}
				
				$options .= ', content_name: \'' . $this->db->escape(htmlspecialchars($product['name'])) . '\'';
				$options .= ', content_type: \'product\'';
				
				if(!empty($content)) {
					$options .= ', contents: [' . json_encode($content) . ']';
				}
				
				$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';

				break;
			case 'initiatecheckout';
				// Initiate Checkout
				$cart = $this->cart->getProducts();
				foreach($cart as $cart_product) {
					$this->load->model('catalog/product');
					$product = $this->model_catalog_product->getProduct($cart_product['product_id']);
					
					$content = array();
					
					if(!empty($product[$pid])) {
						$products[] = '\'' . $product[$pid] . '\'';
						$content['id'] = $product[$pid];
					} else {
						$products[] = '\'' . $product['product_id'] . '\'';
						$content['id'] = $product['product_id'];
					}
					
					$content['quantity'] = $cart_product['quantity'];
						
					if(!empty($vat_incl)) {
						$content['item_price'] = $this->formatValue($this->tax->calculate($cart_product['price'],$cart_product['tax_class_id']));
					} else {
						$content['item_price'] = $this->formatValue($cart_product['price']);
					}
						
					$contents[] = $content;				
				}
				
				$cart_products = implode(',', $products);
				
				$value = $this->getCartTotal($vat_incl);
				
				$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
				$options .= ', content_ids: [' . $cart_products  . ']';
				$options .= ', num_items: \'' . $this->cart->countProducts() . '\'';
				$options .= ', content_type: \'product\'';
				
				if(!empty($contents)) {
					$contents_json = array();
					foreach ($contents as $v) {
						$contents_json[] = json_encode($v) ;
					}
					$options .= ', contents: [' . implode(',' , $contents_json) . ']';
				}
				
				$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';
			
				break;
			case 'completeregistration';
				// Complete Registration
				$value = $event_data['completeregistration']['value'];
				
				$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';

				break;
			case 'search';
				// Search
				$search_string = (!empty($this->request->get['search']) ? $this->request->get['search'] : '' );	
				if (!empty($search_string)){			
					$this->load->model('catalog/product');
					
					$params = array('filter_name' => $search_string);
					if (!empty($this->request->get['description'])) {
						$params['filter_description'] = $this->request->get['description'];
					}
					
					$search_results = $this->model_catalog_product->getProducts($params);

					$content_ids = '';
					$content = array();
									
					foreach($search_results as $key => $product) {
						if(!empty($product[$pid])) {
							$products[] = '\'' . $product[$pid] . '\'';
							$content['id'] = $product[$pid];
						} else {
							$products[] = '\'' . $product['product_id'] . '\'';
							$content['id'] = $product['product_id'];
						}
						
						$content['quantity'] = $product['minimum'];
						
						if(!empty($vat_incl)) {
							$content['item_price'] = $this->formatValue($this->tax->calculate($product['price'],$product['tax_class_id']));
						} else {
							$content['item_price'] = $this->formatValue($product['price']);
						}
						
						$contents[] = $content;			
					}
					
					$content_ids = implode(',',  $products);
					$value = $event_data['search']['value'];
					
					$options .= 'value: \'' . $this->formatValue($value) . '\'';
					$options .= ', currency: \'' . $fb_currency . '\'';
					$options .= ', search_string: \'' . $this->db->escape($search_string) . '\'';
					$options .= ', content_ids: [' . $content_ids  . ']';
					$options .= ', content_type: \'product\'';
					
					if(!empty($contents)) {
						$contents_json = array();
						foreach ($contents as $v) {
							$contents_json[] = json_encode($v) ;
						}
						$options .= ', contents: [' . implode(',' , $contents_json) . ']';
					}
					
					$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';
				}
				break;
			case 'pageview':
			default :
				// Default PageView, displayed on all pages
				$value = $event_data['pageview']['value'];
					
				$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
		}
		
		return $options;
	}
	
	private function customEvent(){
		$prefix = $this->prefix();
		
		$fb_data = $this->getSettingData();
		$custom_event = false;
		$status = false;
		
		if (!empty($fb_data[$prefix . 'fb_marketing_custom_events'])) {
			$custom_events = $fb_data[$prefix . 'fb_marketing_custom_events'];
			$product_catalog_id = $fb_data[$prefix . 'fb_marketing_product_catalog_id'];
		
			foreach ($custom_events as $event) {
				$status = (!empty($this->matchURI($event['path'], $event['match'])) && !empty($event['status'])) ?  true : false ;			
				if ($status) {
					$custom_event = $this->constructEventString($event,$product_catalog_id);
				
				}
			}
		}
		
		return $custom_event;
	}
	
	private function matchURI($path,$match) {		
		$output = false;
				
		switch($match) {
			case 'route':
				$query_array = array();
				$url = parse_url($_SERVER['REQUEST_URI']);
				if(!empty($url['query'])) {
					parse_str($url['query'], $query_array);
				} else if(!empty($this->request->get['route'])) {
					$query_array['route'] = $this->request->get['route'];
				}
				if (!empty($query_array['route'])) {
					$output = (strcasecmp($path, $query_array['route']) === 0 ) ? true : false ;
				} else {
					$output  = false;
				}
				break;
			case 'strict':
				$config_url = parse_url($this->config->get('config_url'));
				$output = (strcasecmp($path,str_replace($config_url['path'] . 'index.php', '', $_SERVER['REQUEST_URI'])) === 0 ) ? true : false ;
				break;
			case 'ends':
				$url = parse_url($_SERVER['REQUEST_URI']);
				$output = !empty($path == substr($url['path'], -strlen($path))) ? true : false ;
				break;
			case 'contains':
				$output = !empty(strpos($_SERVER['REQUEST_URI'],$path)) ? true : false ;
				break;
		}
		return $output;
	}
	
	private function constructEventString($event, $product_catalog_id) {
		$type = $event['type'];
		$value = $event['value'];
		$event_string = '';
		
		$options = $this->getCustomEventOptions($event, $type, $product_catalog_id);
		$event_string = "fbq('track', '" . $this->pixel_events[$type] . "', {" . $options . "});";
		
		return $event_string;	
	}
	
	protected function getCustomEventOptions($event_data, $event, $product_catalog_id) {
		$prefix = $this->prefix();
		$fb_currency = $this->config->get('config_currency');
		
		$vat_incl = $this->config->get($prefix . 'fb_marketing_values_vat_inc');
		$pid = $this->config->get($prefix . 'fb_marketing_pid');
		
		$products = array();
		$product_count = 0;
		$options = '';
		
		$contents = array();
		$tokens = array(
		'[product_price]',
		'[cart_total]',
		'[cart_subtotal]',
		'[order_total]',
		'[order_subtotal]'
		);
		
		switch ($event) {
			case 'purchase':
				
				if(!empty($this->session->data['last_order_id'])) {
					$order_id = $this->session->data['last_order_id'];
				} elseif (!empty($this->request->get['debug'])) {
					$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` ORDER BY `order_id` DESC LIMIT 1");
					$order_id = $query->row['order_id'];
				}
				
				if (!empty($order_id)) {
					// Complete Checkout
					$query = $this->db->query("SELECT op.*, p.sku, p.upc, p.ean, p.jan, p.mpn, p.isbn FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "product` p ON op.product_id = p.product_id WHERE `order_id` = '" . (int)$order_id . "'");
					foreach($query->rows as $product) {
						$content = array();
						if(!empty($product[$pid])) {
							$products[] = '\'' . $product[$pid] . '\'';
							$content['id'] = $product[$pid];
						} else {
							$products[] = '\'' . $product['product_id'] . '\'';
							$content['id'] = $product['product_id'];
						}	
						
						$product_count += $product['quantity'];
						
						$content['quantity'] = $product['quantity'];
						
						if(!empty($vat_incl)) {
							$content['item_price'] = $this->formatValue($product['price'] + $product['tax']);
						} else {
							$content['item_price'] = $this->formatValue($product['price']);
						}
							
						$contents[] = $content;
					}
					
					$cart_products = implode(',' , $products);
					$order_value = 0;
					
					if(!empty($vat_incl)) {
						$query = $this->db->query("SELECT total FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
						if ($query->num_rows) {
							$order_value = $query->row['total'];
						}
					} else if($event_data['value'] == '[order_subtotal]') {
						$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY sort_order ASC");
						
						if ($query->num_rows) {
							$totals = $query->rows;
							
							$subtotal = array_filter(
								$totals, 
								function($v, $k) {
									if ($v['code'] === 'sub_total') return $v;
								}, 
								ARRAY_FILTER_USE_BOTH
							);
							$order_value = reset($subtotal)['value'];
						}
					} else {
						$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int)$order_id . "'");
						if ($query->num_rows) {
							$totals = $query->rows;
						
							$sort_order = array();

							foreach ($totals as $key => $value) {
								$sort_order[$key] = $value['sort_order'];
							}
							
							array_multisort($sort_order, SORT_ASC, $totals);
							
							array_pop($totals);
							
							foreach ($totals as $total) {
								if ($total['code'] != 'tax') {
									$order_value = $order_value +  $total['value'];
								}
							}
						}
					}
					
					$options .= 'value: \'' . $this->formatValue((!in_array($event_data['value'],$tokens) ? $event_data['value'] : (in_array($event_data['value'], array('[order_total]', '[order_subtotal]')) ? $order_value : '0.00' ) )) . '\'';
				
					$options .= ', currency: \'' . $fb_currency . '\'';
					$options .= ', num_items: \'' . $product_count . '\'';
					$options .= ', content_ids: [' . $cart_products . ']';
					$options .= ', content_type: \'product\'';
					
					if(!empty($contents)) {
						$contents_json = array();
						foreach ($contents as $v) {
							$contents_json[] = json_encode($v) ;
						}
						$options .= ', contents: [' . implode(',' , $contents_json) . ']';
					}
									
					$options .= !empty($product_catalog_id) ? ', product_catalog_id: \''. $product_catalog_id . '\'' : '';
					
					unset($order_id);
					
				}
				break;
			case 'lead':	
			case 'viewcontent':
				// View Content
				$value = 0;
				$content = array();
				
				if(!empty($this->request->get['product_id'])) {
					$this->load->model('catalog/product');
					$product = $this->model_catalog_product->getProduct($this->request->get['product_id']);
				
					if($event_data['value'] == '[product_price]') {
						$price = empty($product['special']) ? $product['price'] : $product['special'] ;
						if(!empty($vat_incl)) {
							$value  = $this->tax->calculate($price, $product['tax_class_id']);
						} else {
							$value = $price;
						}
					} else {
						$value = $event_data['value'];
					}
					
					if(!empty($product[$pid])) {
						$content['id'] = $product[$pid];
					} else {
						$content['id'] = $product['product_id'];
					}
					
					$content['quantity'] = $product['minimum'];
					$content['item_price'] = $value;
					$name = $product['name'];
				
				} else {
					$value = !in_array($event_data['value'], $tokens) ? $event_data['value'] : '0.00' ;
					$name = $this->document->getTitle();
				}
				
				$options .= 'value: \'' . $this->formatValue($value)  . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
				$options .= ', content_name: \'' . $this->db->escape($name) . '\'';
				
				if(!empty($this->request->get['product_id'])) {
					if(!empty($product[$pid])) {
						$options .= ', content_ids: [\'' . $product[$pid] . '\']';
					} else {
						$options .= ', content_ids: [\'' . $product['product_id'] . '\']';
					}
				
					$options .= ', content_type: \'product\'';
				
					if(!empty($content)) {
						$options .= ', contents: [' . json_encode($content) . ']';
					}
				}
				
				$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';

				break;
			case 'initiatecheckout';
				// Initiate Checkout
				$cart = $this->cart->getProducts();
				foreach($cart as $cart_product) {
					$this->load->model('catalog/product');
					$product = $this->model_catalog_product->getProduct($cart_product['product_id']);
					
					$content = array();
					
					if(!empty($product[$pid])) {
						$products[] = '\'' . $product[$pid] . '\'';
						$content['id'] = $product[$pid];
					} else {
						$products[] = '\'' . $product['product_id'] . '\'';
						$content['id'] = $product['product_id'];
					}
					
					$content['quantity'] = $cart_product['quantity'];
						
					if(!empty($vat_incl)) {
						$content['item_price'] = $this->formatValue($this->tax->calculate($cart_product['price'],$cart_product['tax_class_id']));
					} else {
						$content['item_price'] = $this->formatValue($cart_product['price']);
					}
						
					$contents[] = $content;				
				}
				
				$cart_products = implode(',', $products);
				
				if($event_data['value'] == '[cart_subtotal]') {
					$value = $this->cart->getSubTotal();
				} else {
					$value = $this->getCartTotal($vat_incl);
				}
				
				$options .= 'value: \'' . $this->formatValue((!in_array($event_data['value'],$tokens) ? $event_data['value'] : (in_array($event_data['value'], array('[cart_total]','[cart_subtotal]')) ? $value : '0.00' ) )) . '\'';
				
				//$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
				$options .= ', content_ids: [' . $cart_products  . ']';
				$options .= ', num_items: \'' . $this->cart->countProducts() . '\'';
				$options .= ', content_type: \'product\'';
				
				if(!empty($contents)) {
					$contents_json = array();
					foreach ($contents as $v) {
						$contents_json[] = json_encode($v) ;
					}
					$options .= ', contents: [' . implode(',' , $contents_json) . ']';
				}
				
				$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';
			
				break;
			case 'completeregistration';
				// Complete Registration
				$value = $event_data['value'];
				
				$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';

				break;
			case 'search';
				// Search
				$search_string = (!empty($this->request->get['search']) ? $this->request->get['search'] : '' );	
				if (!empty($search_string)){			
					$this->load->model('catalog/product');
					
					$params = array('filter_name' => $search_string);
					if (!empty($this->request->get['description'])) {
						$params['filter_description'] = $this->request->get['description'];
					}
					
					$search_results = $this->model_catalog_product->getProducts($params);

					$content_ids = '';
					$content = array();
									
					foreach($search_results as $key => $product) {
						if(!empty($product[$pid])) {
							$products[] = '\'' . $product[$pid] . '\'';
							$content['id'] = $product[$pid];
						} else {
							$products[] = '\'' . $product['product_id'] . '\'';
							$content['id'] = $product['product_id'];
						}
						
						$content['quantity'] = $product['minimum'];
						
						if(!empty($vat_incl)) {
							$content['item_price'] = $this->formatValue($this->tax->calculate($product['price'],$product['tax_class_id']));
						} else {
							$content['item_price'] = $this->formatValue($product['price']);
						}
						
						$contents[] = $content;			
					}
					
					$content_ids = implode(',',  $products);
					$value = $event_data['value'];
					
					$options .= 'value: \'' . $this->formatValue($value) . '\'';
					$options .= ', currency: \'' . $fb_currency . '\'';
					$options .= ', search_string: \'' . $this->db->escape($search_string) . '\'';
					$options .= ', content_ids: [' . $content_ids  . ']';
					$options .= ', content_type: \'product\'';
					
					if(!empty($contents)) {
						$contents_json = array();
						foreach ($contents as $v) {
							$contents_json[] = json_encode($v) ;
						}
						$options .= ', contents: [' . implode(',' , $contents_json) . ']';
					}
					
					$options .= !empty($product_catalog_id) ? ', product_catalog_id:\''. $product_catalog_id . '\'' : '';
				}
				break;
			case 'pageview':
			default :
				// Default PageView, displayed on all pages
				$value = '0.00';
					
				$options .= 'value: \'' . $this->formatValue($value) . '\'';
				$options .= ', currency: \'' . $fb_currency . '\'';
		}
		
		return $options;
	}
	
	protected function getAdvancedMatchingData($data_types) {
		
		if ($this->customer->isLogged() && !empty($data_types)){
			$advanced_matching_data = ", {";
			
			foreach ($data_types as $key => $status) {
				$advanced_matching_data .= "" . $key . ": '" . $this->getTypeData($key) . "',";
				
			}
			
			$advanced_matching_data = substr($advanced_matching_data,0,-1);
			$advanced_matching_data .= "}";
			
			return $advanced_matching_data;
		} else if (!empty($this->session->data['last_order_id']) && !empty($data_types)){
			$advanced_matching_data = ", {";
			
			foreach ($data_types as $key => $status) {
				$advanced_matching_data .= "" . $key . ": '" . $this->getTypeDataOrder($key) . "',";
				
			}
			
			$advanced_matching_data = substr($advanced_matching_data,0,-1);
			$advanced_matching_data .= "}";
			
			return $advanced_matching_data;
		}
	}
	
	protected function getTypeData($type) { 
		switch ($type) {
		
			case 'em':
				return strtolower(trim($this->customer->getEmail()));
				break;
			case 'fn': 
				return strtolower(trim($this->customer->getFirstName()));
				break;
			case 'ln': 
				return strtolower(trim($this->customer->getLastName()));
				break;
			case 'ph': 
				return $this->cleanPhoneNumber($this->customer->getTelephone());
				break;
			case 'ct':
				
				$address_id = $this->customer->getAddressID();
				if(!empty($address_id )) {
					$this->load->model('account/address');
					$address = $this->model_account_address->getAddress($this->customer->getAddressID());
			
					if(!empty($address['city'])) {
						return strtolower(trim($address['city']));
					}
				}
				break;
			case 'st':
				$address_id = $this->customer->getAddressID();
				if(!empty($address_id )) {
					$this->load->model('account/address');
					$address = $this->model_account_address->getAddress($this->customer->getAddressID());
			
					if(!empty($address['zone_id'])) {
						return strtolower(trim($this->getState($address['zone_id'])));
					}
				}
				break;		
			case 'zp':
				$address_id = $this->customer->getAddressID();
				if(!empty($address_id )) {
					$this->load->model('account/address');
					$address = $this->model_account_address->getAddress($this->customer->getAddressID());
			
					if(!empty($address['postcode'])) {
						return (int)(trim($address['postcode']));
					}
				}
				break;		
		}
	}
	
	protected function getTypeDataOrder($type) { 
		if (!empty($this->session->data['last_order_id'])) {
			$this->load->model('checkout/order');
		
			$order = $this->model_checkout_order->getOrder($this->session->data['last_order_id']);
		
			switch ($type) {
				case 'em':
					return strtolower(trim($order['email']));
					break;
				case 'fn': 
					return strtolower(trim($order['firstname']));
					break;
				case 'ln': 
					return strtolower(trim($order['lastname']));
					break;
				case 'ph': 
					return $this->cleanPhoneNumber($order['telephone']);
					break;
				case 'ct':
					if(!empty($order['payment_city'])) {
						return strtolower(trim($order['payment_city']));
					}
					break;
				case 'st':
					if(!empty($order['payment_zone_id'])) {
						return strtolower(trim($this->getState($order['payment_zone_id'])));
					}
					break;		
				case 'zp':
					if(!empty($order['payment_postcode'])) {
						return (int)(trim($order['payment_postcode']));
					}
					break;		
			}
		}
	}
	
	protected function getSettingData () {
		$store_id = $this->config->get('config_store_id');
		$this->load->model('setting/setting');
		$prefix = $this->prefix();
		
		return $this->model_setting_setting->getSetting($prefix . 'fb_marketing',$store_id);
	}
	
	protected function cleanPhoneNumber($number) {
		$number = str_replace('+', '00', $number);
		$number = str_replace(' ', '', $number);
		return $number;
	}
	
	protected function getState($zone_id) {
		$this->load->model('localisation/zone');
		$state = $this->model_localisation_zone->getZone($zone_id);
		return $state['code'];
	}
	
	protected function formatValue ($value) {
		
		if(empty($value)) {
			$value = 0.00;
		}
		return number_format($value, 2 , '.' , '' );
	}
	
	protected function getCartTotal($vat_incl) {
		// Totals
		$order_data = array();

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array. 
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);
	
		// Register events
	 	if (version_compare(VERSION, '3.0.0.0', '>=')) {
	 		$folder = 'setting';
	 		$prefix = 'total_';
	 	} else {
	 		$folder = 'extension';
	 		$prefix = '';
	 	}
	
		$this->load->model($folder .'/extension');
		$model_path = 'model_' . $folder . '_extension';
		
		$sort_order = array();

		$results = $this->$model_path->getExtensions('total');

		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
		}

		array_multisort($sort_order, SORT_ASC, $results);

		foreach ($results as $result) {			
			if ($this->config->get($prefix . $result['code'] . '_status')) {	
				$code = $result['code'];
				if (version_compare(VERSION, '2.3.0.0', '>=')) {
					$this->load->model('extension/total/' . $code);
					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $code}->getTotal($total_data);
				} else {
					$this->load->model('total/' . $code);
					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_total_' . $code}->getTotal($total_data);
				}

			}
		}

		$sort_order = array();

		foreach ($totals as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $totals);
		
		if(!empty($vat_incl)) {	

			$cart_total = end($totals);
			return $cart_total['value'];

		} else {
			
			array_pop($totals);
			$cart_total = 0;
			
			foreach ($totals as $total) {
				if ($total['code'] != 'tax') {
					$cart_total += $total['value'];
				}
			}
			
			return $cart_total;
			
		}	
	}
	
	public function addToCart ($route,$args,&$output) {
		$this->insertPixel('AddToCart');
	}
	
	public function addToWishlist ($route,$args,&$output) {
		$this->insertPixel('AddToWishlist');
	}
	
	public function CompleteRegistration ($route,$args,$output) {
		if($route == 'checkout/register/save') {
			$this->session->data['saveRegistration'] = true;
			$this->insertPixel('CompleteRegistration');
		} else {
			if(!empty($this->session->data['saveRegistration']) && !empty($this->session->data['completeRegistrationPixel'])) {
				$output = $this->response->getOutput();
				$output .= $this->session->data['completeRegistrationPixel'];
				
				unset($this->session->data['completeRegistrationPixel']);
				unset($this->session->data['saveRegistration']);
				
				$this->response->setOutput($output);
			}
		}
	}
	
	public function CompleteRegistration2 ($route,$args,$output) {
		//$this->insertPixel('CompleteRegistration2');
		
		$output = $this->index();
		$this->response->setOutput($output);
		
	}
	
	public function AddPaymentInfo ($route,$args,&$output) {
		$this->insertPixel('AddPaymentInfo');
	}
	
	protected function insertPixel($event) {
		$prefix = $this->prefix();
		$settings = $this->getSettingData();
		$pid = $this->config->get($prefix . 'fb_marketing_pid');
		
		
		
		if (!empty($settings[$prefix . 'fb_marketing_events'][strtolower($event)]['status'])) {
		
			switch ($event) {
				case 'AddToWishlist':
				case 'AddToCart':
					if (isset($this->request->post['product_id'])) {
						$product_id = (int)$this->request->post['product_id'];
					} else {
						$product_id = 0;
					}
					
					// Hijack response output	
					$response = $this->response->getOutput();
					$string = json_decode($response, true);
		
					if(!empty($product_id ) && !empty($string['success'])) {
						
						$content = array();	
						
						if($event == 'AddToWishlist') {
							$this->load->model('catalog/product');
							$product_info = $this->model_catalog_product->getProduct($product_id);
						} else {
						 	$cart_products = $this->cart->getProducts();
							foreach($cart_products as $product) {
								if($product['product_id'] == $product_id) {
									$product_info = $product;
								}
							}
						}
						
						if(!empty($product_info)) {
									
							$price = $product_info['price'] ;	
							$pixel = '<script><!--';
							
							if (!empty($settings[$prefix . 'fb_marketing_values_vat_inc'])) {
								$value = $this->tax->calculate($price,$product_info['tax_class_id']);
							} else {
								$value = $price;
							}
							
							if(!empty($product_info[$pid])) {
								$content_id = $product_info[$pid];
							} else {
								$content_id = $product_info['product_id'];
							}
							
							$content['id'] = $content_id;
							
							if($event == 'AddToWishlist') { 
								$content['quantity'] = 1 ;
							} else {
								$content['quantity'] = $product['quantity'] ;
							}
							
							$content['item_price'] = $value;
							
							$pixel .= 'fbq([\'track\', \'' . $event . '\', { value : \'' . $this->formatValue($value) . '\', currency: \'' . $this->config->get('config_currency'). '\', content_ids: [\'' . $content_id . '\'], content_type: \'product\', content_name: \'' . $this->db->escape($product_info['name']) . '\', num_items: \'' . $product_info['quantity'] . '\'';
							
							if(!empty($content)) {
								$pixel .= ', contents: [' . json_encode($content) . ']';
							}
							
							if(!empty($settings[$prefix . 'fb_marketing_product_catalog_id'])) {
								$pixel .= ' , product_catalog_id: \'' . $settings[$prefix . 'fb_marketing_product_catalog_id'] . '\'';
							}
				
							$pixel .= '}]);';
							$pixel .= '//--></script>';
				
							$string['success'] .= $pixel;
							
							/* Check for Journal3 theme*/
							if ($this->config->get('config_theme') == 'journal3' && !empty($string['notification']['message'])) {
								$string['notification']['message'] .= $pixel;
							}
							
							$this->response->addHeader('Content-Type: application/json');
							$this->response->setOutput(json_encode($string));
				
						}
					}	
					break;
				case 'CompleteRegistration' :			
					if(!empty($this->session->data['saveRegistration'])) {
						$output = json_decode($this->response->getOutput(), true);
						if(empty($output)) {
							$this->request->post['dynamic_event'] = 'CompleteRegistration';
							$this->session->data['completeRegistrationPixel'] = $this->index();			
						}
					}
					break;
				
				case 'AddPaymentInfo' :
					
						$output = $this->response->getOutput();
						$value = $settings[$prefix . 'fb_marketing_events'][strtolower($event)]['value'];
						$pixel = '<script><!--';
						$pixel .= 'fbq([\'track\', \'' . $event . '\', { value : \'' . $this->formatValue($value) . '\', currency: \'' . $this->config->get('config_currency'). '\'';
						
						// Contents 
						$cart = $this->cart->getProducts();
						foreach($cart as $cart_product) {
							$this->load->model('catalog/product');
							$product = $this->model_catalog_product->getProduct($cart_product['product_id']);
					
							$content = array();
					
							if(!empty($product[$pid])) {
								$products[] = '\'' . $product[$pid] . '\'';
								$content['id'] = $product[$pid];
							} else {
								$products[] = '\'' . $product['product_id'] . '\'';
								$content['id'] = $product['product_id'];
							}
					
							$content['quantity'] = $cart_product['quantity'];
						
							if(!empty($vat_incl)) {
								$content['item_price'] = $this->formatValue($this->tax->calculate($cart_product['price'],$cart_product['tax_class_id']));
							} else {
								$content['item_price'] = $this->formatValue($cart_product['price']);
							}
						
							$contents[] = $content;				
						}
				
						$cart_products = implode(',', $products);
					
						$pixel .= ', content_ids: [' . $cart_products  . ']';
						$pixel .= ', content_type: \'product\'';
					
						if(!empty($contents)) {
							$contents_json = array();
							foreach ($contents as $v) {
								$contents_json[] = json_encode($v) ;
							}
							$pixel .= ', contents: [' . implode(',' , $contents_json) . ']';
						}
					
						if(!empty($settings[$prefix . 'fb_marketing_product_catalog_id'])) {
							$pixel .= ' , product_catalog_id: \'' . $settings[$prefix . 'fb_marketing_product_catalog_id'] . '\'';
						}
					
						$pixel .= '}])';
						$pixel .= '//--></script>';
						
						$output .= $pixel;
					
						$this->session->data['AddPaymentInfoPixel'] = $pixel; 
						$this->response->setOutput($output);
					break;	
			}
		}
	}
	
	public function addOrder ($route,$args) {
		
		//if (!empty($this->session->data['last_order_id'])) {
		//	unset($this->session->data['last_order_id']);	
		//}
		
		if (!empty($this->session->data['order_id'])) {
			$this->session->data['last_order_id'] = $this->session->data['order_id'];
		}
		
		//$this->log->write('AddOrder triggered - this->session->data[\'last_order_id\'] = ' . (!empty($this->session->data['last_order_id']) ? $this->session->data['last_order_id'] : 'empty/unset') );
	}
	
	private function path () {
		if (version_compare(VERSION, '2.3.0.0', '>=')) {
			return 'extension/analytics/fb_marketing';
		} else {
			return 'analytics/fb_marketing';
		}
	}
	
	private function folder () {
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
			return 'setting';
		} else {
			return 'extension';
		}	
	}
	
	private function prefix () {
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
			return 'analytics_';
		} else {
			return '';
		}	
	}
}