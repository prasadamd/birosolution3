<?php
class ControllerExtensionModuleExternalLink extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/externallink');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_externallink', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_link_title'] = $this->language->get('entry_link_title');
		$data['entry_link_url'] = $this->language->get('entry_link_url');
		$data['entry_status'] = $this->language->get('entry_status');


		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_externallink_add'] = $this->language->get('button_externallink_add');
		$data['button_remove'] = $this->language->get('button_remove');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['link_title'])) {
			$data['error_link_title'] = $this->error['link_title'];
		} else {
			$data['error_link_title'] = array();
		}
		
		if (isset($this->error['link_url'])) {
			$data['error_link_url'] = $this->error['link_url'];
		} else {
			$data['error_link_url'] = array();
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
			'href' => $this->url->link('extension/module/externallink', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/externallink', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

		$data['user_token'] = $this->session->data['user_token'];


		
		if (isset($this->request->post['externallink'])) {
			$data['module_externallink_status'] = $this->request->post['module_externallink_status'];
		} else {
			$data['module_externallink_status'] = $this->config->get('module_externallink_status');
		}
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		


		if (isset($this->request->post['module_externallink_url'])) {
			$module_externallink_urls = $this->request->post['module_externallink_url'];
		} elseif ($this->config->get('module_externallink_url')) {
			$module_externallink_urls = $this->config->get('module_externallink_url');
		} else {
			$module_externallink_urls = array();
		}

		$data['module_externallink_urls'] = array();

		foreach ($module_externallink_urls as $module_externallink_url) {

			$data['module_externallink_urls'][] = array(
				'link_title' 				=> $module_externallink_url['link_title'],
				'link_url'                     	=> $module_externallink_url['link_url'],
				'sort_order'               	=> $module_externallink_url['sort_order']
			);
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/externallink', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/externallink')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}




		return !$this->error;
	}
}
