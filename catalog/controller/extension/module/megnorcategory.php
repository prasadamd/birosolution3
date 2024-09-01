<?php
class ControllerExtensionModuleMegnorCategory extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/megnorcategory');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}
		
	
		$data['all_blogs'] = $this->url->link('information/blogger/blogs');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');
		
		$this->load->model('tool/image');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			
			if($category['image']){
	   				 $image = $this->model_tool_image->resize($category['image'], 100, 100);
						} else {
	    			$image = false;
				}
			
			
			
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					/* 2 Level Sub Categories START */
					$childs_data = array();
					$child_2 = $this->model_catalog_category->getCategories($child['category_id']);

					foreach ($child_2 as $childs) {
						$filter_data = array(
							'filter_category_id'  => $childs['category_id'],
							'filter_sub_category' => true
						);

						$childs_data[] = array(
							'name'  => $childs['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
							'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $childs['category_id'])
						);
					}
					/* 2 Level Sub Categories END */

					$children_data[] = array(
						'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'childs' => $childs_data,
						'column'   => $child['column'] ? $child['column'] : 1,
						'image'  => $child['image'] ? $this->model_tool_image->resize($child['image'], 225, 155) : false,
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}

		return $this->load->view('extension/module/megnorcategory', $data);
	}
}