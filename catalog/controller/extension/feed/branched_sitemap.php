<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2022, http://sergetkach.com/
 */

class ControllerExtensionFeedBranchedSitemap extends Controller {
	private $sitemap;
	private $exist_main_cat;
	private $xml_image_href;
	private $base_url;
	private $page;
	private $limit;
	private $changefreq;
	private $cachetime;

	function __construct($registry) {
		parent::__construct($registry);
		
		// Prevent dependency for page 404 from sitemap status when friendlyURLWithoutHtaccess() is called
		if (isset($this->session->data['bs_flag']) && $this->session->data['bs_flag']) {
			if (!$this->config->get('feed_branched_sitemap_status')) {
				$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
				exit;
			}
		}

		// Var 1: Define language -- Was early
		// for case when htaccess rules already defined -- old users...
		// Явное указание языка через GET - совместимо с OCDEV.pro - Мультиязык SEO PRO, код языка в url и правильный hreflang
		if (isset($this->request->get['lang_code']) && $this->request->get['lang_code']) {
			$this->config->set('config_language_id', $this->getLanguageIdByCode($this->request->get['lang_code']));
		}

		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$this->base_url = $this->config->get('config_ssl');
		} else {
			$this->base_url = $this->config->get('config_url');
		}

		$this->page = 1;

		if (isset($this->request->get['page'])) {
			$this->page = $this->request->get['page'];
		}

		// cachetime
		$this->cachetime = $this->config->get('feed_branched_sitemap_cachetime');

		// limit
		$this->limit = $this->config->get('feed_branched_sitemap_limit');

		if (!$this->limit) {
			$this->limit = 10000;
		}

		$this->load->model('extension/feed/branched_sitemap');

		$this->exist_main_cat = $this->model_extension_feed_branched_sitemap->existMainCat();

		// if images are included
		if ($this->config->get('feed_branched_sitemap_image_status')) {
			$this->load->model('tool/image');

			$this->xml_image_href = ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
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

		// todo...
		// get licence
		$this->sitemap = new Sitemap($this->config->get('feed_branched_sitemap_licence'));

		$this->changefreq = array(
			'category_changefreq_default'			 => 'yearly', // Более 1 года
			'category_changefreq_correlation'	 => array(
				'1'		 => 'daily',
				'7'		 => 'weekly',
				'30'	 => 'monthly',
				'365'	 => 'yearly',
			),
			'product_changefreq_default'			 => 'yearly', // Более 1 года
			'product_changefreq_correlation'	 => array(
				'1'		 => 'daily',
				'7'		 => 'weekly',
				'30'	 => 'monthly',
				'365'	 => 'yearly',
			),
		);
	}

	public function index()	{
		if ($this->config->get('feed_branched_sitemap_sitemapindex_status')) {
      return $this->sitemaindex();
    } else {
      return $this->allInOne();
    }
  }

	private function allInOne()	{
		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . $this->xml_image_href . '>' . PHP_EOL;
		$output	 .= '<url>';
		$output	 .= '<loc>' . $this->url->link('common/home') . '</loc>';
		$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>';
//		$output	 .= '<changefreq>daily</changefreq>';
		$output	 .= '<priority>1.0</priority>';
		$output	 .= '</url>';

		$output	 .= $this->getCategoriesAll();
		$output	 .= $this->getProductsAll();
		$output	 .= $this->getManufacturersAll();
		$output	 .= $this->getInformationAll();

		// Blogs . Begin
		if ($this->config->get('feed_branched_sitemap_blogs')) {
			if (array_key_exists('ocstore_default', $this->config->get('feed_branched_sitemap_blogs'))) {
				$output	 .= $this->getocStoreBlogCategoriesAll();
				$output	 .= $this->getocStoreBlogArticlesAll();
			}

			if (array_key_exists('octemplates', $this->config->get('feed_branched_sitemap_blogs'))) {
				$output	 .= $this->getOCTemplatesBlogCategoriesAll();
				$output	 .= $this->getOCTemplatesBlogArticlesAll();
			}
		}
		// Blogs . End

		$output .= '</urlset>' . PHP_EOL;

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	private function sitemaindex() {
		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap.xls"?>' . PHP_EOL; */
		$output	 .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$output .= '<sitemap>'
			. '<loc>' . $this->branchLink('main') . '</loc>'
			. '</sitemap>';

		$output	 .= $this->getCategoriesIndex();
		$output	 .= $this->getProductsIndex();
		$output	 .= $this->getManufacturersIndex();
		$output	 .= $this->getInformationIndex();

		// Blogs . Begin
		if ($this->config->get('feed_branched_sitemap_blogs')) {
			if (array_key_exists('ocstore_default', $this->config->get('feed_branched_sitemap_blogs'))) {
				$output	 .= $this->getocStoreBlogCategoriesIndex();
				$output	 .= $this->getocStoreBlogArticlesIndex();
			}

			if (array_key_exists('octemplates', $this->config->get('feed_branched_sitemap_blogs'))) {
				$output	 .= $this->getOCTemplatesBlogCategoriesIndex();
				$output	 .= $this->getOCTemplatesBlogArticlesIndex();
			}
		}
		// Blogs . End

		$output .= '</sitemapindex>' . PHP_EOL;

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	/* Main
	  --------------------------------------------------------------------------- */

	public function main() {
		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
		$output	 .= '<url>';
		$output	 .= '<loc>' . $this->url->link('common/home') . '</loc>';
		$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>';
//		$output	 .= '<changefreq>daily</changefreq>';
		$output	 .= '<priority>1.0</priority>';
		$output	 .= '</url>';
		$output	 .= '</urlset>' . PHP_EOL;

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	/* Categories
	  --------------------------------------------------------------------------- */

	public function categories() {
		if (!$this->page) {
			return $this->getCategoriesIndex();
		} else {
			return $this->getCategoriesOnPage();
		}
	}

	private function getCategoriesIndex() {
		$output = '';

		// No Levels - important date modified
		$categories_total = $this->model_extension_feed_branched_sitemap->getTotalCategories();

		$n_pages = ceil($categories_total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('categories', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	private function getCategoriesOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'categories_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getCategories($filter_data);

		$output .= $this->categoriesList($categories);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	private function getCategoriesAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'categories_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getCategories();

		$output = $this->categoriesList($categories);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function categoriesList($categories) {
		$output = '';

		foreach ($categories as $category) {
			$output .= '<url>' . PHP_EOL;

			//$output .= '<loc>' . $this->url->link('product/category', 'path=' . $category['category_id']) . '</loc>' . PHP_EOL;
			$output .= '<loc>' . $this->url->link('product/category', 'path=' . $this->model_extension_feed_branched_sitemap->getPathByCategory($category['category_id'])) . '</loc>' . PHP_EOL;

			if ($category['date_modified'] > '0000-00-00 00:00:00')
				$date		 = $category['date_modified'];
			else
				$date		 = $category['date_added'];
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;

//			$data = array(
//				'date'									 => $date,
//				'changefreq_correlation' => $this->changefreq['category_changefreq_correlation'],
//				'changefreq_default'		 => $this->changefreq['category_changefreq_default']
//			);
//
//			$output .= '<changefreq>' . $this->sitemap->getCategoryChangefreq($data) . '</changefreq>' . PHP_EOL;

			$output .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_category_level_1') . '</priority>' . PHP_EOL;

			$output .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	/* Products
	  --------------------------------------------------------------------------- */

	public function products() {
		if (!$this->page) {
			return $this->getProductsIndex();
		} else {
			return $this->getProductsOnPage();
		}
	}

	private function getProductsIndex() {
		$output = '';

		$product_total = $this->model_extension_feed_branched_sitemap->getTotalProducts();

		$n_pages = ceil($product_total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('products', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	private function getProductsOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'products_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . $this->xml_image_href . '>' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		$products = $this->model_extension_feed_branched_sitemap->getProducts($filter_data);

		$output .= $this->productsList($products);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	private function getProductsAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'products_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		$products = $this->model_extension_feed_branched_sitemap->getProducts();

		$output = $this->productsList($products);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function productsList($products) {
		$output = '';

		foreach ($products as $product) {
			$output .= '<url>' . PHP_EOL;

			if ($this->exist_main_cat) {
				$output .= '<loc>' . $this->url->link('product/product', 'path=' . $this->model_extension_feed_branched_sitemap->getPathByProduct($product['product_id']) . '&product_id=' . $product['product_id']) . '</loc>' . PHP_EOL;
			} else {
				$output .= '<loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>' . PHP_EOL;
			}

			if ($product['date_modified'] > '0000-00-00 00:00:00')
				$date		 = $product['date_modified'];
			else
				$date		 = $product['date_added'];
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;

//			$data = array(
//				'date'									 => $date,
//				'changefreq_correlation' => $this->changefreq['product_changefreq_correlation'],
//				'changefreq_default'		 => $this->changefreq['product_changefreq_default']
//			);
//
//			$output .= '<changefreq>' . $this->sitemap->getProductChangefreq($data) . '</changefreq>' . PHP_EOL;

			$output .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_product') . '</priority>' . PHP_EOL;

			// image
			if ($this->config->get('feed_branched_sitemap_image_status')) {
				if ($product['image']) {
					$image_info = pathinfo($product['image']);

					// Sometimes can be 'undefined' ... - bug of filemanager or...
					if (isset($image_info['extension'])) {
						// Image Config is defferent for 2.1 (2.2), for 2.3 & for 3.0.2 !!
						// A! WebP
						if ($this->config->get('feed_branched_sitemap_webp_status')) {
							$image_info['extension'] = 'webp';
						}

						// Image Resize create hight load - so we can avoid it
						if ($this->config->get('feed_branched_sitemap_off_check_image_file')) {
							$image = $image_info['dirname'] . '/' . $image_info['filename'] . '-' . $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width') . 'x' . $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height') . '.' . $image_info['extension'];

							$image = HTTPS_SERVER . 'image/cache/' . $image;

							if (!is_file(DIR_IMAGE . 'cache/' . $image)) {
								// Report :)
								$this->log->write('Branched Sitemap :: Image "' . $image . '" not exists on page ' . $this->url->link('product/product', '&product_id=' . $product['product_id']));
							}
						} else {
							$image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
						}
					}

					if ($image) {
						$output	 .= '<image:image>' . PHP_EOL;
						$output	 .= '<image:loc>' . $image . '</image:loc>' . PHP_EOL;
						if ($this->config->get('feed_branched_sitemap_require_image_caption') && $product['name']) {
							$output	 .= '<image:caption>' . $this->cleanup($product['name']) . '</image:caption>' . PHP_EOL;
							$output	 .= '<image:title>' . $this->cleanup($product['name']) . '</image:title>' . PHP_EOL;
						}
						$output .= '</image:image>' . PHP_EOL;
					}
				}
			}

			$output .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	/* Manufacturers
	  --------------------------------------------------------------------------- */

	public function manufacturers() {
		if (!$this->page) {
			return $this->getManufacturersIndex();
		} else {
			return $this->getManufacturersOnPage();
		}
	}

	private function getManufacturersIndex() {
		$output = '';

		$manufacturers_total = $this->model_extension_feed_branched_sitemap->getTotalManufacturers();

		$n_pages = ceil($manufacturers_total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('manufacturers', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	private function getManufacturersOnPage()	{
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'manufacturers_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap-manufacturers.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		$manufacturers = $this->model_extension_feed_branched_sitemap->getManufacturers($filter_data);

		$output .= $this->manufacturersList($manufacturers);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	private function getManufacturersAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'manufacturers_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		$manufacturers = $this->model_extension_feed_branched_sitemap->getManufacturers();

		$output = $this->manufacturersList($manufacturers);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function manufacturersList($manufacturers) {
		$output = '';

		foreach ($manufacturers as $manufacturer) {
			$output	 .= '<url>' . PHP_EOL;
			$output	 .= '<loc>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '</loc>' . PHP_EOL;
			$output	 .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_manufacturer') . '</priority>' . PHP_EOL;
			$output	 .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	/* Information
	  --------------------------------------------------------------------------- */

	public function information() {
		if (!$this->page) {
			return $this->getInformationIndex();
		} else {
			return $this->getInformationOnPage();
		}
	}

	private function getInformationIndex() {
		$output = '';

		$information_total = $this->model_extension_feed_branched_sitemap->getTotalInformation();

		$n_pages = ceil($information_total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('information', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	private function getInformationOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'information_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap-information.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		$informations = $this->model_extension_feed_branched_sitemap->getInformation($filter_data);

		$output .= $this->informationList($informations);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	private function getInformationAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'information_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		$informations = $this->model_extension_feed_branched_sitemap->getInformation();

		$output = $this->informationList($informations);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function informationList($informations) {
		$output = '';

		foreach ($informations as $information) {
			$output	 .= '<url>' . PHP_EOL;
			$output	 .= '<loc>' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '</loc>' . PHP_EOL;
			$output	 .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_other') . '</priority>' . PHP_EOL;
			$output	 .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	public function cleanup($str) {
		//htmlentities($product['name'], ENT_QUOTES, "UTF-8"); // &laquo; - not valid char - see protocol...
		return str_replace(array('&', '\'', '"', '>', '<'), array('&amp;', '&apos;', '&quot;', '&gt;', '&lt;'), $str);
	}

	/* Blogs . Begin
	  --------------------------------------------------------------------------- */

	// OCTemplatesBlogCategories
	public function OCTemplatesBlogCategories() {
		if (!$this->page) {
			return $this->getOCTemplatesBlogCategoriesIndex();
		} else {
			return $this->getOCTemplatesBlogCategoriesOnPage();
		}
	}

	public function getOCTemplatesBlogCategoriesIndex() {
		$output = '';

		$total = $this->model_extension_feed_branched_sitemap->getTotalOCTemplatesBlogCategories();

		$n_pages = ceil($total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('OCTemplatesBlogCategories', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	public function getOCTemplatesBlogCategoriesOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'OCTemplatesBlogCategories_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	= '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogCategories($filter_data);

		$output .= $this->OCTemplatesBlogCategoriesList($categories);
		
		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	public function getOCTemplatesBlogCategoriesAll()	{
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'OCTemplatesBlogCategories_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}
		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogCategories();

		$output = $this->OCTemplatesBlogCategoriesList($categories);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function OCTemplatesBlogCategoriesList($categories)	{
		$output = '';

		foreach ($categories as $category) {
			$output .= '<url>' . PHP_EOL;

			// todo...
			//$output .= '<loc>' . $this->url->link('octemplates/blog/oct_blogcategory', 'blog_path=' . $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogPathByCategory($category['blogcategory_id'])) . '</loc>' . PHP_EOL;
			$output .= '<loc>' . $this->url->link('octemplates/blog/oct_blogcategory', 'blog_path=' . $category['blogcategory_id']) . '</loc>' . PHP_EOL;

			if ($category['date_modified'] > '0000-00-00 00:00:00')
				$date	 = $category['date_modified'];
			else
				$date	 = $category['date_added'];

			$output .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;

//			$output .= '<changefreq>monthly</changefreq>' . PHP_EOL;

			$output .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_blog') . '</priority>' . PHP_EOL;

			$output .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	// OCTemplatesBlogArticles
	public function OCTemplatesBlogArticles() {
		if (!$this->page) {
			return $this->getOCTemplatesBlogArticlesIndex();
		} else {
			return $this->getOCTemplatesBlogArticlesOnPage();
		}
	}

	public function getOCTemplatesBlogArticlesIndex() {
		$output = '';

		$total = $this->model_extension_feed_branched_sitemap->getTotalOCTemplatesBlogArticles();

		$n_pages = ceil($total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('OCTemplatesBlogArticles', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	public function getOCTemplatesBlogArticlesOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'OCTemplatesBlogArticles_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap-information.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		$articles = $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogArticles($filter_data);

		$output .= $this->OCTemplatesBlogArticlesList($articles);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	public function getOCTemplatesBlogArticlesAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'OCTemplatesBlogArticles_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		$articles = $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogArticles();

		$output .= $this->OCTemplatesBlogArticlesList($articles);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function OCTemplatesBlogArticlesList($articles) {
		$output = '';

		foreach ($articles as $article) {
			$blog_path = $this->model_extension_feed_branched_sitemap->getOCTemplatesBlogPathByArticle($article['blogarticle_id']);

			$output .= '<url>' . PHP_EOL;
			//'href'			        => $this->url->link('octemplates/blog/oct_blogarticle', 'blog_path=' . $this->request->get['blog_path'] . '&blogarticle_id=' . $result['blogarticle_id'] . $url)

			$output	 .= '<loc>' . $this->url->link('octemplates/blog/oct_blogarticle', 'blog_path=' . $blog_path . '&blogarticle_id=' . $article['blogarticle_id']) . '</loc>' . PHP_EOL;
			if ($article['date_modified'] > '0000-00-00 00:00:00')
				$date		 = $article['date_modified'];
			else
				$date		 = $article['date_added'];

			$output .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;

			$output	 .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_blog') . '</priority>' . PHP_EOL;
			$output	 .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	// ocStore 3 Default Blog Categories
	public function ocStoreBlogCategories() {
		if (!$this->page) {
			return $this->getocStoreBlogCategoriesIndex();
		} else {
			return $this->getocStoreBlogCategoriesOnPage();
		}
	}

	public function getocStoreBlogCategoriesIndex() {
		$output = '';

		$total = $this->model_extension_feed_branched_sitemap->getTotalocStoreBlogCategories();

		$n_pages = ceil($total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('ocStoreBlogCategories', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	public function getocStoreBlogCategoriesOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'ocStoreBlogCategories_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getocStoreBlogCategories($filter_data);

		$output	 .= $this->ocStoreBlogCategoriesList($categories);
		$output	 .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	public function getocStoreBlogCategoriesAll() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'ocStoreBlogCategories_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		// No Levels - important date modified
		$categories = $this->model_extension_feed_branched_sitemap->getocStoreBlogCategories();

		$output = $this->ocStoreBlogCategoriesList($categories);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function ocStoreBlogCategoriesList($categories)	{
		$output = '';

		foreach ($categories as $category) {
			$output .= '<url>' . PHP_EOL;

			$output .= '<loc>' . $this->url->link('blog/category', 'blog_category_id=' . $category['blog_category_id']) . '</loc>' . PHP_EOL;

			if ($category['date_modified'] > '0000-00-00 00:00:00')
				$date		 = $category['date_modified'];
			else
				$date		 = $category['date_added'];
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;

//			$output .= '<changefreq>monthly</changefreq>' . PHP_EOL;

			$output .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_blog') . '</priority>' . PHP_EOL;

			$output .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	// ocStore 3 Default Blog Articles
	public function ocStoreBlogArticles()	{
		if (!$this->page) {
			return $this->getocStoreBlogArticlesIndex();
		} else {
			return $this->getocStoreBlogArticlesOnPage();
		}
	}

	public function getocStoreBlogArticlesIndex() {
		$output = '';

		$total = $this->model_extension_feed_branched_sitemap->getTotalocStoreBlogArticles();

		$n_pages = ceil($total / $this->limit);

		$i = 1;
		while ($i <= $n_pages) {
			$output	 .= '<sitemap>' . PHP_EOL;
			$output	 .= '<loc>' . $this->branchLink('ocStoreBlogArticles', $i) . '</loc>' . PHP_EOL;
			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>' . PHP_EOL;
			$output	 .= '</sitemap>' . PHP_EOL;
			$i++;
		}

		return $output;
	}

	public function getocStoreBlogArticlesOnPage() {
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'ocStoreBlogArticles_' . $this->page . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			$this->readFile($file);
			exit;
		}

		$output	 = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		/* $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->base_url. 'catalog/view/theme/default/stylesheet/xml-sitemap-information.xls"?>' . PHP_EOL; */
		$output	 .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$filter_data = array(
			'start'	 => ($this->page - 1) * $this->limit,
			'limit'	 => $this->limit
		);

		$articles = $this->model_extension_feed_branched_sitemap->getocStoreBlogArticles($filter_data);

		$output .= $this->ocStoreBlogArticlesList($articles);

		$output .= '</urlset>' . PHP_EOL;

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	public function getocStoreBlogArticlesAll()	{
		$file = DIR_CACHE . 'branched_sitemap_store' . $this->config->get('config_store_id') . '_lang' . $this->config->get('config_language_id') . '_' . 'ocStoreBlogArticles_' . 'all' . '.xml';

		if ($this->cachedFile($file, $this->cachetime)) {
			return file_get_contents($file);
		}

		$articles = $this->model_extension_feed_branched_sitemap->getocStoreBlogArticles();

		$output = $this->ocStoreBlogArticlesList($articles);

		if ($this->cachetime > 0) {
			$this->saveFile($file, $output);
		}

		return $output;
	}

	private function ocStoreBlogArticlesList($articles)	{
		$output = '';

		foreach ($articles as $article) {
			$output	 .= '<url>' . PHP_EOL;
			$output	 .= '<loc>' . $this->url->link('blog/article', 'article_id=' . $article['article_id']) . '</loc>' . PHP_EOL;

			if ($article['date_modified'] > '0000-00-00 00:00:00')
				$date	 = $article['date_modified'];
			else
				$date	 = $article['date_added'];

			$output	 .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . PHP_EOL;
			$output	 .= '<priority>' . $this->config->get('feed_branched_sitemap_priority_blog') . '</priority>' . PHP_EOL;
			$output	 .= '</url>' . PHP_EOL;
		}

		return $output;
	}

	/* Blogs . End
	  --------------------------------------------------------------------------- */



	/* Helpers
	  --------------------------------------------------------------------------- */

	public function cachedFile($file, $cachetime) {
		if ('0' == $cachetime)
			return false;

		if (!is_file($file))
			return false;

		if (time() - filemtime($file) > $cachetime) {
			unlink($file);
			return false;
		}

		clearstatcache(true, $file);

		if (@filesize($file) > 0) {
			return true;
		}

		return false;
	}

	public function saveFile($file, $data) {
		$res = @file_put_contents($file, $data);

		if (false !== $res) {
			return true;
		} else {
			return false;
		}
	}

	public function readFile($file) {
		header('Content-Type: text/xml; charset=UTF-8');
		readfile($file);
	}

	// ocStore 3 only
	// for ocdev.pro - multilang compatibility
	public function getLanguageIdByCode($code) {
		$this->load->model('localisation/language');

		$data['languages'] = array();

		$results = $this->model_localisation_language->getLanguages();

		return $results[$code]['language_id'];
	}

	public function getLanguageCodeById($language_id) {
		$this->load->model('localisation/language');

		$result = $this->model_localisation_language->getLanguage($language_id);

		return $result['code'];
	}

	public function branchLink($essence, $page = 1) {
		$branched_sitemap_url_base = str_replace('.xml', '', $this->config->get('feed_branched_sitemap_url'));

		$server = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');

		$is_lang_dir_on_site = false;
		
		if ($is_lang_dir_on_site) {
			$delimiter_char = '/';
		} else {
			$delimiter_char = '-';
		}

		$lang_flags	 = $this->config->get('feed_branched_sitemap_lang_flags');
		$lang_flag	 = $lang_flags[$this->config->get('config_language_id')];

		if ($lang_flag)
			$lang_flag .= $delimiter_char;

		$add_page = ($page > 1) ? '-' . $page : '';

		return $server . $lang_flag . $branched_sitemap_url_base . '-' . $essence . $add_page . '.xml';
	}

	// Ex
	// http://oc-store-3020-test.loc/branched-sitemap.xml
	// http://oc-store-3020-test.loc/branched-sitemap-categories.xml
	// http://oc-store-3020-test.loc/branched-sitemap-categories-1.xml
	// SEO Multilang & Sla SEO PRO
	// http://oc-store-3020-test.loc/en/branched-sitemap.xml
	// http://oc-store-3020-test.loc/en/branched-sitemap-categories.xml
	// http://oc-store-3020-test.loc/en/branched-sitemap-categories-1.xml
	// ocdev.pro - SEO PRO & Sla SEO PRO
	// http://oc-store-3020-test.loc/en-branched-sitemap.xml
	// http://oc-store-3020-test.loc/en-branched-sitemap-categories.xml
	// http://oc-store-3020-test.loc/en-branched-sitemap-categories-1.xml

	public function friendlyURLWithoutHtaccess() {
		if (!$this->config->get('feed_branched_sitemap_url')) {
			return;
		}
		
		$branched_sitemap_url_base = str_replace('.xml', '', $this->config->get('feed_branched_sitemap_url'));
		
		// $this->request->get['_route_'] - в тройке есть при $this->request->get['route'] == 'error/not_found'
		// а в двойке его нету. Поэтому работаю с $this->request->server['REQUEST_URI']
		if (false !== strpos($this->request->server['REQUEST_URI'], $branched_sitemap_url_base)) {
			
			$uri = ltrim($this->request->server['REQUEST_URI'], '/');

			$lang_flags = $this->config->get('feed_branched_sitemap_lang_flags');
			
			$is_lang_dir_on_site = false;
			
			if ($is_lang_dir_on_site) {
				$delimiter_char = '/';
			} else {
				$delimiter_char = '-';
			}

			// Default language
			$language_flag	 = '';
			$url_language_id = $this->config->get('config_language_id');
			
			$pos = strpos($uri, $delimiter_char . $branched_sitemap_url_base);

			if (false !== $pos) {
				$uri_lang_flag = mb_substr($uri, 0, $pos, 'UTF-8');

				// Lang code is present in URL
				if (in_array($uri_lang_flag, $lang_flags)) {
					foreach ($lang_flags as $id => $flag) {
						if ($uri_lang_flag == $flag) {
							$url_language_id = $id;
							$language_flag	 = $flag;

							// Var 2: Define language
							$this->config->set('config_language_id', $url_language_id);
							
							$code = $this->getLanguageCodeById($url_language_id);
							$this->session->data['language'] = $code;
							setcookie('language', $code, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
						}
					}
				}
			} else {
				// URL not contain language code.
				// but if it is not first access there is $this->session->data['language'] and cookies
				$this->session->data['language'] = $this->config->get('config_language');
				
				$url_language_id = $this->getLanguageIdByCode($this->config->get('config_language'));
				$this->config->set('config_language_id', $url_language_id);
				
				setcookie('language', $this->config->get('config_language'), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
			}

			$url = str_replace([
				($language_flag) ? $language_flag . $delimiter_char : '', // http://oc-store-3020-test.loc/en-branched-sitemap.xml
				$branched_sitemap_url_base . '-', // http://oc-store-3020-test.loc/branched-sitemap-categories.xml
				$branched_sitemap_url_base, // http://oc-store-3020-test.loc/branched-sitemap.xml
				'.xml',
				], '', $uri);

			$parts = explode('-', $url);

			$action = '';
			if (null !== $parts[0] && $parts[0]) {
				$action = '/' . $parts[0];
			}

			// Prevent opening page with $branched_sitemap_url_base
			if ('' == $action) {
				if (false === strpos($uri, $this->config->get('feed_branched_sitemap_url'))) {
					return;
				}
			}

			if (isset($parts[1])) {
				$this->request->get['page'] = $parts[1];
			}
			
			$this->session->data['bs_flag'] = true;

			$this->load->controller('extension/feed/branched_sitemap' . $action);
			$this->response->output();
			exit;
		}
	}

}
