<?php
class ControllerExtensionAnalyticsFbMarketing extends Controller {
	private $version = '2.4.1';
	private $setting_keys_array = array('status', 'version', 'pixel_id', 'product_catalog_id', 'events', 'advanced_matching', 'values_vat_inc', 'manual_only_mode', 'pid','product_feed');
	private $default_events = array(
			'ViewContent',
			'Search',
			'AddToCart',
			'AddToWishlist',
			'InitiateCheckout',
			'AddPaymentInfo',
			'Purchase',
			//'Lead',
			'CompleteRegistration',
			'PageView'
		);
	private $custom_events = array(
			'ViewContent',
			'Search',
			//'AddToCart',
			//'AddToWishlist',
			'InitiateCheckout',
			//'AddPaymentInfo',
			'Purchase',
			'Lead',
			'CompleteRegistration',
			//'PageView'
		);	
	private $adv_match_types = array(
			'em' 	=> 'Email',
			'fn'	=> 'First Name',
			'ln'	=> 'Last Name',
			'ph' 	=> 'Phone',
			'ct'	=> 'City',
			'st' 	=> 'State',
			'zp'	=> 'Zip'
		);
	private $prod_id_options = array(
			'product_id'	=> 'Product ID',
			'model'	=> 'Model',
			'sku'	=> 'SKU',
			'upc'	=> 'UPC',
			'ean' 	=> 'EAN',
			'jan'	=> 'JAN',
			'isbn' 	=> 'ISBN',
			'mpn' 	=> 'MPN',
		);
	
	private $match_options = array(
			'route'		=> 'Route (matches $_GET <i>route</i> only)',
			'strict'	=> 'Strict (exact match required)',
			'ends'		=> 'Ends with (recommended for single SEO keywords)',
			'contains'	=> 'Contains (matches any part of the url)'
		);
	
	private $error = array();
		
	public function index() {		
		
		$path = $this->path();
		$prefix = $this->prefix();
		$folder = $this->folder();
		
		$this->load->language($path);
		$data['permission'] = $this->user->hasPermission('modify', $path);
		$data['controller_path'] = $path;

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript("https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js");
		$this->document->addStyle("https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css");
		
		$this->load->model('setting/setting');
		
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
	 		$data['token'] = $this->session->data['user_token'];
	 	} else {
	 		$data['token'] = $this->session->data['token'];
	 	}
	 	
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->saveSettings();
			$this->session->data['success'] = $this->language->get('text_success');
			
			if (version_compare(VERSION, '3.0.0.0', '>=')) {
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $data['token'] . '&type=analytics', true));
			} else if (version_compare(VERSION, '2.3.0.0', '>=')) {
				$this->response->redirect($this->url->link('extension/extension', 'token=' . $data['token'] . '&type=analytics', true));
			} else {
				$this->response->redirect($this->url->link('extension/analytics', 'token=' . $data['token'] , 'SSL'));
			}
		}
		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_signup'] = $this->language->get('text_signup');
		
		$data['text_pixel_configuration'] = $this->language->get('text_pixel_configuration');
		$data['text_advanced_matching_data'] = $this->language->get('text_advanced_matching_data');
		$data['text_marketing_api'] = $this->language->get('text_marketing_api');
		$data['text_about'] = $this->language->get('text_about');
		
		$data['text_ajax_save'] = $this->language->get('text_ajax_save');
		
		$data['text_name'] = $this->language->get('text_name');
		$data['text_path'] = $this->language->get('text_path');
		$data['text_value'] = $this->language->get('text_value');
		$data['text_status'] = $this->language->get('text_status');
		$data['text_match'] = $this->language->get('text_match');
		
		$data['placeholder_path'] = $this->language->get('placeholder_path');
		$data['placeholder_value'] = $this->language->get('placeholder_value');
		
		$data['help_pixel_id'] = htmlspecialchars($this->language->get('help_pixel_id'));
		$data['help_product_catalog_id'] = htmlspecialchars($this->language->get('help_product_catalog_id'));
		$data['help_advanced_matching'] = htmlspecialchars($this->language->get('help_advanced_matching'));
		$data['help_values_vat_inc'] = htmlspecialchars($this->language->get('help_values_vat_inc'));
		$data['help_manual_only_mode'] = htmlspecialchars($this->language->get('help_manual_only_mode'));
		$data['help_pid'] = htmlspecialchars($this->language->get('help_pid'));
		$data['help_product_feed'] = htmlspecialchars($this->language->get('help_product_feed'));
		$data['help_custom_events'] = $this->language->get('help_custom_events');
		
		$data['entry_custom_events'] = $this->language->get('entry_custom_events');
		
		$data['error_permission'] = $this->language->get('error_permission');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['code'])) {
			$data['error_code'] = $this->error['code'];
		} else {
			$data['error_code'] = '';
		}
		
		$data['breadcrumbs'] = array();
		
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
			$data['action'] = $this->url->link('extension/analytics/fb_marketing', 'user_token=' . $data['token'], true);
			$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $data['token'] . '&type=analytics', true);
		} else if (version_compare(VERSION, '2.3.0.0', '>=')) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $data['token'], true),
			);
		
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('extension/extension', 'token=' . $data['token'] . '&type=analytics', true),
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/analytics/fb_marketing', 'token=' . $data['token'], true)
			);

			$data['action'] = $this->url->link('extension/analytics/fb_marketing', 'token=' . $data['token'], true);
			$data['cancel'] = $this->url->link('extension/extension', 'token=' . $data['token'] . '&type=analytics', true);
			
		} else {
		
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $data['token'], 'SSL'),
			);
		
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('extension/analytics', 'token=' . $data['token'], 'SSL'),
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('analytics/fb_marketing', 'token=' . $data['token'], 'SSL')
			);
		
			$data['action'] = $this->url->link('analytics/fb_marketing', 'token=' . $data['token'], 'SSL');
			$data['cancel'] = $this->url->link('extension/analytics', 'token=' . $data['token'], 'SSL');
		}
		
		$data['version'] = $this->version;
		
		// Load Stores
		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();
		array_unshift($stores, array(
			'store_id'	=> 0,
			'name'		=> $this->config->get('config_name'),
			'url'		=> HTTP_CATALOG,
			'ssl'		=> HTTPS_CATALOG
		));
	
		$data['stores'] = $stores;
		
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
			foreach($this->default_events as $event) {
				$data['events'][strtolower($event)] = $event;
			}
			foreach($this->custom_events as $event) {
				$data['custom_events'][strtolower($event)] = $event;
			}
		} else {
			$data['events'] = $this->default_events;
			$data['custom_events'] = $this->custom_events;
		}
		
		$data['adv_match_types'] = $this->adv_match_types;
		
		$data['pid_options'] =  $this->prod_id_options;
		$data['match_options'] =  $this->match_options;
		
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		if (version_compare(VERSION, '2.3.0.0', '>=')) {
			$feed_path = 'index.php?route=extension/feed/fb_product_catalog';
		} else {
			$feed_path = 'index.php?route=feed/fb_product_catalog';
		}
		
		$data['data_feed'] = $feed_path;
		
		foreach ($this->setting_keys_array as $setting) {
			$data['entry_' . $setting] = $this->language->get('entry_' . $setting);	
			$data[$prefix . 'fb_marketing_' . $setting] = array();
		}
		
		foreach($stores as $store) {
			foreach ($this->setting_keys_array as $setting) {
				if ($setting == 'events') {
					foreach($data['events'] as $event) {
						$key = strtolower($event);
						$_key = $prefix . 'fb_marketing_' . $setting;
						$event_data = $this->getEventData($store['store_id'], $key);
					
						if (isset($this->request->post[$_key][$store['store_id']][$key])) {
							$data[$_key][$store['store_id']][$key] = $this->request->post[$_key][$store['store_id']][$key];
						} elseif ($event_data) { 
							$data[$_key][$store['store_id']][$key] = $event_data;
						} else {
							$data[$_key][$store['store_id']][$key] = array();
						}
					}
				} else {
					$$setting = $this->getPixelData($store['store_id'], $setting);
					$_key = $prefix . 'fb_marketing_' . $setting;
					if (isset($this->request->post[$_key][$store['store_id']])) {
						$data[$_key][$store['store_id']] = $this->request->post[$_key][$store['store_id']];
					} elseif ($$setting) { 
						$data[$_key][$store['store_id']] = $$setting;
					} else {
						$data[$_key][$store['store_id']] = '';
					}
				}	
			}
			
			$custom_events_data = $this->getPixelData($store['store_id'], 'custom_events');
			if (isset($this->request->post[$prefix .'fb_marketing_custom_events'])) {
				$data[$prefix .'fb_marketing_custom_events'][$store['store_id']] = $this->request->post[$prefix .'fb_marketing_custom_events'];
			} elseif ($custom_events_data) { 
				$data[$prefix .'fb_marketing_custom_events'][$store['store_id']] = $custom_events_data;
			} else {
				$data[$prefix . 'fb_marketing_custom_events'][$store['store_id']] = false;
			}
		}
		
		$advanced_matching_data = $this->getPixelData(0, 'advanced_matching_data');
		if (isset($this->request->post[$prefix .'fb_marketing_advanced_matching_data'])) {
			$data[$prefix .'fb_marketing_advanced_matching_data'] = $this->request->post[$prefix .'fb_marketing_advanced_matching_data'];
		} elseif ($advanced_matching_data) { 
			$data[$prefix .'fb_marketing_advanced_matching_data'] = $advanced_matching_data;
		} else {
			$data[$prefix . 'fb_marketing_advanced_matching_data'] = false;
		}
		
		// Business logic for custom event rows
		$data['pixel_event_row'] = array();
		$data['pixel_event_row_object'] = '';
		
		foreach ($data['stores'] as $store) {
			$data['pixel_event_row'][$store['store_id']] = 0;
			if ($data[$prefix . 'fb_marketing_custom_events'][$store['store_id']]) {
				end($data[$prefix . 'fb_marketing_custom_events'][$store['store_id']]);
				$data['pixel_event_row'][$store['store_id']] = key($data[$prefix . 'fb_marketing_custom_events'][$store['store_id']]) + 1;
			}
			$data['pixel_event_row_object'] .= $store['store_id'] . ':' . $data['pixel_event_row'][$store['store_id']] . ',' ;
			
		}
		$data['pixel_event_row_object'] = substr($data['pixel_event_row_object'],0,-1);		
		
		/* Check if update is necessary */ 
		$this->checkUpdate();
		
		/* Check if the required Opencart events are still correct */ 
		$this->checkEvents();
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
	 		$this->response->setOutput($this->load->view($path, $data));
	 	} else {
	 		$this->response->setOutput($this->load->view($path . '.tpl', $data));
	 	}
	}
	
	public function loadSettingsModal() {
		$path = $this->path();
		
		$data['oc_events'] = $this->getOCEvents('fb_marketing');
		$data['permission'] = $this->user->hasPermission('modify', $path);
		$data['controller_path'] = $path;
		
		if (version_compare(VERSION, '3.0.0.0', '>=')) {
	 		$data['user_token']	= $this->session->data['user_token'];
	 		$this->response->setOutput($this->load->view($path . '_core_settings', $data));
	 	} else {
	 		$data['token']	= $this->session->data['token'];
	 		$this->response->setOutput($this->load->view($path . '_core_settings.tpl', $data));
	 	}
	}
	
	protected function getOCEvents($code) {
		
		$events = array();
		
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code). "'");
		
		if ($query->num_rows) {
			$events = $query->rows;
		}
		
		return $events;
	}
	
	public function saveOCEvent() {
		
		$this->load->language($this->path());
		$json = array();
	
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->post['trigger'])  && !empty($this->request->post['action'])) {
			$folder = $this->folder();
			// Register events
			$this->load->model($folder . '/event');
			$event_model_path = 'model_' . $folder . '_event';
		
			//Purchase event
			$this->$event_model_path->addEvent('fb_marketing', $this->db->escape($this->request->post['trigger']), $this->db->escape($this->request->post['action']));
			$json['success'] = 'New OC Event saved succesfully!';
			
		} else {
			$json['warning'] = $this->language->get('error_warning');
		}
		
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function saveSettings() {
		
		$this->load->language($this->path());
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->load->model('setting/setting');
			
			$prefix = $this->prefix();
			
			parse_str(htmlspecialchars_decode($this->request->post['settings']), $settings);
			
			$save_data = array();
			
			foreach ($this->setting_keys_array as $setting) {
				if (!empty($settings[$prefix . 'fb_marketing_' . $setting])) {
					foreach ($settings[$prefix . 'fb_marketing_' . $setting] as $key => $value) {
				
						$save_data[$key][$prefix . 'fb_marketing_' . $setting] = $value;

					}
				}
			}
			
			if (!empty($settings[$prefix . 'fb_marketing_advanced_matching_data'] )) {
				foreach ($settings[$prefix . 'fb_marketing_advanced_matching_data'] as $key => $value) {
					$save_data[0][$prefix . 'fb_marketing_advanced_matching_data'][$key] = $value;
				}
			}
			
			if (!empty($settings[$prefix . 'fb_marketing_custom_events'] )) {
				foreach ($settings[$prefix . 'fb_marketing_custom_events'] as $key => $value) {
					$save_data[$key][$prefix . 'fb_marketing_custom_events'] = array();
					foreach($value as $row => $row_data) {
						$save_data[$key][$prefix . 'fb_marketing_custom_events'][] = $row_data;
					}
				}
			}
			
			$save_data[0][$prefix . 'fb_marketing_version'] = $this->version;
			foreach ($save_data as $store_id => $data) {
				$this->model_setting_setting->editSetting($prefix . 'fb_marketing', $data, $store_id);
			
			}
			$json['post'] = $save_data;
			
			$json['success'] = $this->language->get('text_success');
			
		} else {
			$json['warning'] = $this->language->get('error_warning');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}
	
	protected function getPixelData ($store_id, $action) {
		
		$pixel_data = $this->getSettingData($store_id);
		$prefix = $this->prefix();
		
		if (!empty($pixel_data)) {
			return !empty($pixel_data[$prefix . 'fb_marketing_' . $action]) ?  $pixel_data[$prefix . 'fb_marketing_' . $action] : false ;
			
		}
	}
	
	protected function getEventData ($store_id, $event) {
		$event_data = array();
		$prefix = $this->prefix();
		
		$data = $this->getSettingData($store_id);
		
		if (!empty($data[$prefix . 'fb_marketing_events'])) {
			$events_data = $data[$prefix . 'fb_marketing_events'];
		}
		
		if (empty($events_data)) {
			$events_data = array();
			foreach($this->default_events as $default_event) {
				$events_data[strtolower($default_event)]['status'] = 1;
			}		
		}
		
		switch ($event) {
			case 'viewcontent': 
				$event_data = array(
					'status'=> $events_data['viewcontent']['status'],
					'path' 	=> array('All Product Pages (product/product)', 0),
					'value'	=> array('Product Price', 0)
				);
				break;
			case 'search':
				$value = (!empty($events_data['search']['value']) ? $events_data['search']['value'] : '' ); 
				$event_data = array(
					'status'=> $events_data['search']['status'],
					'path' 	=> array('Search Page (product/search)', 0),
					'value'	=> array($value, 1)
				);
				break;
			case 'addtocart':
				$event_data = array(
					'status'=> $events_data['addtocart']['status'],
					'path' 	=> array('Dynamic Event on AddToCart click',0),
					'value'	=> array('Product Price', 0)
				);
				break;
			case 'addtowishlist':
				$event_data = array(
					'status'=> $events_data['addtowishlist']['status'],
					'path' 	=> array('Dynamic Event on AddToWishlist click',0),
					'value'	=> array('Product Price',0)
				);
				break;	
			case 'initiatecheckout':
				$event_data = array(
					'status'=> $events_data['initiatecheckout']['status'],
					'path' 	=> array('Checkout page (checkout/checkout)', 0),
					'value'	=> array('Cart Total', 0)
				);
				break;
			case 'addpaymentinfo':
				$value = !empty($events_data['addpaymentinfo']['value']) ? $events_data['addpaymentinfo']['value'] : '' ; 
				$event_data = array(
					'status'=> $events_data['addpaymentinfo']['status'],
					'path' 	=> array('Dynamic Event on Add Payment Info', 0),
					'value'	=> array($value, 1)
				);
				break;
			case 'purchase':
				$event_data = array(
					'status'=> $events_data['purchase']['status'],
					'path' 	=> array('Checkout Success Page (checkout/success)', 0),
					'value'	=> array('Order Total', 0)
				);
				break;
			case 'lead':
				$path = !empty($events_data['lead']['path']) ? $events_data['lead']['path'] : '' ; 
				$value = !empty($events_data['lead']['value']) ? $events_data['lead']['value'] : '' ; 
				$event_data = array(
					'status'=> $events_data['lead']['status'],
					'path' 	=> array($path, 1),
					'value'	=> array($value, 1)
				);
				break;
			case 'completeregistration':
				$value = !empty($events_data['completeregistration']['value']) ? $events_data['completeregistration']['value'] : '' ; 
				$event_data = array(
					'status'=> $events_data['completeregistration']['status'],
					'path' 	=> array('Registration Success Page (account & affiliate/success)', 0),
					'value'	=> array($value , 1)
				);
				break;
			case 'pageview':
				$value = !empty($events_data['pageview']['value']) ? $events_data['pageview']['value'] : '' ;
				$event_data = array(
					'status'=> $events_data['pageview']['status'],
					'path' 	=> array('Default pixel event', 0),
					'value'	=> array($value, 1)
				);
				break;		
		}
		return $event_data;
	}
	
	protected function getSettingData ($store_id) {
		$this->load->model('setting/setting');
		return $this->model_setting_setting->getSetting($this->prefix() . 'fb_marketing',$store_id);
	}
	
	public function install() {
		
		$path = $this->path();
		$prefix = $this->prefix();
		$folder = $this->folder();
		
		// Load Stores
		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();
		array_unshift($stores, array(
			'store_id'	=> 0,
			'name'		=> $this->config->get('config_name')
		));
		
		$data['stores'] = $stores;
		
		//Default Settings
		$events_data = array(
			"viewcontent" => array(
				"status" 	=> "1",
				"path" 		=> "All Product Pages (product/product)",
				"value" 	=> "Product Price"
				),
			"search"	 => array(
				"status" 	=> "1",
				"path"		=> "Search Page (product/search)",
				"value"		=> ""
				),
			"addtocart"  => array(
				"status"	=> "1",
				"path" 		=> "Dynamic Event on AddToCart click",
				"value"		=> "Product Price"
				),
			"addtowishlist" => array(
				"status"	=> "1",
				"path"		=> "Dynamic Event on AddToWishlist click",
				"value"		=> "Product Price"
				),
			"initiatecheckout" => array( 
				"status"	=> "1",
				"path"		=> "Checkout page (checkout/checkout)",
				"value"		=> "Cart Total"
				),
			"addpaymentinfo" => array(
				"status"	=> "1",
				"path"		=> "Dynamic Event on Add Payment Info",
				"value"		=> ""
				),
			"purchase"		=> array(
				"status"	=> "1",
				"path"		=> "Checkout Success Page (checkout/success)",
				"value" 	=> "Order Total"
				),
			"lead"		=> array(
				"status"	=> "1",
				"path"		=> "",
				"value"		=> ""
				),
			"completeregistration"	=> array(
				"status"	=> "1",
				"path"		=> "Registration Success Page (account & affiliate/success)",
				"value"		=>""
				),
			"pageview"		=> array(
				"status"	=> "1",
				"path"		=> "Default pixel event",
				"value"		=> ""
				)
		);
		
		$data = array(
			$prefix . 'fb_marketing_status' 				=> 1 ,
			$prefix . 'fb_marketing_version' 				=> $this->version,
			$prefix . 'fb_marketing_pixel_id' 				=> '',
			$prefix . 'fb_marketing_product_catalog_id' 	=> '',
			$prefix . 'fb_marketing_events'					=> $events_data,
			$prefix . 'fb_marketing_advanced_matching'		=> '1',
			$prefix . 'fb_marketing_values_vat_inc'			=> '0',
			$prefix . 'fb_marketing_pid'					=> 'product_id',
			$prefix . 'fb_marketing_product_feed'			=> '1',
		);
		
		$this->load->model('setting/setting');	
		
		foreach ($stores as $store) {
			$this->model_setting_setting->editSetting($prefix . 'fb_marketing', $data, $store['store_id']);
		}
		
		// Advanced Matching Data Types
		
		$data_types = array(
			'em' 	=> 'on',
			'fn'	=> 'on',
			'ln'	=> 'on',
			'ph'	=> 'on'
		);
		
		$this->db->query("INSERT INTO `". DB_PREFIX . "setting` (`store_id`, `code`, `key`,`value`,`serialized`) VALUES ('0','" . $prefix . "fb_marketing', '" . $prefix . "fb_marketing_advanced_matching_data', '" . json_encode($data_types) . "', '1');");	
		
	 	// Register events
	 	if (version_compare(VERSION, '2.2.0.0', '>=')) { 
	 		$this->load->model($folder . '/event');
			$event_model_path = 'model_' . $folder . '_event';
		
			//Purchase event
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/model/checkout/order/addOrder/after', $path . '/addOrder');
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/model/d_quickcheckout/order/addOrder/after', $path . '/addOrder');
		
			//AddToCart event
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/controller/checkout/cart/add/after',  $path . '/addToCart');
		
			//AddToWishlist event
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/controller/account/wishlist/add/after',  $path . '/addToWishlist');
		
			//CompleteRegistration event, dynamic in checkout
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/controller/checkout/register/save/after',  $path . '/CompleteRegistration');
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/controller/checkout/shipping_address/after',  $path . '/CompleteRegistration');
		
			//AddPaymentInfo event, dynamic in checkout
			$this->$event_model_path->addEvent('fb_marketing', 'catalog/controller/checkout/payment_address/after',  $path . '/AddPaymentInfo');
	 	} else {
			//Purchase event
			$this->addEvent('post.order.add', 'addOrder');
		}
		return true;
	}
	
	public function uninstall() {
		$folder = $this->folder();
		$prefix = $this->prefix();
		
		// Delete settings
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $prefix . "fb_marketing'");
		
		// Unregister events
		$this->load->model($folder . '/event');
	 	$event_model_path = 'model_' . $folder . '_event';
	 	
	 	if (version_compare(VERSION, '3.0.0.0', '>=')) {
	 		$this->$event_model_path->deleteEventByCode('fb_marketing');
	 	} else {
	 		$this->$event_model_path->deleteEvent('fb_marketing');
	 	}
	}
	
	protected function checkUpdate() {
		
		$path = $this->path();
		$folder = $this->folder();
		$prefix = $this->prefix();
		
		$this->load->model($folder . '/event');
		$event_model_path = 'model_' . $folder . '_event';
		
		// Load Stores
		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();
		array_unshift($stores, array(
			'store_id'	=> 0,
			'name'		=> $this->config->get('config_name')
		));
	
		$version_key = $prefix . 'fb_marketing_version';
		$pid_key = $prefix . 'fb_marketing_pid';
		
		$version = $this->config->get($version_key);
		
		if (empty($version)) {
			$this->db->query("INSERT INTO `". DB_PREFIX . "setting` (`store_id`, `code`, `key`,`value`,`serialized`) VALUES ('0','" . $prefix . "fb_marketing', '" . $prefix . "fb_marketing_version', '" . $this->version . "', '0');");
		} else if(version_compare($this->config->get($version_key), $this->version, '!=')) {
				$this->db->query("UPDATE `". DB_PREFIX . "setting` SET `value` = '" . $this->version . "' WHERE `store_id` = '0' AND `key` = '" . $prefix . "fb_marketing_version'");
		}
		
		foreach($stores as $store) {
			$pid = $this->config->get($pid_key);
			if (empty($pid)) {
				$this->db->query("INSERT INTO `". DB_PREFIX . "setting` (`store_id`, `code`, `key`,`value`,`serialized`) VALUES ('" . (int)$store['store_id']. "','" . $prefix . "fb_marketing', '" . $prefix . "fb_marketing_pid', 'product_id', '0');");
			}
		}

		if (version_compare(VERSION, '2.2.0.0', '>=')) {
			$this->deleteEvent('catalog/model/checkout/order/addOrder/after');
			$this->deleteEvent('catalog/model/d_quickcheckout/order/addOrder/after');
		
			$this->addEvent('catalog/controller/checkout/success/before', 'addOrder');
			
			/* AJAX Quick Checkout fix */
			if (is_file(DIR_APPLICATION . 'controller/module/d_quickcheckout.php')) {
				$trigger = 'catalog/controller/d_quickcheckout/confirm/updateOrder/after';
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" . $trigger . "'");
				if(empty($query->num_rows)) {
					$this->addEvent('catalog/controller/extension/d_quickcheckout/confirm/updateOrder/after', 'addOrder');
				}
			}
			
			if (is_file(DIR_APPLICATION . 'controller/extension/module/d_quickcheckout.php')) {
				$trigger = 'catalog/controller/extension/d_quickcheckout/confirm/updateOrder/after';
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" . $trigger . "'");
				if(empty($query->num_rows)) {
					$this->addEvent('catalog/controller/extension/d_quickcheckout/confirm/updateOrder/after', 'addOrder');
				}
			}
			
		} else {
			$this->addEvent('post.order.add', 'addOrder');
		}
	}
	
	protected function checkEvents() {
		$path = $this->path;
		$events = array();
		
		if (version_compare(VERSION, '2.2.0.0', '>=')) {
			//Purchase event
			$events['purchase'] = array(
				'trigger' 	=> 'catalog/controller/checkout/success/before',
				'action' 	=> 'addOrder'
			);
			//AddToCart event
			$events['addtocart'] = array(
				'trigger' 	=> 'catalog/controller/checkout/cart/add/after',
				'action' 	=>  'addToCart'
			);
			//AddToWishlist event
			$events['addtocart'] = array(
				'trigger' 	=> 'catalog/controller/account/wishlist/add/after',
				'action' 	=>  'addToWishlist'
			);
			//CompleteRegistration event, dynamic in checkout
			$events['completeregistration1'] = array(
				'trigger' 	=> 'catalog/controller/checkout/register/save/after',
				'action' 	=>  'CompleteRegistration'
			);
			$events['completeregistration2'] = array(
				'trigger' 	=> 'catalog/controller/checkout/shipping_address/after',
				'action' 	=>  'CompleteRegistration'
			);
			//AddPaymentInfo event, dynamic in checkout
			$events['addpaymentinfo'] = array(
				'trigger' 	=> 'catalog/controller/checkout/payment_address/after',
				'action' 	=>  'AddPaymentInfo'
			);
		} else {
			//Purchase event
			$events['purchase'] = array(
				'trigger' 	=> 'post.order.add',
				'action' 	=> 'addOrder'
			);
		}
		
		foreach ($events as $key => $event) {
			if (!$this->checkEvent($event['trigger'],$event['action'])){
				$this->addEvent($event['trigger'],$event['action']);
			}
		}
	}
	
	protected function checkEvent($trigger, $action){	
		$path = $this->path();
		$search =  $path . '/' . $action;
		
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" .  $this->db->escape($trigger) . "' AND `action` = '" . $this->db->escape($search) . "'");
		return !empty($query->num_rows) ? true : false ;
		
	}
	
	public function addEvent($trigger, $action){		
		$path = $this->path();
		$search =  $path . '/' . $action;
		
		$folder = $this->folder();
		
		$this->load->model($folder . '/event');
		$event_model_path = 'model_' . $folder . '_event';
		
		$sql = "SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" . $this->db->escape($trigger) . "' AND `action` = '" . $this->db->escape($search) . "'";
		
		$query = $this->db->query($sql);
		if (empty($query->num_rows)) {
			$this->$event_model_path->addEvent('fb_marketing', $trigger , $path . '/' . $action );
		}
		
	}
	
	public function deleteEvent($trigger){		
		$sql = "SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" . $trigger . "'";
		
		$query = $this->db->query($sql);
		if (!empty($query->num_rows)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `trigger` = '" . $this->db->escape($trigger) . "'");
		}
	}
	
	protected function validate() {
		
		if (!$this->user->hasPermission('modify', $this->path())) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	public function toggle() {
		
		$this->load->language($this->path());
		$prefix = $this->prefix();
		
		$json = array();
		$key = $prefix . 'fb_marketing_events';
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			
			$explode = explode('_',$this->request->post['row']);
			$event = reset($explode);
			$store_id = end($explode);
			
			if (!in_array($event,array_map("strtolower", $this->default_events))) {
				$key = $prefix . 'fb_marketing_custom_events';
			}
			
			$old_settings = $this->getSettingValue($key, $store_id);
		
			$json['old'] = $old_settings[$event];
		
			$new_settings = $old_settings;
		
			$new_settings[$event]['status'] = !$old_settings[$event]['status'];
			$json['new'] = $new_settings[$event];
			
			if (version_compare(VERSION, '2.1.0.0', '>=')) {
				$value = json_encode($new_settings);
			} else {
				$value = serialize($new_settings);
			}
			
			$this->db->query("UPDATE `". DB_PREFIX . "setting` SET `value` = '" . $value . "' WHERE `key` = '" . $key . "' AND `store_id` = '" . (int)$store_id . "'");
			$json['success'] = $new_settings[$event]['status'] ? $this->language->get('text_activated') : $this->language->get('text_deactivated') ;
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function toggleAdvMatchTypes() {
		
		$this->load->language($this->path());
		$prefix = $this->prefix();
		
		$json = array();
		$insert = false;
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['id'])) {
			
			$explode = explode('_',$this->request->post['id']);
			$type = end($explode);			
			
			$old_settings = $this->getSettingValue($prefix . 'fb_marketing_advanced_matching_data', 0);
			
			if(is_null($old_settings)) {
				$old_settings = array();
				$insert = true;
			}
			
			$new_settings = $old_settings;
			if (key_exists($type,$old_settings)) {
				unset($new_settings[$type]);
				$json['success'] = $this->language->get('text_adv_match_deactivated');
			} else {
				$new_settings[$type] = "on";
				$json['success'] = $this->language->get('text_adv_match_activated');
			}
			
			$json['old'] = $old_settings;
			$json['new'] = $new_settings;
			
			if (version_compare(VERSION, '2.1.0.0', '>=')) {
				$value = json_encode($new_settings);
			} else {
				$value = serialize($new_settings);
			}
			
			$sql = !$insert ? "UPDATE `". DB_PREFIX . "setting` SET `value` = '" . $value . "' WHERE `key` = '" . $prefix . "fb_marketing_advanced_matching_data' AND `store_id` = '0'" : "INSERT INTO `". DB_PREFIX . "setting` VALUES (NULL,0,'" . $prefix . "fb_marketing', '" . $prefix . "fb_marketing_advanced_matching_data', '" . $value . "',1)";
			$this->db->query($sql);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	
	public function deactivate() {
		$this->load->language($this->path());
		
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
			//Implement logic to save the event status
			//$this->db->query("UPDATE `". DB_PREFIX . "category_discount` SET `status` = '0' WHERE `category_discount_id` = '" . (int)$this->request->post['row'] . "' ");
		}
		
		$json['success'] = $this->language->get('text_deactivated');
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function getSettingValue($key,$store_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

		if ($query->num_rows) {
			$result = $query->row;
			if (!$result['serialized']) {
				return $result['value'];
			} else {
				if (version_compare(VERSION, '2.1.0.0', '>=')) {
					return json_decode($result['value'], true);
				} else {
					return unserialize($result['value']);
				}
				
			}
		} else {
			return null;	
		}
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