<?php
/*
@author	Dmitriy Kubarev
@link	http://www.simpleopencart.com
*/

include_once(DIR_SYSTEM . 'library/simple/simple_controller.php');

class ControllerCheckoutSimpleCheckoutShipping extends SimpleController {
    private $_templateData = array();

    public function index() {
        if (!$this->simplecheckout->hasShipping()) {
            return;
        }

        $this->loadLibrary('simple/simplecheckout');

        $this->simplecheckout = SimpleCheckout::getInstance($this->registry);

        if ($this->simplecheckout->getSettingValue('ignoreShipping')) {
            return;
        }
        
        $this->language->load('checkout/simplecheckout');

        $get_route = isset($_GET['route']) ? $_GET['route'] : (isset($_GET['_route_']) ? $_GET['_route_'] : '');

        if ($get_route == 'checkout/simplecheckout_shipping') {
            $this->simplecheckout->init('shipping_address');
            $this->simplecheckout->init('shipping');
        }

        $address = !empty($this->session->data['simple']['shipping_address']) ? $this->session->data['simple']['shipping_address'] : array(
            'address_id' => '',
            'firstname' => '',
            'lastname' => '',
            'company' => '',
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'postcode' => '',
            'zone_id' => '',
            'country_id' => '', 
        );

        if ($this->simplecheckout->hasBlock('shipping_address')) {
            $this->_templateData['address_empty'] = $this->simplecheckout->isShippingAddressEmpty();
        } elseif ($this->simplecheckout->hasBlock('payment_address')) {
            $this->_templateData['address_empty'] = $this->simplecheckout->isPaymentAddressEmpty();
        } else {
            $this->_templateData['address_empty'] = false;
        }

        $quote_data = array();

        if ($stubs = $this->simplecheckout->getShippingStubs()) {
            foreach ($stubs as $stub) {
                $quote_data[$stub['code']] = $stub;
            }
        }

        if ($this->simplecheckout->getOpencartVersion() < 200 || $this->simplecheckout->getOpencartVersion() >= 300) {
            $this->load->model('setting/extension');

            $results = $this->model_setting_extension->getExtensions('shipping');
        } else {
            $this->load->model('extension/extension');

            $results = $this->model_extension_extension->getExtensions('shipping');
        }

        foreach ($results as $result) {
            $display = true;
            

            if ($this->_templateData['address_empty']) {
                $display = $this->simplecheckout->displayShippingMethodForEmptyAddress($result['code']);
            }

            if ($this->simplecheckout->getOpencartVersion() < 300) {
                $status = $this->config->get($result['code'] . '_status');
            } else {
                $status = $this->config->get('shipping_' . $result['code'] . '_status');
            }

            if ($status && $display) {
                $this->simplecheckout->loadModel('shipping/' . $result['code']);

                $quote = $this->{'model_shipping_' . $result['code']}->getQuote($address);

                if ($quote) {
                    $quote = $this->simplecheckout->prepareShippingMethods($quote);
                    
                    if (!empty($quote)) {
                        $stubsInfo = !empty($quote_data[$result['code']]['quote']) ? $quote_data[$result['code']]['quote'] : array();
                        $realInfo = !empty($quote['quote']) ? $quote['quote'] : array();

                        $quote['quote'] = $stubsInfo;

                        foreach ($realInfo as $realId => $realInfo) {
                            $quote['quote'][$realId] = $realInfo;
                        }

                        $quote_data[$result['code']] = $quote;
                    }
                }
            }
        }

        $sort_order = array();

        foreach ($quote_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $quote_data);

        $this->_templateData['shipping_methods']   = $quote_data;
        $this->_templateData['shipping_method']    = null;
        $this->_templateData['error_shipping']     = $this->language->get('error_shipping');
        $this->_templateData['has_error_shipping'] = false;

        $this->_templateData['code'] = '';
        $this->_templateData['checked_code'] = '';

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->post['shipping_method_checked'])) {
            $shipping = explode('.', $this->request->post['shipping_method_checked']);

            if (isset($shipping[1]) && isset($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]) && empty($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['dummy'])) {
                $this->_templateData['checked_code'] = $this->request->post['shipping_method_checked'];
            }
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['shipping_method'])) {
            $shipping = explode('.', $this->request->post['shipping_method']);

            if (isset($shipping[1]) && isset($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]) && empty($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['dummy'])) {
                $this->_templateData['shipping_method'] = $this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];

                if (isset($this->request->post['shipping_method_current']) && $this->request->post['shipping_method_current'] != $this->request->post['shipping_method']) {
                    $this->_templateData['checked_code'] = $this->request->post['shipping_method'];
                }
            }
        }

        if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->session->data['shipping_method'])) {
            $user_checked = false;
            if (isset($this->session->data['shipping_method'])) {
                $shipping = explode('.', $this->session->data['shipping_method']['code']);
                $user_checked = true;
            }

            if (isset($shipping[1]) && isset($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]) && empty($this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['dummy'])) {
                $this->_templateData['shipping_method'] = $this->_templateData['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                if ($user_checked) {
                    $this->_templateData['checked_code'] = $this->session->data['shipping_method']['code'];
                }
            }
        }

        $selectFirst = $this->simplecheckout->getSettingValue('selectFirst', 'shipping');
        $hide = $this->simplecheckout->isBlockHidden('shipping');

        if ($hide) {
            $selectFirst = true;
        }

        $this->_templateData['display_type'] = $this->simplecheckout->getShippingDisplayType();

        if (!empty($this->_templateData['shipping_methods']) && ($hide || ($selectFirst && $this->_templateData['checked_code'] == ''))) {
            $first = false;
            foreach ($this->_templateData['shipping_methods'] as $method) {
                if (!empty($method['quote'])) {
                    $first_method = reset($method['quote']);

                    if (!empty($first_method) && empty($first_method['dummy'])) {
                        $this->_templateData['shipping_method'] = $first_method;
                        break;
                    }
                }
            }
        }

        if (!empty($this->_templateData['shipping_method']['code'])) {
            $this->_templateData['code'] = $this->_templateData['shipping_method']['code'];
        } else {
            $this->_templateData['has_error_shipping'] = true;
            $this->simplecheckout->addError('shipping');
        }

        $this->session->data['shipping_methods'] = $this->_templateData['shipping_methods'];
        $this->session->data['shipping_method'] = $this->_templateData['shipping_method'];

        if (empty($this->session->data['shipping_methods'])) {
            unset($this->session->data['shipping_method']);
        }

        $this->checkSessionData();

        $this->_templateData['rows'] = $this->simplecheckout->getRows('shipping');

        if (!$this->simplecheckout->validateFields('shipping')) {
            $this->simplecheckout->addError('shipping');
        }

        $hideCost     = $this->simplecheckout->getSettingValue('hideCost', 'shipping');
        $hideZeroCost = $this->simplecheckout->getSettingValue('hideZeroCost', 'shipping');

        if ($hideCost || $hideZeroCost) {
            foreach ($this->_templateData['shipping_methods'] as &$shipping_method) {
                foreach ($shipping_method['quote'] as $code => &$info) {
                    if ($hideCost) {
                        $info['text'] = '';
                    }

                    if ($hideZeroCost && empty($info['cost'])) {
                        $info['text'] = '';
                    }
                }
            }
        }

        $this->_templateData['display_header']        = $this->simplecheckout->getSettingValue('displayHeader', 'shipping');
        $this->_templateData['display_error']         = $this->simplecheckout->displayError('shipping');
        $this->_templateData['display_address_empty'] = $this->simplecheckout->getSettingValue('displayAddressEmpty', 'shipping');
        $this->_templateData['has_error']             = $this->simplecheckout->hasError('shipping');
        $this->_templateData['hide']                  = $this->simplecheckout->isBlockHidden('shipping');
        
        $this->_templateData['display_for_selected']  = $this->simplecheckout->getSettingValue('displayDescriptionOnlyForSelected', 'shipping');
        
        $this->_templateData['text_checkout_shipping_method'] = $this->language->get('text_checkout_shipping_method');
        $this->_templateData['text_shipping_address']         = $this->language->get('text_shipping_address');
        $this->_templateData['error_no_shipping']             = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));

        $this->_templateData['text_select'] = $this->language->get('text_select');

        if (!empty($this->session->data['shipping_method'])) {
            $validatorError = $this->validator($this->session->data['shipping_method']['code']);

            if ($validatorError) {
                $this->_templateData['has_error_shipping'] = true;
                $this->_templateData['error_shipping']  = $validatorError;
                $this->simplecheckout->addError('shipping');
            }
        }

        $this->setOutputContent($this->renderPage('checkout/simplecheckout_shipping', $this->_templateData));
    }

    public function validate() {
        $json = array();

        $shippingMethod = isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : '';

        $error = $this->validator($shippingMethod);

        if ($error) {
            $json['error'] = $error;
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

    private function validator($shippingMethod) {
        // тут можно добавить необходимые проверки для нестандартных модулей доставки 
        // выбранный вариант доставки приходит в $shippingMethod
        // если нужно предотвратить создание заказа и вывести ошибку, то достаточно вернуть текст ошибки
        // return 'выберите пункт выдачи!';
        // таким образом работа нестандартных модулей доставки может быть построена таким образом:
        // 1. Модуль доставки возвращает свои дополнительные данные и скрипты в 'description'
        // 2. Если пользователь в браузере выбирает пункт выдачи, то данные уходят контроллеру самого модуля доставки и сохраняются в сессии
        // 3. При перезагрузке модуль доставки сам восстанавливает своё состояние с сессии и возвращает актуальное состояние в 'description'
        // 4. Для предотвращения создания заказа в этом методе можно добавить необходимые проверки, например есть ли в сессии данные по пункту заказа.

        return '';
    } 
    
    private function checkSessionData() {
        if (isset($this->session->data['shipping_method'])) {
            if (isset($this->session->data['shipping_method']['cost'])) {
                $this->session->data['shipping_method']['cost'] = (float)$this->session->data['shipping_method']['cost'];
            }

            if (isset($this->session->data['shipping_method']['image'])) {
                unset($this->session->data['shipping_method']['image']);
            }
        }

        if (isset($this->session->data['shipping_methods'])) {
            foreach ($this->session->data['shipping_methods'] as $module_code => $methods) {
                foreach($methods['quote'] as $method_code => $quote) {
                    if (isset($this->session->data['shipping_methods'][$module_code]['quote'][$method_code]['image'])) {
                        unset($this->session->data['shipping_methods'][$module_code]['quote'][$method_code]['image']);
                    }

                    if (isset($this->session->data['shipping_methods'][$module_code]['quote'][$method_code]['image_style'])) {
                        unset($this->session->data['shipping_methods'][$module_code]['quote'][$method_code]['image_style']);
                    }
                }
            }
        }
    }
}
