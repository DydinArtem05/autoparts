<?php

define('DIR_SITEMAP', dirname(DIR_APPLICATION) . '/sitemap/');

class ControllerApiSitemap extends Controller {
	
	private static $map_model = null;
	private static $model_path = null;
	private static $lang_id = null;
	private $langs = array();
	private $cats = array();	
	private $cat_data = array();
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->config->load('fia/fia_sitemap');
		self::$lang_id = $this->config->get('config_language');
		$map_model = $this->config->get('fia_fstmp_model');
		$model_path = $this->config->get('fia_fstmp_path');			
		$this->load->model($model_path);		
		$results  = $this->{$map_model}->getCatsByParentId(0);
		foreach ($results as $row) {
			$category_data[$row['parent_id']][$row['category_id']] = $row;
		}
		$this->cats = $category_data;
		$this->rebuilder();
		unset($this->cats);	
		$alternate = $this->config->get('feed_furious_sitemap_langs');	
		$this->langs = $this->cache->get('map.seolang');
		if (!$this->langs) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE status = '1' ORDER BY sort_order, name");
			foreach ($query->rows as $result) {
				$this->langs[$result['code']] = array(
					'language_id' => $result['language_id'],
					'code'        => $result['code'],
					'alternate'   => $alternate[$result['code']],
					'name' 		  => $result['name']
				);
			}
			$this->cache->set('map.seolang', $this->langs);
		}		
	}
	
	public function index() {		
		if (isset($this->request->post['api_token'])) {
			$recive = $this->request->post['api_token'];
			$configuration = md5($this->config->get('config_encryption'));
			
			if (strcmp($recive, $configuration) == 0) {
				$map_model = $this->config->get('fia_fstmp_model');
				$model_path = $this->config->get('fia_fstmp_path');
				$this->load->model($model_path);
				if ($this->request->server['HTTPS']) {
					$catalog = HTTPS_SERVER;
				} else {
					$catalog = HTTP_SERVER;
				}				
				$output = '<?xml version="1.0" encoding="UTF-8"?>';
				$output .= '<?xml-stylesheet type="text/xsl" href="' . $catalog . 'sitemap/stylesheet.min.xsl"?>';
				$output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
				$output .= '<sitemap>';
				$output .= '	<loc>' . $catalog . 'sitemap/index.xml</loc>';
				$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
				$output .= '</sitemap>';
				if ($this->config->get('feed_furious_sitemap_category_status')) {	
					$output .= '<sitemap>';		
					$output .= '	<loc>' . $catalog . 'sitemap/category.xml</loc>';
					$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
					$output .= '</sitemap>';
				}
				if ($this->config->get('feed_furious_sitemap_manufacturer_status')) {
					$output .= '<sitemap>';		
					$output .= '	<loc>' . $catalog . 'sitemap/brand.xml</loc>';
					$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
					$output .= '</sitemap>';
				}
				if ($this->config->get('feed_furious_sitemap_information_status')) {
					$output .= '<sitemap>';		
					$output .= '	<loc>' . $catalog . 'sitemap/article.xml</loc>';
					$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
					$output .= '</sitemap>';
				}
				if ($this->config->get('feed_furious_sitemap_product_status')) {
					$output .= '<sitemap>';		
					$output .= '	<loc>' . $catalog . 'sitemap/product.xml</loc>';
					$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
					$output .= '</sitemap>';
				}
				$output .= '</sitemapindex>';	
				$file = dirname(DIR_APPLICATION) . '/sitemap.xml';
				$handle = fopen($file, 'w');
				flock($handle, LOCK_EX);
				fwrite($handle, $output);
				flock($handle, LOCK_UN);
				fclose($handle);
	
				$sitemap_info = $this->prepareSitemaps($map_model);
			}			
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($sitemap_info));
	}
	
	private function xmlEntities($str) {
		$str = str_replace('&nbsp;', ' ', $str);
		$str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT , 'UTF-8');
		$str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
		$str = html_entity_decode($str);
		$str = htmlspecialchars_decode($str);
		$str = strip_tags($str);
		return $str;
	}
	
	private function prepareSitemaps($model = false) {
		$timer_start = microtime(true);	
		if ($model) {
			$out_head = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
			$out_head .= '<?xml-stylesheet type="text/xsl" href="' . HTTPS_SERVER . 'sitemap/stylesheet.min.xsl"?>'. "\n";
			$out_head .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'. "\n";
			$products = array();
			$output = $out_head;
			foreach ($this->langs as $main_code) {				
				$this->initLanguage($main_code['code'], $main_code['language_id']);
				$output .= '	<url>'. "\n";			
				$output .= '	<loc>' . $this->url->link('common/home') . '</loc>'. "\n";
				foreach ($this->langs as $lang_code) {
					if ($main_code['code'] != $lang_code['code']) {
					$this->initLanguage($lang_code['code'], $lang_code['language_id']);
					$output .= '	<xhtml:link rel="alternate" hreflang="' . $lang_code['alternate'] . '" href="' . $this->url->link('common/home') . '"/>'. "\n";
					}
				}
				$output .= '	<changefreq>' . trim($this->config->get('feed_furious_sitemap_home_frequency')) . '</changefreq>'. "\n";
				$output .= '	<priority>' . trim($this->config->get('feed_furious_sitemap_home_priority')) . '</priority>'. "\n";
				$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>'. "\n";
				$output .= '	</url>'. "\n";
			}
			$output .= '</urlset>'. "\n";
			$file = DIR_SITEMAP . 'index.xml';
			$handle = fopen($file, 'w');
			flock($handle, LOCK_EX);
			fwrite($handle, $output);
			flock($handle, LOCK_UN);
			fclose($handle);
			//Categories			
			if ($this->config->get('feed_furious_sitemap_category_status')) {
				if ($this->cat_data) {
					$output = '';				
					$output .= $out_head;					
						foreach ($this->cat_data as $category) {
							foreach ($this->langs as $main_code) {				
								$this->initLanguage($main_code['code'], $main_code['language_id']);
								$output .= '<url>'. "\n";
								$output .= '	<loc>' . $this->url->link('product/category', 'path=' . $category['path']) . '</loc>'. "\n";
								foreach ($this->langs as $lang_code) {
									if ($main_code['code'] != $lang_code['code']) {
										$this->initLanguage($lang_code['code'], $lang_code['language_id']);							
										$output .= '	<xhtml:link rel="alternate" hreflang="' . $lang_code['alternate'] . '" href="' . $this->url->link('product/category', 'path=' . $category['path']) . '"/>'. "\n";
									}
								}	
								$output .= '	<changefreq>' . trim($this->config->get('feed_furious_sitemap_category_frequency')) . '</changefreq>'. "\n";
								$output .= '	<priority>' . trim($this->config->get('feed_furious_sitemap_category_priority')) . '</priority>'. "\n";
								$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($category['date_modified'])) . '</lastmod>'. "\n";
								$output .= '</url>'. "\n";
							}
						}					
					
					$output.= '</urlset>';
					$file = DIR_SITEMAP . 'category.xml';
					$handle = fopen($file, 'w');
					flock($handle, LOCK_EX);
					fwrite($handle, $output);
					flock($handle, LOCK_UN);
					fclose($handle);
				}
			}
			//Brands
			if ($this->config->get('feed_furious_sitemap_manufacturer_status')) {
				$manufacturers = $this->{$model}->getMan();
				if ($manufacturers) {
					$output = '';				
					$output .= $out_head;			
						foreach($manufacturers as $manufacturer) {
							foreach ($this->langs as $main_code) {				
								$this->initLanguage($main_code['code'], $main_code['language_id']);
								$output .= '<url>'. "\n";
								$output .= '	<loc>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '</loc>'. "\n";
								foreach ($this->langs as $lang_code) {
									if ($main_code['code'] != $lang_code['code']) {
										$this->initLanguage($lang_code['code'], $lang_code['language_id']);
										$output .= '	<xhtml:link rel="alternate" hreflang="' . $lang_code['alternate'] . '" href="' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '"/>'. "\n";
									}
								}							
								$output .= '	<changefreq>' . trim($this->config->get('feed_furious_sitemap_manufacturer_frequency')) . '</changefreq>'. "\n";
								$output .= '	<priority>' . trim($this->config->get('feed_furious_sitemap_manufacturer_priority')) . '</priority>'. "\n";
								$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>'. "\n";
								$output .= '</url>'. "\n";
							}
						}
					$output.= '</urlset>';
					$file = DIR_SITEMAP . 'brand.xml';
					$handle = fopen($file, 'w');
					flock($handle, LOCK_EX);
					fwrite($handle, $output);
					flock($handle, LOCK_UN);
					fclose($handle);
				}
			}
			//Articles
			if ($this->config->get('feed_furious_sitemap_information_status')) {
				$info = $this->{$model}->getInformations();
				if ($info) {
					$output = '';				
					$output .= $out_head;			
						foreach($info as $information) {
							foreach ($this->langs as $main_code) {				
							$this->initLanguage($main_code['code'], $main_code['language_id']);
								$output .= '<url>'. "\n";
								$output .= '	<loc>' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '</loc>'. "\n";
								foreach ($this->langs as $lang_code) {
									if ($main_code['code'] != $lang_code['code']) {
										$this->initLanguage($lang_code['code'], $lang_code['language_id']);
										$output .= '	<xhtml:link rel="alternate" hreflang="' . $lang_code['alternate'] . '" href="' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '"/>'. "\n";
									}
								}
								$output .= '	<changefreq>' . trim($this->config->get('feed_furious_sitemap_information_frequency')) . '</changefreq>'. "\n";
								$output .= '	<priority>' . trim($this->config->get('feed_furious_sitemap_information_priority')) . '</priority>'. "\n";
								$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>'. "\n";
								$output .= '</url>'. "\n";
							}
						}
					$output .= '</urlset>';
					$file = DIR_SITEMAP . 'article.xml';
					$handle = fopen($file, 'w');
					flock($handle, LOCK_EX);
					fwrite($handle, $output);
					flock($handle, LOCK_UN);
					fclose($handle);
				}				
			}
			//Products
			if ($this->config->get('feed_furious_sitemap_product_status')) {
				$products = $this->{$model}->getProducts(0, 0);
				if ($products) {
					$this->load->model('tool/image');
					$output = '';
					$output .= '<?xml version="1.0" encoding="UTF-8"?>';
					$output .= '<?xml-stylesheet type="text/xsl" href="' . HTTPS_SERVER . 'sitemap/sitemap.xsl"?>';	
					$output .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
						foreach ($products as $product) {
							foreach ($this->langs as $main_code) {				
							$this->initLanguage($main_code['code'], $main_code['language_id']);
								$output .= '<url>'. "\n";
								$output .= '	<loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>'. "\n";
								foreach ($this->langs as $lang_code) {
									if ($main_code['code'] != $lang_code['code']) {
										$this->initLanguage($lang_code['code'], $lang_code['language_id']);
										$output .= '	<xhtml:link rel="alternate" hreflang="' . $lang_code['alternate'] . '" href="' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '"/>'. "\n";
									}
								}
								$output .= '	<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])) . '</lastmod>'. "\n";
								$output .= '	<changefreq>' . trim($this->config->get('feed_furious_sitemap_product_frequency')) . '</changefreq>'. "\n";
								$output .= '	<priority>' . trim($this->config->get('feed_furious_sitemap_product_priority')) . '</priority>'. "\n";
							if ($product['image']) {
								$meta_data = $this->{$model}->getProData($product['product_id'], $main_code['language_id']);
								if ($meta_data) {
								$output .= '	<image:image>'. "\n";
								$output .= '		<image:loc>' . $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) . '</image:loc>'. "\n";
								$output .= '		<image:title>' . trim(htmlspecialchars($this->xmlEntities($meta_data['name']), ENT_QUOTES, 'UTF-8')) . '</image:title>'. "\n";
								$output .= '		<image:license>https://creativecommons.org/licenses/by-sa/4.0/</image:license>'. "\n";
								$output .= '		<image:caption>' . trim(htmlspecialchars($this->xmlEntities($meta_data['meta_title']), ENT_QUOTES, 'UTF-8')) . '</image:caption>'. "\n";					
								$output .= '	</image:image>'. "\n";
								}
							}
								$output .= '</url>'. "\n";	
							}
						}
					$output.= '</urlset>';
					$file = DIR_SITEMAP . 'product.xml';
					$handle = fopen($file, 'w');
					flock($handle, LOCK_EX);
					fwrite($handle, $output);
					flock($handle, LOCK_UN);
					fclose($handle);
				}
			}
		}
		return number_format(microtime(true) - $timer_start, 4);		
	}
	
	private function rebuilder($parent_id = 0, $current_path = null) {		
		$new_path = '';	 
		if (array_key_exists($parent_id, $this->cats)) {
			$results = $this->cats[$parent_id];
			foreach ($results as $result) {
				if (!isset($current_path)) {
					$new_path = $result['category_id'];
				} else {
					$new_path = $current_path . '_' . $result['category_id'];
				}			
				if (array_key_exists($result['category_id'], $this->cats)) {
					$children = $this->rebuilder($result['category_id'], $new_path);
				}				
				$result['path'] = $new_path;				
				$this->cat_data[$result['category_id']] = $result;					
			}
    	}
	}
	
	private function initLanguage($lang_code, $language_id) {
		$this->session->data['language'] = $lang_code;
		$this->config->set('config_language_id', $language_id);	
		$language = new Language($lang_code);
		$language->load($lang_code);
		$this->registry->set('language', $language);
	}
}