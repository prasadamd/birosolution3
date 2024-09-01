<?php
class ControllerExtensionModuleExternalLink extends Controller {
	public function index() {
		$this->load->language('extension/module/externallink');

		$data['heading_title'] = $this->language->get('heading_title');

		
		$external_links= $this->config->get('module_externallink_url');

		
		$data['externallinks'] = array();


		if(!empty($external_links)) {

		foreach($external_links as $external_link) {
			
			
			foreach($external_link['link_title'] as $key=>$value) {
				
				
				if($key == (int)$this->config->get('config_language_id')) {
			
					$data['externallinks'][] = array(
					
						'link_title'	=> $external_link['link_title'][(int)$this->config->get('config_language_id')]['title'],
						'link_url'	=> $external_link['link_url'],
						'sort_order'	=> $external_link['sort_order'],
					
					);
				}
			
			}
			
		}

	 }

			return $this->load->view('extension/module/externallink', $data);
	}
}