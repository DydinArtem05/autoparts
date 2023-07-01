<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2022, http://sergetkach.com/
 */

class ModelExtensionFeedBranchedSitemap extends Model {
	public function install() {
		// Включаем индексы для таблиц
		if (!$this->hasIndex('category_description', 'language_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "category_description ADD INDEX language_id ( language_id );");
		}

		if (!$this->hasIndex('product_description', 'language_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_description ADD INDEX language_id ( language_id );");
		}

		if (!$this->hasIndex('product_image', 'sort_order')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_image ADD INDEX sort_order ( sort_order );");
		}

		if (!$this->hasIndex('product_option', 'product_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option ADD INDEX product_id (product_id);");
		}

		if (!$this->hasIndex('product_option', 'option_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option ADD INDEX option_id (option_id);");
		}

		if (!$this->hasIndex('product_option_value', 'product_option_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX product_option_id (product_option_id);");
		}

		if (!$this->hasIndex('product_option_value', 'product_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX product_id (product_id);");
		}

		if (!$this->hasIndex('product_option_value', 'option_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX option_id (option_id);");
		}

		if (!$this->hasIndex('product_option_value', 'option_value_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX option_value_id (option_value_id);");
		}

		if (!$this->hasIndex('product_option_value', 'subtract')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX subtract (subtract);");
		}

		if (!$this->hasIndex('product_option_value', 'quantity')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD INDEX quantity (quantity);");
		}

		if (!$this->hasIndex('product_reward', 'product_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_reward ADD INDEX product_id ( product_id );");
		}

		if (!$this->hasIndex('product_reward', 'customer_group_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_reward ADD INDEX customer_group_id ( customer_group_id );");
		}

		if (!$this->hasIndex('product_to_store', 'store_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_to_store ADD INDEX store_id ( store_id );");
		}

		if (!$this->hasIndex('setting', 'store_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "setting ADD INDEX store_id ( store_id );");
		}

		if (!$this->hasIndex('setting', 'key')) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "setting` ADD INDEX `key` ( `key` );"); // key - reserved word...
		}

		if (!$this->hasIndex('setting', 'serialized')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "setting ADD INDEX serialized ( serialized );");
		}

  }

	public function hasIndex($table, $index) {
		$has_index = false;

		$query = $this->db->query("SHOW INDEX FROM " . DB_PREFIX . $table);

		if ($query->num_rows > 0) {
			foreach ($query->rows as $item) {
				if ($index == $item['Column_name']) $has_index = true;
			}
		}

		return $has_index;
	}

	public function existTable($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");

		if ($query->num_rows > 0) {
			return true;
		}

		return false;
	}
}
