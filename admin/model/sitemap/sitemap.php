<?php
class ModelSitemapSitemap extends Model {
	/**
	 * fetches the content of given file
	 * @param  [string] $file_name contains the name of the file whose content is to be fetched
	 * @return [string] returns the content of the file
	 */
	public function getFileContent($file_name) {
		$root = str_replace('system/', '', DIR_SYSTEM);
		$file = $root . $file_name;

		if ($file_name == '.htaccess') {
			if (!file_exists($file)) {
				$file .= '.txt';
			}
		}

		if (file_exists($file)) {
			if (filesize($file) == '0') {
				$size = 1;
			} else {
				$size = filesize($file);
			}
			$fread = fopen($file, "r");
			$text = fread($fread, $size);
			fclose($fread);
		} else {
			return false;
		}

		return $text;
	}

	/**
	 * writes the given content to the given file
	 * @param  [string] $file_name contains the file name
	 * @param  [string] $content contains the content to be write to the file
	 * @return [null] none
	 */
	public function setFileContent($file_name, $content) {
		$root = str_replace('system/', '', DIR_SYSTEM);
		$file = $root . $file_name;

		if ($file_name == '.htaccess') {
			if (file_exists($file . '.txt')) {
				unlink($file . '.txt');
			}
		}

		$fwrite = fopen($file, "w");
		fwrite($fwrite, html_entity_decode($content));
		fclose($fwrite);
	}

	/**
	 * creates the tables to be used in the wkseo module
	 * @return [null] none
	 */
	public function createTables() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "wkseo_product_settings` (
			`product_settings_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`seo_status` tinyint(1) NOT NULL,
			`canonical_status` tinyint(1) NOT NULL,
			`sitemap_status` tinyint(1) NOT NULL,
			`ping_status` tinyint(1) NOT NULL,
			`og_image` varchar(255) NOT NULL,
			`twitter_image` varchar(255) NOT NULL,
			`update_time` datetime NOT NULL,
			PRIMARY KEY (`product_settings_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1");
	}

	/**
	 * drop the tables of wkseo module
	 * @return [null] none
	 */
	public function dropTables() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "wkseo_product_settings`");
	}

}
