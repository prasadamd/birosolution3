<?php
class ControllerExtensionModuleOopCategoryPlus extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/oop_category_plus');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_oop_category_plus', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/oop_category_plus', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/oop_category_plus', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['oop_display_menu'] = $this->language->get('oop_display_menu');
		$data['oop_collapsed'] = $this->language->get('oop_collapsed');	
		$data['oop_partially_opened'] = $this->language->get('oop_partially_opened');			
		$data['oop_opened'] = $this->language->get('oop_opened');		
		$data['oop_open_levels_count'] = $this->language->get('oop_open_levels_count');		
		
		if (isset($this->request->post['module_oop_category_plus_display'])) {
			$data['module_oop_category_plus_display'] = $this->request->post['module_oop_category_plus_display'];
		} else {
			$data['module_oop_category_plus_display'] = $this->config->get('module_oop_category_plus_display');
		}		
		
		if (isset($this->request->post['module_oop_category_plus_partially_opened'])) {
			$data['module_oop_category_plus_partially_opened'] = $this->request->post['module_oop_category_plus_partially_opened'];
		} else {
			$data['module_oop_category_plus_partially_opened'] = $this->config->get('module_oop_category_plus_partially_opened');
		}		
		
		if (isset($this->request->post['module_oop_category_plus_status'])) {
			$data['module_oop_category_plus_status'] = $this->request->post['module_oop_category_plus_status'];
		} else {
			$data['module_oop_category_plus_status'] = $this->config->get('module_oop_category_plus_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/oop_category_plus', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/oop_category_plus')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
