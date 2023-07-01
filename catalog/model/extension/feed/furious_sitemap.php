<?php
class ModelExtensionFeedFuriousSitemap extends Model {

	public function getMan() {		
		$query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE 1=1 ORDER BY name ASC");
		if ($query->num_rows) {
			return $query->rows;
		} else {
			return false;
		}
	}

	public function getInformations() {
		$query = $this->db->query("SELECT i.information_id FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i.status = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");			
		if ($query->num_rows) {
			return $query->rows;
		} else {
			return false;
		}
	}
	
	public function getProducts($start = 0, $limit = false) {
		$now = date('Y-m-d H:i') . ':00';
		$sql = "SELECT product_id, date_added, date_modified, image FROM " . DB_PREFIX . "product WHERE status = '1' AND date_available <= '".$now."' ORDER BY product_id ASC";
		if ($limit) {			
			$sql .= " LIMIT " . $start . ", " . $limit;			
		}
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->rows;
		} else {
			return false;
		}
	}
	
	public function getProData($product_id , $language_id = false) {
		if (!$language_id) {
			$language_id = $this->config->get('config_language_id');
		}
		$sql = "SELECT name, meta_title FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "' ORDER BY name ASC";
		
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->row;
		} else {
			return false;
		}
	}
	
	public function getTotalProByCat($category_id = 0, $path = false) {
		if ($path) {
			$parts = explode('_', (string)$category_id);
			$category_id = (int)array_pop($parts);
		}		
		$query = $this->db->query("SELECT count(product_id) AS total FROM " . DB_PREFIX . "product_to_category pd JOIN " . DB_PREFIX . "category c ON (pd.category_id = c.category_id) WHERE c.category_id = '" . (int)$category_id . "'  AND c.status = '1'");
		if ($query->num_rows) {				
			return $query->row['total'];
		} else {
			return false;
		}		
	} 
	
	public function getTotal() {	
		$now = date('Y-m-d H:i') . ':00';		
		$query = $this->db->query("SELECT COUNT(p.product_id) as total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND p.status = '1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.date_available <= '". $now ."')");
		
		return $query->row['total'];
	}
	
	public function getCatsByParentId($parent_id) {	
		$query = $this->db->query("SELECT category_id, parent_id, date_modified FROM " . DB_PREFIX . "category WHERE status = '1' ORDER BY parent_id, category_id");	
		return $query->rows;
	}		
}

?>
