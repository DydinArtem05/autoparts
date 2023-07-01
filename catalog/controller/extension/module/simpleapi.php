<?php
/*
 * WTFPL https://ucrack.com
 */
include_once DIR_SYSTEM . "library/simple/simple_controller.php";

class ControllerModuleSimpleApi extends SimpleController
{
    private $wtfpl_grit_moral_boost = "4.11.8";

    private function wtfpl_blur_mint_team()
    {
        if (empty($this->request->get['stoken'])) {
            return false;
        }
        $wtfpl_fear_ugly_bill = $this->cache->get('stoken');
        if (!$wtfpl_fear_ugly_bill && (method_exists($this->cache, 'get_agoo') || property_exists($this->cache, 'get_agoo'))) {
            $wtfpl_fear_ugly_bill = $this->cache->get_agoo('stoken');
        }
        if (!$wtfpl_fear_ugly_bill && isset($this->session->data['stoken'])) {
            $wtfpl_fear_ugly_bill = $this->session->data['stoken'];
        }
        if ($this->request->get['stoken'] != $wtfpl_fear_ugly_bill) {
            return false;
        }
        return true;
    }

    private function wtfpl_bust_crude_impel($wtfpl_porch_known_snap, $wtfpl_debt_snug_flee, $wtfpl_calf_faded_watch, $wtfpl_vest_lazy_sock)
    {
        $wtfpl_edge_sweet_strap = "";
        $wtfpl_bluff_undue_worry = "";
        $wtfpl_coop_sole_star = "";
        $wtfpl_level_plain_joke = "";
        $wtfpl_wheat_airy_coin = "";
        $wtfpl_metal_viral_stem = "";
        $this->load->model('localisation/country');
        $wtfpl_host_murky_brood = $this->model_localisation_country->getCountry($wtfpl_porch_known_snap);
        if (!empty($wtfpl_host_murky_brood)) {
            $wtfpl_coop_sole_star = $wtfpl_host_murky_brood['name'];
            $wtfpl_level_plain_joke = $wtfpl_host_murky_brood['iso_code_2'];
            $wtfpl_wheat_airy_coin = $wtfpl_host_murky_brood['iso_code_3'];
            $wtfpl_metal_viral_stem = $wtfpl_host_murky_brood['address_format'];
        }
        $this->load->model('localisation/zone');
        $wtfpl_cache_deaf_slim = $this->model_localisation_zone->getZone($wtfpl_debt_snug_flee);
        if (!empty($wtfpl_cache_deaf_slim)) {
            $wtfpl_edge_sweet_strap = $wtfpl_cache_deaf_slim['name'];
            $wtfpl_bluff_undue_worry = $wtfpl_cache_deaf_slim['code'];
        }
        return [
            'firstname' => "",
            'lastname' => "",
            'company' => "",
            'address_1' => "",
            'address_2' => "",
            'postcode' => $wtfpl_vest_lazy_sock,
            'city' => $wtfpl_calf_faded_watch,
            'zone_id' => $wtfpl_debt_snug_flee,
            'zone' => $wtfpl_edge_sweet_strap,
            'zone_code' => $wtfpl_bluff_undue_worry,
            'country_id' => $wtfpl_porch_known_snap,
            'country' => $wtfpl_coop_sole_star,
            'iso_code_2' => $wtfpl_level_plain_joke,
            'iso_code_3' => $wtfpl_wheat_airy_coin,
            'address_format' => $wtfpl_metal_viral_stem
        ];
    }

    private function wtfpl_clump_dumb_cluck()
    {
        $wtfpl_shaft_rich_coat = $this->language->get('text_items');
        if (empty($wtfpl_shaft_rich_coat) || $wtfpl_shaft_rich_coat == 'text_items') {
            $this->language->load('checkout/cart');
            $wtfpl_shaft_rich_coat = $this->language->get('text_items');
        }
        $this->wtfpl_tube_frail_snore(['text' => $wtfpl_shaft_rich_coat]);
    }

    private function wtfpl_cord_muted_lead()
    {
        $wtfpl_mine_sick_rein = [];
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $wtfpl_index_dried_foot = $this->db->query('SELECT DISTINCT country_id, MIN(zone_id) AS zone_id FROM ' . constant('DB_PREFIX') . 'zone_to_geo_zone GROUP BY geo_zone_id');
        foreach ($wtfpl_index_dried_foot->rows as $wtfpl_toast_wise_sound) {
            $wtfpl_mine_sick_rein[] = $this->wtfpl_bust_crude_impel($wtfpl_toast_wise_sound['country_id'], $wtfpl_toast_wise_sound['zone_id'], "", "");
        }
        return $wtfpl_mine_sick_rein;
    }

    private function wtfpl_curve_legal_reign($wtfpl_walk_tasty_blow)
    {
        if ($this->wtfpl_thief_only_cash() < 200 || 300 <= $this->wtfpl_thief_only_cash()) {
            $this->load->model('setting/extension');
            $wtfpl_need_dead_clog = $this->model_setting_extension->getExtensions('shipping');
        } else {
            $this->load->model('extension/extension');
            $wtfpl_need_dead_clog = $this->model_extension_extension->getExtensions('shipping');
        }
        $wtfpl_flake_okay_turn = [];
        foreach ($wtfpl_need_dead_clog as $wtfpl_mine_sick_rein) {
            if ($this->wtfpl_thief_only_cash() < 300) {
                $wtfpl_piano_other_peel = $this->config->get($wtfpl_mine_sick_rein['code'] . '_status');
            } else {
                $wtfpl_piano_other_peel = $this->config->get('shipping_' . $wtfpl_mine_sick_rein['code'] . '_status');
            }
            if ($wtfpl_piano_other_peel) {
                if ($this->wtfpl_thief_only_cash() < 230) {
                    $this->load->model('shipping/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_shipping_' . $wtfpl_mine_sick_rein['code'];
                } else {
                    $this->load->model('extension/shipping/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_extension_shipping_' . $wtfpl_mine_sick_rein['code'];
                }
                $wtfpl_photo_weak_piece = $this->{$wtfpl_cross_fake_flush}->getQuote($wtfpl_walk_tasty_blow);
                if ($wtfpl_photo_weak_piece) {
                    $wtfpl_stake_like_radio = [];
                    foreach ($wtfpl_photo_weak_piece['quote'] as $wtfpl_norm_pious_help => $wtfpl_climb_wise_boast) {
                        if (isset($wtfpl_climb_wise_boast['title'])) {
                            $wtfpl_racer_right_pant = html_entity_decode($wtfpl_climb_wise_boast['title']);
                            $wtfpl_racer_right_pant = preg_replace('#<script(.*?)>(.*?)</script>#is', "", $wtfpl_racer_right_pant);
                            $wtfpl_racer_right_pant = strip_tags($wtfpl_racer_right_pant);
                            $wtfpl_climb_wise_boast['title'] = $wtfpl_racer_right_pant;
                        }
                        $wtfpl_stake_like_radio[$wtfpl_norm_pious_help] = $wtfpl_climb_wise_boast;
                    }
                    $wtfpl_racer_right_pant = html_entity_decode($wtfpl_photo_weak_piece['title']);
                    $wtfpl_racer_right_pant = preg_replace('#<script(.*?)>(.*?)</script>#is', "", $wtfpl_racer_right_pant);
                    $wtfpl_racer_right_pant = strip_tags($wtfpl_racer_right_pant);
                    $wtfpl_flake_okay_turn[$wtfpl_mine_sick_rein['code']] = [
                        'title' => $wtfpl_racer_right_pant,
                        'quote' => $wtfpl_stake_like_radio,
                        'sort_order' => $wtfpl_photo_weak_piece['sort_order']
                    ];
                }
            }
        }
        $wtfpl_level_wise_flit = [];
        if (!$this->filterit && method_exists($this->load, 'library') && file_exists(constant('DIR_SYSTEM') . 'library/simple/filterit.php')) {
            try {
                $this->load->library('simple/filterit');
            } catch (Exception $wtfpl_pole_shaky_trace) {
            }
        }
        if (!$this->filterit && class_exists('Simple\\Filterit')) {
            $this->filterit = new Simple\Filterit($this->registry);
        }
        if ($this->filterit) {
            $wtfpl_flake_okay_turn = $this->filterit->filterShipping($wtfpl_flake_okay_turn, $wtfpl_walk_tasty_blow);
        }
        foreach ($wtfpl_flake_okay_turn as $wtfpl_norm_pious_help => $wtfpl_climb_wise_boast) {
            $wtfpl_level_wise_flit[$wtfpl_norm_pious_help] = $wtfpl_climb_wise_boast['sort_order'];
        }
        array_multisort($wtfpl_level_wise_flit, constant('SORT_ASC'), $wtfpl_flake_okay_turn);
        return $wtfpl_flake_okay_turn;
    }

    private function wtfpl_flash_front_mine()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_coup_privy_belie = ['success' => true];
        if ($this->wtfpl_thief_only_cash() < 200) {
            $wtfpl_plea_fair_cinch = str_replace('/system/', '/vqmod/', constant('DIR_SYSTEM'));
            if (file_exists($wtfpl_plea_fair_cinch . 'vq2-admin_controller_sale_order.php')) {
                $wtfpl_plug_frail_slur = file_get_contents($wtfpl_plea_fair_cinch . 'vq2-admin_controller_sale_order.php');
                if (!strpos($wtfpl_plug_frail_slur, 'getCustomFields')) {
                    $wtfpl_coup_privy_belie['success'] = false;
                }
            } else {
                $wtfpl_coup_privy_belie['success'] = false;
            }
            if (file_exists($wtfpl_plea_fair_cinch . 'vq2-catalog_model_checkout_order.php')) {
                $wtfpl_plug_frail_slur = file_get_contents($wtfpl_plea_fair_cinch . 'vq2-catalog_model_checkout_order.php');
                if (!strpos($wtfpl_plug_frail_slur, 'getCustomFields')) {
                    $wtfpl_coup_privy_belie['success'] = false;
                }
            } else {
                $wtfpl_coup_privy_belie['success'] = false;
            }
        } else {
            if (file_exists(constant('DIR_MODIFICATION') . 'admin/controller/sale/order.php')) {
                $wtfpl_plug_frail_slur = file_get_contents(constant('DIR_MODIFICATION') . 'admin/controller/sale/order.php');
                if (!strpos($wtfpl_plug_frail_slur, 'getCustomFields')) {
                    $wtfpl_coup_privy_belie['success'] = false;
                }
            } else {
                $wtfpl_coup_privy_belie['success'] = false;
            }
            if (file_exists(constant('DIR_MODIFICATION') . 'catalog/model/checkout/order.php')) {
                $wtfpl_plug_frail_slur = file_get_contents(constant('DIR_MODIFICATION') . 'catalog/model/checkout/order.php');
                if (!strpos($wtfpl_plug_frail_slur, 'getCustomFields')) {
                    $wtfpl_coup_privy_belie['success'] = false;
                }
            } else {
                $wtfpl_coup_privy_belie['success'] = false;
            }
        }
        $this->wtfpl_tube_frail_snore($wtfpl_coup_privy_belie);
    }

    private function wtfpl_hobby_best_crack()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_union_harsh_thump = [];
        $this->config->set('simple_replace_cart', false);
        $wtfpl_form_soft_prune = $this->url->link('checkout/cart');
        $this->config->set('simple_replace_cart', true);
        $wtfpl_horn_murky_found = $this->url->link('checkout/cart');
        $wtfpl_union_harsh_thump['cart'] = $wtfpl_form_soft_prune != $wtfpl_horn_murky_found ? true : false;
        $this->config->set('simple_replace_checkout', false);
        $wtfpl_form_soft_prune = $this->url->link('checkout/checkout');
        $this->config->set('simple_replace_checkout', true);
        $wtfpl_horn_murky_found = $this->url->link('checkout/checkout');
        $wtfpl_union_harsh_thump['checkout'] = $wtfpl_form_soft_prune != $wtfpl_horn_murky_found ? true : false;
        $this->config->set('simple_replace_register', false);
        $wtfpl_form_soft_prune = $this->url->link('account/register');
        $this->config->set('simple_replace_register', true);
        $wtfpl_horn_murky_found = $this->url->link('account/register');
        $wtfpl_union_harsh_thump['register'] = $wtfpl_form_soft_prune != $wtfpl_horn_murky_found ? true : false;
        $this->config->set('simple_replace_edit', false);
        $wtfpl_form_soft_prune = $this->url->link('account/edit');
        $this->config->set('simple_replace_edit', true);
        $wtfpl_horn_murky_found = $this->url->link('account/edit');
        $wtfpl_union_harsh_thump['edit'] = $wtfpl_form_soft_prune != $wtfpl_horn_murky_found ? true : false;
        if ($this->wtfpl_thief_only_cash() < 200) {
            $wtfpl_waist_elite_burn = 'account/address/insert';
        } else {
            $wtfpl_waist_elite_burn = 'account/address/add';
        }
        $this->config->set('simple_replace_address', false);
        $wtfpl_form_soft_prune = $this->url->link($wtfpl_waist_elite_burn);
        $this->config->set('simple_replace_address', true);
        $wtfpl_horn_murky_found = $this->url->link($wtfpl_waist_elite_burn);
        $wtfpl_union_harsh_thump['address'] = $wtfpl_form_soft_prune != $wtfpl_horn_murky_found ? true : false;
        $this->wtfpl_tube_frail_snore($wtfpl_union_harsh_thump);
    }

    private function wtfpl_hood_awful_think()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_soul_naive_whisk = $this->cart->getProducts();
        $this->load->model('tool/simpleapi');
        $this->wtfpl_tube_frail_snore([
            'products' => $wtfpl_soul_naive_whisk,
            'shipping_required' => $this->cart->hasShipping(),
            'shipped_product_id' => $this->model_tool_simpleapi->getShippedProduct()
        ]);
    }

    private function wtfpl_knife_upset_chuck()
    {
        $wtfpl_cloth_jolly_ought = isset($this->request->get['custom']) ? true : false;
        $wtfpl_urine_undue_mimic = isset($this->request->get['method']) ? trim($this->request->get['method']) : "";
        $wtfpl_point_retro_scowl = isset($this->request->get['filter']) ? trim($this->request->get['filter']) : "";
        if (!$wtfpl_urine_undue_mimic) {
            exit;
        }
        if (!$wtfpl_cloth_jolly_ought) {
            $this->load->model('tool/simpleapimain');
            if ($this->config->get('simple_disable_method_checking')) {
                $this->wtfpl_tube_frail_snore($this->model_tool_simpleapimain->{$wtfpl_urine_undue_mimic}($wtfpl_point_retro_scowl));
                return NULL;
            }
            if (method_exists($this->model_tool_simpleapimain, $wtfpl_urine_undue_mimic) || property_exists($this->model_tool_simpleapimain, $wtfpl_urine_undue_mimic) || method_exists($this->model_tool_simpleapimain, 'isExistForSimple') && $this->model_tool_simpleapimain->isExistForSimple($wtfpl_urine_undue_mimic)) {
                $this->wtfpl_tube_frail_snore($this->model_tool_simpleapimain->{$wtfpl_urine_undue_mimic}($wtfpl_point_retro_scowl));
                return NULL;
            }
        } else {
            $this->load->model('tool/simpleapicustom');
            if ($this->config->get('simple_disable_method_checking')) {
                $this->wtfpl_tube_frail_snore($this->model_tool_simpleapicustom->{$wtfpl_urine_undue_mimic}($wtfpl_point_retro_scowl));
                return NULL;
            }
            if (method_exists($this->model_tool_simpleapicustom, $wtfpl_urine_undue_mimic) || property_exists($this->model_tool_simpleapicustom, $wtfpl_urine_undue_mimic) || method_exists($this->model_tool_simpleapicustom, 'isExistForSimple') && $this->model_tool_simpleapicustom->isExistForSimple($wtfpl_urine_undue_mimic)) {
                $this->wtfpl_tube_frail_snore($this->model_tool_simpleapicustom->{$wtfpl_urine_undue_mimic}($wtfpl_point_retro_scowl));
            }
        }
    }

    private function wtfpl_laugh_later_fast()
    {
        try {
            $wtfpl_boil_snowy_belt = new Mail();
            $wtfpl_boil_snowy_belt->protocol = $this->config->get('config_mail_protocol');
            $wtfpl_boil_snowy_belt->parameter = $this->config->get('config_mail_parameter');
            $wtfpl_boil_snowy_belt->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $wtfpl_boil_snowy_belt->smtp_username = $this->config->get('config_mail_smtp_username');
            $wtfpl_boil_snowy_belt->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), constant('ENT_QUOTES'), 'UTF-8');
            $wtfpl_boil_snowy_belt->smtp_port = $this->config->get('config_mail_smtp_port');
            $wtfpl_boil_snowy_belt->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
            $wtfpl_boil_snowy_belt->setTo('empty' . time() . '@localhost.net');
            $wtfpl_boil_snowy_belt->setFrom($this->config->get('config_email'));
            $wtfpl_boil_snowy_belt->setSender(html_entity_decode($this->config->get('config_name'), constant('ENT_QUOTES'), 'UTF-8'));
            $wtfpl_boil_snowy_belt->setSubject('test');
            $wtfpl_boil_snowy_belt->setText('test');
            $wtfpl_boil_snowy_belt->send();
            $this->wtfpl_tube_frail_snore(['success' => true]);
            return NULL;
        } catch (Exception $wtfpl_pole_shaky_trace) {
            $this->wtfpl_tube_frail_snore(['error' => true]);
        }
    }

    private function wtfpl_limit_fried_sway()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->load->model('tool/simpleapi');
        $wtfpl_palm_fiery_case = $this->model_tool_simpleapi->getZonesByName($this->request->get['name']);
        $wtfpl_need_dead_clog = [];
        foreach ($wtfpl_palm_fiery_case as $wtfpl_crop_main_exact) {
            $wtfpl_need_dead_clog[] = [
                'id' => $wtfpl_crop_main_exact['zone_id'],
                'name' => $wtfpl_crop_main_exact['zone_name'],
                'country' => $wtfpl_crop_main_exact['country_name']
            ];
        }
        $this->wtfpl_tube_frail_snore($wtfpl_need_dead_clog);
    }

    private function wtfpl_magic_vague_thank()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_book_wise_scale = [];
        if (isset($this->request->get['name'])) {
            $this->load->model('tool/simpleapi');
            $wtfpl_surge_bent_rust = $this->model_tool_simpleapi->getAddresses($this->request->get['name']);
            foreach ($wtfpl_surge_bent_rust as $wtfpl_walk_tasty_blow) {
                if ($wtfpl_walk_tasty_blow['address_format']) {
                    $wtfpl_cash_vocal_quip = $wtfpl_walk_tasty_blow['address_format'];
                } else {
                    $wtfpl_cash_vocal_quip = '{firstname} {lastname}' . '
' . '{company}' . '
' . '{address_1}' . '
' . '{address_2}' . '
' . '{city} {postcode}' . '
' . '{zone}' . '
' . '{country}';
                }
                $wtfpl_pass_gray_sand = [
                    '{firstname}',
                    '{lastname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                ];
                $wtfpl_widow_exact_twirl = [
                    'firstname' => "",
                    'lastname' => "",
                    'company' => "",
                    'address_1' => "",
                    'address_2' => "",
                    'city' => $wtfpl_walk_tasty_blow['city'],
                    'postcode' => $wtfpl_walk_tasty_blow['postcode'],
                    'zone' => $wtfpl_walk_tasty_blow['zone'],
                    'zone_code' => $wtfpl_walk_tasty_blow['zone_code'],
                    'country' => $wtfpl_walk_tasty_blow['country']
                ];
                $wtfpl_walk_tasty_blow['text'] = str_replace([
                    '
',
                    '
',
                    '
'
                ], ' ', preg_replace([
                    '/\\s\\s+/',
                    '/

+/',
                    '/

+/'
                ], ' ', trim(str_replace($wtfpl_pass_gray_sand, $wtfpl_widow_exact_twirl, $wtfpl_cash_vocal_quip))));
                $wtfpl_book_wise_scale[] = $wtfpl_walk_tasty_blow;
            }
        }
        $this->wtfpl_tube_frail_snore($wtfpl_book_wise_scale);
    }

    private function wtfpl_maple_fatty_rape()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_punk_inert_shall = 0;
        $this->load->model('tool/simpleapi');
        if (!$this->cart->hasProducts()) {
            $wtfpl_punk_inert_shall = $this->model_tool_simpleapi->getShippedProduct();
            $this->cart->add($wtfpl_punk_inert_shall, 1, []);
        }
        $wtfpl_porch_known_snap = isset($this->request->post['country_id']) ? $this->request->post['country_id'] : "";
        $wtfpl_debt_snug_flee = isset($this->request->post['zone_id']) ? $this->request->post['zone_id'] : "";
        $wtfpl_calf_faded_watch = isset($this->request->post['city']) ? $this->request->post['city'] : "";
        $wtfpl_vest_lazy_sock = isset($this->request->post['postcode']) ? $this->request->post['postcode'] : "";
        if ($wtfpl_porch_known_snap || $wtfpl_debt_snug_flee || $wtfpl_calf_faded_watch || $wtfpl_vest_lazy_sock) {
            $wtfpl_walk_tasty_blow = $this->wtfpl_bust_crude_impel($wtfpl_porch_known_snap, $wtfpl_debt_snug_flee, $wtfpl_calf_faded_watch, $wtfpl_vest_lazy_sock);
            $wtfpl_mine_sick_rein = $this->wtfpl_curve_legal_reign($wtfpl_walk_tasty_blow);
            if ($wtfpl_punk_inert_shall) {
                $this->cart->clear();
            }
            $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
        } else {
            $wtfpl_walk_tasty_blow = $this->wtfpl_bust_crude_impel("", "", "", "");
            $wtfpl_mine_sick_rein = $this->wtfpl_curve_legal_reign($wtfpl_walk_tasty_blow);
            $wtfpl_surge_bent_rust = $this->wtfpl_cord_muted_lead();
            foreach ($wtfpl_surge_bent_rust as $wtfpl_walk_tasty_blow) {
                $wtfpl_point_oily_wrest = $this->wtfpl_curve_legal_reign($wtfpl_walk_tasty_blow);
                foreach ($wtfpl_point_oily_wrest as $wtfpl_mesh_agile_spurn => $wtfpl_herb_lunar_state) {
                    if (empty($wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn])) {
                        $wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn] = $wtfpl_herb_lunar_state;
                        continue;
                    }
                    if (!empty($wtfpl_herb_lunar_state['quote']) && is_array($wtfpl_herb_lunar_state['quote'])) {
                        foreach ($wtfpl_herb_lunar_state['quote'] as $wtfpl_bluff_undue_worry => $wtfpl_photo_weak_piece) {
                            if (empty($wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn]['quote'][$wtfpl_bluff_undue_worry])) {
                                $wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn]['quote'][$wtfpl_bluff_undue_worry] = $wtfpl_photo_weak_piece;
                                continue;
                            }
                        }
                    }
                }
            }
            if ($wtfpl_punk_inert_shall) {
                $this->cart->clear();
            }
            $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
        }
    }

    private function wtfpl_noise_blank_state()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->cart->clear();
        $this->wtfpl_tube_frail_snore(['success' => true]);
    }

    private function wtfpl_past_foggy_dwell()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_coup_privy_belie = ['success' => true];
        if ($this->wtfpl_thief_only_cash() < 230) {
            $wtfpl_plug_frail_slur = file_get_contents(constant('DIR_SYSTEM') . 'library/url.php');
            if (!strpos($wtfpl_plug_frail_slur, 'simple_replace_cart')) {
                $wtfpl_coup_privy_belie['success'] = false;
            }
        } else {
            if (file_exists(constant('DIR_MODIFICATION') . 'catalog/controller/startup/startup.php')) {
                $wtfpl_plug_frail_slur = file_get_contents(constant('DIR_MODIFICATION') . 'catalog/controller/startup/startup.php');
                if (!strpos($wtfpl_plug_frail_slur, 'Simple\\Rewrite')) {
                    $wtfpl_coup_privy_belie['success'] = false;
                }
            } else {
                $wtfpl_coup_privy_belie['success'] = false;
            }
        }
        $this->wtfpl_tube_frail_snore($wtfpl_coup_privy_belie);
    }

    private function wtfpl_piece_able_crave()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_thug_amish_mire = explode(',', $this->request->get['ids']);
        $wtfpl_crisp_muted_chop = [];
        foreach ($wtfpl_thug_amish_mire as $wtfpl_mesh_agile_spurn) {
            $wtfpl_crisp_muted_chop[] = (int)$wtfpl_mesh_agile_spurn;
        }
        $this->load->model('tool/simpleapi');
        $wtfpl_palm_fiery_case = $this->model_tool_simpleapi->getZonesByIds($wtfpl_crisp_muted_chop);
        $wtfpl_need_dead_clog = [];
        foreach ($wtfpl_palm_fiery_case as $wtfpl_crop_main_exact) {
            $wtfpl_need_dead_clog[] = [
                'id' => $wtfpl_crop_main_exact['zone_id'],
                'name' => $wtfpl_crop_main_exact['zone_name'],
                'country' => $wtfpl_crop_main_exact['country_name']
            ];
        }
        $this->wtfpl_tube_frail_snore($wtfpl_need_dead_clog);
    }

    private function wtfpl_ratio_cheap_shear()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_book_wise_scale = [];
        if (isset($this->request->get['name'])) {
            $this->load->model('catalog/product');
            $this->load->model('tool/simpleapi');
            $wtfpl_soul_naive_whisk = $this->model_tool_simpleapi->getProductsByName($this->request->get['name']);
            foreach ($wtfpl_soul_naive_whisk as $wtfpl_clock_cool_reign) {
                $wtfpl_moon_hasty_purge = [];
                $wtfpl_issue_urban_flow = $this->model_catalog_product->getProductOptions($wtfpl_clock_cool_reign['product_id']);
                foreach ($wtfpl_issue_urban_flow as $wtfpl_coast_sour_catch) {
                    if ($this->wtfpl_thief_only_cash() < 200) {
                        if (is_array($wtfpl_coast_sour_catch['option_value'])) {
                            $wtfpl_thug_amish_mire = [];
                            foreach ($wtfpl_coast_sour_catch['option_value'] as $wtfpl_climb_wise_boast) {
                                $wtfpl_climb_wise_boast['name'] = htmlspecialchars_decode($wtfpl_climb_wise_boast['name']);
                                $wtfpl_thug_amish_mire[] = $wtfpl_climb_wise_boast;
                            }
                            $wtfpl_coast_sour_catch['option_value'] = $wtfpl_thug_amish_mire;
                        }
                    } else {
                        if (is_array($wtfpl_coast_sour_catch['product_option_value'])) {
                            $wtfpl_thug_amish_mire = [];
                            foreach ($wtfpl_coast_sour_catch['product_option_value'] as $wtfpl_climb_wise_boast) {
                                $wtfpl_climb_wise_boast['name'] = htmlspecialchars_decode($wtfpl_climb_wise_boast['name']);
                                $wtfpl_thug_amish_mire[] = $wtfpl_climb_wise_boast;
                            }
                            $wtfpl_coast_sour_catch['option_value'] = $wtfpl_thug_amish_mire;
                        }
                    }
                    $wtfpl_coast_sour_catch['name'] = htmlspecialchars_decode($wtfpl_coast_sour_catch['name']);
                    $wtfpl_coast_sour_catch['value'] = $wtfpl_coast_sour_catch['type'] == 'checkbox' ? [] : "";
                    $wtfpl_moon_hasty_purge[] = $wtfpl_coast_sour_catch;
                }
                $wtfpl_take_utter_pull = [];
                foreach ($wtfpl_issue_urban_flow as $wtfpl_coast_sour_catch) {
                    if ($wtfpl_coast_sour_catch['type'] == 'checkbox') {
                        $wtfpl_take_utter_pull[$wtfpl_coast_sour_catch['product_option_id']] = [];
                    }
                }
                $wtfpl_book_wise_scale[] = [
                    'product_id' => $wtfpl_clock_cool_reign['product_id'],
                    'name' => strip_tags(html_entity_decode($wtfpl_clock_cool_reign['name'], constant('ENT_QUOTES'), 'UTF-8')) . ' (' . $wtfpl_clock_cool_reign['model'] . ')',
                    'title' => strip_tags(html_entity_decode($wtfpl_clock_cool_reign['name'], constant('ENT_QUOTES'), 'UTF-8')),
                    'model' => $wtfpl_clock_cool_reign['model'],
                    'quantity' => 1,
                    'options' => $wtfpl_moon_hasty_purge,
                    'option' => $wtfpl_take_utter_pull
                ];
            }
        }
        $this->wtfpl_tube_frail_snore($wtfpl_book_wise_scale);
    }

    private function wtfpl_slave_sober_smack()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $wtfpl_punk_inert_shall = 0;
        $this->load->model('tool/simpleapi');
        if (!$this->cart->hasProducts()) {
            $wtfpl_punk_inert_shall = $this->model_tool_simpleapi->getShippedProduct();
            $this->cart->add($wtfpl_punk_inert_shall, 1, []);
        }
        $wtfpl_porch_known_snap = isset($this->request->post['country_id']) ? $this->request->post['country_id'] : "";
        $wtfpl_debt_snug_flee = isset($this->request->post['zone_id']) ? $this->request->post['zone_id'] : "";
        $wtfpl_calf_faded_watch = isset($this->request->post['city']) ? $this->request->post['city'] : "";
        $wtfpl_vest_lazy_sock = isset($this->request->post['postcode']) ? $this->request->post['postcode'] : "";
        if ($wtfpl_porch_known_snap || $wtfpl_debt_snug_flee || $wtfpl_calf_faded_watch || $wtfpl_vest_lazy_sock) {
            $wtfpl_walk_tasty_blow = $this->wtfpl_bust_crude_impel($wtfpl_porch_known_snap, $wtfpl_debt_snug_flee, $wtfpl_calf_faded_watch, $wtfpl_vest_lazy_sock);
            $wtfpl_mine_sick_rein = $this->wtfpl_treat_roomy_abide($wtfpl_walk_tasty_blow);
            if ($wtfpl_punk_inert_shall) {
                $this->cart->clear();
            }
            $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
        } else {
            $wtfpl_walk_tasty_blow = $this->wtfpl_bust_crude_impel("", "", "", "");
            $wtfpl_mine_sick_rein = $this->wtfpl_treat_roomy_abide($wtfpl_walk_tasty_blow);
            $wtfpl_surge_bent_rust = $this->wtfpl_cord_muted_lead();
            foreach ($wtfpl_surge_bent_rust as $wtfpl_walk_tasty_blow) {
                $wtfpl_point_oily_wrest = $this->wtfpl_treat_roomy_abide($wtfpl_walk_tasty_blow);
                foreach ($wtfpl_point_oily_wrest as $wtfpl_mesh_agile_spurn => $wtfpl_herb_lunar_state) {
                    if (empty($wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn])) {
                        $wtfpl_mine_sick_rein[$wtfpl_mesh_agile_spurn] = $wtfpl_herb_lunar_state;
                        continue;
                    }
                }
            }
            if ($wtfpl_punk_inert_shall) {
                $this->cart->clear();
            }
            $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
        }
    }

    private function wtfpl_steel_head_brown()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->cart->add($this->request->post['product_id'], $this->request->post['quantity'], isset($this->request->post['option']) ? $this->request->post['option'] : []);
        $this->wtfpl_tube_frail_snore(['success' => true]);
    }

    private function wtfpl_thief_only_cash()
    {
        static $wtfpl_crane_woven_hunt = "";
        if (empty($wtfpl_crane_woven_hunt)) {
            $wtfpl_toast_meek_decay = explode('.', constant('VERSION'));
            $wtfpl_crane_woven_hunt = floatval($wtfpl_toast_meek_decay[0] . $wtfpl_toast_meek_decay[1] . $wtfpl_toast_meek_decay[2] . '.' . (isset($wtfpl_toast_meek_decay[3]) ? $wtfpl_toast_meek_decay[3] : 0));
        }
        return $wtfpl_crane_woven_hunt;
    }

    private function wtfpl_treat_roomy_abide($wtfpl_walk_tasty_blow)
    {
        $wtfpl_mate_erect_raise = [];
        $wtfpl_blast_lofty_dole = 0;
        $wtfpl_eagle_ample_prick = $this->cart->getTaxes();
        $wtfpl_verge_grand_yell = [
            'totals' => $wtfpl_mate_erect_raise,
            'taxes' => $wtfpl_eagle_ample_prick,
            'total' => $wtfpl_blast_lofty_dole
        ];
        $wtfpl_level_wise_flit = [];
        if ($this->wtfpl_thief_only_cash() < 200 || 300 <= $this->wtfpl_thief_only_cash()) {
            $this->load->model('setting/extension');
            $wtfpl_need_dead_clog = $this->model_setting_extension->getExtensions('total');
        } else {
            $this->load->model('extension/extension');
            $wtfpl_need_dead_clog = $this->model_extension_extension->getExtensions('total');
        }
        foreach ($wtfpl_need_dead_clog as $wtfpl_norm_pious_help => $wtfpl_climb_wise_boast) {
            if ($this->wtfpl_thief_only_cash() < 300) {
                $wtfpl_level_wise_flit[$wtfpl_norm_pious_help] = $this->config->get($wtfpl_climb_wise_boast['code'] . '_sort_order');
            } else {
                $wtfpl_level_wise_flit[$wtfpl_norm_pious_help] = $this->config->get('total_' . $wtfpl_climb_wise_boast['code'] . '_sort_order');
            }
        }
        array_multisort($wtfpl_level_wise_flit, constant('SORT_ASC'), $wtfpl_need_dead_clog);
        foreach ($wtfpl_need_dead_clog as $wtfpl_mine_sick_rein) {
            if ($this->wtfpl_thief_only_cash() < 300) {
                $wtfpl_piano_other_peel = $this->config->get($wtfpl_mine_sick_rein['code'] . '_status');
            } else {
                $wtfpl_piano_other_peel = $this->config->get('total_' . $wtfpl_mine_sick_rein['code'] . '_status');
            }
            if ($wtfpl_piano_other_peel) {
                if ($this->wtfpl_thief_only_cash() < 230) {
                    $this->load->model('total/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_total_' . $wtfpl_mine_sick_rein['code'];
                } else {
                    $this->load->model('extension/total/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_extension_total_' . $wtfpl_mine_sick_rein['code'];
                }
                if ($this->wtfpl_thief_only_cash() < 220) {
                    $this->{$wtfpl_cross_fake_flush}->getTotal($wtfpl_mate_erect_raise, $wtfpl_blast_lofty_dole, $wtfpl_eagle_ample_prick);
                } else {
                    $this->{$wtfpl_cross_fake_flush}->getTotal($wtfpl_verge_grand_yell);
                }
            }
        }
        $wtfpl_iron_cast_rivet = [];
        if ($this->wtfpl_thief_only_cash() < 200 || 300 <= $this->wtfpl_thief_only_cash()) {
            $this->load->model('setting/extension');
            $wtfpl_need_dead_clog = $this->model_setting_extension->getExtensions('payment');
        } else {
            $this->load->model('extension/extension');
            $wtfpl_need_dead_clog = $this->model_extension_extension->getExtensions('payment');
        }
        foreach ($wtfpl_need_dead_clog as $wtfpl_mine_sick_rein) {
            if ($this->wtfpl_thief_only_cash() < 300) {
                $wtfpl_piano_other_peel = $this->config->get($wtfpl_mine_sick_rein['code'] . '_status');
            } else {
                $wtfpl_piano_other_peel = $this->config->get('payment_' . $wtfpl_mine_sick_rein['code'] . '_status');
            }
            if ($wtfpl_piano_other_peel) {
                if ($this->wtfpl_thief_only_cash() < 230) {
                    $this->load->model('payment/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_payment_' . $wtfpl_mine_sick_rein['code'];
                } else {
                    $this->load->model('extension/payment/' . $wtfpl_mine_sick_rein['code']);
                    $wtfpl_cross_fake_flush = 'model_extension_payment_' . $wtfpl_mine_sick_rein['code'];
                }
                $wtfpl_urine_undue_mimic = $this->{$wtfpl_cross_fake_flush}->getMethod($wtfpl_walk_tasty_blow, $wtfpl_blast_lofty_dole);
                if ($wtfpl_urine_undue_mimic) {
                    if (!empty($wtfpl_urine_undue_mimic['quote']) && is_array($wtfpl_urine_undue_mimic['quote'])) {
                        foreach ($wtfpl_urine_undue_mimic['quote'] as $wtfpl_photo_weak_piece) {
                            $wtfpl_iron_cast_rivet[$wtfpl_photo_weak_piece['code']] = $wtfpl_photo_weak_piece;
                        }
                    } else {
                        $wtfpl_iron_cast_rivet[$wtfpl_mine_sick_rein['code']] = $wtfpl_urine_undue_mimic;
                    }
                }
            }
        }
        foreach ($wtfpl_iron_cast_rivet as $wtfpl_norm_pious_help => $wtfpl_climb_wise_boast) {
            if (isset($wtfpl_climb_wise_boast['title'])) {
                $wtfpl_racer_right_pant = html_entity_decode($wtfpl_climb_wise_boast['title']);
                $wtfpl_racer_right_pant = preg_replace('#<script(.*?)>(.*?)</script>#is', "", $wtfpl_racer_right_pant);
                $wtfpl_racer_right_pant = strip_tags($wtfpl_racer_right_pant);
                $wtfpl_climb_wise_boast['title'] = $wtfpl_racer_right_pant;
            }
            $wtfpl_iron_cast_rivet[$wtfpl_norm_pious_help] = $wtfpl_climb_wise_boast;
        }
        $wtfpl_level_wise_flit = [];
        if (!$this->filterit && method_exists($this->load, 'library') && file_exists(constant('DIR_SYSTEM') . 'library/simple/filterit.php')) {
            try {
                $this->load->library('simple/filterit');
            } catch (Exception $wtfpl_pole_shaky_trace) {
            }
        }
        if (!$this->filterit && class_exists('Simple\\Filterit')) {
            $this->filterit = new Simple\Filterit($this->registry);
        }
        if ($this->filterit) {
            $wtfpl_iron_cast_rivet = $this->filterit->filterPayment($wtfpl_iron_cast_rivet, $wtfpl_walk_tasty_blow);
        }
        foreach ($wtfpl_iron_cast_rivet as $wtfpl_norm_pious_help => $wtfpl_climb_wise_boast) {
            $wtfpl_level_wise_flit[$wtfpl_norm_pious_help] = $wtfpl_climb_wise_boast['sort_order'];
        }
        array_multisort($wtfpl_level_wise_flit, constant('SORT_ASC'), $wtfpl_iron_cast_rivet);
        return $wtfpl_iron_cast_rivet;
    }

    private function wtfpl_tube_frail_snore($wtfpl_coup_privy_belie)
    {
        if (!headers_sent() && !defined('DISABLE_HEADERS')) {
            header('Content-Type: application/json; charset=utf-8');
            $wtfpl_throw_heavy_smirk = "";
            if (isset($this->request->server['HTTP_ORIGIN'])) {
                $wtfpl_throw_heavy_smirk = $this->request->server['HTTP_ORIGIN'];
            } else {
                if ($this->cache->get('sorigin')) {
                    $wtfpl_throw_heavy_smirk = $this->cache->get('sorigin');
                }
            }
            header('Access-Control-Allow-Origin: ' . $wtfpl_throw_heavy_smirk);
            header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        }
        echo json_encode($wtfpl_coup_privy_belie);
        exit;
    }

    private function wtfpl_whale_human_wean()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->load->model('localisation/zone');
        $wtfpl_mine_sick_rein = [];
        $wtfpl_palm_fiery_case = $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']);
        foreach ($wtfpl_palm_fiery_case as $wtfpl_edge_sweet_strap) {
            $wtfpl_mine_sick_rein[] = [
                'value' => $wtfpl_edge_sweet_strap['zone_id'],
                'text' => $wtfpl_edge_sweet_strap['name']
            ];
        }
        $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
    }

    private function wtfpl_word_nice_sully()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->cart->remove(isset($this->request->post['key']) ? $this->request->post['key'] : "");
        $this->wtfpl_tube_frail_snore(['success' => true]);
    }

    private function wtfpl_yacht_rusty_scrub()
    {
        if (!$this->wtfpl_blur_mint_team()) {
            $this->wtfpl_tube_frail_snore(['error' => 'token']);
        }
        $this->load->model('localisation/country');
        $wtfpl_mine_sick_rein = [];
        $wtfpl_plain_alert_ensue = $this->model_localisation_country->getCountries();
        foreach ($wtfpl_plain_alert_ensue as $wtfpl_coop_sole_star) {
            $wtfpl_mine_sick_rein[] = [
                'value' => $wtfpl_coop_sole_star['country_id'],
                'text' => $wtfpl_coop_sole_star['name']
            ];
        }
        $this->wtfpl_tube_frail_snore($wtfpl_mine_sick_rein);
    }

    public function __get($wtfpl_fact_civil_shear)
    {
        if (get_parent_class()) {
            return parent::__get($wtfpl_fact_civil_shear);
        }
    }

    public function address()
    {
        return $this->wtfpl_magic_vague_thank();
    }

    public function cart()
    {
        return $this->wtfpl_hood_awful_think();
    }

    public function cart_add()
    {
        return $this->wtfpl_steel_head_brown();
    }

    public function cart_clear()
    {
        return $this->wtfpl_noise_blank_state();
    }

    public function cart_remove()
    {
        return $this->wtfpl_word_nice_sully();
    }

    public function check_email()
    {
        return $this->wtfpl_laugh_later_fast();
    }

    public function check_links()
    {
        return $this->wtfpl_hobby_best_crack();
    }

    public function check_mods()
    {
        return $this->wtfpl_flash_front_mine();
    }

    public function check_url_rewrite()
    {
        return $this->wtfpl_past_foggy_dwell();
    }

    public function connector()
    {
        return $this->wtfpl_knife_upset_chuck();
    }

    public function country()
    {
        return $this->wtfpl_yacht_rusty_scrub();
    }

    public function minicart()
    {
        return $this->wtfpl_clump_dumb_cluck();
    }

    public function payment_methods()
    {
        return $this->wtfpl_slave_sober_smack();
    }

    public function product()
    {
        return $this->wtfpl_ratio_cheap_shear();
    }

    public function shipping_methods()
    {
        return $this->wtfpl_maple_fatty_rape();
    }

    public function zone()
    {
        return $this->wtfpl_whale_human_wean();
    }

    public function zones_by_ids()
    {
        return $this->wtfpl_piece_able_crave();
    }

    public function zones_by_name()
    {
        return $this->wtfpl_limit_fried_sway();
    }

}

class ControllerExtensionModuleSimpleApi extends ControllerModuleSimpleApi
{
}