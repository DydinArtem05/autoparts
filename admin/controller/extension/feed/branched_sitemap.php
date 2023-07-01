<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2022, http://sergetkach.com/
 */

ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
error_reporting(E_ALL);

class ControllerExtensionFeedBranchedSitemap extends Controller {
	private $error = array();

	public function install()	{
		$this->load->model('extension/feed/branched_sitemap');

		$this->model_extension_feed_branched_sitemap->install();
	}

	public function index()	{
		$this->load->language('extension/feed/branched_sitemap');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$this->load->model('extension/feed/branched_sitemap');

		$model = $this->model_extension_feed_branched_sitemap;

		$data['text_success'] = '';

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_branched_sitemap', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			//$this->response->redirect($this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
			$data['text_success'] = $this->language->get('text_success'); // if no success redirect
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['tab_general'] = $this->language->get('tab_general');

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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/branched_sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action']	 = $this->url->link('extension/feed/branched_sitemap', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel']	 = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);

		if (isset($this->request->post['feed_branched_sitemap_licence'])) {
			$data['feed_branched_sitemap_licence'] = $this->request->post['feed_branched_sitemap_licence'];
		} else {
			$data['feed_branched_sitemap_licence'] = $this->config->get('feed_branched_sitemap_licence');
		}

		if (version_compare(PHP_VERSION, '7.2') >= 0) {
			$php_v = '72_73';
		} elseif (version_compare(PHP_VERSION, '7.1') >= 0) {
			$php_v = '71';
		} elseif (version_compare(PHP_VERSION, '5.6.0') >= 0) {
			$php_v = '56_70';
		} elseif (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$php_v = '54_56';
		} else {
			echo "Sorry! Version for PHP 5.3 Not Supported!<br>Please contact to author!";
			exit;
		}

		$file = DIR_SYSTEM . 'library/branched_sitemap/branched_sitemap_' . $php_v . '.php';

		if (is_file($file)) {
			require_once $file;
		} else {
			echo "No file '$file'<br>";
			exit;
		}

		if ($data['feed_branched_sitemap_licence']) {
			$sitemap							 = new Sitemap($data['feed_branched_sitemap_licence']);
			$data['valid_licence'] = $sitemap->isValidLicence($data['feed_branched_sitemap_licence']);
		} else {
			$data['valid_licence'] = false;
		}

		if (isset($this->request->post['feed_branched_sitemap_status'])) {
			$data['feed_branched_sitemap_status'] = $this->request->post['feed_branched_sitemap_status'];
		} else {
			$data['feed_branched_sitemap_status'] = $this->config->get('feed_branched_sitemap_status');
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (count($data['languages']) > 1) {
			$data['is_multilingual'] = true;
		} else {
			$data['is_multilingual'] = false;
		}

		$data['systems'] = array('OpenCart', 'ocStore');

		if (isset($this->request->post['feed_branched_sitemap_system'])) {
			$data['feed_branched_sitemap_system'] = $this->request->post['feed_branched_sitemap_system'];
		} elseif ($this->config->get('feed_branched_sitemap_system')) {
			$data['feed_branched_sitemap_system'] = $this->config->get('feed_branched_sitemap_system');
		} else {
			$data['feed_branched_sitemap_system'] = '';
		}

		if (isset($this->request->post['feed_branched_sitemap_multishop'])) {
			$data['feed_branched_sitemap_multishop'] = $this->request->post['feed_branched_sitemap_multishop'];
		} elseif ($this->config->get('feed_branched_sitemap_multishop')) {
			$data['feed_branched_sitemap_multishop'] = $this->config->get('feed_branched_sitemap_multishop');
		} else {
			$data['feed_branched_sitemap_multishop'] = '';
		}

		$data['cachetime_values'] = array(
			array('value' => 0, 'text' => $this->language->get('cachetime_values_0')),
			array('value' => 3600, 'text' => $this->language->get('cachetime_values_1hour')),
			array('value' => 21600, 'text' => $this->language->get('cachetime_values_6hours')),
			array('value' => 43200, 'text' => $this->language->get('cachetime_values_12hours')),
			array('value' => 86400, 'text' => $this->language->get('cachetime_values_24hours')),
			array('value' => 604800, 'text' => $this->language->get('cachetime_values_1week')),
		);

		// there is 0 value - so condition elseif($this->config->get('branched_sitemap_cachetime')) { is not good

		if (isset($this->request->post['feed_branched_sitemap_cachetime'])) {
			$data['feed_branched_sitemap_cachetime'] = $this->request->post['feed_branched_sitemap_cachetime'];
		} elseif ($this->config->get('feed_branched_sitemap_cachetime') || $this->config->get('feed_branched_sitemap_cachetime') === '0') {
			$data['feed_branched_sitemap_cachetime'] = $this->config->get('feed_branched_sitemap_cachetime');
		} else {
			$data['feed_branched_sitemap_cachetime'] = 86400;
		}


		// Feed
		// Priority
		$data['a_priority_possible'] = array('1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1');

		// Category Level 1
		if (isset($this->request->post['feed_branched_sitemap_priority_category_level_1'])) {
			$data['feed_branched_sitemap_priority_category_level_1'] = $this->request->post['feed_branched_sitemap_priority_category_level_1'];
		} elseif ($this->config->get('feed_branched_sitemap_priority_category_level_1')) {
			$data['feed_branched_sitemap_priority_category_level_1'] = $this->config->get('feed_branched_sitemap_priority_category_level_1');
		} else {
			$data['feed_branched_sitemap_priority_category_level_1'] = '0.8';
		}

//    // Category Level 2
//		if (isset($this->request->post['feed_branched_sitemap_priority_category_level_2'])) {
//			$data['feed_branched_sitemap_priority_category_level_2'] = $this->request->post['feed_branched_sitemap_priority_category_level_2'];
//		} elseif ($this->config->get('feed_branched_sitemap_priority_category_level_2')) {
//			$data['feed_branched_sitemap_priority_category_level_2'] = $this->config->get('feed_branched_sitemap_priority_category_level_2');
//		} else {
//			$data['feed_branched_sitemap_priority_category_level_2'] = '0.7';
//		}
//    
//    // Category Level More
//		if (isset($this->request->post['feed_branched_sitemap_priority_category_level_more'])) {
//			$data['feed_branched_sitemap_priority_category_level_more'] = $this->request->post['feed_branched_sitemap_priority_category_level_more'];
//		} elseif ($this->config->get('feed_branched_sitemap_priority_category_level_more')) {
//			$data['feed_branched_sitemap_priority_category_level_more'] = $this->config->get('feed_branched_sitemap_priority_category_level_more');
//		} else {
//			$data['feed_branched_sitemap_priority_category_level_more'] = '0.7';
//		}
		// Priority Product
		if (isset($this->request->post['feed_branched_sitemap_priority_product'])) {
			$data['feed_branched_sitemap_priority_product'] = $this->request->post['feed_branched_sitemap_priority_product'];
		} elseif ($this->config->get('feed_branched_sitemap_priority_product')) {
			$data['feed_branched_sitemap_priority_product'] = $this->config->get('feed_branched_sitemap_priority_product');
		} else {
			$data['feed_branched_sitemap_priority_product'] = '0.6';
		}

		// Priority Manufacturer
		if (isset($this->request->post['feed_branched_sitemap_priority_manufacturer'])) {
			$data['feed_branched_sitemap_priority_manufacturer'] = $this->request->post['feed_branched_sitemap_priority_manufacturer'];
		} elseif ($this->config->get('feed_branched_sitemap_priority_manufacturer')) {
			$data['feed_branched_sitemap_priority_manufacturer'] = $this->config->get('feed_branched_sitemap_priority_manufacturer');
		} else {
			$data['feed_branched_sitemap_priority_manufacturer'] = '0.5';
		}

		// Priority Blog
		if (isset($this->request->post['feed_branched_sitemap_priority_blog'])) {
			$data['feed_branched_sitemap_priority_blog'] = $this->request->post['feed_branched_sitemap_priority_blog'];
		} elseif ($this->config->get('feed_branched_sitemap_priority_blog')) {
			$data['feed_branched_sitemap_priority_blog'] = $this->config->get('feed_branched_sitemap_priority_blog');
		} else {
			$data['feed_branched_sitemap_priority_blog'] = '0.5';
		}

		// Priority Other
		if (isset($this->request->post['feed_branched_sitemap_priority_other'])) {
			$data['feed_branched_sitemap_priority_other'] = $this->request->post['feed_branched_sitemap_priority_other'];
		} elseif ($this->config->get('feed_branched_sitemap_priority_other')) {
			$data['feed_branched_sitemap_priority_other'] = $this->config->get('feed_branched_sitemap_priority_other');
		} else {
			$data['feed_branched_sitemap_priority_other'] = '0.5';
		}

		// Feed Urls    
		if (isset($this->request->post['feed_branched_sitemap_url'])) {
			$data['feed_branched_sitemap_url'] = $this->request->post['feed_branched_sitemap_url'];
		} elseif (null !== $this->config->get('feed_branched_sitemap_url')) {
			$data['feed_branched_sitemap_url'] = $this->config->get('feed_branched_sitemap_url');
		} else {
			$data['feed_branched_sitemap_url'] = 'branched-sitemap.xml';
		}

		if (isset($this->request->post['feed_branched_sitemap_urls'])) {
			$data['feed_branched_sitemap_urls'] = $this->request->post['feed_branched_sitemap_urls'];
		} elseif ($this->config->get('feed_branched_sitemap_urls')) {
			$data['feed_branched_sitemap_urls'] = $this->config->get('feed_branched_sitemap_urls');
		} else {
			$data['feed_branched_sitemap_urls'] = [];

			foreach ($data['languages'] as $language) {
				$data['feed_branched_sitemap_urls'][$language['language_id']] = '';
			}
		}

		$data['sitemap_urls_are_defined'] = false;

		foreach ($data['languages'] as $language) {
			if (!empty($data['feed_branched_sitemap_urls'][$language['language_id']])) {
				$data['sitemap_urls_are_defined'] = true;
			}
		}

		if (isset($this->request->post['feed_branched_sitemap_home_urls'])) {
			$data['feed_branched_sitemap_home_urls'] = $this->request->post['feed_branched_sitemap_home_urls'];
		} elseif ($this->config->get('feed_branched_sitemap_home_urls')) {
			$data['feed_branched_sitemap_home_urls'] = $this->config->get('feed_branched_sitemap_home_urls');
		} else {
			$data['feed_branched_sitemap_home_urls'] = [];

			foreach ($data['languages'] as $language) {
				$data['feed_branched_sitemap_home_urls'][$language['language_id']] = HTTPS_CATALOG . $this->dir4Lang($language['code']);
			}
		}

		if (isset($this->request->post['feed_branched_sitemap_lang_flags'])) {
			$data['feed_branched_sitemap_lang_flags'] = $this->request->post['feed_branched_sitemap_lang_flags'];
		} elseif ($this->config->get('feed_branched_sitemap_lang_flags')) {
			$data['feed_branched_sitemap_lang_flags'] = $this->config->get('feed_branched_sitemap_lang_flags');
		} else {
			$data['feed_branched_sitemap_lang_flags'] = [];

			foreach ($data['languages'] as $language) {
				$data['feed_branched_sitemap_lang_flags'][$language['language_id']] = '';
			}
		}

		if (isset($this->request->post['feed_branched_sitemap_sitemapindex_status'])) {
			$data['feed_branched_sitemap_sitemapindex_status'] = $this->request->post['feed_branched_sitemap_sitemapindex_status'];
		} elseif (null !== $this->config->get('feed_branched_sitemap_sitemapindex_status')) {
			$data['feed_branched_sitemap_sitemapindex_status'] = $this->config->get('feed_branched_sitemap_sitemapindex_status');
		} else {
			$data['feed_branched_sitemap_sitemapindex_status'] = true;
		}

		$data['limits'] = array(100, 200, 500, 1000, 3000, 5000, 10000, 20000, 30000, 40000, 50000);

		if (isset($this->request->post['feed_branched_sitemap_limit'])) {
			$data['feed_branched_sitemap_limit'] = $this->request->post['feed_branched_sitemap_limit'];
		} elseif ($this->config->get('feed_branched_sitemap_limit')) {
			$data['feed_branched_sitemap_limit'] = $this->config->get('feed_branched_sitemap_limit');
		} else {
			$data['feed_branched_sitemap_limit'] = 10000;
		}

		if (isset($this->request->post['feed_branched_sitemap_off_description'])) {
			$data['feed_branched_sitemap_off_description'] = $this->request->post['feed_branched_sitemap_off_description'];
		} elseif ($this->config->get('feed_branched_sitemap_off_description')) {
			$data['feed_branched_sitemap_off_description'] = $this->config->get('feed_branched_sitemap_off_description');
		} else {
			$data['feed_branched_sitemap_off_description'] = '';
		}

		$data['blogs_possible'] = array(
			'ocstore_default'	 => $this->language->get('text_blogs_ocstore_default'),
			'octemplates'			 => $this->language->get('text_blogs_octemplates'),
			//'newsblog' => $this->language->get('text_blogs_newsblog')),
			//'aridius' => $this->language->get('text_blogs_aridius')),
			//'technics' => $this->language->get('text_blogs_technics')),
		);

		if (!$model->existTable('blog_category')) {
			unset($data['blogs_possible']['ocstore_default']);
		}

		if (!$model->existTable('oct_blogcategory')) {
			unset($data['blogs_possible']['octemplates']);
		}

		$data['blog_are_present'] = false;
		if (count($data['blogs_possible']) > 0) {
			$data['blog_are_present'] = true;
		}

		// A! Если чекбокс был снят, то поле с блогами отсутствует в POST-запросе
		if (isset($this->request->post['feed_branched_sitemap_blogs'])) {
			$data['feed_branched_sitemap_blogs'] = $this->request->post['feed_branched_sitemap_blogs'];
		} elseif ($this->request->server['REQUEST_METHOD'] != 'POST' && $this->config->get('feed_branched_sitemap_blogs')) {
			$data['feed_branched_sitemap_blogs'] = $this->config->get('feed_branched_sitemap_blogs');
		} else {
			$data['feed_branched_sitemap_blogs'] = [];
		}


		// Feed Image
		if (isset($this->request->post['feed_branched_sitemap_image_status'])) {
			$data['feed_branched_sitemap_image_status'] = $this->request->post['feed_branched_sitemap_image_status'];
		} elseif ($this->config->get('feed_branched_sitemap_image_status')) {
			$data['feed_branched_sitemap_image_status'] = $this->config->get('feed_branched_sitemap_image_status');
		} else {
			$data['feed_branched_sitemap_image_status'] = false;
		}

		if (isset($this->request->post['feed_branched_sitemap_off_check_image_file'])) {
			$data['feed_branched_sitemap_off_check_image_file'] = $this->request->post['feed_branched_sitemap_off_check_image_file'];
		} elseif ($this->config->get('feed_branched_sitemap_off_check_image_file')) {
			$data['feed_branched_sitemap_off_check_image_file'] = $this->config->get('feed_branched_sitemap_off_check_image_file');
		} else {
			$data['feed_branched_sitemap_off_check_image_file'] = '';
		}

		if (isset($this->request->post['feed_branched_sitemap_webp_status'])) {
			$data['feed_branched_sitemap_webp_status'] = $this->request->post['feed_branched_sitemap_webp_status'];
		} elseif ($this->config->get('feed_branched_sitemap_webp_status')) {
			$data['feed_branched_sitemap_webp_status'] = $this->config->get('feed_branched_sitemap_webp_status');
		} else {
			$data['feed_branched_sitemap_webp_status'] = false;
		}

		if (isset($this->request->post['feed_branched_sitemap_require_image_caption'])) {
			$data['feed_branched_sitemap_require_image_caption'] = $this->request->post['feed_branched_sitemap_require_image_caption'];
		} elseif ($this->config->get('feed_branched_sitemap_require_image_caption')) {
			$data['feed_branched_sitemap_require_image_caption'] = $this->config->get('feed_branched_sitemap_require_image_caption');
		} else {
			$data['feed_branched_sitemap_require_image_caption'] = '';
		}


		// Common Blocks
		$data['header']			 = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');


		// Response
		$this->response->setOutput($this->load->view('extension/feed/branched_sitemap', $data));
	}

	public function autoSave() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('feed_branched_sitemap', $this->request->post);
	}

	public function addSitemapRewriteUrl() {
		$this->load->language('extension/feed/branched_sitemap');

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		if (count($languages) > 1) {
			$is_multilingual = true;
		} else {
			$is_multilingual = false;
		}

		$json						 = [];
		$json['errors']	 = null;
		$error_todo			 = false;

		if (version_compare(VERSION, '3.0', '>=')) {
			$route_feed = 'extension/feed/';
		} elseif (version_compare(VERSION, '2.2', '>=')) {
			$route_feed = 'extension/feed/';
		} elseif (version_compare(VERSION, '2.0', '>=')) {
			$route_feed = 'feed/';
		}

		$route_bs = 'index.php?route=' . $route_feed . 'branched_sitemap';

		$sitemap_url = $this->request->post['sitemap_url']; // A! it is from modal form, NOT feed_branched_sitemap_url

		if (!preg_match('/^([a-z0-9-.]+)$/', $sitemap_url)) {
			$json['errors']['url_format'] = $this->language->get('error_url_format');
			goto addSitemapRewriteUrlEnd;
		}

		$sitemap_url_base = basename($sitemap_url, '.xml');
		
		if ('sitemap.xml' == $sitemap_url) {
			$new_rules = '';

			$dir_site = str_replace('system/', '', DIR_SYSTEM);

			$file = '.htaccess';

			if (!is_file($dir_site . $file)) {
				$json['errors']['isnt_file'] = sprintf($this->language->get('error_file_exist'), $file);
				$error_todo									 = true;
				goto addSitemapRewriteUrlEnd;
			} elseif (!is_writable($dir_site . $file)) {
				$json['errors']['isnt_writable'] = sprintf($this->language->get('error_writable'), $file);
				$error_todo											 = true;
				goto addSitemapRewriteUrlEnd;
			}
		}

	  $is_lang_dir_on_site = false;
		
		if ($is_lang_dir_on_site) {
			$delimiter_char = '/';
		} else {
			$delimiter_char = '-';
		}

		if ($is_multilingual) {
			$n_empty_language_flag = 0;

			$equals_control = [];

			foreach ($languages as $language) {
				$url = trim($this->request->post['language_url'][$language['language_id']], '/ ');

				// equals
				if (in_array($url, $equals_control)) {
					$json['errors']['equals_url'] = $this->language->get('error_equals_url');
					goto addSitemapRewriteUrlEnd;
				}

				$equals_control[] = $url;

				// check response code
				if (200 != $this->httpCode($url) && 200 != $this->httpCode($url . '/')) {
					$json['errors']['page_response_code'] = sprintf($this->language->get('error_page_response_code'), $url, $url);
					goto addSitemapRewriteUrlEnd;
				}
			}

			foreach ($languages as $language) {
				$language_url = trim($this->request->post['language_url'][$language['language_id']]);

				$language_flag = $this->langDirFromURL($language_url);

				$json['lang_flags'][$language['language_id']] = $language_flag;

				if ('' == $language_flag) {
					$n_empty_language_flag++;
					$delimiter = '';
				} else {
					$delimiter = $delimiter_char;
				}

				$json['feeds_urls'][$language['language_id']] = HTTPS_CATALOG . $language_flag . $delimiter . $sitemap_url;
			}

			if ($n_empty_language_flag == count($languages)) {
				$json['errors']['empty_language_flag'] = $this->language->get('error_empty_language_flag');
			}
		} else {
			$json['feeds_urls'][$this->config->get('config_language_id')] = HTTPS_CATALOG . $sitemap_url;
		}

		if ('sitemap.xml' == $sitemap_url) {
			$content = file_get_contents($dir_site . $file);

			// Закомментим дефолтную карту сайта
			// RewriteRule ^sitemap.xml$ index.php?route=feed/google_sitemap [L] -- 2.1
			// RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L] -- 2.2+

			$s = PHP_EOL . 'RewriteRule ^sitemap.xml$ index.php?route=' . $route_feed . 'google_sitemap [L]';

			if (false !== strpos($content, $s)) {
				$s2 = str_replace(PHP_EOL, '', $s);

				$content = str_replace($s2, '# ' . $s2, $content);
			}

			file_put_contents($dir_site . $file, $content, LOCK_EX);
		}

		addSitemapRewriteUrlEnd:

		if (isset($json['errors'])) {
			$json['error'] = '';
			foreach ($json['errors'] as $error) {
				$json['error'] .= $error . '<br>';
			}

			if ($error_todo) {
				$json['error'] .= $this->language->get('error_todo');
			}
		} else {
			$json['success'] = $is_multilingual ? $this->language->get('success_todo_2') : $this->language->get('success_todo_1');

			$json['success'] .= '<ul>';

			foreach ($languages as $language) {
				$json['success'] .= '<li><a href="' . $json['feeds_urls'][$language['language_id']] . '" target="_blank">' . $json['feeds_urls'][$language['language_id']] . '</a></li>';
			}

			$json['success'] .= '</ul>';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getServerType() {
		$server = false;

		$headers = get_headers(HTTPS_CATALOG);

		foreach ($headers as $value) {
			if (false !== strpos($value, 'Server') || false !== strpos($value, 'server')) {

				$array = explode(':', $value);

				$server = trim($array[1]);

				break;
			}
		}

		return $server;
	}

	public function dir4Lang($code)	{
		$array = explode('-', $code);

		if ($this->config->get('config_language') == $code)
			return '';

		return $array[0];
	}

	public function langDirFromURL($url) {
		$domain = str_replace(['http', 'https', ':', '//', 'www', '/'], '', HTTPS_CATALOG);

		$url = str_replace(['http', 'https', ':', '//', 'www', $domain, ($domain . '/')], '', $url);

		$url = trim($url, '/');

		return $url;
	}

	public function httpCode($url) {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_exec($ch);

		if (!curl_errno($ch)) {
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		} else {
			$code = false;
		}

		curl_close($ch);

		return $code;
	}

	protected function validate()	{
		if (!$this->user->hasPermission('modify', 'extension/feed/branched_sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

}
