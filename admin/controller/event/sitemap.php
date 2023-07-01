<?php
class ControllerEventSitemap extends Controller {
	
	public function reSiteMap() {
		if ($this->config->get('feed_furious_sitemap_status')) {
			if ($this->request->server['HTTPS']) {
				$catalog = HTTPS_CATALOG;
			} else {
				$catalog = HTTP_CATALOG;
			}				
			$url = $catalog . 'index.php?route=api/sitemap';
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_FILETIME, true);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, 'api_token=' . md5($this->config->get('config_encryption')));		
			$response = curl_exec($curl);		
			$info = curl_getinfo($curl);
			
			curl_close($curl);
			if ($response) {
				$this->session->data['success'] = 'Sitemap loaded: ' . $response . ' sec.';	
				return true;
			} else {
				$this->log->write('sitemap generated error:' . print_r($info,1));
			}
		}
	}
}