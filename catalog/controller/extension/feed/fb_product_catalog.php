<?php
class ControllerExtensionFeedFbProductCatalog extends Controller {
	public function index() {
		$path = $this->path();
		$folder = $this->folder();
		$prefix = $this->prefix();
			
		if ($this->config->get($prefix . 'fb_marketing_product_feed')) {
			$output  = '<?xml version="1.0" encoding="UTF-8" ?>';
			$output .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
			$output .= '  <channel>';
			$output .= '  <title>' . $this->config->get('config_name') . '</title>';
			$output .= '  <description>' . $this->config->get('config_meta_description') . '</description>';
			$output .= '  <link>' . $this->config->get('config_url') . '</link>';
			
			$this->load->model($path . '/fb_product_catalog');
			//$this->load->model('catalog/category');
			$this->load->model('catalog/product');
			$this->load->model('localisation/language');
			$this->load->model('tool/image');

			$product_data = array();
			
			if(!empty($this->request->get['lang'])) {
				$language_code = $this->request->get['lang'];
			} else {
				$language_code = $this->config->get('config_language');
			}
			
			$feed_model_path = 'model_' . str_replace('/','_', $path). '_fb_product_catalog';
			$language_id = $this->$feed_model_path->getLanguageId($language_code);
			$this->config->set('config_language_id', $language_id);
			
			/*
			$fb_product_catalog_categories = $this->model_extension_feed_fb_product_catalog->getCategories();

			foreach ($fb_product_catalog_categories as $fb_product_catalog_category) {
				$filter_data = array(
					'filter_category_id' => $fb_product_catalog_category['category_id'],
					'filter_filter'      => false
				);
			*/
				$products = $this->model_catalog_product->getProducts();

				foreach ($products as $product) {
					if (!in_array($product['product_id'], $product_data) && $product['description']) {
						$output .= '<item>';
						$output .= '<title><![CDATA[' . $product['name'] . ']]></title>';
						$output .= '<link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</link>';
						$output .= '<description><![CDATA[' . strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')) . ']]></description>';
						$output .= '<g:brand><![CDATA[' . html_entity_decode($product['manufacturer'], ENT_QUOTES, 'UTF-8') . ']]></g:brand>';
						$output .= '<g:condition>new</g:condition>';
						$output .= '<g:id>' . $product['product_id'] . '</g:id>';

						if ($product['image']) {
							$output .= '  <g:image_link>' . $this->model_tool_image->resize($product['image'], 500, 500) . '</g:image_link>';
						} else {
							$output .= '  <g:image_link></g:image_link>';
						}

						$output .= '  <g:model_number>' . $product['model'] . '</g:model_number>';

						if ($product['mpn']) {
							$output .= '  <g:mpn><![CDATA[' . $product['mpn'] . ']]></g:mpn>' ;
						} else {
							$output .= '  <g:identifier_exists>false</g:identifier_exists>';
						}

						if ($product['upc']) {
							$output .= '  <g:upc>' . $product['upc'] . '</g:upc>';
						}

						if ($product['ean']) {
							$output .= '  <g:ean>' . $product['ean'] . '</g:ean>';
						}

						$currencies = array(
							'USD',
							'EUR',
							'GBP'
						);

						if (in_array($this->session->data['currency'], $currencies)) {
							$currency_code = $this->session->data['currency'];
							$currency_value = $this->currency->getValue($this->session->data['currency']);
						} else {
							$currency_code = 'USD';
							$currency_value = $this->currency->getValue('USD');
						}
						
						$output .= '  <g:price>' . $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id']), $currency_code, $currency_value, false) . '</g:price>';

						if ((float)$product['special']) {
							$output .= '  <g:sale_price>' .  $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id']), $currency_code, $currency_value, false) . '</g:sale_price>';
						}

						/*
						$output .= '  <g:google_product_category>' . $fb_product_catalog_category['fb_product_catalog_category_id'] . '</g:google_product_category>';

						$categories = $this->model_catalog_product->getCategories($product['product_id']);

						foreach ($categories as $category) {
							$path = $this->getPath($category['category_id']);

							if ($path) {
								$string = '';

								foreach (explode('_', $path) as $path_id) {
									$category_info = $this->model_catalog_category->getCategory($path_id);

									if ($category_info) {
										if (!$string) {
											$string = $category_info['name'];
										} else {
											$string .= ' &gt; ' . $category_info['name'];
										}
									}
								}

								$output .= '<g:product_type><![CDATA[' . $string . ']]></g:product_type>';
							}
						}
						*/
						
						$output .= '  <g:quantity>' . $product['quantity'] . '</g:quantity>';
						$output .= '  <g:weight>' . $this->weight->format($product['weight'], $product['weight_class_id']) . '</g:weight>';
						$output .= '  <g:availability><![CDATA[' . ($product['quantity'] ? 'in stock' : 'out of stock') . ']]></g:availability>';
						$output .= '</item>';
					}
				}
			/* } */

			$output .= '  </channel>';
			$output .= '</rss>';

			$this->response->addHeader('Content-Type: application/rss+xml');
			$this->response->setOutput($output);
		}
	}

	protected function getPath($parent_id, $current_path = '') {
		$category_info = $this->model_catalog_category->getCategory($parent_id);

		if ($category_info) {
			if (!$current_path) {
				$new_path = $category_info['category_id'];
			} else {
				$new_path = $category_info['category_id'] . '_' . $current_path;
			}

			$path = $this->getPath($category_info['parent_id'], $new_path);

			if ($path) {
				return $path;
			} else {
				return $new_path;
			}
		}
	}
	
	private function path () {
		if (version_compare(VERSION, '2.3.0.0', '>=')) {
			return 'extension/feed';
		} else {
			return 'feed';
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
