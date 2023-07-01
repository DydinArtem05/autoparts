<?php
class ControllerExtensionFeedFuriousSitemap extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('extension/feed/furious_sitemap');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {	
			$this->model_setting_setting->editSetting('feed_furious_sitemap', $this->request->post);			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));		
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/furious_sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['save'] = $this->url->link('extension/feed/furious_sitemap', 'user_token=' . $this->session->data['user_token'], true);
		$data['action'] = $this->url->link('extension/feed/furious_sitemap', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);

		$data['feed_furious_sitemap_sitemap_url'] = HTTP_CATALOG . 'sitemap.xml';
		
		$this->config->load('fia/fia_sitemap');		
		$vars = $this->config->get('fia_fstmp_vars');
		
		foreach($vars as $var){
			if (isset($this->request->post[$var])) {
				$data[$var] = $this->request->post[$var];
			} else {
				$data[$var] = $this->config->get($var);
			}
		}
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/furious_sitemap', $data));
	}
	
	private function getEvents(){
		$events = array(
			'sitemap_EditBefore' => array(
				'trigger' => 'admin/controller/extension/feed/furious_sitemap/after',
				'action'  => 'event/sitemap/reSiteMap',
			),
			'sitemap_AddBefore' => array(
				'trigger' => 'admin/model/catalog/*/add*/after',
				'action'  => 'event/sitemap/reSiteMap',
			),
			'sitemap_DelBefore' => array(
				'trigger' => 'admin/model/catalog/*/delete*/after',
				'action'  => 'event/sitemap/reSiteMap',
			)
		);
		return $events;
	}
	
	public function install() {
		$this->load->model('setting/event');
		$events = $this->getEvents();
		foreach ($events as $code => $value) {
			$this->model_setting_event->deleteEventByCode($code);
			$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}
		$htaccess = @file_get_contents(DIR_CATALOG.'../.htaccess');
		if (strpos($htaccess, 'RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]') !== false) {
			if (is_writable(DIR_CATALOG.'../.htaccess')) {
				$htaccess = str_replace('RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]', '#RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]', $htaccess);			  
				file_put_contents(DIR_CATALOG.'../.htaccess', $htaccess);
			}
		}
	}
	
	public function uninstall() {
		$this->load->model('setting/event');
		$events = $this->getEvents();
		foreach ($events as $code => $value) {
			$this->model_setting_event->deleteEventByCode($code);
		}
		$htaccess = @file_get_contents(DIR_CATALOG.'../.htaccess');
		if (strpos($htaccess, '#RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]') !== false) {
			if (is_writable(DIR_CATALOG.'../.htaccess')) {
				$htaccess = str_replace('#RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]', 'RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]', $htaccess);			  
				file_put_contents(DIR_CATALOG.'../.htaccess', $htaccess);
			}
		}
	}
	
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/feed/furious_sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}	
}
