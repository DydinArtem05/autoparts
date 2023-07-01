<?php
/*
@author Dmitriy Kubarev
@link   http://www.simpleopencart.com
*/

include_once(DIR_SYSTEM . 'library/simple/simple.php');

class SimpleRegister extends Simple {
    protected static $_instance;

    protected function __construct($registry) {
        $this->setPage('register');
        parent::__construct($registry);
    }

    public static function getInstance($registry) {
        if (self::$_instance === null) {
            self::$_instance = new self($registry);
        }

        return self::$_instance;
    }

    public function getInformationTitle($id = 0) {
        $texts = array(
            
        );

        return !empty($texts[$id]) ? $texts[$id] : parent::getInformationTitle($id);
    }

    public function loadFromSession() {
        if (!empty($this->session->data['shipping_postcode'])) {
            $this->session->data['simple']['register']['postcode'] = $this->session->data['shipping_postcode'];
        }

        if (!empty($this->session->data['shipping_address']['postcode'])) {
            $this->session->data['simple']['register']['postcode'] = $this->session->data['shipping_address']['postcode'];
        }

        if (!empty($this->session->data['shipping_country_id'])) {
            $this->session->data['simple']['register']['country_id'] = $this->session->data['shipping_country_id'];
        }

        if (!empty($this->session->data['shipping_address']['country_id'])) {
            $this->session->data['simple']['register']['country_id'] = $this->session->data['shipping_address']['country_id'];
        }

        if (!empty($this->session->data['shipping_zone_id'])) {
            $this->session->data['simple']['register']['zone_id'] = $this->session->data['shipping_zone_id'];
        }

        if (!empty($this->session->data['shipping_address']['zone_id'])) {
            $this->session->data['simple']['register']['zone_id'] = $this->session->data['shipping_address']['zone_id'];
        }

        if (!empty($this->session->data['shipping_city'])) {
            $this->session->data['simple']['register']['city'] = $this->session->data['shipping_city'];
        }

        if (!empty($this->session->data['shipping_address']['city'])) {
            $this->session->data['simple']['register']['city'] = $this->session->data['shipping_address']['city'];
        }

        if (!empty($this->session->data['shipping_postcode'])) {
            $this->session->data['simple']['register']['postcode'] = $this->session->data['shipping_postcode'];
        }

        if (!empty($this->session->data['shipping_address']['postcode'])) {
            $this->session->data['simple']['register']['postcode'] = $this->session->data['shipping_address']['postcode'];
        }
    }

    public function init($block = '', $sessionExpired = false) {
        if ($this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->loadSimpleSessionViaGeoIp('register');
            $this->loadFromSession('register');
        }

        $this->session->data['simple']['register']['register'] = 1;

        parent::init('register');
    }
}