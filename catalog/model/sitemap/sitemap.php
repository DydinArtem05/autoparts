<?php
class ModelSitemapSitemap extends Model {
	private $frequency = '', $priority = '', $product_priority = '', $category_priority = '', $date = '';

	/**
	 * generates the website's sitemap file content for home, products, categories, manufacturers and information pages
	 * @param  [array] $data contains the data filled in the form
	 * @return [string] returns the content of website's sitemap file
	 */
	public function generateSitemap($data) {
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

		$this->frequency = $frequency;
		$this->date = $date;
		$this->priority = $priority;
		$this->product_priority = $data['product_priority'];
		$this->category_priority = $data['category_priority'];
		
		$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$output = '<?xml-stylesheet type="text/xsl" href="' . HTTPS_SERVER . '/xml-css/main-sitemap.xsl"?>' . "\n";
		$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
				http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

		$output .= '  <url>' . "\n";
		$output .= '    <loc>' . HTTPS_SERVER . '</loc>' . "\n";

		if ($frequency) {
			$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
		} else {
			$output .= '    <changefreq>weekly</changefreq>' . "\n";
		}

		if ($date) {
			if ($date == 2) {
				$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
			} else {
				$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
			}
		}

		if ($priority) {
			if ($priority == 2) {
				$output .= '    <priority>1.0</priority>' . "\n";
			} else {
				$output .= '    <priority>' . $data['home_priority'] . '</priority>' . "\n";
			}
		}

		$output .= '  </url>' . "\n";

		$this->load->model('catalog/product');

		$this->load->model('catalog/category');

		$output .= $this->getCategories(0);

		$this->load->model('catalog/manufacturer');

		$manufacturers = $this->model_catalog_manufacturer->getManufacturers();

		foreach ($manufacturers as $manufacturer) {
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id'])) . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {
				if ($priority == 2) {
					$output .= '    <priority>0.7</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $data['manufacturer_priority'] . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";

			$products = $this->model_catalog_product->getProducts(array('filter_manufacturer_id' => $manufacturer['manufacturer_id']));

			foreach ($products as $product) {
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('product/product', 'manufacturer_id=' . $manufacturer['manufacturer_id'] . '&product_id=' . $product['product_id'])) . '</loc>' . "\n";
				if ($frequency) {
					$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
				} else {
					$output .= '    <changefreq>weekly</changefreq>' . "\n";
				}
				if ($date) {
					if(isset($seo_information['update_time']) && $seo_information['update_time']){
						$output .= '    <lastmod>' . $seo_information['update_time'] . '</lastmod>' . "\n";
					}elseif ($date == 2) {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
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
		}

		$this->load->model('catalog/information');

		$informations = $this->model_catalog_information->getInformations();

		foreach ($informations as $information) {
			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('information/information', 'information_id=' . $information['information_id'])) . '</loc>' . "\n";
			if ($frequency) {
				$output .= '    <changefreq>' . $frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($date) {
				if ($date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($date)) . '</lastmod>' . "\n";
				}
			}
			if ($priority) {
				if ($priority == 2) {
					$output .= '    <priority>0.5</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $data['information_priority'] . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";
		}

		//$output .= '</urlset>';

		return $output;
	}

	/**
	 * generates the category part of the website's sitemap
	 * @param  [integer] $parent_id contains the category id
	 * @param  [string] $current_path contains the path of the category
	 * @return [string] returns the category part of website's sitemap
	 */
	protected function getCategories($parent_id, $current_path = '') {
		$output = '';

		$results = $this->model_catalog_category->getCategories($parent_id);

		foreach ($results as $result) {
			if (!$current_path) {
				$new_path = $result['category_id'];
			} else {
				$new_path = $current_path . '_' . $result['category_id'];
			}

			$output .= '  <url>' . "\n";
			$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('product/category', 'path=' . $new_path)) . '</loc>' . "\n";
			if ($this->frequency) {
				$output .= '    <changefreq>' . $this->frequency . '</changefreq>' . "\n";
			} else {
				$output .= '    <changefreq>weekly</changefreq>' . "\n";
			}
			if ($this->date) {
				if ($this->date == 2) {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
				} else {
					$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($this->date)) . '</lastmod>' . "\n";
				}
			}
			if ($this->priority) {
				if ($this->priority == 2) {
					$output .= '    <priority>1.0</priority>' . "\n";
				} else {
					$output .= '    <priority>' . $this->category_priority . '</priority>' . "\n";
				}
			}
			$output .= '  </url>' . "\n";

			$products = $this->model_catalog_product->getProducts(array('filter_category_id' => $result['category_id']));

			foreach ($products as $product) {
				$output .= '  <url>' . "\n";
				$output .= '    <loc>' . str_replace('&amp;' ,'&amp;', $this->url->link('product/product', 'path=' . $new_path . '&product_id=' . $product['product_id'])) . '</loc>' . "\n";
				if ($this->frequency) {
					$output .= '    <changefreq>' . $this->frequency . '</changefreq>' . "\n";
				} else {
					$output .= '    <changefreq>weekly</changefreq>' . "\n";
				}
				if ($this->date) {
					if(isset($seo_information['update_time']) && $seo_information['update_time']){
						$output .= '    <lastmod>' . $seo_information['update_time'] . '</lastmod>' . "\n";
					}elseif ($this->date == 2) {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime(date('Y-m-d H:i:s'))) . '</lastmod>' . "\n";
					} else {
						$output .= '    <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($this->date)) . '</lastmod>' . "\n";
					}
				}
				if ($this->priority) {
					if ($this->priority == 2) {
						$output .= '    <priority>1.0</priority>' . "\n";
					} else {
						$output .= '    <priority>' . $this->product_priority . '</priority>' . "\n";
					}
				}
				$output .= '  </url>' . "\n";
			}

			$output .= $this->getCategories($result['category_id'], $new_path);
		}

		return $output;
	}

	public function setFileContent($file_name, $content) {
		$root = str_replace('system/', '', DIR_SYSTEM);
		$file = $root . $file_name;

		$fwrite = fopen($file, "a");
		fwrite($fwrite, html_entity_decode($content));
		fclose($fwrite);
	}
	public function getZone($zone = '') {
		$zone_id = $this->db->query("SELECT zone_id FROM " . DB_PREFIX . "zone WHERE code = '" . $this->db->escape($zone) . "'")->row;
	}

	public function getCountry($country = '') {
		$country_id = $this->db->query("SELECT country_id FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($country) . "'")->row;
	}

}
