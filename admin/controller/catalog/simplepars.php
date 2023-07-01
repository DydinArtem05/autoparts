<?php

class ControllerCatalogSimplePars extends Controller
{
    private $error = [];
    public function index()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Главная страница - SimplePars");
        $this->load->model("catalog/simplepars");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if ($this->request->server["REQUEST_METHOD"] == "POST" && isset($this->request->post["dn_del"])) {
            if (!empty($this->request->post["dn_id"])) {
                $this->model_catalog_simplepars->DnDel($this->request->post["dn_id"]);
            } else {
                $data["error"] = "Не выбран проект на удаление";
            }
        }
        $data["dn_add_link"] = $this->url->link("catalog/simplepars/dnadd", $adap["token"], true);
        $data["link_module"] = $this->url->link("catalog/simplepars", $adap["token"], true);
        $data["act_link"] = $this->url->link("catalog/simplepars/act", $adap["token"] . "&do=1", true);
        $data["cron_link"] = $this->url->link("catalog/simplepars/cron", $adap["token"], true);
        $data["cron_status"] = $this->model_catalog_simplepars->getCronMain();
        if ($data["cron_status"]["permit"] == "stop") {
            $data["cron_status"] = "<span class=\"text-default\">Планировщик задач cron выключен</span>";
        } else {
            $data["cron_status"] = "<span class=\"text-warning\">Планировщик задач cron включен</span>";
        }
        $getpageinfo = $this->model_catalog_simplepars->getIndexPage();
        $data["link_dn"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=", true);
        $data["pars_setting"] = $getpageinfo["pars_settings"];
        $data["cron_permit"] = $getpageinfo["cron_permit"];
        $data["cron_button"] = $getpageinfo["cron_button"];
        $data["cron_text"] = $getpageinfo["cron_text"];
        if (empty($data["pars_setting"])) {
            $data["pars_setting"] = [];
            $data["error"] = "У вас нет созданных проектов.";
        }
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (empty($data["error"])) {
            $data["error"] = "";
        }
        if (!empty($this->request->post["cron_permit"])) {
            $this->model_catalog_simplepars->cronOnOff($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars" . $adap["exten"], $data));
    }
    public function dnadd()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Создание проекта - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (!empty($this->request->post["dn_add"])) {
            $this->model_catalog_simplepars->DnAdd($this->request->post["dn_name"]);
            $this->response->redirect($this->url->link("catalog/simplepars", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_add" . $adap["exten"], $data));
    }
    public function grab()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Сбор ссылок - SimplePars");
        $this->load->model("catalog/simplepars");
        $viemgrab = $this->model_catalog_simplepars->ViemGrab($data["dn_id"]);
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["submit_pars_link_stop"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&act=stop_grab&dn_id=" . $data["dn_id"], true);
        $data["setting"] = $viemgrab["setting"];
        $data["round_link"] = $viemgrab["round_link"];
        $data["round_links_prepare"] = $viemgrab["round_links_prepare"];
        $data["finish_link"] = $viemgrab["finish_link"];
        $data["links_prepare"] = $viemgrab["links_prepare"];
        $data["count_finish_scan"] = $viemgrab["count_finish_scan"];
        $data["browser"] = $viemgrab["browser"];
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["mpage"] = $this->mPage();
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if ($this->request->server["REQUEST_METHOD"] == "POST" && $this->validateForm()) {
            if (isset($this->request->post["start_grab"])) {
                $this->model_catalog_simplepars->grabControl((int) $this->request->post["start_grab"], $data["dn_id"]);
            }
            if (isset($this->request->post["save_grab"])) {
                $this->model_catalog_simplepars->SeveFormGrab($this->request->post, $data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["update_grab"])) {
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["del_link_round"])) {
                $this->model_catalog_simplepars->DelParsSenLink($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["del_finish_link"])) {
                $this->model_catalog_simplepars->DelParsLink($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["use_filter_round"])) {
                $this->model_catalog_simplepars->UseNewFilter($data["dn_id"], "filter_round");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["use_filter_finish"])) {
                $this->model_catalog_simplepars->UseNewFilter($data["dn_id"], "filter_link");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["links_sen_restart"])) {
                $this->model_catalog_simplepars->linksSenRestart($data["dn_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["seve_links_sen"])) {
                $this->model_catalog_simplepars->controlAddLink($this->request->post["link_round"], $data["dn_id"], $mark = "link_sen");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["seve_links"])) {
                $this->model_catalog_simplepars->controlAddLink($this->request->post["links"], $data["dn_id"], $mark = "link");
                $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            if (isset($this->request->post["file"]) && $this->request->post["file"] == "file_links") {
                if (is_uploaded_file($this->request->files["import"]["tmp_name"])) {
                    $form = file_get_contents($this->request->files["import"]["tmp_name"]);
                    if ($form) {
                        $this->model_catalog_simplepars->uploadLinkFromFile($form, $data["dn_id"], $this->request->post["file_link_who"]);
                        $this->response->redirect($this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    } else {
                        $data["error"] = " Фаил ссылок пустой.";
                    }
                } else {
                    $data["error"] = " Не выбран файл настроек для загрузки";
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_grab" . $adap["exten"], $data));
    }
    public function paramsetup()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Настройки парсинга - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        $data["mpage"] = $this->mPage();
        $getparamsetup = $this->model_catalog_simplepars->GetParamsetup($data["dn_id"]);
        $data["hrefs"] = $getparamsetup["hrefs"];
        $data["params"] = $getparamsetup["params"];
        $data["setting"] = $getparamsetup["setting"];
        $data["browser"] = $getparamsetup["browser"];
        $data["view_href"] = $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["page_code"] = "<h1><strong>Warning!</strong> Не выбрана ССЫЛКА для просмотра кода ---></h1>";
        $data["view_link"] = "";
        $data["menup"]["type_param"] = 1;
        if (!empty($this->request->post["view_href"])) {
            $data["view_link"] = str_replace("&amp;", "&", $this->request->post["view_href"]);
            $show_url = $this->model_catalog_simplepars->getUrlId($data["view_link"]);
            if (empty($show_url["id"])) {
                $show_url["id"] = 0;
                $this->session->data["view_link"] = $data["view_link"];
            }
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=" . (int) $show_url["id"], true));
        }
        if (isset($this->request->get["url_id"])) {
            $url_id = (int) $this->request->get["url_id"];
            if ($url_id == 0) {
                $data["view_link"] = $this->session->data["view_link"];
                $data["page_code"] = @htmlspecialchars(@$this->model_catalog_simplepars->CachePage($this->session->data["view_link"], $data["dn_id"]));
                unset($this->session->data["view_link"]);
            } else {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["view_link"] = $show_url["link"];
                    $data["page_code"] = htmlspecialchars($this->model_catalog_simplepars->CachePage($show_url["link"], $data["dn_id"]));
                }
            }
        }
        if (isset($this->request->post["copy_param"]) && $this->request->post["copy_param"] == "yes") {
            if ($this->request->post["get_param_id"] == 0) {
                $data["error"] = " Выберите границу парсинга которую хотите скопировать.";
            } else {
                $this->model_catalog_simplepars->copyParamPars($this->request->post["get_param_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
        }
        if (isset($this->request->post["del_param"]) && $this->request->post["del_param"] == "yes") {
            if ($this->request->post["get_param_id"] == 0) {
                $data["error"] = "Для удаления необходимо выбрать границу парсинга";
            } else {
                $this->model_catalog_simplepars->delParamPars($this->request->post["get_param_id"]);
                $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
        }
        if (isset($this->request->post["pre_view_param"])) {
            $this->model_catalog_simplepars->setViewParam($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["cache_page"])) {
            $this->model_catalog_simplepars->changeCacheParam($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_paramsetup" . $adap["exten"], $data));
    }
    public function createcsv()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("CSV/Парсинг - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        $data["mpage"] = $this->mPage();
        $createcsv = $this->model_catalog_simplepars->GetParamsetup($data["dn_id"]);
        $getformcsv = $this->model_catalog_simplepars->getFormCsv($data["dn_id"]);
        
        $data["params"] = $createcsv["params"];
        $data["links_select"] = $getformcsv["links_select"];
        $data["formcsv"] = $getformcsv["formcsv"];
        $data["setting"] = $getformcsv["setting"];
        $data["browser"] = $getformcsv["browser"];
        $data["csv_exists"] = $getformcsv["csv_exists"];
        $data["setup"] = $getformcsv["setup"];
        $data["link_lists"] = $getformcsv["link_lists"];
        $data["link_errors"] = $getformcsv["link_errors"];
        $data["view_href"] = "";
        if (!is_array($data["formcsv"]) || empty($data["formcsv"])) {
            $data["key_finish"] = NULL;
        } else {
            $data["key_finish"] = array_keys($data["formcsv"])[count($data["formcsv"]) - 1];
        }
        if (!is_array($data["setup"]["grans_permit_list"]) || empty($data["setup"]["grans_permit_list"])) {
            $data["grans_permit_key_max"] = 1;
        } else {
            $data["grans_permit_key_max"] = array_keys($data["setup"]["grans_permit_list"])[count($data["setup"]["grans_permit_list"]) - 1];
        }
        if (isset($this->request->post["save_form_csv"])) {
            $this->model_catalog_simplepars->saveFormCsv($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pars_data_start"])) {
            $this->model_catalog_simplepars->controlParsDataToCsv($data["dn_id"]);
        }
        if (!empty($this->request->post["download_csv"])) {
            $this->model_catalog_simplepars->dwFile("csv", $data["dn_id"]);
        }
        if (!empty($this->request->post["del_csv"])) {
            $this->model_catalog_simplepars->delFile($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $this->session->data["error"] = "Выберите ссылку для пред просмотра";
                    $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                }
            }
            $data["view_href"] = $this->request->post["view_href"];
            $answer = $this->model_catalog_simplepars->controlShowParsToCsv($data["view_href"], $data["dn_id"]);
            if ($answer == "redirect") {
                $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
            }
            $data["show"] = $answer;
        } else {
            if (!empty($this->request->get["url_id"])) {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["view_href"] = $show_url["link"];
                    $answer = $this->model_catalog_simplepars->controlShowParsToCsv($data["view_href"], $data["dn_id"]);
                    if ($answer == "redirect") {
                        $this->response->redirect($this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    }
                    $data["show"] = $answer;
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_createcsv" . $adap["exten"], $data));
    }
    public function replace()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Поиск/Замена - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["get_param_href"] = $this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=", true);
        $replace = $this->model_catalog_simplepars->getReplacePage($data["dn_id"], $param_id);
        $data["params"] = $replace["params"];
        $data["replace"] = $replace["replace"];
        $data["replace_links"] = $replace["replace_links"];
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (!empty($this->session->data["rep_view_href"])) {
            $data["view_href"] = $this->session->data["rep_view_href"];
            unset($this->session->data["rep_view_href"]);
        } else {
            $data["view_href"] = "";
        }
        if (!empty($replace["show"])) {
            $data["show"] = $replace["show"];
        }
        if (empty($this->request->get["param_id"])) {
            $data["param_id"] = 0;
            $data["param_name"] = "";
        } else {
            $data["param_id"] = (int) $this->request->get["param_id"];
            $data["param_name"] = "";
            foreach ($data["params"] as $param) {
                if ($data["param_id"] == $param["id"]) {
                    if ($param["type"] == 2) {
                        $data["param_name"] = "@ " . $param["name"];
                        $data["param_type"] = 2;
                    } else {
                        $data["param_name"] = $param["name"];
                        $data["param_type"] = 1;
                    }
                }
            }
        }
        if (isset($this->request->post["save_replace"])) {
            $this->model_catalog_simplepars->saveReplacePage($this->request->post, $data["dn_id"], $param_id);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        if (isset($this->request->post["check_text"])) {
            $this->model_catalog_simplepars->saveReplacePage($this->request->post, $data["dn_id"], $param_id);
            $this->model_catalog_simplepars->showReplaceText($this->request->post, $param_id, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        if (isset($this->request->post["download_param"])) {
            if (empty($this->request->post["download_link"])) {
                $this->request->post["download_link"] = $this->request->post["view_href"];
            }
            $this->session->data["rep_view_href"] = $this->request->post["download_link"];
            $this->model_catalog_simplepars->getParamShow($this->request->post, $param_id, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        if (isset($this->request->post["download_param_and_check"])) {
            if (empty($this->request->post["download_link"])) {
                $this->request->post["download_link"] = $this->request->post["view_href"];
            }
            $this->session->data["rep_view_href"] = $this->request->post["download_link"];
            $this->model_catalog_simplepars->saveReplacePage($this->request->post, $data["dn_id"], $param_id);
            $this->model_catalog_simplepars->getParamShow($this->request->post, $param_id, $data["dn_id"]);
            $temp["text_give"] = $this->model_catalog_simplepars->getGranFromFile($param_id, "input_text");
            $this->model_catalog_simplepars->showReplaceText($temp, $param_id, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&param_id=" . $param_id, true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_replace" . $adap["exten"], $data));
    }
    public function productsetup()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Парсинг в ИМ - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["params"] = $this->model_catalog_simplepars->getParsParams($data["dn_id"]);
        $data["setup"] = $this->model_catalog_simplepars->getPrsetupToPage($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSettingToProduct($data["dn_id"]);
        $data["manufs"] = $this->model_catalog_simplepars->getManufs();
        $data["categorys"] = $this->model_catalog_simplepars->madeCatTree(1);
        $data["attr_groups"] = $this->model_catalog_simplepars->getAttrGroup();
        $data["stores"] = $this->model_catalog_simplepars->getAllStore();
        $data["langs"] = $this->model_catalog_simplepars->getAllLang();
        $data["stock_status"] = $this->model_catalog_simplepars->getAllStockStatus();
        $data["options"] = $this->model_catalog_simplepars->getAllOpts();
        $data["length_classes"] = $this->model_catalog_simplepars->getLengthClassId();
        $data["weight_classes"] = $this->model_catalog_simplepars->getWeightClassId();
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        $data["link_lists"] = $this->model_catalog_simplepars->getAllLinkList($data["dn_id"]);
        $data["link_errors"] = $this->model_catalog_simplepars->getAllLinkError($data["dn_id"]);
        $data["layouts"] = $this->model_catalog_simplepars->getAllLayouts();
        $data["cast_groups"] = $this->model_catalog_simplepars->getAllGroupCustomer();
        $data["option_module_img"] = $this->model_catalog_simplepars->checkModuleOption();
        $data["hpm_inst"] = $this->model_catalog_simplepars->checkModuleHpm();
        if (!is_array($data["setup"]["opts"]) || empty($data["setup"]["opts"])) {
            $data["opt_key_max"] = NULL;
        } else {
            $data["opt_key_max"] = array_keys($data["setup"]["opts"])[count($data["setup"]["opts"]) - 1];
        }
        if (empty($data["setting"]["r_price_spec_date_start"])) {
            $data["setting"]["r_price_spec_date_start"] = date("Y-m-d");
        }
        if (empty($data["setting"]["r_price_spec_date_end"])) {
            $data["setting"]["r_price_spec_date_end"] = "0000-00-00";
        }
        foreach ($data["stores"] as $key_s => $store) {
            if (in_array($store["store_id"], $data["setting"]["r_store"])) {
                $data["stores"][$key_s]["checked"] = 1;
            } else {
                $data["stores"][$key_s]["checked"] = 0;
            }
        }
        foreach ($data["langs"] as $key_l => $lang) {
            if (in_array($lang["language_id"], $data["setting"]["r_lang"])) {
                $data["langs"][$key_l]["checked"] = 1;
            } else {
                $data["langs"][$key_l]["checked"] = 0;
            }
        }
        if (!is_array($data["setup"]["grans_permit_list"]) || empty($data["setup"]["grans_permit_list"])) {
            $data["grans_permit_key_max"] = 1;
        } else {
            $data["grans_permit_key_max"] = array_keys($data["setup"]["grans_permit_list"])[count($data["setup"]["grans_permit_list"]) - 1];
        }
        $data["href_show"] = $this->url->link("catalog/simplepars/productshow", $adap["token"] . "&dn_id=" . $data["dn_id"], true);
        if ($data["setting"]["sid"] == "sku" && $data["setting"]["r_sku"] == 1) {
            $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Артикул (sku)";
        } else {
            if ($data["setting"]["sid"] == "name" && $data["setting"]["r_name"] == 1) {
                $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Название";
            } else {
                if ($data["setting"]["sid"] == "upc" && $data["setting"]["r_upc"] == 1) {
                    $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле upc";
                } else {
                    if ($data["setting"]["sid"] == "ean" && $data["setting"]["r_ean"] == 1) {
                        $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле ean";
                    } else {
                        if ($data["setting"]["sid"] == "jan" && $data["setting"]["r_jan"] == 1) {
                            $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле jan";
                        } else {
                            if ($data["setting"]["sid"] == "isbn" && $data["setting"]["r_isbn"] == 1) {
                                $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле isbn";
                            } else {
                                if ($data["setting"]["sid"] == "mpn" && $data["setting"]["r_mpn"] == 1) {
                                    $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле mpn";
                                } else {
                                    if ($data["setting"]["sid"] == "location" && $data["setting"]["r_location"] == 1) {
                                        $data["error"] = "Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле location";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (isset($this->request->post["save"])) {
            $this->model_catalog_simplepars->savePrsetup($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pars_data_start"])) {
            $this->model_catalog_simplepars->startParsToIm($data["dn_id"]);
        }
        if (isset($this->request->post["links_restart"])) {
            $this->model_catalog_simplepars->linksRestart($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_productsetup" . $adap["exten"], $data));
    }
    public function productshow()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Пред просмотр парсинга ИМ - SimplePars");
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["param_id"])) {
            $param_id = (int) $this->request->get["param_id"];
        } else {
            $param_id = "";
        }
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["view_href"] = "";
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["links"] = $this->model_catalog_simplepars->getFormShowProduct($data["dn_id"]);
        $data["back_url"] = $this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true);
        $data["http_catalog"] = HTTP_CATALOG;
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $data["error"] = "Не выбрана ссылка для пред просмотра.";
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                    $data["view_href"] = $this->request->post["view_href"];
                    $data["product"] = $this->model_catalog_simplepars->goShowToIm($this->request->post["view_href"], $data["dn_id"]);
                }
            } else {
                $data["view_href"] = $this->request->post["view_href"];
                $data["product"] = $this->model_catalog_simplepars->goShowToIm($this->request->post["view_href"], $data["dn_id"]);
            }
        } else {
            if (!empty($this->request->get["url_id"])) {
                $show_url = $this->model_catalog_simplepars->getUrlFromId($this->request->get["url_id"]);
                if (!empty($show_url["link"])) {
                    $data["product"] = $this->model_catalog_simplepars->goShowToIm($show_url["link"], $data["dn_id"]);
                }
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_productshow" . $adap["exten"], $data));
    }
    public function listurl()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Менеджер ссылок - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        $data["url_param"] = $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_csv"] = $this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_im"] = $this->url->link("catalog/simplepars/productshow", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&url_id=", true);
        $data["url_page"] = $this->url->link("catalog/simplepars/cachedn", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page=", true);
        $data["list_names"] = $this->model_catalog_simplepars->getAllLinkList($data["dn_id"]);
        $data["list_errors"] = $this->model_catalog_simplepars->getAllLinkError($data["dn_id"]);
        $data["button_url_price"] = $this->model_catalog_simplepars->urlCheckPriceListToPage($data["dn_id"]);
        if (!empty($this->request->post["list_add"])) {
            $this->model_catalog_simplepars->addNewLinkList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (!empty($this->request->post["list_del"])) {
            $this->model_catalog_simplepars->delLinkList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (!empty($this->request->post["download_url_price"])) {
            $this->model_catalog_simplepars->dwFile("urlPriceList", $data["dn_id"]);
        }
        if (!empty($this->request->post["del_url_price"])) {
            $this->model_catalog_simplepars->urlDelPriceListUrl($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["file"]) && $this->request->post["file"] == "file_links") {
            if (is_uploaded_file($this->request->files["import"]["tmp_name"])) {
                $form = file_get_contents($this->request->files["import"]["tmp_name"]);
                if ($form) {
                    $this->model_catalog_simplepars->uploadLinkFromFile($form, $data["dn_id"], $this->request->post["file_link_who"]);
                    $this->response->redirect($this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                } else {
                    $data["error"] = " Фаил ссылок пустой.";
                }
            } else {
                $data["error"] = " Не выбран файл настроек для загрузки";
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_listurl" . $adap["exten"], $data));
    }
    public function tools()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Редактор товаров - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        if (empty($this->request->get["pt_id"])) {
            $this->request->get["pt_id"] = 0;
        }
        $data["pattern"] = $this->model_catalog_simplepars->toolGetPatternToPage($this->request->get["pt_id"]);
        $data["patterns_all"] = $this->model_catalog_simplepars->toolGetAllPatterns($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if ($data["setting"]["vers_op"] == "ocstore2" || $data["setting"]["vers_op"] == "ocstore3") {
            $data["setting"]["vers_op"] = 1;
        } else {
            $data["setting"]["vers_op"] = 0;
        }
        $data["dns_id"] = $this->model_catalog_simplepars->getAllProject();
        $data["categorys"] = $this->model_catalog_simplepars->toolMadeCategoryToPage();
        $data["manufs"] = $this->model_catalog_simplepars->getManufs();
        $data["langs"] = $this->model_catalog_simplepars->getAllLang();
        $data["cast_groups"] = $this->model_catalog_simplepars->getAllGroupCustomer();
        $data["stock_status"] = $this->model_catalog_simplepars->getAllStockStatus();
        $data["permit_hpm"] = $this->model_catalog_simplepars->checkModuleHpm();
        $data["pt_naw"] = $this->request->get["pt_id"];
        $data["url_pt"] = $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&pt_id=", true);
        if (!empty($data["pattern"]["langs"])) {
            foreach ($data["langs"] as $key_l => $lang) {
                if (in_array($lang["language_id"], $data["pattern"]["langs"])) {
                    $data["langs"][$key_l]["checked"] = 1;
                } else {
                    $data["langs"][$key_l]["checked"] = 0;
                }
            }
        } else {
            foreach ($data["langs"] as $key_l => $lang) {
                if ($lang["language_id"] == $this->model_catalog_simplepars->getLangDef()) {
                    $data["langs"][$key_l]["checked"] = 1;
                } else {
                    $data["langs"][$key_l]["checked"] = 0;
                }
            }
        }
        $what = ["\\", PHP_EOL];
        $than = ["\\\\", ""];
        $data["categorys_do"] = $data["categorys"];
        $data["manufs_do"] = $data["manufs"];
        foreach ($data["manufs_do"] as &$mf) {
            $mf["name"] = str_ireplace($what, $than, htmlspecialchars($mf["name"], ENT_QUOTES));
        }
        array_unshift($data["categorys"], ["id" => "0", "name" => "Товары без категорий"]);
        if (!empty($data["pattern"]["cats"])) {
            foreach ($data["categorys"] as $key_c => $cat) {
                if (in_array($cat["id"], $data["pattern"]["cats"])) {
                    $data["categorys"][$key_c]["checked"] = 1;
                } else {
                    $data["categorys"][$key_c]["checked"] = 0;
                }
                $data["categorys"][$key_c]["name"] = str_ireplace($what, $than, htmlspecialchars($cat["name"], ENT_QUOTES));
            }
        } else {
            foreach ($data["categorys"] as $key_c => $cat) {
                $data["categorys"][$key_c]["checked"] = 0;
                $data["categorys"][$key_c]["name"] = str_ireplace($what, $than, htmlspecialchars($cat["name"], ENT_QUOTES));
            }
        }
        $data["categorys"][0]["name"] = "<b>Товары без категорий</b>";
        if (!empty($data["pattern"]["new_cats"])) {
            foreach ($data["categorys_do"] as $key_d => $cat) {
                if (in_array($cat["id"], $data["pattern"]["new_cats"])) {
                    $data["categorys_do"][$key_d]["checked"] = 1;
                } else {
                    $data["categorys_do"][$key_d]["checked"] = 0;
                }
                $data["categorys_do"][$key_d]["name"] = str_ireplace($what, $than, htmlspecialchars($cat["name"], ENT_QUOTES));
            }
        } else {
            foreach ($data["categorys_do"] as $key_d => $cat) {
                $data["categorys_do"][$key_d]["checked"] = 0;
                $data["categorys_do"][$key_d]["name"] = str_ireplace($what, $than, htmlspecialchars($cat["name"], ENT_QUOTES));
            }
        }
        if (!empty($data["pattern"]["manufs"])) {
            foreach ($data["manufs"] as $key_m => $manufs) {
                if (in_array($manufs["id"], $data["pattern"]["manufs"])) {
                    $data["manufs"][$key_m]["checked"] = 1;
                } else {
                    $data["manufs"][$key_m]["checked"] = 0;
                }
            }
        } else {
            foreach ($data["manufs"] as $key_m => $manufs) {
                $data["manufs"][$key_m]["checked"] = 0;
            }
        }
        $data["dns_select"][0] = ["dn_id" => "all", "dn_name" => "Все товары, без учета проектов!!!", "checked" => 0];
        $data["dns_select"][1] = ["dn_id" => "0", "dn_name" => "error", "checked" => 0];
        if (!empty($data["pattern"]["dns_arr"])) {
            foreach ($data["dns_id"] as $key_ds => $dns_id) {
                if ($data["setting"]["dn_id"] == $dns_id["dn_id"]) {
                    $key_ds = 0;
                    $dns_id["dn_name"] = "<b>Товары этого проекта </b>";
                }
                if (in_array($dns_id["dn_id"], $data["pattern"]["dns_arr"])) {
                    $dns_id["checked"] = 1;
                    $data["dns_select"][$key_ds + 1] = $dns_id;
                } else {
                    $dns_id["checked"] = 0;
                    $data["dns_select"][$key_ds + 1] = $dns_id;
                }
            }
            if (in_array("all", $data["pattern"]["dns_arr"])) {
                $data["dns_select"][0]["checked"] = 1;
            }
        } else {
            foreach ($data["dns_id"] as $key_ds => $dns_id) {
                if ($data["setting"]["dn_id"] == $dns_id["dn_id"]) {
                    $dns_id["checked"] = 1;
                    $dns_id["dn_name"] = "<b>Товары этого проекта </b>";
                    $data["dns_select"][1] = $dns_id;
                } else {
                    $dns_id["checked"] = 0;
                    $data["dns_select"][$key_ds + 1] = $dns_id;
                }
            }
        }
        if (isset($this->request->post["get_filter"])) {
            $this->model_catalog_simplepars->toolFilterToPage($this->request->post, $data["dn_id"]);
        }
        if (isset($this->request->post["apply_action"])) {
            $this->model_catalog_simplepars->toolControlerFunction($this->request->post, $data["dn_id"], "user");
        }
        if (isset($this->request->post["pattern_add"])) {
            $this->model_catalog_simplepars->toolAddPattern($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["pattern_update"]) && !empty($this->request->post["pattern_take"])) {
            $this->model_catalog_simplepars->toolUpdatePattern($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&pt_id=" . (int) $this->request->post["pattern_take"], true));
        }
        if (isset($this->request->post["patern_del"])) {
            $this->model_catalog_simplepars->toolDelPattern($this->request->post["pattern_take"]);
            $this->response->redirect($this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_tools" . $adap["exten"], $data));
    }
    public function logs()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Логи - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        $data["logs"] = $this->model_catalog_simplepars->getLogs($data["dn_id"]);
        if (isset($this->request->post["save_logs_setting"])) {
            $this->model_catalog_simplepars->saveLogSetting($this->request->post, (int) $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["dl_lods"])) {
            $file = DIR_LOGS . "simplepars_id-" . (int) $data["dn_id"] . ".log";
            $handle = fopen($file, "w+");
            fclose($handle);
            $this->response->redirect($this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["dw_lods"])) {
            $this->model_catalog_simplepars->dwFile("logs", $data["dn_id"]);
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_logs" . $adap["exten"], $data));
    }
    public function splitxml()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Обработчик XML - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $data["mpage"] = $this->mPage();
        $get_page = $this->model_catalog_simplepars->getSplitXmpPage($data["dn_id"]);
        $data["setting"] = $get_page["setting"];
        $data["xml"] = $get_page["xml"];
        $data["links"] = $get_page["links"];
        $data["view_href"] = "";
        $data["page_code"] = "<h1><strong>Warning!</strong> Не выбрана ССЫЛКА для просмотра кода ^</h1>";
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        if (isset($this->request->post["go_show"])) {
            if (empty($this->request->post["view_href"])) {
                if (empty($this->request->post["view_href2"])) {
                    $data["error"] = "Не выбрана ссылка для пред просмотра.";
                } else {
                    $this->request->post["view_href"] = $this->request->post["view_href2"];
                    $data["view_href"] = $this->request->post["view_href"];
                    $l[] = $this->request->post["view_href"];
                    $data["page_code"] = $this->model_catalog_simplepars->xmlCutsAnswerFromCurl($l, $data["dn_id"]);
                }
            } else {
                $data["view_href"] = $this->request->post["view_href"];
                $l[] = $this->request->post["view_href"];
                $data["page_code"] = $this->model_catalog_simplepars->xmlCutsAnswerFromCurl($l, $data["dn_id"]);
            }
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_splitxml" . $adap["exten"], $data));
    }
    public function browser()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Настройка запросов - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        if (isset($this->session->data["warning"])) {
            $data["warning"] = $this->session->data["warning"];
            unset($this->session->data["warning"]);
        }
        $data["mpage"] = $this->mPage();
        $data["browser"] = $this->model_catalog_simplepars->getSettingBrowser($data["dn_id"]);
        $data["proxy_list"] = $this->model_catalog_simplepars->getProxyListToPage($data["dn_id"]);
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["save_browser"])) {
            $this->model_catalog_simplepars->seveBrowser($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["save_proxy_list"])) {
            $this->model_catalog_simplepars->saveProxyList($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["clear_proxy_list"])) {
            $this->model_catalog_simplepars->clearProxyList($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (isset($this->request->post["reset_proxy_list"])) {
            $this->model_catalog_simplepars->resetProxyList($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_browser" . $adap["exten"], $data));
    }
    public function share()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("Импорт/Экспорт Настроек - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        if (isset($this->request->post["dw_form"])) {
            $data["file_json"] = $this->model_catalog_simplepars->getExportForm((int) $this->request->post["links"], $data["dn_id"]);
            $this->response->addheader("Pragma: public");
            $this->response->addheader("Expires: 0");
            $this->response->addheader("Content-Description: File Transfer");
            $this->response->addheader("Content-Type: application/octet-stream");
            $this->response->addheader("Content-Disposition: attachment; filename=\"SPsetting-" . (int) $data["dn_id"] . ".json\"");
            $this->response->addheader("Content-Transfer-Encoding: binary");
            $this->response->setOutput($data["file_json"]);
        }
        if (isset($this->request->post["sub_import"])) {
            if (is_uploaded_file($this->request->files["import"]["tmp_name"])) {
                if ($this->request->files["import"]["type"] === "application/json") {
                    $form = file_get_contents($this->request->files["import"]["tmp_name"]);
                    if ($form) {
                        $this->model_catalog_simplepars->importFrom($form, $data["dn_id"]);
                        $this->response->redirect($this->url->link("catalog/simplepars/share", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
                    } else {
                        $data["error"] = " Не выбран файл для загрузки";
                    }
                } else {
                    $data["error"] = " Неправильный формат файла настроек.";
                }
            } else {
                $data["error"] = " Файл не загружен.";
            }
        }
        if (isset($this->request->post["clear_project"])) {
            $this->model_catalog_simplepars->clearProject($data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/share", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        if (!isset($this->request->post["dw_form"])) {
            $this->response->setOutput($this->load->view("catalog/simplepars_share" . $adap["exten"], $data));
        }
    }
    public function phpscripts()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->document->setTitle("PHP скрипты - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        $data["mpage"] = $this->mPage();
        $data["setting"] = $this->model_catalog_simplepars->getSetting($data["dn_id"]);
        $data["scripts"] = $this->model_catalog_simplepars->scriptFindAll();
        $data["tasks"] = $this->model_catalog_simplepars->scriptGetAllTask($data["dn_id"]);
        if (empty($data["tasks"])) {
            $data["js_ts_key"] = 1;
        } else {
            $data["js_ts_key"] = array_keys($data["tasks"])[count($data["tasks"]) - 1];
        }
        if (!empty($this->request->post["save_script_task"])) {
            $this->model_catalog_simplepars->scriptSaveTasks($this->request->post, $data["dn_id"]);
            $this->response->redirect($this->url->link("catalog/simplepars/phpscripts", $adap["token"] . "&dn_id=" . $data["dn_id"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_phpscripts" . $adap["exten"], $data));
    }
    public function cron()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Менеджер заданий (CRON) - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        }
        $cronpage = $this->model_catalog_simplepars->getCronPageInfo();
        $data["cron_permit"] = $cronpage["cron_permit"];
        $data["cron_button"] = $cronpage["cron_button"];
        $data["crons"] = $cronpage["crons"];
        $data["dn_list"] = $cronpage["dn_list"];
        $data["time_machin"] = $cronpage["time_machin"];
        $data["select_time"] = $cronpage["select_time"];
        $data["user_times"] = $cronpage["user_times"];
        $data["patterns_json"] = $cronpage["patterns_json"];
        $data["patterns"] = $cronpage["patterns"];
        $data["tools_last_key"] = $cronpage["tools_last_key"];
        $data["href_dn"] = $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=", true);
        if (!empty($this->request->post["cron_permit"])) {
            $this->model_catalog_simplepars->cronOnOff($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["cron_add"])) {
            $this->model_catalog_simplepars->cronAddTask($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["save"])) {
            $this->model_catalog_simplepars->saveFormCron($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["task_del"])) {
            $this->model_catalog_simplepars->cronDelTask($this->request->post);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        if (!empty($this->request->post["rest_task"])) {
            $this->model_catalog_simplepars->cronRestartTaskFromUser($this->request->post["rest_task"]);
            $this->response->redirect($this->url->link("catalog/simplepars/cron", $adap["token"], true));
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_cron" . $adap["exten"], $data));
    }
    public function ajax()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["who"])) {
            if ($this->request->get["who"] == "paramsetup") {
                if (isset($this->request->post["act"])) {
                    $this->request->post["act"] = trim($this->request->post["act"]);
                    if ($this->request->post["act"] == "new") {
                        $param = $this->model_catalog_simplepars->addParamPars($this->request->post, $this->request->get["dn_id"]);
                        exit(json_encode($param));
                    }
                    $this->model_catalog_simplepars->saveParamPars($this->request->post);
                    exit("save_param");
                }
                if (isset($this->request->post["get_param_id"])) {
                    $activ_param = $this->model_catalog_simplepars->getActivParam($this->request->post["get_param_id"]);
                    exit(json_encode($activ_param));
                }
                if (!empty($this->request->post["piece_code"])) {
                    $show_code = $this->model_catalog_simplepars->showPieceCode($this->request->post, $data["dn_id"]);
                    exit($show_code["page_code"]);
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "cache_page") {
                    $this->model_catalog_simplepars->changeTypeCaching($this->request->post["cache_page"], $data["dn_id"]);
                    exit("Выбор метода кеширования сохранен");
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "pre_view_syntax") {
                    $this->model_catalog_simplepars->changeSelectSyntax($this->request->post["pre_view_syntax"], $data["dn_id"]);
                    exit("Выбор подсветки синтаксиса сохранен");
                }
                if (!empty($this->request->post["do"]) && $this->request->post["do"] == "pre_view_param") {
                    $this->model_catalog_simplepars->changeSelectPreview($this->request->post["pre_view_param"], $data["dn_id"]);
                    exit("Выбор подсветки синтаксиса сохранен");
                }
            } else {
                if ($this->request->get["who"] == "logs") {
                    if ($this->request->get["do"] == "get_logs") {
                        $logs = $this->model_catalog_simplepars->getLogs($data["dn_id"]);
                        exit($logs);
                    }
                } else {
                    if ($this->request->get["who"] == "tools") {
                        $this->load->model("tool/image");
                        if ($this->request->get["do"] == "filter") {
                            $answ = [];
                            $temp = $this->model_catalog_simplepars->toolFilterToPage($this->request->post, $data["dn_id"]);
                            $pagination = new Pagination();
                            $pagination->total = $temp["total"];
                            $pagination->page = (int) $this->request->post["page"];
                            $pagination->limit = (int) $this->request->post["page_count"];
                            $pagination->url = $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page={page}");
                            $answ["pagination"] = $this->model_catalog_simplepars->toolRenderPage($pagination->render());
                            foreach ($temp["products"] as &$product) {
                                $product["url_in"] = $this->url->link("catalog/product/edit", $adap["token"] . "&product_id=" . $product["product_id"], true);
                            }
                            $answ["totla"] = $temp["total"];
                            $answ["back_cod"] = $temp["back_cod"];
                            $answ["products"] = $temp["products"];
                            exit(json_encode($answ));
                        } else {
                            if ($this->request->get["do"] == "action") {
                                $this->model_catalog_simplepars->toolControlerFunction($this->request->post, $data["dn_id"], "user");
                            }
                        }
                    } else {
                        if ($this->request->get["who"] == "listurl") {
                            if ($this->request->get["do"] == "filter") {
                                $answ = [];
                                $temp = $this->model_catalog_simplepars->urlFilterToPage($this->request->post, $data["dn_id"]);
                                $pagination = new Pagination();
                                $pagination->total = $temp["total"];
                                $pagination->page = (int) $this->request->post["page"];
                                $pagination->limit = (int) $this->request->post["page_count"];
                                $pagination->url = $this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"] . "&page={page}");
                                $answ["pagination"] = $this->model_catalog_simplepars->toolRenderPage($pagination->render());
                                $answ["totla"] = $temp["total"];
                                $answ["urls"] = $temp["urls"];
                                exit(json_encode($answ));
                            }
                            if ($this->request->get["do"] == "action") {
                                $this->model_catalog_simplepars->urlControlerFunction($this->request->post, $data["dn_id"]);
                            }
                        } else {
                            if ($this->request->get["who"] == "splitxml") {
                                if (!empty($this->request->post["show_test"])) {
                                    $this->model_catalog_simplepars->xmlSaveGran($this->request->post, $data["dn_id"]);
                                    $show_code = $this->model_catalog_simplepars->xmlShowPieceCode($this->request->post, $data["dn_id"]);
                                    exit($show_code);
                                }
                                if (!empty($this->request->post["save_gran"])) {
                                    $this->model_catalog_simplepars->xmlSaveGran($this->request->post, $data["dn_id"]);
                                    exit("Граница деления XML на разные товары сохранена");
                                }
                                if (!empty($this->request->post["save_cache_page"])) {
                                    $this->model_catalog_simplepars->changeTypeCaching($this->request->post["cache_page"], $data["dn_id"]);
                                }
                            } else {
                                if ($this->request->get["who"] == "get_urls") {
                                    if (empty($this->request->post["links_restart"])) {
                                        $this->model_catalog_simplepars->saveLinkListAndError($this->request->post, $data["dn_id"]);
                                        $pars_url = $this->model_catalog_simplepars->getUrlToPars($data["dn_id"], $this->request->post["link_list"], $this->request->post["link_error"]);
                                        exit(json_encode($pars_url));
                                    }
                                    $this->model_catalog_simplepars->restLinkToPars($this->request->post, $data["dn_id"]);
                                    exit(json_encode("Рестарт ссылок произведен"));
                                }
                                if ($this->request->get["who"] == "get_urls_sen") {
                                    if (empty($this->request->post["links_restart"])) {
                                        $pars_url = $this->model_catalog_simplepars->getUrlSenToPars($data["dn_id"]);
                                        exit(json_encode($pars_url));
                                    }
                                    $this->model_catalog_simplepars->restSenLinkToPars($data["dn_id"]);
                                    exit(json_encode("Рестарт ссылок произведен"));
                                }
                                if ($this->request->get["who"] == "br_auth") {
                                    $this->model_catalog_simplepars->saveAuthSettingAjax($this->request->post, $data["dn_id"]);
                                    $code = $this->model_catalog_simplepars->controlBrowserAuth($data["dn_id"]);
                                    exit(json_encode($code));
                                }
                                if ($this->request->get["who"] == "br_auth_get_page") {
                                    $this->model_catalog_simplepars->saveAuthSettingAjax($this->request->post, $data["dn_id"]);
                                    $code = $this->model_catalog_simplepars->controlDownloadPageToAuth($this->request->post["auth_url_check"], $data["dn_id"]);
                                    exit(json_encode($code));
                                }
                                if ($this->request->get["who"] == "br_auth_check") {
                                    $this->model_catalog_simplepars->saveAuthSettingAjax($this->request->post, $data["dn_id"]);
                                    $code = $this->model_catalog_simplepars->controlAuthCheck($this->request->post, $data["dn_id"]);
                                    exit(json_encode($code));
                                }
                                if ($this->request->get["who"] == "phpscripts") {
                                    if ($this->request->post["do"] == "get_script") {
                                        $value = $this->model_catalog_simplepars->scriptGetData($this->request->post, $data["dn_id"]);
                                        exit(json_encode($value));
                                    }
                                    if ($this->request->post["do"] == "script_add_update") {
                                        $value = $this->model_catalog_simplepars->scriptAddOrUpdate($this->request->post, $data["dn_id"]);
                                        exit(json_encode($value));
                                    }
                                    if ($this->request->post["do"] == "script_del") {
                                        $value = $this->model_catalog_simplepars->scriptDel($this->request->post, $data["dn_id"]);
                                        exit(json_encode($value));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function parsajax()
    {
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $this->load->model("catalog/simplepars");
        if (!empty($this->request->get["who"])) {
            if ($this->request->get["who"] == "grab") {
                if ($this->request->get["i"] == 1) {
                    $this->model_catalog_simplepars->SeveFormGrab($this->request->post, $data["dn_id"]);
                }
                $this->model_catalog_simplepars->grabControl($this->request->get["i"], $data["dn_id"]);
            } else {
                if ($this->request->get["who"] == "pr_csv") {
                    $this->model_catalog_simplepars->controlParsDataToCsv($data["dn_id"]);
                } else {
                    if ($this->request->get["who"] == "pr_im") {
                        $this->model_catalog_simplepars->startParsToIm($data["dn_id"]);
                    } else {
                        if ($this->request->get["who"] == "br_pr") {
                            $this->model_catalog_simplepars->startCheckProxy($data["dn_id"]);
                        } else {
                            if ($this->request->get["who"] == "pr_cache") {
                                if ($this->request->get["i"] == 1) {
                                    $this->model_catalog_simplepars->saveCacheForm($this->request->post, $data["dn_id"]);
                                }
                                $this->model_catalog_simplepars->controlParsToCache($data["dn_id"]);
                            } else {
                                if ($this->request->get["who"] == "pr_xml") {
                                    $this->model_catalog_simplepars->controlParsToXml($data["dn_id"]);
                                } else {
                                    exit(json_encode("error pars who"));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function cronstart()
    {
        $this->load->model("catalog/simplepars");
        $this->model_catalog_simplepars->cronStart();
    }
    private function validateForm()
    {
        if (!$this->user->hasPermission("modify", "catalog/simplepars")) {
            $this->error["warning"] = $this->language->get("error_permission");
        }
        if (!$this->error) {
            return true;
        }
        return false;
    }
    public function mPage()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $data["dn_id"] = (int) $this->request->get["dn_id"];
        $mpage[1] = ["active" => "", "title" => "<i class=\"fa fa-eye\" aria-hidden=\"true\"></i> Сбор ссылок", "href" => $this->url->link("catalog/simplepars/grab", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[2] = ["active" => "", "title" => "<i class=\"fa fa-tasks\" aria-hidden=\"true\"></i> Настройки парсинга", "href" => $this->url->link("catalog/simplepars/paramsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[3] = ["active" => "", "title" => "<i class=\"fa fa-search\" aria-hidden=\"true\"></i> Поиск/Замена", "href" => $this->url->link("catalog/simplepars/replace", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[4] = ["active" => "", "title" => "<i class=\"fa fa-cart-arrow-down\" aria-hidden=\"true\"></i> Парсинг в ИМ", "href" => $this->url->link("catalog/simplepars/productsetup", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[5] = ["active" => "", "title" => "<i class=\"fa fa-file-text\" aria-hidden=\"true\"></i> Парсинг в CSV", "href" => $this->url->link("catalog/simplepars/createcsv", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[6] = ["active" => "", "title" => "<i class=\"fa fa-pencil\" aria-hidden=\"true\"></i> Редактор товаров", "href" => $this->url->link("catalog/simplepars/tools", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[7] = ["active" => "", "title" => "<i class=\"fa fa-list\" aria-hidden=\"true\"></i> Менеджер URL", "href" => $this->url->link("catalog/simplepars/listurl", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[8] = ["active" => "", "title" => "<i class=\"fa fa-bug\" aria-hidden=\"true\"></i> Логи", "href" => $this->url->link("catalog/simplepars/logs", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[9] = ["active" => "", "title" => "<i class=\"fa fa-file-excel-o\" aria-hidden=\"true\"></i> Обработчик XML", "href" => $this->url->link("catalog/simplepars/splitxml", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[10] = ["active" => "", "title" => "<i class=\"fa fa-chrome\" aria-hidden=\"true\"></i> Настройка запросов", "href" => $this->url->link("catalog/simplepars/browser", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[11] = ["active" => "", "title" => "<i class=\"fa fa-file-code-o\" aria-hidden=\"true\"></i> PHP скрипты", "href" => $this->url->link("catalog/simplepars/phpscripts", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        $mpage[12] = ["active" => "", "title" => "<i class=\"fa fa-exchange\" aria-hidden=\"true\"></i> Импорт/Экспорт Настроек", "href" => $this->url->link("catalog/simplepars/share", $adap["token"] . "&dn_id=" . $data["dn_id"], true)];
        if ($this->request->get["route"] == "catalog/simplepars/grab") {
            $mpage[1]["active"] = "active";
        } else {
            if ($this->request->get["route"] == "catalog/simplepars/paramsetup") {
                $mpage[2]["active"] = "active";
            } else {
                if ($this->request->get["route"] == "catalog/simplepars/replace") {
                    $mpage[3]["active"] = "active";
                } else {
                    if ($this->request->get["route"] == "catalog/simplepars/productsetup") {
                        $mpage[4]["active"] = "active";
                    } else {
                        if ($this->request->get["route"] == "catalog/simplepars/createcsv") {
                            $mpage[5]["active"] = "active";
                        } else {
                            if ($this->request->get["route"] == "catalog/simplepars/tools") {
                                $mpage[6]["active"] = "active";
                            } else {
                                if ($this->request->get["route"] == "catalog/simplepars/listurl") {
                                    $mpage[7]["active"] = "active";
                                } else {
                                    if ($this->request->get["route"] == "catalog/simplepars/logs") {
                                        $mpage[8]["active"] = "active";
                                    } else {
                                        if ($this->request->get["route"] == "catalog/simplepars/splitxml") {
                                            $mpage[9]["active"] = "active";
                                        } else {
                                            if ($this->request->get["route"] == "catalog/simplepars/browser") {
                                                $mpage[10]["active"] = "active";
                                            } else {
                                                if ($this->request->get["route"] == "catalog/simplepars/phpscripts") {
                                                    $mpage[11]["active"] = "active";
                                                } else {
                                                    if ($this->request->get["route"] == "catalog/simplepars/share") {
                                                        $mpage[12]["active"] = "active";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $mpage;
    }
    public function adap()
    {
        $n_vers = 1;
        if (empty($this->session->data["token"])) {
            $adap["token_t"] = "user_token=";
            $adap["token_v"] = $this->session->data["user_token"];
            $adap["token"] = $adap["token_t"] . $adap["token_v"];
            $adap["exten"] = "";
            $adap["n_vers"] = $n_vers;
        } else {
            $adap["token_t"] = "token=";
            $adap["token_v"] = $this->session->data["token"];
            $adap["token"] = $adap["token_t"] . $adap["token_v"];
            $adap["exten"] = ".tpl";
            $adap["n_vers"] = $n_vers;
        }
        return $adap;
    }
    public function breadcrumbs($adap)
    {
        $breadcrumbs = [];
        $breadcrumbs[] = ["text" => "Главная", "href" => $this->url->link("common/dashboard", $adap["token"], true)];
        $breadcrumbs[] = ["text" => "SimplePars " . $this->model_catalog_simplepars->simpleParsVersion(), "href" => $this->url->link("catalog/simplepars", $adap["token"], true)];
        return $breadcrumbs;
    }
    public function act()
    {
        $adap = $this->adap();
        $data["adap"] = $adap;
        $this->document->setTitle("Активация модуля - SimplePars");
        $this->load->model("catalog/simplepars");
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $data["breadcrumbs"] = $this->breadcrumbs($adap);
        if (isset($this->request->post["activ"])) {
            $code = $this->actModule($this->request->post);
            if ($code) {
                $this->response->redirect($this->url->link("catalog/simplepars", $adap["token"], true));
            } else {
                $this->response->redirect($this->url->link("catalog/simplepars/act", $adap["token"], true));
            }
        }
        if (isset($this->request->post["de_activ"])) {
            $this->delActiv($this->request->post);
        }
        if (!empty($this->request->get["do"])) {
            $activ = $this->sprawdz($adap, $this->request->get["do"]);
        }
        if (isset($this->session->data["error"])) {
            $data["error"] = $this->session->data["error"];
            unset($this->session->data["error"]);
        } else {
            $data["error"] = "";
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
        if (isset($this->session->data["success_act"])) {
            $data["success_act"] = $this->session->data["success_act"];
            unset($this->session->data["success_act"]);
        } else {
            $data["success_act"] = "";
        }
        $this->response->setOutput($this->load->view("catalog/simplepars_act" . $adap["exten"], $data));
    }
    public function delActiv($data)
    {
        $pars = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pars`");
        $pars = $pars->row;
        if (!empty($data["hash"])) {
            $data["hash"] = trim($data["hash"]);
            $post = $this->prePostToPolice($pars);
            $post["hash"] = $data["hash"];
            $answer = $this->reportToPolice($post, $del = 1);
            preg_match("#\\{\\!(.*?)\\!\\}#", $answer["content"], $get_pass);
            if (!empty($get_pass[1])) {
                $pass = $get_pass[1];
            } else {
                $pass = 0;
            }
            if ($pass == "good") {
                $this->db->query("UPDATE `" . DB_PREFIX . "pars` SET `hash` = '',`mod_ver` = '0'");
            } else {
                if ($pass == "not_del") {
                    $this->session->data["error"] = " Операция не выполнена, перепроверьте вводимые данные.";
                } else {
                    if ($pass == "not_time") {
                        preg_match("#\\{\\|(.*?)\\|\\}#", $answer["content"], $get_pass);
                        if (!empty($get_pass[1])) {
                            $time = $get_pass[1];
                        } else {
                            $time = 259200;
                        }
                        $sec = $time % 60;
                        $time = floor($time / 60);
                        $min = $time % 60;
                        $time = floor($time / 60);
                        $view_time = $time . ":" . $min . ":" . $sec;
                        $this->session->data["error"] = " Вы сможете отвязать ключ через : " . $view_time . " (ч:м:с)";
                    }
                }
            }
        } else {
            $this->session->data["error"] = " Укажите ключ активации который вы желаете отвязать от доменного имени";
        }
    }
    public function permitPolice($value, $data, $form = "simplepars", $do = "rassol2")
    {
        $value = md5($form . trim($value) . $do);
        $to = 0;
        $i = 0;
        $this->load->model("catalog/simplepars");
        while ($i < 30) {
            if ($value === $data) {
                $to = 1;
            } else {
                $value = md5($value);
                $i++;
            }
        }
        return $to;
    }
    public function policePermit($adap)
    {
        $adap2 = $this->adap();
        $rand = rand(1, 299);
        $rand = $this->hasloAutoryzacyjne($rand, "var");
        $value = ["p", "a", "r", "s", " ", "s", "e", "t", " ", "m", "o", "d", "_", "v", "e", "r", " ", "="];
        if ($adap == 1) {
            $this->db->query("UPDATE " . DB_PREFIX . implode($value) . "'" . $rand . "', date ='" . date("Y-m-d H:i:s") . "'");
            $this->session->data["success_act"] = " Модуль зарегистрирован";
        } else {
            $this->session->data["error"] = " Модуль не активирован";
            $this->response->redirect($this->url->link("catalog/simplepars/act", $adap2["token"], true));
        }
        return $adap;
    }
    public function actModule($data)
    {
        $code = 0;
        if (empty($data["type_key"])) {
            $data["type_key"] = "free";
        }
        if (!empty($data["hash"])) {
            if ($data["hash"] == md5(sha1(sha1(sha1($_SERVER['HTTP_HOST'].'simplepars'.'4_9'))))) {
                $this->db->query("UPDATE `" . DB_PREFIX . "pars` SET \n        hash ='" . $this->db->escape($data["hash"]) . "', \n        key_lic ='" . $this->db->escape($data["type_key"]) . "',\n        mod_ver ='aa2d6e4f578eb0cfaba23beef76c2194',\n        date ='" . date("Y-m-d H:i:s") . "'");
                $code = 1;
            } else {
                $this->db->query("UPDATE `" . DB_PREFIX . "pars` SET hash ='', key_lic ='', mod_ver ='', date ='" . date("Y-m-d H:i:s") . "'");
                $this->session->data["error"] = "Ошибка лицензионного ключа.";
            }
        } else {
            $this->session->data["error"] = "Укажите ключ активации";
        }
        return $code;
    }
    public function prePostToPolice($pars)
    {
        $data["dm"] = urlencode($_SERVER["HTTP_HOST"]);
        $setting = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pars_setting`");
        $setting = $setting->row;
        if (empty($setting["vers_op"])) {
            $setting["vers_op"] = "non";
        }
        if (empty($pars["key_lic"])) {
            $pars["key_lic"] = "free";
        }
        if (empty($pars["hash"])) {
            $pars["hash"] = "bad_hash";
        }
        $post = [];
        $post = ["dm" => $data["dm"], "key" => $pars["key_lic"], "hash" => $pars["hash"], "ver" => $setting["vers_op"]];
        return $post;
    }
    public function sprawdz($adap, $act_try = "0")
    {
        $pars = $this->db->query("SELECT * FROM `" . DB_PREFIX . "pars`")->row;
        if (empty($pars["hash"]) || $pars["hash"] == "aa2d6e4f578eb0cfaba23beef76c2194") {
            $this->policePermit(0);
        }
    }
    public function reportToPolice($data, $del = "0")
    {
        $url = "localhost";
        if ($del == 1) {
            $url = "localhost";
        }
        $uagent = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $data = curl_getinfo($ch);
        curl_close($ch);
        $data["errno"] = $err;
        $data["errmsg"] = $errmsg;
        $data["content"] = $content;
        return $data;
    }
    public function hasloAutoryzacyjne($value, $what)
    {
        $return = "0";
        $hashs = ["34aa2ab6455e7eb43302735380eafb64", "797ce2d284092b56f20c89f43bf45b2f", "2bc8379440a4ac31dbe9484e8f56b567", "232de05697444509ede687d8a6498439", "6f0fc0215f767ef0be91856854450b38", "e6a502bbc85fb96ea10faad85a43742f", "dc1669e14bdf81604fe05f749f6eccd8", "942ba3373d6a58f4a9b7826f24ab3446", "c7b66fbeb848c7ea943eb9c30669c80a", "b911dbad705603dcf73cbc9f2e69d590", "217f5f03d11222d92fe04e88c46ef815", "b1dac48da1a4b1704ba646de315f3ed3", "c08549e9a7420b7227f57167988273d2", "ad4adf6b6d364f1f607884156ef12456", "5af18e8ea353cb31a54a1aedff2675c0", "04de7b9fa3660c11a6ded46f103a9981", "0014dd24a0e7df09d0f31be9d0c79c77", "7649d2c5f720378556c0d15e53b9200b", "b842a0099f342f9bbb24c3150be02bb6", "ca4128435e2e68624e965b3d318ac4c9", "72f1eec4b08dcf2be34e655cdce98017", "e60ae7ccf67be2036251cf032952da94", "06ea6aeb55422593e7831bb08a04a142", "dc3119b8735ebda0bd9a6660e9fcb600", "473c6779b7ec3852a963ef84ee1df2d5", "95edb3bcb92320e88038b68fbb927614", "3c916df457a2f7c8ff8b46d17ff00484", "5a98480016ab30208875b303f8508d33", "bcb9bcd9c387cbbc7d70206718385182", "a7a53802ca78e93811c196ccd5fb3763", "2c2ebaa1c49c5f4f37fc7902ad5e28f3", "9c42f49c28f6c92cc838ee483fd9466b", "679f0bcc325f72176980588c4944da61", "cb8d43120bc01220fae74eb90e5b0d46", "1c03a65e16501445e7d4a178bf761762", "2ceb859158c30e9073bda648a14c22b9", "49235c78c40ddbd99d3e8de4bff2dd9e", "1ff7117bb5b2137787af9ce2d5f27727", "084c26da011a23e338003e10ce9f4d2e", "9bdab20d55a934b610fbf43a20a356cf", "616ced929958973919375044d3953138", "12bf9222a3d5e1bcab77ce2d6e006af1", "3f2d26490a54843bb99452048a4cb34a", "1ec4a124aad3a34198529adaedebc742", "9605e300c409e806dbc81ae97380e3f8", "f6fac0e00ab6ccc69985a1d787235f70", "d385f07baf3425e680eeda4a116648d4", "69ae6962c5e6ed4809c8f58283146b23", "299b2710c23dc3a33ed1d29a2f8aaf61", "208a23a9ed478f8aa5d8c719fdd40318", "f565e0869814fb59308159c4d04326ff", "707559a24d6a6762884ecbcef43a0d7a", "f1cbfedbd5a65a8f3d2ed4162ba8c11b", "6ab5fecfd25686447aa27118f3480577", "834d4d7c2e8740eac2a120f9d13b196c", "6f21d4bb21e91c63ac6fbfc6e0394950", "57099b0a32f072316c5bbf04f4359b26", "6ff9aa764a7ae47a54c4e8d60b3e33c7", "6b4d164e31a166f95a6d8facbf07ff0f", "cb61e7d083e7bde57c32de9aefaf4a13", "018275069bbac2499374d515b1d7b740", "51a94368ebc9c4b97e5fb5e646849fec", "9877e67fcd864b02c131a591946b0416", "74a5012feaa21997e41a70617681479a", "6d1d97a41f5d2dac90ea9a5f0d74a9c2", "76230488e31db5b231d6910687fae9f6", "be60d1443e169307d6b0cb870580e5d7", "98edbd8c34a14c9e8d9e8023748aabe2", "26ef25e11d53903bd03b554177ce58ba", "3b150e6104d028cb0778cdaf16febe04", "0fdfb835846b51538e403469ab456284", "950ac5ad03c28c198ac015ae43b75127", "7d97e97119a82dc59fc79c9f0ba53eb8", "eb5bc78ad5a8a360305553798479250e", "aea4a7cb593d67091222ebba1cf0b2cf", "56628ebb6cf6cb1cb1ac39e071b145ee", "969d6241615778fdaad152f2d4c8a216", "34d7999354c5f5eb20a22b6ccaf36bd9", "65e6726a7459eebf8b765ccc2aa3e529", "13bc93f1a06839699180f5349cc38394", "2cf880c16f2ab97419474bd1e6b4ef42", "7391e4499611b7e5b6c8a6fa47b89db7", "b8e4eb6e82562af061bd68d16a554efe", "301e94d0cb797835dad92a6d25563319", "398583b46d6956de900c02266bea378a", "e6e57e1042e3e9e412769ec024002fd3", "8ace16808263a6a2144cf8c1003e7c42", "b4ca707265e5b5cfdcfdccd405f78fc5", "dc9cf0016fffc8137ac3937f263bbfd3", "b700584efffb6958a2480a6bc6a2f6c5", "ee2b14ca9630fb1220d73e9796b5c69d", "9e7c819ba81a329e511ca2cb8261c895", "243d537ccb9c2627454ac1ec4fd4e081", "109c31ebb11ef03037f80b5a57b734e1", "263b0a07e659f7bfb9baafbb0288ac90", "d42d6c9dd48e56001f0d199b28cffdc4", "62f1eef124c14d61b9ef6438ee49970f", "6cb7497721329635e91d2c7cb7464fea", "508f170bde1639f305a3c2950c5f7958", "4cd57c44d0ea633627ba2e3d5d5566a7", "0cd7e974bc9cbe2aefa0020cf67cddf8", "ce9d065b7e8e8dd84db24c7046a6ed2a", "8648d7ae2eff826d0f1e6b374c3d6074", "eaf9e30a0b52a52b2b203af3452b0bd0", "a20d9cf9361e5cb888752f0c321c0990", "31bc512679f136d5eff20a2471269d29", "162597fc602151af1265bfbce949faa7", "a3979f70f14588d29d72f3601e5aa270", "583c3e086a0fcec9e29776b96cc351a1", "0c79cf6b6139c9b5c7079b50a420f5dc", "08e0ad11c5a8afc4bd35786a037d6b8f", "6bbc941e8ca97b21ba03f1039bec3f22", "b8d3fcdfc677750224f56470eb1364ca", "8b9f083d42e127842845e63b2e799373", "24085063b9f69f7a4bab54bf65ffd48a", "1b9845f45127e11727f394b486a1746a", "bc066f3d6761ae93447b5466cfbec079", "7e069a9d23e74da4abf8c83356cf7462", "e734ea43717a39fbe5bceb2ca79d437b", "48e8c1f4a62aab02fbca522c5d0f50bd", "71316ef983628b3a4faa2a4d180909ec", "287f5331264e3b3f6a0b93670539fbd1", "dba1b3d0287995518ac83bbe07f6b206", "0f4bca716519f84c0fd9493144875e8d", "9940201dcd76ccc3b57c1208903d9c7d", "1d32ee5abff82020af02be5f147e7c4d", "df244f9d78ae35eb9483184427279b5d", "9b40d32c866cb2185c859d9b0109943d", "d9e61425c9c049e46fc1fb26edc6e9aa", "93f7aeccc6a16431267dd19bc2371a53", "2dedaf5c0f433a82b031367d4c1ea652", "4fba640197cc845c131f9ce1ac40b2d5", "c668f68f0a1d312d7cfb562afef9e333", "a1bd9d7fa625974883e10a07859a2532", "4235668617b836f205d621928efd7e19", "c53fb186f57e4a745a6c3850be0fa4cb", "9fa47eb64c24bc8a5aedb21930427e02", "1291fc107d81f931434da27706724f41", "ef44708053f0489f6bec18bf396e629c", "0e8005f1f96fb5d294b90e1ee1c553bb", "200362757b9e96d2dfdd73708cbd7841", "dcd19204a430909ae9883d27ef1b7c7e", "378b1a290ab5083c8a911b67e47dd578", "46d1ea3c8fd48c99a6fc2509df6fc574", "3583d3baf0eea1961e2528ee82f59116", "fc93f06d1769eafe3b8b17df6f3a82a2", "29a1bd19788a147bdaed4dba695419d9", "4b691b1c0f5b624c981a2442f99b7f62", "f26284384f8aeef509e8f37bc616af03", "1ab5d066d42a22e98df9e4d5fedba1f4", "e8aafb3ee32bcd223d227cb53b9100b0", "c67a7718137b717d0be2eae3a46e9aba", "fad133232be58e7ce73153c53f38505e", "2a81629bb6776d884552d52103c706a5", "46145f8eed1932adc9dbd86a8d72e95a", "78310be953e3d5e4198252c0f59a126c", "48fc11ba1be83e52d0dbf158c8a12e86", "a6610cb2fddef1c86f16ce6b5a144ed8", "db2e488309eefd3144c101a4893cd5d0", "90149c67c673958ae38ee074bc708f22", "5a752955b58b179142ddad1e331affb4", "b94a8c8432b33197e852fdf769ea894e", "955266335a31d7ff9f7c19701fe27104", "898331db753d455667586d55bb9bd07a", "e67bf76534d24b1e77dee34e69daffb2", "734361399b3f56d86427135c0775713b", "49a76feea8523e174cfea3ac4cbce986", "16eda7ab200ddec2aeecf655cbd62828", "b126038ccc1838ad155e5229e4bc3659", "2812bfbcc24466f97810fe9db0e5618f", "ac5bb67cb218bc4728705e4ff9bcb1dc", "3ae94c90e06fe7920a747013ddfe10a8", "e6d7a3f915600dc527ee8071018aab7e", "c8aeb2d08bc3e037ece7c98c5641b74a", "73c0923df8b066da45e27fa503d245f7", "7c387ee6d301aa28cf5111b64bde34c9", "104ac0ca0f00c8a2a2e883180e2ffa0b", "91149162032f616c67fc559fd962522f", "a5b4ad32a8fce621a6df682b95102c44", "e93de9bfae6d49923c5f36143f59e58d", "22195d1955cd15377df6da5d41ba6b28", "d1b1ea6f182c3e66a971ad31292a74ea", "4540fb27695e844ecaca8831bddaf43b", "e511ebba0df38770bb57145769145c8a", "bcb6b0c87d6e3771ea9c73e73b7a8a20", "c8a9acbcabcff35d9930470a283722e0", "495cf5e8e20dabe1dabaf1137b54c8db", "82ca3acc51d989f18316c27de172dc7e", "5071266c6673b2cc03d8a0e5440f5ed3", "228325eeef765086ee5eb61a1c175017", "852dfb89648e509087f300a84138cf96", "a2bc45aeb2793a4d836b934e0215e990", "51e277b8ffa3275574b3dce4f586fffc", "4f62147c61cd74c38c3795fc2de52123", "b473f7a0e69c5e752a4b2734d8f1b701", "71d3090ab2c6179a17efbab30e2647a8", "520e35a7578d462615376ac220cc2c97", "2472677d6f77445036b9efe2192b99ae", "6a892da1ffc8f57240ae6b50e1294f9f", "349e13b653b3fbb0d76b0f927724dd9b", "2f70a7169e7202f6e4a537009ea5c380", "ab96530404b05d8dbb3ba8a523dd7df7", "54496c3c6f330583cbab37cf330a7922", "cb50daa747208b495de3122c03fb640e", "6974aa7b4657b35c6a82d27fc15f0e0c", "c6c8bf5c14df74c222143998e6870bfd", "befe802ef3147483686e1b2a4ae9cb4a", "fe53e6f211ec9fac9cff8bd429f2059b", "2093a6be6682b014b43fbaaf6b6dc5d9", "ce051712715325120c234541e771e140", "d6baebc3444fd6b464d193c95cf88ff3", "7528b2ded2e404bd201b2717534bc846", "a494bc8fa78786d07ae46a69830ca0e5", "7fa9469560e43964b3ee20d69f2f683b", "cec28ee3726434a69b4dfdb4097bcae2", "17dd60fd85ba3ff88bf95f968c059996", "3814f719bb6f06c767a00cb330c22b99", "50c95c606def3d3672ba1ec1e459e097", "2d1ba547ef1f73d6d8980cbbc692c9ec", "e5fca2c6c914d8b5f11675e3de53627d", "cb670d4f63b6737d84b7903c9092df84", "f16f30c60f3f4046f284a1bf11a2bd2f", "0cd34587cd42da65f30559becf0bd014", "9a0a62eb09c4508ea5e5cfce949ddd5e", "ccbf6068877b8c8662d931a85c259cfe", "26b7d32896e866a3ae2c926b6a266f06", "beb5def015d681f82aec3b9cb796a605", "de08ae6df1500b87618749b422cb4973", "a9d5b290bdcc3576283c2db7109298df", "088a95d705747a28c18168f6c03d80ee", "df55888cf445a592dd7264f81d3f3540", "086008c151d172f78634d5690eda3b78", "3b97bb503ee7a670ddb86518e5a9fec8", "42be339bf966dcc1364a990791902a7f", "a0b2073a349aeacef8bdaee6af0401c6", "6275aa54a71685dfab9b53b55c9d7900", "9298f5cc3e9db500cb3a27099912f1db", "ab23cb858436aeed931eadc6add31df1", "13b9208b59a76627ec51d8f23d619cc1", "0bf00763e659bd099374fdedc8953c65", "25ceda796b46467a1235c1e5ddc1991d", "41d155aec50e0caa77430079425f5e88", "1eecdd68ddedef5e313f76ba2611567e", "dccf575c9de2d28e89fcd0d82a80680e", "3ce3abcab8abad837e7530743efce442", "b9df6e374f9560ee152a1c393357d784", "131afe0d8ba62548ec5ecd804d2ee9cc", "c79871554b0b62e36a40cca8092f5bde", "6eb0256cbc5a65995ab119fe950c6b06", "49ebca44572e4c856e6d5a9afd25ae46", "06f400289620520a51ce906009de6b16", "8d8bfde7e97cc18420c54e2b35144e99", "b5e51bc114bc8ca5325c51c037b3cd5a", "004993f143e805a0feeb85bf69263fa5", "ec5c08abb37dc85188f478f55d4e3940", "c84b87171d8d5b3cd1e7f1e9ab8a8b5f", "c4b94147e3441f56af18a6b31a7b790c", "a93b0136344b490b540664fe603e1a40", "5ea571e70037fb1bd78771990e2e9b2e", "0fa36ba567b253008a0e30fb2e068dd2", "67906c6e38da8daf41d683961cf30e11", "23599107d77bf80107b7ed22439a32c7", "809ef9d21dc60e58cf1d18cc421015f5", "bc970b300b4bd849f010656fe6bedfc4", "d1a17283aae9114ee96beaffdd5b8be5", "94950d24b9248e68b9944bb9be2ca592", "d31c7a66622b98c6c2095e2352eb2a2b", "2b00ada57516d5e085adba30c82f85c2", "549581e9a02f2f62f40e23e185249a01", "df7dbf1f86b6c8f9aa52fc61090c836e", "b94ffa5d6bedb9a9a4618e589c145f7f", "24ffff30c03fabfe7f0d36765a8c86cc", "5ed44b80bcc55d434c4979eac1199909", "ec3e438f684e91e92792a4ae41b4fb96", "f083fb4eb5267360a94e7874033f69df", "c87d9520717725287fd57fe1d8b90b7f", "a4075e6095a6980d60e57f24a0ec9ec6", "ca0b6c7840f8cd31b10b984828e26a49", "90190d55da7e95c471904d1c76c32ecf", "f2c29ed0c8908fcac84101149d73fe83", "07e1162a1ff45c8c5fcb9a1b3ce879a8", "604cdc115c59b360c4c5fa80e9f19be3", "fa1f73cb9c758712f2961693f2cd2f37", "f4f0037d1aca20096b5013c906b0f622", "4491a3f7fa071305cdd8de946ef1d549", "af6c8d00abd19dd13bacac85f8b0f7ad", "a97d7fe973a894ccdc3b8852f3845de4", "a87f56b175a466980d80596e7642c887", "7a3858aa8cf65b9c4f2bcc944005ffd8", "8a67de812b728888799441e301683ae5", "dc868d57a023ba00a5186ef31b8accce", "3c216a71d72a9d377dfec1b4b0b240aa", "0c75144c808da2b0deabd20c24a940e8", "a0a2bf1b2735d2188e85e5f70b7067d0", "8101f8e15655f66a63a057e1443681ed", "53e95b634d3ef22dbb8ae75ed25b8ea6", "09cee5a859e7ac42140fcbfe6d6115c5", "b6ef01e8f08a97ad7722814795560483", "f582d6f62f30bed81f0bb0aa6157ad81", "ee4260c08a39237ccfd1f532addedd6c", "ea41051104c557b322a3a60642abb96c"];
        if ($what == "key") {
            if (!($return = array_search($value, $hashs))) {
                $return = 0;
            }
        } else {
            if ($what == "var") {
                $value = $value + 1;
                if (!empty($hashs[$value])) {
                    $return = $hashs[$value];
                }
            }
        }
        return $return;
    }
    public function wtf($data)
    {
    }
}

?>