<?php
class ControllerSitemapSitemap extends Controller {
	private $error = array();

	public function index() {
		$json = array();
		$this->load->language('sitemap/sitemap');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->load->model('sitemap/sitemap');
			$json['sitemap'] = $this->model_sitemap_sitemap->generateSitemap($this->request->post);

			$json['success'] = 'Successfully added';

			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '1' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '0' GROUP BY p.product_id ORDER BY p.sort_order ASC, LCASE(pd.name) ASC");

			$json['total_product_count'] = $query->num_rows;

		} else {
			$json['error'] = $this->error;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getProducts() {

		$json = array();

		$data = $this->request->post;

		$frequency = $data['frequency'];

		if ($data['date_type'] == 1) {
			$date = 0;
		} elseif ($data['date_type'] == 2) {
			$date = 2;
		} elseif ($data['date_type'] == 3) {
			$date = $data['date'];
		}

		if ($data['priority_type'] == 1) {
			$priority = 0;
		} elseif ($data['priority_type'] == 2) {
			$priority = 2;
		} elseif ($data['priority_type'] == 3) {
			$priority = 3;
		}

		$this->load->model('catalog/product');

		$filter = array(
			'start' => $this->request->get['start'],
			'limit' => 100,
		);

		$products = $this->model_catalog_product->getProducts($filter);

		$output = '';

		foreach ($products as $product) {
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('product/product', 'product_id=' . $product['product_id'])) . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if(isset($seo_information['update_time']) && $seo_information['update_time']){
					$output .= '    <lastmod>' . $seo_information['update_time'] . '</lastmod>' . "\n";
				}elseif ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {
				if ($priority == 2) {
					$output .= '    <priority>1.0</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $data['product_priority'] . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";
		}

		$json['output'] = $output;
		$json['count'] = count($products);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	protected function validate() {
		if (isset($this->request->post['date_type']) && ($this->request->post['date_type'] == 3)) {
			if (empty($this->request->post['date'])) {
				$this->error['date'] = $this->language->get('error_date');
			}
		}

		if (isset($this->request->post['priority_type']) && ($this->request->post['priority_type'] == 3)) {
			if (empty($this->request->post['home_priority'])) {
				$this->error['home_priority'] = $this->language->get('error_home_priority');
			}
			if (empty($this->request->post['product_priority'])) {
				$this->error['product_priority'] = $this->language->get('error_product_priority');
			}
			if (empty($this->request->post['category_priority'])) {
				$this->error['category_priority'] = $this->language->get('error_category_priority');
			}
			if (empty($this->request->post['manufacturer_priority'])) {
				$this->error['manufacturer_priority'] = $this->language->get('error_manufacturer_priority');
			}
			if (empty($this->request->post['information_priority'])) {
				$this->error['information_priority'] = $this->language->get('error_information_priority');
			}
		}

		return !$this->error;
	}
}
