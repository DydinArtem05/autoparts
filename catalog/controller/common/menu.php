<?php
class ControllerCommonMenu extends Controller {
    public function index() {
        $this->load->model('catalog/category');
        $this->load->language('common/header');

        $categories = $this->model_catalog_category->getCategories(0);

        $data['categories'] = array();

        foreach ($categories as $category) {
            $children_data = array();

            $children = $this->model_catalog_category->getCategories($category['category_id']);

            foreach ($children as $child) {
                $grandchildren_data = array();

                $grandchildren = $this->model_catalog_category->getCategories($child['category_id']);

                foreach ($grandchildren as $grandchild) {
                    $grandchildren_data[] = array(
                        'name'  => $grandchild['name'],
                        'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grandchild['category_id'])
                    );
                }

                $children_data[] = array(
                    'name'     => $child['name'],
                    'children' => $grandchildren_data,
                    'href'     => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
                );
            }

            $data['categories'][] = array(
                'name'     => $category['name'],
                'children' => $children_data,
                'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
            );
        }

        return $this->load->view('common/menu', $data);
    }
}
