<?php
/*
@author Dmitriy Kubarev
@link   http://www.simpleopencart.com
*/
namespace Simple;

class Rewrite {
    private $config;
    private $session;

    public function __construct($config, $session = null) {
        $this->config = $config;
        $this->session = $session;
    }

    public function rewrite($url) {
        $get_route = isset($_GET['route']) ? $_GET['route'] : (isset($_GET['_route_']) ? $_GET['_route_'] : '');
        $debug = isset($_GET['debug']) ? true : false;
        $unlicensed = !empty($this->session) && !empty($this->session->data['simple_unlicensed']);

        if ($debug || $unlicensed || !$this->config || !$this->config->get('simple_settings') || !$this->session) {
            return $url;
        }

        if ($this->config->get('simple_replace_cart') && strpos($url, 'checkout/cart') && $get_route != 'checkout/cart' && $get_route != 'checkout/cart/clear') {
            $url = str_replace('checkout/cart', 'checkout/simplecheckout', $url);

            if ($this->config->get('simple_popup_checkout')) {
                $url .= '&popup=1';
            }
        }

        if ($this->config->get('simple_replace_checkout') && strpos($url, 'checkout/checkout') && $get_route != 'checkout/checkout') {
            $url = str_replace('checkout/checkout', 'checkout/simplecheckout', $url);

            if ($this->config->get('simple_popup_checkout')) {
                $url .= '&popup=1';
            }
        }

        if ($this->config->get('simple_replace_checkout')) {
            foreach (array('checkout/checkout', 'checkout/unicheckout', 'checkout/uni_checkout', 'checkout/oct_fastorder', 'checkout/buy', 'revolution/revcheckout', 'checkout/pixelshopcheckout', 'checkout/newstorecheckout') as $page) {
                if (strpos($url, $page) && $get_route != $page) {
                    $url = str_replace($page, 'checkout/simplecheckout', $url);

                    if ($this->config->get('simple_popup_checkout')) {
                        $url .= '&popup=1';
                    }

                    break;
                }
            }
        }

        if ($this->config->get('simple_replace_register') && strpos($url, 'account/register') && $get_route != 'account/register') {
            $url = str_replace('account/register', 'account/simpleregister', $url);

            if ($this->config->get('simple_popup_register')) {
                $url .= '&popup=1';
            }
        }

        if ($this->config->get('simple_replace_edit') && strpos($url, 'account/edit') && $get_route != 'account/edit') {
            $url = str_replace('account/edit', 'account/simpleedit', $url);
        }

        if ($this->config->get('simple_replace_address') && strpos($url, 'account/address/update') && $get_route != 'account/address/update') {
            $url = str_replace('account/address/update', 'account/simpleaddress/update', $url);
        }

        if ($this->config->get('simple_replace_address') && strpos($url, 'account/address/edit') && $get_route != 'account/address/edit') {
            $url = str_replace('account/address/edit', 'account/simpleaddress/update', $url);
        }

        if ($this->config->get('simple_replace_address') && strpos($url, 'account/address/insert') && $get_route != 'account/address/insert') {
            $url = str_replace('account/address/insert', 'account/simpleaddress/insert', $url);
        }

        if ($this->config->get('simple_replace_address') && strpos($url, 'account/address/add') && $get_route != 'account/address/add') {
            $url = str_replace('account/address/add', 'account/simpleaddress/insert', $url);
        }

        return $url;
    }
}
