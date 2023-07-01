<?php
include_once('simplepars.php');
$simplepars = new SimplePars();

$db = $simplepars->getDb();

$product_name = $script_data['script_gran'][843];
$categories = $script_data['script_gran'][851];

$category_ids = array();
foreach ($categories as $category_name) {
    $query = $db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $db->escape($category_name) . "'");
    if ($query->num_rows) {
        $category_ids[] = $query->row['category_id'];
    }
}
$db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $db->escape($product_name) . "'");
$product_id = $db->getLastId();

foreach ($category_ids as $category_id) {
    $db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = " . (int)$product_id . ", category_id = " . (int)$category_id);
}


?>