<?php
class ModelCatalogSimplePars extends Model {

############################################################################################
############################################################################################
#						Создание проктов. Начальные настройки
############################################################################################
############################################################################################

#данные для главной страницы. INDEX
public function getIndexPage(){

	$data = [];
	//Получаем массив всех проектов
	$pars_settings = $this->getAllProject();
	$cron_main = $this->getCronMain();

	foreach($pars_settings as $value){
    //отправляем запрос на создание базы данных
    $this->madeBd($value['dn_id']);
  }

  //Кнопка включения выключения крона
	if($cron_main['permit'] == 'run') {
		$data['cron_button']['text'] = 'CRON включен => Отключить';
		$data['cron_button']['class'] = 'btn btn-warning';
	} else {
		$data['cron_button']['text'] = 'CRON отключен => Включить';
		$data['cron_button']['class'] = 'btn btn-default';
	}
  
  if($cron_main['permit'] == 'run' && $cron_main['work'] > 0){
  	$data['cron_text'] = '<span class="text-danger"><b>Внимание!!! CRON выполняет задание, если вы хотите приступить к ручному парсингу желательно отключить cron ===></b></span>';
  }else{
  	$data['cron_text'] = '';
  }

  $dirs = [];
  $dirs[] = DIR_APPLICATION.'simplepars/cache_page/';
  $dirs[] = DIR_APPLICATION.'simplepars/cookie/';
  $dirs[] = DIR_APPLICATION.'simplepars/replace/';
  $dirs[] = DIR_APPLICATION.'simplepars/xml_page/';
  $dirs[] = DIR_APPLICATION.'simplepars/scripts';
  $dirs[] = DIR_APPLICATION.'uploads/';
  $dirs[] = DIR_IMAGE.'catalog/SPshow/';
  foreach($dirs as $dir){
  	if(!is_dir($dir)){ mkdir($dir, 0755, true); }
  }

  $vers_op = $this->checkEngine();
  $this->db->query("UPDATE ".DB_PREFIX."pars_setting SET vers_op='".$vers_op."'");


  $data['pars_settings'] = $pars_settings;
  $data['cron_permit'] = $cron_main['permit'];

  return $data;
}

#получение id доноров
public function getAllProject(){
	$pars_settings = $this->db->query("SELECT `dn_id`, `dn_name` FROM ".DB_PREFIX."pars_setting ORDER BY dn_id ASC")->rows;
	return $pars_settings;
}

//создаем все базы данных которых нехватает.
public function madeBd($dn_id){
	//проверяем таблицу браузер
	$browser = $this->db->query("SELECT id FROM `".DB_PREFIX."pars_browser` WHERE `dn_id`=".(int)$dn_id);
	if($browser->num_rows == 0){
		$this->createDbBrowser($dn_id);
	}

	$pars_xml = $this->db->query("SELECT id FROM `".DB_PREFIX."pars_xml` WHERE `dn_id`=".(int)$dn_id);
	if($pars_xml->num_rows ==0){
		$this->createDbXml($dn_id);
	}

}

#Создание донора.
public function DnAdd($data){
	$data = htmlspecialchars($data);

	if(!empty($data)){
		//определяем версию движка
		$engine = $this->checkEngine();

		$this->db->query("INSERT INTO `" . DB_PREFIX . "pars_setting` SET
			`dn_name` ='".$this->db->escape($data)."',
			`vers_op`='".$this->db->escape($engine)."'");
		$dn_id = $this->db->getLastId();

		#Создаем таблицу Prsetup
		$this->createDbPrsetup($dn_id);
		#Создаем таблицу браузера
		$this->createDbBrowser($dn_id);

		//проверяем есть ли группа атрибутов. Если нет создаем.
		$attr_group_id = $this->db->query("SELECT attribute_group_id FROM `" . DB_PREFIX . "attribute_group_description` WHERE attribute_group_id =1");
		if($attr_group_id->num_rows == 0){
			$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group` SET `attribute_group_id`=1, `sort_order` = 0");

			//получаем списко используемых языков
			$lang = $this->db->query("SELECT * FROM ".DB_PREFIX."language ORDER BY `language_id` ASC");
			$langs = $lang->rows;
			//Для всех языков прописываем группу.
			foreach ($langs as $key => $lang) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group_description` SET `attribute_group_id` =1, `language_id`=".(int)$lang['language_id'].",`name`=''");
			}

		}
	}else{
		$this->session->data['error'] = 'Не задано имя проекта';
	}
}

#Удаление донора
public function DnDel($data){
	
	foreach($data as $dn_id){

		//получаем id всех границ парсинга для удаления их файлов пред просмотра.
		$param_id = $this->db->query("SELECT `id` FROM ".DB_PREFIX."pars_param WHERE `dn_id` =".(int)$dn_id);
		$param_id = $param_id->rows;
		
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_setting` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_sen_link` WHERE `dn_id` =".(int)$dn_id);
    $this->db->query("DELETE FROM `" . DB_PREFIX . "pars_link` WHERE `dn_id` =".(int)$dn_id);
    $this->db->query("DELETE FROM `" . DB_PREFIX . "pars_param` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_createcsv` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_prsetup` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_replace` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_browser` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_proxy_list` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_cron_list` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_link_list` WHERE `dn_id` =".(int)$dn_id);
		$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_xml` WHERE `dn_id` =".(int)$dn_id);


		//Удаляем лог файл
		$path_log = DIR_LOGS."simplepars_id-".$dn_id.".log";
		if (file_exists($path_log)) {
			unlink($path_log);
		}
		
		$path_cookies = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
		if (file_exists($path_cookies)) {
			unlink($path_cookies);
		}
		
		//Удаление файлов кеша, поиск замены.
		foreach ($param_id as $param) {
			$file_param = DIR_APPLICATION.'simplepars/replace/'.$param['id'];
			//Проверяем есть ли такой фаил
			if (file_exists($file_param.'_input_arr.txt')) { unlink($file_param.'_input_arr.txt'); }
			if (file_exists($file_param.'_input_text.txt')) { unlink($file_param.'_input_text.txt'); }
			if (file_exists($file_param.'_output.txt')) { unlink($file_param.'_output.txt'); }
		}

		//Удаление кеша страниц донора.
		$this->urlDelAllCache($dn_id);
		$cache_path = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id;
		@rmdir($cache_path);
		
		//Удаление файлов xml
		$this->delAllPageXml($dn_id);
		$xml_path = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id;
		@rmdir($xml_path);
	}
}

############################################################################################
############################################################################################
#						Страница сбора ссылок, и работа ссылками.
############################################################################################
############################################################################################

//Добвляем ссылки в очередь парсинга
public function AddParsSenLink($link='', $dn_id){
	$link = $this->ClearLink($link);
	$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "pars_sen_link` SET
		`link` ='".$this->db->escape($link)."',
		`key_md5` ='".md5($dn_id.$link)."',
		`dn_id`=".(int)$dn_id);
}

//Добвляем ссылки в выдачу
public function AddParsLink($link='', $dn_id){
	$link = $this->ClearLink($link);
	$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "pars_link` SET
		`link` ='".$this->db->escape($link)."',
		`key_md5` ='".md5($dn_id.$link)."',
		`dn_id`=".(int)$dn_id
	);

}

//Удалить ссылки очереди
public function DelParsSenLink($dn_id){
	$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_sen_link` WHERE `dn_id` =".(int)$dn_id);
}

//Удалить ссылки выдачи
public function DelParsLink($dn_id){

	$this->db->query("DELETE FROM `" . DB_PREFIX . "pars_link` WHERE `dn_id` =".(int)$dn_id);
}

#Остановка парсинга сбора ссылок
public function StopParsLink($dn_id){
	$this->db->query("UPDATE `" . DB_PREFIX . "pars_setting` SET `pars_stop`=0 WHERE `dn_id`=".(int)$dn_id);
}

//пометить ссылки как непросканированные.
public function linksRestart($dn_id){
	$this->db->query("UPDATE `" . DB_PREFIX . "pars_link` SET `scan`=1 WHERE `dn_id`=".(int)$dn_id);
}

//пометить ссылки очереди как непросканированные.
public function linksSenRestart($dn_id){
	$this->db->query("UPDATE `" . DB_PREFIX . "pars_sen_link` SET `scan`=1 WHERE `dn_id`=".(int)$dn_id);
}

#Фунция очистки ссылок от всякого
public function ClearLink($link){
	$link = htmlspecialchars_decode(trim($link));
	return $link;
}

#Вывод содержимого страницы grab
public function ViemGrab($dn_id){
	#получаем все настройки донора
	$setting = $this->getSetting($dn_id);
	#получаем ссылки очереди сканиарования
	$round_links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 1 AND `dn_id`=".(int)$dn_id." ORDER BY id ASC LIMIT 0,".$setting['page_cou_link']);
	$round_links_count = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 1 AND `dn_id`=".(int)$dn_id);
	$round_links_count = $round_links_count->row['count'];

	#получаем количество просканированных ссылок, и очереди
	$count_finish_scan = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 0 AND `dn_id`=".(int)$dn_id);
	$count_finish_scan = $count_finish_scan->row['count'];

	#получаем ссылки выдачи
	$finish_links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id." ORDER BY id ASC LIMIT 0,".$setting['page_cou_link']);
	$finish_links_count = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id);
	$finish_links_count = $finish_links_count->row['count'];

	$browser = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_browser` WHERE dn_id=".(int)$dn_id);
	$viemgrab['browser'] = $browser->row;

	#Привожу в удобный вид для отображения
	$viemgrab['round_links_prepare'] = $round_links_count;
	$round_link = '';
	foreach ($round_links->rows as $key => $value) {
		if ($key == 0) { $round_link = $value['link'];	} elseif ($key == $setting['page_cou_link']) { break;} else { $round_link .= PHP_EOL.$value['link']; }
	}

	#Привожу в удобный вид для отображения
	$viemgrab['links_prepare'] = $finish_links_count;
	$finish_link = '';
	foreach ($finish_links->rows as $key => $value) {
		if ($key == 0) { $finish_link = $value['link'];	} elseif ($key == $setting['page_cou_link']) { break;} else { $finish_link .= PHP_EOL.$value['link']; }
	}

	//составляем в удобном виде для вывода границ парсинга
	$raund_param = explode('{!na!}', $setting['filter_round_param']);
	if(empty($raund_param[0])){
		$raund_param[0] = '';
	}
	if(empty($raund_param[1])){
		$raund_param[1] = '';
	}

	$link_param = explode('{!na!}', $setting['filter_link_param']);
	if(empty($link_param[0])){
		$link_param[0] = '';
	}
	if(empty($link_param[1])){
		$link_param[1] = '';
	}

	//пролизводим проверку есть ли дериктории
	$cache_dir = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/';
	if(!is_dir($cache_dir)){ mkdir($cache_dir, 0755, true); }

	$xml_dir = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/';
	if(!is_dir($xml_dir)){ mkdir($xml_dir, 0755, true); }

	$setting['link_param_start'] = $link_param[0];
	$setting['link_param_stop'] = $link_param[1];
	$setting['round_param_start'] = $raund_param[0];
	$setting['round_param_stop'] = $raund_param[1];

	if(!empty($setting['filter_round_rules'])){ 
		$setting['filter_round_rules'] = $this->madeRulesToPage($setting['filter_round_rules']);
	}

	if(!empty($setting['filter_link_rules'])){ 
		$setting['filter_link_rules'] = $this->madeRulesToPage($setting['filter_link_rules']);
	}

	#Составляю массв для рендеринга. num_rows
	$viemgrab['setting'] = $setting;
	$viemgrab['round_link'] = $round_link;

	$viemgrab['finish_link'] = $finish_link;
	$viemgrab['count_finish_scan'] = $count_finish_scan;
	$viemgrab['greb_cout_sen_link'] = $round_links_count;
	return $viemgrab;
}

#Повторная фильтрация списка.
public function UseNewFilter($dn_id, $who){
	//Получаем настройки.
	$setting = $this->getSetting($dn_id);

  if($who == 'filter_round'){
   $data_links = $this->db->query("SELECT `link` FROM `". DB_PREFIX ."pars_sen_link` WHERE dn_id=".(int)$dn_id);
  }elseif($who == 'filter_link') {
   $data_links = $this->db->query("SELECT `link` FROM `". DB_PREFIX ."pars_link` WHERE dn_id=".(int)$dn_id);
  }
  $data_links = $data_links->rows;
  
  if(!empty($data_links)){
   if($who == 'filter_round'){
    $this->DelParsSenLink($dn_id);
   }elseif($who == 'filter_link'){
    $this->DelParsLink($dn_id);
   }
   foreach($data_links as $var){
    $links[] = $var['link'];
   }
   $this->filterLink($links, $setting, $dn_id, $who);
  }
}

#Сохранение настроек сбора ссылок
public function SeveFormGrab($data, $dn_id){

	$data['dn_name'] = htmlspecialchars($data['dn_name']);
	if(empty($data['start_link'])){
		$data['start_link'] = '';
	}else{
		if(preg_match('#^http[s]?\:\/\/(.*)[.]#i', $data['start_link'])){
			$data['start_link'] = $this->ClearLink($data['start_link']);
		}else{
			$data['start_link'] = '';
			$this->session->data['error'] = ' Стартовая ссылка должна содержать протокол. http:// или https://';
		}
	}
	if(empty($data['page_cou_link'])){ $data['page_cou_link'] = 5000; }
	if(empty($data['filter_round_yes'])){	$data['filter_round_yes'] = ''; }
	if(empty($data['filter_round_no'])){ $data['filter_round_no'] = ''; }
	if(empty($data['filter_link_yes'])){ $data['filter_link_yes'] = ''; }
	if(empty($data['filter_link_no'])){	$data['filter_link_no'] = ''; }
	if(empty($data['filter_round_method'])){ $data['filter_round_method'] = 'or'; }
	if(empty($data['filter_link_method'])){	$data['filter_link_method'] = 'or'; }
	if(empty($data['pars_pause'])){	$data['pars_pause'] = 0; }
	if(empty($data['type_grab'])){ $data['type_grab'] = 1; }
	if(empty($data['thread'])){ $data['thread'] = 1; }
	if(empty($data['round_param_start'])){ $data['round_param_start'] = ''; }
	if(empty($data['round_param_stop'])){	$data['round_param_stop'] = ''; }
	if(empty($data['filter_round_depth'])){	$data['filter_round_depth'] = ''; }
	if(empty($data['filter_round_slash'])){	$data['filter_round_slash'] = 0; }
	if(empty($data['filter_round_domain'])){ $data['filter_round_domain'] = 0; }
	
	//Приобразовываем данные для записи правил поиск замены.
	if(empty($data['filter_round_rules'])){ 
		$data['filter_round_rules'] = ''; 
	}else{

		$data['filter_round_rules'] = $this->parseRulesToReplace(['rules' => $data['filter_round_rules']]);
		if(!empty($data['filter_round_rules'])){
			$data['filter_round_rules'] = json_encode($data['filter_round_rules']);
		}

	}

	if(empty($data['link_param_start'])){ $data['link_param_start'] = ''; }
	if(empty($data['link_param_stop'])){ $data['link_param_stop'] = ''; }
	if(empty($data['filter_link_depth'])){ $data['filter_link_depth'] = ''; }
	if(empty($data['filter_link_slash'])){ $data['filter_link_slash'] = 0; }
	if(empty($data['filter_link_domain'])){ $data['filter_link_domain'] = 0; }

	if(empty($data['filter_link_rules'])){ 
		$data['filter_link_rules'] = ''; 
	}else{

		$data['filter_link_rules'] = $this->parseRulesToReplace(['rules' => $data['filter_link_rules']]);
		if(!empty($data['filter_link_rules'])){
			$data['filter_link_rules'] = json_encode($data['filter_link_rules']);
		}

	}

	//собираем параметры парсинга в ссылках
	if(!empty($data['round_param_start']) || !empty($data['round_param_stop'])){
		$filter_round_param = $data['round_param_start'].'{!na!}'.$data['round_param_stop'];
	}else{
		$filter_round_param = '';
	}

	if(!empty($data['link_param_start']) || !empty($data['link_param_stop'])){
		$filter_link_param = $data['link_param_start'].'{!na!}'.$data['link_param_stop'];
	}else{
		$filter_link_param = '';
	}

	$this->db->query("UPDATE `" . DB_PREFIX . "pars_setting` SET
		`dn_name`='".$this->db->escape($data['dn_name'])."',
		`start_link`='".$this->db->escape($data['start_link'])."',
		`page_cou_link`='".(int)$data['page_cou_link']."',
		`filter_round_yes`='".$this->db->escape($data['filter_round_yes'])."',
		`filter_round_no`='".$this->db->escape($data['filter_round_no'])."',
		`filter_round_method`='".$this->db->escape($data['filter_round_method'])."',
		`filter_round_param`='".$this->db->escape($filter_round_param)."',
		`filter_round_depth`='".$this->db->escape($data['filter_round_depth'])."',
		`filter_round_slash`='".$this->db->escape($data['filter_round_slash'])."',
		`filter_round_domain`='".$this->db->escape($data['filter_round_domain'])."',
		`filter_round_rules`='".$this->db->escape($data['filter_round_rules'])."',
		`filter_link_yes`='".$this->db->escape($data['filter_link_yes'])."',
		`filter_link_no`='".$this->db->escape($data['filter_link_no'])."',
		`filter_link_method`='".$data['filter_link_method']."',
		`filter_link_param`='".$this->db->escape($filter_link_param)."',
		`filter_link_depth`='".$this->db->escape($data['filter_link_depth'])."',
		`filter_link_slash`='".$this->db->escape($data['filter_link_slash'])."',
		`filter_link_domain`='".$this->db->escape($data['filter_link_domain'])."',
		`filter_link_rules`='".$this->db->escape($data['filter_link_rules'])."',
		`type_grab`='".$this->db->escape($data['type_grab'])."',
		`thread`='".$this->db->escape($data['thread'])."',
		`pars_pause`='".$this->db->escape($data['pars_pause'])."'
		WHERE `dn_id`=".(int)$dn_id);

	//настройки браузера.
  $this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET cache_page = ".(int)$data['cache_page']." WHERE dn_id =".(int)$dn_id);

}

//Фунция для парсинга ссылок с карты саята под архивом.
public function getPageFromGzip($urls, $dn_id){

  $datas = [];
  //перебираем пул ссылок и делаем выгрузку
	foreach($urls as $url){
		
		$url = $this->urlEncoding($url);
		
		$content = '';
		$resurs = '';
		
		@$resurs = gzopen($url, 'r');
		
		if(!empty($resurs)){
	 		$content = gzread($resurs, 100000000);
		}


		//проверяем есть ли такой файл.
	  if (!empty($content)) {
	  
	  	//имитация нормального ответа курла.
	  	$data['url'] = $url;
		  $data['content_type'] = 'text/html; charset=utf-8';
		  $data['http_code'] = '200';
			$data['errno'] = '0';
		  $data['errmsg'] = '';
		  $data['sp_log'] = 'log_gzopen';
		  $data['browser'] = [];
	  	$data['content'] = $content;
	  
	  }else{

	  	//имитация ошибочного ответа.
	  	$data['url'] = $url;
		  $data['content_type'] = 'text/html; charset=utf-8';
		  $data['http_code'] = '404';
			$data['errno'] = '1001';
		  $data['errmsg'] = 'Модуль не получил данные с сайта донора, при парсинга sitemap + gz';
		  $data['sp_log'] = 'log_gzopen';
		  $data['browser'] = [];
	  	$data['content'] = '';
	  
	  }

	  $datas[$url] = $data;

	}

  return $datas;
}

//Входная фунция на парсинг.
public function grabControl($i, $dn_id){
	//Получаем настройки
	$setting = $this->getSetting($dn_id);
	$browser = $this->getBrowserToCurl($dn_id);
	$links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 1 AND `dn_id`=".(int)$dn_id." ORDER BY id ASC LIMIT 0,5");
	//Получаем список заданий для выполнения
	if($setting['scripts_permit']){
		$script_tasks = $this->scriptGetTasksToExe($dn_id);
		$setting['thread'] = 1;#Если включено использование скриптов модуль работает в одном потоке. 
	}

	$ans = [];

	if($links->num_rows > 0){
		
		//Блак многопоточности. берем нужное количество ссылок.
		$urls = [];
		foreach($links->rows as $key => $url){
			if($key < $setting['thread']){ $urls[] = $url['link']; } else { break; }
		}

	}else{

		//если нет ссылок в очереди и это первый запрос.
		if($i == 1){
			$urls[] = $setting['start_link'];
		}else{
			$this->answjs('link_end','Сбор ссылок завершен.');
		}

	}

 	//Отправка данных на собственные скрипты. 
	if(!empty($script_tasks)){

		$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
		$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
		$setting = $script_data['setting']; 
		$browser = $script_data['browser'];
		$urls = $script_data['urls'];
		unset($script_data);
	
	}

	//Если все же мы дошли до сюда, то парсинг будет.
	//определяем через что будем парсить, cURL или архиватор.
	if ($setting['type_grab'] == 3) {
	
		//парсинг через архиватор
		$datas = $this->getPageFromGzip($urls, $dn_id);

		//Далее разбираем данные из мульти курла и делаем все нужные записи.
		foreach($datas as $key => $data){
			//производим зяпись лога курл, и паролельно проверяем нужно ли делать дальнейшую работу.
			$curl_error = $this->sentLogMultiCurl($data ,$dn_id);

			#помечаем ссылку как отсканированная
	  	$this->db->query("UPDATE ".DB_PREFIX."pars_sen_link SET scan=0 WHERE link='".$this->db->escape($data['url'])."' AND dn_id=".$dn_id);

			//если пришла ошибка заканчиваем эту итерацию и переходим к следующей.
			if($curl_error['error']){
				//если из ответа сервера страница не прошла проверку удаляем ее из массива. 
				unset($datas[$key]);
				continue;
			}
		}

	}else{
		//делаем мульти запрос
		$datas = $this->requestConstructor(0, $urls, $dn_id, $browser, $setting, 0);
	}

  foreach($datas as $key => $data){		
		//передаем на обработку данные
		$this->ParsLink($data, $setting, $dn_id);
	}

	//Получаем количество ссылок для показа. Ненужная нагрузка но всем нравится.
	$ans['sen_count'] = $this->db->query("SELECT COUNT(*) as sen_count FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 0 AND `dn_id`=".(int)$dn_id);
	$ans['sen_count'] = $ans['sen_count']->row['sen_count'];

	$ans['sen_count_no'] = $this->db->query("SELECT COUNT(*) as sen_count_no FROM " . DB_PREFIX . "pars_sen_link WHERE scan = 1 AND `dn_id`=".(int)$dn_id);
	$ans['sen_count_no'] = $ans['sen_count_no']->row['sen_count_no'];

	$ans['link_count'] = $this->db->query("SELECT COUNT(*) as link_count FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id);
	$ans['link_count'] = $ans['link_count']->row['link_count'];

	#пауза парсинга
  $this->timeSleep($setting['pars_pause']);
	$this->answjs('go', 'Производится сбор ссылок', $ans);

}

//Основня фунция парсинга ссылок
public function ParsLink($data, $setting, $dn_id){
	/////////////////////////////////////////////
	// Определяем тип сбора ссылок.
	// 1) обычный сайт. 2-3) Sitemap стандартный. 4) Xml <url> 
	////////////////////////////////////////////
	if ($setting['type_grab'] == 1) {
		$reg_url = '#<a[^>]*? href=["\']?([^"\'>]+)["\']?#s';
		#$reg_url = '#<a[^>]+?href=["\']?([^"\'>]+)["\']?#s';
		#$reg_url = '#<a.+?href=["\']?([^"\'>]+)["\']?#s';
	}elseif($setting['type_grab'] < 3){
		$reg_url = '#<loc>(.*?)<\/loc>#s';
	}else{
		$reg_url = '#<url>(.*?)</url>#s';
	}

	//передаем нужные данные.
	$url = $data['url'];
	$pre_html = $data['content'];
	$who = 'all'; #Тип фильтра по умолчанию.

	//Проверяем для кого фильтруем. Если нет области внутри которой искать ссылки, тогда для всех
	//all - для двух списков; filter_round_param - для очереди; filter_link_param - для выдачи
	//
	//Если пусто то all
	if (empty($setting['filter_round_param'].$setting['filter_link_param'])) {

		//Делим парсинг ссылок на отдельные списки
		preg_match_all($reg_url, $pre_html, $data_links);
		#$this->wtfarrey($data_links);

		if(!empty($data_links)){
			$data_link = $this->madeTidyLinks($data_links, $url);
			$this->filterLink($data_link, $setting, $dn_id, $who);
		}

	} else {
		//Есть ограничения по области.

		//
		//Для очереди.
		//
		$who = 'filter_round';
		//Проверяем если параметры париснга
		if(!empty($setting['filter_round_param'])){

			//Получаем параметры париснга очереди.
			$param_raund = explode('{!na!}', $setting['filter_round_param']);
			
			//Если нет одной из границ добавляем начало либо конец строки.
			if(empty($param_raund[0])){ $param_raund[0] = '^'; } else { $param_raund[0] = preg_quote(htmlspecialchars_decode($param_raund[0]), '#');}
			if(empty($param_raund[1])){ $param_raund[1] = '$'; } else { $param_raund[1] = preg_quote(htmlspecialchars_decode($param_raund[1]), '#');}

			//выполняе запрос на получение куска кода
			$reg = '#'. $param_raund[0] .'(.*?)'. $param_raund[1] .'#su';
			preg_match($reg, $pre_html, $html);

			//проверяем что бы был хоть какой то код.
			if (!empty($html[1])) {
				//выполняем сбор ссылок внутри вырезанного кода.
				preg_match_all($reg_url, $html[1], $data_links);
				#$this->wtfarrey($data_links);
				if(!empty($data_links)){
					//Приводим в порядок ссылки
					$data_link = $this->madeTidyLinks($data_links, $url);

					//отправляем на фильтрацию для очереди сканирования.
					$this->filterLink($data_link, $setting, $dn_id, $who);
				}
			}

		}else{

			preg_match_all($reg_url, $pre_html, $data_links);
			if(!empty($data_links)){
				//Приводим в порядок ссылки
				$data_link = $this->madeTidyLinks($data_links, $url);

				//отправляем на фильтрацию для очереди сканирования.
				$this->filterLink($data_link, $setting, $dn_id, $who);
			}
		}

		//
		//Для выдачи.
		//
		$who = 'filter_link';
		unset($html);
		//Проверяем если параметры париснга
		if(!empty($setting['filter_link_param'])){

			//Получаем параметры париснга выдачи
			$param_link = explode('{!na!}', $setting['filter_link_param']);

			//Если нет одной из границ добавляем начало либо конец строки.
			if(empty($param_link[0])){ $param_link[0] = '^'; } else { $param_link[0] = preg_quote(htmlspecialchars_decode($param_link[0]), '#');}
			if(empty($param_link[1])){ $param_link[1] = '$'; } else { $param_link[1] = preg_quote(htmlspecialchars_decode($param_link[1]), '#');}

			//выполняе запрос на получение куска кода
			$reg = '#'. $param_link[0] .'(.*?)'. $param_link[1] .'#su';
			preg_match($reg, $pre_html, $html);
			
			//проверяем что бы был хоть какой то код.
			if (!empty($html[1])) {
				//выполняем сбор ссылок внутри вырезанного кода.
				preg_match_all($reg_url, $html[1], $data_links);

				if(!empty($data_links)){
					//Приводим в порядок ссылки
					$data_link = $this->madeTidyLinks($data_links, $url);

					//отправляем на фильтрацию для очереди сканирования.
					$this->filterLink($data_link, $setting, $dn_id, $who);
				}
			}

		}else{

			preg_match_all($reg_url, $pre_html, $data_links);
			if(!empty($data_links)){
				//Приводим в порядок ссылки
				$data_link = $this->madeTidyLinks($data_links, $url);

				//отправляем на фильтрацию для очереди сканирования.
				$this->filterLink($data_link, $setting, $dn_id, $who);
			}
		}

	}

}

//фунция загрузки ссылок с файла.
public function uploadLinkFromFile($data, $dn_id, $who=2){
	//Перебираем файл и составляем массив.
	$links_file = explode(PHP_EOL, $data);

	//перебираем массив оставляем только ссылки.
	$links = [];
	foreach ($links_file as $key => $value) {
		if(preg_match('#^http#', $value)){ $links[] = $value; }
	}

	#Если в файле нету ссылок то завершаем работу. 
	if(empty($links)){
		$this->session->data['error'] = ' В файле нету ссылок для добавления.';
		return;
	}

	//получаем настройки и отправляем ссылки на запись в базу.
	$setting = $this->getSetting($dn_id);
	if ($who == 1) { $who = 'filter_link';} else { $who = 'filter_round'; }
	$this->filterLink($links, $setting, $dn_id, $who);
	$this->session->data['success'] = ' Все ссылки что СООТВЕТСТВОВАЛИ ВАШИМ НАСТРОЙКАМ были добавлены в список.';

}

//Фильтрация ссылок.
//ВНИМАНИЕ !!! ссылки должны приходить полные c http://
public function filterLink($links, $setting, $dn_id, $who = 'all'){
	#$this->wtfarrey($links);

	if ($who == 'all' || $who == 'filter_round') {
		$filter_round_yes = preg_split('#\n|\r\n|\r#', $setting['filter_round_yes']);
		$filter_round_no = preg_split('#\n|\r\n|\r#', $setting['filter_round_no']);
		$filter_round_method = $setting['filter_round_method'];
		$link_round = [];
	}

	if ($who == 'all' || $who == 'filter_link') {
		$filter_link_yes = preg_split('#\n|\r\n|\r#', $setting['filter_link_yes']);
		$filter_link_no = preg_split('#\n|\r\n|\r#', $setting['filter_link_no']);
		$filter_link_method = $setting['filter_link_method'];
		$link_finish = [];
	}


	//приводим в порядок фильтры.
	if ($who == 'all' || $who == 'filter_round') {

		foreach ($filter_round_yes as $key => $value) {
			if(!empty(trim($value))){
				$filter_round_yes[$key] = $this->modFilterLinkRules($value);
			}else{
				unset($filter_round_yes[$key]);
			}
		}

		foreach ($filter_round_no as $key => $value) {
			if(!empty(trim($value))){
				$filter_round_no[$key] = $this->modFilterLinkRules($value);;
			}else{
				unset($filter_round_no[$key]);
			}
		}

	}

	if ($who == 'all' || $who == 'filter_link') {
		
		foreach ($filter_link_yes as $key => $value) {
			if(!empty(trim($value))){
				$filter_link_yes[$key] = $this->modFilterLinkRules($value);
			}else{
				unset($filter_link_yes[$key]);
			}
		}

		foreach ($filter_link_no as $key => $value) {
			if(!empty(trim($value))){
				$filter_link_no[$key] = $this->modFilterLinkRules($value);
			}else{
				unset($filter_link_no[$key]);
			}
		}

	}

	//подготовка правил поиск замены.
	$setting['filter_round_rules'] = json_decode($setting['filter_round_rules']);
	$setting['filter_link_rules'] = json_decode($setting['filter_link_rules']);

	//
	//Производим фильтрацию.
	//
	if(!empty($links)){

		// Формирую значение для проверки домена. проверяем нужно ли делать проверку доменного имени, если да то подготавливаемдомен
		$domain = []; #По умолчанию создаем такую переменную. Что бы передавать в поиск замену.
		if (($setting['filter_round_domain'] || $setting['filter_link_domain']) && !empty($setting['start_link'])) {
			$domain = parse_url($setting['start_link']);
			#$this->wtfarrey($domain);
			//Специально подготавливаю домен что бы определить по нему внутренная или внешнаяя ссылка. Без регулярных выражений
			$domain['host'] = '//'.$domain['host'];
			#$this->wtfarrey($domain);
		}
		#$this->wtfarrey($domain);
		foreach($links as $link){ 
			$link = htmlspecialchars_decode($link);

			//////////////////////////////////////////////
			//Для очереди
			//////////////////////////////////////////////

			if ($who == 'all' || $who == 'filter_round') {
				$permit = 1; #допуск к выполнению фильтров.
				//Пороверяем что делать с слешем
				// 0 - не важно
				// 1 - слеш в конце ссылки
				// 2 - только без слеша
				if ($setting['filter_round_slash'] == 1 && substr($link, -1) != "/") { $permit = 0; }
				if ($setting['filter_round_slash'] == 2 && substr($link, -1) == "/") { $permit = 0; }

				//Уровень вложенности
				if (!empty($setting['filter_round_depth'])) {

					//получаем уровни вложенности в ссылке
					$link_depth = count(array_diff(explode("/", $link), array(''))) - 1;
					//Получаем параметры вложенности.
					$depth = explode('-', $setting['filter_round_depth']);

					if (!isset($depth[1]) || ((int)$depth[1] == 0)) {
						//Это не диапазон
						$depth[0] = (int)$depth[0];
						//основная проверка
						if ($link_depth != $depth[0]) { $permit = 0;}

					}else{

						//Диапазон
						$depth[0] = (int)$depth[0];
						$depth[1] = (int)$depth[1];
						//основная проверка
						if ($link_depth < $depth[0] || $link_depth > $depth[1]) { $permit = 0;}
					}

				}

				//Проверяем доменное имя.
				// 0- Внутренние и внешние ссылки
        // 1 - Только внутренние ссылки
        // 2 - Только внешние ссылки
				if ($setting['filter_round_domain'] == 1 && !empty($setting['start_link'])){ 

					if(stripos($link, $domain['host']) === false){ $permit = 0; }

				}elseif ($setting['filter_round_domain'] == 2){ 

					if(stripos($link, $domain['host'])){ $permit = 0; }

				}

				#$this->wtfarrey($permit);
				if ($permit) {
					//Проверяем есть ли фильтры
					if(!empty($filter_round_yes)){

						if ($filter_round_method == 'or') {

							foreach ($filter_round_yes as $filter) {
								#$reg = '#'.preg_quote($filter, '#').'#';
								if(preg_match($filter, $link)){
									$go_round = $link;
									break;
								}
							}

						} elseif ($filter_round_method == 'and') {

							$link_tmp = $link;
							foreach ($filter_round_yes as $filter) {
								#$reg = '#'.preg_quote($filter, '#').'#';
								if(!preg_match($filter, $link_tmp)){
									unset($link_tmp);
									break;
								}
							}
							//Если все фильтры совпали записуем в массив
							if(!empty($link_tmp)) { $go_round = $link_tmp; }

						}

	  			} else {
	  				$go_round = $link;
	  			}

  			}

			}

			//////////////////////////////////////////////
			//Для выдачи
			/////////////////////////////////////////////

			if ($who == 'all' || $who == 'filter_link') {

				$permit = 1; #допуск к выполнению фильтров.
				//Пороверяем что делать с слешем
				// 0 - не важно
				// 1 - слеш в конце ссылки
				// 2 - только без слеша
				if ($setting['filter_link_slash'] == 1 && substr($link, -1) != "/") { $permit = 0; }
				if ($setting['filter_link_slash'] == 2 && substr($link, -1) == "/") { $permit = 0; }

				//Уровень вложенности
				if (!empty($setting['filter_link_depth'])) {

					//получаем уровни вложенности в ссылке
					$link_depth = count(array_diff(explode("/", $link), array(''))) - 1;
					//Получаем параметры вложенности.
					$depth = explode('-', $setting['filter_link_depth']);

					if (!isset($depth[1]) || ((int)$depth[1] == 0)) {
						//Это не диапазон
						$depth[0] = (int)$depth[0];
						//основная проверка
						if ($link_depth != $depth[0]) { $permit = 0;}

					}else{
						
						//Диапазон
						$depth[0] = (int)$depth[0];
						$depth[1] = (int)$depth[1];
						//основная проверка
						if ($link_depth < $depth[0] || $link_depth > $depth[1]) { $permit = 0;}
					}

				}
				
				//Проверяем доменное имя.
				// 0- Внутренние и внешние ссылки
        // 1 - Только внутренние ссылки
        // 2 - Только внешние ссылки
				if ($setting['filter_link_domain'] == 1 && !empty($setting['start_link'])){
					if(stripos($link, $domain['host']) === false){ $permit = 0;}
				}elseif ($setting['filter_link_domain'] == 2 && !empty($setting['start_link'])){ 
					if(stripos($link, $domain['host'])){ $permit = 0; }
				}
				#$this->wtfarrey($permit);
				if ($permit) {
					//если фильтры не пустые
	  			if(!empty($filter_link_yes)){

						if ($filter_link_method == 'or') {

							foreach ($filter_link_yes as $filter) {
								#$reg = '#'.preg_quote($filter, '#').'#';
								if(preg_match($filter, $link)){
									#$this->wtfarrey($link);
									$go_finish = $link;
									break;
								}
							}

						} elseif ($filter_link_method == 'and') {

							$link_tmp = $link;
							foreach ($filter_link_yes as $filter) {
								#$reg = '#'.preg_quote($filter, '#').'#';
								if(!preg_match($filter, $link_tmp)){
									unset($link_tmp);
									break;
								}
							}
							//Если все фильтры совпали записуем в массив
							if(!empty($link_tmp)) { $go_finish = $link_tmp; }

						}

					} else {
						$go_finish = $link;
					}
				}

			}
			
			//Конец положительных фильтров
			//Начало фильтров отрицания.

			///////////////////////////////////////////////////////
			//Очередь
			///////////////////////////////////////////////////////

			if ($who == 'all' || $who == 'filter_round') {
				//Проверяем есть ли ссылки вообше
				if (!empty($go_round)) {

					$link_temp = $go_round;
					if (!empty($filter_round_no)) {
						#$this->wtfarrey($filter_round_no);
						//производим проверку
						foreach ($filter_round_no as $filter) {
							#$reg = '#'.preg_quote($filter, '#').'#';
							if(preg_match($filter, $link_temp)){
								unset($link_temp);
								break;
							}
						}

						if (!empty($link_temp)) { 
							$link_round[] = $this->madeFindReplaceToUrl($link_temp, $setting['filter_round_rules'], $domain, $setting);
						}

					} else {
						$link_round[] = $this->madeFindReplaceToUrl($link_temp, $setting['filter_round_rules'], $domain, $setting);
					}
				}
			}
			///////////////////////////////////////////////////////
			//Выдача
			///////////////////////////////////////////////////////

			if ($who == 'all' || $who == 'filter_link') {
				//Проверяем есть ли ссылки вообше
				if (!empty($go_finish)) {
					$link_temp = $go_finish;
					if (!empty($filter_link_no)) {
						//производим проверку
						foreach ($filter_link_no as $filter) {
							#  '#'.$filter.'#';
							if(preg_match($filter, $link_temp)){
								unset($link_temp);
								break;
							}
						}

						if (!empty($link_temp)) { 
							$link_finish[] = $this->madeFindReplaceToUrl($link_temp, $setting['filter_link_rules'], $domain, $setting);
						}

					} else {
						//отправляем на поиск замену, перед добавлением. А так же проверяем что бы после этого не была пустота.
						$link_finish[] = $this->madeFindReplaceToUrl($link_temp, $setting['filter_link_rules'], $domain, $setting);
					}
				}
			}
			//В конце цикла фильтрации удаляем.
			unset($go_round);
			unset($go_finish);
		}

	}

	//Для повторной фильтрации проверка.
	if ($who == 'all') {

		if (!empty($link_round)) {
			foreach ($link_round as $round) {
				$this->AddParsSenLink($round, $dn_id);
			}
		}

		if (!empty($link_finish)) {
			foreach ($link_finish as $finish) {
				$this->AddParsLink($finish, $dn_id);
			}
		}

	}elseif($who == 'filter_round'){

		if (!empty($link_round)) {
			foreach ($link_round as $round) {
				$this->AddParsSenLink($round, $dn_id);
			}
		}

	} elseif ($who == 'filter_link') {

		if (!empty($link_finish)) {
			foreach ($link_finish as $finish) {
				$this->AddParsLink($finish, $dn_id);
			}
		}

	}
	#$this->wtfarrey($link_round);
	#$this->wtfarrey($link_finish);
}

//приводим ссылки в правильный вид, убераем ненужное, добавляем нужное.
public function madeTidyLinks($data_link, $url){
	//получаем главный домен, для работы.
	$url = parse_url($url);
	$domain = $url['scheme'].'://'.$url['host'];

	#Убираем дубли
	$data_link = array_unique($data_link[1]);

	foreach($data_link as $key => $value){

		if(!empty($value)){
			//преобразуем сушности в символы, если на сайте доноре используют сушности по типу &#x2F;
			$value = html_entity_decode($value);
			//Фикс относительных ссылок. удаляем ../ в ссылках
			$value = str_replace('../', '/', $value);
			//Удаляем ненужные переносы строки и пробелы.
			$value = trim(str_replace(PHP_EOL, '', $value));

			//боримся с доменами где ссылка начинается на //
			$value = preg_replace('#^\/\/#', $url['scheme'].'://', $value);
			$http = parse_url($value);

			if(empty($http['scheme']) or empty($http['host'])){

				if($value[0] != '/'){
					$data_link[$key] = $domain.'/'.$value;
				}else{
					$data_link[$key] = $domain.$value;
				}

			}else{
				$data_link[$key] = $value;
			}
		}else{
			unset($data_link[$key]);
		}
	}
	#$this->wtfarrey($data_link);
	return $data_link;
}

//производим поиск замену для ссылки. 
public function madeFindReplaceToUrl($url, $rules, $domain, $setting){
	
	if(!empty($rules)){

		//Если нет данных в масиве домен то добываем их.
		if(empty($domain)){
			$domain = parse_url($setting['start_link']);
			$domain['host'] = '//'.$domain['host'];
		}

		foreach($rules as $rule){

			if(isset($rule[0]) && isset($rule[1])){

				$rule[0] = $this->pregRegLeft($rule[0]);
				$rule[1] = $this->pregRegRight($rule[1]);
				#$this->wtfarrey($rule[0]);
				#$this->wtfarrey($rule[1]);
				$url = trim(preg_replace($rule[0], $rule[1], $url));

				//проверяем домен после поиск замены.
				if ($setting['filter_round_domain'] == 1 && !empty($setting['start_link'])){ 
					if(stripos($url, $domain['host']) === false){ $url = ''; }
				}elseif ($setting['filter_round_domain'] == 2){ 
					if(stripos($url, $domain['host'])){ $url = ''; }
				}

				//проверяем есть ли http или https
				if(!preg_match("#(^http://)|(^https://)#", $url)){
					$url = '';
				}

			}

		}


	}

	return $url;
}

//Фунция для модификации правил поиска, для использования {skip}
public function modFilterLinkRules($filter) {
	$value = '';
	//Если фильтр не пустой начинаем зачишать. И преобразовывать.
	if (!empty($filter)) {

		//Отлавливаем регулярные вырежения в правилах поиск замена
		if(preg_match('#^\{reg\[(.*)\]\}$#', $filter, $reg)){
			$value = htmlspecialchars_decode($reg[1]);
		}else{
			$value = '#' . str_replace('\{skip\}', '(.*?)', preg_quote(trim(htmlspecialchars_decode($filter)), '#')) . '#';
		}

	}

	return $value;
}
############################################################################################
############################################################################################
#						Фунции страницы ParamSetup
############################################################################################
############################################################################################

#Вывод формирования страницы Paramsetup
public function GetParamsetup($dn_id){
	//Получаем настройки.
	$data_setting = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_setting` WHERE dn_id=".(int)$dn_id);
	$data['setting'] = $data_setting->row;

	#Получаем ссылки
	$data_links = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_link` WHERE dn_id=".(int)$dn_id." ORDER BY id ASC LIMIT 0, 100");
	$data['hrefs'] = $data_links->rows;

	#Получаем список параметров.
	$data_params = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_param` WHERE dn_id=".(int)$dn_id." ORDER BY type, id ASC");
	$data['params'] = $data_params->rows;

	$browser = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_browser` WHERE dn_id=".(int)$dn_id);
	$data['browser'] = $browser->row;

	return $data;
}

#Получение параметра который редактируем
public function getActivParam($data){
	$data_param_activ = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_param` WHERE id=".(int)$data);
	$param_activ = [];
	foreach ($data_param_activ->row as $key => $value) {
		$param_activ[$key] = htmlspecialchars_decode($value);
	}

	return $param_activ;
}

#Сохранения параметра парсинга
public function addParamPars($data, $dn_id){

	if($data['type_param'] == 1 or $data['type_param'] == 3){
		$data['delim'] = ';';
		$data['base_id'] = 0;
		$data['reverse'] = 0;
	}

	$this->db->query("INSERT INTO " . DB_PREFIX . "pars_param SET
		dn_id =".(int)$dn_id.",
		name='".$this->db->escape($data['param_name'])."',
		start='".$this->db->escape($data['param_start'])."',
		stop='".$this->db->escape($data['param_stop'])."',
		type=".(int)$data['type_param'].",
		with_teg=".(int)$data['with_teg'].",
		skip_enter='".$this->db->escape($data['skip_enter'])."',
		skip_where=".(int)$data['skip_where'].",
		reverse=".(int)$data['reverse'].",
		base_id=".(int)$data['base_id'].",
		delim='".$this->db->escape($data['delim'])."'");

		$param = $this->getActivParam($this->db->getLastId());

		return $param;
}

#Обновление параметра парсинга
public function saveParamPars($data){

	if($data['type_param'] == 1){
		$data['delim'] = ';';
		$data['base_id'] = 0;
		$data['reverse'] = 0;
	}

	$this->db->query("UPDATE `". DB_PREFIX ."pars_param` SET
		`name`='".$data['param_name']."',
		`start`='".$this->db->escape($data['param_start'])."' ,
		`stop`='".$this->db->escape($data['param_stop'])."',
		`type`='".$this->db->escape($data['type_param'])."',
		`with_teg`=".(int)$data['with_teg'].",
		`skip_enter`='".$this->db->escape($data['skip_enter'])."',
		`skip_where`=".(int)$data['skip_where'].",
		`reverse`=".(int)$data['reverse'].",
		`base_id`=".(int)$data['base_id'].",
		`delim`='".$this->db->escape($data['delim'])."'
		WHERE `id`=".(int)$data['act']);
}

public function delParamPars($id){
	$this->db->query("DELETE FROM `". DB_PREFIX ."pars_param` WHERE id=".(int)$id);
	//Удаляем значения поиск замена для удаленного параметра парсинга.
	$this->db->query("DELETE FROM `". DB_PREFIX ."pars_replace` WHERE param_id=".(int)$id);

	//Удаление файлов кеша, поиск замены.
	$file = DIR_APPLICATION.'simplepars/replace/'.$id;
	//Проверяем есть ли такой фаил
	if (file_exists($file.'_input_arr.txt')) { unlink($file.'_input_arr.txt'); }
	if (file_exists($file.'_input_text.txt')) { unlink($file.'_input_text.txt'); }
	if (file_exists($file.'_output.txt')) { unlink($file.'_output.txt'); }
}

//Копируем границу парсинга.
public function copyParamPars($param_id){
	//получаем настройки границы
	$param = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_param` WHERE id=".(int)$param_id)->row;

	//Если не пусто то копируем.
	if(!empty($param)){
		$this->db->query("INSERT INTO `".DB_PREFIX."pars_param` SET 
		`dn_id` = ".(int)$param['dn_id'].",
		`name` = '".$this->db->escape($param['name'])."',
		`start` = '".$this->db->escape($param['start'])."',
		`stop` = '".$this->db->escape($param['stop'])."',
		`type` = ".(int)$param['type'].",
		`with_teg` = ".(int)$param['with_teg'].",
		`skip_enter` = '".$this->db->escape($param['skip_enter'])."',
		`skip_where` = ".(int)$param['skip_where'].",
		`reverse` = ".(int)$param['reverse'].",
		`base_id` = ".(int)$param['base_id'].",
		`delim` = '".$this->db->escape($param['delim'])."'");

		//получаем новый id 
		$param_new_id = $this->db->getLastId();
		//Сообшение об успешном копировании.
		$this->session->data['success'] = " Граница парсинга [".$param['id']."] успешно скопирована в новую границу [".$param_new_id."]";
	}
}

//Скрыть оnбразить пред просмотр сайта.
public function setViewParam($data, $dn_id){
	$this->db->query("UPDATE `". DB_PREFIX ."pars_setting` SET pre_view_param=".(int)$data['pre_view_param']." WHERE dn_id=".(int)$dn_id);
	$this->db->query("UPDATE `". DB_PREFIX ."pars_setting` SET pre_view_syntax=".(int)$data['pre_view_syntax']." WHERE dn_id=".(int)$dn_id);
}

//Изменить параметр использования кеша.
public function changeCacheParam($data, $dn_id){
	$this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET cache_page = ".(int)$data['cache_page']." WHERE dn_id =".(int)$dn_id);
}
//Показать часть вырезанного кода.
public function showPieceCode($data, $dn_id){

	//параметры что предпросматриваем.
	$html = $this->CachePage($data['link'],$dn_id);
	//поддержка skip в построении границ париснга
	$reg_ruls = ['\{skip\}'=>'.*?','\{br\}'=>'\\r?\\n','\{\.\*\}'=>'.*','\{\.\}'=>'.'];
	//Обычная граница
	if($data['type_param']==1){

		$start = htmlspecialchars_decode($data['param_start']);
		$stop = htmlspecialchars_decode($data['param_stop']);

		$reg = '#'. strtr(preg_quote($start, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop, '#'), $reg_ruls) .'#su';
		preg_match_all($reg, $html, $pre_view);
		#$this->wtfarrey($pre_view);

		$pre_view[$data['with_teg']] = $this->skipEntryParam($pre_view[$data['with_teg']], 1, $data['skip_where'], $data['skip_enter']);

		if(empty($pre_view[$data['with_teg']][0])){$pre_view[$data['with_teg']][0]='';}
		$return['activ_param']['type'] = 1;
		$return['page_code'] = $pre_view[$data['with_teg']][0];
		$return['activ_param']['name'] = $data['param_name'];
		$return['activ_param']['id'] = $data['param_id'];
		$return['activ_param']['start'] = htmlspecialchars($start);
		$return['activ_param']['stop'] = htmlspecialchars($stop);
		$return['activ_param']['with_teg'] = $data['with_teg'];
		$return['activ_param']['skip_enter'] = $data['skip_enter'];
		$return['activ_param']['skip_where'] = $data['skip_where'];

	//Повторяющаяя граница парсинаг
	}elseif($data['type_param']==2){
		if($data['base_id'] != 0){
			//Получаем информацию о границах парсинга.
			$param_base = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_param WHERE id=".(int)$data['base_id']);
			$param_base = $param_base->row;
 			$start_base = htmlspecialchars_decode($param_base['start']);
	   	$stop_base = htmlspecialchars_decode($param_base['stop']);
	   
	   	$reg = '#'. strtr(preg_quote($start_base, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop_base, '#'), $reg_ruls).'#su';
	  	preg_match_all($reg, $html, $code);
	  	//Опередяем пропуск вхождения
  		$code[$param_base['with_teg']] = $this->skipEntryParam($code[$param_base['with_teg']], 1, $param_base['skip_where'], $param_base['skip_enter']);
  	}else{
  		$code[0][0] = $html;
  		$param_base['with_teg'] = 0;
  	}


  	//получили границу парсинга, если ее нет добавили пробел.
  	if(empty($code[$param_base['with_teg']][0])){$code[$param_base['with_teg']][0]='';}

  	//проверяем задал ли пользователь границы парсинга, если нет отдаем ему то что после обычных границ
  	if(empty($data['param_start']) || empty($data['param_stop'])){

  		$return['page_code'] = $code[$param_base['with_teg']][0];
  		$return['activ_param']['name'] = $data['param_name'];
  		$return['activ_param']['id'] = $data['param_id'];
  		$return['activ_param']['start'] = '';
			$return['activ_param']['stop'] = '';
			$return['activ_param']['type'] = 2;
			$return['activ_param']['with_teg'] = $data['with_teg'];
			$return['activ_param']['skip_enter'] = $data['skip_enter'];
			$return['activ_param']['skip_where'] = $data['skip_where'];
			$return['activ_param']['delim'] = $data['delim'];
			$return['activ_param']['reverse'] = $data['reverse'];
			$return['activ_param']['base_id'] = $data['base_id'];

  	}elseif(!empty($data['param_start']) && !empty($data['param_stop'])){
	  	//начал парсинга повторяющей границы.
	  	$start = htmlspecialchars_decode($data['param_start']);
			$stop = htmlspecialchars_decode($data['param_stop']);

			$reg = '#'. strtr(preg_quote($start, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop, '#'), $reg_ruls).'#su';

			preg_match_all($reg, $code[$param_base['with_teg']][0], $pre_view);

			//Отсееваем ненужные вхождения
			$pre_view[$data['with_teg']] = $this->skipEntryParam($pre_view[$data['with_teg']], 2, $data['skip_where'], $data['skip_enter']);
			//Вывод массива в обратном порядке.
			if($data['reverse'] == 1){
				$pre_view[$data['with_teg']] = array_reverse($pre_view[$data['with_teg']]);
			}

			$return['activ_param']['type'] = 2;
			$i = 1;
			$return['page_code'] ='';
			foreach($pre_view[$data['with_teg']] as $text){
				$return['page_code'] .= '!=========================================================== Повторение №'.$i.' ========== Разделитель ['.$data['delim'].'] =================================================!'.PHP_EOL.PHP_EOL.$text.PHP_EOL.PHP_EOL;
				$i++;
			}
			$return['activ_param']['name'] = $data['param_name'];
			$return['activ_param']['id'] = $data['param_id'];
			$return['activ_param']['start'] = htmlspecialchars($start);
			$return['activ_param']['stop'] = htmlspecialchars($stop);
			$return['activ_param']['type'] = 1;
			$return['activ_param']['with_teg'] = $data['with_teg'];
			$return['activ_param']['skip_enter'] = $data['skip_enter'];
			$return['activ_param']['skip_where'] = $data['skip_where'];
			$return['activ_param']['delim'] = $data['delim'];
			$return['activ_param']['reverse'] = $data['reverse'];
			$return['activ_param']['base_id'] = $data['base_id'];
		}
		#$this->wtfarrey($return);
	}

	return $return;
}

############################################################################################
############################################################################################
#						Фунции связанные с страницей настройки CSV прайса
############################################################################################
############################################################################################

#Фунция получения настроек csv
public function getFormCsv($dn_id){
  //получаем все настройки
	$setting = $this->getSetting($dn_id);
	
  $formcsv = $this->getSettingCsv($dn_id);
  
  $links_select = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id." ORDER BY id ASC LIMIT 0, ".$setting['page_cou_link']);

	$browser = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_browser` WHERE dn_id=".(int)$dn_id);
	$data['browser'] = $browser->row;


	//преобразуем данные допуска границ для страницы в парсинге в ИМ
	$setup = $this->getPrSetup($dn_id);
	#$data['setup']['grans_permit_list'] = $this->madeGransPermitListToArr($setup['grans_permit_list']);

	//проверяем что бы строка не была пустой.
	if(!empty($setup['grans_permit_list'])){
		$data['setup']['grans_permit_list'] = htmlspecialchars_decode($setup['grans_permit_list']);
		$data['setup']['grans_permit_list'] = explode('{next}', $data['setup']['grans_permit_list']);
		foreach($data['setup']['grans_permit_list'] as &$gran_arr){

			$gran_arr = explode('{!na!}', $gran_arr);
			$gran_arr = [
						        'switch' => $gran_arr[0],
						        'name' => $gran_arr[1],
						        'gran' => $gran_arr[2],
						        'operator' => $gran_arr[3],
						        'value' => $gran_arr[4],
						        'when_check' => $gran_arr[5]
					    		];
		}
	}else{
		//если строка пустая вернем пустой массив.
		$data['setup']['grans_permit_list'] = [];
	}

  if(empty($setting['csv_name'])){
    $setting['csv_name'] = 'price-'.$dn_id;
  }
  $csv_file = './uploads/'.$setting['csv_name'].'.csv';

  if (file_exists($csv_file)) {
  	$data['csv_exists'] = true;
  } else {
  	$data['csv_exists'] = false;

  }

  //получаем информацию о списках.
  $data['link_lists'] = $this->getAllLinkList($dn_id);
  $data['link_errors'] = $this->getAllLinkError($dn_id);


  //получаем ссылки для вывода информации
  $data['setting'] = $setting;
  $data['links_select'] = $links_select->rows;
  $data['formcsv'] = $formcsv;

 	return $data;
}

public function saveFormCsv($data, $dn_id){
 	#$this->wtfarrey($data);
 	$data['csv_escape'] = '"'; //Этот параметр более неиспользуется, и ждет время на полное удаление из базы.
 	#заменяем пробелы на нижнее подчеркивание в названии прайса.
 	$data['csv_name'] = str_replace(' ', '_', $data['csv_name']);

 	//Сохраняем имя и паузу парсинга.
	  $this->db->query("UPDATE ". DB_PREFIX ."pars_setting  SET 
	  	csv_name='".$this->db->escape($data['csv_name'])."' , 
	  	pars_pause='".$this->db->escape($data['pars_pause'])."', 
	  	thread='".$this->db->escape($data['thread'])."', 
	  	grans_permit='".$this->db->escape($data['grans_permit'])."', 
	  	csv_delim='".$this->db->escape($data['csv_delim'])."', 
	  	csv_escape='".$this->db->escape($data['csv_escape'])."', 
	  	csv_charset=".(int)$data['csv_charset']." WHERE dn_id=".(int)$dn_id);

	  //перед сохранением удаляем все настройки из базы
  	$this->db->query("DELETE FROM ". DB_PREFIX ."pars_createcsv WHERE dn_id=".(int)$dn_id);

  if(!empty($data['csv'])){

   #записывам все параметры заново.
   foreach($data['csv'] as $column){
    $this->db->query("INSERT INTO ". DB_PREFIX ."pars_createcsv SET 
    	dn_id=".(int)$dn_id.", 
    	name='".$this->db->escape($column['name'])."',
    	value='".$this->db->escape($column['value'])."',
    	csv_column='".$this->db->escape($column['csv_column'])."'");
   }
  }

  //Преобразуем блок допуска границ.
	if(!empty($data['grans_permit_list'])){

		//перебере
		foreach($data['grans_permit_list'] as $gran_arr_key => &$gran_arr){

			//если в поле не настроена граница парсинга то такое правело не сохраняем.
			if(empty($gran_arr['gran'])){
				unset($data['grans_permit_list'][$gran_arr_key]);
			}else{
				$gran_arr = implode('{!na!}', $gran_arr);
			}

		}
		#$this->wtfarrey($data['grans_permit_list']);
		$data['grans_permit_list'] = implode('{next}', $data['grans_permit_list']);

	}else{

		$data['grans_permit_list'] = '';
	
	}

	//сохраняем допуски.
	$this->db->query("UPDATE ". DB_PREFIX ."pars_prsetup SET grans_permit_list='".$this->db->escape($data['grans_permit_list'])."' WHERE dn_id=".(int)$dn_id);

  //настройки браузера.
  $this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET cache_page = ".(int)$data['cache_page']." WHERE dn_id =".(int)$dn_id);

  $this->session->data['success'] = "Настройки сохранены";
}

#Контролер на добавление новых ссылок на парсинг
public function controlAddLink($data, $dn_id, $mark='link'){

 	$links = explode(PHP_EOL, $data);

 	if($mark == 'link'){
	  $this->DelParsLink($dn_id);

	  //Использую бредовую фунцию нужно написать свою фунцию по валидации url.
	  foreach($links as $link){
	    if(!empty($link)){
	      $url = parse_url($link);

	      if(!empty($url['scheme']) && !empty($url['host'])){
	        $this->AddParsLink($link, $dn_id);
	      }
	    }
	  }

	}elseif($mark == 'link_sen'){
		$this->DelParsSenLink($dn_id);
		foreach($links as $link){
	    if(!empty($link)){
	      $url = parse_url($link);

	      if(!empty($url['scheme']) && !empty($url['host'])){
	        $this->AddParsSenLink($link, $dn_id);
	      }
	    }
	  }

	}
}

#Контроллер для пред просмотра CSV.
public function controlShowParsToCsv($url, $dn_id){
  #проверка какой предпросмотр вызывается
  $html = $this->CachePage($url, $dn_id);
  $settingcsv = $this->getSettingCsv($dn_id);
	//проверяем есть ли настройки.
	if(empty($settingcsv)){
		$this->session->data['error'] = ' Отсутствуют настройки файла CSV';
		return $csv = 'redirect';
	}
	
	$csv = $this->changeDataToCsv($html, $settingcsv, $url, $dn_id);

	//Получам дополнительные данные из настроек.
	$setting = $this->getSetting($dn_id);
	//текст предупреждения
	$csv['permit_grans_text'] = '';

	//Получаем разрешения на действия.
	if(!empty($setting['grans_permit'])){
		//для совместимости 
		$tmp_html['content'] = $html;
		$tmp_html['url'] = $url;
		$form = $this->preparinDataToStore($tmp_html, $dn_id);

		$form['permit_grans'] = $this->checkGransPermit($form, $setting, $dn_id);

		//проверяем допуски
		if( empty($form['permit_grans'][4]['permit']) ){
			$csv['permit_grans_text'] = 'ВНИМАНИЕ!!!<br> Страница не будет спарсена.<br>Поскольку:'.$form['permit_grans'][4]['log'];
		}

	}

	$csv['value'] = $this->transformCsv($csv['value']);
	array_walk_recursive($csv['value'], array($this, 'htmlview'));
	#$this->wtfarrey($csv);
	return $csv;
}

public function changeDataToCsv($html, $form, $url, $dn_id){
	
	$params = $this->getParsParams($dn_id);

	$grans_key = [];
	$grans_data = [];
	foreach($params as $param){

		$temp = $this->parsParam($html, $param['id'], $params);

		if($param['type'] == 1){
			$grans_key[] = '{gran_'.$param['id'].'}';
			$grans_data[] = $this->findReplace($temp, $param['id']);

		}else{
			//запускаем поиск заме для повторяющейся границы парсинга.
			foreach($temp as &$var){
				$var = $this->findReplace($var, $param['id']);
			}
			//обьеденяем массив с разделителем.
			$grans_key[] = '{gran_'.$param['id'].'}';
			$grans_data[] = implode($param['delim'], $temp); 
		}
	}

	//преобразуем в стандартизироанный массив.
	$csv['title'] = [];
	$csv['value'] = [];
	foreach($form as $value){
		
		//делаем преобразование. 
		for ($i=0; $i < 5; $i++) { 
			$value['value'] = str_replace($grans_key, $grans_data, $value['value']);
		}

		//форматирование колонок с указанием строго количества колонок под одну границу
		$count_column = substr_count($value['value'],'{csvnc}') +1; #определяем реальное количествно колонок в границе.
		$value['csv_column'] = (int)$value['csv_column'];

		if(!empty($value['csv_column']) && $count_column > $value['csv_column']){

			$value['value'] = explode('{csvnc}', $value['value']);
			$value['value'] = array_splice($value['value'], 0, $value['csv_column']);
			$value['value'] = implode('{csvnc}', $value['value']);
			$csv['value'][] = $value['value'];

		}elseif(!empty($value['csv_column']) && $count_column < $value['csv_column']){
			
			while($count_column < $value['csv_column']){

				$value['value'] .= '{csvnc}';
				$count_column++;
			}
			$csv['value'][] = $value['value'];

		}else{
			$csv['value'][] = $value['value'];
		}

		$csv['title'][] = $value['name'];
		//дублируем имена колонок.
		while( ($value['csv_column'] - 1) > 0 ){
			$csv['title'][] = $value['name'];
			$value['csv_column']--;
		}
	}
	#$this->wtfarrey($csv);
	return $csv;
}

//Преобразованя операторов {csvnс} и {csvnl}
public function transformCsv($data){
	//перебор массива. {csvnl}
	$il = 0;
	foreach($data as $key => $csvnl){
		//если есть совпадение с оператором {csvnc}
		if(strripos($csvnl, '{csvnl}')){
			$csvnl_arrey = explode('{csvnl}', $csvnl);
			if(is_array($csvnl_arrey)){
				foreach($csvnl_arrey as $value){
					$csvnl_data[$il][] = $value;
					$il++;

				}
			}
		}else{
			//Убераем один лищний перенос строки.
			if($il !=0){
				$il2 = $il -1;
			}else{
				$il2 = $il;
			}
			$csvnl_data[$il2][] = $csvnl;
		}
	}

	//Теперь разбиваем массив на ячейки. {csvnc}
	foreach($csvnl_data as $nl_key => $arr_csvnc){
		//Раскрываем многомерный массив,
		foreach($arr_csvnc as $nc_key => $csvnc){
		#$this->wtfarrey($csvnc);
  		if(strripos($csvnc, '{csvnc}') !== false){
  			$csvnc = explode('{csvnc}', $csvnc);
  			foreach($csvnc as $new_colum){
					$new_data[$nl_key][] = $this->madeLogicalMathem($new_colum, 'str');
  			}

  		}else{
  			$new_data[$nl_key][] = $this->madeLogicalMathem($csvnc, 'str');
  		}

		}

	}

	#$this->wtfarrey($new_data);
	return $new_data;
}

//Создание файла csv
public function createCsv($csv, $setting, $dn_id){
  #Записываем, или дозаписываем данные csv файла
	$csv_delim = htmlspecialchars_decode($setting['csv_delim']);
	$csv_escape = '"';

	//Кодировка файла.
	$tail = '//TRANSLIT';
	if($setting['csv_charset'] == 1){
		$csv_charset = 'WINDOWS-1251'.$tail;
	}elseif($setting['csv_charset'] == 2){
		$csv_charset = 'UTF-8'.$tail;
	}else{
		$csv_charset = 'WINDOWS-1251'.$tail;
	}

  #имя файла по умолчанию
  $path = "./uploads/price-".$dn_id.".csv";

  #имя файла по желанию
  if(!empty($setting['csv_name'])){
    $path = "./uploads/".iconv("UTF-8", "WINDOWS-1251", $setting['csv_name']).".csv";
  }

  if(!file_exists($path)){
  	foreach($csv['title'] as $kay => $title){
	    @$csv['title'][$kay] = htmlspecialchars_decode(trim(iconv("UTF-8", $csv_charset, $title)));
	  }
    #открываем фаил и записываем title
    $file = fopen($path, 'a+');
    	fputcsv($file, $csv['title'], $csv_delim, $csv_escape);
    fclose($file);
  }

  foreach($csv['value'] as $csv_data){
	  #Меняем кодировку для файла csv
	  foreach($csv_data as $kay => $csv_var){
	    @$csv_data[$kay] = trim(iconv("UTF-8", $csv_charset, $csv_var));
	  }

	  #$this->wtfarrey($csv_data);

	  $file = fopen($path, 'a+');
	  	fputcsv($file, $csv_data, $csv_delim, $csv_escape);
	  fclose($file);
	}
}

#контролер парсинга в CSV. Храни меня господь разобратся что тут написал! ;)
public function controlParsDataToCsv($dn_id){
  $setting = $this->getSetting($dn_id);
  $browser = $this->getBrowserToCurl($dn_id);
  $settingcsv = $this->getSettingCsv($dn_id);

  //Получаем список заданий для выполнения
	if($setting['scripts_permit']){
		$script_tasks = $this->scriptGetTasksToExe($dn_id);
		$setting['thread'] = 1;#Если включено использование скриптов модуль работает в одном потоке. 
	}

  //Если настройки csv несделаны отдаем ошибку что форма не настроена.
	if(empty($settingcsv)){
		$answ['progress'] = 100;
		$answ['clink'] = ['link_scan_count' => 0, 'link_count' => 0,];
		$this->answjs('finish',' Отсутствуют настройки файла CSV',$answ);
	}

  $pars_url = $this->getUrlToPars($dn_id, $setting['link_list'], $setting['link_error']);
  #$this->wtfarrey($pars_url);
  #Если ссылок нету завершаем работу модуля.
  if(empty($pars_url['links'])){

    $answ['progress'] = 100;
    $answ['clink'] = ['link_scan_count' => $pars_url['total'], 'link_count' => $pars_url['queue'],];
    $this->answjs('finish','Парсинг закончился, ссылок больше нет﻿',$answ);

  }else{

  	//собираем массив ссылок для мульти запроса.
  	$urls = [];
  	foreach($pars_url['links'] as $key => $url){
  		if($key < $setting['thread']) {$urls[] = $url['link']; } else { break; }
  	}

  	//Отправка данных на собственные скрипты. 
		if(!empty($script_tasks)){

			$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
			$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
			$setting = $script_data['setting']; 
			$browser = $script_data['browser'];
			$urls = $script_data['urls'];
			unset($script_data);
		
		}

  	//отправляем запрос
  	$datas = $this->requestConstructor(1, $urls, $dn_id, $browser, $setting, 0);
  		
  	foreach($datas as $key => $data){
			//Получаем разрешения на действия.
			if(!empty($setting['grans_permit'])){
				//плохая практика но что поделать, дергаем данные парсинга в ИМ
				$form = $this->preparinDataToStore($data, $dn_id);
				$permit_grans = $this->checkGransPermit($form, $setting, $dn_id);
				#$this->wtfarrey($permit_grans);
				//проверяем массив допуска и сравниваем с выбранным действием. 
				if( empty($permit_grans[4]['permit'])){ 
					$this->log('NoGranPermit', $permit_grans[4]['log'], $dn_id);
					continue; 
				}
			}

  		$html = $data['content'];
  		$csv = [];
  		$csv = $this->changeDataToCsv($html, $settingcsv, $data['url'], $dn_id);

  		//преобразовывем данные для csv
			$csv['value'] = $this->transformCsv($csv['value']);
			
			//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){

				//получаем данные границы.
				if(empty($form)){ $form = $this->preparinDataToStore($data, $dn_id); }
				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'csv'=>$csv, 'script_gran'=>$form['script_gran'], 'url'=>$data['url']];
				unset($form);
				$script_data = $this->scriptController(4, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				$csv = $script_data['csv'];
				unset($script_data);
			
			}

			//записываем данные в csv
			$this->createCsv($csv, $setting, $dn_id);

			//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){

				//получаем данные границы.
				if(empty($form)){ $form = $this->preparinDataToStore($data, $dn_id); }
				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'csv'=>$csv, 'script_gran'=>$form['script_gran'], 'url'=>$data['url']];
				unset($form);
				$script_data = $this->scriptController(5, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				unset($script_data);
			
			}

  	}


    
    #считаем процент для прогрес бара
    $scan = ($pars_url['total']-$pars_url['queue']);
    $progress = $scan/($pars_url['total']/100);
    $answ['progress'] = $progress;
    $answ['clink'] = [
                       'link_scan_count' => $scan,
                       'link_count' => $pars_url['queue'],
                      ];
    #пауза парсинга
    $this->timeSleep($setting['pars_pause']);
    $this->answjs('go','Производится парсинг',$answ);
  }
}

############################################################################################
############################################################################################
#						Фунции связанные с страницей настройки Парсинга в ИМ
############################################################################################
############################################################################################
	//Получение параметров парсинга для выбора.
public function getSettingToProduct($dn_id){

	//получаем все настройки
	$setting = $this->getSetting($dn_id);

	//Удаляем ненужно, а то мешает отладке, удалить потом этот блок.
	unset($setting['filter_round_yes']);
	unset($setting['filter_round_method']);
	unset($setting['filter_round_no']);
	unset($setting['filter_link_yes']);
	unset($setting['filter_link_no']);
	unset($setting['filter_link_method']);
	unset($setting['start_link']);
	unset($setting['csv_name']);
	unset($setting['csv_delim']);
	unset($setting['csv_escape']);
	unset($setting['csv_charset']);

	//преобразовываем данные в нужный формат для работы с товарами.
	$setting['r_store'] = explode(',', $setting['r_store']);
	//Проверяем не убрал ли пользователь все галочки. если убрал ставим по умолчанию.
	if(empty($setting['r_store'][0])) {$setting['r_store'][0] = 0;}

	$setting['r_lang'] = explode(',', $setting['r_lang']);

	//Проверяем не убрал ли пользователь все галочки. если убрал ставим по умолчанию.
	if(empty($setting['r_lang'][0])) {$setting['r_lang'][0] = 1;}

	#$this->wtfarrey($setting);
  return $setting;
}

//получения параметров парсинга для вывода на странице модуля.
public function getPrsetupToPage($dn_id){
	$setup = $this->getPrSetup($dn_id);

  //приводим опции в состояние.
	$setup['opt_name'] = explode('{next}', $setup['opt_name']);
	$setup['opt_value'] = explode('{next}', $setup['opt_value']);
	$setup['opt_price'] = explode('{next}', $setup['opt_price']);
	$setup['opt_quant'] = explode('{next}', $setup['opt_quant']);
	$setup['opt_quant_d'] = explode('{next}', $setup['opt_quant_d']);
	$setup['opt_imgs'] = explode('{next}', $setup['opt_imgs']);
	$setup['opt_data'] = explode('{next}', $setup['opt_data']);
	$setup['opts'] = [];
	//Собераем один массив для вывода опций
	$opt_quant_d = $setup['opt_quant_d'][0];
	foreach ($setup['opt_name'] as $key => $opt_name) {
		$opt_name = explode('{|}', $opt_name);
		if(empty($opt_name[1])){ $opt_name[1]=0; }
		$setup['opts'][$key]['name'] = $opt_name[0];
		$setup['opts'][$key]['opt_id'] = $opt_name[1];
		$setup['opts'][$key]['value'] = $setup['opt_value'][$key];
		$setup['opts'][$key]['price'] = $setup['opt_price'][$key];
		$setup['opts'][$key]['quant'] = $setup['opt_quant'][$key];
		if(!empty($setup['opt_imgs'][$key])){
			$setup['opts'][$key]['imgs'] = $setup['opt_imgs'][$key];
		}
		if (!isset($setup['opt_quant_d'][$key])) { 
			$setup['opts'][$key]['quant_d'] = $opt_quant_d; 
		} else { 
			$setup['opts'][$key]['quant_d'] = $setup['opt_quant_d'][$key];
		}
		//преобразовываем дополнительные данные для опций.
		preg_match('#\{required_(.)?\}#', $setup['opt_data'][$key], $required);
		preg_match('#\{price_prefix_(.)?\}#', $setup['opt_data'][$key], $price_prefix);
		preg_match('#\{imgs_type_(.)?\}#', $setup['opt_data'][$key], $imgs_type);
		
		if (empty($required[1])){ $required[1] = 0; }
		if (empty($price_prefix[1])){ $price_prefix[1] = '+'; }
		if (empty($imgs_type[1])){ $imgs_type[1] = 0; }
		$setup['opts'][$key]['data']['required'] = $required[1];
		$setup['opts'][$key]['data']['price_prefix'] = $price_prefix[1];
		$setup['opts'][$key]['data']['imgs_type'] = $imgs_type[1];
	}
	
	//Преобразуем данные допуска границ для страницы в парсинге в ИМ
	//проверяем что бы строка не была пустой.
	if(!empty($setup['grans_permit_list'])){
		$setup['grans_permit_list'] = htmlspecialchars_decode($setup['grans_permit_list']);
		$setup['grans_permit_list'] = explode('{next}', $setup['grans_permit_list']);
		foreach($setup['grans_permit_list'] as &$gran_arr){

			$gran_arr = explode('{!na!}', $gran_arr);
			$gran_arr = [
						        'switch' => $gran_arr[0],
						        'name' => $gran_arr[1],
						        'gran' => $gran_arr[2],
						        'operator' => $gran_arr[3],
						        'value' => $gran_arr[4],
						        'when_check' => $gran_arr[5]
					    		];
		}
	}else{
		//если строка пустая вернем пустой массив.
		$setup['grans_permit_list'] = [];
	}

	#$this->wtfarrey($setup);
	return $setup;
}

//Сохранить форму настроек пр в магазин
public function savePrsetup($data, $dn_id){
	#$this->wtfarrey($data);
	
	if(empty($data['model'])){ $data['model'] = '';	}
	if(empty($data['sku'])){ $data['sku'] = '';	}
	if(empty($data['name'])) { $data['name'] = ''; }
	if(empty($data['thread'])) { $data['thread'] = 1; }
	if(!isset($data['r_made_url'])){ $data['r_made_url'] = 1;	}
	if(!isset($data['r_made_meta'])){	$data['r_made_meta'] = 0;	}

	if(empty($data['price'])){ $data['price'] = '';	}
	if(empty($data['price_spec'])){ $data['price_spec'] = '';	}
	if(empty($data['r_price_spec'])){ $data['r_price_spec'] = 0;	}
	if(empty($data['r_price_spec_groups'])){ $data['r_price_spec_groups'] = '';	}
	if(empty($data['r_price_spec_date_start'])){ $data['r_price_spec_date_start'] = '';	}
	if(empty($data['r_price_spec_date_end'])){ $data['r_price_spec_date_end'] = '';	}
	if(empty($data['cost'])){ $data['cost'] = '';	}
	if(empty($data['r_cost'])){ $data['r_cost'] = 0;	}

	if(!isset($data['quant'])){	$data['quant'] = ''; }
	if(empty($data['quant_d'])){ $data['quant_d'] = '';	}
	if(empty($data['r_status_zero'])){ $data['r_status_zero'] = 5; }
	if(empty($data['r_status'])){ $data['r_status'] = 0; }

	if(empty($data['manufac'])){ $data['manufac'] = '';	}
	if(empty($data['manufac_d'])){ $data['manufac_d'] = 0;	}
	if(!isset($data['r_manufac_made_url'])){ $data['r_manufac_made_url'] = 0;	}
	if(!isset($data['r_manufac_made_meta'])){	$data['r_manufac_made_meta'] = 0;	}
	if(empty($data['des'])) {	$data['des'] = '';	}
	if(empty($data['cat'])){ $data['cat'] = '';	}
	if(empty($data['cat_d'])){ $data['cat_d'] = 0;	}
	if(!isset($data['r_cat_perent'])){ $data['r_cat_perent'] = 0;	}
	if(!isset($data['r_cat_made_url'])){ $data['r_cat_made_url'] = 1;	}
	if(!isset($data['r_cat_made_meta'])){	$data['r_cat_made_meta'] = 0;	}
	if(empty($data['des_d'])){ $data['des_d'] = '';	}
	if(empty($data['des_dir'])){ $data['des_dir'] = 'description'; }
	if(empty($data['img'])){ $data['img'] = '';	}
	if(empty($data['img_d'])){ $data['img_d'] = '';	}
	if(empty($data['img_dir'])){ $data['img_dir'] = 'product'; }
	if(empty($data['attr'])){	$data['attr'] = '';	}
	if(empty($data['r_attr_group'])){	$data['r_attr_group'] = 1;	}

	if(empty($data['u_manufac'])){	$data['u_manufac'] = 0;	}
	if(empty($data['u_des'])){	$data['u_des'] = 0;	}
	if(empty($data['u_cat'])){	$data['u_cat'] = 0;	}
	if(empty($data['u_img'])){	$data['u_img'] = 0;	}
	if(empty($data['u_attr'])){	$data['u_attr'] = 0;	}
	if(empty($data['u_opt'])){	$data['u_opt'] = 0;	}
	if(empty($data['u_made_meta'])){	$data['u_made_meta'] = 0;	}
	if(empty($data['u_up_url'])){	$data['u_up_url'] = 0;	}

	#Разное
	if(!isset($data['upc'])){ $data['upc'] = '';	}
	if(!isset($data['ean'])){ $data['ean'] = '';	}
	if(!isset($data['jan'])){ $data['jan'] = '';	}
	if(!isset($data['isbn'])){ $data['isbn'] = '';	}
	if(!isset($data['mpn'])){ $data['mpn'] = '';	}
	if(!isset($data['location'])){ $data['location'] = '';	}
	if(!isset($data['minimum'])){ $data['minimum'] = 1;	}
	if(!isset($data['subtract'])){ $data['subtract'] = '';	}
	if(!isset($data['length'])){ $data['length'] = '0.00';	}
	if(!isset($data['width'])){ $data['width'] = '0.00';	}
	if(!isset($data['height'])){ $data['height'] = '0.00';	}
	if(!isset($data['length_class_id'])){ $data['length_class_id'] = 1;	}
	if(!isset($data['weight'])){ $data['weight'] = '0.00';	}
	if(!isset($data['weight_class_id'])){ $data['weight_class_id'] = 1;	}
	if(!isset($data['status'])){ $data['status'] = 1;	}
	if(!isset($data['sort_order'])){ $data['sort_order'] = 0;	}
	if(!isset($data['layout_pr'])){ $data['layout_pr'] = 0;	}
	if(!isset($data['layout_cat'])){ $data['layout_cat'] = 0;	}
	if(!isset($data['tags'])){ $data['tags'] = '';	}
	if(empty($data['related_sku'])){ $data['related_sku'] = '';	}
	if(empty($data['hpm_sku'])){ $data['hpm_sku'] = '';	}

	#Разное, правила
	if(empty($data['r_upc'])){ $data['r_upc'] = 0;	}
	if(empty($data['r_ean'])){ $data['r_ean'] = 0;	}
	if(empty($data['r_jan'])){ $data['r_jan'] = 0;	}
	if(empty($data['r_isbn'])){ $data['r_isbn'] = 0;	}
	if(empty($data['r_mpn'])){ $data['r_mpn'] = 0;	}
	if(empty($data['r_location'])){ $data['r_location'] = 0;	}
	if(empty($data['r_minimum'])){ $data['r_minimum'] = 0;	}
	if(empty($data['r_subtract'])){ $data['r_subtract'] = 0;	}
	if(empty($data['r_length'])){ $data['r_length'] = 0;	}
	if(empty($data['r_width'])){ $data['r_width'] = 0;	}
	if(empty($data['r_height'])){ $data['r_height'] = 0;	}
	if(empty($data['r_length_class_id'])){ $data['r_length_class_id'] = 0;	}
	if(empty($data['r_weight'])){ $data['r_weight'] = 0;	}
	if(empty($data['r_weight_class_id'])){ $data['r_weight_class_id'] = 0;	}
	if(empty($data['r_status'])){ $data['r_status'] = 0;	}
	if(empty($data['r_sort_order'])){ $data['r_sort_order'] = 0;	}
	if(empty($data['r_layout_pr'])){ $data['r_layout_pr'] = 0;	}
	if(empty($data['r_hpm'])){ $data['r_hpm'] = 0;	}
	if(empty($data['r_related'])){ $data['r_related'] = 0;	}

	
	#SEO вкладка
	#Товар
  if(empty($data['seo_url'])){ $data['seo_url'] = '';	}
	if(empty($data['seo_h1'])){ $data['seo_h1'] = '';	}
	if(empty($data['seo_title'])){ $data['seo_title'] = '';	}
	if(empty($data['seo_desc'])){ $data['seo_desc'] = '';	}
	if(empty($data['seo_keyw'])){ $data['seo_keyw'] = '';	}
	if(empty($data['img_name'])){ $data['img_name'] = '';	}

	#Категории
	if(empty($data['cat_seo_url'])){ $data['cat_seo_url'] = '';	}
	if(empty($data['cat_seo_h1'])){ $data['cat_seo_h1'] = '';	}
	if(empty($data['cat_seo_title'])){ $data['cat_seo_title'] = '';	}
	if(empty($data['cat_seo_desc'])){ $data['cat_seo_desc'] = '';	}
	if(empty($data['cat_seo_keyw'])){	$data['cat_seo_keyw'] = '';	}
	#Производитель
	if(empty($data['manuf_seo_url'])){ $data['manuf_seo_url'] = '';	}
	if(empty($data['manuf_seo_h1'])){	$data['manuf_seo_h1'] = '';	}
	if(empty($data['manuf_seo_title'])){ $data['manuf_seo_title'] = '';	}
	if(empty($data['manuf_seo_desc'])){	$data['manuf_seo_desc'] = '';	}
	if(empty($data['manuf_seo_keyw'])){	$data['manuf_seo_keyw'] = '';	}

	if(empty($data['cache_page'])){	$data['cache_page'] = 0;	}


	//Дополнительные преобразования перед записью в базу
	if (empty($data['r_store'])) {
		$data['r_store'] = '';
		$temp_s = $this->getAllStore();
		foreach ($temp_s as $key_ts => $t_s) {
			if ($key_ts != 0) { $data['r_store'] .= ','.$t_s['store_id']; } else { $data['r_store'] = $t_s['store_id']; }
		}

	} else {
		$data['r_store'] = implode(',',$data['r_store']);
	}

	#Если убрали все галочки в языке тогда записываем выбрать все языки в магазине.
	if(empty($data['r_lang'])) {
		$data['r_lang']='';
		$temp_l = $this->getAllLang();
		foreach ($temp_l as $key_tl => $t_l) {
			if ($key_tl != 0) { $data['r_lang'] .= ','.$t_l['language_id']; } else { $data['r_lang'] = $t_l['language_id']; }
		}

	} else {
		$data['r_lang'] = implode(',',$data['r_lang']);
	}

	#$this->wtfarrey($data);

	//преобразуем блок опций для записи в бд.
	$data['opt_name'] = '';
	$data['opt_value'] = '';
	$data['opt_price'] = '';
	$data['opt_quant'] = '';
	$data['opt_quant_d'] = '';
	$data['opt_imgs'] = '';
	$data['opt_data'] = '';

	//получаем сигнал есть ли старонний модуль для опций.
	$check_option_module = $this->checkModuleOption();
	
	foreach ($data['opts'] as $key => $opt) {

		//если старонний модуль опций не установлен сбиваем настройку опций.
		if( $opt['data']['imgs_type'] == 2 && empty($check_option_module) ){ 
			$opt['data']['imgs_type'] = 0;
			$this->session->data['error'] = 'ВНИМАНИЕ!!! У вас не установлен дополнительный модуль опций, и вы не можете использовать изображение опций по стандарту "Модуль опций от HyperLabTeam"';
		}

		if ($key == 0) {
			$data['opt_name'] = $opt['name'].'{|}'.$opt['opt_id'];
			$data['opt_value'] = $opt['value'];
			$data['opt_price'] = $opt['price'];
			$data['opt_quant'] = $opt['quant'];
			$data['opt_quant_d'] = $opt['quant_d'];
			$data['opt_imgs'] = $opt['imgs'];
			$data['opt_data'] = '{required_'.$opt['data']['required'].'}{price_prefix_'.$opt['data']['price_prefix'].'}{imgs_type_'.$opt['data']['imgs_type'].'}';
		} else {
			$data['opt_name'] = $data['opt_name'].'{next}'.$opt['name'].'{|}'.$opt['opt_id'];
			$data['opt_value'] = $data['opt_value'].'{next}'.$opt['value'];
			$data['opt_price'] = $data['opt_price'].'{next}'.$opt['price'];
			$data['opt_quant'] = $data['opt_quant'].'{next}'.$opt['quant'];
			$data['opt_quant_d'] = $data['opt_quant_d'].'{next}'.$opt['quant_d'];
			$data['opt_imgs'] = $data['opt_imgs'].'{next}'.$opt['imgs'];
			$data['opt_data'] = $data['opt_data'].'{next}'.'{required_'.$opt['data']['required'].'}{price_prefix_'.$opt['data']['price_prefix'].'}{imgs_type_'.$opt['data']['imgs_type'].'}';
		}
	}

	//Преобразуем блок допуска границ.
	if(!empty($data['grans_permit_list'])){

		//перебере
		foreach($data['grans_permit_list'] as $gran_arr_key => &$gran_arr){

			//если в поле не настроена граница парсинга то такое правело не сохраняем.
			if(empty($gran_arr['gran'])){
				unset($data['grans_permit_list'][$gran_arr_key]);
			}else{
				$gran_arr = implode('{!na!}', $gran_arr);
			}

		}
		#$this->wtfarrey($data['grans_permit_list']);
		$data['grans_permit_list'] = implode('{next}', $data['grans_permit_list']);

	}else{

		$data['grans_permit_list'] = '';
	
	}

 	#$this->wtfarrey($data);
  //Сохранение настройки границ в базу..
  $this->db->query("UPDATE ". DB_PREFIX ."pars_prsetup SET
  	model='".$this->db->escape($data['model'])."',
  	sku='".$this->db->escape($data['sku'])."',
  	name='".$this->db->escape($data['name'])."',
  	price='".$this->db->escape($data['price'])."',
  	price_spec='".$this->db->escape($data['price_spec'])."',
  	cost='".$this->db->escape($data['cost'])."',
  	quant='".$this->db->escape($data['quant'])."',
  	quant_d=".(int)$data['quant_d'].",
  	manufac='".$this->db->escape($data['manufac'])."',
  	manufac_d=".(int)$data['manufac_d'].",
  	des='".$this->db->escape($data['des'])."',
  	des_d='".$this->db->escape($data['des_d'])."',
  	des_dir='".$this->db->escape($data['des_dir'])."',
  	cat='".$this->db->escape($data['cat'])."',
  	cat_d=".(int)$data['cat_d'].",
  	img='".$this->db->escape($data['img'])."',
  	img_d='".$this->db->escape($data['img_d'])."',
  	img_dir='".$this->db->escape($data['img_dir'])."',
  	img_name='".$this->db->escape($data['img_name'])."',
  	attr='".$this->db->escape($data['attr'])."',
  	upc='".$this->db->escape($data['upc'])."',
  	ean='".$this->db->escape($data['ean'])."',
  	jan='".$this->db->escape($data['jan'])."',
  	isbn='".$this->db->escape($data['isbn'])."',
  	mpn='".$this->db->escape($data['mpn'])."',
  	location='".$this->db->escape($data['location'])."',
  	minimum='".$this->db->escape($data['minimum'])."',
  	subtract='".$this->db->escape($data['subtract'])."',
  	length='".$this->db->escape($data['length'])."',
  	width='".$this->db->escape($data['width'])."',
  	height='".$this->db->escape($data['height'])."',
  	length_class_id='".$this->db->escape($data['length_class_id'])."',
  	weight='".$this->db->escape($data['weight'])."',
  	weight_class_id='".$this->db->escape($data['weight_class_id'])."',
  	status='".$this->db->escape($data['status'])."',
  	sort_order='".$this->db->escape($data['sort_order'])."',
  	layout_pr='".$this->db->escape($data['layout_pr'])."',
  	layout_cat='".$this->db->escape($data['layout_cat'])."',
  	tags='".$this->db->escape($data['tags'])."',
  	related_sku='".$this->db->escape($data['related_sku'])."',
  	hpm_sku='".$this->db->escape($data['hpm_sku'])."',
  	opt_name='".$this->db->escape($data['opt_name'])."',
  	opt_value='".$this->db->escape($data['opt_value'])."',
  	opt_price='".$this->db->escape($data['opt_price'])."',
  	opt_quant='".$this->db->escape($data['opt_quant'])."',
  	opt_quant_d='".$this->db->escape($data['opt_quant_d'])."',
  	opt_imgs='".$this->db->escape($data['opt_imgs'])."',
  	opt_data='".$this->db->escape($data['opt_data'])."',
  	grans_permit_list='".$this->db->escape($data['grans_permit_list'])."',
  	seo_url='".$this->db->escape($data['seo_url'])."',
  	seo_h1='".$this->db->escape($data['seo_h1'])."',
  	seo_title='".$this->db->escape($data['seo_title'])."',
  	seo_desc='".$this->db->escape($data['seo_desc'])."',
  	seo_keyw='".$this->db->escape($data['seo_keyw'])."',
  	cat_seo_url='".$this->db->escape($data['cat_seo_url'])."',
  	cat_seo_h1='".$this->db->escape($data['cat_seo_h1'])."',
  	cat_seo_title='".$this->db->escape($data['cat_seo_title'])."',
  	cat_seo_desc='".$this->db->escape($data['cat_seo_desc'])."',
  	cat_seo_keyw='".$this->db->escape($data['cat_seo_keyw'])."',
  	manuf_seo_url='".$this->db->escape($data['manuf_seo_url'])."',
  	manuf_seo_h1='".$this->db->escape($data['manuf_seo_h1'])."',
  	manuf_seo_title='".$this->db->escape($data['manuf_seo_title'])."',
  	manuf_seo_desc='".$this->db->escape($data['manuf_seo_desc'])."',
  	manuf_seo_keyw='".$this->db->escape($data['manuf_seo_keyw'])."'
  	WHERE dn_id=".(int)$dn_id);

  //Сохраняем правила парсинга.
  $this->db->query("UPDATE `". DB_PREFIX ."pars_setting` SET
  	pars_pause='".$this->db->escape($data['pars_pause'])."',
  	action=".(int)$data['action'].",
  	thread=".(int)$data['thread'].",
  	sid='".$this->db->escape($data['sid'])."',
  	grans_permit='".$this->db->escape($data['grans_permit'])."',
  	u_manufac='".$this->db->escape($data['u_manufac'])."',
  	u_des='".$this->db->escape($data['u_des'])."',
  	u_cat='".$this->db->escape($data['u_cat'])."',
  	u_img='".$this->db->escape($data['u_img'])."',
  	u_attr='".$this->db->escape($data['u_attr'])."',
  	u_opt='".$this->db->escape($data['u_opt'])."',
  	u_made_meta='".$this->db->escape($data['u_made_meta'])."',
  	u_up_url='".$this->db->escape($data['u_up_url'])."',
  	r_store='".$this->db->escape($data['r_store'])."',
  	r_lang='".$this->db->escape($data['r_lang'])."',
  	r_model=".(int)$data['rules']['model'].",
  	r_sku=".(int)$data['rules']['sku'].",
  	r_name=".(int)$data['rules']['name'].",
  	r_made_url=".(int)$data['r_made_url'].",
  	r_made_meta=".(int)$data['r_made_meta'].",
  	r_price=".(int)$data['rules']['price'].",
  	r_price_spec='".$this->db->escape($data['r_price_spec'])."',
  	r_price_spec_groups='".$this->db->escape($data['r_price_spec_groups'])."',
  	r_price_spec_date_start='".$this->db->escape($data['r_price_spec_date_start'])."',
  	r_price_spec_date_end='".$this->db->escape($data['r_price_spec_date_end'])."',
  	r_cost='".$this->db->escape($data['r_cost'])."',
  	r_quant=".(int)$data['rules']['quant'].",
  	r_status_zero=".(int)$data['r_status_zero'].",
  	r_manufac=".(int)$data['rules']['manufac'].",
  	r_manufac_made_url=".(int)$data['r_manufac_made_url'].",
  	r_manufac_made_meta=".(int)$data['r_manufac_made_meta'].",
  	r_des=".(int)$data['rules']['des'].",
  	r_des_dir=".(int)$data['rules']['des_dir'].",
  	r_cat=".(int)$data['rules']['cat'].",
  	r_cat_perent=".(int)$data['r_cat_perent'].",
  	r_cat_made_url=".(int)$data['r_cat_made_url'].",
  	r_cat_made_meta=".(int)$data['r_cat_made_meta'].",
  	r_img=".(int)$data['rules']['img'].",
  	r_img_dir=".(int)$data['rules']['img_dir'].",
  	r_attr=".(int)$data['rules']['attr'].",
  	r_opt=".(int)$data['r_opt'].",
		r_attr_group=".(int)$data['r_attr_group'].",
  	r_upc=".(int)$data['r_upc'].",
  	r_ean=".(int)$data['r_ean'].",
  	r_jan=".(int)$data['r_jan'].",
  	r_isbn=".(int)$data['r_isbn'].",
  	r_mpn=".(int)$data['r_mpn'].",
  	r_location=".(int)$data['r_location'].",
  	r_minimum=".(int)$data['r_minimum'].",
  	r_subtract=".(int)$data['r_subtract'].",
  	r_length=".(int)$data['r_length'].",
  	r_width=".(int)$data['r_width'].",
  	r_height=".(int)$data['r_height'].",
  	r_length_class_id=".(int)$data['r_length_class_id'].",
  	r_weight=".(int)$data['r_weight'].",
  	r_weight_class_id=".(int)$data['r_weight_class_id'].",
  	r_status=".(int)$data['r_status'].",
  	r_tags=".(int)$data['r_tags'].",
  	r_layout_pr=".(int)$data['r_layout_pr'].",
  	r_sort_order=".(int)$data['r_sort_order'].",
  	r_related=".(int)$data['r_related'].",
  	r_hpm=".(int)$data['r_hpm']."
  	WHERE `dn_id`=".(int)$dn_id
  );

  //настройки браузера.
  if(empty($data['webp_conv'])){ $data['webp_conv'] == 0;}

  $this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET 
  	cache_page = ".(int)$data['cache_page'].",
  	webp_conv = ".(int)$data['webp_conv']." 
  	WHERE dn_id =".(int)$dn_id
  );

}

//Получение параметров парсинга для выбора. И для работы парсера
public function getParsParams($dn_id){
	//Получаем все параметры парсинга. Если у они есть.
  $rows = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_param WHERE dn_id=".(int)$dn_id."  ORDER BY type, id ASC")->rows;
  $params = [];
  foreach ($rows as $key => $param) {
  	if($param['type'] == 2) { $param['name'] = '@ '.$param['name']; }
  	$params[$param['id']] = $param;
  }

  #$this->wtfarrey($params);
  return $params;
}

#Фунция перебора категорий. Для первого вызова используй madeCatTree(1)
public function madeCatTree($i=0, $categories=[], $parent_id = 0, $parent_name = '', $language_id=0){
	//моя доработака
  if($i != 0){
  	//Получаем id языка
		$language_id = $this->getLangDef();

  	$query = $this->db->query("SELECT c.category_id, c.parent_id, d.name FROM ". DB_PREFIX ."category c INNER JOIN ". DB_PREFIX ."category_description d ON c.category_id = d.category_id WHERE d.language_id =".(int)$language_id." ORDER by d.name");

    $category_data = array();
    foreach ($query->rows as $row) {
      $category_data[$row['parent_id']][$row['category_id']] = $row;
    }
    $output = array();
    $output += $this->madeCatTree(0, $category_data);

  }else{
    //Стандартная фунция ниже
    $output = array();

    if (array_key_exists($parent_id, $categories)) {
      if ($parent_name != '') {
        $parent_name .= '->';
      }

      foreach ($categories[$parent_id] as $category) {
        $output[$category['category_id']] = $parent_name . $category['name'];
        $output += $this->madeCatTree(0, $categories, $category['category_id'], $parent_name . $category['name']);
      }
    }
  }

  return $output;
}

//Получение настроек пр в магазин
public function getPrSetup($dn_id){
	$setup = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_prsetup WHERE `dn_id`=".(int)$dn_id);
  $setup = $setup->row;
  #$this->wtfarrey($setup);
  return $setup;
}

//проверка присуцтвует ли товар
public function checkProduct($data, $setting, $link, $dn_id){
	$do['add'] = ['permit' => 0, 'pr_id' => 0];
	$do['up']  = ['permit' => 0, 'pr_id' => 0];
	
	#$this->wtfarrey($data);

	#Первичная проверка
	if(empty($data[$setting['sid']])){

		if($setting['sid'] == 'model' && $setting['r_model'] == 1){
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];
		}else{
			#нету идентификатора товара для создания
			$log['sid'] = $setting['sid'];
			$log['link'] = $link;
			$this->log('NoSid', $log, $dn_id);
		}


	}elseif($setting['sid'] == 'sku'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku='".$this->db->escape($data['sku'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'upc'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE upc='".$this->db->escape($data['upc'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'ean'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE ean='".$this->db->escape($data['ean'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'jan'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE jan='".$this->db->escape($data['jan'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'isbn'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE isbn='".$this->db->escape($data['isbn'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'mpn'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE mpn='".$this->db->escape($data['mpn'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'location'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE location='".$this->db->escape($data['location'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'name'){

		$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_description WHERE name='".$this->db->escape($data['name'])."' LIMIT 1");
		if($check->num_rows > 0){
			$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
			$do['add'] = ['permit' => 0, 'pr_id' => 0];
		}else{
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];

		}

	}elseif($setting['sid'] == 'model'){

		//если модель парсится.
		if($setting['r_model'] == 2){

			$check = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model='".$this->db->escape($data['model'])."' LIMIT 1");
			if($check->num_rows > 0){
				$do['up'] = ['permit' => 1, 'pr_id' => $check->row['product_id']];
				$do['add'] = ['permit' => 0, 'pr_id' => 0];
			}else{
				$do['up'] = ['permit' => 0, 'pr_id' => 0];
				$do['add'] = ['permit' => 1, 'pr_id' => 0];

			}

		}elseif($setting['r_model'] == 1){
			//если модель формируется по умолчанию
			$do['up'] = ['permit' => 0, 'pr_id' => 0];
			$do['add'] = ['permit' => 1, 'pr_id' => 0];
		}

	}else{

		$log ='';
		$this->log('addProductNoSidCheck', $log, $dn_id);
	}
	#$this->wtfarrey($setting['sid']);
	return $do;
}

//Фунция добавления url к товару.
#$do - это массив который содержит 2 параметра. 1. Кому присваем, 2 что делаем обновляем или добавляем.
public function addSeoUrl($url, $id, $setting, $langs, $stores, $dn_id, $do){

	//проверяем кому мы создаем url
	if($do['where'] == 'pr'){
		$query = 'product_id=';
	}elseif($do['where'] == 'cat'){
		$query = 'category_id=';
	}elseif($do['where'] == 'manuf'){
		$query = 'manufacturer_id=';
	}else{
		$query = 'error_id='; #Заглушка мало ли. :)
	}
	$logs['where'] = $query.$id;
	//обрезаем если длинее 255 символов
	$url = substr($url, 0, 254);

	//Проверяем с каикм движком мы работаем.
	if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'opencart2'){

		//смотрим на действие если обновить тогда удаляем запись.
		if($do['what'] == 'up'){
			$this->db->query("DELETE FROM `".DB_PREFIX."url_alias` WHERE `query`='".$this->db->escape($query).(int)$id."'");
		}
		//проверяем если ли такая запись.
		$chek_url = $this->db->query("SELECT * FROM `".DB_PREFIX."url_alias` WHERE `keyword`='".$this->db->escape($url)."'");

		if($chek_url->num_rows > 0){
			$url = $id.'-'.$url;
			$url = substr($url, 0, 254);
		}

		//Создаем url товару
		$this->db->query("INSERT INTO `".DB_PREFIX."url_alias` SET `query`='".$this->db->escape($query).(int)$id."',`keyword`='".$this->db->escape($url)."'");
		$logs['url'] = $url;

	}elseif($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){

		//смотрим на действие если обновить тогда удаляем запись.
		if($do['what'] == 'up'){
			foreach ($langs as $lang) {
				$this->db->query("DELETE FROM `".DB_PREFIX."seo_url` WHERE `query`='".$this->db->escape($query).(int)$id."' AND `language_id`=".(int)$lang['language_id']);
			}
		}
		//проверяем если ли такая запись.
		$chek_url = $this->db->query("SELECT * FROM `".DB_PREFIX."seo_url` WHERE `keyword`='".$this->db->escape($url)."'");

		if($chek_url->num_rows > 0){
			$url = $id.'-'.$url;
			$url = substr($url, 0, 254);
		}

		foreach($langs as $lang){
			//Создаем url товару
			foreach ($stores as $store) {
				$this->db->query("INSERT INTO `".DB_PREFIX."seo_url` SET
					`store_id`=".$store['store_id'].",
					`language_id`=".(int)$lang['language_id'].",
					`query`='".$this->db->escape($query).(int)$id."',
					`keyword`='".$this->db->escape($url)."'");
			}
		}
		$logs['url'] = $url;
	}
	$this->log('LogAddSeoUrl', $logs, $dn_id);
}

//Поучаем id атрибута
public function getIdAttr($name){
	$name = substr(trim($name), 0, 256);
	#Убираем двое точие в конце атрибута.
	if(substr($name, -1) == ':'){ $name = substr($name, 0, -1); }
	#Вдруг имя атрибута стало пустым.
	if(empty($name)){
		return 0;
	}

	$rows = $this->db->query("SELECT `attribute_id` as attr_id FROM `".DB_PREFIX."attribute_description` WHERE `name` ='".$this->db->escape($name)."'");
	if($rows->num_rows == 0){
		$attr_id = 0;
	}else{
		$attr_id = $rows->row['attr_id'];
	}
	return $attr_id;

}

//Создаем атрибут и возврашаем его id
public function addAttr($name, $langs, $setting, $dn_id){
	$name = substr(trim($name), 0, 256);
	$attr_id = 0;
	#Убираем двое точие в конце атрибута.
	if(substr($name, -1) == ':'){ $name = substr($name, 0, -1); }
	#Вдруг имя атрибута стало пустым.
	if(empty($name)){
		return $attr_id;
	}

	$this->db->query("INSERT INTO `".DB_PREFIX."attribute` SET `attribute_group_id`='".(int)$setting['r_attr_group']."',`sort_order`=0");
	$attr_id = $this->db->getLastId();

	//проверяем что бы создался
	if($attr_id > 0){
		#Записываем в дескрипшн.
		foreach($langs as $lang){
			$this->db->query("INSERT INTO ".DB_PREFIX."attribute_description SET attribute_id = '".(int)$attr_id."', language_id = '".(int)$lang['language_id']."', name = '".$this->db->escape($name)."'");
		}
		//Сообшаем о создании нового атрибута.
		$log = ['attr_name' => $name, 'r_attr_group' => $setting['r_attr_group']];
		$this->log('AddNewAttr', $log, $dn_id);
	}else{
		$log = ['attr_name' => $name, 'r_attr_group' => $setting['r_attr_group']];
		$this->log('NoAddNewAttr', $log, $dn_id);
	}

	return $attr_id;
}

//Создаем производителя.
public function addManuf($form, $langs, $setting, $stores, $dn_id){
	$name = trim($form['manufac']);
	if(empty($name)){
		return 0;
	}
	//Сео данные
	$data['meta_h1'] = '';
	$data['meta_title'] = '';
	$data['meta_description'] = '';
	$data['meta_keyword'] = '';

	//Проверяем работу с SEO данными
	if($setting['r_manufac_made_meta'] ==1){
		$data['meta_h1'] = $form['manuf_seo_h1'];
		$data['meta_title'] = $form['manuf_seo_title'];
		$data['meta_description'] = $form['manuf_seo_desc'];
		$data['meta_keyword'] = $form['manuf_seo_keyw'];
	}

	$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name = '".$this->db->escape($name)."', sort_order = 0");
	$manuf_id = $this->db->getLastId();

	//Проверяем на каком движке работаем. Если OcStore тогда добавляем еше manufacturer_description
	if($setting['vers_op']=='ocstore2'){

		//Добавляем в таблицу oc_manufacturer_description
		foreach($langs as $lang){
			$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description`	SET
				`manufacturer_id`=".(int)$manuf_id.",
				`language_id`=".(int)$lang['language_id'].",
				`name`='".$this->db->escape($name)."',
				`meta_h1`='".$this->db->escape($data['meta_h1'])."',
				`meta_title`='".$this->db->escape($data['meta_title'])."',
				`meta_description`='".$this->db->escape($data['meta_description'])."',
				`meta_keyword`='".$this->db->escape($data['meta_keyword'])."'
				");
		}

	}elseif($setting['vers_op']=='ocstore3'){

		//Добавляем в таблицу oc_manufacturer_description
		foreach($langs as $lang){
			$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET
				`manufacturer_id`=".(int)$manuf_id.",
				`language_id`=".(int)$lang['language_id'].",
				`meta_h1`='".$this->db->escape($data['meta_h1'])."',
				`meta_title`='".$this->db->escape($data['meta_title'])."',
				`meta_description`='".$this->db->escape($data['meta_description'])."',
				`meta_keyword`='".$this->db->escape($data['meta_keyword'])."'
				");
		}

	}

	//Создаем таблицу c_manufacturer_to_store
	foreach ($stores as $store) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id`=".(int)$manuf_id.",`store_id`=".$store['store_id']);
	}


	$this->log('addManuf', $log = ['id'=>$manuf_id, 'name'=>$name], $dn_id);

	//////////////////////////////////////////////////
	//Работа с SEO_URL
	// 0 - Незаполнять
	// 1 - Создать из имени товара
	// 2 - Создать по шаблону заполненому на вкладке SEO
	////////////////////////////////////////////////////
	if($setting['r_manufac_made_url'] == 1){

		//Получаем юрл из имени.
		if(!empty($name)){
			$manuf_url = $this->madeUrl($name);

			//Записываем url
			$do = ['where'=>'manuf','what'=>'add'];
			$this->addSeoUrl($manuf_url, $manuf_id, $setting, $langs, $stores, $dn_id, $do);
		}else{
			$logs['name'] = 'manufacture name';
			$this->log('badUrl', $logs, $dn_id);
		}

	}elseif($setting['r_manufac_made_url'] == 2){

		if(!empty($form['manuf_seo_url'])){
			//Получаем юрл из имени.
			$manuf_url = $this->madeUrl($form['manuf_seo_url']);

			//Записываем url
			$do = ['where'=>'manuf','what'=>'add'];
			$this->addSeoUrl($manuf_url, $manuf_id, $setting, $langs, $stores, $dn_id, $do);
		}else{
			$logs['name'] = 'seo_url';
			$this->log('badUrl', $logs, $dn_id);
		}
	}

	return $manuf_id;
}

//Преобразование фото.
public function madeImgArrey($image, $url){
	//преобразовываем фото
	if(!empty($image)){
		$domain = parse_url($url);
		#Делаем из строки массив категорий.
		$image = $this->madeLogicalMathem($image, 'str');

		$imgs = explode('{csvnc}', $image);
		#Убираем из массива пустые значения
		foreach($imgs as $var){
			//Удаляем лишние проблеы
			$var = trim($var);
			if($var != false){
				//Добавлем нужные элементы к ссылке.
				if($var[0] == '/' && $var[1] != '/'){
					$var = $domain['scheme'].'://'.$domain['host'].$var;

				}elseif($var[0] == '/' && $var[1] == '/'){
					$var = str_ireplace('//', $domain['scheme'].'://',$var);
				}
				$img[] = $var;
			}
		}

		if(!empty($img)){
			$img = array_unique($img);
		}else{
			$img = [];
		}
	}else{
			$img = [];
	}
	#$this->wtfarrey($img);
	return $img;
}

//Преобразование категорий
public function madeCatArrey($category){
	//Преобразования категорий
	if(!empty($category)){
		#Делаем из строки массив категорий.
		$category = $this->madeLogicalMathem($category, 'str');
		$cats = explode('{csvnc}', $category);
		#Убираем из массива пустые значения
		foreach($cats as $var){
			if($var != false){
				$cat[] = $var;
			}
		}

		if(empty($cat)){
			$cat = [];
		}
		#$this->wtfarrey($cat);
		return $cat;
	}

}
//найти сушествует такая категория или нет.
public function findCategory($cat_way){
	$cat_way = trim($cat_way);
	$cat_id = 0;
	if(!empty($cat_way)){
		$cat_tree = $this->madeCatTree(1);

		if(!$cat_id =	array_search($cat_way, $cat_tree)){ $cat_id = 0; }
		#$this->wtfarrey($cat_tree);
		#$this->wtfarrey($cat_way);
	}
	return $cat_id;
}

//Получить id категорий которые мы запрашиваем в массиве Масси должен быть одномерным. Значения идут от родительской категории к дочерней.
//Возврашается ассоциативный массив где ключи в обратном порядке. От дочерней к родительской!!!
public function getCategorysId($cats){
	$ids[0] = 0; #Заглушка на всякий случай.

	if(!empty($cats)){
		//Получаем дерево категорий что есть в магазине.
		$cat_tree = $this->madeCatTree(1);
		//Формируем по очередности имена категорий и проверяем есть ли в магазине.
		$cat_way = '';
		foreach ($cats as $key => $cat) {
			if ($key == 0) {
				$cat_way = trim($cat);
				if(!$ids[0] =	array_search($cat_way, $cat_tree)){ $ids[0] = 0; }

			} else {
				$cat_way .= '->'.trim($cat);
				if(!$ids[] =	array_search($cat_way, $cat_tree)){ $ids[] = 0; }
			}
		}

	}
	$ids = array_reverse($ids);
	//Возврашаем массив.
	#$this->wtfarrey($ids);
	return $ids;
}

//Фунция добавления товара в категорию
//Принимает массив id категорий. id товара. И настройки
public function addProdToCat($cats, $pr_id, $setting, $add_new='0'){
	#$this->wtfarrey($cats);
	$log = '';

	//Производим запись данных в категорию.
	foreach ($cats as $key => $cat_id) {

		if($key == 0) { $log = $cat_id; } else { $log .= ','.$cat_id;}

		//Если это только первая итерация. И это движок ocStor тогда мы записываем как главная категория.
		if( $key ==0 && empty($add_new) && ($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3')){
			//Добавление товар в категорию ocStore
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET
						product_id = '" . (int)$pr_id . "',
						category_id = '" . (int)$cat_id . "',
						main_category = 1");
		}else{
			//Добавление товар в категорию Opencart
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET
				product_id = '" . (int)$pr_id . "',
				category_id = '" . (int)$cat_id."'");
		}

		//Останавливаем зпись товара в категории. По настройке показывать в.
		# 0 - Только в младшей.
		# 1 - В младшей и в одной родительской.
		# 2 - В младщей и во всех родительских.
		if ($setting['r_cat_perent'] == 0) { break; } elseif ($setting['r_cat_perent'] == 1 && $key == 1) { break; }

		# Дополнительно если первая категория пустая то родителей не добавляем
		if($key == 1 && $cat_id == 0){ break; }

	}

	//отправляем лог 
	return $log;
}

//Добавляем атрибу в товар.
public function addAttrToProduct($pr_id, $attr, $langs, $dn_id){
	#$this->wtfarrey($attr);
	if( !empty($attr[1]) ){ $attr[1] = trim($attr[1]); }	
	//перед тем как производить запись новых атриубтов в товар производим удаление.
	$this->db->query("DELETE FROM `".DB_PREFIX."product_attribute` WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);
	//Добавляем
	foreach($langs as $lang){
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id`=".(int)$pr_id.", `attribute_id`=".(int)$attr['id'].", `language_id`=".(int)$lang['language_id'].", `text`='".$this->db->escape($attr[1])."'");
	}
	$log = ['name' => $attr[0], 'value'=>$attr[1], 'attr_id'=> $attr['id']];
	$this->log('addAttrToProductLog', $log, $dn_id);
}

//Добавление акционных цен.
public function addPriceSpecToProduct($price_spec, $setting, $pr_id, $dn_id){
	//Получаем список выбранных групп пользователей.
	$cast_groups = $this->getGroupCustomer($setting);

	//Переменная для логов.
	$group = '';

	//перебираем все группы.
	foreach ($cast_groups as $cast_group) {
		//Удаляем акцию если такая есть.
		$special_id = $this->db->query("SELECT product_special_id FROM ".DB_PREFIX."product_special 
			WHERE product_id=".(int)$pr_id." AND customer_group_id=".(int)$cast_group['customer_group_id']);

		if($special_id->num_rows == 0){
			
			//Создаем заново акцию.
			$this->db->query("INSERT INTO ".DB_PREFIX."product_special SET
				product_id = ".(int)$pr_id.",
				customer_group_id = ".(int)$cast_group['customer_group_id'].",
				priority = 1,
				price = ".$price_spec.",
				date_start = '".$this->db->escape($setting['r_price_spec_date_start'])."',
				date_end = '".$this->db->escape($setting['r_price_spec_date_end'])."'");

		}elseif($special_id->num_rows > 0){

			//Создаем заново акцию.
			$this->db->query("UPDATE ".DB_PREFIX."product_special SET
				product_id = ".(int)$pr_id.",
				customer_group_id = ".(int)$cast_group['customer_group_id'].",
				priority = 1,
				price = ".$price_spec.",
				date_start = '".$this->db->escape($setting['r_price_spec_date_start'])."',
				date_end = '".$this->db->escape($setting['r_price_spec_date_end'])."' 
				WHERE product_special_id =".$special_id->row['product_special_id']);

		}

		//Записываем id в переменную для логов.
		$group .= ','.$cast_group['customer_group_id'];

	}

	$logs = [
						'price_spec'=>$price_spec,
						'group'=>$group,
						'date'=> $setting['r_price_spec_date_start'].' - '.$setting['r_price_spec_date_end']
					];
	$this->log('addPriceSpecToProduct', $logs, $dn_id);
}

//Удаление акции
public function delPriceSpecToProduct($price_spec, $setting, $pr_id, $dn_id){
	$sql = "DELETE FROM ".DB_PREFIX."product_special WHERE product_id=".(int)$pr_id;
	#$this->wtfarrey($sql);
	$this->db->query($sql);
}

//Создание категорий исходя из дерева категорий, и сушествующих категорий.
public function addCat($form, $setting, $langs, $stores,$dn_id){

	$cat = $form['cat'];
	#$this->wtfarrey($form);
	#Получаем категории из базы в нужном виде.
	$cat_tree = $this->madeCatTree(1);
	#Проверяем и создаем категории если такой нет.
	$cat_way = '';

	//Данные по умолчани для создания категорий.
	$cat_id = 0;
	$data['parent_id'] = 0; #id родительско категории
	$data['image'] = '';
	$data['top'] = 0;
	$data['column'] = 1;
	$data['sort_order'] = 0;
	$data['status'] = 1;
	//Сео данные
	$data['meta_h1'] = '';
	$data['description'] = '';
	$data['meta_title'] = '';
	$data['meta_description'] = '';
	$data['meta_keyword'] = '';

	if($setting['r_cat_made_meta'] ==1){
		$data['meta_h1'] = $form['cat_seo_h1'];
		$data['meta_title'] = $form['cat_seo_title'];
		$data['meta_description'] = $form['cat_seo_desc'];
		$data['meta_keyword'] = $form['cat_seo_keyw'];
	}

	//Язык по умолчанию.
	$language_default_id = $this->getLangDef();

	//проверяем есть ли в языках стандартный язык системы. Если нет добавляем.
	if(array_search($language_default_id, array_column($langs, 'language_id')) === false){
		$langs[] = ['language_id' => $language_default_id];
	}

	#$this->wtfarrey($langs);

	foreach($cat as $key => $name){
		#Очешаем от лишнего
		$name = trim($name);
		#Составляем путь для сравнения
		if($key == 0){$cat_way = $name;}else{$cat_way = $cat_way .= '->'.$name;}

		#Сравниваем.
		$cat_id =	array_search($cat_way, $cat_tree);

		#Если такой категории нет создаем ее.
		if($cat_id == 0){
			#Узнаем родительская это категория или нет. Что бы понять как ее создать
			if($key == 0){
				$data['top'] = 1;
				$data['parent_id'] = 0;
			}else{
				$data['top'] = 0;
			}

			//Добавляем в базу oc_category
			$this->db->query("INSERT INTO " . DB_PREFIX . "category SET
				parent_id = '" . (int)$data['parent_id'] . "',
				`top` = '" . (int)$data['top'] . "',
				`column` = '" . (int)$data['column'] . "',
				sort_order = '" . (int)$data['sort_order'] . "',
				status = '" . (int)$data['status'] . "',
				date_modified = NOW(),
				date_added = NOW()");

			$cat_id = $this->db->getLastId();

			//проверяем стоит ли дальше создавать
			if($cat_id){

				//Проверяем версию движка для правильного заполнения.
				$mh1 = '';
				if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
					$mh1 = ",meta_h1='".$this->db->escape($data['meta_h1'])."'";
				}

				//Добавлем в базу oc_category_description
				foreach($langs as $lang){
					$sql = "INSERT INTO " . DB_PREFIX . "category_description SET
						category_id = '" . (int)$cat_id . "',
						language_id = '" . (int)$lang['language_id'] . "',
						name = '" . $this->db->escape($name) . "',
						description = '". $this->db->escape($data['description']) ."',
						meta_title = '" . $this->db->escape($data['meta_title']) . "',
						meta_description = '" . $this->db->escape($data['meta_description']) . "',
						meta_keyword = '" . $this->db->escape($data['meta_keyword'])."'".$mh1;
					$this->db->query($sql);
				}

				//Добавляем в таблицу oc_category_to_store, и oc_category_to_layout
				foreach ($stores as $store){

					$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "category_to_store SET
						category_id = '" . (int)$cat_id . "',
						store_id = " . $store['store_id']);

					//Добавляем данные макета товар
					if(!empty($form['layout_cat'])){
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_to_layout SET
							category_id = '".(int)$cat_id."',
							store_id = '".$store['store_id']."',
							layout_id = '".(int)$form['layout_cat']."'");
					}

				}

			}#Конец проверки на созданную категорию в oc_category


			#передаем id родителя для следующей категории.
			$data['parent_id'] = $cat_id;
			#Пишем в лог информацию о создании категории.
			$log['id'] = $cat_id;
			$log['cat_way'] = $cat_way;

			$this->log('addCat', $log, $dn_id);

			//записываем в логи сведения про добавления макета.
			if(!empty($form['layout_cat'])){
				$logs = ['cat_id' => (int)$cat_id, 'layout_id' => (int)$form['layout_cat']];
				$this->log('addCatToLayout', $logs, $dn_id);
			}

			//////////////////////////////////////////////////
				//Работа с SEO_URL
				// 0 - Незаполнять
				// 1 - Создать из имени товара
				// 2 - Создать по шаблону заполненому на вкладке SEO
				////////////////////////////////////////////////////
				if($setting['r_cat_made_url'] == 1){

					//Получаем юрл из имени.
					if(!empty($name)){
						$cat_url = $this->madeUrl($name);

						//Записываем url
						$do = ['where'=>'cat','what'=>'add'];
						$this->addSeoUrl($cat_url, $cat_id, $setting, $langs, $stores, $dn_id, $do);
					}else{
						$logs['name'] = 'category name';
						$this->log('badUrl', $logs, $dn_id);
					}

				}elseif($setting['r_cat_made_url'] == 2){

					if(!empty($form['cat_seo_url'])){
						//Получаем юрл из имени.
						$cat_url = $this->madeUrl($form['cat_seo_url']);

						//Записываем url
						$do = ['where'=>'cat','what'=>'add'];
						$this->addSeoUrl($cat_url, $cat_id, $setting, $langs, $stores, $dn_id, $do);
					}else{
						$logs['name'] = 'seo_url';
						$this->log('badUrl', $logs, $dn_id);
					}
				}


		}else{
			#передаем id родителя для следующей категории.
			$data['parent_id'] = $cat_id;
		}

	}
	//Тестовая фунция репаир категорий. Незнаю нужно или нет но потестируем. Магическая хрень, делаем категории видимыми.
	$this->repairCategories();
	if(empty($cat_id)) $cat_id = 0;
	#return $cat_id;
}

//Преобразование атрибутов.
public function madeAttrArrey($attrs){
	if(!empty($attrs)){
		//Для совместимости переводим в единый стандарт.
		$attrs = $this->madeLogicalMathem($attrs, 'str');

		$attrs = explode('{csvnc}', $attrs);
		#$this->wtfarrey($attrs);
		//Удаляем все пустые значения из начала массива.
		foreach($attrs as $key => $var){

			if(empty(trim($var))){
	      unset($attrs[$key]);
	    }else{
	      break;
	    }

		}
		$attrs = array_values($attrs);
		#$this->wtfarrey($attrs);
		//Здесь закопано 2 процесса. 1) записываем в массив значение. 2) Делим отдельный атрибут на отдельный массив.
		$i = 1;
		foreach($attrs as $key => $var){
			$attr[$i][] = $var;

			if($key % 2 ==1){
				$i++;
			}

		}

		//Проверяем что предыдушие правила не вычистили все что было в массиве.
		if (!empty($attr)) {
			#$this->wtfarrey($attr);
			//Удаляем атрибуты без имени или без значения. Те массивы где не полная пара.
			foreach($attr as $key => $value){
				if(empty($value[1])){
					$attr[$key][1]  = '';
				}
			}
		} else {
			//Если масси пришел пустым все же отдаем его.
			$attr = [];
		}

	} else {
		$attr = [];
	}
	#$this->wtfarrey($attr);
	return $attr;
}

//Функция создания ссылок. На вход должна поступать строка. На выходе строка форматированная для url
public function madeUrl($data){
	//Преобразовываем сушности.
	$data = html_entity_decode($data);
	//переводим русские символы в латиницу
  $data = $this->symbolToEn($data);
	//Заменяем все пробелы на тире
	$data = str_replace(' ', '-', $data);
	//Удалем все кроме латинских букв, цифр и знака тире.
 	$data = preg_replace('#[^A-Za-z0-9\-\_]#', '', $data);
 	//Наводим марафет, убераем по два и более тре подряд.
 	$data = preg_replace('/-+/', '-', $data);
 	$data = preg_replace('/_+/', '_', $data);
 	//Приводим к нижнему регистру. Незнаю зачем но кажется так луче :)
  $data = mb_strtolower($data);

 	return $data;
}

//Получаем сушествующие группы атрибутов.
public function getAttrGroup(){
	//Получаем id языка
	$language_id = $this->db->query("SELECT language_id FROM ".DB_PREFIX."language WHERE status=1");
	$language_id = $language_id->row['language_id'];
	if(empty($language_id)){ $language_id = 0;}

	//Немного по тупому сделал. Но что имеем. Пока что с языками все сложно.
	$attr_group = [];
	$attr_group = $this->db->query("SELECT * FROM `".DB_PREFIX."attribute_group_description` WHERE language_id='".$language_id."'");
	$attr_group = $attr_group->rows;
	$attr_group = array_column($attr_group, 'name', 'attribute_group_id');
	unset($attr_group[1]);

	return $attr_group;
}

//Получаем id мануфактуры
public function getIdManuf($name){
	$name = trim($name);
	$rows = $this->db->query("SELECT * FROM `".DB_PREFIX."manufacturer` WHERE `name`='".$this->db->escape($name)."'");

	if($rows->num_rows == 0){
		$manuf_id = 0;
	}else{
		$manuf_id = $rows->row['manufacturer_id'];
	}
	return $manuf_id;
}

//Получаем список магазинов.
public function getStore($setting){
	//Полюбому отдаем массив
	$store = [];
	foreach ($setting['r_store'] as $key => $value) {
		$store[] = ['store_id' => $value];
	}
	#$this->wtfarrey($store);
	return $store;
}
//Получаем список магазинов.
public function getAllStore(){
	$store = [];
	$store[] = ['store_id'=>0, 'name'=>'Главный'];
	$stores = $this->db->query("SELECT store_id, name FROM ".DB_PREFIX."store");
	if ($stores->num_rows > 0) {
		foreach ($stores->rows as $row) {
			$store[] = $row;
		}
	}

	#$this->wtfarrey($store);
	return $store;
}

//Получаем список всех групп покупателей.
public function getAllGroupCustomer(){
	$cast_groups = $this->db->query("SELECT c.customer_group_id, d.name
		FROM ".DB_PREFIX."customer_group c INNER JOIN ".DB_PREFIX."customer_group_description d
		ON c.customer_group_id = d.customer_group_id
		WHERE d.language_id = 1");
	$cast_groups = $cast_groups->rows;

	if (!empty($cast_groups)) {
		$cast_groups = array_column($cast_groups, 'name', 'customer_group_id');
	}

	#$this->wtfarrey($cast_groups);
	return $cast_groups;
}

//Получаем список всех групп покупателей.
public function getGroupCustomer($setting){
	//проверяем какую группу выбрал пользователь
	$sql = '';
	if ($setting['r_price_spec_groups'] !='all') {
		$sql = ' WHERE customer_group_id='.$setting['r_price_spec_groups'];
	}
	$cast_groups = $this->db->query("SELECT customer_group_id FROM ".DB_PREFIX."customer_group".$sql);
	$cast_groups = $cast_groups->rows;

	return $cast_groups;
}

//получаем единицы длины
public function getLengthClassId(){
	$length_class_id = $this->db->query("SELECT * FROM ".DB_PREFIX."length_class_description WHERE language_id = 1");
	$length_class_id = $length_class_id->rows;
	return $length_class_id;
}

//получаем единицы веса
public function getWeightClassId(){
	$weight_class_id = $this->db->query("SELECT * FROM ".DB_PREFIX."weight_class_description WHERE language_id = 1");
	$weight_class_id = $weight_class_id->rows;
	return $weight_class_id;
}

//Получаем выбранный язык
public function getLang($setting){
	//проверяем какой язык выбра пользователем. По умолчанию все.
	$langs = [];

	foreach ($setting['r_lang'] as $key => $value) {
		$langs[] = ['language_id' => $value];
	}

	#$this->wtfarrey($langs);
	return $langs;
}

//Получаем список языков
public function getAllLang(){
	//Проверяем какой язык выбран.
	$lang = $this->db->query("SELECT language_id, name FROM ".DB_PREFIX."language");
	return $lang->rows;
}

//Получаем список статусов
public function getAllStockStatus(){

	//Получаем id языка
	$language_id = $this->db->query("SELECT language_id FROM ".DB_PREFIX."language WHERE status=1");
	$language_id = $language_id->row['language_id'];
	if(empty($language_id)){ $language_id = 0;}

	//Проверяем какой язык выбран.
	$stock_status = $this->db->query("SELECT * FROM `".DB_PREFIX."stock_status` WHERE language_id =".$language_id);

	return $stock_status->rows;
}

//получаем список опций
public function getAllOpts(){
	$language_id = $this->getLangDef();
	$options = $this->db->query("SELECT o.option_id, d.name FROM `".DB_PREFIX."option` o INNER JOIN ".DB_PREFIX."option_description d ON o.option_id = d.option_id WHERE d.language_id =".(int)$language_id." ORDER BY o.option_id");
	$options = $options->rows;
	#$this->wtfarrey($options);
	return $options;
}

//Определяем статус для товара
public function getProductStatus($data, $setting){
	////////////////////////////////////////////////
	// 1 = Не отключать товар
	// 2 = Отключить товар
	// 3 = Отключить товар при нулевом остатке
	////////////////////////////////////////////////

	#По умолчанию товарам
	$status = 1;
	$data['status'] = (int)$data['status'];
	if ($data['status'] == 1) {
		$status = 1;
	} elseif ($data['status'] == 2) {
		$status = 0;
	} elseif (($data['status'] == 3) && $setting['r_quant'] && $data['quant'] == 0) {
		$status = 0;
	} elseif (($data['status'] == 3) && $setting['r_quant'] && $data['quant'] != '0') {
		$status = 1;
	}

	return $status;
}

//фунйция подготовки данных option
public function madeOption($data){
	$opts = [];

	//вырезаем ненужное из опции
	$data['opt_name'] = str_replace('{csvnc}', '', $data['opt_name']);

	//Получаем имена опций.
	$opt_array = explode('{next}', $data['opt_name']);
	$opt_value = explode('{next}', $data['opt_value']);
	$opt_price = explode('{next}', $data['opt_price']);
	$opt_quant = explode('{next}', $data['opt_quant']);
	$opt_quant_d = explode('{next}', $data['opt_quant_d']);
	$opt_imgs = explode('{next}', $data['opt_imgs']);
	$opt_data = explode('{next}', $data['opt_data']);

	$quant_d = $opt_quant_d[0];
	foreach ($opt_array as $key => $opt_name ) {
		$add = 1; #маркер добавления массива с опцией.

		//Проверяем если текст для имени опции. Если нет тогда берем по умолчанию.
		$opt_name = explode('{|}', $opt_name);
		if (empty($opt_name[0]) && empty($opt_name[1])) {
			$add = 0;
		}

		//если есть имя или id опции тогда добавляем ее в массив.
		if ($add) {
			$opts[$key]['name'] = $opt_name[0];
			$opts[$key]['opt_id'] = $opt_name[1];
			$opts[$key]['value_data'] = explode('{csvnc}', $opt_value[$key]);
			$opts[$key]['price'] = explode('{csvnc}', $opt_price[$key]);
			$opts[$key]['quant'] = explode('{csvnc}', $opt_quant[$key]);
			if(empty($opt_quant_d[$key])){
				$opts[$key]['quant_d'] = $quant_d;
			}else{
				$opts[$key]['quant_d'] = $opt_quant_d[$key];
			}
			if(!empty($opt_imgs[$key])){
				$opts[$key]['imgs'] = explode('{csvnc}', $opt_imgs[$key]);
			}

			//преобразовываем дополнительные данные для опций.
			preg_match('#\{required_(.)?\}#', $opt_data[$key], $required);
			preg_match('#\{price_prefix_(.)?\}#', $opt_data[$key], $price_prefix);
			preg_match('#\{imgs_type_(.)?\}#', $opt_data[$key], $imgs_type);
			if (empty($required[1])){ $required[1] = 0; }
			if (empty($price_prefix[1])){ $price_prefix[1] = '+'; }
			if (empty($imgs_type[1])){ $imgs_type[1] = 0; }
			$opts[$key]['required'] = $required[1];
			$opts[$key]['price_prefix'] = $price_prefix[1];
			$opts[$key]['imgs_type'] = $imgs_type[1];

			//приводим опции их значения к единому стандарту, что бы на каждое значение были данные, нисмотря ни на что.
			//Приводим в соотвецтвие цену и количество к колву значений опции. Если нету значения опции удаляем ее цену и колво.
			$quant_d = (int)$opts[$key]['quant_d'];
			foreach ($opts[$key]['value_data'] as $key_v => $value) {
				$value = trim($value);

				if (!empty($value) || $value == '0') {

					$opts[$key]['value'][$key_v]['value_id'] = 0;
					$opts[$key]['value'][$key_v]['value'] = $this->madeLogicalMathem($value, 'str');

					if(empty($opts[$key]['price'][$key_v])){
						$opts[$key]['value'][$key_v]['price'] = '';
					}else{
						$opts[$key]['value'][$key_v]['price'] = $this->madeLogicalMathem($opts[$key]['price'][$key_v], 'int');
					}
					//количество опции по умолчанию. 
					if(empty($opts[$key]['quant'][$key_v])){
						$opts[$key]['value'][$key_v]['quant'] = $quant_d;
					}else{
						$opts[$key]['value'][$key_v]['quant'] = (int)$this->madeLogicalMathem($opts[$key]['quant'][$key_v], 'int');
					}
					
					//Раставляем фото опций. 
					if(empty($opts[$key]['imgs'][$key_v])){
						$opts[$key]['value'][$key_v]['imgs'] = '';
					}else{
						$opts[$key]['value'][$key_v]['imgs'] = $this->madeLogicalMathem($opts[$key]['imgs'][$key_v], 'str');
					}
					
					$opts[$key]['value'][$key_v]['imgs_type'] = $opts[$key]['imgs_type'];
					$opts[$key]['value'][$key_v]['price_prefix'] = $opts[$key]['price_prefix'];

				}
			}
			//Удаляем ненужные данные.
			unset($opts[$key]['value_data']);
			unset($opts[$key]['price']);
			unset($opts[$key]['quant']);
			unset($opts[$key]['imgs']);
			unset($opts[$key]['price_prefix']);
			unset($opts[$key]['imgs_type']);

		}

	}
	#$this->wtfarrey($opts);
	return $opts;
}

//Контроллер работы с опциями. Да будет так!
public function controlOption($data, $setting, $langs, $pr_id, $dn_id, $do='add'){
	//Послание в будущае 
	//Фунциия говно, перегружена, нужно делать на более мелкие. Если встретиш проблемы в логике не ленись подели.

	#$this->wtfarrey($data);
	//////////////////////////////////////////////////
	//Работа с Оption при добавлении товара
	// 0 - Не работать с опциями
  // 1 - Создавать, заполнять в товар
  // 2 - Заполнять в товаре без создания новых опций и значений опций
	////////////////////////////////////////////////////

	//////////////////////////////////////////////////
	//Работа с Оption при обновлении
	// 0 - Не работать с опциями
  // 1 - Обновить значения существующих опций
  // 2 - Добавить новые опции и обновить существующие
  // 3 - Добавить новый не обновлять существующие
  // 4 - Удалить все опции и загрузить заново
	////////////////////////////////////////////////////

	#$this->wtfarrey($setting);
	#$this->wtfarrey($data);

	if ( $do == 'add' && ($setting['r_opt'] == 1 || $setting['r_opt'] == 2) ){

		//записываем все id опций что пришли, и все id значений для удаления тех опций которых уже нет.
		$del_arrey = ['opt'=>'','value'=>''];
		foreach ($data as $key => &$opt) {

			//проверяем есть ли id опции, id выше имени опции.
			if (empty($opt['opt_id'])) {
				$opt['opt_id'] = $this->getOptId($opt['name']);
			}

			//Если r_opt'] == 2 нам нужно прервать эту итерацию без создания новой опции.
			if(empty($opt['opt_id']) && $setting['r_opt'] == 2){ continue; } 

			//Проверяем сново id опции, если 0 значит такой нет и нужно создать.
			if (empty($opt['opt_id'])) {
				$opt['opt_id'] = $this->addNewOpt($opt['name'], $langs, $dn_id);
			}

			//Проверяем что бы были значения в опциях.
			if(!empty($opt['value'])){
				//Проверяем есть ли в товаре такая опция.
				$product_option_id = $this->checkOptToProduct($pr_id, $opt['opt_id']);
				//если нет создаем запись.
				if (empty($product_option_id)) {
					$product_option_id = $this->addOptToProduct($opt, $setting, $langs, $pr_id, $dn_id);
				}

				//проверяем сушествуют ли значения опции.
				foreach ($opt['value'] as $key_v => &$value) {

					$value = $this->getOptionValueId($opt['opt_id'], $value);

					//Если r_opt == 2 нам нужно прервать эту итерацию без создания нового значения опции.
					if(empty($value['value_id']) && $setting['r_opt'] == 2){ continue; }

					//Если такого значения нет тогда создаем.
					if (empty($value['value_id'])) {
						$value = $this->addNewOptionValue($opt['opt_id'], $value, $setting, $langs, $dn_id);
					}

					//теперь проверяем если в товаре такая опция с таким значением.
					$pr_opt_value_id = $this->checkProductOptValue($opt, $value, $setting, $pr_id, $dn_id);
					//если такая запись есть обновляем ее.
					if (!empty($pr_opt_value_id)) {
						$this->doProductOptValue($opt, $value, $product_option_id, $pr_opt_value_id, $setting, $pr_id, $dn_id, $do='up');
					} else {
						//А если нету создаем.
						$this->doProductOptValue($opt, $value, $product_option_id, $pr_opt_value_id, $setting, $pr_id, $dn_id, $do='add');
					}

					//Записываем все id значений опций что бы удалить те которых уже нет.
					if(empty($del_arrey['value'])){ $del_arrey['value'] = $value['value_id']; }else{ $del_arrey['value'] .= ','.$value['value_id']; }
				}
			}

			//Записываем в списко все id опций что к нам пришли что бы удалить их товара те которые не пришли.
			if(empty($del_arrey['opt'])){ $del_arrey['opt'] = $opt['opt_id']; }else{ $del_arrey['opt'] .= ','.$opt['opt_id']; }
		}

	}elseif( $do = 'up' && ($setting['u_opt'] == 1 || $setting['u_opt'] == 2 || $setting['u_opt'] == 3 || $setting['u_opt'] == 4) ){

		//записываем все id опций что пришли, и все id значений для удаления тех опций которых уже нет.
		$del_arrey = ['opt'=>'','value'=>''];
		if ($setting['u_opt'] == 4){
			//Отправляем на удаление необновленных опций.
			$this->delOptNotBeenUpdate($del_arrey, $pr_id, $setting, $dn_id);
		}
		foreach ($data as $key => &$opt) {

			//проверяем есть ли id опции, id выше имени опции.
			if (empty($opt['opt_id'])) {
				$opt['opt_id'] = $this->getOptId($opt['name']);
			}

			//Если u_opt == 1 нам нужно прервать эту итерацию без создания новой опции.
			if(empty($opt['opt_id']) && $setting['u_opt'] == 1){ continue; } 

			//Проверяем сново id опции, если 0 значит такой нет и нужно создать.
			if (empty($opt['opt_id'])) {
				$opt['opt_id'] = $this->addNewOpt($opt['name'], $langs, $dn_id);
			}

			//Проверяем что бы были значения в опциях.
			if(!empty($opt['value'])){
				//Проверяем есть ли в товаре такая опция.
				$product_option_id = $this->checkOptToProduct($pr_id, $opt['opt_id']);

				//Если u_opt == 1 нам нужно прервать эту итерацию без создания новой опции в товар.
				if(empty($product_option_id) && $setting['u_opt'] == 1){ continue; } 

				//если нет создаем запись.
				if (empty($product_option_id)) {
					$product_option_id = $this->addOptToProduct($opt, $setting, $langs, $pr_id, $dn_id);
				}

				//проверяем сушествуют ли значения опции.
				foreach ($opt['value'] as $key_v => &$value) {

					$value = $this->getOptionValueId($opt['opt_id'], $value);

					//Если такого значения нет тогда создаем.
					if (empty($value['value_id'])) {
						$value = $this->addNewOptionValue($opt['opt_id'], $value, $setting, $langs, $dn_id);
					}

					//теперь проверяем если в товаре такая опция с таким значением.
					$pr_opt_value_id = $this->checkProductOptValue($opt, $value, $setting, $pr_id, $dn_id);
					//если такая запись есть обновляем ее.
					if (!empty($pr_opt_value_id)) {
						//Если u_opt'] == 3 нам нужно нельзя обновлять существующие опции.
						if($setting['u_opt'] != 3){
							$this->doProductOptValue($opt, $value, $product_option_id, $pr_opt_value_id, $setting, $pr_id, $dn_id, $do='up');
						}
					} else {
						//А если нету создаем.
						$this->doProductOptValue($opt, $value, $product_option_id, $pr_opt_value_id, $setting, $pr_id, $dn_id, $do='add');
					}

					//Записываем все id значений опций что бы удалить те которых уже нет.
					if(empty($del_arrey['value'])){ $del_arrey['value'] = $value['value_id']; }else{ $del_arrey['value'] .= ','.$value['value_id']; }
				}
			}

			//Записываем в списко все id опций что к нам пришли что бы удалить их товара те которые не пришли.
			if(empty($del_arrey['opt'])){ $del_arrey['opt'] = $opt['opt_id']; }else{ $del_arrey['opt'] .= ','.$opt['opt_id']; }
		}

		/*if ($setting['u_opt'] == 4){
			//Отправляем на удаление необновленных опций.
			$this->delOptNotBeenUpdate($del_arrey, $pr_id, $setting, $dn_id);
		}*/

	}
	#$this->wtfarrey($data);
	#$this->wtfarrey($del_arrey);
	
}

//Фунция удаленеия опций которые не обновились.
public function delOptNotBeenUpdate($del_arrey, $pr_id, $setting, $dn_id){
	// Эта фунция принимает массив с двумя значениями
	// opt - список id опций которые ОБНОВИЛИСЬ через запятую
	// value - списко id значений опций которые были обновлены
	//
	// В этой фуунции делаем выборку всех опций этого товара id которых не равне тому что у нас есть,
	// И делаем тоже самое с значениями. И вытераем их из жизни нашего магазина ;)
  #$this->wtfarrey($del_arrey);

	//получаем информацию по работе с фото опциями
  $us_img = $this->checkModuleOption();
	$sql_del_value_and = "";

	//Если ничего не обновили то удаляем все.
	if(empty($del_arrey['opt'])){ $del_arrey['opt'] = 0; }
	if(empty($del_arrey['value'])){$del_arrey['value'] = 0; }

	//Выполняем проверку есть ли опции в списке. 
	$sql_del_opt = "DELETE FROM ".DB_PREFIX."product_option WHERE product_id = ".$pr_id;
	if(!empty($del_arrey['opt'])){ 
		$sql_del_opt .= " AND option_id not in (".$del_arrey['opt'].")";	
		$sql_del_value_and = " AND option_value_id not in (".$del_arrey['value'].")";
	}
	$this->db->query($sql_del_opt);

	//проверяем нужно ли затирать фото
	if($us_img == 1){

		$imegs = $this->db->query("SELECT image FROM `".DB_PREFIX."product_option_value` WHERE product_id = ".$pr_id.$sql_del_value_and)->rows;
		#если массив с фото не пустой удаляем фото.
		if(!empty($imegs)){
			foreach($imegs as $img){
				@unlink(DIR_IMAGE.$img['image']);
			}
		}

	}elseif($us_img == 2){
		
		#определяем из каких значений нужно выбрать фото.
		if(!empty($del_arrey['opt'])){ 
			$imegs = $this->db->query("SELECT image FROM `".DB_PREFIX."poip_option_image` 
				WHERE product_id = ".$pr_id." AND product_option_value_id not in (".$del_arrey['value'].")")->rows;
		}else{
			$imegs = $this->db->query("SELECT image FROM `".DB_PREFIX."poip_option_image` WHERE product_id = ".$pr_id)->rows;
		}
		
		#если массив с фото не пустой удаляем фото.
		if(!empty($imegs)){
			foreach($imegs as $img){
				@unlink(DIR_IMAGE.$img['image']);
			}
		}
		
	}

	//Выполняем удаление всех значений опций которых нет в обновлении.
	$sql_del_value = "DELETE FROM ".DB_PREFIX."product_option_value WHERE product_id = ".$pr_id.$sql_del_value_and;
	$this->db->query($sql_del_value);

}

//Проверяем если в товаре такая опция
public function checkOptToProduct($pr_id, $opt_id){
	$product_option_id = 0;

	//Проверяем есть ли у товара эта опция
	$chack_opt = $this->db->query("SELECT * FROM `".DB_PREFIX."product_option` WHERE
		`product_id`=".(int)$pr_id." AND `option_id` =".(int)$opt_id);

	//если нету создаем.
	if ($chack_opt->num_rows > 0) {
		$product_option_id = $chack_opt->row['product_option_id'];
	}
	return $product_option_id;
}

//Добавляем опцию в товар
public function addOptToProduct($opt, $setting, $langs, $pr_id, $dn_id) {
	$product_option_id = 0;

	$this->db->query("INSERT INTO `".DB_PREFIX."product_option` SET
			`product_id`=".(int)$pr_id.",
			`option_id`=".(int)$opt['opt_id'].",
			`value`='',
			`required` = ".$this->db->escape($opt['required']));
	//Полуячаем id новой записи
	$product_option_id = $this->db->getLastId();

	//отправляем отчет в логи
	$log['opt_id'] = $opt['opt_id'];

	$this->log('addOptToProduct', $log, $dn_id);

	return $product_option_id;
}

//Проверяем есть ли запись в oc_product_option_value
public function checkProductOptValue($opt, $value, $setting, $pr_id, $dn_id){
	$product_option_value_id = 0;
	$sql = "SELECT * FROM `".DB_PREFIX."product_option_value` WHERE
		`product_id`=".(int)$pr_id." AND `option_id` =".(int)$opt['opt_id']." AND `option_value_id`=".(int)$value['value_id'];

	//теперь проверяем есть ли у этой опции такое значение что нам нужно.
	$chack_opt_value = $this->db->query("SELECT * FROM `".DB_PREFIX."product_option_value` WHERE
		`product_id`=".(int)$pr_id." AND `option_id` =".(int)$opt['opt_id']." AND `option_value_id`=".(int)$value['value_id']);

	if ($chack_opt_value->num_rows > 0) {

		$product_option_value_id = $chack_opt_value->row['product_option_value_id'];

	}

	return $product_option_value_id;
}

//Заполнение изображений опций для модуля Изображение опций PRO от 19th
public function optModuleOptionImegPro($pr_id, $product_option_id, $pr_opt_value_id, $value, $do){

	//проверяем действие обновить или добавить.
	if($do == 'add'){
		//проверяем что бы ссылка на фото была.
		if(!empty($value['imgs'])){
			#$this->wtfarrey('Добавление');
			$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."poip_option_image SET
				product_id = ".(int)$pr_id.",
				product_option_id = ".(int)$product_option_id.",
				product_option_value_id = ".(int)$pr_opt_value_id.",
				image = '".$this->db->escape($value['imgs'])."',
				sort_order = 1");

		}

	}
}

//Фкнция создания и обновления данных опции в товаре.
public function doProductOptValue($opt, $value, $product_option_id, $pr_opt_value_id, $setting, $pr_id, $dn_id, $do='add'){
	#$this->wtfarrey($value);

	//Использовать ли фото по стандарту опенкарта ? 
	$set_sql = '';
	if($value['imgs_type'] == 2 && $do == 'add'){ 
		//проверяем есть ли изображения
		if(!empty($value['imgs'])){ $value['imgs'] = $this->dwImgsToOption($value['imgs'], $dn_id); }
		//составляем строку с записью фото опции.
		$set_sql = "image='".$this->db->escape($value['imgs'])."',";
		$log['img'] = $value['imgs'];
	}elseif($value['imgs_type'] == 3 && $do == 'add'){
		//проверяем есть ли изображения
		if(!empty($value['imgs'])){ $value['imgs'] = $this->dwImgsToOption($value['imgs'], $dn_id); }
		$log['img'] = $value['imgs'];
	}

	if(empty($log['img'])){ $log['img'] = 'Опция без изображения';}

	#проверяем что нужно. Обновить или создать.
	if($do == 'add') {

		$this->db->query("INSERT INTO ".DB_PREFIX."product_option_value SET
			product_option_id=".(int)$product_option_id.",
			product_id=".(int)$pr_id.",
			option_id=".(int)$opt['opt_id'].",
			option_value_id=".(int)$value['value_id'].",
			quantity=".(int)$value['quant'].",
			subtract=1,
			price=".(float)$value['price'].",
			price_prefix='".$this->db->escape($value['price_prefix'])."',
			points=0,
			points_prefix='+',
			weight='0.00',".$set_sql."
			weight_prefix='+'");

		//получаем последний id 
		$pr_opt_value_id = $this->db->getLastId();
		#$this->wtfarrey($pr_opt_value_id);

		//отправляем отчет в логи
		$log['opt_id'] = (int)$opt['opt_id'];
		$log['value_id'] = (int)$value['value_id'];
		$log['pref'] = $this->db->escape($value['price_prefix']);
		$log['price'] = (float)$value['price'];
		$log['quant'] = (int)$value['quant'];

		$this->log('doProductOptValueAdd', $log, $dn_id);

	} elseif ($do == 'up') {

		$this->db->query("UPDATE ".DB_PREFIX."product_option_value SET
			product_option_id=".(int)$product_option_id.",
			product_id=".(int)$pr_id.",
			option_id=".(int)$opt['opt_id'].",
			option_value_id=".(int)$value['value_id'].",
			quantity=".(int)$value['quant'].",
			subtract=1,
			price=".(float)$value['price'].",
			price_prefix='".$this->db->escape($value['price_prefix'])."',
			points=0,
			points_prefix='+',
			weight='0.00',".$set_sql."
			weight_prefix='+'
			WHERE product_option_value_id =".(int)$pr_opt_value_id);
		//отправляем отчет в логи
		$log['opt_id'] = (int)$opt['opt_id'];
		$log['value_id'] = (int)$value['value_id'];
		$log['pref'] = $this->db->escape($value['price_prefix']);
		$log['price'] = (float)$value['price'];
		$log['quant'] = (int)$value['quant'];

		//Потому что опенкарт и опции это худшае что я когда либо видел
		//Я должен выполнять запрос на обновления соотсюда. С места гда этот запрос крайне не ожидано увидеть. 
		$this->db->query("UPDATE ".DB_PREFIX."product_option SET 
			required = ".(int)$opt['required']." 
			WHERE product_id = ".(int)$pr_id." AND option_id = ".(int)$opt['opt_id']);

		$this->log('doProductOptValueUp', $log, $dn_id);

	}

	//Изображения опций, адаптация модуоля Изображени Опций Про
	if($value['imgs_type'] == 3 && $do == 'add'){
		$this->optModuleOptionImegPro($pr_id, $product_option_id, $pr_opt_value_id, $value, $do);
	}

}

//Фунция добавление новых опций.
public function addNewOpt($opt_name, $langs, $dn_id){
	$opt_id = 0;
	$opt_name = trim($opt_name);
	//Создаем основую запись опции
	$this->db->query("INSERT INTO `".DB_PREFIX."option` SET `type`='select', `sort_order` = '0'");

	//Полуячаем id новой опции
	$opt_id = $this->db->getLastId();

	//Записываем дескрипшин опции
	foreach ($langs as $key => $lang) {
		$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."option_description` SET
			`option_id` = ".(int)$opt_id.",
			`language_id` = '".(int)$lang['language_id']."',
			`name` = '".$this->db->escape($opt_name)."'");
	}

	//отправляем отчет в логи
	$log['opt_id'] = $opt_id;
	$log['opt_name'] = $opt_name;

	$this->log('LogAddNewOpt', $log, $dn_id);

	return $opt_id;
}

//Фунция получения id значения опции.
public function getOptionValueId($opt_id, $value){

	$value['value'] = trim($value['value']);
	$rows = $this->db->query("SELECT * FROM ".DB_PREFIX."option_value_description WHERE
		name ='".$this->db->escape($value['value'])."' AND option_id =".(int)$opt_id);

	if($rows->num_rows > 0){
		$value['value_id'] = $rows->row['option_value_id'];
	}

	#$this->wtfarrey($rows->row['option_id']);
	return $value;
}

//Фунция создания нового занчения опции
public function addNewOptionValue($opt_id, $value, $setting, $langs, $dn_id){

	#$this->wtfarrey($value);
	//Использовать ли фото по стандарту опенкарта ? 
	$set_sql = '';
	if($value['imgs_type'] == 1){ 
		//проверяем есть ли 
		if(!empty($value['imgs'])){ $value['imgs'] = $this->dwImgsToOption($value['imgs'], $dn_id); }
		//составляем строку с записью фото опции.
		$set_sql = "image='".$this->db->escape($value['imgs'])."',";
	}

	$value['value'] = trim($value['value']);
	$this->db->query("INSERT INTO ".DB_PREFIX."option_value SET option_id =".(int)$opt_id.",".$set_sql." sort_order=0");
	$value['value_id'] = $this->db->getLastId();

	//Создаем таблицу дескрипшин опции
	foreach ($langs as $key => $lang) {
		$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."option_value_description SET
			option_value_id =".(int)$value['value_id'].",
			language_id =".(int)$lang['language_id'].",
			option_id =".(int)$opt_id.",
			name ='".$this->db->escape($value['value'])."'");
	}

	//отправляем отчет в логи
	$log['opt_id'] = $opt_id;
	$log['value_id'] = $value['value_id'];
	$log['value'] = $value['value'];

	$this->log('addNewOptionValue', $log, $dn_id);

	return $value;
}

//скачивание фото для опций, возврашает одну строку для записи в базу данных
public function dwImgsToOption($img, $dn_id){
	$str = '';
	//получаем данные с браузера.
	$browser = $this->getBrowserToCurl($dn_id);

	//качаем фото.
	$imgs = $this->dwImagToProduct($dn_id, [$img], 'options/', '', 1, $browser, $is_des=0);
	if(!empty($imgs[0])){ $str = $imgs[0];}

	#$this->wtfarrey($str);
	return $str;
}

//Фунция получения id опции.
public function getOptId($opt_name){
	$opt_id = 0;
	$opt_name = trim($opt_name);
	$opt = $this->db->query("SELECT * FROM `".DB_PREFIX."option_description` WHERE `name`='".$this->db->escape($opt_name)."'");
	if ($opt->num_rows > 0){
		$opt_id = $opt->row['option_id'];
	}

	#$this->wtfarrey($opt_id);
	return $opt_id;
}

//получаем список всех макетов. 
public function getAllLayouts(){
	$layouts = $this->db->query("SELECT * FROM `".DB_PREFIX."layout`")->rows;
	return $layouts;
}

//фунция преобразования строки проверочных полей в массив.
public function madeGransPermitListToArr($data){

	//проверяем что бы строка не была пустой.
	if(!empty($data)){
		$data = htmlspecialchars_decode($data);
		$data = explode('{next}', $data);
		foreach($data as &$gran_arr){

			$gran_arr = explode('{!na!}', $gran_arr);
			$gran_arr = [
						        'switch' => $gran_arr[0],
						        'name' => $gran_arr[1],
						        'gran' => $this->madeLogicalMathem($gran_arr[2], 'str'),
						        'operator' => $gran_arr[3],
						        'value' => $gran_arr[4],
						        'when_check' => $gran_arr[5]
					    		];
		}
	}else{
		//если строка пустая вернем пустой массив.
		$data = [];
	}
	#$this->wtfarrey($data);
	return $data;
}

//Проверка страницы на допуск к работе.
public function checkGransPermit($form, $setting, $dn_id){

	//1 - Добавлени Т | 2 - обновление товар | 3 - добавление и обновление Т |4 - Парсинг в csv | 5 - парсинг в кеш
	$data = [
					'1' => ['permit' =>1, 'log' => 'Код 808'], 
					'2' => ['permit' =>1, 'log' => 'Код 808'],
					'3' => ['permit' =>1, 'log' => 'Код 808'], 
					'4' => ['permit' =>1, 'log' => 'Код 808'], 
					'5' => ['permit' =>1, 'log' => 'Код 808'],
				];

	//проверяем что бы массив не был пустым.
	if(!empty($form['grans_permit_list'])){

		//проверяем все правила по очереди. 
		foreach($form['grans_permit_list'] as $rules){
			#$this->wtfarrey($rules);

			if(!empty($data[$rules['when_check']]['permit'])){

				//проверяем что бы правило было включено.
				if($rules['switch']){
					#Все типы правил. 
					#1 ->Не пустая | 2 ->Пустая | 3 ->Равна = | 4 ->Не равна != | 5 ->Содержит %значение% | 
					#6 ->Не содержит %значение% | 7 ->Регулярка | 8 ->Больше равно |9 ->Меньше равно
					
					//Если граница пустая отключаем загрузку страницы
					if($rules['operator'] == 1){

						if( empty($rules['gran']) && $rules['gran'] != '0' ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Пустое';

						}
					//Если граница НЕ пустая отключаем загрузку страницы
					}elseif($rules['operator'] == 2){

						if( !empty($rules['gran']) || $rules['gran'] == '0' ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Не пустое';

						}
					//Если граница НЕ равна значениею отменяем загрузку
					}elseif($rules['operator'] == 3){

						if( $rules['gran'] != $rules['value'] ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Не равно ['.$rules['value'].']';

						}
					//Если граница меньше отменяем её
					}elseif($rules['operator'] == 8){

						if( $rules['gran'] < $rules['value'] ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Меньше ['.$rules['value'].']';

						}
					//Если граница больше отменяем её
					}elseif($rules['operator'] == 9){

						if( $rules['gran'] > $rules['value'] ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Больше ['.$rules['value'].']';

						}
					//Если граница равна значению то отменяем загзку
					}elseif($rules['operator'] == 4){

						if( $rules['gran'] == $rules['value'] ){

							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Равно ['.$rules['value'].']';

						}
					//Если граница не содержит значение отменяем загрузку
					}elseif($rules['operator'] == 5){

						$value = preg_quote($rules['value'], '#');
						if(!preg_match('#(.*)'.$value.'(.*)#s', $rules['gran'])){
						
							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Не содержит значение ['.$rules['value'].']';
						
						}
					//Если содержит значение отменяем
					}elseif($rules['operator'] == 6){

						$value = preg_quote($rules['value'], '#');
						if(preg_match('#(.*)'.$value.'(.*)#s', $rules['gran'])){
							
							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Содержит значение ['.$rules['value'].']';
						
						}
					//проверка по регулярному выражению. 
					}elseif($rules['operator'] == 7){

						//Отлавливаем регулярные вырежения в правилах поиск замена
						if(preg_match('#^\{reg\[(.*)\]\}$#', $rules['value'], $reg)){
							//Вернем в жизнь правило.
							$reg = htmlspecialchars_decode($reg[1]);
							//проверка правила, если правило false значит отбрасываем эту страницу.
							if(!preg_match($reg, $rules['gran'])){
								$data[$rules['when_check']]['permit'] = 0;
								$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Не прошло проверку регулярного выражения '.$rules['value'];
							}

						}else{
							$data[$rules['when_check']]['permit'] = 0;
							$data[$rules['when_check']]['log'] = ' Правило допуска ['.$rules['name'].'] Содержит неправильно записанное регулярное выражение!!!';
						}
					}
				}
			}
		}
	}

	//проверяем границу добавление и обновление если она содержит 0 тоже самое добавляем В add и up
	if($data[3]['permit'] == '0'){
		$data[1]['permit'] = 0;
		$data[1]['log'] = $data[3]['log'];
		$data[2]['permit'] = 0;
		$data[2]['log'] = $data[3]['log'];
	}
	#$this->wtfarrey($form['grans_permit_list']);
	#$this->wtfarrey($setting);
	#$this->wtfarrey($data);
	return $data;
}

//фунция удаления фото товара.
public function delImgsInProduct($pr_id, $dn_id){

	//получаем главное фото.
	$main_imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product WHERE product_id = ".$pr_id);
	#$this->wtfarrey($main_imgs);
	//Удаляем главное фото товара.
	if($main_imgs->num_rows > 0){
		@unlink(DIR_IMAGE.$main_imgs->row['image']);
	}
	//Для экономия места удаляем массив.
	unset($main_imgs);

	//обновляем запись в базе что фото было удалены.
	$this->db->query("UPDATE ".DB_PREFIX."product SET image = '' WHERE product_id =".$pr_id);


	//получаем сиписок доп фото
	$imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product_image WHERE product_id IN (".$pr_id.")");
	//Удаляем доп фото
	foreach($imgs->rows as $img){
		@unlink(DIR_IMAGE.$img['image']);
	}
	unset($imgs);

	//Удаляем запись в базе про доп фото, мы же их удалили.
	$this->db->query("DELETE FROM `".DB_PREFIX."product_image` WHERE `product_id` = ".$pr_id);
}

//фунция скачивания фото
public function dwImagToProduct($dn_id, $imgs, $dir, $img_name, $under, $browser, $is_des=0){
	//Фунцяи скачивает фото, расскладывает и возврашает массив для записи в БД
	#$this->wtfarrey($imgs);
	#фото для товара
	$path = DIR_IMAGE.'catalog/';
	if(empty($dir)){ $dir = 'img_dir';}
	$href = [];
	#Удаляем слешы из начала и конца имени директории фото. Подготавливаем директорию под загрузку.
	if($dir[0] == '/'){ $dir = substr($dir, 1);}
	if(substr($dir, -1) == '/'){ $dir = substr($dir, 0, -1); }
	//Убераем обратные слеши, аля я на винде :)
	$dir = str_replace('\\', '/', $dir);
	#путь к директории на загрузку.
	$path .=$dir;

	//Если включена переменная подпапки зарание создаем подпапки.
	if($under){
		for($i=0;$i<10;$i++){
			$dir_add = $path.'/'.$i;
			if(!is_dir($dir_add)){ mkdir($dir_add, 0755, true); }
		}

		#Получаем цифру для вычисления под директории.
		$uder_dir = substr(microtime(), -1).'/';

	}else{
		$dir_add = $path;
		if(!is_dir($dir_add)){ mkdir($dir_add, 0755, true); }
		//если не используем то просто пустота.
		$uder_dir = '';
	}

	//Делаем массив из директорий, вдруг комунто нужно много вложенности.

	#загатовка для чистки массива
	$search = [" ","'","+",'!'];
	$replace = ['_','','',''];

	//декадируем все пути фото
	array_walk($imgs, function(&$v){ $v = $this->urlEncoding($v);});

	//делим массив на порции
	$imgs_chunk = array_chunk($imgs, 10);
	#$this->wtfarrey($imgs_chunk);
	foreach($imgs_chunk as $chunk){
		$data_img = $this->curlImg($chunk, $browser, $dn_id);
		#$this->wtfarrey($data_img);
		
		//Перебераем массив
		foreach($data_img as $key => $img){

			$img_temp = str_replace($search, $replace, urldecode($img['url']));
			#получаем имя фото. И отрезаем от него хвостик.
			$img_temp = preg_replace('#\?(.*)#', '', basename($img_temp));

			//Проверяем есть ли расширение файла. Если нет, добавляем.
			$exec = pathinfo($img_temp);
			$name = $exec['filename'];
			if(empty($exec['extension'])){
				$ext = '.png';
			}else{
				$ext = (preg_match('#(^jpeg)|(^jpg)|(^png)|(^jpe)|(^webp)|(^gif)|(^bmp)#i', $exec['extension'])) ? '.'.$exec['extension'] : '.jpg';
			}

			//применяем свое имя фото, если оно есть, если нет используем то что спарсилось.
			if(!empty($img_name)){

				$name = $img_name;

			}else{
				
				$name = $this->symbolToEn($name);
				//проверяем длину имени фото, если длина больше 250 символов, ссылка не верна
				if(strlen($name) > 250) {
					$name = 'sp-bad-url-img.jpg';
				}

			}
						
			#если файл скачался.
			if(!empty($img['img'])){
				#Сохраняем фото
				#если выбрано сохранять по подпапкам.
				
				$path_img = $path.'/'.$uder_dir.$name.$ext;
				//Проверяем есть ли такое фото. Если да то добавляем цифрув начала имени.
				for($i=1;$i>0;){
					if(file_exists($path_img)){
						$path_img = $path.'/'.$uder_dir.$name.'_'.$i.$ext;
						$i++;
					}else{
						$i=0;
					}
				}
				
				#сохранение фото на диск
				//Проверяем нужно ли конвертировать изображения ищ webp.
				if($browser['webp_conv'] > 0 && preg_match('#\.webp$#', $path_img) && function_exists('imagecreatefromwebp') && function_exists('imagejpeg')){
					//записываем файл в временную директорию.
					$temp_webp = DIR_IMAGE.'catalog/SPshow/tmp_webp.webp';
					file_put_contents($temp_webp, $img['img']);

					if($browser['webp_conv'] == 1){
						//меняе формат форто
						$path_img = preg_replace('#\.webp$#', '.jpg', $path_img);
						//Читаем webp
						$img['img'] = imageCreatefromWebp($temp_webp);
						//Сохранем ресурс в фото в формате jpg
						imageJpeg($img['img'], $path_img, 100);
						//удаляем ресурс из памяти.
						imagedestroy($img['img']);
					}else{
						//меняе формат форто
						$path_img = preg_replace('#\.webp$#', '.png', $path_img);
						//Читаем webp
						$img['img'] = imageCreatefromWebp($temp_webp);
						//Сохранем ресурс в фото в формате jpg
						imagepng($img['img'], $path_img, -1);
						//удаляем ресурс из памяти.
						imagedestroy($img['img']);
					}

				}else{
					file_put_contents($path_img, $img['img']);
				}
				//Финальное имя для базы данных
				$href[] = 'catalog/'.$dir.'/'.$uder_dir.basename($path_img);

			}else{//Для описания что бы не сбивать порядок фото в массиве. 

				//Через жопу определяем парсятся фото в описани или нет. Если фунция зайдет то переделать под отдельный маячок.
				if($is_des){
					$href[] = ''; #так нужно что бы не сбить порядок ключей в массиве фото.
				}

			}

		}
	}
	#$this->wtfarrey($href);
	return $href;
}


//загрузка фото в описании.
public function dwImgToDesc($desc, $url, $des_dir, $img_name, $under, $dn_id, $browser){
	$desc = htmlspecialchars_decode($desc);

	preg_match_all('#\{img\}(.*?)\>#s', $desc, $imgs_tmp);

	//Если массив не пустой значит есть фото в описании. 
	if(!empty($imgs_tmp)){

		//определяем доме для относительной ссылки.
		$domain = parse_url($url);

		#Массив для отправки на скачивание фото в мультипоточном режиме.
		$img_arr = [];

		//перебираем каждый элемент массива для преобразования.
		foreach($imgs_tmp[0] as $key_img => $var){
			#$this->wtfarrey($var);
			//Удаляем лишние
			$var = preg_replace('#\{img\}(.*?)src="#su', '', $var);
			$var = preg_replace('#"(.*)#su', '', $var);
			$var = trim($var);

			#$this->wtfarrey($var);
			$imgs[$key_img]['short'] = str_replace(PHP_EOL, '', $var);
			if($var != false){
				//Добавлем нужные элементы к ссылке.
				if($var[0] == '/' && $var[1] != '/'){
					$var = $domain['scheme'].'://'.$domain['host'].$var;

				}elseif($var[0] == '/' && $var[1] == '/'){
					$var = str_ireplace('//', $domain['scheme'].'://',$var);
				}
				$imgs[$key_img]['full'] = str_replace(PHP_EOL, '', $var);
				$img_arr[] = str_replace(PHP_EOL, '', $var);
			}

		}

		//Если массив на скачивание не пустой тогда качаем все фото.
		if(!empty($img_arr)){

			$img_path = $this->dwImagToProduct($dn_id, $img_arr, $des_dir, $img_name, $under, $browser, 1);
			#для удобства переносим результат в обыший массив.
			foreach($img_path as $key_path => $path){
				#$imgs[$key_path]['path'] = $path;
				//Добавляем недостающую часть.
				$path = HTTPS_CATALOG.'image/'.$path;
				//заменяем в описании текст с фото донора на наши фото.
				#$desc = preg_replace('#\{img\}(.*?)'.preg_quote($imgs[$key_path]['short'], '#').'(.*?)>#m', '<img alt="" src="'.$path.'" width="100%">', $desc, 1);
				//текст ниже если вы хотите сохранить параметры фото в описании
				$desc = preg_replace('#\{img\}#', '<img', $desc);
				$desc = preg_replace('#src="'.preg_quote($imgs[$key_path]['short'], '#').'"#', 'src="'.$path.'"', $desc, 1);

			}

		}

	}
	#$this->wtfarrey($desc);
	return $desc;
}

//Обьеденение товара HPM. 
//Код этой фунции был предоставлен пользователем mpn2005 за что спасибо. 
public function madeHpm($data, $pr_id, $dn_id){

	//проверяем что бы поле для связи не было пустым.
	if(!empty($data[$data['hpm_sku']])){
		//получаем данные для связи. 
		$group_data = $this->db->query("SELECT p.".$this->db->escape($data['hpm_sku']).", MAX(hpl.parent_id) AS parent_id, GROUP_CONCAT(p.product_id SEPARATOR ',') AS products 
			FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."hpmodel_links` hpl ON (p.product_id = hpl.product_id) 
			WHERE p.".$this->db->escape($data['hpm_sku'])." = '".$this->db->escape($data[$data['hpm_sku']])."' GROUP BY p.".$this->db->escape($data['hpm_sku']))->row;

		//разбираем связь, если она не пустая.
		if(!empty($group_data) && !empty($group_data['products'])){

			$products = explode(',', $group_data['products']);
			sort($products);
			$parent_id = $group_data['parent_id'];

			if($parent_id > 0) {
				$str_pr = '';#для логов
	      foreach($products as $product_id){
	        $hl_query = $this->db->query("SELECT COUNT(*) AS total 
	        															FROM `".DB_PREFIX."hpmodel_links` WHERE parent_id = '".(int)$parent_id."' AND product_id = '".(int)$product_id."'");
	        if($hl_query->row['total'] == 0){
	          $this->db->query("INSERT INTO `".DB_PREFIX."hpmodel_links` SET `parent_id` = '".(int)$parent_id."', `product_id` = '".(int)$product_id."'");
	          #Записываем id для логов
	          if(!empty($str_pr)){ $str_pr .= ','.(int)$product_id; }else{ $str_pr = (int)$product_id; }
	        }
	      }
	      $log = ['parent'=> (int)$parent_id, 'products' => $str_pr];
	    }else{
	      $parent_id = $products[0];                
	      foreach($products as $product_id){
	        $this->db->query("INSERT INTO `".DB_PREFIX."hpmodel_links` SET `parent_id` = '".(int)$parent_id."', `product_id` = '".(int)$product_id ."'");
	        #Записываем id для логов
	        if(!empty($str_pr)){ $str_pr .= ','.(int)$product_id; }else{ $str_pr = (int)$product_id; }
	      }
	      $log = ['parent'=> (int)$parent_id, 'products' => $str_pr];
	    }

	    //Отправляем запись логов.
	    if(!empty($log)){
	    	$this->log('AddProductToHpm', $log, $dn_id);
	  	}

	  	#$this->load->controller('extension/module/hpmodel/update'); 

		}
	}
} 

//Обрабатываем логические и математические операторы.
public function madeLogicalMathem($data, $type='int'){

	//Проверяем есть ли здесь if если да определяем какое значение брать. 
	if(substr($data, 0, 4) === "{if["){
		
		$logicas = preg_split('#(^\{if\[.*?\]\}|\{elif\[.*?\]\}|\{else\})#s', $data, -1, PREG_SPLIT_DELIM_CAPTURE);
		if(substr($logicas[1], 0, 4) === "{if["){ unset($logicas[0]);}

		//Значение по умолчанию, если ниодно из условий не отработает.
		$data = '';

		//перебираем все условия.
		foreach($logicas as $key => $if){
			//Определяем значения с уловиями.
			if($key % 2){
				
				//Если это else просто отдаем значение.
				if($if == 'else'){
					$key++; 
					if(!empty($logicas[$key])){ $data = $logicas[$key];}else{ $data = '';}
					break;
				}

				//Если это if или elif передаем значение на вычисление тела if
				$if = $this->madeLogicalToIf($if);
				if($if){ 
					$key++; 
					if(!empty($logicas[$key])){ $data = $logicas[$key];}else{ $data = '';}
					break;
				}
			}
		}
	}

	#$this->wtfarrey($logicas);

	$var = '';
	if($type == 'int' && $data === '0'){ $var = '0';}
	$data = explode('{|}', $data);
	
	//перебераем все варианты логического оператора
	foreach ($data as $value) {

		//если поле пустое то ставим 0
		//if(empty($value)){
		if( $type == 'int' && empty($value)){

			unset($value);
			$value[0] = '0';
		
		}elseif( $type == 'str' && empty($value) && $value !='0'){
			
			unset($value);
			$value[0] = '';

		}else{

			##############################
			# разбер математических фунций
			##############################
			//предварительно убераем двойные значения.
			#$this->wtfarrey($value);
			$value = preg_replace('#(\{[+*-/]\})(\{[+*-/]\})+#', '$1', $value);
			//Делим строку на массив в перемешку с оперантами.
			#$this->wtfarrey($value);
			$value = preg_split('#(\{[+*-/]\})#', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			#$this->wtfarrey($value);
			//Если массив пустой, то присваеваем пустоту первому ключу, так как в конце будем именно с него брать значение.
			if(empty($value)){$value[0] = '';}
			
			//Если первый элемент массива не значение а оперант, то добавляем перед ним ноль. Что бы массив начинался с числа.
			if(preg_match('#\{[+*-/]\}#', $value[0])){ array_unshift($value, 0); }
			
			//Запускаем цикл который будет перебирать массив и делать математические действия.
			$i =1;
			#$this->wtfarrey($value);
		  while($i){
		    //Если нету операнта, и второго значения значит уже не выполняем математику.
		    if(!isset($value[1]) || !isset($value[2])){
		      $i = 0;
		    }else{
		    	
		      if($value[1] == '{+}'){

		        $value[0] = (float)str_replace(array(' ', ','), array('', '.'), $value[0]) + (float)str_replace(array(' ', ','), array('', '.'), $value[2]);
		        unset($value[1]);
		        unset($value[2]);
		        $value = array_values($value);

		      }elseif($value[1] == '{*}'){

		        $value[0] = (float)str_replace(array(' ', ','), array('', '.'), $value[0]) * (float)str_replace(array(' ', ','), array('', '.'), $value[2]);
		        unset($value[1]);
		        unset($value[2]);
		        $value = array_values($value);

		      }elseif($value[1] == '{-}'){

		        $value[0] = (float)str_replace(array(' ', ','), array('', '.'), $value[0]) - (float)str_replace(array(' ', ','), array('', '.'), $value[2]);
		        unset($value[1]);
		        unset($value[2]);
		        $value = array_values($value);

		      }elseif($value[1] == '{/}'){

		        if(empty((float)$value[2])){ $value[2] = 1;}
		       
		        $value[0] = (float)str_replace(array(' ', ','), array('', '.'), $value[0]) / (float)str_replace(array(' ', ','), array('', '.'), $value[2]);
		        unset($value[1]);
		        unset($value[2]);
		        $value = array_values($value);

		      }
		    }
		  }
		}

		//выбираем какую цену оставить
		#$this->wtfarrey($value);

		if( $type == 'int' && !empty($value[0])){

			$var = strtr($value[0], [','=>'.',' '=>'']);
			break;

		}elseif( $type == 'str' && $value[0] !== '' ){

			$var = $value[0];
			break;


		}

	}

	return $var;
}

//Фунция по обработке логики в if. Должно отдать либо 1 либо 0
public function madeLogicalToIf($data){
	#$this->wtfarrey($data);
	//параметр что отдается. 
	$var = 0;

	//Достаем правило из if 
	$data = preg_replace('#\{[el]*?if\[(.*?)\]\}#', '$1', $data);
	
	//Если там пусто и не равно 0 начинаем вычислять.
	if(!empty($data)){

		##############################
		# разбер логических фунций
		##############################
		
		$value = preg_split("#(\{&gt;\}|\{&lt;\}|\{=\})#", $data, -1, PREG_SPLIT_DELIM_CAPTURE);

		//Если есть два элемента в массиве значит в if какая то логика, если нет просто проверяем тру или фолс
		if(isset($value[1])){

			
			//Если первый элемент массива не значение а оперант, то добавляем перед ним пустую строку. Что бы было с чем ровнять.
			#if(preg_match('#\{(&gt;)|(&lt;)|(=)\}#', $value[0])){ array_unshift($value, ''); }

			//Если нет второго элемента тоже ставим пустую строку.
			#if(!isset($value[2])){ $value[2] = '';}

			#$this->wtfarrey($value);

			//Запускаем цикл который будет перебирать массив и делать логическуие вычисления
		  while(true){
		    	
	      if($value[1] == '{&gt;}'){
	      	
	      	if(trim($value[0]) > trim($value[2])) { $var = 1; } else { $var = 0; }
	      	break;

	      }elseif($value[1] == '{&lt;}'){

	      	if(trim($value[0]) < trim($value[2])) { $var = 1; } else { $var = 0; }
	      	break;

	      }elseif($value[1] == '{=}'){

	      	if(trim($value[0]) == trim($value[2])) { $var = 1; } else { $var = 0; }
	      	break;

	      }else{
	      	$var = 1;
	      	break;
	      }

		  }


	  }else{
	  	if($value[0]){ $var = 1;}
	  }
	}

	return $var;
}

public function startParsToIm($dn_id){

	//Получам дополнительные данные из настроек.
	$setting = $this->getSettingToProduct($dn_id);
	
	//Получаем список заданий для выполнения
	if($setting['scripts_permit']){
		$script_tasks = $this->scriptGetTasksToExe($dn_id);
		$setting['thread'] = 1;#Если включено использование скриптов модуль работает в одном потоке. 
	}


	if($setting['sid'] == 'sku' && $setting['r_sku'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
  	$this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Артикул (sku)',$an);
	}elseif($setting['sid'] == 'name' && $setting['r_name'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Измените действие в поле Название',$an);
	}elseif($setting['sid'] == 'upc' && $setting['r_upc'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле upc',$an);
	}elseif($setting['sid'] == 'ean' && $setting['r_ean'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле ean',$an);
	}elseif($setting['sid'] == 'jan' && $setting['r_jan'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле jan',$an);
	}elseif($setting['sid'] == 'isbn' && $setting['r_isbn'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле isbn',$an);
	}elseif($setting['sid'] == 'mpn' && $setting['r_mpn'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле mpn',$an);
	}elseif($setting['sid'] == 'location' && $setting['r_location'] == 1){
		$an = ['progress'=>100,'clink'=>['link_scan_count' => 0,'link_count' => 0]];
	  $this->answjs('finish','ПАРСИНГ ОСТАНОВЛЕН : Нельзя обновлять значение которое является идентификатором товара. Отключите обновленив поле location',$an);
	}

	//Получаем списк неспарсенных ссылок.
	$pars_url = $this->getUrlToPars($dn_id, $setting['link_list'], $setting['link_error']);

	//Проверяем закончился ли парсинг.
	if(empty($pars_url['links'])){
		//Подсчет ссылок
		$totals = $pars_url['total'];
		$answ['progress'] = 100;
		$answ['clink'] = ['link_scan_count' => $pars_url['total'], 'link_count' => $pars_url['queue'],];

    $this->answjs('finish','Парсинг закончился, ссылок больше нет﻿',$answ);
	}else{

		//Блак многопоточности. берем нужное количество ссылок.
		$urls = [];
		foreach($pars_url['links'] as $key => $url){
			if($key < $setting['thread']){ $urls[] = $url['link']; } else { break; }
		}

		//получаем настройки браузера, один раз что бы сократить запросы в базу.
		$browser = $this->getBrowserToCurl($dn_id);

		//Отправка данных на собственные скрипты. 
		if(!empty($script_tasks)){
			
			$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
			$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
			$setting = $script_data['setting']; 
			$browser = $script_data['browser'];
			$urls = $script_data['urls'];
			unset($script_data);
		
		}

		//делаем мульти запрос
		$datas = $this->requestConstructor(1, $urls, $dn_id, $browser, $setting, 0);

  	foreach($datas as $key => $data){
			//Ссылка
			$link = $data['url'];
			//Прасим данные
			$form = $this->preparinDataToStore($data, $dn_id);

			//Получаем разрешения на действия.
			if(!empty($setting['grans_permit'])){
				$permit_grans = $this->checkGransPermit($form, $setting, $dn_id);

				//проверяем массив допуска и сравниваем с выбранным действием. 
				if($setting['action'] != 3 && empty($permit_grans[$setting['action']]['permit'])){ 
					$this->log('NoGranPermit', $permit_grans[$setting['action']]['log'], $dn_id);
					continue; 
				}
			}

			//Получаем разрешения на действия.
			$permit = $this->checkProduct($form, $setting, $link, $dn_id);

			//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){

				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'form'=>$form, 'permit'=>$permit, 'url'=>$link];
				$script_data = $this->scriptController(2, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				$permit = $script_data['permit'];
				$form = $script_data['form'];
				unset($script_data);
			
			}

			//Проверка выбора действия./// И проверка допуска страницы ///////////////// 
			// Допуск страниц $permit_grans[1]['permit']
			// 1 - Добавлени Т | 2 - обновление товар | 3 - добавление и обновление Т |4 - Парсинг в csv | 5 - парсинг в кеш
			//
			// Действия с товаром $setting['action']
			// 1 -Добавлять | 2 - Обновлять | 3 - Добавлять и обновлять
			///////////////////////////////////////////////////////////////////////////
			if($setting['action'] == 1){

				//провека допуска к добавлению товара
				if($permit['add']['permit'] == 1){
					$pr_id = $this->addProduct($form, $link, $setting, $dn_id, $browser);
				}else{
					$log = ['sid' => $setting['sid'],	'sid_value' => $form[$setting['sid']],];
					$this->log('addProductIsTrue', $log, $dn_id);
				}

			}elseif($setting['action'] == 2){

				//провека допуска к обновлению товара
				if($permit['up']['permit'] == 1){
					$this->updateProduct($form, $link, $setting, $dn_id, $permit['up']['pr_id'], $browser);
				}else{
					$log = [ 'sid' => $setting['sid'],	'sid_value' => $form[$setting['sid']], 'link' => $link ];
					#$this->wtfarrey($log);
					$this->log('NoFindProductToUpdate', $log, $dn_id);
				}

			}elseif($setting['action'] == 3){

				if($permit['add']['permit'] == 1){

					//проверка допуска страницы к добавлению товара, и включена ли проверка допуска
					if(!isset($permit_grans) || !empty($permit_grans[1]['permit'])){ 

						//провека допуска на добавление товара
						$pr_id = $this->addProduct($form, $link, $setting, $dn_id, $browser);

					}else{
						$this->log('NoGranPermit', $permit_grans[1]['log'], $dn_id);
						continue; 
					}

				}elseif($permit['up']['permit'] == 1){

					//проверка допуска страницы к обновлению товара, и включена ли проверка допуска
					if(!isset($permit_grans) || !empty($permit_grans[2]['permit'])){ 

						//проверка на обновление товара
						$this->updateProduct($form, $link, $setting, $dn_id, $permit['up']['pr_id'], $browser);

					}else{
						$this->log('NoGranPermit', $permit_grans[2]['log'], $dn_id);
						continue; 
					}

				}
			}

			//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){

				if(!empty($pr_id)){ $permit['add']['pr_id'] = $pr_id; }
				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'form'=>$form, 'permit'=>$permit, 'url'=>$link];
				$script_data = $this->scriptController(3, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				$permit = $script_data['permit'];
				$form = $script_data['form'];
				unset($script_data);
			
			}

		}

    #считаем процент для прогрес бара
    $scan = ($pars_url['total']-$pars_url['queue']);
    $progress = $scan/($pars_url['total']/100);
    $answ['progress'] = $progress;
    $answ['clink'] = ['link_scan_count' => $scan, 'link_count' => $pars_url['queue'],];

    #$this->fastscr();
    #пауза парсинга
    $this->timeSleep($setting['pars_pause']);
    $this->answjs('go','Производится парсинг',$answ);
	}
}

//Запускаем парсинг одной ссылки.
public function preparinDataToStore($data, $dn_id){
	#$time_start = microtime(true);
	//получаем все поля парсинга в им.
	$form = $this->getPrSetup($dn_id);
	//получаем все созданные границы париснга. 
	$params = $this->getParsParams($dn_id);

	//Получаем $url
	$url = $data['url'];

	$grans_key = [];
	$grans_data = [];
	foreach($params as $param){
		#$this->wtfarrey($param);
		$temp = $this->parsParam($data['content'], $param['id'], $params);

		if($param['type'] == 1){
			#$grans['{gran_'.$param['id'].'}'] = $temp;
			$grans_key[$param['id']] = '{gran_'.$param['id'].'}';
			$grans_data[$param['id']] = $this->findReplace($temp, $param['id']);
		}else{
			//запускаем поиск заме для повторяющейся границы парсинга.
			foreach($temp as &$var){
				$var = $this->findReplace($var, $param['id']);
			}
			//обьеденяем массив с разделителем.
			#$grans['{gran_'.$param['id'].'}'] = implode($param['delim'], $temp);
			$grans_key[$param['id']] = '{gran_'.$param['id'].'}';
			$grans_data[$param['id']] = implode($param['delim'], $temp); 
			
		}
	}
	#$this->wtfarrey($form['attr']);
	//делаем преобразование. 
	for ($i=0; $i < 5; $i++) { 
		$form = str_replace($grans_key, $grans_data, $form);
	}
	#$this->wtfarrey($form['grans_permit_list']);
	#$this->wtfarrey($grans_key);

	//Финальная обработка данных в массиве.

	//Ниже вырезаем спец маяки из данных где должно быть одно значение, а использовали повторяющиеся границы парсинга.
	$form['model'] = substr(trim($this->madeLogicalMathem(str_replace('{csvnc}','',$form['model']), 'str')), 0, 64);
	$form['sku'] = substr(trim($this->madeLogicalMathem(str_replace('{csvnc}','',$form['sku']), 'str')), 0, 64);
	$form['name'] = substr(trim($this->madeLogicalMathem(str_replace(['{csvnc}','"'], ['','&quot;'], $form['name']), 'str' )), 0, 255);

	//Работаем над ценой товара
	$form['price'] = str_replace('{csvnc}','',$form['price']);
	$form['price'] = $this->madeLogicalMathem($form['price'], 'int');

	//работаем с ценой скидки.
	$form['price_spec'] = str_replace('{csvnc}','',$form['price_spec']);
	$form['price_spec'] = $this->madeLogicalMathem($form['price_spec'], 'int');
	if($form['price_spec'] == $form['price']){ $form['price_spec'] = 0; }

	$form['cost'] = str_replace('{csvnc}','',$form['cost']);
	$form['cost'] = $this->madeLogicalMathem($form['cost'], 'int');

	$form['quant'] = str_replace('{csvnc}','',$form['quant']);
	$form['quant'] = $this->madeLogicalMathem($form['quant'], 'int');

	$form['manufac'] = trim(str_replace('{csvnc}','',$form['manufac']));
	$form['manufac'] = $this->madeLogicalMathem($form['manufac'], 'str');

	$form['des'] = trim(str_replace('{csvnc}','',$form['des']));
	$form['des'] = $this->madeLogicalMathem($form['des'], 'str');
	
	#Разное
	$form['upc'] = trim(str_replace('{csvnc}','',$form['upc']));
	$form['upc'] = substr($this->madeLogicalMathem($form['upc'], 'str'), 0, 64);

	$form['ean'] = trim(str_replace('{csvnc}','',$form['ean']));
	$form['ean'] = substr($this->madeLogicalMathem($form['ean'], 'str'), 0, 64);
	
	$form['jan'] = trim(str_replace('{csvnc}','',$form['jan']));
	$form['jan'] = substr($this->madeLogicalMathem($form['jan'], 'str'), 0, 64);
	
	$form['isbn'] = trim(str_replace('{csvnc}','',$form['isbn']));
	$form['isbn'] = substr($this->madeLogicalMathem($form['isbn'], 'str'), 0, 64);
	
	$form['mpn'] = trim(str_replace('{csvnc}','',$form['mpn']));
	$form['mpn'] = substr($this->madeLogicalMathem($form['mpn'], 'str'), 0, 64);
	
	$form['location'] = trim(str_replace('{csvnc}','',$form['location']));
	$form['location'] = substr($this->madeLogicalMathem($form['location'], 'str'), 0, 128);
	
	$form['minimum'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['minimum']), 'int'); 
	if(empty($form['minimum'])) {$form['minimum'] = 1;}
	
	$form['subtract'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['subtract']), 'int');

	$form['length'] = (float)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['length']), 'int');
	if(empty($form['length'])) {$form['length'] = '0.00';}

	$form['width'] = (float)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['width']), 'int');
	if(empty($form['width'])) {$form['width'] = '0.00';}

	$form['height'] = (float)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['height']), 'int');
	if(empty($form['height'])) {$form['height'] = '0.00';}

	$form['length_class_id'] = trim($this->madeLogicalMathem(str_replace('{csvnc}','',$form['length_class_id']), 'int'));
	
	$form['weight'] = str_replace('{csvnc}','',$form['weight']);
	$form['weight'] = (float)$this->madeLogicalMathem($form['weight'], 'int');
	if(empty($form['weight'])) {$form['weight'] = '0.00';}

	#$this->wtfarrey($form['weight']);

	$form['weight_class_id'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['weight_class_id']), 'int');
	$form['status'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['status']), 'int');
	$form['sort_order'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['sort_order']), 'str');
	$form['layout_pr'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['layout_pr']), 'int');
	$form['layout_cat'] = (int)$this->madeLogicalMathem(str_replace('{csvnc}','',$form['layout_cat']), 'int');

	$form['tags'] = trim($this->madeLogicalMathem(str_replace('{csvnc}','',$form['tags']), 'str'));

	#Товар
	$form['seo_url'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['seo_url']), 'str');
	$form['seo_h1'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['seo_h1']), 'str');
	$form['seo_title'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['seo_title']), 'str');
	$form['seo_desc'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['seo_desc']), 'str');
	$form['seo_keyw'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['seo_keyw']), 'str');
	#Категории
	$form['cat_seo_url'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['cat_seo_url']), 'str');
	$form['cat_seo_h1'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['cat_seo_h1']), 'str');
	$form['cat_seo_title'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['cat_seo_title']), 'str');
	$form['cat_seo_desc'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['cat_seo_desc']), 'str');
	$form['cat_seo_keyw'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['cat_seo_keyw']), 'str');
	#Производители
	$form['manuf_seo_url'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['manuf_seo_url']), 'str');
	$form['manuf_seo_h1'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['manuf_seo_h1']), 'str');
	$form['manuf_seo_title'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['manuf_seo_title']), 'str');
	$form['manuf_seo_desc'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['manuf_seo_desc']), 'str');
	$form['manuf_seo_keyw'] = $this->madeLogicalMathem(str_replace('{csvnc}','',$form['manuf_seo_keyw']), 'str');

	//Преобразования категорий
	#$this->wtfarrey($form['cat']);
	$form['cat'] = $this->madeCatArrey($form['cat']);
	#$this->wtfarrey($form['cat']);
	//преобразовывает фото для парсинга.
	#$this->wtfarrey($form['img']);
	$form['img'] = $this->madeImgArrey($form['img'], $url);
	#$this->wtfarrey($form['img']);
	//Преобразовываем атрибуты.
	#$this->wtfarrey($form['attr']);
	$form['attr'] = $this->madeAttrArrey($form['attr']);
	//преобразуем опции
	$form['opts'] = $this->madeOption($form);
	#$this->wtfarrey($form['opts']);
	//преобразовываем проверочные данные в массив
	$form['grans_permit_list'] = $this->madeGransPermitListToArr($form['grans_permit_list']);
	#$this->wtfarrey($form['grans_permit_list']);
	//приготавливаем границу для создание папок под фото товара.
	$form['img_dir'] = $this->symbolToEn(str_replace(['{csvnc}','{!na!}', ' '], ['/', '/', '_'], trim($form['img_dir'])));
	$form['img_dir'] = preg_replace('#[^a-zA-Z0-9-_/]#', '', $form['img_dir']);
	//готовим имя фото
	$form['img_name'] = $this->symbolToEn(str_replace(['{csvnc}','{!na!}', ' '], ['', '', '_'], trim($form['img_name'])));
	$form['img_name'] = substr(preg_replace('#[^a-zA-Z0-9-_]#', '', $form['img_name']), 0, 240);
	//приготавливаем границу для создание папок под фото товара.
	$form['des_dir'] = $this->symbolToEn(str_replace(['{csvnc}','{!na!}', ' '], ['/', '/', '_'], trim($form['des_dir'])));
	$form['des_dir'] = preg_replace('#[^a-zA-Z0-9-_/]#', '', $form['des_dir']);
	$form['script_gran'] = $grans_data;
	
	#$this->wtfarrey((microtime(true)-$time_start));

	return $form; 

}

public function addProduct($data, $link, $setting, $dn_id, $browser){

	//получаем списко используемых языков
	$langs = $this->getLang($setting);
	//Получаем выбранный магазин.
	$stores = $this->getStore($setting);
	
	$pr_id = 0;

	//Ниже поля по умолчанию.
	if(empty($data['model'])){ $data['model'] = '';	}
	if(empty($data['sku'])){ $data['sku'] = '';	}
	if(empty($data['name'])){	$data['name'] = ''; }
	if(empty($data['price'])){
		$data['price'] = '0';
	}else{
		$data['price'] = (float)str_replace(' ', '', str_replace(',', '.', $data['price']));
	}
	if(empty($data['price_spec'])){
		$data['price_spec'] = 0;
	}else{
		$data['price_spec'] = (float)str_replace(' ', '', str_replace(',', '.', $data['price_spec']));
	}

	if(empty($data['cost'])){ $data['cost'] = 0; }

	if(empty($data['des'])){
		$data['des'] = '';
		if(!empty($data['des_d'])){
			$data['des'] = $data['des_d'];
		}
	}

	if(empty($data['cat'])){	$data['cat'] = []; }
	if(empty($data['img'])){	$data['img'] = []; }
	if(empty($data['attr'])){	$data['attr'] = [];	}

	//Дописываем нужные поля. Пока они не парсятся но на будушее будут обрабатыватся.
	if(empty($data['tax_class_id'])){	$data['tax_class_id'] = 0; }
	if(empty($setting['r_status_zero'])){	$data['stock_status_id'] = 7;	} else { $data['stock_status_id'] = $setting['r_status_zero'];}
	if(empty($data['date_available'])){	$data['date_available'] = date("Y-m-d"); }
	if(empty($data['shipping'])){	$data['shipping'] = 1; }
	
	//количество товара
	if(empty($data['quant'])){
		if($data['quant'] != '0'){
			if(empty($data['quant_d'])){
				$data['quant'] = 0;
			}else{
				$data['quant'] = (int)$data['quant_d'];
			}
		}

	}else{

		$data['quant'] = (int)$data['quant'];
		if($data['quant'] == 0){
			if(empty($data['quant_d'])){
				$data['quant'] = 0;
			}else{
				$data['quant'] = (int)$data['quant_d'];
			}
		}
	}

	//определяем статус товара
	$data['status'] = $this->getProductStatus($data, $setting);

	$permit = 1;
	//Если по правилам модель создается по умолчанию то так и делаем.
	if($setting['r_model'] == 1){
		$model = $this->db->query("SELECT MAX(`product_id`) as lid FROM " . DB_PREFIX . "product");
		$data['model'] = $model->row['lid']+1;
	}elseif($setting['r_model'] == 2){
		if(empty($data['model'])){
			$permit = 0;
			$log = '';
			$this->log('NoParsModel', $log, $dn_id);
		}
	}

	if($permit == 1){

		//Здесь начинаем собирать все кости в кучу.
		//========================================

		//Создаем массив с данными для логов добавления товара.
		$log[0] = ['sid'=>$setting['sid'], 'sid_value'=>$data[$setting['sid']]];
		
		/////////////////////////////////////////////////
		//Работа с Производителями.
		// 0 - Не учитывать | 1-Создавать, добавлять в товар| 2 - Добавлять в товар если уже создан в магазине
		//////////////////////////////////////////////////
		if($setting['r_manufac'] == 0 || $setting['r_manufac'] == 2){

			$data['manufacturer_id'] = 0;

		}elseif($setting['r_manufac'] == 1){

			if(empty($data['manufac'])){
				//По умолчанию
				$data['manufacturer_id'] = $data['manufac_d'];
			}else{

				#Получаем id мануфактуры.
				$manuf_id = $this->getIdManuf($data['manufac']);

				#если нету такой тогда создаем. И получаем id этой мануфак
				if($manuf_id > 0){
					$data['manufacturer_id'] = $manuf_id;
				}else{
					#Создаем производителя. И получаем в ответ id этого производителя.
					$data['manufacturer_id'] = $this->addManuf($data, $langs, $setting, $stores, $dn_id);
				}
			}

		}elseif($setting['r_manufac'] == 2){

			if(empty($data['manufac'])){
				//По умолчанию
				$data['manufacturer_id'] = $data['manufac_d'];
			}else{

				#Получаем id мануфактуры.
				$manuf_id = $this->getIdManuf($data['manufac']);

				#если нету такой тогда создаем. И получаем id этой мануфак
				if($manuf_id > 0){
					$data['manufacturer_id'] = $manuf_id;
				}else{
					$data['manufacturer_id'] = 0;
				}
			}

		}

		////////////////////////////////Заморозка загруки false//////////////////
		if(1){

			//Главный запрос на добавления товара.
			$this->db->query("INSERT INTO " . DB_PREFIX . "product
				SET model = '" . $this->db->escape($data['model']) . "',
				sku = '" . $this->db->escape($data['sku']) . "',
				quantity = '" . (int)$data['quant'] . "',
				stock_status_id = '" . (int)$data['stock_status_id'] . "',
				date_available = '" . $this->db->escape($data['date_available']) . "',
				manufacturer_id = '" . (int)$data['manufacturer_id'] . "',
				shipping = '" . (int)$data['shipping'] . "',
				price = '" . (float)$data['price'] . "',
				cost = '" . (float)$data['cost'] . "',
				tax_class_id = '" . (int)$data['tax_class_id'] . "',
				upc = '" . $this->db->escape($data['upc']) . "',
				ean = '" . $this->db->escape($data['ean']) . "',
				jan = '" . $this->db->escape($data['jan']) . "',
				isbn = '" . $this->db->escape($data['isbn']) . "',
				mpn = '" . $this->db->escape($data['mpn']) . "',
				location = '" . $this->db->escape($data['location']) . "',
				minimum = " . (int)$data['minimum'] . ",
				subtract = ".(int)$data['subtract'].",
				length = ".(float)$data['length'].",
				width = ".(float)$data['width'].",
				height = ".(float)$data['height'].",
				weight = ".(float)$data['weight'].",
				weight_class_id = '" . (int)$data['weight_class_id'] . "',
				length_class_id = '" . (int)$data['length_class_id'] . "',
				sort_order = '" . (int)$data['sort_order'] . "',
				dn_id = '" . (int)$dn_id . "',
				status = '" . (int)$data['status'] . "',
				date_added = NOW(),
				date_modified = NOW()");

			//Получаем id нового товара.
			$pr_id = $this->db->getLastId();

			//Добаляем товар в магазины
			foreach ($stores as $store) {
				$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_to_store SET product_id = '" . (int)$pr_id . "', store_id = '" . $store['store_id']. "'");
			}

			//Записываем значения в лог файл
			$log[0]['pr_id'] = $pr_id;
			$log[] = ['name'=> 'Код товара [model]', 'value'=> $data['model']];
			$log[] = ['name'=> 'Артикул [sku]', 'value'=> $data['sku']];
			$log[] = ['name'=> 'Название','value'=>$this->db->escape($data['name'])];
			$log[] = ['name'=> 'Количество', 'value'=> (int)$data['quant']];
			$log[] = ['name'=> 'Производитель id', 'value'=> (int)$data['manufacturer_id']];
			$log[] = ['name'=> 'Цена', 'value'=> (float)$data['price']];
			$log[] = ['name'=> 'UPС', 'value'=> $data['upc']];
			$log[] = ['name'=> 'EAN', 'value'=> $data['ean']];
			$log[] = ['name'=> 'JAN', 'value'=> $data['jan']];
			$log[] = ['name'=> 'ISBN', 'value'=> $data['isbn']];
			$log[] = ['name'=> 'MPN', 'value'=> $data['mpn']];
			$log[] = ['name'=> 'Location', 'value'=> $data['location']];
			$log[] = ['name'=> 'Минимальный заказ', 'value'=> (int)$data['minimum']];
			$log[] = ['name'=> 'Вычитать со склада', 'value'=> (int)$data['subtract']];
			$log[] = ['name'=> 'Длина', 'value'=> (float)$data['length']];
			$log[] = ['name'=> 'Ширина', 'value'=> (float)$data['width']];
			$log[] = ['name'=> 'Высота', 'value'=> (float)$data['height']];
			$log[] = ['name'=> 'Единица длины', 'value'=> (int)$data['length_class_id']];
			$log[] = ['name'=> 'Вес', 'value'=> (float)$data['weight']];
			$log[] = ['name'=> 'Единица веса', 'value'=> (int)$data['weight_class_id']];
			$log[] = ['name'=> 'Сортировка', 'value'=> (int)$data['sort_order'] ];
			$log[] = ['name'=> 'Статус', 'value'=> (int)$data['status']];
			$log[] = ['name'=> 'Закупочная цена', 'value'=> (float)$data['cost']];

			#Контроль создалась основная часть товара.
			if($pr_id){

				//Таблицы для описания незнаю буду ли с ними работать но добавлю в основу модуля.
				if(empty($data['tags'])){
					$data['tags'] = '';
				}
				if(empty($data['meta_title'])){
					$data['meta_title'] = '';
				}
				if(empty($data['meta_h1'])){
					$data['meta_h1'] = '';
				}
				if(empty($data['meta_description'])){
					$data['meta_description'] = '';
				}
				if(empty($data['meta_keyword'])){
					$data['meta_keyword'] = '';
				}

				//////////////////////////////////////////////////
				//Работа с MATA ДАННЫМИ
				// 0 - Незаполнять
				// 1 - По SEO шаблону
				////////////////////////////////////////////////////
				if($setting['r_made_meta'] == 1){

					if(!empty($data['seo_title'])){
						$data['meta_title'] = $data['seo_title'];
					}
					if(!empty($data['seo_desc'])){
						$data['meta_description'] = $data['seo_desc'];
					}
					if(!empty($data['seo_keyw'])){
						$data['meta_keyword'] = $data['seo_keyw'];
					}
					if(!empty($data['seo_h1'])){
						$data['meta_h1'] = $data['seo_h1'];
					}
				}

				//////////////////////////////////////////////////
				//Работа с ОПИСАНИЕМ
				// 0 - Не заполнять
				// 1 - Заполнять
				////////////////////////////////////////////////////
				if($setting['r_des'] == 1){
					//обработка фото описаний.
					$data['des'] = $this->dwImgToDesc($data['des'], $link, $data['des_dir'], $data['img_name'], $setting['r_des_dir'], $dn_id, $browser);
					$log[] = ['name'=> 'Описание','value'=>'Добавлено (описание в лог не пишется)'];
				}else{
					$data['des'] = '';
				}

				//Проверяем версию движка для правильного заполнения.
				$mh1 = '';
				if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
					$mh1 = ",meta_h1='".$this->db->escape($data['meta_h1'])."'";
				}

				//Добавление описания
				foreach ($langs as $key => $lang) {
					//Составляе строку.
					$sql = "INSERT INTO " . DB_PREFIX . "product_description
					SET product_id = '" . (int)$pr_id . "',
					language_id = '" . (int)$lang['language_id'] . "',
					name = '".$this->db->escape($data['name'])."',
					description = '" . $this->db->escape($data['des']) . "',
					tag = '" . $this->db->escape($data['tags']) . "',
					meta_title = '" . $this->db->escape($data['meta_title']) . "',
					meta_description = '" . $this->db->escape($data['meta_description']) . "',
					meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'".$mh1;
					//Запрос
					$this->db->query($sql);
				}

				//логи
				$log[] = ['name'=>'Теги товара','value'=>$this->db->escape($data['tags'])];

				///////////////////////////////////////
				//Категории
				// 0 - Не заполнять [Не рекомендую, товар получит категорию с id = 0]
				// 1 - Создавать категории и заполнять в товаре
				// 2 - Заполнить категорию в товаре только если категория уже создана в магазине
				///////////////////////////////////////
				$data['cats_id'][0] = 0;

				if($setting['r_cat'] == 1){
					#Создать категории и привазять товар.

					//проверяем массив категорий.
					if(!empty($data['cat'])){

						//проверяем есть ли такая котегория и если есть возврашем ее id
						$data['cats_id'] = $this->getCategorysId($data['cat']);

						//если такая категория есть тогда оставляем его id для товара, если нет. Отправляемся создавать категории.
						if($data['cats_id'][0] == 0){
							$this->addCat($data, $setting, $langs, $stores, $dn_id);
							$data['cats_id'] = $this->getCategorysId($data['cat']);
						}

					}elseif($data['cat_d'] != 0){
						$data['cats_id'][0] = $data['cat_d'];
					}

				}elseif($setting['r_cat'] == 2){ //если добавлять товар только в сушествуюшие категории.
					//проверяем массив категорий.
					if(!empty($data['cat'])){

						//проверяем есть ли такая котегория и если есть возврашем ее id
						$data['cats_id'] = $this->getCategorysId($data['cat']);
						//Категория по умолчанию
						if($data['cats_id'][0] == 0 && $data['cat_d']!=0){
							$data['cats_id'][0] = $data['cat_d'];
						}

					}elseif($data['cat_d']!=0){
						$data['cats_id'][0] = $data['cat_d'];
					}

				}

				//Добавляем товар в нужную категорию.
				$log_cat = $this->addProdToCat($data['cats_id'], $pr_id, $setting);
				$log[] = ['name' =>'Категории','value'=>$log_cat];
				$this->log('addProduct', $log, $dn_id);
				
				//////////////////////////////////////////////////
				//Работа с акционными ценами.
				//////////////////////////////////////////////////
				//Если акционная цена не равна нулю значит будем ее добавлять в магазин
				if ($data['price_spec'] != 0) {
					$this->addPriceSpecToProduct($data['price_spec'], $setting, $pr_id, $dn_id);
				}

				//////////////////////////////////////////////////
				//Работа с фото.
				// УСЛОВИЯ | 0 - Не добавлять|1 - Добавлять
				// ПРАВИЛО ДИРЕКТОРИЙ| 0 - не создавать папки и не раскладывать фото | 1- Создать директории и разложить фото
				// 
				//////////////////////////////////////////////////
				#Массив с сылками путями к фото для базы.
				$data['img_path'] = [];
				if($setting['r_img'] == 1){
					
					if(empty($data['img'])){

						if(!empty($data['img_d'])){
							$data['img'][0] = $data['img_d'];
							$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);
						}else{
							$logs['pr_id'] = $pr_id;
							$this->log('fotoNotData', $logs, $dn_id);
						}

					}else{
						$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);
					}
				}

				//Добавление Фото
				if(!empty($data['img_path'])){
					#Добавление главного фото
					$this->db->query("UPDATE ".DB_PREFIX."product SET image = '".$this->db->escape($data['img_path'][0])."' WHERE product_id = '".(int)$pr_id."'");

					#Добавление доп фото.
					foreach ($data['img_path'] as $key => $image) {
						if($key == 0) continue;
						$this->db->query("INSERT INTO ".DB_PREFIX."product_image SET product_id = '".(int)$pr_id."', image = '".$this->db->escape($image)."', sort_order = ".(int)$key);
					}
				}


				//////////////////////////////////////////////////
				//Работа с АТРИБУТАМИ
				// 0 - Не работать с атрибутами
				// 1 - Создавать атрибута если такого нет, добавлять атрибуты в товар
				// 2 - Добавлять в товар без создания новых атрибутов
				////////////////////////////////////////////////////
				if($setting['r_attr'] == 0){
					$data['attr'] = [];

				}elseif($setting['r_attr'] == 1){
					#Создаем атрибуты и добавляем в товар.

					#Проверяем сушествует атрибут или нет.
					foreach($data['attr'] as $attr){

						$attr['id'] = $this->getIdAttr($attr[0]);
						#Если нету тогда создаем.
						if($attr['id'] == 0){
							$attr['id'] = $this->addAttr($attr[0], $langs, $setting, $dn_id);
							//Если после создания атрибут есть тогда записываем его в товар. Если нет проходим дальше.
							if($attr['id'] != 0){
								$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
							}

						}else{
							#Если такой атрибут найден тогда присвяеваем его товару.
							$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
						}

					}

				}elseif($setting['r_attr'] == 2){
					#Проверяем есть ли такой атрибу если да добавляем в товар.
					foreach($data['attr'] as $attr){
						$attr['id'] = $this->getIdAttr($attr[0]);
						//Если есть такой атрибут добавляем его в товар. Если нет пропускаем.
						if($attr['id'] != 0){
							$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
						}
					}

				}
				//////////////////////////////////////////////////
				//Работа с Оption
				// 0 - Не работать с опциями
	      // 1 - Создавать, добавлять в товар
	      // 2 - Добавлять в товар без создания новых опций
				////////////////////////////////////////////////////
				$this->controlOption($data['opts'], $setting, $langs, $pr_id, $dn_id, 'add');

				//////////////////////////////////////////////////
				//Работа с МАКЕТАМИ
				// Если значение пустое или ноль, то пи создании товара модуль игнорирует эту настройку.
				////////////////////////////////////////////////////			
				if(!empty($data['layout_pr'])){

					foreach ($stores as $store) {
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_to_layout SET 
							product_id = '".(int)$pr_id."', 
							store_id = '".$store['store_id']. "',
							layout_id = '".(int)$data['layout_pr']."'");
					}
					
					$logs['layout_id'] = $data['layout_pr'];
					$this->log('addlayout', $logs, $dn_id);
				}

				//////////////////////////////////////////////////
				//Работа с SEO_URL
				// 0 - Незаполнять
				// 1 - Создать из имени товара
				// 2 - Создать по шаблону заполненому на вкладке SEO
				////////////////////////////////////////////////////
				if($setting['r_made_url'] == 1){

					//Получаем юрл из имени.
					if(!empty($data['name'])){
						$pr_url = $this->madeUrl($data['name']);

						//Записываем url
						$do = ['where'=>'pr','what'=>'add'];
						$this->addSeoUrl($pr_url, $pr_id, $setting, $langs, $stores, $dn_id, $do);
					}else{
						$logs['name'] = 'product name';
						$this->log('badUrl', $logs, $dn_id);
					}

				}elseif($setting['r_made_url'] == 2){

					if(!empty($data['seo_url'])){
						//Получаем юрл из имени.
						$pr_url = $this->madeUrl($data['seo_url']);

						//Записываем url
						$do = ['where'=>'pr','what'=>'add'];
						$this->addSeoUrl($pr_url, $pr_id, $setting, $langs, $stores, $dn_id, $do);
					}else{
						$logs['name'] = 'seo_url';
						$this->log('badUrl', $logs, $dn_id);
					}
				}

				//////////////////////////////////////////////////
				// Заполнение рекомендованных товаров
				// 0 - Не работать с рекомендациями. 
				// 1 - Обновлять рекомендации.
				////////////////////////////////////////////////////

				if($data['related_sku']){
					
					//Поскольку это создание товара проверяем пустое поле с идентификатором связи или нет.
					if(!empty($data[$data['related_sku']])){

						$related_arr = explode(';', $data[$data['related_sku']]);
						//проходим по каждому артикулу и составляем запрос.
						$sid = '';
						foreach($related_arr as $key => $rel_sku){
							if($key){ $sid .= ",'".$this->db->escape($rel_sku)."'"; }else{ $sid .= "'".$this->db->escape($rel_sku)."'"; }
						}

						//получаем id товаров с которыми нужно сделать связи.
						$relate_prs = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product WHERE ".$setting['sid']." in (".$sid.")");

						//Если связи есть в магазине то записываем их.
						if($relate_prs->num_rows){
							//массив для логов.
							$rel_log = [];
							foreach($relate_prs->rows as $relate){
								$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$pr_id.", related_id =".(int)$relate['product_id']);
								$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$relate['product_id'].", related_id =".(int)$pr_id);
								$rel_log[] = [$pr_id, $relate['product_id']];
							}
							//запишем в логи
							if(!empty($rel_log)){ $this->log('relateAddProduct', $rel_log, $dn_id); }
						}
					}

					//Проверяем другие товары хотят наш товар добавить себе в избранное или нет.
					$relate_prs = $this->db->query("SELECT product_id, ".$data['related_sku']." as re_str 
																					FROM ".DB_PREFIX."product 
																					WHERE ".$data['related_sku']." LIKE '%".$this->db->escape($data[$setting['sid']])."%'");
					//Если ответ не пустой тогда перебераем.
					if($relate_prs->num_rows){
						//перебираем все отваты.
						$rel_log = [];
						foreach($relate_prs->rows as $relate){

							//Дальше убиждаемся что связь верна.
							$relate['re_str'] = explode(';', $relate['re_str']);
							if(in_array($data[$setting['sid']], $relate['re_str'])){
								$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$pr_id.", related_id =".(int)$relate['product_id']);
								$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$relate['product_id'].", related_id =".(int)$pr_id);
								$rel_log[] = [$pr_id, $relate['product_id']];
							}

						}
						//запишем в логи
						if(!empty($rel_log)){ $this->log('relateAddProduct', $rel_log, $dn_id); }
					}
				}

				//////////////////////////////////////////////////
				//Работа с АДАПТАЦИЯМИ под модули
				//
				// HYPER MULTI PRODUCT MODELS он же HPM
				//
				////////////////////////////////////////////////////
				if($data['hpm_sku']){
					$this->madeHpm($data, $pr_id, $dn_id);
				}

			}#Контроль над $pr_id

		}#Заморозка добавления товара.
	}#Конец permit

	return $pr_id;
}

//Одна из самых страшных фунций :)
public function updateProduct($data, $link, $setting, $dn_id, $pr_id=0, $browser){
		
	#$this->wtfarrey($setting);
	if(empty($data['model'])){ $data['model'] = '';	}
	if(empty($data['sku'])){ $data['sku'] = '';	}
	if(empty($data['name'])){	$data['name'] = ''; }

	if(empty($data['price'])){
		$data['price'] = 0;
	}else{
		$data['price'] = (float)str_replace(' ', '', str_replace(',', '.', $data['price']));
	}

	if(empty($data['price_spec'])){
		$data['price_spec'] = 0;
	}else{
		$data['price_spec'] = (float)str_replace(' ', '', str_replace(',', '.', $data['price_spec']));
	}

	if(empty($data['des'])){ $data['des'] = ''; }
	if(empty($data['cat'])){ $data['cat'] = []; }
	if(empty($data['img'])){ $data['img'] = []; }
	if(empty($data['attr'])){ $data['attr'] = []; }

	//Таблицы для описания незнаю буду ли с ними работать но добавлю в основу модуля.
	if(empty($data['tags'])){ $data['tags'] = ''; }
	if(empty($data['meta_title'])){ $data['meta_title'] = ''; }
	if(empty($data['meta_h1'])){ $data['meta_h1'] = '';}
	if(empty($data['meta_description'])){ $data['meta_description'] = ''; }
	if(empty($data['meta_keyword'])){ $data['meta_keyword'] = ''; }
	if(empty($setting['r_status_zero'])){	$data['stock_status_id'] = 7;	} else { $data['stock_status_id'] = $setting['r_status_zero'];}
	
	//количество товара
	if(empty($data['quant'])){
		if($data['quant'] != '0'){
			if(empty($data['quant_d'])){
				$data['quant'] = 0;
			}else{
				$data['quant'] = (int)$data['quant_d'];
			}
		}

	}else{

		$data['quant'] = (int)$data['quant'];
		if($data['quant'] == 0){
			if(empty($data['quant_d'])){
				$data['quant'] = 0;
			}else{
				$data['quant'] = (int)$data['quant_d'];
			}
		}
	}
	
	//определяем статус товара
	$data['status'] = $this->getProductStatus($data, $setting);

	//Получаем выбранный магазин.
	$stores = $this->getStore($setting);
	//получаем списко используемых языков
	$langs = $this->getLang($setting);

	//Товар найден в базе дальше начинаем смотреть что нам обновить.
	if($pr_id > 0){
		#К обновлению допушен начнем составлять логи.
		$log[] = ['pr_id'=>$pr_id, 'sid'=>$setting['sid'], 'sid_value'=>$data[$setting['sid']]];
		#Начинаем разбор данных на обновленние.

		///////////////////////////////////////
		//Разное
		// 0 - Нет | 1 - Обновить
		///////////////////////////////////////
		$set_product = 'SET';
		//SKU	|| 0 - Нет | 1 - Обновить
		if($setting['r_sku'] == 1 && !empty($data['sku'])){
			$set_product = $set_product." sku='".$this->db->escape($data['sku'])."',";
			$log[] = ['name'=>'Артикул (sku)', 'value'=>$data['sku']];
		}
		if($setting['r_upc']){
			$set_product = $set_product." upc='".$this->db->escape($data['upc'])."',";
			$log[] = ['name'=> 'UPС', 'value'=> $data['upc']];
		}
		if($setting['r_ean']){
			$set_product = $set_product." ean='".$this->db->escape($data['ean'])."',";
			$log[] = ['name'=> 'EAN', 'value'=> $data['ean']];
		}
		if($setting['r_jan']){
			$set_product = $set_product." jan='".$this->db->escape($data['jan'])."',";
			$log[] = ['name'=> 'JAN', 'value'=> $data['jan']];
		}
		if($setting['r_isbn']){
			$set_product = $set_product." isbn='".$this->db->escape($data['isbn'])."',";
			$log[] = ['name'=> 'ISBN', 'value'=> $data['isbn']];
		}
		if($setting['r_mpn']){
			$set_product = $set_product." mpn='".$this->db->escape($data['mpn'])."',";
			$log[] = ['name'=> 'MPN', 'value'=> $data['mpn']];
		}
		if($setting['r_location']){
			$set_product = $set_product." location='".$this->db->escape($data['location'])."',";
			$log[] = ['name'=> 'Location', 'value'=> $data['location']];
		}
		if($setting['r_minimum']){
			$set_product = $set_product." minimum='".(int)$data['minimum']."',";
			$log[] = ['name'=> 'Минимальный заказ', 'value'=> (int)$data['minimum']];
		}
		if($setting['r_subtract']){
			$set_product = $set_product." subtract='".(int)$data['subtract']."',";
			$log[] = ['name'=> 'Вычитать со склада', 'value'=> (int)$data['subtract']];
		}
		if($setting['r_length']){
			$set_product = $set_product." length='".(float)$data['length']."',";
			$log[] = ['name'=> 'Длина', 'value'=> (float)$data['length']];
		}
		if($setting['r_width']){
			$set_product = $set_product." width='".(float)$data['width']."',";
			$log[] = ['name'=> 'Ширина', 'value'=> (float)$data['width']];
		}
		if($setting['r_height']){
			$set_product = $set_product." height='".(float)$data['height']."',";
			$log[] = ['name'=> 'Высота', 'value'=> (float)$data['height']];
		}
		if($setting['r_length_class_id']){
			$set_product = $set_product." length_class_id='".(int)$data['length_class_id']."',";
			$log[] = ['name'=> 'Единица длины', 'value'=> (int)$data['length_class_id']];
		}
		if($setting['r_weight']){
			$set_product = $set_product." weight='".(float)$data['weight']."',";
			$log[] = ['name'=> 'Вес', 'value'=> (float)$data['weight']];
		}
		if($setting['r_weight_class_id']){
			$set_product = $set_product." weight_class_id='".(int)$data['weight_class_id']."',";
			$log[] = ['name'=> 'Единица веса', 'value'=> (int)$data['weight_class_id']];
		}
		if($setting['r_status']){
			$set_product = $set_product." status='".(int)$data['status']."',";
			$log[] = ['name'=> 'Статус', 'value'=> (int)$data['status']];
		}
		if($setting['r_sort_order'] && ($data['sort_order'] !== '')){
			$set_product = $set_product." sort_order='".(int)$data['sort_order']."',";
			$log[] = ['name'=> 'Сортировка', 'value'=> (int)$data['sort_order'] ];
		}

		if($setting['r_cost']){
			$set_product = $set_product." cost='".(float)$data['cost']."',";
			$log[] = ['name'=> 'Закупочная цена', 'value'=> (float)$data['cost'] ];
		}

		$set_product = $set_product." dn_id = ".(int)$dn_id.", date_modified = NOW()";

		//Обязательный запрос на обновление. 
		$up_sku = $this->db->query("UPDATE " . DB_PREFIX . "product ".$set_product." WHERE `product_id`=".(int)$pr_id);

		#$this->wtfarrey($set_product);
		///////////////////////////////////////
		//Название
		// 0 - Нет | 1 - Обновить
		///////////////////////////////////////

		//Запрос на добавление в description
		$set_pr_desc = '';
		if($setting['r_name'] == 1 && !empty($data['name'])){
			
			$set_pr_desc .= "`name`='".$this->db->escape($data['name'])."'";

			//добавим информацию в логи.
			$log[] = ['name'=>'Название', 'value'=>$data['name']];

		}

		//////////////////////////////////////////////////
		//Работа с MЕTA ДАННЫМИ
		// 0 - Незаполнять
		// 1 - По SEO шаблону
		////////////////////////////////////////////////////

		//Если работаем с сео шаблонами.
		if($setting['u_made_meta'] == 1){

			if(!empty($data['seo_title'])){
				$data['meta_title'] = $data['seo_title'];
			}
			if(!empty($data['seo_desc'])){
				$data['meta_description'] = $data['seo_desc'];
			}
			if(!empty($data['seo_keyw'])){
				$data['meta_keyword'] = $data['seo_keyw'];
			}
			if(!empty($data['seo_h1'])){
				$data['meta_h1'] = $data['seo_h1'];
			}

			//проверяем пустой ли запрос. Если нет добавляем запятую.
			if(!empty($set_pr_desc)){ $set_pr_desc .= ","; }

			$set_pr_desc .= "meta_title = '".$this->db->escape($data['meta_title'])."',
											meta_description = '".$this->db->escape($data['meta_description'])."',
											meta_keyword = '".$this->db->escape($data['meta_keyword'])."'";

			//Проверяем версию движка для правильного заполнения.
			if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
				$set_pr_desc .= ",meta_h1='".$this->db->escape($data['meta_h1'])."'";
			}
		}

		///////////////////////////////////////
		//Описание
		// 0 - Не обновлять | 1 - Обновить | 2 - Добавить в конец описания | 3 - Добавить в начало описания.
		///////////////////////////////////////
		if($setting['u_des'] != 0){	

			//обработка фото описаний.
			$data['des'] = $this->dwImgToDesc($data['des'], $link, $data['des_dir'], $data['img_name'], $setting['r_des_dir'], $dn_id, $browser);
			if(!empty($set_pr_desc)){ $set_pr_desc .= ",";}#Если запрос не пустой добавим туда запятую.

			//проверяем как обновлять.
			if($setting['u_des'] == 1){
				 
				$set_pr_desc .= "`description`='".$this->db->escape($data['des'])."'";
			
			}elseif($setting['u_des'] == 2){

				$set_pr_desc .= "`description`= CONCAT('".$this->db->escape($data['des'].PHP_EOL)."', description)";

			}elseif($setting['u_des'] == 3){

				$set_pr_desc .= "`description`= CONCAT(`description`,'".$this->db->escape(PHP_EOL.$data['des'])."')";
				
			}

			//Пишем лог обновления для описания.
			$log[] = ['name'=>'Описание', 'value'=>'{описание в логи не пишется}'];

		}

		///////////////////////////////////////
		//Теги
		// 0 - Нет | 1 - Обновить
		///////////////////////////////////////
		if($setting['r_tags'] == 1){

			if(!empty($set_pr_desc)){ $set_pr_desc .= ","; }#Если запрос не пустой добавим туда запятую. 
			$set_pr_desc .= "tag='".$this->db->escape($data['tags'])."'";
			$log[] = ['name'=>'Добавлены теги в товар', 'value'=>$this->db->escape($data['tags'])];
		
		}

		///////////////////////////////////////
		//ВЫПОЛНЕНИЯ ЗАПРОСА В БАЗУ oc_product_description
		///////////////////////////////////////
		if(!empty($set_pr_desc)){
			//Составляе строку.
			foreach ($langs as $lang) {
				//проверяем есть ли запись в этом языке.
				$sql_desc = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product_description WHERE `product_id`=".(int)$pr_id." AND language_id=".(int)$lang['language_id']);

				#Если есть запись обновляем, если нету создаем.
				if($sql_desc->num_rows > 0) {
					$sql = "UPDATE ".DB_PREFIX."product_description SET ".$set_pr_desc." WHERE `product_id`=".(int)$pr_id." AND language_id=".(int)$lang['language_id'];
					//Запрос
					#$this->wtfarrey($sql);
					$this->db->query($sql);

				}else{
					
					#$set_pr_desc .= ",product_id=".(int)$pr_id.", language_id=".(int)$lang['language_id'];
					$sql = "INSERT INTO " . DB_PREFIX . "product_description SET ".$set_pr_desc.",product_id=".(int)$pr_id.", language_id=".(int)$lang['language_id'];
					//Запрос
					$this->db->query($sql);
				}
			}
		}

		//////////////////////////////////////////////////
		//Работа с SEO_URL
		// 0 - Незаполнять
		// 1 - Создать из имени товара
		// 2 - Создать по шаблону заполненому на вкладке SEO
		////////////////////////////////////////////////////
		if($setting['r_made_url'] == 1 && $setting['u_up_url'] == 1){

			//Получаем юрл из имени.
			if(!empty($data['name'])){
				$pr_url = $this->madeUrl($data['name']);

				//Записываем url
				$do = ['where'=>'pr','what'=>'up'];
				$this->addSeoUrl($pr_url, $pr_id, $setting, $langs, $stores, $dn_id, $do);
			}else{
				$logs['name'] = 'product name';
				$this->log('badUrl', $logs, $dn_id);
			}

		}elseif($setting['r_made_url'] == 2 && $setting['u_up_url'] == 1){

			if(!empty($data['seo_url'])){
				//Получаем юрл из имени.
				$pr_url = $this->madeUrl($data['seo_url']);

				//Записываем url
				$do = ['where'=>'pr','what'=>'up'];
				$this->addSeoUrl($pr_url, $pr_id, $setting, $langs, $stores, $dn_id, $do);
			}else{
				$logs['name'] = 'seo_url';
				$this->log('badUrl', $logs, $dn_id);
			}
		}


		///////////////////////////////////////
		//Цена
		// 0 - Нет | 1 - Обновить | 2 - обн. если цена выросла. | 3 - обн. если цена упала
		///////////////////////////////////////

		if($setting['r_price'] == 1){
			$up_price = $this->db->query("UPDATE " . DB_PREFIX . "product SET `price`='".(float)$data['price']."' WHERE `product_id`=".(int)$pr_id);

			if($up_price) $log[] = ['name'=>'Цена', 'value'=>$data['price']];

		}elseif($setting['r_price'] == 2){
			
			$temp_price = $this->db->query("SELECT price FROM oc_product WHERE product_id = ".(int)$pr_id." AND price < '".(float)$data['price']."'")->num_rows;

			if($temp_price){

				$up_price = $this->db->query("UPDATE " . DB_PREFIX . "product SET `price`='".(float)$data['price']."' WHERE `product_id`=".(int)$pr_id);

				if($up_price) $log[] = ['name'=>'Цена (выросла)', 'value'=>$data['price']];

			}

		}elseif($setting['r_price'] == 3){
			
			$temp_price = $this->db->query("SELECT price FROM oc_product WHERE product_id = ".(int)$pr_id." AND price > '".(float)$data['price']."'")->num_rows;

			if($temp_price){

				$up_price = $this->db->query("UPDATE " . DB_PREFIX . "product SET `price`='".(float)$data['price']."' WHERE `product_id`=".(int)$pr_id);

				if($up_price) $log[] = ['name'=>'Цена (упала)', 'value'=>$data['price']];

			}
		}

		///////////////////////////////////////
		// Обновление акционных цен
		// 0 - Не обновлять цену акций. 1 - Обновлть цены акций
		///////////////////////////////////////

		if($setting['r_price_spec'] == 1){

			//Если акция равна 0 то удаляем ее из товара.
			if ($data['price_spec'] != 0) {
				$this->addPriceSpecToProduct($data['price_spec'], $setting, $pr_id, $dn_id);
			}else{
				$this->delPriceSpecToProduct($data['price_spec'], $setting, $pr_id, $dn_id);
			}

		}

		///////////////////////////////////////
		//Количество
		// 0 - Нет | 1 - Обновить
		///////////////////////////////////////
		if($setting['r_quant'] == 1){
			$up_quant = $this->db->query("UPDATE " . DB_PREFIX . "product SET
				`quantity`='".(int)$data['quant']."',
				`stock_status_id`='".(int)$data['stock_status_id']."'
				WHERE `product_id`=".(int)$pr_id);

			if($up_quant) $log[] = ['name'=>'Количество', 'value'=>$data['quant']];
		}
		///////////////////////////////////////
		//Производитель
		// 0-Не обновлять | 1-Создавать и обновлять в товаре, если новое значение| 2-Обновлять в товаре если новый производитель и он уже создан в магазин
		///////////////////////////////////////
		if($setting['u_manufac'] == 1 && !empty($data['manufac'])){

			$manuf_id = $this->getIdManuf($data['manufac']);

			if($manuf_id == 0){
				//Создаем производителя.
				$manuf_id = $this->addManuf($data, $langs, $setting, $stores, $dn_id);
				#Проверяем создание.
				if($manuf_id != 0){
					$up_manuf = $this->db->query("UPDATE ".DB_PREFIX."product SET `manufacturer_id`='".(int)$manuf_id."' WHERE `product_id`=".(int)$pr_id);

					if($up_manuf) $log[] = ['name'=>'Производитель id', 'value'=>$manuf_id];
				}

			}else{
				$up_manuf = $this->db->query("UPDATE ".DB_PREFIX."product SET `manufacturer_id`='".(int)$manuf_id."' WHERE `product_id`=".(int)$pr_id);

				if($up_manuf) $log[] = ['name'=>'Производитель id', 'value'=>$manuf_id];
			}

		}elseif($setting['u_manufac'] == 2 && !empty($data['manufac'])){

			$manuf_id = $this->getIdManuf($data['manufac']);
			if($manuf_id > 0){
				$up_manuf = $this->db->query("UPDATE " . DB_PREFIX . "product SET `manufacturer_id`='".(int)$manuf_id."' WHERE `product_id`=".(int)$pr_id);

				if($up_manuf) $log[] = ['name'=>'Производитель id', 'value'=>$manuf_id];
			}

		}

		///////////////////////////////////////
		//Категории
		// 0 - Не обновлять категории
		// 1 - Создавать | Обновлять категории в товаре
		// 2 - Не создавать новые категории, но обновлять если категория уже создана
		// 3 - Создавать, и добавлять товар в дополнительные новые категории
		///////////////////////////////////////
		if($setting['u_cat'] == 1){

			//Формируем имя для логов. В дальнейшем нужно вырезать. Зачем эти ништяки если их не читают.
			$cat_way = implode('->', $data['cat']);
			//проверяем есть ли такая котегория и если есть возврашем ее id
			$data['cats_id'] = $this->getCategorysId($data['cat']);

			if($data['cats_id'][0] == 0){
				$this->addCat($data, $setting, $langs, $stores, $dn_id);
				$data['cats_id'] = $this->getCategorysId($data['cat']);
			}

			if($data['cats_id'][0] > 0){

				//Вместо обновления, мы удаляем записи, и создаем заново.
				$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id`=".(int)$pr_id);

				//Добавляем товар в нужную категорию.
				$this->addProdToCat($data['cats_id'], $pr_id, $setting);

				$log[] = ['name'=>'Категория id = '.implode(',',$data['cats_id']).' Адрес', 'value'=>$cat_way];
			}

		}elseif($setting['u_cat'] == 2){
			//Формируем имя для логов. В дальнейшем нужно вырезать. Зачем эти ништяки если их не читают.
			$cat_way = implode('->', $data['cat']);

			//проверяем есть ли такая котегория и если есть возврашем ИХ!!!!!! ID
			$data['cats_id'] = $this->getCategorysId($data['cat']);

			if($data['cats_id'][0] != 0){

				//Вместо обновления, мы удаляем записи, и создаем заново.
				$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id`=".(int)$pr_id);

				//Добавляем товар в нужную категорию.
				$this->addProdToCat($data['cats_id'], $pr_id, $setting);

				$log[] = ['name'=>'Категория id = '.implode(',',$data['cats_id']).' Адрес', 'value'=>$cat_way];

			}else{
				$log[] = ['name'=>'Категория НЕ ОБНОВЛЕНА! Поскольку категория ', 'value'=>'|'.$cat_way.'| Не создана в магазине'];
			}

		}elseif($setting['u_cat'] == 3){
			
			//Формируем имя для логов. В дальнейшем нужно вырезать. Зачем эти ништяки если их не читают.
			$cat_way = implode('->', $data['cat']);
			//проверяем есть ли такая котегория и если есть возврашем ее id
			$data['cats_id'] = $this->getCategorysId($data['cat']);

			if($data['cats_id'][0] == 0){
				$this->addCat($data, $setting, $langs, $stores, $dn_id);
				$data['cats_id'] = $this->getCategorysId($data['cat']);
			}

			if($data['cats_id'][0] > 0){

				//Добавляем товар в нужную категорию.
				$this->addProdToCat($data['cats_id'], $pr_id, $setting, 'add_new');

				$log[] = ['name'=>'Категории добавлены в товар id = '.implode(',',$data['cats_id']).' Адрес', 'value'=>$cat_way];
			}
		}

		//////////////////////////////////////
		//Фото
		// 0 - Не обновлять
		// 1 - Заменит все изображения товара (Без физического удаления изображений с хостинга)
		// 2 - Заменит все изображения товара (С удалением изображений с диска. Будут удалены изображения что привязаны к товару в момент обновления)
		// 3 - Добавлять дополнительные изображения при обновлении (Внимание!!! Не производится проверка на дубли!!!)
		// 4 - Добавляет в товар фото, только если в товаре небыло фото.
		///////////////////////////////////////
		if($setting['u_img'] == 1){
			
			#Обновлять [Заменит все фото у товара][Изображения не удаляюстся с сервера!]
			$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);
			//если массив пустой тогда ничего не удаляем, нету смысла.
			if(!empty($data['img_path'])){

				$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id=".(int)$pr_id);

				//Добавляем главное фото
				$up_img_main = $this->db->query("UPDATE ".DB_PREFIX."product SET image='".$this->db->escape($data['img_path'][0])."' WHERE product_id ='". (int)$pr_id ."'");

				if($up_img_main) $log[] = ['name'=>'Главное изображение', 'value' => HTTP_CATALOG.'image/'.$data['img_path'][0]];
				//Удаляем главное фото из массива.
				unset($data['img_path'][0]);

				//Добавлем доп фото.
				foreach($data['img_path'] as $key => $image){
					$up_img = $this->db->query("INSERT INTO ".DB_PREFIX ."product_image SET product_id ='".(int)$pr_id."', image ='".$this->db->escape($image)."', sort_order = ".(int)$key);

					if($up_img) $log[] = ['name'=>'Дополнительные изображения', 'value' => HTTP_CATALOG.'image/'.$image];
				}
			}

		}elseif($setting['u_img'] == 2){
			
			#Обновлять и удалить старые [Внимание!!! Старые фото товара будут удалены с сервера]
			//для удаления фото товара применяем фунцию из редактора товаров ;) 
			$this->delImgsInProduct($pr_id, $dn_id);
			$log[] = ['name'=>'Удалены старые изображения фото с сервера. И загружены новые', 'value' => '=>'];

			$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);
			//если массив пустой тогда ничего не удаляем, нету смысла.
			if(!empty($data['img_path'])){

				//Добавляем главное фото
				$up_img_main = $this->db->query("UPDATE ".DB_PREFIX."product SET image='".$this->db->escape($data['img_path'][0])."' WHERE product_id ='". (int)$pr_id ."'");

				if($up_img_main) $log[] = ['name'=>'Главное изображение', 'value' => HTTP_CATALOG.'image/'.$data['img_path'][0]];
				//Удаляем главное фото из массива.
				unset($data['img_path'][0]);

				//Добавлем доп фото.
				foreach($data['img_path'] as $key => $image){
					$up_img = $this->db->query("INSERT INTO ".DB_PREFIX ."product_image SET product_id ='".(int)$pr_id."', image ='".$this->db->escape($image)."',sort_order = ".(int)$key);

					if($up_img) $log[] = ['name'=>'Дополнительные изображения', 'value' => HTTP_CATALOG.'image/'.$image];
				}
			}

		}elseif($setting['u_img'] == 3){
			
			#Добавлять при обновлении товара
			//начинаем перебор массива с фото
			$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);

			//если нету главного фото то добавляем его в товар.
			$check_main_img = $this->db->query("SELECT image FROM " . DB_PREFIX . "product WHERE product_id=".(int)$pr_id);

			if(empty($check_main_img->row['image']) && !empty($data['img_path'])){
				$up_img = $this->db->query("UPDATE ".DB_PREFIX."product SET image='".$this->db->escape($data['img_path'][0])."' WHERE product_id =".(int)$pr_id);

				if($up_img) $log[] = ['name'=>'Главное изображение', 'value' => HTTP_CATALOG.'image/'.$data['img_path'][0]];
				//Удаляем главное фото из массива.
				unset($data['img_path'][0]);
			}

			//Добавлем доп фото.
			foreach($data['img_path'] as $key => $image){
				$up_img = $this->db->query("INSERT INTO ".DB_PREFIX."product_image SET product_id = '".(int)$pr_id."', image = '".$this->db->escape($image)."', sort_order = ".(int)$key);

				if($up_img) $log[] = ['name'=>'Дополнительные изображения', 'value' => HTTP_CATALOG.'image/'.$image];
			}

		}elseif($setting['u_img'] == 4){
			
			#Добавлять при обновлении товара, если в товаре небыло фото.
			

			//если нету главного фото то добавляем его в товар.
			$check_main_img = $this->db->query("SELECT `image` FROM `".DB_PREFIX."product` WHERE `product_id` = ".(int)$pr_id)->row['image'];
			$check_all_img = $this->db->query("SELECT `product_id` FROM `".DB_PREFIX."product_image` WHERE `product_id` = ".(int)$pr_id)->num_rows;

			if(empty($check_main_img) && empty($check_all_img)){

				//начинаем перебор массива с фото
				$data['img_path'] = $this->dwImagToProduct($dn_id, $data['img'], $data['img_dir'], $data['img_name'], $setting['r_img_dir'], $browser, 0);
				
				//загружаем фото, и проверяем массив что бы был не пустой.
				if(!empty($data['img_path'])){
					$up_img = $this->db->query("UPDATE ".DB_PREFIX."product SET image='".$this->db->escape($data['img_path'][0])."' WHERE product_id =".(int)$pr_id);
					if($up_img) $log[] = ['name'=>'Главное изображение', 'value' => HTTP_CATALOG.'image/'.$data['img_path'][0]];
					//Удаляем главное фото из массива.
					unset($data['img_path'][0]);
				
					//Добавлем доп фото.
					foreach($data['img_path'] as $key => $image){
						$up_img = $this->db->query("INSERT INTO ".DB_PREFIX."product_image SET product_id = '".(int)$pr_id."', image = '".$this->db->escape($image)."', sort_order = ".(int)$key);
						if($up_img) $log[] = ['name'=>'Дополнительные изображения', 'value' => HTTP_CATALOG.'image/'.$image];
					}
				}
			}
		}

		//////////////////////////////////////
		//Атрибуты
		// 0 - Не работать с атрибутами.
		// 1 - Создавать/Добавлять/Обновлять атрибуты.
		// 2 - Добавить/Обновить атрибуты в товаре, не создавать новые.
		// 3 - Добавить новый не обновлять существующие, не создавать новые
		// 4 - Обновить значения существующих атрибутов, не добавлять, не создавать новые.
		// 5 - Удалить все атрибуты в товаре и загрузить заново
		///////////////////////////////////////
		if($setting['u_attr'] == 1){
			
			#Создаем атрибуты и добавляем в товар.
			#Проверяем сушествует атрибут или нет.
			foreach($data['attr'] as $attr){

				$attr['id'] = $this->getIdAttr($attr[0]);

				#Если нету тогда создаем.
				if($attr['id'] == 0){

					$attr['id'] = $this->addAttr($attr[0], $langs, $setting, $dn_id);
					//Если после создания атрибут есть тогда записываем его в товар. Если нет проходим дальше.
					if($attr['id'] != 0){
						$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);

						$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
					}

				}elseif($attr['id'] > 0){

					//Проверяем есть ли в товере такой атрибут.
					$check_attr = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute`
						WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);

					if($check_attr->num_rows > 0){

						//Значит такой атрибут есть и нужно его обновить.
						$attr[1] = trim($attr[1]);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text`='".$this->db->escape($attr[1])."' WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);
						$log[] = ['name'=>'Атрибут обновлен в товаре '.$attr[0], 'value'=>$attr[1]];

					}else{

						//если нет тогда добавить его в товар.
						$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
						$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
					}
				}
			}

		}elseif($setting['u_attr'] == 2){
			
			#добавляем в товар только сушествующие или обновляем только сушествующие
			#Проверяем сушествует атрибут или нет.
			foreach($data['attr'] as $attr){

				$attr['id'] = $this->getIdAttr($attr[0]);

				#Если сушествует тогда проверяем если в товаре.
				if($attr['id'] > 0){

					//Проверяем есть ли в товере такой атрибут.
					$check_attr = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute`
						WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);

					if($check_attr->num_rows > 0){

						//Значит такой атрибут есть и нужно его обновить.
						$attr[1] = trim($attr[1]);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text`='".$this->db->escape($attr[1])."' WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);
						$log[] = ['name'=>'Атрибут обновлен в товаре '.$attr[0], 'value'=>$attr[1]];

					}else{

						//если нет тогда добавить его в товар.
						$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
						$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
					}
				}
			}

		}elseif($setting['u_attr'] == 3){
			
			#добавляем в товар только сушествующие не обновляем, не создаем.
			#Проверяем сушествует атрибут или нет.
			foreach($data['attr'] as $attr){

				$attr['id'] = $this->getIdAttr($attr[0]);

				#Если сушествует тогда проверяем если в товаре.
				if($attr['id'] > 0){

					//Проверяем есть ли в товере такой атрибут.
					$check_attr = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute`
						WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);

					//если нет тогда добавить его в товар.
					if($check_attr->num_rows == 0){
						$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
						$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
					}

				}
			}

		}elseif($setting['u_attr'] == 4){
			
			#Обновить только сушествующие
			#Проверяем сушествует атрибут или нет.
			foreach($data['attr'] as $attr){

				$attr['id'] = $this->getIdAttr($attr[0]);

				#Если сушествует тогда проверяем если в товаре.
				if($attr['id'] > 0){

					//Проверяем есть ли в товере такой атрибут.
					$check_attr = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute`
						WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);

					if($check_attr->num_rows > 0){

						//Значит такой атрибут есть и нужно его обновить.
						$attr[1] = trim($attr[1]);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text`='".$this->db->escape($attr[1])."' WHERE `product_id`=".(int)$pr_id." AND `attribute_id`=".(int)$attr['id']);
						$log[] = ['name'=>'Атрибут обновлен в товаре '.$attr[0], 'value'=>$attr[1]];

					}
				}
			}

		}elseif($setting['u_attr'] == 5){
			
			#Удаляем из товар все атрибуты и записываем заново.

			#Сначала удаляем все атрибуты из товара.
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id`=".(int)$pr_id);

			#Проверяем сушествует атрибут или нет.
			foreach($data['attr'] as $attr){

				$attr['id'] = $this->getIdAttr($attr[0]);

				#Если нету тогда создаем.
				if($attr['id'] == 0){

					$attr['id'] = $this->addAttr($attr[0], $langs, $setting, $dn_id);
					//Если после создания атрибут есть тогда записываем его в товар. Если нет проходим дальше.
					if($attr['id'] != 0){
						$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);

						$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
					}

				}elseif($attr['id'] > 0){
					//если нет тогда добавить его в товар.
					$this->addAttrToProduct($pr_id, $attr, $langs, $dn_id);
					$log[] = ['name'=>'Атрибут добавлен в товар '.trim($attr[0]), 'value'=>trim($attr[1])];
				}
			}

		}
		//////////////////////////////////////////////////
		//Обновление макета в товаре.
		// 0) - не обновлять. 1) - обновлять
		////////////////////////////////////////////////////
		if(!empty($setting['r_layout_pr'])){

			//Удаляем записи макета в товаре.
			$this->db->query("DELETE FROM `".DB_PREFIX."product_to_layout` WHERE product_id = ".(int)$pr_id);

			if(!empty($data['layout_pr'])){

				foreach ($stores as $store) {
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_to_layout SET 
						product_id = '".(int)$pr_id."', 
						store_id = '".$store['store_id']. "',
						layout_id = '".(int)$data['layout_pr']."'");
				}
				
				$log[] = ['name'=> 'Обновлен макет в товаре на layout_id', 'value'=>(int)$data['layout_pr']];
				#$this->log('uplayout', $logs, $dn_id);
			}

		}

		//Отправляем отчет об обновлении.
		$this->log('UpdateProduct', $log, $dn_id);

		//////////////////////////////////////////////////
		//Работа с Оption при добавлении товара
		// 0 - Не работать с опциями
	  // 1 - Создавать, заполнять в товар
	  // 2 - Заполнять в товаре без создания новых опций и значений опций
		////////////////////////////////////////////////////
		$this->controlOption($data['opts'], $setting, $langs, $pr_id, $dn_id, 'up');


		//////////////////////////////////////////////////
		// Заполнение рекомендованных товаров
		// 0 - Не работать с рекомендациями. 
		// 1 - Обновлять рекомендации.
		////////////////////////////////////////////////////

		if($setting['r_related'] == 1 && $data['related_sku']){

			//Удаляем для начала все связи этого товара.
			$this->db->query("DELETE FROM `".DB_PREFIX."product_related` WHERE product_id=".(int)$pr_id);
			
			//получаем данные с поля для проверки связи.
			$related_sku = $this->db->query("SELECT ".$data['related_sku']." as rel_str FROM ".DB_PREFIX."product WHERE product_id=".$pr_id);

			//Проверяем есть ли что
			if($related_sku->num_rows){

				$related_arr = explode(';', $related_sku->row['rel_str']);
				//проходим по каждому артикулу и составляем запрос.
				$sid = '';
				foreach($related_arr as $key => $rel_sku){
					if($key){ $sid .= ",'".$this->db->escape($rel_sku)."'"; }else{ $sid .= "'".$this->db->escape($rel_sku)."'"; }
				}

				//получаем id товаров с которыми нужно сделать связи.
				$relate_prs = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product WHERE ".$setting['sid']." in (".$sid.")");

				//Если связи есть в магазине то записываем их.
				if($relate_prs->num_rows){
					//массив для логов.
					$rel_log = [];
					foreach($relate_prs->rows as $relate){
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$pr_id.", related_id =".(int)$relate['product_id']);
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$relate['product_id'].", related_id =".(int)$pr_id);
						$rel_log[] = [$pr_id, $relate['product_id']];
					}
					//запишем в логи
					if(!empty($rel_log)){ $this->log('relateAddProduct', $rel_log, $dn_id); }
				}
			}

			//Проверяем другие товары хотят наш товар добавить себе в избранное или нет.
			$relate_prs = $this->db->query("SELECT product_id, ".$data['related_sku']." as re_str 
																			FROM ".DB_PREFIX."product 
																			WHERE ".$data['related_sku']." LIKE '%".$this->db->escape($data[$setting['sid']])."%'");
			//Если ответ не пустой тогда перебераем.
			if($relate_prs->num_rows){
				//перебираем все отваты.
				$rel_log = [];
				foreach($relate_prs->rows as $relate){

					//Дальше убиждаемся что связь верна.
					$relate['re_str'] = explode(';', $relate['re_str']);
					if(in_array($data[$setting['sid']], $relate['re_str'])){
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$pr_id.", related_id =".(int)$relate['product_id']);
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id =".(int)$relate['product_id'].", related_id =".(int)$pr_id);
						$rel_log[] = [$pr_id, $relate['product_id']];
					}

				}
				//запишем в логи
				if(!empty($rel_log)){ $this->log('relateAddProduct', $rel_log, $dn_id); }
			}
		}

		//////////////////////////////////////////////////
		//Работа с АДАПТАЦИЯМИ под модули
		//
		// HYPER MULTI PRODUCT MODELS он же HPM
		//
		////////////////////////////////////////////////////
		if($setting['r_hpm'] == 1){
			$this->madeHpm($data, $pr_id, $dn_id);
		}
	}

}

############################################################################################
############################################################################################
#						Страница пред просмотра парсинга в им.
############################################################################################
############################################################################################

public function getFormShowProduct($dn_id){
	$links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id." ORDER BY id ASC LIMIT 0, 250");
	$links = $links->rows;
	return $links;
}

//Контролер пред просмотра товара.
public function goShowToIm($url, $dn_id){
	//Выполняем запрос на пред просмотр.
	$url = str_replace('&amp;', '&', $url);
	$urls[] = $url;
	$datas = $this->multiCurl($urls, $dn_id);

	//пишем логи, но не проверяем ошибку она не нужна в пред просмотре.
	$curl_error = $this->sentLogMultiCurl($datas[$url], $dn_id);
	//Берем тот массив с мульти запроса у которого ключ равен ссылке. 
	$form = $this->preparinDataToStore($datas[$url], $dn_id);
	
	//информация о допусках

	//Получам дополнительные данные из настроек.
	$setting = $this->getSettingToProduct($dn_id);
	
	//Получаем разрешения на действия.
	if(!empty($setting['grans_permit'])){
		
		$form['permit_grans'] = $this->checkGransPermit($form, $setting, $dn_id);
		//проверяем допуски
		if( empty($form['permit_grans'][1]['permit']) || empty($form['permit_grans'][2]['permit']) || empty($form['permit_grans'][3]['permit'])){
			$form['permit_grans_text'] = 'ВНИМАНИЕ!!! Страница не прошла все допуски, подробнее в отладочной информации.';
		}

	}
	
	if($setting['r_model'] == 1){ 
		$form['model'] = 'По умолчанию (id товара)';
	}

	//Грубое применение значений по умолчанию.
	//количество товара
	if(empty($form['quant'])){
		if($form['quant'] != '0'){
			if(empty($form['quant_d'])){
				$form['quant'] = 0;
			}else{
				$form['quant'] = (int)$form['quant_d'];
			}
		}

	}else{

		$form['quant'] = (int)$form['quant'];
		if($form['quant'] == 0){
			if(empty($form['quant_d'])){
				$form['quant'] = 0;
			}else{
				$form['quant'] = (int)$form['quant_d'];
			}
		}
	}

	if(empty($form['des'])){
		$form['des'] = $form['des_d'];
	}
	if(empty($form['img'])){
		$form['img'][] = $form['img_d'];
	}
	if(empty($form['price'])){
		$form['price'] = 0;
	}else{
		$form['price'] = (float)str_replace(',','.', str_replace(' ', '', $form['price']));
	}

	$form['img_info'] = $form['img'];
	//преобразование и парсинг фото для пред посмотра.

	$form['img'] = $this->madeImgShow($form['img'], $dn_id);

	array_walk_recursive($form, array($this, 'htmlview'));

	$form['debug_text'] = $this->madeDebugIfo($form, $url);

	#$this->wtfarrey($form);
	return $form;
}

//Пред просмотр формирование отладочной информации.
public function madeDebugIfo($data, $url){
	$text = [];

	if(!empty($data)){
		$text['pr']['url'] = ['name'=>'Ссылка на товар который просматриваете', 'row'=>1, 'text'=>$url];
		$text['pr']['model'] = ['name'=>'Код товара', 'row'=>1, 'text'=>$data['model']];
		$text['pr']['sku'] = ['name'=>'Артикул', 'row'=>1, 'text'=>$data['sku']];
		$text['pr']['name'] = ['name'=>'Название', 'row'=>1, 'text'=>$data['name']];
		$text['pr']['price'] = ['name'=>'Цена', 'row'=>1, 'text'=>$data['price']];
		$text['pr']['price_spec'] = ['name'=>'Акционная цена', 'row'=>1, 'text'=>$data['price_spec']];
		$text['pr']['quant'] = ['name'=>'Количество', 'row'=>1, 'text'=>$data['quant']];
		$text['pr']['manufac'] = ['name'=>'Производитель', 'row'=>1, 'text'=>$data['manufac']];
		$text['pr']['des'] = ['name'=>'Описание', 'row'=>10, 'text'=>$data['des']];
		$text['pr']['cat'] = ['name'=>'Категории', 'row'=>5, 'text'=>''];
		$text['pr']['img'] = ['name'=>'Изображения', 'row'=>10, 'text'=>''];
		$text['pr']['attr'] = ['name'=>'Атрибуты', 'row'=>10, 'text'=>''];


		$text['seo']['seo_url'] = ['name'=>'SEO URL Ссылка на товар', 'row'=>1, 'text'=>$this->madeUrl($data['seo_url'])];
    $text['seo']['seo_h1'] = ['name'=>'HTML-тег H1 товара', 'row'=>1, 'text'=>$data['seo_h1']];
    $text['seo']['seo_title'] = ['name'=>'HTML-тег Title товара', 'row'=>2, 'text'=>$data['seo_title']];
    $text['seo']['seo_desc'] = ['name'=>'Мета-тег Description товара', 'row'=>5, 'text'=>$data['seo_desc']];
    $text['seo']['seo_keyw'] = ['name'=>'Мета-тег Keywords товара', 'row'=>5, 'text'=>$data['seo_keyw']];

    $text['seo']['cat_seo_url'] = ['name'=>'SEO URL Ссылка категории', 'row'=>1, 'text'=>$this->madeUrl($data['cat_seo_url'])];
    $text['seo']['cat_seo_h1'] = ['name'=>'HTML-тег H1 категории', 'row'=>1, 'text'=>$data['cat_seo_h1']];
    $text['seo']['cat_seo_title'] = ['name'=>'HTML-тег Title категории', 'row'=>2, 'text'=>$data['cat_seo_title']];
    $text['seo']['cat_seo_desc'] = ['name'=>'Мета-тег Description категории', 'row'=>5, 'text'=>$data['cat_seo_desc']];
    $text['seo']['cat_seo_keyw'] = ['name'=>'Мета-тег Keywords категории', 'row'=>5, 'text'=>$data['cat_seo_keyw']];

    $text['seo']['manuf_seo_url'] = ['name'=>'SEO URL Ссылка производителя', 'row'=>1, 'text'=>$this->madeUrl($data['manuf_seo_url'])];
    $text['seo']['manuf_seo_h1'] = ['name'=>'HTML-тег H1 производителя', 'row'=>1, 'text'=>$data['manuf_seo_h1']];
    $text['seo']['manuf_seo_title'] = ['name'=>'HTML-тег Title производителя', 'row'=>2, 'text'=>$data['manuf_seo_title']];
    $text['seo']['manuf_seo_desc'] = ['name'=>'Мета-тег Description производителя', 'row'=>5, 'text'=>$data['manuf_seo_desc']];
    $text['seo']['manuf_seo_keyw'] = ['name'=>'Мета-тег Keywords производителя', 'row'=>5, 'text'=>$data['manuf_seo_keyw']];

		//Фото отдельный подход
		if(!empty($data['cat'])){

			foreach($data['cat'] as $cat){
				$text['pr']['cat']['text'] .= $cat.PHP_EOL;
			}
		}

		//Фото отдельный подход
		if(!empty($data['img_info'])){

			foreach($data['img_info'] as $img){
				$text['pr']['img']['text'] .= $img.PHP_EOL;
			}
		}

		//Атрибуты отдельный подход
		if(!empty($data['attr'])){

			foreach($data['attr'] as $attr){
				
				if(!empty($attr[0])){
					#@$text['pr']['attr']['text'] .= $attr[0].' => '.$attr[1].PHP_EOL; #Заглушил временно, можно удалить заглушку 
					$text['pr']['attr']['text'] .= $attr[0].' => '.$attr[1].PHP_EOL; #Заглушил временно, можно удалить заглушку 
				}
			}
		}

		//Подготовка опций к дебагу.
		$opts_debu = [];
		$opts_debu['text'] = '';
		#$this->wtfarrey($data);
		if(!empty($data['opt_name'])){
			$opts_debu['name'] = explode('{next}', $data['opt_name']);
			$opts_debu['opt_value'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_value']));
			$opts_debu['opt_price'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_price']));
			$opts_debu['opt_quant'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_quant']));
			$opts_debu['opt_quant_d'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_quant_d']));
			$opts_debu['opt_imgs'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_imgs']));
			$opts_debu['opt_data'] = explode('{next}', str_replace('{!na!}', '{csvnc}', $data['opt_data']));
			$deb_quant_d = $opts_debu['opt_quant_d'][0];
			
			foreach ($opts_debu['name'] as $key => $name) {
				if ($name != '{|}0'){
					$name = explode('{|}', $name);
					$dop = explode('}{', $opts_debu['opt_data'][$key]);
					if($dop[0] == '{required_1'){ $dop[0] = 'Да'; } else { $dop[0] = 'Нет'; }
					$dop[1] = str_replace('price_prefix_', '', $dop[1]);
					$dop[1] = str_replace('}', '', $dop[1]);
					if(empty($opts_debu['opt_imgs'][$key])){ $opts_debu['opt_imgs'][$key] = ''; }

					//определяем колво.
					//Если для этой опции не указали значение по умолчанию.
					if(!isset($opts_debu['opt_quant_d'][$key])) { $opts_debu['opt_quant_d'][$key] = $deb_quant_d;}
					//Если нету колва то берем значение по умолчанию. 
					if (empty($opts_debu['opt_quant'][$key])) { $opts_debu['opt_quant'][$key] = '[Сработало по умолчанию, Кол-во = '.$opts_debu['opt_quant_d'][$key].']';}

					$opts_debu['text'] = $opts_debu['text'].
																'Название опции  => '.$name[0].PHP_EOL.
																'Значение опции  => '.$opts_debu['opt_value'][$key].PHP_EOL.
																'Цены опции      => '.$opts_debu['opt_price'][$key].PHP_EOL.
																'Кол-во опции    => '.$opts_debu['opt_quant'][$key].PHP_EOL.
																'Изображение     => '.$opts_debu['opt_imgs'][$key].PHP_EOL.
																'ID по умолчанию => '.$name[1].PHP_EOL.
																'Обязательная    => '.$dop[0].PHP_EOL.
																'Префикс цены    => '.$dop[1].PHP_EOL.PHP_EOL.
																'##################################################'.PHP_EOL.PHP_EOL;
				}

			}

		}
		$text['pr']['opts'] = ['name'=>'Опции', 'row'=>19, 'text'=>$opts_debu['text']];

		//Создаем текст из раздела данные.
		$pr_data = '
		 [upc] => '.$data['upc'].PHP_EOL.
    '[ean] => '.$data['ean'].PHP_EOL.
    '[jan] => '.$data['jan'].PHP_EOL.
    '[isbn] => '.$data['isbn'].PHP_EOL.
    '[mpn] => '.$data['mpn'].PHP_EOL.
    'Расположение [location] => '.$data['location'].PHP_EOL.
    'Минимальное кол-во [minimum] => '.$data['minimum'].PHP_EOL.
    'Вычитать со склада [subtract] => '.$data['subtract'].PHP_EOL.
    'Размеры (Длина) [length] => '.$data['length'].PHP_EOL.
    'Размеры (Ширина) [width] => '.$data['width'].PHP_EOL.
    'Размеры (Высота) [height] => '.$data['height'].PHP_EOL.
    'Единица длины [length_class_id] => '.$data['length_class_id'].PHP_EOL.
    'Вес товара [weight] => '.$data['weight'].PHP_EOL.
    'Единица веса [weight_class_id] => '.$data['weight_class_id'].PHP_EOL.
    'Статус товара [status] => '.$data['status'].PHP_EOL.
    'Порядок сортировки [sort_order] => '.$data['sort_order'].PHP_EOL.
    'Теги товара => '.$data['tags'].PHP_EOL;

    //отдельно в отладку добавим данные об макетах если они включены.
    if(!empty($data['layout_pr'])){ $pr_data .= 'Макет товара id = '.$data['layout_pr'].PHP_EOL;}
    if(!empty($data['layout_cat'])){ $pr_data .= 'Макет для категорий id = '.$data['layout_cat'].PHP_EOL;}

		$text['pr']['pr_data'] = ['name'=>'Дополнительные данные', 'row'=>16, 'text'=>trim($pr_data)];

		//создаем отладочную информацию для допусков страницы
		$pr_permit = '';
		if(!empty($data['permit_grans_text'])){

			foreach($data['permit_grans'] as $key => $permit){

				if($key <= 3 && empty($permit['permit'])){
					$pr_permit = $permit['log'].PHP_EOL;
				}

			}

		}
		$text['pr']['pr_permit_html'] = ['name'=>'Допуск страницы к Парсингу', 'row'=>5, 'text'=>$pr_permit];
    
	}
	#$this->wtfarrey($data);
	return $text;
}

//Преобразования фото для пред просмотра.
public function madeImgShow($image, $dn_id){
	//обьявляем путь.
	$dir_image = 'image/catalog/';
	$path_check = DIR_IMAGE.'catalog/SPshow';
	$data_imgs = [];
	$browser = $this->getBrowserToCurl($dn_id);

	// Создаем директорию если пользовател ее затер.
	if(!is_dir($path_check)){ mkdir($path_check, 0755, true); }

	array_walk($image, function(&$v){ $v = $this->urlEncoding($v);});

	//делем массив для много поточности
	$imgs_chunk = array_chunk($image, 5);
	#$this->wtfarrey($imgs_chunk);

	foreach($imgs_chunk as $chunk){
		$data_img = $this->curlImg($chunk, $browser, $dn_id);
		
		foreach($data_img as $key => $img){
			$old_name = md5(basename($img['url'].$key)).'.jpg';
			$path_img = $dir_image.'SPshow/'.$old_name;
			file_put_contents('../'.$path_img, $img['img']);
			$data_imgs[] = '../'.$path_img;
		}

	}

	#$this->wtfarrey($data_img);
	return $data_imgs;
}
############################################################################################
############################################################################################
#						Фунции отвечающие за просмотр страницы Менеджер ссылок
############################################################################################
############################################################################################

//обшая фунция по получению ссылок для парсинга.
public function getUrlToPars($dn_id, $list="", $http_code=""){
	#$this->fastscr();
	//определяем о каком списке товаров идет речь.
	$where = " WHERE dn_id=".(int)$dn_id;
	//проверяем выбор списка ссылок.
	if($list != 0){
		$where .= " AND list =".(int)$list;
	}
	//проверяем выбранный список по ошибках
	if(!empty($http_code)){
		$where .= " AND error =".(int)$http_code;
	}

	#$this->wtfarrey($where);

	//получаем ссылки для обработки.
	$links = $this->db->query("SELECT link FROM ". DB_PREFIX ."pars_link".$where." AND scan=1 ORDER BY id ASC LIMIT 0,5");
	$links = $links->rows;

	//получаем все не обработанные ссылки.
	$queue = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_link".$where." AND scan=1");
  $queue = $queue->row['count'];

  //получаем все ссылки которые есть 
  $total = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_link".$where);
  $total = $total->row['count'];

  //получаем полный список ссылок.
  $full = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_link WHERE dn_id=".(int)$dn_id);
  $full = $full->row['count'];

  //формируем массив ответа.
  $data = ['links' => $links, 'queue' => $queue, 'total' => $total, 'full' => $full];
  #$this->wtfarrey($data);

  return $data;
}

//обшая фунция по получению ссылок для XML страницы
public function getUrlSenToPars($dn_id){

	//определяем о каком списке товаров идет речь.
	$where = " WHERE dn_id=".(int)$dn_id;
	//проверяем выбор списка ссылок.

	#$this->wtfarrey($where);

	//получаем ссылки для обработки.
	$links = $this->db->query("SELECT link FROM ". DB_PREFIX ."pars_sen_link".$where." AND scan=1 ORDER BY id ASC LIMIT 0,5");
	$links = $links->rows;

	//получаем все не обработанные ссылки.
	$queue = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_sen_link".$where." AND scan=1");
  $queue = $queue->row['count'];

  //получаем все ссылки которые есть 
  $total = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_sen_link".$where);
  $total = $total->row['count'];

  //получаем полный список ссылок.
  $full = $this->db->query("SELECT COUNT(*) as count FROM ". DB_PREFIX ."pars_sen_link WHERE dn_id=".(int)$dn_id);
  $full = $full->row['count'];

  //формируем массив ответа.
  $data = ['links' => $links, 'queue' => $queue, 'total' => $total, 'full' => $full];
  #$this->wtfarrey($data);

  return $data;
}

//проверяет сушествует ли прайс лист для скачивания на хостинге
public function urlCheckPriceListToPage($dn_id){

	$path = DIR_APPLICATION.'/uploads/urls_list_'.$dn_id.'.csv';
	if(file_exists($path)){ 
		return true; 
	}else{ 
		return false; 
	}
}

//Удаление прайса с ссылками.
public function urlDelPriceListUrl($dn_id){

	$path = DIR_APPLICATION.'/uploads/urls_list_'.$dn_id.'.csv';

	//Проверяем есть ли такой прайс.
	if (file_exists($path)) {
		unlink($path);
	}
}

//получаем список ссылок
public function getAllLinkList($dn_id){
	//получаем перечень списков
	$lists = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_link_list WHERE `dn_id`=".(int)$dn_id);
	$list_names = $lists->rows;
	return $list_names;
}

//получаем список ссылок
public function getAllLinkError($dn_id){
	//получаем списко ошибок.
	$list_errors = [];
	$errors = $this->db->query("SELECT DISTINCT error FROM ".DB_PREFIX."pars_link WHERE `dn_id`=".(int)$dn_id);
	$data['list_errors'] = [];
	foreach($errors->rows as $value){
		if($value['error'] != 0){ $list_errors[] = $value['error']; }
	}

	$list_errors = array_diff($list_errors, array(''));

	return $list_errors;
}

//сохраняем выбор списка для парсинга
public function saveLinkListAndError($data, $dn_id){
	#$this->wtfarrey($data);
	$this->db->query("UPDATE ".DB_PREFIX."pars_setting SET 
		link_list ='".$this->db->escape($data['link_list'])."', 
		link_error ='".$this->db->escape($data['link_error'])."' WHERE dn_id =".(int)$dn_id);
}

//рестарт ссылок на страницах парсинга.
public function restLinkToPars($data, $dn_id){

	//определяем о каком списке товаров идет речь.
	$where = " WHERE dn_id=".(int)$dn_id;
	//проверяем выбор списка ссылок.
	if($data['link_list'] != 0){
		$where .= " AND list =".(int)$data['link_list'];
	}
	//проверяем выбранный список по ошибках
	if(!empty($data['link_error'])){
		$where .= " AND error =".(int)$data['link_error'];
	}

	//выполняем запрос на рестарт ссылок.
	$this->db->query("UPDATE `".DB_PREFIX."pars_link` SET `scan`=1".$where);
}

//рестарт ссылок на страницах парсинга.
public function restSenLinkToPars($dn_id){
	//выполняем запрос на рестарт ссылок.
	$this->db->query("UPDATE `".DB_PREFIX."pars_sen_link` SET `scan`=1 WHERE dn_id=".(int)$dn_id);
}

//Добавление нового списка
public function addNewLinkList($data, $dn_id){
	
	//проверяем есть ли такой список
	$check = $this->db->query("SELECT id FROM ".DB_PREFIX."pars_link_list WHERE dn_id=".$dn_id." AND name='".$this->db->escape($data['list_name_new'])."'");
	if($check->num_rows){
		$this->session->data['error'] = " Список с таким названием уже существует. Выберите другое название для списка.";
	}else{
		$this->db->query("INSERT INTO ".DB_PREFIX."pars_link_list SET dn_id=".$dn_id.", name='".$this->db->escape($data['list_name_new'])."'");
		$this->session->data['success'] = " Новый список успешно создан.";
	}
}	

//удаление списка
public function delLinkList($data, $dn_id){
	$this->db->query("UPDATE ".DB_PREFIX."pars_link SET list=0 WHERE list=".(int)$data['list_del']);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_link_list` WHERE id=".(int)$data['list_del']);
	$this->session->data['success'] = " Список успешно удален. Если в списке были ссылки они перенесены в общий список.";
}

//Фунция фильтрации ссылок. Возврашает массив с текстом запроса WHERE и списком id по этому запросу.
public function urlsGetId($data, $dn_id){

	//Составляем хвостик WHERE 
	$where = ' WHERE dn_id ='.(int)$dn_id;

	//если пришел массив с id списков
	if(!empty($data['list_name'])){
		$where .= " AND list in (".implode(',', $data['list_name']).")";
	}

	//если пришел запрос на фильтрацию по типу ошибки.
	if(!empty($data['list_error'])){
		$where .= " AND error in (".implode(',', $data['list_error']).")";
	}

	//пришел запрос на фильтрацию ссылок по состаюнию париснга пользователем
	if($data['link_scan'] != 'all'){
		$where .= " AND scan = ".(int)$data['link_scan'];
	}

	//пришел запрос на фильтрацию ссылок по состаюнию париснга кроном
	if($data['link_scan_cron'] != 'all'){
		$where .= " AND scan_cron = ".(int)$data['link_scan_cron'];
	}

	if(!empty($data['filters'])){
		//перебераем все фильтры
		foreach ($data['filters'] as $filter) {

			//для начала проверяем есть ли значение в этой фильтре
			if(empty($filter['value']) && $filter['value'] != '0'){
				$filter['value'] = '';
			}
				
			//определяем поле в таблице.
			if($filter['take_filtr'] == '0'){
				continue;
			}elseif($filter['take_filtr'] == 'string' || $filter['take_filtr'] == 'url_id'){ 

				//Если фильтра в базе данных делается то все в этом блоке. Выбираем метод фильтрации.
				if($filter['take_filtr'] == 'string'){
					$table = DB_PREFIX.'pars_link.link';
				}else{
					$table = DB_PREFIX.'pars_link.id';
				}

				$pos = $this->toolFilterPosition($filter['position'], $filter['style']);

				//делим значение на массив если ли это многомерное значение. Заодно удаляем пустые массивы
				$filter['value'] = explode('|', $filter['value']);
				foreach($filter['value'] as $key_value => $value){

					$value = $this->db->escape($value);
					if($key_value == 0){ 
						$where .= ' AND ('.$table.str_replace('{data}', $value, $pos); 
					}else{
						$where .= ' OR '.$table.str_replace('{data}', $value, $pos);
					}
				}

				$where .= ')';

			}elseif($filter['take_filtr'] == 'date_cach'){

				$table = DB_PREFIX.'pars_link.key_md5';

				$cache_dir = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/*.txt';
				$files = glob($cache_dir);
				$url_cached_date = [];
				$and = ' AND (';
				$bracket = '';
				foreach($files as $file){
					
					$date = date("Y-m-d H:i:s", filectime($file));
					$file = preg_replace('#(.*?)/cache_page/(.*?)/#', '', $file);
					$file = str_replace('.txt', '', $file);

					//если совпадает введенная дата, и дата кеширования.
					if( empty($filter['style']) && stripos($date, $filter['value']) !== false){
						$where .= $and.$table."='".$file."'";
						$and = ' OR ';
						$bracket = ')';
					}elseif( !empty($filter['style']) && stripos($date, $filter['value']) === false){
						$where .= $and.$table."='".$file."'";
						$and = ' OR ';
						$bracket = ')';
					}

				}

				$where .= $bracket;# Добавляется скобочка если было совпадение, маразм но что поделаеш :( 
			}
		}
	}

	#$this->wtfarrey($where);

	$urls_id = $this->db->query("SELECT id FROM ".DB_PREFIX."pars_link".$where)->rows;

	$list_id = '';
	foreach($urls_id as $key => $url){
		if($key){ $list_id .= ','.$url['id']; } else { $list_id = $url['id'];}
	}

	if(empty($list_id)){ $list_id = 0;}

	return $list_id;
}

//Фильтрация ссылок
public function urlFilterToPage($data, $dn_id){
	//возврашаемый массив
	$answ = [];

	//определяем колво товаров на страницу
	$limit_start = ($data['page'] * $data['page_count']) - $data['page_count'];
	$limit_stop = $limit_start + $data['page_count'];
	$limit = ' LIMIT '.$limit_start.','.$limit_stop;

	//получаем список id товаров которые попадают под фильтры
	$urls_id = $this->urlsGetId($data, $dn_id);

	//получаем ссылки из базы
	$urls = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_link WHERE id in (".$urls_id.") ORDER BY id".$limit)->rows;

	//Получаем списко списков ссылок
	$link_list = $this->db->query("SELECT id, name FROM ".DB_PREFIX."pars_link_list WHERE dn_id = ".$dn_id);
	$link_list = $link_list->rows;
	$link_list = array_column($link_list, 'name', 'id');
	
	#Проверяем есть ли такой файл кеша.
	foreach($urls as &$value){
		
		//присваеваем название списков ссылкам.			
		if(!empty($link_list[$value['list']])){
			$value['list'] = $link_list[$value['list']];
		}else{
			$value['list'] = 'Общий';
		}

		//работа с ошибками.
		if(empty($value['error'])){
			$value['error'] = '';
		}elseif($value['error'] > 0){
			$value['error'] = '<b class="text-danger">'.$value['error'].'</b>';
		}

		//Информация по кешу.
		$file = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/'.$value['key_md5'].'.txt';
		$value['cached_time'] = 'not cached';
		if(file_exists($file)){
			
			$info = stat($file);
			$value['cached_time'] = date("Y-m-d H:i:s", $info['mtime']);
			$value['size'] = $info['size'];
			#$this->wtfarrey($value);
		}
	}
	//Передаем данные на ответ.
	$answ['urls'] = $urls;
	$total_urls = $this->db->query("SELECT COUNT(*) as count FROM ".DB_PREFIX."pars_link WHERE id in (".$urls_id.")");
	$answ['total'] = $total_urls->row['count'];

	#$this->wtfarrey($data);
	#$this->wtfarrey($urls);

	return $answ;
}

//контроллер действий.
public function urlControlerFunction($data, $dn_id){

	#$this->wtfarrey($data);
	//проверяем выбрано ли действие.
	if(!empty($data['do_action'])){
		
		//определяем какая фунция была выбрана.
		if($data['do_action'] == 'url_change_list'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlActionChangeList($data, $urls_id, $dn_id);

		}elseif($data['do_action'] == 'url_del_error'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlActionDelErrors($data, $urls_id, $dn_id);

		}elseif($data['do_action'] == 'url_del_cache'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlActionDelCache($data, $urls_id, $dn_id);

		}elseif($data['do_action'] == 'url_replace'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlActionFindReplace($data, $urls_id, $dn_id);

		}elseif($data['do_action'] == 'url_del'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlActionDeletUrls($data, $urls_id, $dn_id);

		}elseif($data['do_action'] == 'url_create_price_all'){

			$this->urlCreatePriceAll($dn_id);

		}elseif($data['do_action'] == 'url_create_price_filter'){

			$urls_id = $this->urlsGetId($data, $dn_id);
			$this->urlCreatePriceFilter($data, $urls_id, $dn_id);

		}
		
		#$this->wtfarrey($urls_id);
		exit(json_encode("Действие выполнено!"));
	}
	
}

//Фунци изменения списка в ссылке
public function urlActionChangeList($data, $urls_id, $dn_id){
	$this->db->query("UPDATE ".DB_PREFIX."pars_link SET list = ".(int)$data['new_list']." WHERE id in (".$urls_id.")");
}

//Фунция удаления ошибок с ссылок.
public function urlActionDelErrors($data, $urls_id, $dn_id){

	//проверяем какие ошибки сбрасывать.
	if($data['del_errors'] == 'all'){
		$where_error = '';	
	}else{
		$where_error = " AND error =".$this->db->escape($data['del_errors']);
	}
	//Выполняем запрос.
	$this->db->query("UPDATE ".DB_PREFIX."pars_link SET error = '' WHERE id in (".$urls_id.")".$where_error);
}

//Фунция очистки кеша.
public function urlActionDelCache($data, $urls_id, $dn_id){
	
	//проверяем какой кеш чистить.
	if($data['which_cache'] == 'url_get'){
		//Получаем спсико ссылок кеш которых нужно почистить.
		$urls = $this->db->query("SELECT key_md5 FROM ".DB_PREFIX."pars_link WHERE id in (".$urls_id.")");
		$urls = $urls->rows;
		
		//если массив не пустой приступаем к очистки кеша. 
		if(!empty($urls)){

			//перебираем масив и удаляем кеш.
			foreach($urls as $url){
				$path = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/'.$url['key_md5'].'.txt';
				@unlink($path);
			}			
		}
	}elseif($data['which_cache'] == 'url_all'){
		$this->urlDelAllCache($dn_id);
	}
}

//фунция поиск замена в ссылках.
public function urlActionFindReplace($data, $urls_id, $dn_id){

	//получаем список ссылок с которыми будем работать.
	$urls = $this->db->query("SELECT id, link FROM ".DB_PREFIX."pars_link WHERE id in (".$urls_id.")");
	$urls = $urls->rows;

	//если ссылки есть по нужным фильтрам тогда приступаем к работе.
	if(!empty($urls)){

		//преобразуем правила. 
		$data['rules'] = $this->parseRulesToReplace($data);

		//перебираем ссылки.
		foreach($urls as $url){
			//Для сокрашение запросов в базу данных
			$standard = $url['link'];

			//если есть правила для поиск замеены
			if(!empty($data['rules'])){
				//перебираем правила поиск замены
				foreach($data['rules'] as $rule){

	  			if(isset($rule[0]) && isset($rule[1])){

	  				$rule[0] = $this->pregRegLeft($rule[0]);
	  				$rule[1] = $this->pregRegRight($rule[1]);
	  				$url['link'] = preg_replace($rule[0], $rule[1], html_entity_decode($url['link']));
	  			}
	  		}
  		}
  		//если есть что добавить вконец добавляем
  		if(isset($data['url_end'])){
  			$url['link'] = $url['link'].$data['url_end'];
  		}

  		//проверяем в ссылке что то изменилось.
  		if($standard != $url['link']){
	  		//проверяем что делать обновить текушие ссылки. Или добавить новые.
	  		//0 - обновить | 1 - Добавить как новую.
	  		if(empty($data['what_do'])){
	  			
	  			//очишаем
	  			$url['link'] = $this->ClearLink($url['link']);
	  			//Зашита от дурака, если кто то догадается сделать поискз заменга протакола.
	  			if(preg_match('#(^http://)|(^https://)#', $url['link'])){
		  			//обновляем ссылку
		  			$this->db->query("UPDATE IGNORE ".DB_PREFIX."pars_link SET 
		  				link ='".$this->db->escape($url['link'])."',
		  				key_md5 = '".md5($dn_id.$url['link'])."' 
		  				WHERE id =".$url['id']);
	  			}
	  		}else{
	  			//Зашита от дурака, если кто то догадается сделать поискз заменга протакола.
	  			if(preg_match('#(^http://)|(^https://)#', $url['link'])){
	  				$this->AddParsLink($url['link'], $dn_id);
	  			}
	  			
	  		}
  		}
		}
	}
}

//Фунция по удалению ссылок
public function urlActionDeletUrls($data, $urls_id, $dn_id){

	//получаем списко файлов которые нужно удалить.
	$files = $this->db->query("SELECT key_md5 FROM `".DB_PREFIX."pars_link` WHERE id in (".$urls_id.")");
	#$this->wtfarrey($files);
	$cache_dir = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/';
	foreach($files->rows as $file){
		@unlink($cache_dir.$file['key_md5'].'.txt');
	}
	
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_link` WHERE id in (".$urls_id.")");
}

//Вспомагательная фунция удаления файлов кеша.
public function urlDelAllCache($dn_id){
	//Адерсс директории с фалами.
	$cache_dir = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/*.txt';
	$files = glob($cache_dir);
	//производим удаление
	array_map('unlink', $files);

}

//Записываем файл все ссылки этого проекта.
public function urlCreatePriceAll($dn_id){

	//путь к файлу с сылками.
	$path = DIR_APPLICATION.'uploads/urls_list_'.$dn_id.'.csv';
	
	//запускаем цикл который будет доставать по очереди ссылки и записывать в файл.
	$i = 1;
	while($i){

		if($i == 1){ 
			$limit = " 0,5000"; 
		}else{
			$limit = " ".(($i-1)*5000).", 5000";
		}

		//Запрашиваем ссылки
		$urls = $this->db->query("SELECT link FROM ".DB_PREFIX."pars_link WHERE dn_id = ".$dn_id." LIMIT".$limit)->rows;

		//Записываем ссылки в файл если они не закончились. 
		if(!empty($urls)){ 
		
			$i++; #сразу выставляем флаг на следуюшею итерацию

			//формируем текст для записи в файл.
			$text = '';
			foreach($urls as $key => $url){
				if($key==0){ $text = $url['link'].PHP_EOL; }else{ $text .= $url['link'].PHP_EOL;}
			}

			//дозаписываем в файл ссылки.
			file_put_contents($path, $text, FILE_APPEND);

		}else{ 
			$i = 0; 
		}
	}
}

//Записываем файл отфильтрованные ссылки этого проекта.
public function urlCreatePriceFilter($data, $urls_id, $dn_id){

	//путь к файлу с сылками.
	$path = DIR_APPLICATION.'uploads/urls_list_'.$dn_id.'.csv';
	
	//запускаем цикл который будет доставать по очереди ссылки и записывать в файл.
	$i = 1;
	while($i){

		if($i == 1){ 
			$limit = " 0,5000"; 
		}else{
			$limit = " ".(($i-1)*5000).", 5000";
		}

		//Запрашиваем ссылки
		$urls = $this->db->query("SELECT link FROM ".DB_PREFIX."pars_link WHERE id in (".$urls_id.") LIMIT".$limit)->rows;

		//Записываем ссылки в файл если они не закончились. 
		if(!empty($urls)){ 
		
			$i++; #сразу выставляем флаг на следуюшею итерацию

			//формируем текст для записи в файл.
			$text = '';
			foreach($urls as $key => $url){
				if($key==0){ $text = $url['link'].PHP_EOL; }else{ $text .= $url['link'].PHP_EOL;}
			}

			//дозаписываем в файл ссылки.
			file_put_contents($path, $text, FILE_APPEND);

		}else{ 
			$i = 0; 
		}
	}
}


//Сохраняем настройки
public function saveCacheForm($data, $dn_id){
	#$this->wtfarrey($data);
	$this->db->query("UPDATE ". DB_PREFIX ."pars_setting  SET 
	  	pars_pause='".$this->db->escape($data['pars_pause'])."', 
	  	thread='".$this->db->escape($data['thread'])."' WHERE dn_id=".(int)$dn_id);
}

//Контролдлер парсинга в кеш
public function controlParsToCache($dn_id){
  $setting = $this->getSetting($dn_id);

  //Получаем списк неспарсенных ссылок.
	$pars_url = $this->getUrlToPars($dn_id, $setting['link_list'], $setting['link_error']);

  #Если ссылок нету завершаем работу модуля.
  if(empty($pars_url['links'])){

    $answ['progress'] = 100;
    $answ['clink'] = ['link_scan_count' => $pars_url['total'], 'link_count' => $pars_url['queue'],];
    $this->answjs('finish','Парсинг закончился, ссылок больше нет﻿',$answ);

  }else{

  	//собираем массив ссылок для мульти запроса.
  	$urls = [];
  	foreach($pars_url['links'] as $key => $url){
  		if($key < $setting['thread']) {$urls[] = $url['link']; } else { break; }
  	}

  	$browser = $this->getBrowserToCurl($dn_id);
  	$browser['cache_page'] = 2;

  	$datas = $this->multiCurl($urls, $dn_id, $browser);

  	//Далее разбираем данные из мульти курла и делаем все нужные записи.
  	foreach($datas as $link => $data){
  		
  		//производим зяпись лога курл, и паролельно проверяем нужно ли делать дальнейшую работу.
  		$curl_error = $this->sentLogMultiCurl($data ,$dn_id);
  
			#помечаем ссылку как отсканированная
    	$this->db->query("UPDATE ". DB_PREFIX ."pars_link SET scan=0, error='".$curl_error['http_code']."' WHERE link='".$data['url']."' AND dn_id=".$dn_id);

  		//если пришла ошибка заканчиваем эту итерацию и переходим к следующей.
  		if($curl_error['error']){ 
  			continue;
  		}
  	}
    
    #считаем процент для прогрес бара
    $scan = ($pars_url['total'] - $pars_url['queue']);
    $progress = $scan/($pars_url['total']/100);
    $answ['progress'] = $progress;
    $answ['clink'] = [
                       'link_scan_count' => $scan,
                       'link_count' => $pars_url['queue'],
                      ];
    #пауза парсинга
    $this->timeSleep($setting['pars_pause']);
    $this->answjs('go','Производится парсинг',$answ);
  }
}

############################################################################################
############################################################################################
#						TOOLS
############################################################################################
############################################################################################

//сохранение шаблона редактора товара
public function toolAddPattern($data, $dn_id){
	#$this->wtfarrey($data);
	if($data['do_tools'] == 'webp_convert'){
		$this->session->data['error'] = ' Запрещено создать шаблон действия с преобразованием WEBP в JPG';
		return false;
	}
	$pattern_name = $data['pattern_name'];
	$data = json_encode($data);
	//записываем настройки в базу данных.
	$this->db->query("INSERT INTO ".DB_PREFIX."pars_tools_pattern 
										SET dn_id =".(int)$dn_id.", 
										name = '".$this->db->escape($pattern_name)."',
										setting = '".$this->db->escape($data)."'");
}

//обновление шаьлона
public function toolUpdatePattern($data, $dn_id){
	if($data['do_tools'] == 'webp_convert'){
		$this->session->data['error'] = ' Запрещено создать шаблон действия с преобразованием WEBP в JPG';
		return false;
	}
	$pattern_name = $data['pattern_name'];
	$pattern_id = (int)$data['pattern_take'];
	$data = json_encode($data);
	//записываем настройки в базу данных.
	$this->db->query("UPDATE ".DB_PREFIX."pars_tools_pattern 
										SET dn_id =".(int)$dn_id.", 
										name = '".$this->db->escape($pattern_name)."',
										setting = '".$this->db->escape($data)."' WHERE id =".$pattern_id);
}

//удаление шаблона
public function toolDelPattern($pt_id){
	#Запрос на удаление
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_tools_pattern` WHERE id = ".(int)$pt_id);
}

//получить список всех шаблонов, данного проекта.
public function toolGetAllPatterns($dn_id){
	$patterns = [];
	//получаем все шаблоны
	$data = $this->db->query("SELECT id, name FROM ".DB_PREFIX."pars_tools_pattern WHERE dn_id =".(int)$dn_id);
	if($data->num_rows != 0){

		$patterns = $data->rows;

	}
	#$this->wtfarrey($patterns);
	return $patterns;
}

//Получение данных для страницы.
public function toolGetPatternToPage($pt_id){
	$data = [];

	//получаем данные о патерне
	$temp = $this->toolGetPattern($pt_id);
	
	if(!empty($temp['setting'])){
		$data = $temp['setting'];
	}

	//получаем id последнего ключа для twig
	if(!empty($data['filters'])){
	  if (!is_array($data['filters']) || empty($data['filters'])) {
	    $data['key_f_last'] = NULL;
	  }else{
	    $data['key_f_last'] = array_keys($data['filters'])[count($data['filters'])-1];
	  }
	}else{
		$data['key_f_last'] = 0;
	}

	if(empty($data['cats'])){ $data['cats'] = [];}

	#$this->wtfarrey($data);
	return $data;
}

//Получение данных о патерне.
public function toolGetPattern($pt_id){
	$data = [];

	if(!empty($pt_id)){
		$pattern = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_tools_pattern WHERE id = ".(int)$pt_id);
		$pattern = $pattern->row;

		//проверяем что бы настройки были не пустые.
		if(!empty($pattern['setting'])){
			$pattern['setting'] = htmlspecialchars_decode($pattern['setting']);
			$pattern['setting'] = json_decode($pattern['setting'], true);
		}
	}

	if(empty($pattern['setting']['cats'])){ $pattern['setting']['cats'] = [];}
	$data = $pattern;

	#$this->wtfarrey($data);
	return $data;
}

//Преобразования фильтров, для sql запроса
public function toolFilterPosition($pos, $style){

	//перобразуем сушности html в теги
	$pos = html_entity_decode($pos);
	
	if($pos == '={data}' && $style == 1){
	
		$pos = " != '{data}'";
	
	}elseif($pos == '={data}' && $style == 0){

		$pos = " = '{data}'";

	}elseif($pos == '%{data}' && $style == 1){

		$pos = " NOT LIKE '%{data}'";

	}elseif($pos == '%{data}' && $style == 0){

		$pos = " LIKE '%{data}'";

	}elseif($pos == '{data}%' && $style == 1){

		$pos = " NOT LIKE '{data}%'";

	}elseif($pos == '{data}%' && $style == 0){

		$pos = " LIKE '{data}%'";

	}elseif($pos == '%{data}%' && $style == 1){

		$pos = " NOT LIKE '%{data}%'";

	}elseif($pos == '%{data}%' && $style == 0){

		$pos = " LIKE '%{data}%'";

	}elseif($pos == '>={data}' && $style == 1){

		$pos = " <= '{data}'";

	}elseif($pos == '>={data}' && $style == 0){

		$pos = " >= '{data}'";

	}elseif($pos == '<={data}' && $style == 1){

		$pos = " >= '{data}'";

	}elseif($pos == '<={data}' && $style == 0){

		$pos = " <= '{data}'";

	}

	#$this->wtfarrey($pos);
	return $pos;
}

// Костыльная переделка пагинации под мои нужды
public function toolRenderPage($html){
	$html = str_replace('class="pagination"', "class='pagination' id='del_ul'", $html);

	//позорише мое, если читаете эту фунцию простите меня за такой подход.
	$html = preg_replace('#<a href="(.*?)">(|\&lt;)</a>#', '<button type="button" class="btn btn btn-sm" onclick=\'controlFilter(1)\'>|&lt;</button> ', $html);
	$html = preg_replace('#<a href="(.*?)">1</a>#', '<button type="button" class="btn btn btn-sm" onclick=\'controlFilter(1)\'>1</button> ', $html);
	
	$html = preg_replace('#<a href="(.*?)page=#', '<button type="button" class="btn btn btn-sm" onclick="controlFilter(', $html);
	$html = str_replace('">', ')">', $html);
	$html = str_replace('</a>', '</button> ', $html);
	$html = str_replace('class="active"', '', $html);
	
	$html = str_replace('<span>', '<button type="button" name="page" class="btn btn-primary btn-sm">', $html);
	$html = str_replace('</span>', '</button> ', $html);

	#$this->wtfarrey($html);
	return $html;
}

//Ресайз фото для вывода в таблице.
public function toolResizeImg($img){
	$image = $img;
	if (is_file(DIR_IMAGE . $img)) {
		$image = $this->model_tool_image->resize($img, 40, 40);
	} else {
		$image = $this->model_tool_image->resize(DIR_IMAGE .'no_image.png', 40, 40);
	}

	return $image;
}

//получения списка категорий для страницы тулс
public function toolMadeCategoryToPage(){
	$data = $this->madeCatTree(1);
	$categorys = [];
	if(!empty($data)){
		foreach($data as $key => $value){
			$categorys[] = ['id' =>$key, 'name'=>$value];
		}
	}
	
	#$this->wtfarrey($categorys);
	return $categorys;
}

//Изменить цену
public function toolChangePrice($data, $prs_id, $dn_id){

	$data['value'] = htmlspecialchars_decode($data['value']);
	$step = 0.01;
	$site = '%';

	//определяем условия окргуления.
	preg_match('#^\{(.*?)\}#', $data['value'], $rounds);
	//Вырезаем алгоритм округления из общего правила, и приводим правило в нужный формат
	if(!empty($rounds[0])){

		//вырезаем кусок ненужный для наценки.
		$data['value'] = preg_replace('#^\{(.*?)\}#','',$data['value']);

		//Приводим форматируем данные веденные пользователем. Да да, ведь вы все равно пишите лишние пробелы и запятые.
		$rounds[1] = trim(str_replace(' ','',str_replace(',','.',$rounds[1])));

		//Проверяем правильность ввода праила окргурления.
		if(preg_match('#^[0-9]+[.]*[0-9]*[|]*[<>%]*$#',$rounds[1])){
			//делим правило на значение кратное которому округляем. И на условие округления

			$round = explode('|', $rounds[1]);
			$step = (float)$round[0];

			//Указываем условие округления
			if(!empty($round[1])){
				$site = $round[1];
			}
		}
	}

	//приводим значение числа.
	$data['value'] = (float)trim(str_replace(',', '.', $data['value']));

	if($data['operator'] == '='){

		//Округляем. По умолчанию до двух нулей после запятой.
		$price = '';
		if($site == ">" && $step != 0){
			$price = 'CEILING( '.$data['value'].'/'.$step.' )*'.$step;
		}elseif($site == '<' && $step != 0){
			$price = 'FLOOR( '.$data['value'].'/'.$step.' )*'.$step;
		}else{
			$price = 'ROUND( '.$data['value'].'/'.$step.' )*'.$step;
		}

	}else{

		//Округляем. По умолчанию до двух нулей после запятой.
		$price = '';
		if($site == ">" && $step != 0){
			$price = 'CEILING( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}elseif($site == '<' && $step != 0){
			$price = 'FLOOR( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}else{
			$price = 'ROUND( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}

	}

	$sql = "UPDATE `".DB_PREFIX."product` SET price = ".$price." WHERE product_id IN (".$prs_id.")";
	$this->db->query($sql);
}

//Изменить колво
public function toolChangeQuant($data, $prs_id, $dn_id){
	//приводим значение числа.
	$data['value'] = (int)trim(str_replace(',', '.', $data['value']));
	$set = '';
	if($data['operator'] == '='){
		$sql = "UPDATE `".DB_PREFIX."product` SET quantity = ".$data['value']." WHERE product_id IN (".$prs_id.")";
	}else{
		$sql = "UPDATE `".DB_PREFIX."product` SET quantity = quantity ".$data['operator'].$data['value']." WHERE product_id IN (".$prs_id.")";
	}

	$this->db->query($sql);
}

//Изменить статус в товарах
public function toolChangeStatus($data, $prs_id, $dn_id){
	//приводим значение числа.
	$data['value'] = (int)$data['value'];
	$sql = "UPDATE `".DB_PREFIX."product`	SET	status = ".$data['value']." WHERE product_id IN (".$prs_id.")";
	#$this->wtfarrey($sql);
	$this->db->query($sql);
}

//Изменение статуса товара при нулевом остатке
public function toolChangeStockStatus($data, $prs_id, $dn_id){
	$data['value'] = (int)$data['value'];
	//делаем действия только в том случаи если сток статус был выбран
	if(!empty($data['value'])){
		$sql = "UPDATE `".DB_PREFIX."product`	SET	stock_status_id = ".$data['value']." WHERE product_id IN (".$prs_id.")";
		#$this->wtfarrey($sql);
		$this->db->query($sql);
	}
}

//Добавляем товар в дополнительные категории.
public function toolAddCatsToProducts($data, $prs_id, $dn_id){

	//для начала проверяем что бы под фильтры папал хоть один товар
	if(!empty($prs_id)){
		
		//преобразуем строку с id товаров в массив.
		$pr_id_arr = explode(',', $prs_id);

		//проверяем что бы пользователь не забыл передать списко категорий.
		if(!empty($data['new_cats'])){

			//перебераем массив категорий
			foreach($data['new_cats'] as $cat){

				//переберираем массив товаров
				foreach($pr_id_arr as $product_id){
					$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."product_to_category` SET product_id = ".$product_id.", category_id = ".$cat);
				}

			}

		}
		
	}	
}

//Заменяем все категории в товаре.
public function toolChangeCatsToProducts($data, $prs_id, $dn_id){

	//для начала проверяем что бы под фильтры папал хоть один товар
	if(!empty($prs_id)){
		
		//преобразуем строку с id товаров в массив.
		$pr_id_arr = explode(',', $prs_id);
		$setting = $this->getSetting($dn_id);

		// Удаляем все записи категорий в товаре.
		$this->db->query("DELETE FROM `".DB_PREFIX."product_to_category` WHERE `product_id` IN (".$prs_id.")");

		//проверяем какая версия движка стоит. И указываем главную категорию
		if( !empty($data['main_cat']) && ($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3') ){

			//Удаляем из массива с новыми категориями категорию равную главной.
			$data['main_cat'] = (int)$data['main_cat'];
			unset($data['new_cats'][$data['main_cat']]);

			//записываем в товар новую главную категорию
			foreach($pr_id_arr as $product_id){
				$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."product_to_category` SET product_id = ".$product_id.", category_id = ".$data['main_cat'].", main_category = 1");
			}

		}

		//проверяем что бы пользователь не забыл передать списко категорий.
		if(!empty($data['new_cats'])){

			//перебераем массив категорий
			foreach($data['new_cats'] as $cat){

				//переберираем массив товаров
				foreach($pr_id_arr as $product_id){
					$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."product_to_category` SET product_id = ".$product_id.", category_id = ".$cat);
				}

			}

		}
	}
}

//Заменяем все главную категорию в товаре.
public function toolChangeMainCatToProducts($data, $prs_id, $dn_id){
	
	//для начала проверяем что бы под фильтры папал хоть один товар
	if(!empty($prs_id)){
		
		//преобразуем строку с id товаров в массив.
		$pr_id_arr = explode(',', $prs_id);
		$setting = $this->getSetting($dn_id);

		//проверяем какая версия движка стоит. И указываем главную категорию
		if( !empty($data['main_cat']) && ($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3') ){

			$this->db->query("UPDATE `".DB_PREFIX."product_to_category` SET main_category = 0 WHERE `product_id` IN (".$prs_id.")");

			//Удаляем из массива с новыми категориями категорию равную главной.
			$data['main_cat'] = (int)$data['main_cat'];

			//записываем в товар новую главную категорию
			foreach($pr_id_arr as $product_id){
				
				$temp = $this->db->query("SELECT category_id FROM `".DB_PREFIX."product_to_category` WHERE product_id = ".$product_id." AND category_id = ".$data['main_cat'])->num_rows;
				#$this->wtfarrey($temp);
				if($temp){
					$this->db->query("UPDATE `".DB_PREFIX."product_to_category` SET main_category = 1 WHERE product_id = ".$product_id." AND category_id = ".$data['main_cat']);
				}else{
					$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."product_to_category` SET product_id = ".$product_id.", category_id = ".$data['main_cat'].", main_category = 1");
				}

			}

		}
	}
}

//присваеваем родительские категории товару или наоборот.
public function toolWorkWithParentCat($data, $prs_id, $dn_id){

	//Получаем язык админки для получения списка категорий
	$language_id = $this->getLangDef();
	
	//Получаем массив категорий в товаре.
	$cats_now = $this->db->query("SELECT * FROM `".DB_PREFIX."product_to_category` WHERE product_id IN (".$prs_id.") ORDER BY category_id")->rows;
	
	//проверяем есть ли товары с которыми нам нужно работать.
	if(!empty($cats_now)){

		//получаем весь список категорий в ммагазине
		$cats_sql = $this->db->query("SELECT c.category_id, c.parent_id FROM ". DB_PREFIX ."category c INNER JOIN ". DB_PREFIX ."category_description d ON c.category_id = d.category_id WHERE d.language_id =".(int)$language_id)->rows;
		$cats_sql = array_column($cats_sql, 'parent_id', 'category_id');
		
		//показывать во всех родительских категориях
		if($data['operator'] == 1){
			$cats_tmp = array_unique(array_column($cats_now, 'category_id'));

			//Создаем массив всех родителей. Адавая машина.
			$arrey_cats = [0=>[0=>0]];
			foreach($cats_tmp as $var){
				$arrey_cats[$var][] = $var;
				if(!empty($cats_sql[$var])){ 
					$arrey_cats[$var][] = $cats_sql[$var];
					$i = $cats_sql[$var];
					
					while($i){
						if(!empty($cats_sql[$i])){
							$arrey_cats[$var][] = $cats_sql[$i];
							$i = $cats_sql[$i];
						}else{
							$i = false;
						}
					}
				}
			}

			//запускаем адавую машину по простановке всех род категорий. Перебираем товары что попали под фильтр.
			foreach($cats_now as $pr){

				//запускаем проставление род категорий
				if(!empty($arrey_cats[$pr['category_id']])){
					foreach($arrey_cats[$pr['category_id']] as  $cat){
							$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_to_category SET
								product_id = '" . (int)$pr['product_id'] . "',
								category_id = '" . (int)$cat."'");
			
					}
				}
			}

		}elseif($data['operator'] == 2){

			$setting = $this->getSetting($dn_id);

			$cats = array_column($cats_now, 'category_id', 'product_id');

			foreach($cats as $pr_id => $cat_id){
				$this->db->query("DELETE FROM `".DB_PREFIX."product_to_category` WHERE `product_id` =".(int)$pr_id);

				//Если это только первая итерация. И это движок ocStor тогда мы записываем как главная категория.
				$vers_op = ", main_category = 1";
				if( $setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){ $vers_op = ", main_category = 1";}
				//Добавление товар в категорию Opencart
				$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET
					product_id = '" . (int)$pr_id . "',
					category_id = '" . (int)$cat_id."'".$vers_op);
			}

		}
	}
}


//изменить производителя в товаре. 
public function toolChangeManuf($data, $prs_id, $dn_id){
	$data['new_manuf'] = (int)$data['new_manuf'];

	//Выполняем обновление информации об производителе.
	if(!empty($data['new_manuf'])){
		$sql = "UPDATE `".DB_PREFIX."product`	SET	manufacturer_id = ".$data['new_manuf']." WHERE product_id IN (".$prs_id.")";
		$this->db->query($sql);
	}
}

//изменение акционных цен
public function toolChangeSpecPrice($data, $prs_id, $dn_id){

	$data['value'] = htmlspecialchars_decode($data['value']);
	$step = 0.01;
	$site = '%';

	//определяем условия окргуления.
	preg_match('#^\{(.*?)\}#', $data['value'], $rounds);
	//Вырезаем алгоритм округления из общего правила, и приводим правило в нужный формат
	if(!empty($rounds[0])){

		//вырезаем кусок ненужный для наценки.
		$data['value'] = preg_replace('#^\{(.*?)\}#','',$data['value']);

		//Приводим форматируем данные веденные пользователем. Да да, ведь вы все равно пишите лишние пробелы и запятые.
		$rounds[1] = trim(str_replace(' ','',str_replace(',','.',$rounds[1])));

		//Проверяем правильность ввода праила окргурления.
		if(preg_match('#^[0-9]+[.]*[0-9]*[|]*[<>%]*$#',$rounds[1])){
			//делим правило на значение кратное которому округляем. И на условие округления

			$round = explode('|', $rounds[1]);
			$step = (float)$round[0];

			//Указываем условие округления
			if(!empty($round[1])){
				$site = $round[1];
			}
		}
	}

	//приводим значение числа.
	$data['value'] = (float)trim(str_replace(',', '.', $data['value']));

	if($data['operator'] == '='){

		//Округляем. По умолчанию до двух нулей после запятой.
		$price = '';
		if($site == ">" && $step != 0){
			$price = 'CEILING( '.$data['value'].'/'.$step.' )*'.$step;
		}elseif($site == '<' && $step != 0){
			$price = 'FLOOR( '.$data['value'].'/'.$step.' )*'.$step;
		}else{
			$price = 'ROUND( '.$data['value'].'/'.$step.' )*'.$step;
		}

	}else{

		//Округляем. По умолчанию до двух нулей после запятой.
		$price = '';
		if($site == ">" && $step != 0){
			$price = 'CEILING( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}elseif($site == '<' && $step != 0){
			$price = 'FLOOR( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}else{
			$price = 'ROUND( (price'.$data['operator'].$data['value'].')/'.$step.' )*'.$step;
		}

	}

	$sql = "UPDATE `".DB_PREFIX."product_special` SET price = ".$price." WHERE customer_group_id = ".(int)$data['cast_group']." AND product_id IN (".$prs_id.")";
	$this->db->query($sql);

}

//изменить проект в товаре
public function toolChangeDn($data, $prs_id, $dn_id){
	//приводим значение числа.
	$data['value'] = (int)$data['value'];
	$sql = "UPDATE `".DB_PREFIX."product` SET	dn_id = ".$data['value']." WHERE product_id IN (".$prs_id.")";
	#$this->wtfarrey($sql);
	$this->db->query($sql);
}

//изменить мета данные
public function toolChangeMeta($data, $prs_id, $dn_id){
	$setting = $this->getSetting($dn_id);

	//составляем запрос на рботу с языковыми файлами
	$langs_id = implode(',', $data['langs']);
	#$this->wtfarrey($langs_id);
	//определяем где заполнять.
	if($data['operator'] == 'product'){ 

		$seo = $this->db->query("SELECT seo_h1, seo_title, seo_desc, seo_keyw FROM `".DB_PREFIX."pars_prsetup` WHERE dn_id =".$dn_id);
		$seo = $seo->row;

		//вырезаем все границы потому что их тут не может быть.
		foreach($seo as &$value){
			$value = $this->db->escape(preg_replace('#\{gran_(.*?)\}#', '', $value));
		}

		//Проверяем какая версия движка 
		$ocstore = '';
		if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
			$ocstore = ", meta_h1 ='".$seo['seo_h1']."'";
		}

		//составляем строку запроса.
		$sql = "UPDATE ".DB_PREFIX."product_description 
		SET meta_title = '".$seo['seo_title']."', 
		meta_description = '".$seo['seo_desc']."', 
		meta_keyword = '".$seo['seo_keyw']."'".$ocstore." WHERE product_id IN (".$prs_id.") AND language_id IN (".$langs_id.")";

		$this->db->query($sql);

	}elseif($data['operator'] == 'category'){

		//проверяем выбрали ли пользователь категории из списка, если да то берем напрямую их id
		if(!empty($data['cats'])){
			
			$ct_ids = implode(',', $data['cats']);
		
		} else {

			//получаем список товаров чьи категории будм править.
			$categorys = $this->db->query("SELECT category_id FROM ".DB_PREFIX."product_to_category WHERE product_id IN (".$prs_id.")");
			$categorys = $categorys->rows;
			$categorys = array_unique($categorys, SORT_REGULAR);
			//набор id для редактирования категорий.
			$ct_ids = '';
			foreach ($categorys as $key => $category) {
				if($key == 0){ $ct_ids = $category['category_id'] ;}else{ $ct_ids .= ','.$category['category_id']; }
			}
		
		}

		//если есть категории тогда приступаем обновлять
		if($ct_ids){
			$seo = $this->db->query("SELECT cat_seo_h1, cat_seo_title, cat_seo_desc, cat_seo_keyw FROM `".DB_PREFIX."pars_prsetup` WHERE dn_id =".$dn_id);
			$seo = $seo->row;
			
			//вырезаем все границы потому что их тут не может быть.
			foreach($seo as &$value){
				$value = $this->db->escape(preg_replace('#\{gran_(.*?)\}#', '', $value));
			}

			
			//Проверяем какая версия движка 
			$ocstore = '';
			if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
				$ocstore = ", meta_h1 ='".$seo['cat_seo_h1']."'";
			}

			//обновляем сео данные категории
			$sql = "UPDATE ".DB_PREFIX."category_description SET 
			meta_title ='".$seo['cat_seo_title']."', 
			meta_description ='".$seo['cat_seo_desc']."', 
			meta_keyword ='".$seo['cat_seo_keyw']."'".$ocstore." WHERE category_id IN (".$ct_ids.") AND language_id IN (".$langs_id.")";

			
			$this->db->query($sql);
		}

	}elseif($data['operator'] == 'manuf'){

		if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){
			
			//проверяем выбрали ли пользователь Производителей из списка, если да то берем напрямую их id
			if(!empty($data['manufs'])){
				
				$mf_ids = implode(',', $data['manufs']);
			
			} else {

				$manufs = $this->db->query("SELECT manufacturer_id FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				$manufs = $manufs->rows;
				$manufs = array_unique($manufs, SORT_REGULAR);

				//набор id для редактирования категорий.
				$mf_ids = '';
				foreach ($manufs as $key_mf => $manuf) {
					if($key_mf == 0){ $mf_ids = $manuf['manufacturer_id'] ;}else{ $mf_ids .= ','.$manuf['manufacturer_id']; }
				}
			}

			//Если есть id для редактирования тогда выполняем запрос.
			if($mf_ids){

				$seo = $this->db->query("SELECT manuf_seo_h1, manuf_seo_title, manuf_seo_desc, manuf_seo_keyw FROM `".DB_PREFIX."pars_prsetup` WHERE dn_id =".$dn_id);
				$seo = $seo->row;
				//вырезаем все границы потому что их тут не может быть.
				foreach($seo as &$value){
					$value = $this->db->escape(preg_replace('#\{gran_(.*?)\}#', '', $value));
				}
				//обновляем сео данные производителей
				$sql = "UPDATE ".DB_PREFIX."manufacturer_description SET 
				meta_h1 ='".$seo['manuf_seo_h1']."', 
				meta_title ='".$seo['manuf_seo_title']."', 
				meta_description ='".$seo['manuf_seo_desc']."', 
				meta_keyword ='".$seo['manuf_seo_keyw']."' WHERE manufacturer_id IN (".$mf_ids.") AND language_id IN (".$langs_id.")";

				$this->db->query($sql);
			}

		}

	}
}

//заполнить юрл
public function toolChangeUrl($data, $prs_id, $dn_id){

	//получаем глобальные настройки. И определяем какой движок используется.
	$setting = $this->getSetting($dn_id);

	//составляем запрос на рботу с языковыми файлами
	$langs_id = implode(',', $data['langs']);

	//Из за разности движков определяем в какой таблице нужно работать.
	$table = '';
	if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'opencart2'){
		$table = DB_PREFIX."url_alias"; 
	}elseif($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){
		$table = DB_PREFIX."seo_url"; 
	}
	
	#$this->wtfarrey($table);

	if($data['operator'] == 'product'){ 

		//префикс запрорса.
		$prefix = "product_id=";
		//опередяем какие товары обновлять. 
		$products = $this->db->query("SELECT product_id, name, language_id FROM ".DB_PREFIX."product_description 
																WHERE	product_id IN (".$prs_id.") AND language_id IN (".$langs_id.")");
		$products = $products->rows;
		#$this->wtfarrey($products);
		foreach($products as $product){
			//преобразовываем имя в seo_url
			$product['seo_url'] = $this->db->escape(substr($this->madeUrl($product['name']), 0, 255));
			unset($product['name']);

			//Удаляем запись если такая была ранние
			$this->db->query("DELETE FROM ".$table." WHERE query ='".$prefix.$product['product_id']."'");

			//проверяем есть ли такой url в базе.
			$count_pr = $this->db->query("SELECT COUNT(*) as count FROM ".$table." WHERE keyword = '".$product['seo_url']."'");

			//если такой юрл есть то добавляем в конце id товара
			if($count_pr->row['count']){
				//получаем длину id товара. И отнимаем ее от 255 что бы добавить туда id товара
				$strlen = (255 - (strlen($product['product_id']) + 1));
				$product['seo_url'] = substr($product['seo_url'], 0, $strlen).'-'.$product['product_id'];
			}
			
			//выполняем запрос на добавление seo url товара
			if($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){
				$sql = "INSERT INTO ".$table." SET keyword = '".$product['seo_url']."', language_id=".$product['language_id'].", query = '".$prefix.$product['product_id']."'";
				$this->db->query($sql);
			}else{
				$sql = "INSERT INTO ".$table." SET keyword = '".$product['seo_url']."', query = '".$prefix.$product['product_id']."'";
				$this->db->query($sql);
			}
		}

	}elseif($data['operator'] == 'category'){

		//префикс запрорса.
		$prefix = "category_id=";

		//проверяем выбрали ли пользователь категории из списка, если да то берем напрямую их id
		if(!empty($data['cats'])){
			
			$ct_ids = implode(',', $data['cats']);
		
		} else {
			//опередяем какие Категории. 
			$categorys = $this->db->query("SELECT category_id FROM ".DB_PREFIX."product_to_category WHERE product_id IN (".$prs_id.")");
			$categorys = $categorys->rows;
			$categorys = array_unique($categorys, SORT_REGULAR);

			//набор id для редактирования категорий.
			$ct_ids = '';
			foreach ($categorys as $key => $category) {
				if($key == 0){ $ct_ids = $category['category_id'] ;}else{ $ct_ids .= ','.$category['category_id']; }
			}
		}

		//если есть категории который нужно править продолжаем.
		if($ct_ids){

			$categorys = $this->db->query("SELECT category_id, name, language_id FROM ".DB_PREFIX."category_description 
																		WHERE category_id IN (".$ct_ids.") AND language_id IN (".$langs_id.")");
			$categorys = $categorys->rows;

			foreach($categorys as $category){

				$category['seo_url'] = $this->db->escape(substr($this->madeUrl($category['name']), 0, 255));
				unset($category['name']);

				//Удаляем запись если такая была ранние
				$this->db->query("DELETE FROM ".$table." WHERE query ='".$prefix.$category['category_id']."'");

				//проверяем есть ли такой url в базе.
				$count_ct = $this->db->query("SELECT COUNT(*) as count FROM ".$table." WHERE keyword = '".$category['seo_url']."'");

				//если такой юрл есть то добавляем в конце id товара
				if($count_ct->row['count']){
					//получаем длину id товара. И отнимаем ее от 255 что бы добавить туда id товара
					$strlen = (255 - (strlen($category['category_id']) + 1));
					$category['seo_url'] = substr($category['seo_url'], 0, $strlen).'-'.$category['category_id'];
				}

				//выполняем запрос на добавление seo url категории
				if($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){
					$sql = "INSERT INTO ".$table." SET keyword = '".$category['seo_url']."', language_id=".$category['language_id'].", query = '".$prefix.$category['category_id']."'";
					$this->db->query($sql);
				}else{
					$sql = "INSERT INTO ".$table." SET keyword = '".$category['seo_url']."', query = '".$prefix.$category['category_id']."'";
					#$this->wtfarrey($sql);
					$this->db->query($sql);
				}

			}
		}

	}elseif($data['operator'] == 'manuf'){

		//префикс запрорса.
		$prefix = "manufacturer_id=";
		//опередяем какие производителей обновлять.  manufacturer_id
		$manufs = $this->db->query("SELECT ".DB_PREFIX."product.manufacturer_id, ".DB_PREFIX."manufacturer.name 
																FROM ".DB_PREFIX."product INNER JOIN ".DB_PREFIX."manufacturer 
																ON ".DB_PREFIX."product.manufacturer_id = ".DB_PREFIX."manufacturer.manufacturer_id
																WHERE ".DB_PREFIX."product.product_id IN (".$prs_id.")");
		$manufs = $manufs->rows;
		$manufs = array_unique($manufs, SORT_REGULAR);
		#$this->wtfarrey($manufs);
		foreach($manufs as $manuf){
			#$this->wtfarrey($manuf);
			//преобразовываем имя в seo_url
			$manuf['seo_url'] = $this->db->escape(substr($this->madeUrl($manuf['name']), 0, 255));
			unset($manuf['name']);

			//Удаляем запись если такая была ранние
			$this->db->query("DELETE FROM ".$table." WHERE query ='".$prefix.$manuf['manufacturer_id']."'");

			//проверяем есть ли такой url в базе.
			$count_mf = $this->db->query("SELECT COUNT(*) as count FROM ".$table." WHERE keyword = '".$manuf['seo_url']."'");

			//если такой юрл есть то добавляем в конце id товара
			if($count_mf->row['count']){
				//получаем длину id товара. И отнимаем ее от 255 что бы добавить туда id товара
				$strlen = (255 - (strlen($manuf['manufacturer_id']) + 1));
				$manuf['seo_url'] = substr($manuf['seo_url'], 0, $strlen).'-'.$manuf['manufacturer_id'];
			}
			
			//выполняем запрос на добавление seo url товара
			if($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){
				//заполняем для всех языков
				$manuf_langs = explode(',', $langs_id);
				foreach($manuf_langs as $manuf_lang){
					$sql = "INSERT INTO ".$table." SET keyword = '".$manuf['seo_url']."', language_id=".$manuf_lang.", query = '".$prefix.$manuf['manufacturer_id']."'";
					$this->db->query($sql);
				}
			}else{
				$sql = "INSERT INTO ".$table." SET keyword = '".$manuf['seo_url']."', query = '".$prefix.$manuf['manufacturer_id']."'";
				$this->db->query($sql);
			}
		}
	}
}

//использовать поиск замену
public function toolFindReplace($data, $prs_id, $dn_id){
	#$this->wtfarrey($data);
	//проверяем есть ли правила.
	if(!empty($data['rules'])){

		//преобразуем правила. 
		$data['rules'] = $this->parseRulesToReplace($data);
		#$this->wtfarrey($data['rules']);

		//проверяем что бы правила поиск замены не были пусты.
		if(!empty($data['rules'])){

			//проверяем что бы было выбрано поле.
			if($data['operator'] == 'product_name'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, language_id, name FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");

				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){

					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['name'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['name']));
								}elseif($rule[1] == '{lower}'){
									$product['name'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['name']));
								}else{
									$product['name'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['name']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
			  			name ='".$this->db->escape($product['name'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
			  	}
		  	}

			}elseif($data['operator'] == 'product_desc'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, language_id, description FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['description'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['description']));
								}elseif($rule[1] == '{lower}'){
									$product['description'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['description']));
								}else{
									$product['description'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['description']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
			  			description ='".$this->db->escape($product['description'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_h1'){

				//получаем информацию о движке.
				$setting = $this->getSetting($dn_id);

				//если это ocstore то делаем работу.
				if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'ocstore3'){

					//получаем данные о товаре в котором будем работать.
					$products = $this->db->query("SELECT product_id, language_id, meta_h1 FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
					//проверяем что бы под фильтр попали товары.
					if($products->num_rows > 0){
						
						foreach($products->rows as $product){

				  		foreach($data['rules'] as $rule){

				  			if(isset($rule[0]) && isset($rule[1])){

				  				$rule[0] = $this->pregRegLeft($rule[0]);
				  				$rule[1] = $this->pregRegRight($rule[1]);

				  				if($rule[1] == '{upper}'){
										$product['meta_h1'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['meta_h1']));
									}elseif($rule[1] == '{lower}'){
										$product['meta_h1'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['meta_h1']));
									}else{
										$product['meta_h1'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['meta_h1']));
									}

				  			}
				  		}

				  		//Записываем рузультат.
				  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
				  			meta_h1 ='".$this->db->escape($product['meta_h1'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
				  	}
					}

				}

			}elseif($data['operator'] == 'product_title'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, language_id, meta_title FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['meta_title'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['meta_title']));
								}elseif($rule[1] == '{lower}'){
									$product['meta_title'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['meta_title']));
								}else{
									$product['meta_title'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['meta_title']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
			  			meta_title ='".$this->db->escape($product['meta_title'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_meta_desc'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, language_id, meta_description FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['meta_description'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['meta_description']));
								}elseif($rule[1] == '{lower}'){
									$product['meta_description'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['meta_description']));
								}else{
									$product['meta_description'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['meta_description']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
			  			meta_description ='".$this->db->escape($product['meta_description'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_keyword'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, language_id, meta_keyword FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['meta_keyword'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['meta_keyword']));
								}elseif($rule[1] == '{lower}'){
									$product['meta_keyword'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['meta_keyword']));
								}else{
									$product['meta_keyword'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['meta_keyword']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product_description SET 
			  			meta_keyword ='".$this->db->escape($product['meta_keyword'])."' WHERE product_id =".$product['product_id']." AND language_id=".(int)$product['language_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_model'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, model FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['model'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['model']));
								}elseif($rule[1] == '{lower}'){
									$product['model'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['model']));
								}else{
									$product['model'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['model']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET model ='".$this->db->escape($product['model'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_sku'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, sku FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['sku'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['sku']));
								}elseif($rule[1] == '{lower}'){
									$product['sku'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['sku']));
								}else{
									$product['sku'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['sku']));
								}
			  				
			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET sku ='".$this->db->escape($product['sku'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_upc'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, upc FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['upc'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['upc']));
								}elseif($rule[1] == '{lower}'){
									$product['upc'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['upc']));
								}else{
									$product['upc'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['upc']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET upc ='".$this->db->escape($product['upc'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_ean'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, ean FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['ean'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['ean']));
								}elseif($rule[1] == '{lower}'){
									$product['ean'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['ean']));
								}else{
									$product['ean'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['ean']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET ean ='".$this->db->escape($product['ean'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_jan'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, jan FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['jan'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['jan']));
								}elseif($rule[1] == '{lower}'){
									$product['jan'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['jan']));
								}else{
									$product['jan'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['jan']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET jan ='".$this->db->escape($product['jan'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_isbn'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, isbn FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['isbn'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['isbn']));
								}elseif($rule[1] == '{lower}'){
									$product['isbn'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['isbn']));
								}else{
									$product['isbn'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['isbn']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET isbn ='".$this->db->escape($product['isbn'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_mpn'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, mpn FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['mpn'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['mpn']));
								}elseif($rule[1] == '{lower}'){
									$product['mpn'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['mpn']));
								}else{
									$product['mpn'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['mpn']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET mpn ='".$this->db->escape($product['mpn'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}elseif($data['operator'] == 'product_location'){

				//получаем данные о товаре в котором будем работать.
				$products = $this->db->query("SELECT product_id, location FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
				//проверяем что бы под фильтр попали товары.
				if($products->num_rows > 0){
					
					foreach($products->rows as $product){

			  		foreach($data['rules'] as $rule){

			  			if(isset($rule[0]) && isset($rule[1])){

			  				$rule[0] = $this->pregRegLeft($rule[0]);
			  				$rule[1] = $this->pregRegRight($rule[1]);

			  				if($rule[1] == '{upper}'){
									$product['location'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, html_entity_decode($product['location']));
								}elseif($rule[1] == '{lower}'){
									$product['location'] = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, html_entity_decode($product['location']));
								}else{
									$product['location'] = preg_replace($rule[0], $rule[1], html_entity_decode($product['location']));
								}

			  			}
			  		}

			  		//Записываем рузультат.
			  		$this->db->query("UPDATE ".DB_PREFIX."product SET location ='".$this->db->escape($product['location'])."' WHERE product_id =".$product['product_id']);
			  	}
				}

			}

		}
	}
}

//удалить товар
public function toolDelProducts($data, $prs_id, $dn_id){
	//получаем глобальные настройки. И определяем какой движок используется.
	$setting = $this->getSetting($dn_id);

	//Из за разности движков определяем в какой таблице нужно работать.
	$table = '';
	if($setting['vers_op'] == 'ocstore2' || $setting['vers_op'] == 'opencart2'){
		$table = DB_PREFIX."url_alias"; 
	}elseif($setting['vers_op'] == 'ocstore3' || $setting['vers_op'] == 'opencart3'){
		$table = DB_PREFIX."seo_url"; 
	}

	//Определяем еть что удалять или нет.
	if($prs_id){

		// опередляем что нам удалять только товары или товары с фото.
		// 1 - удаляем только товары. | 2 - Удаляем товары и их фото
		if($data['operator'] == 2){

			//Получаем путь к главному фото.
			$main_imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");

			//Удаляем главное фото товара.
			foreach($main_imgs->rows as $img_main){
				@unlink(DIR_IMAGE.$img_main['image']);
			}
			//Для экономия места удаляем массив.
			unset($main_imgs);

			//получаем сиписок доп фото
			$imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product_image WHERE product_id IN (".$prs_id.")");

			//Удаляем доп фото
			foreach($imgs->rows as $img){
				@unlink(DIR_IMAGE.$img['image']);
			}
			unset($imgs);
		}
		
		//Удаляем товар из главной таблицы.
		$this->db->query("DELETE FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
		//Удаляем из описания товара
		$this->db->query("DELETE FROM ".DB_PREFIX."product_description WHERE product_id IN (".$prs_id.")");
		//Удаляем из атрибутов.
		$this->db->query("DELETE FROM ".DB_PREFIX."product_attribute WHERE product_id IN (".$prs_id.")");
		//Удаляем дисконты
		$this->db->query("DELETE FROM ".DB_PREFIX."product_discount WHERE product_id IN (".$prs_id.")");
		//Удаляем фильтры
		$this->db->query("DELETE FROM ".DB_PREFIX."product_filter WHERE product_id IN (".$prs_id.")");
		//Удаляем записи доп фото 
		$this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id IN (".$prs_id.")");
		//Удаляем опуции
		$this->db->query("DELETE FROM ".DB_PREFIX."product_option WHERE product_id IN (".$prs_id.")");
		//Удаляем значения опций 
		$this->db->query("DELETE FROM ".DB_PREFIX."product_option_value WHERE product_id IN (".$prs_id.")");
		//Что то еше  :) 
		$this->db->query("DELETE FROM ".DB_PREFIX."product_recurring WHERE product_id IN (".$prs_id.")");
		$this->db->query("DELETE FROM ".DB_PREFIX."product_related WHERE product_id IN (".$prs_id.")");
		$this->db->query("DELETE FROM ".DB_PREFIX."product_reward WHERE product_id IN (".$prs_id.")");
		$this->db->query("DELETE FROM ".DB_PREFIX."product_special WHERE product_id IN (".$prs_id.")");
		//Удаляем записи товара в категории
		$this->db->query("DELETE FROM ".DB_PREFIX."product_to_category WHERE product_id IN (".$prs_id.")");
		//Удаляем файлы 
		$this->db->query("DELETE FROM ".DB_PREFIX."product_to_download WHERE product_id IN (".$prs_id.")");
		//Удаляем расположение.
		$this->db->query("DELETE FROM ".DB_PREFIX."product_to_layout WHERE product_id IN (".$prs_id.")");
		//Удаляем присвоение товара магазину.
		$this->db->query("DELETE FROM ".DB_PREFIX."product_to_store WHERE product_id IN (".$prs_id.")");
		
		//Формируем массив для удаления SEO_URL
		$tmp_prs_id = explode(',', $prs_id);
		$seo_pr_id = '';
		foreach($tmp_prs_id as $key_seo => $pr_id){
			if($key_seo == 0){ $seo_pr_id = "'product_id=".$pr_id."'"; } else { $seo_pr_id .= ",'product_id=".$pr_id."'"; }
		}
		
		$this->db->query("DELETE FROM ".$table." WHERE query IN (".$seo_pr_id.")");
	}
}

//Фунция удваления фото товара
public function toolDelProductsImg($data, $prs_id, $dn_id){

	#$this->wtfarrey($data);

	//определяем что удалять.
	if($data['operator'] == 1){
		//получаем главное фото.
		$main_imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
		//Удаляем главное фото товара.
		foreach($main_imgs->rows as $img_main){
			@unlink(DIR_IMAGE.$img_main['image']);
		}
		//Для экономия места удаляем массив.
		unset($main_imgs);

		//обновляем запись в базе что фото были удалены.
		$this->db->query("UPDATE ".DB_PREFIX."product SET image = '' WHERE product_id IN (".$prs_id.")");
	}

	//получаем сиписок доп фото
	$imgs = $this->db->query("SELECT image FROM ".DB_PREFIX."product_image WHERE product_id IN (".$prs_id.")");
	//Удаляем доп фото
	foreach($imgs->rows as $img){
		@unlink(DIR_IMAGE.$img['image']);
	}
	unset($imgs);

	//Удаляем запись в базе про доп фото, мы же их удалили.
	$this->db->query("DELETE FROM `".DB_PREFIX."product_image` WHERE `product_id` IN (".$prs_id.")");
}

//удаление акции в товаре.
public function toolDelPriceSpec($data, $prs_id, $dn_id){
	//Удаляем запись в базе про акцию в товарах.
	$this->db->query("DELETE FROM `".DB_PREFIX."product_special` WHERE `product_id` IN (".$prs_id.")");
}

//Удаление атрибутов в товаре.
public function toolDelAttrProducts($data, $prs_id, $dn_id){
	#Удаляем все атрибуты из товара.
	$this->db->query("DELETE FROM `".DB_PREFIX."product_attribute` WHERE `product_id` IN (".$prs_id.")");
}

//Удаление опций в товаре.
public function toolDelOptProducts($data, $prs_id, $dn_id){
	$this->db->query("DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` IN (".$prs_id.")");
	$this->db->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_id` IN (".$prs_id.")");
}

//удаления лога проекта.
public function toolTechDelLogs($data, $prs_id, $dn_id){
	//файл лога.
	$file = DIR_LOGS."simplepars_id-".$dn_id.".log";
  $handle = fopen($file, 'w+');
  fclose($handle);
}

//удаляет кеш страниц донора.
public function toolTechDelCache($data, $prs_id, $dn_id){
	$files = glob(DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/*'); 
	foreach($files as $file){ 
    @unlink($file); 
	}
}

//фунция по удаление пустых производителей.
public function toolDelManufIsNull($data, $prs_id, $dn_id){
	$this->db->query("DELETE m FROM ".DB_PREFIX."manufacturer m LEFT JOIN ".DB_PREFIX."product p 
										ON m.manufacturer_id = p.manufacturer_id
										WHERE p.manufacturer_id IS NULL");

	$this->db->query("DELETE m FROM ".DB_PREFIX."manufacturer_description m LEFT JOIN ".DB_PREFIX."product p 
										ON m.manufacturer_id = p.manufacturer_id
										WHERE p.manufacturer_id IS NULL");
}

//Фунция удаления атрибутов непривязанных к товарам.
public function toolDelAttrIsNull($data, $prs_id, $dn_id){
	$this->db->query("DELETE t FROM ".DB_PREFIX."attribute t LEFT JOIN ".DB_PREFIX."product_attribute p 
										ON t.attribute_id = p.attribute_id 
										WHERE p.attribute_id IS NULL");

	$this->db->query("DELETE t FROM ".DB_PREFIX."attribute_description t LEFT JOIN ".DB_PREFIX."attribute p 
										ON t.attribute_id = p.attribute_id 
										WHERE p.attribute_id IS NULL");
}

//Фунция получения списка id товаров.
public function toolGetPrsId($data){
	#$this->wtfarrey($data);
	$where = ' WHERE';
	#$list_id = '1';
	//Проверяем выбранного поставшика.
	if(!empty($data['dns_arr'])){
		
		//Если стоит галачка все товары то не учитываем остальные берем все.
		if(!in_array('all',$data['dns_arr'])){
			$where .= " ".DB_PREFIX."product.dn_id in (".implode(',', $data['dns_arr']).")";
		}else{
			$where .= " ".DB_PREFIX."product.dn_id >= 0";
		}

	}else{
		$where .= " ".DB_PREFIX."product.dn_id = 0";
	}
	#$this->wtfarrey($where);
	//Проверяем есть ли фильтр по производителям.
	if(!empty($data['manufs'])){
		$where .= " AND ".DB_PREFIX."product.manufacturer_id in (".implode(',', $data['manufs']).")";
	}
	#$this->wtfarrey($data['cats']);
	//Проверяем есть ли фильтр по производителям.
	if(!empty($data['cats'])){

		//проверяем выбраны ли товары без категорий.
		$cats_null = "";
		if(isset($data['cats'][0]) && $data['cats'][0] == 0){ $cats_null = "OR ".DB_PREFIX."product_to_category.product_id IS NULL";}

		$where .= " AND (".DB_PREFIX."product_to_category.category_id in (".implode(',', $data['cats']).") ".$cats_null.")";
		$inner_cats =" LEFT JOIN ".DB_PREFIX."product_to_category ON ".DB_PREFIX."product.product_id = ".DB_PREFIX."product_to_category.product_id";
	}else{
		$inner_cats = "";
	}
	
	//Проверяем есть ли фильтр по языку.
	if(!empty($data['langs'])){
		$where .= " AND ".DB_PREFIX."product_description.language_id in (".implode(',', $data['langs']).")";
	}

	if(!empty($data['filters'])){
		//перебераем все фильтры
		foreach ($data['filters'] as $filter) {

			//для начала проверяем есть ли значение в этой фильтре
			if(empty($filter['value']) && $filter['value'] != '0'){
				$filter['value'] = '';
			}
				
				//определяем поле в таблице.
				if($filter['take_filtr'] == '0'){
					continue;
				}elseif($filter['take_filtr'] == 'product_id'){ 

					$table = DB_PREFIX.'product.product_id';

				}elseif($filter['take_filtr'] == 'sku'){

					$table = DB_PREFIX.'product.sku';

				}elseif($filter['take_filtr'] == 'model'){

					$table = DB_PREFIX.'product.model';
				
				}elseif($filter['take_filtr'] == 'price'){
				
					$table = DB_PREFIX.'product.price';
				
				}elseif($filter['take_filtr'] == 'quantity'){
				
					$table = DB_PREFIX.'product.quantity';
				
				}elseif($filter['take_filtr'] == 'status'){
				
					$table = DB_PREFIX.'product.status';
				
				}elseif($filter['take_filtr'] == 'date_added'){
				
					//проверяем и преобразовываем дату
					$filter['value'] = str_replace('{date}', date("Y-m-d"), $filter['value']);
					$table = DB_PREFIX.'product.date_added';
				
				}elseif($filter['take_filtr'] == 'date_modified'){
				
					//проверяем и преобразовываем дату
					$filter['value'] = str_replace('{date}', date("Y-m-d"), $filter['value']);
					$table = DB_PREFIX.'product.date_modified';
				
				}elseif($filter['take_filtr'] == 'name'){
				
					$table = DB_PREFIX.'product_description.name';
				
				}elseif($filter['take_filtr'] == 'description'){
				
					$table = DB_PREFIX.'product_description.description';
				
				}elseif($filter['take_filtr'] == 'upc'){
				
					$table = DB_PREFIX.'product.upc';
				
				}elseif($filter['take_filtr'] == 'ean'){
				
					$table = DB_PREFIX.'product.ean';
				
				}elseif($filter['take_filtr'] == 'jan'){
				
					$table = DB_PREFIX.'product.jan';
				
				}elseif($filter['take_filtr'] == 'isbn'){
				
					$table = DB_PREFIX.'product.isbn';
				
				}elseif($filter['take_filtr'] == 'mpn'){
				
					$table = DB_PREFIX.'product.mpn';
				
				}elseif($filter['take_filtr'] == 'location'){
				
					$table = DB_PREFIX.'product.location';
				
				}

				#$this->wtfarrey($filter);
				$pos = $this->toolFilterPosition($filter['position'], $filter['style']);

				//делим значение на массив если ли это многомерное значение. Заодно удаляем пустые массивы
				$filter['value'] = explode('|', $filter['value']);
				foreach($filter['value'] as $key_value => $value){

					$value = $this->db->escape($value);
					if($key_value == 0){ 
						$where .= ' AND ('.$table.str_replace('{data}', $value, $pos); 
					}else{
						$where .= ' OR '.$table.str_replace('{data}', $value, $pos);
					}
				}
				$where .= ')';

			
		}
	}

	//главный запрос на получение id товара.
	$sql = "SELECT ".DB_PREFIX."product.product_id 
	FROM ".DB_PREFIX."product INNER JOIN ".DB_PREFIX."product_description 
	ON ".DB_PREFIX."product.product_id = ".DB_PREFIX."product_description.product_id".$inner_cats.$where;

	#$this->wtfarrey($sql);

	$prs_id = $this->db->query($sql)->rows;
	$prs_id = array_unique($prs_id, SORT_REGULAR);

	#$this->wtfarrey($prs_id);

	$list_id = '';
	foreach($prs_id as $key => $pr){

		if($key){ $list_id .= ','.$pr['product_id']; } else { $list_id = $pr['product_id'];}

	}

	if(empty($list_id)){ $list_id = 0;}

	return $list_id;
}

public function toolRecalcCountWithOptions($data, $prs_id, $dn_id){
	//Запрос который обновит колво товаров по обшему колву опций.
	$this->db->query("UPDATE ".DB_PREFIX."product AS p 
		INNER JOIN (SELECT ".DB_PREFIX."product_option_value.product_id, SUM(".DB_PREFIX."product_option_value.quantity) AS quantity_sum 
		FROM ".DB_PREFIX."product_option_value GROUP BY ".DB_PREFIX."product_option_value.product_id) AS pov 
		SET p.quantity=pov.quantity_sum 
		WHERE p.product_id IN (".$prs_id.") AND p.product_id=pov.product_id");
}

//Конвертируем фото с формата webp в jpg
public function toolConvertWebpToJpeg($data, $prs_id, $dn_id){
	
	if($data['operator'] > 0 && function_exists('imagecreatefromwebp') && function_exists('imagejpeg')){
		
		//преобразовываем главные фото товара, если есть такие в формате webp 
		$mein_imegs = $this->db->query("SELECT product_id, image FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.") AND image LIKE '%.webp'")->rows;

		if($mein_imegs){
			
			foreach($mein_imegs as $mein){

				//записываем файл в временную директорию.
				$webp_path = DIR_IMAGE.$mein['image'];

				//проверяем есть ли файл, и попутно читаем его в ресурс.
				if($jpeg = imageCreatefromWebp($webp_path)){
					
					//Проверяем в какой формат записываем файлы.
					if($data['operator'] == 1){

						//меняе формат форто
						$jpeg_path = preg_replace('#\.webp$#', '.jpg', $mein['image']);

						//Сохранем ресурс в фото в формате jpg
						imageJpeg($jpeg, DIR_IMAGE.$jpeg_path, 100);

					}elseif($data['operator'] == 2){

						//меняе формат форто
						$jpeg_path = preg_replace('#\.webp$#', '.png', $mein['image']);

						//Сохранем ресурс в фото в формате jpg
						imagepng($jpeg, DIR_IMAGE.$jpeg_path, -1);

					}
					
					//удаляем ресурс из памяти.
					imagedestroy($jpeg);
					$this->db->query("UPDATE ".DB_PREFIX."product SET image ='".$jpeg_path."' WHERE product_id =".$mein['product_id']);
					unlink($webp_path);
				}
			}
		}

		//преобразовываем доп фото товара, если есть такие в формате webp 
		$imegs = $this->db->query("SELECT product_image_id, image FROM ".DB_PREFIX."product_image WHERE product_id IN (".$prs_id.") AND image LIKE '%.webp'")->rows;

		if($imegs){
			
			foreach($imegs as $img){

				//записываем файл в временную директорию.
				$webp_path = DIR_IMAGE.$img['image'];

				//проверяем есть ли файл, и попутно читаем его в ресурс.
				if($jpeg = imageCreatefromWebp($webp_path)){

					//Проверяем в какой формат записываем файлы.
					if($data['operator'] == 1){

						//меняе формат форто
						$jpeg_path = preg_replace('#\.webp$#', '.jpg', $img['image']);

						//Сохранем ресурс в фото в формате jpg
						imageJpeg($jpeg, DIR_IMAGE.$jpeg_path, 100);

					}elseif($data['operator'] == 2){

						//меняе формат форто
						$jpeg_path = preg_replace('#\.webp$#', '.png', $img['image']);

						//Сохранем ресурс в фото в формате jpg
						imagepng($jpeg, DIR_IMAGE.$jpeg_path, -1);

					}

					//удаляем ресурс из памяти.
					imagedestroy($jpeg);
					$this->db->query("UPDATE ".DB_PREFIX."product_image SET image ='".$jpeg_path."' WHERE product_image_id =".$img['product_image_id']);
					unlink($webp_path);
				}
			}
		}
	}
}


//Обеденяем товары в одну группу.
public function toolAddProductToHpm($data, $prs_id, $dn_id){

	if(empty($data['operator'])){

		return false;

	}elseif($data['operator'] == 'filtered_products'){

		//добавляем в одну группу все отфильтрованные товары.
		if(!empty($prs_id)){
			$products = explode(',', $prs_id);
			sort($products);
			
			//проверяем что бы было хотя бы два товара.
			if(!empty($products[1])){
				
				//определяем кто папа.
				$parent_id = $products[0];

				foreach ($products as $product_id) {
          $hl_query = $this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."hpmodel_links` WHERE parent_id = '".(int)$parent_id."' AND product_id = '".(int)$product_id."'");
          
          if ($hl_query->row['total'] == 0) {
            $this->db->query("INSERT INTO `".DB_PREFIX."hpmodel_links` SET `parent_id` = '".(int)$parent_id."', `product_id` = '".(int)$product_id."'");
          }
        }

			}
		}

	}else{

		//обьеденение всех товаров в разные группы. В одну группу обьеденяем все товары с одинаковым значением. 
		//
		//получаем данные для связи. 
		$query = $this->db->query("SELECT * FROM (SELECT p.".$this->db->escape($data['operator']).", COUNT(p.product_id) AS total, MAX(hpl.parent_id) AS parent_id, MAX(hpl.parent_id IS NULL) AS part, GROUP_CONCAT(p.product_id SEPARATOR ',') AS products FROM `".DB_PREFIX."product` p LEFT JOIN `".DB_PREFIX."hpmodel_links` hpl ON (p.product_id = hpl.product_id) GROUP BY p.".$this->db->escape($data['operator']).") t WHERE total > 1 AND part > 0");

		foreach ($query->rows as $group_data) {
	    $products = explode(',', $group_data['products']);
	    
	    if (!$products) {
	      continue;
	    }
	    
	    sort($products);
	    $parent_id = $group_data['parent_id'];
	    
	    if ($parent_id > 0) {
        foreach ($products as $product_id) {
          $hl_query = $this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX."hpmodel_links` WHERE parent_id = '".(int)$parent_id."' AND product_id = '".(int)$product_id."'");
          
          if ($hl_query->row['total'] == 0) {
            $this->db->query("INSERT INTO `".DB_PREFIX."hpmodel_links` SET `parent_id` = '".(int)$parent_id."', `product_id` = '".(int)$product_id."'");
          }
        }
	    } else {
        $parent_id = $products[0];                
        foreach ($products as $product_id) {
          $this->db->query("INSERT INTO `".DB_PREFIX."hpmodel_links` SET `parent_id` = '".(int)$parent_id."', `product_id` = '".(int)$product_id."'");
        }
	    }
		}
	}
}

//Удаление товаров из групп HPM
public function tooldelProductToHpm($data, $prs_id, $dn_id){
	//Удаление товаров из групп HPM
	$this->db->query("DELETE FROM `".DB_PREFIX."hpmodel_links` WHERE `parent_id` in (".$prs_id.") OR `product_id` in (".$prs_id.")");
}

//Фунция получение товара.
public function toolFilterToPage($data, $dn_id){
	#$this->wtfarrey($data);
	$products = [];
	$back_cod = [];
	$answ = [];
	$page = 1;
	$page_count = 50;
	if(!empty($data['page'])){ $page = $data['page']; }
	if(!empty($data['page_count'])){ $page_count = $data['page_count']; }

	//получаем основной язык админки.
	$language_id = $this->getLangDef();

	//определяем колво товаров на страницу
	$limit_start = ($page * $page_count) - $page_count;
	$limit_stop = $limit_start + $page_count;
	$limit = ' LIMIT '.$limit_start.','.$limit_stop;
	#$this->wtfarrey($limit);

	//получаем список id товаров которые попадают под фильтры
	$prs_id = $this->toolGetPrsId($data);

	//Получаем список товаров
	//////////////////////////////

	//Получаем колво товаров.
	$total_products = $this->db->query("SELECT COUNT(*) as count FROM ".DB_PREFIX."product WHERE product_id IN (".$prs_id.")");
	$total_products = $total_products->row['count'];


	$sql = "SELECT ".DB_PREFIX."product.product_id, ".DB_PREFIX."product.model, ".DB_PREFIX."product.sku, ".DB_PREFIX."product.price, ".DB_PREFIX."product.quantity, ".DB_PREFIX."product.image, ".DB_PREFIX."product.status, ".DB_PREFIX."product.date_added, ".DB_PREFIX."product.date_modified, ".DB_PREFIX."product.dn_id, ".DB_PREFIX."product_description.name FROM ".DB_PREFIX."product INNER JOIN ".DB_PREFIX."product_description ON ".DB_PREFIX."product.product_id = ".DB_PREFIX."product_description.product_id 
		WHERE ".DB_PREFIX."product.product_id in (".$prs_id.") AND ".DB_PREFIX."product_description.language_id = ".$language_id." 
		ORDER BY ".DB_PREFIX."product.product_id ASC".$limit;

	#$this->wtfarrey($sql);

	$back_cod['sql'] = $sql;

	$products = $this->db->query($sql);
	$products = $products->rows;
	$products = array_unique($products, SORT_REGULAR);
	//преобразовываем фото
	foreach($products as &$product){
		#$product['name'] = htmlspecialchars($product['name']);
		$product['url_out'] = HTTP_CATALOG.'index.php?route=product/product&product_id='.$product['product_id'];
		$product['image'] = $this->toolResizeImg($product['image']);
		#$product['description'] = htmlspecialchars($product['description']);
		if($product['status']) { $product['status'] = 'Вкл (1)';}else{ $product['status'] = 'Выкл (0)';}
	}


	$answ['products'] = $products;
	$answ['back_cod'] = $back_cod['sql'];
	$answ['total'] = $total_products;
	#$this->wtfarrey($answ);
	return $answ;
}

//Контроллер выполнения фунций над товарами
public function toolControlerFunction($data, $dn_id, $who = 'user'){

	//Проверяем какой язык выбран, если никакой берем язык по умолчанию.
	if(empty($data['langs'])){ $data['langs'][] = $this->getLangDef(); }

	//проверяем выбрано ли действие.
	if(!empty($data['do_tools'])){
		//Получаем where
		$prs_id = $this->toolGetPrsId($data);

		//определяем какая фунция была выбрана.
		if($data['do_tools'] == 'change_price'){

			$this->toolChangePrice($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'change_quant'){

			$this->toolChangeQuant($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'change_status'){

			$this->toolChangeStatus($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_stock_status'){

			$this->toolChangeStockStatus($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_cats_add'){

			$this->toolAddCatsToProducts($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_cats_ch'){

			$this->toolChangeCatsToProducts($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_main_cat'){

			$this->toolChangeMainCatToProducts($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'show_to_parent_cat'){

			$this->toolWorkWithParentCat($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_manuf'){

			$this->toolChangeManuf($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_spec_price'){

			$this->toolChangeSpecPrice($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_dn'){

			$this->toolChangeDn($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'change_meta'){
			
			$this->toolChangeMeta($data, $prs_id, $dn_id);
		
		}elseif($data['do_tools'] == 'change_url'){
			
			$this->toolChangeUrl($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'replace'){

			$this->toolFindReplace($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_product'){
			
			$this->toolDelProducts($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_product_img'){
			
			$this->toolDelProductsImg($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_price_spec'){
			
			$this->toolDelPriceSpec($data, $prs_id, $dn_id);
			
		}elseif($data['do_tools'] == 'del_attr_product'){
			
			$this->toolDelAttrProducts($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_opt_product'){
			
			$this->toolDelOptProducts($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_manuf_is_null'){
			
			$this->toolDelManufIsNull($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_attr_is_null'){
			
			$this->toolDelAttrIsNull($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'count_with_opt'){
			
			$this->toolRecalcCountWithOptions($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'tech_del_logs'){
			
			$this->toolTechDelLogs($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'tech_del_cache'){
			
			$this->toolTechDelCache($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'add_product_to_hpm'){
			
			$this->toolAddProductToHpm($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'del_product_to_hpm'){
			
			$this->toolDelProductToHpm($data, $prs_id, $dn_id);

		}elseif($data['do_tools'] == 'webp_convert'){
			
			$this->toolConvertWebpToJpeg($data, $prs_id, $dn_id);

		}

		//определяем кто вызвал действие. Если пользователь то даем ответ. А если крон молчим.
		if($who == 'user'){ exit(json_encode("Действие выполнено!")); }
		
		#$this->wtfarrey($where);
	}
	
	#$this->wtfarrey($data);
}

############################################################################################
############################################################################################
#						CRON!!!
############################################################################################
############################################################################################

//Сохранение настроек
public function saveFormCron($data){
	#$this->wtfarrey($data);
	//Обновляем инфу по времени крона.
	if(empty($data['timezone'])){ $data['timezone'] = '+0';} 
		$this->db->query("UPDATE `".DB_PREFIX."pars_cron` SET timezone ='".$this->db->escape($data['timezone'])."'");

	if(!empty($data['cron_list'])){
		//обновляем информацию о заданиях.
		foreach($data['cron_list'] as $cron){

			//приводим в поряд даты перед сохранением.
			$cron['time_day'] = preg_replace('#[^0-9-*]#', '', $cron['time_day']);
			$cron['time_week'] = preg_replace('#[^0-9-*]#', '', $cron['time_week']);
			$cron['time_hour'] = preg_replace('#[^0-9-*]#', '', $cron['time_hour']);
			if(empty($cron['pause'])){ $cron['pause'] = 0;}
			if(empty($cron['timeout']) && $cron['timeout'] != '0'){ $cron['timeout'] = 4;}
			if(empty($cron['cache_page'])){ $cron['cache_page'] = 0;}

			$this->db->query("UPDATE `".DB_PREFIX."pars_cron_list` SET 
				`on`='".$this->db->escape($cron['on'])."',
				`timeout`='".$this->db->escape($cron['timeout'])."',
				`time_day`='".$this->db->escape($cron['time_day'])."',
				`time_week`='".$this->db->escape($cron['time_week'])."',
				`time_hour`='".$this->db->escape($cron['time_hour'])."',
				`thread`='".$this->db->escape($cron['thread'])."',
				`pause`='".$this->db->escape($cron['pause'])."',
				`cache_page`='".$this->db->escape($cron['cache_page'])."',
				`sort`='".$this->db->escape($cron['sort'])."' 
				WHERE id = '".(int)$cron['id']."'
				");


			//удаляем сушествующие записи на крон.
			$this->db->query("DELETE FROM `".DB_PREFIX."pars_cron_tools` WHERE task_id = ".$cron['id']);

			//запись доп заданий.
			if(!empty($cron['ptts'])){

				//перебераем все задания и записываем в базу. 
				foreach($cron['ptts'] as $ptts){

					$this->db->query("INSERT INTO `".DB_PREFIX."pars_cron_tools` SET 
						task_id=".(int)$cron['id'].", 
						pt_id =".(int)$ptts['pt_id'].", 
						when_do =".(int)$ptts['when_do']);

				}
			}
		}
	}
}

//Получаем настройки стрницы крон.
public function getCronPageInfo(){
	
	//статус крона
	$cron_main = $this->getCronMain();

	//получаем данные с крона, точнее список заданий
	$crons = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_cron_list` ORDER BY id ASC");
	$data['crons'] = $crons->rows;
	#$this->wtfarrey($data['crons']);
	$action['pr_grab']  = [
													0 => 'Не очишать Ссылки на товары', 
													1 => 'Удалить Ссылки на товары перед началом сбора',
													2 => 'Удалить Cсылки очереди сканирования перед началом сбора',
                    			3 => 'Удалить Cсылки на товар и очередь сканирования перед началом сбора',
												];
	$action['pr_csv'] = [0 => 'Не удалять прайс лист', 1 => 'Удалить прайс лист в начала работы крона'];
	$action['pr_im']  = [1 => 'Добавлять', 2 => 'Обновлять', 3 => 'Добавлять и обновлять'];
	$task = [1 => 'Сбор ссылок', 2 => 'Парсинг в CSV', 3 => 'Парсинг в ИМ', 4 => 'Парсинг в кэш', 5 => 'Обработчик XML/YML',0 => 'Задание без парсинга'];

	//получаем список всех тулсов.
	$data['patterns'] = $this->cronGetAllPatterns();
	#$this->wtfarrey($data['patterns']);
	$data['patterns_json'] = json_encode($data['patterns']);

	//приводим в порядок данные.
	#$this->wtfarrey($data['crons']);
	foreach ($data['crons'] as &$cron) {
		#$this->wtfarrey($cron);

		//задание приводим в порядок.
		$cron['task_name'] = $task[$cron['task']];

		//действия приводим в порядок.
		if($cron['task'] == 1){
			$cron['action_name'] = $action['pr_grab'][$cron['action']];
		}elseif($cron['task'] == 2){
			$cron['action_name'] = $action['pr_csv'][$cron['action']];
		}elseif($cron['task'] == 3){
			$cron['action_name'] = $action['pr_im'][$cron['action']];
		}else{
			$cron['action_name'] = '';
		}

		//информация для таблицы

		//Колока времени запуска.
		$cron['table_time_srt'] = $this->cronMadeTimeToTable($cron);
		
		//колонка состояния
		$cron['table_on'] = '';
		if($cron['on']){
			$cron['table_on'] = '<span class="text-success"><b>Вкл</b></span>';
		}else{
			$cron['table_on'] = '<span class="text-warning"><b>Выкл</b></span>';
		}

		//Колона информации тайм аута.
		$cron['table_timeout'] = 'Не блокирует';
		if($cron['time_end'] !=0){
			$check_time_end = $cron['time_end'] + $cron['timeout'] * 60**2;
			//делаем сравнение.
			if($cron['status'] != 'run' && time() < $check_time_end){
				$cron['table_timeout'] = '<span class="text-danger"><b>'.gmdate("H:i:s", $check_time_end+$cron_main['timezone']).'</b></span>';
			}

		}

		//Колонка информации
		if(!empty($cron['time_end'])){ 
			$cron['time_end'] = gmdate("Y-m-d H:i:s", $cron['time_end']+$cron_main['timezone']); 
		}else {
			$cron['time_end'] = '';
		}

		$cron['table_info'] = '';
		if($cron['status'] == 'end' && (empty($cron['time_end']))){
			$cron['table_info'] = 'Ожидает первый запуск';
		}elseif($cron['status'] == 'end' && (!empty($cron['time_end']))) {
			$cron['table_info'] = 'Выполнено '.$cron['time_end'];
		}elseif($cron['status'] == 'run'){
			$cron['table_info'] = '<span class="text-danger"><b>Ожидает завершения</b></span>';
		}

		//информация по ссылкам
		if($cron['task'] == '1' || $cron['task'] == '5'){

			$cron['table_link_stat'] = '';
			$table_link_done = $this->db->query("SELECT COUNT(id) as count FROM `".DB_PREFIX."pars_sen_link` WHERE `dn_id`=".(int)$cron['dn_id']." AND scan_cron = 0");
			$table_link_done = $table_link_done->row['count'];
			$table_link_wait = $this->db->query("SELECT COUNT(id) as count FROM `".DB_PREFIX."pars_sen_link` WHERE `dn_id`=".(int)$cron['dn_id']." AND scan_cron = 1");
			$table_link_wait = $table_link_wait->row['count'];

			$cron['table_link_stat'] = '<span class="text-success"><b>'.$table_link_done.'</b></span> / <span class="text-warning"><b>'.$table_link_wait.'</b></span> | '.($table_link_done+$table_link_wait);

		}else{
			$cron['table_link_stat'] = '';
			$table_link_done = $this->db->query("SELECT COUNT(id) as count FROM `".DB_PREFIX."pars_link` WHERE `dn_id`=".(int)$cron['dn_id']." AND scan_cron = 0");
			$table_link_done = $table_link_done->row['count'];

			$table_link_wait = $this->db->query("SELECT COUNT(id) as count FROM `".DB_PREFIX."pars_link` WHERE `dn_id`=".(int)$cron['dn_id']." AND scan_cron = 1");
			$table_link_wait = $table_link_wait->row['count'];

			$cron['table_link_stat'] = '<span class="text-success"><b>'.$table_link_done.'</b></span> / <span class="text-warning"><b>'.$table_link_wait.'</b></span> | '.($table_link_done+$table_link_wait);
		}

		//подготавливаем данные для доп заданий.
		$cron['tools'] = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_cron_tools` WHERE `task_id`=".(int)$cron['id']." ORDER BY id");
		//записываем колво задаений
		$tools_last_id = $cron['tools']->num_rows;
		
		$cron['tools'] = $cron['tools']->rows;
		
		$data['tools_last_key'][$cron['id']] = $tools_last_id;
	}


	//Кнопка включения выключения крона
	if($cron_main['permit'] == 'run') {
		$data['cron_button']['text'] = 'Крон включен => Отключить';
		$data['cron_button']['class'] = 'btn btn-success';
	} else {
		$data['cron_button']['text'] = 'Крон отключен => Включить';
		$data['cron_button']['class'] = 'btn btn-danger';
	}

	$data['cron_permit'] = $cron_main['permit'];

	$dn_list = $this->db->query("SELECT `dn_id`, `dn_name` FROM `".DB_PREFIX."pars_setting`");
	$data['dn_list'] = array_column($dn_list->rows, 'dn_name', 'dn_id');
	
	//Время сайта
	$data['time_machin'] = '<samp class="text-warning">'.gmdate("H:i:s", time()).'</samp>';
	if($cron_main['timezone'] != '+0'){
		$data['time_machin'] = '<samp class="text-success">'.gmdate("H:i:s", time()+$cron_main['timezone']).'</samp>';
	}
	//Время которое выбрал пользователь.
	$data['select_time'] = $cron_main['timezone'];
	//Создаем массив с временными зонами пользователей.
	$data['user_times'] = [
			                    "+0" => 'Выбор часового пояса',
			                    "+3600" => gmdate('H:i:s', time() + 3600),
			                    "+7200" => gmdate('H:i:s', time() + 7200),
			                    "+10800" => gmdate('H:i:s', time() + 10800),
			                    "+14400" => gmdate('H:i:s', time() + 14400),
			                    "+18000" => gmdate('H:i:s', time() + 18000),
			                    "+21600" => gmdate('H:i:s', time() + 21600),
			                    "+25200" => gmdate('H:i:s', time() + 25200),
			                    "+28800" => gmdate('H:i:s', time() + 28800),
			                    "+32400" => gmdate('H:i:s', time() + 32400),
			                    "+36000" => gmdate('H:i:s', time() + 36000),
			                    "+39600" => gmdate('H:i:s', time() + 39600),
			                    "+43200" => gmdate('H:i:s', time() + 43200),
			                    "-3600" => gmdate('H:i:s', time() - 3600),
			                    "-7200" => gmdate('H:i:s', time() - 7200),
			                    "-10800" => gmdate('H:i:s', time() - 10800),
			                    "-14400" => gmdate('H:i:s', time() - 14400),
			                    "-18000" => gmdate('H:i:s', time() - 18000),
			                    "-21600" => gmdate('H:i:s', time() - 21600),
			                    "-25200" => gmdate('H:i:s', time() - 25200),
			                    "-28800" => gmdate('H:i:s', time() - 28800),
			                    "-32400" => gmdate('H:i:s', time() - 32400),
			                    "-36000" => gmdate('H:i:s', time() - 36000),
			                    "-39600" => gmdate('H:i:s', time() - 39600),
                    			"-43200" => gmdate('H:i:s', time() - 43200),
												];
	

	//переводим в json ключи заданий
	//если еше нет заданий
	if(empty($data['tools_last_key'])){
		$data['tools_last_key'] = json_encode($data['tools_last_key'][0] = 0);
	}else{
		$data['tools_last_key'] = json_encode($data['tools_last_key']);
	}
	#$this->wtfarrey($data);
	return $data;
}

//добавить задание
public function cronAddTask($data){

	//приводим в порядок данные для создания крон задачи.
	$dn_id = (int)$data['cron_add_dn'];
	$task = (int)$data['cron_add_task'];
  $action_1 = (int)$data['cron_add_action_1'];
  $action_2 = (int)$data['cron_add_action_2'];
  $action_3 = (int)$data['cron_add_action_3'];
  $action = 0;
  
  $permit = 1;

  if($task == 1){
  	$action = $action_1;
  } elseif($task == 2) {
  	$action = $action_2;
  } elseif($task == 3){
  	$action = $action_3;
  }

  if(empty($dn_id)){
  	$permit = 0;
  	$this->session->data['error'] = ' Не выбран проект для создания задания!';
  }elseif(empty($task)){
  	$permit = 0;
  	$this->session->data['error'] = ' Не выбрано задание для проекта!';
  }
  
	//Создаем пустую болванку.
	if($permit){
		$this->db->query("INSERT INTO `".DB_PREFIX."pars_cron_list` SET `dn_id`='".$dn_id."', `task`='".$task."', `action`='".$action."'");
	}
}

//Удаление задание
public function cronDelTask($data){
	#$this->wtfarrey($data);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_cron_list` WHERE `id` = '".(int)$data['task_del']."'");
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_cron_tools` WHERE `task_id` = '".(int)$data['task_del']."'");
}

//Приведения времени к определенному формату.
public function preparinTimeToCron($str){
	$time = ['0' => 0, '1' => 60];
	//если время не равно * тогда начинаем колдавать.
	if($str != '*'){
		//делим строку на массив.
		$time = explode('-', $str);
		//на вский случай приводим к числу
		$time[0] = (int)$time[0];
		//ключ 0 нижная граница ключ 1 верхняя, если вернхей нету значит она равна нижней
		if(empty($time[1])){ $time[1] = $time[0]+1; }
	}
	return $time;
}

//Проверяем пришло ли время выполнять задание, или нет.
public function chackTimeToCron($task, $main_cron){
	$answer = 0;
	$time_server['now'] = time()+$main_cron['timezone'];
	$task['time_end'] = $task['time_end']+$main_cron['timezone'];
	$time_server['time_day'] = gmdate('d', $time_server['now']);
	$time_server['time_week'] = gmdate('N', $time_server['now']);
	$time_server['time_hour'] = gmdate('H', $time_server['now']);
	
	#$this->wtfarrey($time_server);
	#$this->wtfarrey($task);

	if( ($time_server['time_hour'] >= $task['time_hour'][0]) && ($time_server['time_hour'] < $task['time_hour'][1]) ){
		#$this->wtfarrey('час совпал');
		$answer = 1;
		#Дополнительно проверяем период.
		if($task['status'] == 'end' && ( ($time_server['now'] - $task['time_end']) < ($task['timeout'] * 60**2) ) ){
			$answer = 0;
		}
	}

	if( ($answer == 1) && ($time_server['time_week'] >= $task['time_week'][0]) && ($time_server['time_week'] < $task['time_week'][1]) ){
		$answer = 1;
		#$this->wtfarrey('неделя отработала');
	} else {
		$answer = 0;
	}

	if( ($answer == 1) && ($time_server['time_day'] >= $task['time_day'][0]) && ($time_server['time_day']< $task['time_day'][1]) ){
		$answer = 1;
		#$this->wtfarrey('День сработал');
	} else {
		$answer = 0;
	}

	#$this->wtfarrey($answer);
	return $answer;
}

//Точка входа крона
public function cronStart(){
	
	//Получяае получаем право на выполнение 
	$main_cron = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_cron");
	$main_cron = $main_cron->row;

	//блок отвечающий за отсеевание запроса на создание второго потока парсинга. 
	//так же данный блок фикси аварийное завершение скрипта со стороны хостинга. 
	if($main_cron['work'] > 0){

		//Получаем время сейчас что бы проверить не зависло ли значение.
		if( (time() - $main_cron['work']) > 300 ){ 
			$main_cron['work'] = 0;
		}else{
			echo "Запуск отменен, крон предполагает что один из процессов не завершен.<br>
			Если процесс парсинга не идет, а вы видите это сообщение, то возможно выполнение скрипта было остановлено аварийно.<br>
			Блокировка выполнение будет снята через <b style='color: #a94442;'>".gmdate("H:i:s", 300 - (time() - $main_cron['work']) ) ."</b>";
		}

	}

	if( !empty($main_cron) && ($main_cron['permit'] == 'run' && $main_cron['work'] == '0') ){

		//Получяае список актуальных заданий.
		$cron_list = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_cron_list WHERE `on` != 0 ORDER BY sort")->rows;

		#$this->wtfarrey($cron_list);
		//проверяем все задания нужно ли что то делать.
		foreach($cron_list as $task){

			$task['time_day'] = $this->preparinTimeToCron($task['time_day']);
			$task['time_week'] = $this->preparinTimeToCron($task['time_week']);
			$task['time_hour'] = $this->preparinTimeToCron($task['time_hour']);

			//Получаем разрешение на работу.
			$task['permit'] = $this->chackTimeToCron($task, $main_cron);
			#$this->wtfarrey($task['permit']);

			//Время пришло подаван!
			if($task['permit']){
				$this->cronBlocking();
				$this->cronController($task);
			}

		}

	}

}

//Фунция составления человеко понятной даты выполнения для таблицы
public function cronMadeTimeToTable($task){
	$str = [];
	$time['hour'] = $this->preparinTimeToCron($task['time_hour']);
	$time['day'] = $this->preparinTimeToCron($task['time_day']);
	$time['week'] = $this->preparinTimeToCron($task['time_week']);

	if($time['hour'][1] > 23){ $time['hour'][1] = 23;}
	if($time['day'][0] < 1){ $time['day'][0] = 1;}
	if($time['day'][1] > 31){ $time['day'][1] = 31;}
	if($time['week'][0] < 1){ $time['week'][0] = 1;}
	if($time['week'][1] > 7){ $time['week'][1] = 7;}

	if($time['hour'][0] == $time['hour'][1]){
		$str = 'В <b>'.$time['hour'][0].'</b>ч |';
	}else{
		$str = 'С <b>'.$time['hour'][0].'</b> до <b>'.$time['hour'][1].'</b>ч |';
	}

	if($time['week'][0] == $time['week'][1]){
		$str .= ' в <b>'.$time['week'][0].'</b>й день недели |';
	}else{
		$str .= ' с <b>'.$time['week'][0].'</b> по <b>'.$time['week'][1].'</b>й день недели |';
	}

	if($time['day'][0] == $time['day'][1]){
		$str .= ' в <b>'.$time['day'][0].'</b>й день месяца';
	}else{
		$str .= ' с <b>'.$time['day'][0].'</b> по <b>'.$time['day'][1].'</b>й день месяца';
	}

	return $str;
}

//Активация задания к выполнению
public function cronActivateTask($task){
	$time = time();
	$first_start = 0;
	//Дополнительные задания, если есть перед началом контролер выполнит.
	$this->cronToolsController($task, 1);

	//А теперь меняем статус задания
	$this->db->query("UPDATE `".DB_PREFIX."pars_cron_list` SET `status` = 'run', `time_end`='".$time."' WHERE `id`=".$task['id']);
	
	//опередяем тип задания и обнуляем список заданий. 
	if($task['task'] == 1 || $task['task'] == 5){
		$this->db->query("UPDATE `".DB_PREFIX."pars_sen_link` SET `scan_cron` = 1 WHERE `dn_id` = ".$task['dn_id']);
	}else{
		$this->db->query("UPDATE `".DB_PREFIX."pars_link` SET `scan_cron` = 1 WHERE `dn_id` = ".$task['dn_id']);
	}

	//если задание парсинга в CSV проверяем нужно удалять прайс или нет. 
	if($task['task'] == 2 && $task['action'] == 1){

		$this->delFile($task['dn_id']);

	} elseif($task['task'] == 1){ //если задание сбор ссылок и выставлено удалять ссылки удаляем.
		# 0 - Ничего не делать. 1 - удалить ссылки на товар. 2 - удалить ссылки очереди. 3- удалить ссылки и товара и очереди.
		if($task['action'] == 1){

			$this->DelParsLink($task['dn_id']);

		}elseif($task['action'] == 2){

			$this->DelParsSenLink($task['dn_id']);

		}elseif($task['action'] == 3){

			$this->DelParsLink($task['dn_id']);
			$this->DelParsSenLink($task['dn_id']);

		}
		
		$first_start = 1;

	} elseif($task['task'] == 5){

		//удаляем файлы нарезанного xml
		$this->xmlDelFiles($task['dn_id']);

		//Удаляем ссылки на товар.
		$this->DelParsLink($task['dn_id']);
	
	}

	return $first_start;
}

//фунция перезапуска крона.
public function cronRestart(){
	$url = HTTP_SERVER.'sp_cron.php';

	$uagent = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);     // возвращает веб-страницу
	curl_setopt($ch, CURLOPT_HEADER, 0);             // возвращает заголовки
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);     // переходит по редиректам
	curl_setopt($ch, CURLOPT_ENCODING, "");          // обрабатывает все кодировки | Проблемы в понимании этой опции. Отключил
	curl_setopt($ch, CURLOPT_USERAGENT, $uagent);    // useragent
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);    // таймаут соединения
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);            // таймаут ответа
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);          // останавливаться после 10-ого редиректа
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //Отключить проверку сертификата.
	#curl_setopt($ch, CURLOPT_USERPWD, 'login:password');
	
	//для минимальной задержки вставляю запись сюда.
	$this->cronUnbloking();

	curl_exec( $ch );
	curl_close( $ch );
	exit();
}

//Фунция завершения работы задания.
public function cronTaskFinish($task){

	$this->cronToolsController($task, 2);

	$time = time();
	//Ставит состояние старт заданию
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron_list SET `time_end` = '".$this->db->escape($time)."', `status` = 'end' WHERE id = ".(int)$task['id']);
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron_tools SET `scan` = 0 WHERE task_id = ".(int)$task['id']);
	$this->cronUnbloking();
}

//Включить выключить крон.
public function cronOnOff($data){
	#$this->wtfarrey($data);
	if($data['cron_permit'] == 'stop'){ 
		$data['cron_permit'] = 'run';
		$work = '0'; 
	} else { 
		$data['cron_permit'] = 'stop';
		$work = '1';
	}

	$this->db->query("UPDATE ".DB_PREFIX."pars_cron SET `permit` = '".$this->db->escape($data['cron_permit'])."', work = ".(int)$work);
}

//Статус крона.
public function getCronMain(){
	//статус крона
	$cron = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_cron`");
	return $cron->row;
}

//принудительный рестарт задания от пользователя.
public function cronRestartTaskFromUser($task_id){

	//Получаем данные об задании.
	$task = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_cron_list WHERE id =".(int)$task_id);
	$task = $task->row;

	//Помечаем задание как готовое к выполнению.
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron_list SET status = 'end', time_end = '0' WHERE id =".(int)$task_id);

	//Помечаем все ссылки как не просканированные.
	if($task['task'] == 1 || $task['task'] == 5){
		$this->db->query("UPDATE ".DB_PREFIX."pars_sen_link SET scan_cron = 1 WHERE dn_id =".(int)$task['dn_id']);
	}else{
		$this->db->query("UPDATE ".DB_PREFIX."pars_link SET scan_cron = 1 WHERE dn_id =".(int)$task['dn_id']);
	}
}

//Блокировка создания второго потока. Запрешаем выполнение пока work стоит 1
public function cronBlocking(){
	//запрос на блокировку процесса
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron SET work = ".time());
}

//разблокировать выполнение крона.
public function cronUnbloking(){
	//запрос на разблокировку процесса
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron SET work = 0");
}

//получение всех шаблонов заданий в формате Json | ПОВТОРНО НЕ ИСПОЛЬЗОВАТЬ!
public function cronGetAllPatterns(){
	$data = [];

	$patterns = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_tools_pattern");
	if($patterns->num_rows > 0){
		$data = $patterns->rows;
	}

	#$this->wtfarrey($json);
	return $data;
}

//преобразуем строку доп заданий в массив
public function cronMadeToolsArrey($str){
	$tools = explode(',', $str);
		
	//проверяем есть ли задания.
	if(!empty($tools[0])){
		
		foreach($tools as $key_last => $ptt){
			if(!empty($ptt)){
				$ptt = explode('-', $ptt);
					$tools[$key_last] = ['pt_id' => $ptt[0], 'when' => $ptt[1]];
			}else{
				unset($tools[$key_last]);
			}
		}

	}else{
		$tools = [];
	}

	return $tools;
}

//контроллер выполнения ДОП заданий в кроне. $when = 1 (перед заданием), $when = 2 (после задания)
public function cronToolsController($task, $when_do){
	
	//получаем из базы список всех заданий что нужно выполнить перед началом крона. Задания что еше не делались. 
	$toolse = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_cron_tools WHERE task_id=".(int)$task['id']." AND when_do =".(int)$when_do." AND scan = 0 ORDER BY id");

	//если задания есть то начинаем их выполнять.
	if($toolse->num_rows > 0){

		foreach ($toolse->rows as $tools) {
			
			//получаем данные о патерне.
			$pattern = $this->toolGetPattern($tools['pt_id']);
			
			//отправляем задание на вполнение.
			$this->toolControlerFunction($pattern['setting'], $task['dn_id'], $who = 'cron');
			
			//помечаем записываем что задание было выполнено.
			$this->cronMarkTools($tools['id']);

		}
	}

}

//помеччаем задание как выполненое. 
public function cronMarkTools($cron_tools_id){
	//запрос на помечание задания как выполненое.
	$this->db->query("UPDATE ".DB_PREFIX."pars_cron_tools SET scan = 1 WHERE id=".$cron_tools_id);
}

//Основная фунция выполнения крона. А точнее выполнение одного задания. 
public function cronController($task){
	#$this->wtfarrey($task);
	//Получаем ключевые значения.
	$dn_id = (int)$task['dn_id'];
	//Если парсинг в им то берем отдельный ннастройки. 
	if($task['task'] == 3){
		$setting = $this->getSettingToProduct($dn_id);
		//прави выбранное действие.
		$setting['action'] = $task['action'];
	}elseif($task['task'] == 5){
		$setting = $this->getSetting($dn_id);
		$pars_xml = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_xml WHERE `dn_id`=".(int)$dn_id)->row;
	}else{
		$setting = $this->getSetting($dn_id);
	}

	//применяем отдельные настройки крона.
	$setting['thread'] = $task['thread'];
	$setting['pars_pause'] = $task['pause'];

	//Собственные скрипты. Получаем список заданий для выполнения
	if($setting['scripts_permit']){
		$script_tasks = $this->scriptGetTasksToExe($dn_id);
		$setting['thread'] = 1;#Если включено использование скриптов модуль работает в одном потоке. 
	}

	//проверяем первый запуск задания  или нет. 
	if($task['status'] == 'end'){
		//Выполнить фунцию подготовки к старту
		$first_start = $this->cronActivateTask($task);
	}

	//прроверяем не нажали ли стоп.
	$main_cron = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_cron");
	//Проверяем не остановил ли выполнение крона пользователь!
	if($main_cron->row['permit'] == 'stop'){ 
		$this->cronUnbloking();
		exit('Принудительная остановка выполнения крона!');
	} 

	# Типы заданий
	# 1 - Сбор ссылок | 2 - пасинг в csv | 3 - парсинг в ИМ | 4 - Прасинг в Кеш | 5 - Разбор xml
	//Если задание связано с парсингом в csv
	if($task['task'] == 1){

		//проверяем первый запуск крона или нет. 0 - или нет переменной это не первый, 1 - первый
		if(!empty($first_start)){
			//ох и чихню я тут пишу :( 
			//Подставляем стартовую ссылку для начала сбора
			$urls[] = $setting['start_link'];

		}else{

			$links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_sen_link WHERE scan_cron = 1 AND `dn_id`=".(int)$dn_id." LIMIT 0,5");
			
			if($links->num_rows > 0){
				//Блок многопоточности. берем нужное количество ссылок.
				$urls = [];
				foreach($links->rows as $key => $url){
					if($key < $setting['thread']){ $urls[] = $url['link']; } else { break; }
				}
			
			}else{
				//закончились ссылочки
				$this->cronTaskFinish($task);
				//Закончились ссылки перезапускаем крон. И завершаем выполнение скрипта
				$this->cronRestart();
			}

		}

		//получаем настройки браузера.
	  $browser = $this->getBrowserToCurl($dn_id);
	  //Подменяем значения кеширования из настроек крона. 
	  $browser['cache_page'] = $task['cache_page'];

	  //Отправка данных на собственные скрипты. 
		if(!empty($script_tasks)){

			$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
			$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
			$setting = $script_data['setting']; 
			$browser = $script_data['browser'];
			$urls = $script_data['urls'];
			unset($script_data);
		
		}

		//делаем мульти запрос
		$datas = $this->requestConstructorCron(0, $urls, $dn_id, $browser, $setting, $task, 0);

		//Обрабатываем данные с мульти запроса. 
		foreach($datas as $key => $data){
			//передаем на обработку данные
			$this->ParsLink($data, $setting, $dn_id);
		}

	}elseif($task['task'] == 2){
		$settingcsv = $this->getSettingCsv($dn_id);
	  //Если настройки csv несделаны отдаем ошибку что форма не настроена.
		if(empty($settingcsv)){
			$this->cronTaskFinish($task);
			$this->log('CronCsvNullForm', $log = '', $dn_id);
			$this->cronRestart();
		}

		//получаем ссылки для работы.
		$links = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_link WHERE dn_id=".(int)$dn_id." AND `scan_cron`=1 ORDER BY id ASC LIMIT 0,5");

		//если ссылки закончились заканчиваем этот балаган.
		if($links->num_rows == 0){ 
			//закончились ссылочки
			$this->cronTaskFinish($task);
			//Закончились ссылки перезапускаем крон. И завершаем выполнение скрипта
			$this->cronRestart();	
		} else {

			//собираем массив ссылок для мульти запроса.
	  	$urls = [];
	  	foreach($links->rows as $key => $url){
	  		if($key < $setting['thread']) {$urls[] = $url['link']; } else { break; }
	  	}

	  	//получаем настройки браузера.
	  	$browser = $this->getBrowserToCurl($dn_id);
	  	//Подменяем значения кеширования из настроек крона. 
	  	$browser['cache_page'] = $task['cache_page'];

	  	//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){

				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
				$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				$urls = $script_data['urls'];
				unset($script_data);
			
			}

	  	//делаем мульти запрос
			$datas = $this->requestConstructorCron(1, $urls, $dn_id, $browser, $setting, $task, 0);

	  	//Далее разбираем данные из мульти курла и делаем все нужные записи.
	  	foreach($datas as $data){

	  		//Получаем разрешения на действия.
				if(!empty($setting['grans_permit'])){
					//плохая практика но что поделать, дергаем данные парсинга в ИМ
					$form = $this->preparinDataToStore($data, $dn_id);
					$permit_grans = $this->checkGransPermit($form, $setting, $dn_id);
					#$this->wtfarrey($permit_grans);
					//проверяем массив допуска и сравниваем с выбранным действием. 
					if( empty($permit_grans[4]['permit'])){ 
						$this->log('NoGranPermit', $permit_grans[4]['log'], $dn_id);
						continue; 
					}
				}

	  		$html = $data['content'];
	  		$csv = [];
	  		$csv = $this->changeDataToCsv($html, $settingcsv, $data['url'], $dn_id);

  			//преобразовывем данные для csv
	  		$csv['value'] = $this->transformCsv($csv['value']);

				//Отправка данных на собственные скрипты. 
				if(!empty($script_tasks)){

					//получаем данные границы.
					if(empty($form)){ $form = $this->preparinDataToStore($data, $dn_id); }
					$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'csv'=>$csv, 'script_gran'=>$form['script_gran'], 'url'=>$data['url']];
					unset($form);
					$script_data = $this->scriptController(4, $dn_id, $script_tasks, $script_data);
					$setting = $script_data['setting']; 
					$browser = $script_data['browser'];
					$csv = $script_data['csv'];
					unset($script_data);
				
				}

	  		//записываем данные в csv
	  		$this->createCsv($csv, $setting, $dn_id);

	  		//Отправка данных на собственные скрипты. 
				if(!empty($script_tasks)){

					//получаем данные границы.
					if(empty($form)){ $form = $this->preparinDataToStore($data, $dn_id); }
					$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'csv'=>$csv, 'script_gran'=>$form['script_gran'], 'url'=>$data['url']];
					unset($form);
					$script_data = $this->scriptController(5, $dn_id, $script_tasks, $script_data);
					$setting = $script_data['setting']; 
					$browser = $script_data['browser'];
					unset($script_data);
				
				}
  		
	  	}
		}
	
	} elseif ($task['task'] == 4) {

		//пришел задание парсить в кеш.
		$links = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_link WHERE dn_id=".(int)$dn_id." AND `scan_cron`=1 ORDER BY id ASC LIMIT 0,5");

		#Если ссылок нету завершаем работу модуля.
	  if($links->num_rows == 0){
	  	//закончились ссылочки
			$this->cronTaskFinish($task);
	    //Закончились ссылки перезапускаем крон. И завершаем выполнение скрипта
			$this->cronRestart();	
	  }else{

	  	//собираем массив ссылок для мульти запроса.
	  	$urls = [];
	  	foreach($links->rows as $key => $url){
	  		if($key < $setting['thread']) {$urls[] = $url['link']; } else { break; }
	  	}

	  	$browser = $this->getBrowserToCurl($dn_id);
	  	$browser['cache_page'] = 2;

	  	//делаем мульти запрос
			$datas = $this->requestConstructorCron(1, $urls, $dn_id, $browser, $setting, $task, 0);

			//Здесь должно что то делатся.
			//Но это кеш по этому ничего ;-)  		
	  }

	} elseif ($task['task'] == 3) {

		if($setting['sid'] == 'sku' && $setting['r_sku'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();	
		}elseif($setting['sid'] == 'name' && $setting['r_name'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'upc' && $setting['r_upc'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'ean' && $setting['r_ean'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'jan' && $setting['r_jan'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'isbn' && $setting['r_isbn'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'mpn' && $setting['r_mpn'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}elseif($setting['sid'] == 'location' && $setting['r_location'] == 1){
			$this->cronTaskFinish($task);
			$this->cronRestart();
		}

		//Получаем списк неспарсенных ссылок.
		$links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `dn_id`=".(int)$dn_id." AND `scan_cron`=1 ORDER BY id ASC LIMIT 0,5");

		//Проверяем закончился ли парсинг.
		if($links->num_rows == 0){
			//закончились ссылочки
			$this->cronTaskFinish($task);
			//Закончились ссылки перезапускаем крон. И завершаем выполнение скрипта
			$this->cronRestart();	
		}else{

			//Блак многопоточности. берем нужное количество ссылок.
			$urls = [];
			foreach($links->rows as $key => $url){
				if($key < $setting['thread']){ $urls[] = $url['link']; } else { break; }
			}

			$browser = $this->getBrowserToCurl($dn_id);
	  	$browser['cache_page'] = $task['cache_page'];

	  	//Отправка данных на собственные скрипты. 
			if(!empty($script_tasks)){
				
				$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'urls'=>$urls];
				$script_data = $this->scriptController(1, $dn_id, $script_tasks, $script_data);
				$setting = $script_data['setting']; 
				$browser = $script_data['browser'];
				$urls = $script_data['urls'];
				unset($script_data);
			
			}

	  	//делаем мульти запрос
			$datas = $this->requestConstructorCron(1, $urls, $dn_id, $browser, $setting, $task, 0);

			//перебераем данные с мулти запроса.
			foreach($datas as $data){

				//Ссылка
				$link = $data['url'];
				//Прасим данные
				$form = $this->preparinDataToStore($data, $dn_id);
				//Получаем разрешения на границы
				if(!empty($setting['grans_permit'])){
					$permit_grans = $this->checkGransPermit($form, $setting, $dn_id);

					//проверяем массив допуска и сравниваем с выбранным действием. 
					if($setting['action'] != 3 && empty($permit_grans[$setting['action']]['permit'])){ 
						$this->log('NoGranPermit', $permit_grans[$setting['action']]['log'], $dn_id);
						continue; 
					}
				}

				//Получаем разрешения на действия.
				$permit = $this->checkProduct($form, $setting, $link, $dn_id);

				//Отправка данных на собственные скрипты. 
				if(!empty($script_tasks)){

					$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'form'=>$form, 'permit'=>$permit, 'url'=>$link];
					$script_data = $this->scriptController(2, $dn_id, $script_tasks, $script_data);
					$setting = $script_data['setting']; 
					$browser = $script_data['browser'];
					$permit = $script_data['permit'];
					$form = $script_data['form'];
					unset($script_data);
				
				}

				//Проверка выбора действия.////////////////////
				// 1 -Добавлять | 2 - Обновлять | 3 - Добавлять и обновлять
				//////////////////////////////////////////////
				if($setting['action'] == 1){

					//провека допуска
					if($permit['add']['permit'] == 1){
						$pr_id = $this->addProduct($form, $link, $setting, $dn_id, $browser);
					}else{
						$log = ['sid' => $setting['sid'],	'sid_value' => $form[$setting['sid']],];
						$this->log('addProductIsTrue', $log, $dn_id);
					}

				}elseif($setting['action'] == 2){

					//провека допуска
					if($permit['up']['permit'] == 1){
						$this->updateProduct($form, $link, $setting, $dn_id, $permit['up']['pr_id'], $browser);
					}else{
						$log = [ 'sid' => $setting['sid'],	'sid_value' => $form[$setting['sid']], 'link' => $link ];
						#$this->wtfarrey($log);
						$this->log('NoFindProductToUpdate', $log, $dn_id);
					}

				}elseif($setting['action'] == 3){

					if($permit['add']['permit'] == 1){
						//проверка допуска страницы к добавлению товара, и включена ли проверка допуска
						if(!isset($permit_grans) || !empty($permit_grans[1]['permit'])){ 

							//провека допуска на добавление товара
							$pr_id = $this->addProduct($form, $link, $setting, $dn_id, $browser);

						}else{
							$this->log('NoGranPermit', $permit_grans[1]['log'], $dn_id);
						}

					}elseif($permit['up']['permit'] == 1){
						//проверка допуска страницы к обновлению товара, и включена ли проверка допуска
						if(!isset($permit_grans) || !empty($permit_grans[2]['permit'])){ 

							//проверка на обновление товара
							$this->updateProduct($form, $link, $setting, $dn_id, $permit['up']['pr_id'], $browser);

						}else{
							$this->log('NoGranPermit', $permit_grans[2]['log'], $dn_id);
						}

					}
				}

				//Отправка данных на собственные скрипты. 
				if(!empty($script_tasks)){

					if(!empty($pr_id)){ $permit['add']['pr_id'] = $pr_id; }
					$script_data = ['setting'=>$setting, 'browser'=>$browser, 'dn_id'=>$dn_id, 'form'=>$form, 'permit'=>$permit, 'url'=>$link];
					$script_data = $this->scriptController(3, $dn_id, $script_tasks, $script_data);
					$setting = $script_data['setting']; 
					$browser = $script_data['browser'];
					$permit = $script_data['permit'];
					$form = $script_data['form'];
					unset($script_data);
				
				}

			}
		}
	
	}elseif($task['task'] == 5){

		$links = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_sen_link WHERE scan_cron = 1 AND `dn_id`=".(int)$dn_id." LIMIT 0,1");
		
		if($links->num_rows > 0){
			//Блак многопоточности. берем нужное количество ссылок.
			$urls = [];
			$setting['thread'] = 1;#Принудительно в одни поток.
			foreach($links->rows as $key => $url){
				if($key < $setting['thread']){ $urls[] = $url['link']; } else { break; }
			}
		
		}else{
			//закончились ссылочки
			$this->cronTaskFinish($task);
			//Закончились ссылки перезапускаем крон. И завершаем выполнение скрипта
			$this->cronRestart();	
		}

		//получаем настройки браузера.
	  $browser = $this->getBrowserToCurl($dn_id);
	  //Подменяем значения кеширования из настроек крона. 
	  $browser['cache_page'] = $task['cache_page'];

		//делаем мульти запрос
		$datas = $this->requestConstructorCron(0, $urls, $dn_id, $browser, $setting, $task, 0);

		foreach($datas as $data){

			//передаем на обработку данные
			file_put_contents(DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/0-temp.xml', $data['content']);
  		//основная фунция по делению файла
  		$this->xmlControlSplitAndCut($pars_xml, $data['url'], $setting, $dn_id);

		}
	}

	//$path = DIR_LOGS."simplepars_id-memory-".$dn_id.".log";
	//$text = (memory_get_usage()/1000000).PHP_EOL;
	//file_put_contents($path, $text, FILE_APPEND);
	
	//Перед закрытием цикла while
	$this->timeSleep($setting['pars_pause']);
	//перезапускаем выполнение крона
	$this->cronRestart();	
}

############################################################################################
############################################################################################
#						Фунции отвечающие за поиск замену
############################################################################################
############################################################################################
//Получаем данные страницы поиск замена.
public function getReplacePage($dn_id,$param_id=''){
	$replace_links = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_link` WHERE dn_id=".(int)$dn_id." ORDER BY id ASC LIMIT 0, 1000");

	$connection = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_param WHERE id=".(int)$param_id);
	$connection = $connection->row;

	if(empty($connection)){
		$connection['id'] = 0;
		$connection['base_id'] = 0;
	}

	$replace = $this->db->query("SELECT r.id, r.dn_id, r.param_id, p.base_id, r.text_start, r.text_stop, r.rules, r.hash, r.arithm FROM ". DB_PREFIX ."pars_replace r INNER JOIN ". DB_PREFIX ."pars_param p ON r.param_id = p.id WHERE r.param_id=".(int)$param_id);
	$replace = $replace->row;

	$get_params = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_param WHERE dn_id=".(int)$dn_id." ORDER BY id ASC");
	$get_params = $get_params->rows;

	//помечаем в масиве выбранную гарницу. Нужно для оформления.
	foreach($get_params as $key => $params){
		$get_params[$key]['class'] = 'btn btn-default btn-sm btn-block';
		if($params['id'] == $connection['id']){
			$get_params[$key]['class'] = 'btn btn-success btn-sm btn-block';
		}
		if($params['id'] == $connection['base_id']){
			$get_params[$key]['class'] = 'btn btn-warning btn-sm btn-block';
		}
	}

	//если есть настройки поискз замена, привеодим их в формату вода в форму.
	
	if(!empty($replace['rules'])){
		$replace['rules'] = $this->madeRulesToPage($replace['rules']);

	}
	
	//блок предп просмотра. Если есть.
	$data['show']['text_give'] = $this->getGranFromFile($param_id, 'input_text');
	$data['show']['text_get'] = $this->getGranFromFile($param_id, 'output');

	$data['params'] = $get_params;
	$data['replace'] = $replace;
	$data['replace_links'] = $replace_links->rows;
	#$this->wtfarrey($data);
	return $data;
}

//Сохраняем правила поиск замена
public function saveReplacePage($data,$dn_id,$param_id=''){

	if(empty($param_id)){
		$this->session->data['error'] = 'Не выбран параметр парсинга';
		return;
	}

	//разбираем входные данные для поиск замены.
	$data['rules'] = $this->parseRulesToReplace($data);

	//Если правила не пустые значет кодируем в json
	if(!empty($data['rules'])){
		$data['rules'] = json_encode($data['rules']);
	}

	$this->db->query("DELETE FROM `". DB_PREFIX ."pars_replace` WHERE param_id=".(int)$param_id);
	$res = $this->db->query("INSERT INTO ". DB_PREFIX ."pars_replace SET dn_id=".(int)$dn_id.", param_id=".(int)$param_id.", text_start='".$this->db->escape($data['text_start'])."', text_stop='".$this->db->escape($data['text_stop'])."', rules='".$this->db->escape($data['rules'])."', hash=".(int)$data['hash'].", arithm='".$this->db->escape($data['arithm'])."'");

	if($res){
		$this->session->data['success'] = 'Настройки сохранены успешно.';
	}

}

public function parseRulesToReplace($data){
	#$this->wtfarrey($data);
	if(!empty($data['rules'])){
		//Вот тут немного алгоритмов. Делим правила поиск замена на массив по принзнаку переноса строки.
		$data['rules'] = explode(PHP_EOL,$data['rules']);

		//Каждую строку делим еше на массив по принцепу ( массив из 2 элементов с разделителем с экранированием)
		foreach($data['rules'] as $key => $value){
			
			//отлавливаем регулярки в поиск замену
			if(preg_match('#^\{reg\[(.*)\]\}[|]#', $value, $temp_reg)){ #если регулярка
				//Удаляем из обшей строки правило регулярки
				$value = preg_replace('#^\{reg\[(.*)\]\}#','',$value);

				//правильно делим левую и правую сторону
				$parts = preg_split('#[|]#', $value,2);

				//Возвршаем в правую сторону правило регулярки
				$parts[0] = '{reg['.$temp_reg[1].']}';
				$parts[1] = str_replace(array("\r\n", "\r", "\n"), '', $parts[1]);
				$data['rules'][$key] = $parts;

			}else{#Если не регулярка

				$parts = preg_split('/(?<![^\\\\]\\\\)\|/', str_replace(array("\r\n", "\r", "\n"), '', $value), 2);
				array_walk($parts, function(&$v) { $v = str_replace('\\|', '|', $v); });
				$data['rules'][$key] = $parts;

			}
			
		}

	}else{
		$data['rules'] = '';
	}

	return $data['rules'];
}

//Пред просмотр поиск замена
public function showReplaceText($data, $param_id){
	//Получаем информацию о типе границы парсинга
	$param = $this->db->query("SELECT * FROM `". DB_PREFIX ."pars_param` WHERE id=".(int)$param_id);
	$param = $param->row;

	#Выбираем действие в зависимости от типа границы парсинга.
	if ($param['type'] == 2) {
		
		//Получаем сырой текст, или массив в повторяющихся границах.
		$text_give_t2 = $this->getGranFromFile($param_id, 'input_arr');
		if(empty($text_give_t2)){ $text_give_t2 = [];}

		$text_get = '';
		
		foreach ($text_give_t2 as $key => $value) {

			$value = $this->findReplace($value, $param_id);

			if($key == 0){ $text_get = $value; } else { $text_get = $text_get.$param['delim'].$value;}
		}

		//Проверка на то что бы был текст в пред просмотре и в массиве.
		if(!empty($data['text_give']) && empty($text_give_t2)){
			$data['text_give'] = '';
			$text_get = '';
		}

		$this->putGranToFile($text_get, $param_id, 'output');

	} else {
		//проверяем есть ли текст для поиск замены.
		$text_get = $this->findReplace($data['text_give'], $param_id);
		$this->putGranToFile($text_get, $param_id, 'output');
		$this->putGranToFile($data['text_give'], $param_id, 'input_text');
	}

}

//Фунция парсинга для предпросмотра в поиск замене.
public function getParamShow($data, $param_id, $dn_id){

	//Сразу отработаем варианты отсуцтвия ссылок. И отсуцтвие выбранного параметра парсинга
	if(empty($data['download_link'])){
		$this->session->data['error'] = "Не выбрана ссылка для получения данных";
		return 1;
	}elseif(empty($param_id)){
		$this->session->data['error'] = "Не выбрана граница парсинга для получения данных";
		return 1;
	}

	$params = $this->getParsParams($dn_id);

	//Получаем информацию о разделителе.
	$delim = ( !empty($params[(int)$param_id]) ) ? $params[(int)$param_id] : [];
	
	$data['download_link'] = str_replace('&amp;', '&', $data['download_link']);
	//Выполняем запрос на пред просмотр.
	$urls[] = $data['download_link'];
	$datas = $this->multiCurl($urls, $dn_id);
	//пишем логи, но не проверяем ошибку она не нужна в пред просмотре.
	$curl_error = $this->sentLogMultiCurl($datas[$data['download_link']], $dn_id);
	
	//Удаляем текст полс обработки от старой ссылки.
	$output_file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_output.txt';
	//Проверяем есть ли такой файл, и удаляем
	if (file_exists($output_file)) {	unlink($output_file); }

	//передаем код страницы
	$html = $datas[$data['download_link']]['content'];

	$text_give = $this->parsParam($html, $param_id, $params);

	if(!empty($text_give)){

		if(is_array($text_give)){
			$text = '';

			foreach($text_give as $key => $value){
				$i = $key+1;
				#Выводит в поиск замену повторяющиеся границы парсинга. С разделителем.
				$text .='!========== Повторение [№'.$i.'] ========= Разделитель ['.$delim['delim'].'] ========== !'.PHP_EOL.PHP_EOL.$value.PHP_EOL.PHP_EOL;
			}

		}else{
			$text = $text_give;
		}

		$this->putGranToFile($text, $param_id, 'input_text');
		$this->putGranToFile($text_give, $param_id, 'input_arr');

	}else{#Есои при парсинге новой страницы параметр пустой. УДАЛЯЕМ
		$file_1 = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_text.txt';
		$file_2 = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_arr.txt';

		//Проверяем есть ли такой прайс.
		if (file_exists($file_1)) { unlink($file_1); }
		//Проверяем есть ли такой прайс.
		if (file_exists($file_2)) {	unlink($file_2); }

	}

}

//Фунция поиск замена
public function findReplace($value='', $param_id){

	//Поучаем значения поиск замена. Для этой границы
	$replace = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_replace WHERE param_id=".(int)$param_id);

	//преобразовываем входной текст.
	$value = html_entity_decode($value);
	#$this->wtfarrey($value);
	#если есть правила делаем обработку.
	if($replace->num_rows){
		//переносим обьект в масив для удобства.
		$replace = $replace->row;

		//если правила поиск замена не пусты, выполняем поиск замену.
		if(!empty($replace['rules'])){
			//Взврашем json в массив
			$replace['rules'] = json_decode($replace['rules']);
			#$this->wtfarrey($replace['rules']);
			foreach($replace['rules'] as $rule){

				if(isset($rule[0]) && isset($rule[1])){

					$rule[0] = $this->pregRegLeft($rule[0]);
					$rule[1] = $this->pregRegRight($rule[1]);
					#$this->wtfarrey($rule[0]);
					#$this->wtfarrey($rule[1]);
					if($rule[1] == '{upper}'){
						$value = preg_replace_callback($rule[0], function ($matches) { return mb_strtoupper($matches[0]);}, $value);
						#$this->wtfarrey($value);
					}elseif($rule[1] == '{lower}'){
						$value = preg_replace_callback($rule[0], function ($matches) { return mb_strtolower($matches[0]);}, $value);
					}else{
						$value = preg_replace($rule[0], $rule[1], $value);
					}

				}
				#$this->wtfarrey($value);

			}
		}

		//Наценка
		if(!empty($replace['arithm'])){
			$value = $this->arithmNubers($value, $replace['arithm']);
		}

		if($replace['hash'] !=0 && !empty($value)){
			$value = substr(md5($value), 0, $replace['hash']);
		}

		//Добавляем значение в началао или конец строки.
		$value = htmlspecialchars_decode($replace['text_start']).$value.htmlspecialchars_decode($replace['text_stop']);
	}
	#if($param_id == 8057){ #$this->wtfarrey($value); }
	return $value;
}

//Фунция составления герулярного выражения для левой части поиск замена
public function pregRegLeft($data){

	//Отлавливаем регулярные вырежения в правилах поиск замена
	if(preg_match('#^\{reg\[(.*)\]\}$#', $data, $reg)){

		$reg = htmlspecialchars_decode($reg[1]);

	}else{

		$reg = preg_quote(htmlspecialchars_decode($data), '#');
		//Что заменяем
		$what = ['\{skip\}','\{br\}','\{\.\}','\{\.\*\}'];
		//Чем заменяем
		$than = ['(.*?)','(\\r\\n|\\r|\\n)','(.)','(.*)'];

		//Замена
		$reg = str_replace($what, $than, $reg);
		//Формируем полноценный патерн
		$reg = '#'.$reg.'#su';
		//Зашита от дурака.
		if($reg == '##su'){ $reg = '#^SimplePars$#su';}
	}
	#$this->wtfarrey($reg);
	return $reg;
}

//Фунция составления герулярного выражения для правой части поиск замена
public function pregRegRight($data){
	//Исчим если {br}

	//Модификатор добавления переноса строки
	if(strripos($data, '{br}')!==false){
		$data = str_replace('{br}', "\r\n", $data);
	}

	$data = html_entity_decode($data);
	return $data;
}

public function arithmNubers($value='', $arithm){
	//Преобразование данных их границы в число.
	$arithm = htmlspecialchars_decode($arithm);
	$value = (float)trim(str_replace(' ','',str_replace(',','.',$value)));
	$rounds = ['','']; #Временная переменная для очистки правила от алгоритма округления
	$step = 0.01;
	$site = '%';

	//определяем условия окргуления.
	preg_match('#^\{(.*?)\}#', $arithm, $rounds);
	//Вырезаем алгоритм округления из общего правила, и приводим правило в нужный формат
	if(!empty($rounds[0])){

		//вырезаем кусок ненужный для наценки.
		$arithm = preg_replace('#^\{(.*?)\}#','',$arithm);

		//Приводим форматируем данные веденные пользователем. Да да, ведь вы все равно пишите лишние пробелы и запятые.
		$rounds[1] = trim(str_replace(' ','',str_replace(',','.',$rounds[1])));

		//Проверяем правильность ввода праила окргурления.
		if(preg_match('#^[0-9]+[,.]*[0-9]*[|]*[<>%]*$#',$rounds[1])){
			//делим правило на значение кратное которому округляем. И на условие округления
			$round = explode('|', $rounds[1]);
			$step = (float)$round[0];

			//Указываем условие округления
			if(!empty($round[1])){
				$site = $round[1];
			}
		}
	}
	
	//разделяем на количество правил.
	$arithms = explode('&', $arithm);

	//Запускаем в цикле все правила к одной гарнице
	foreach ($arithms as $arithm) {

		$formula = explode(';', $arithm);
		//Запускаем калькуляцию.
		foreach($formula as $form){
			$form = trim(str_replace(',', '.', $form));

			$break = false;
			//простой тип наценки
			if(preg_match('#^[\-\+\/\*][0-9]+[,.]?[0-9]*$#',$form)){

				//Действие
				$do = $form[0];
				$number = substr($form, 1);
				//Производим магию цифр
				switch ($do) {
					case '-':
						$value = $value - $number;
						$break = true;
						break;
					case '+':
						$value = $value + $number;
						$break = true;
						break;
					case '*':
						$value = $value * $number;
						$break = true;
						break;
					case '/':
						if($number != 0){ $value = $value / $number;	}
						$break = true;
						break;
				}

			}elseif(preg_match('#^[0-9]+[,.]?[0-9]*[\-\+\*\/][0-9]+[,.]?[0-9]*$#',$form)){

				$data = preg_split('#[\-\+\*\/]#', $form);
				$do = str_replace($data,'', $form);

				if($data[0] == $value && $do == '-'){
					$value = $value - $data[1];
					$break = true;
				}elseif($data[0] == $value && $do == '+'){
					$value = $value + $data[1];
					$break = true;
				}elseif($data[0] == $value && $do == '*'){
					$value = $value * $data[1];
					$break = true;
				}elseif($data[0] == $value && $do == '/' && $data[1] != 0){
					$value = $value / $data[1];
					$break = true;
				}

			//Сложный тип наценки
			}elseif(preg_match('#^\([0-9]+[,.]?[0-9]*\-[0-9]+[,.]?[0-9]*\)[\-\+\/\*][0-9]+[,.]?[0-9]*$#',$form)){

				//Получаем значение диапазона
				preg_match('#\((.*?)\)#', $form, $range_temp);
				$range = explode('-', $range_temp[1]);
				//Получаем действие, и number
				$form = preg_replace('#^\((.*?)\)#', '', $form);
				//Действие
				$do = $form[0];
				$number = substr($form, 1);

				//Производим магию цифр
				if($value >= $range[0] && $value <= $range[1] && $do == '-'){
					$value = $value - $number;
					$break = true;
				}elseif($value >= $range[0] && $value <= $range[1] && $do == '+'){
					$value = $value + $number;
					$break = true;
				}elseif($value >= $range[0] && $value <= $range[1] && $do == '*'){
					$value = $value * $number;
					$break = true;
				}elseif($value >= $range[0] && $value <= $range[1] && $do == '/' && $number != 0){
					$value = $value / $number;
					$break = true;
				}
			}

			//прерывание
			if($break){ break; }
		}

	}

	//Округляем. По умолчанию до двух нулей после запятой.
	if($site == ">" && $step != 0){
		$value = ceil($value / $step) * $step;
	}elseif($site == '<' && $step != 0){
		$value = floor($value / $step) * $step;
	}else{
		if($step != 0){	$value = round($value / $step) * $step; }
	}

	//Приводим число в приемлимый для csv формат
	$value = str_replace('.', ',', $value);
	#$this->wtfarrey($value);
	return $value;
}

//Фунция записи границы парсинга в файл, для пред просмотро.
public function putGranToFile($text, $param_id, $who){

	//Проверяем что бы была граница парсинга
	if($param_id > 0){
		//определяем место хранения файла границы парсинга.
		if($who == 'input_arr'){
			$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_arr.txt';
			file_put_contents($file, json_encode($text));
		}elseif($who == 'output'){
			$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_output.txt';
			file_put_contents($file, $text);
		}else{
			$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_text.txt';
			file_put_contents($file, $text);
		}
	}
}

//Фунция чтения границ парсинга для поиск замены из файла.
public function getGranFromFile($param_id, $who){
	$data = '';
	//определяем место хранения файла границы парсинга.
	if($who == 'input_arr'){

		$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_arr.txt';
		if (file_exists($file)) {
			$data = json_decode(file_get_contents($file), true);
		}

	}elseif($who == 'output'){

		$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_output.txt';
		if (file_exists($file)) {
			$data = file_get_contents($file);
		}

	}elseif($who == 'input_text'){

		$file = DIR_APPLICATION.'simplepars/replace/'.$param_id.'_input_text.txt';
		if (file_exists($file)) {
			$data = file_get_contents($file);
		}
		
	}

	return $data;
}

//фунция преобразования правил поиск замены для вывода на странице
public function madeRulesToPage($rules){
	#$this->wtfarrey($rules);
	if(!empty($rules)){
		//делаем из JSON массив php
		$rules = json_decode($rules);
		#$this->wtfarrey($rules);
		//Делаем из массива строку типа "Что_меняем|На_что_меняем"
		$data = '';
		foreach($rules as $var){
			if(isset($var[0]) && isset($var[1])){

				//проверяем если эта регулярка то никаких замен не делаем
				if(preg_match('#^\{reg\[(.*)\]\}$#', $var[0])){
					$str_rule = $var[0].'|'.$var[1];
				}else{
					$str_rule = str_replace('|','\|',$var[0]).'|'.$var[1];
				}

			}else{
				$str_rule = $var[0];
			}
			$data .= $str_rule.PHP_EOL;
		}
		//Убираем послдений ненужный перенос строки.
		$data = substr($data, 0, -1);
	
	}else{
		$data = '';
	}

	return $data;
}

############################################################################################
############################################################################################
#						Фунции связанные с Логами
############################################################################################
############################################################################################

public function saveLogSetting($data, $dn_id){
	#$this->wtfarrey($data);
	//Сохраняем настройки лога.
	if(empty($data['logs_reverse'])){ $data['logs_reverse'] = 0;}
	if(empty($data['logs_mb'])){ $data['logs_mb'] = 25;}

  $this->db->query("UPDATE `". DB_PREFIX ."pars_setting` SET
  	logs_reverse='".(int)$data['logs_reverse']."',
  	logs_mb=".(int)$data['logs_mb']."
  	WHERE `dn_id`=".(int)$dn_id);
}

//Создание лог файла
public function log($mark, $data, $dn_id){
	//Имя и адрес логов.
	$path = DIR_LOGS."simplepars_id-".$dn_id.".log";
	$text = date("Y-m-d H:i:s").'| ';

	//cURL отработал без ошибки
	if($mark == 'log_curl'){
		$text = PHP_EOL.date("Y-m-d H:i:s").'| ';
		$text .= 'Парсинг : ';

		//Определяем прокси
		if($data['browser']['proxy_use'] > 0){ $text_proxy = '| Прокси = ['.$data['browser']['proxy']['ip:port'].']';} else { $text_proxy = '';}

		if($data['errno'] == 0){

			if($data['http_code']==200){
	  		$text .='УСПЕШНЫЙ ЗАПРОС '.$text_proxy .' | Код ответа ['.$data['http_code'].'] Ссылка | '.$data['url'].PHP_EOL;
	  	}elseif($data['http_code']==404){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Страница не найдена. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==400){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Неправильный запрос. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==403){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Forbidden, доступ запрещен. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==301){
	  		$text .= 'РЕДИРЕКТ '.$text_proxy .' | Ваш запрос перенаправлен. Ответ сервера ['.$data['http_code'].'] Ссылка входа - '.htmlspecialchars_decode(trim($data['url']))
	  		.' Адрес куда перенаправлен запрос | '.htmlspecialchars_decode(trim($data['redirect_url'])).PHP_EOL;
	  	}elseif($data['http_code']==302){
	  		$text .= 'РЕДИРЕКТ '.$text_proxy .' | Ваш запрос перенаправлен. Ответ сервера ['.$data['http_code'].'] Ссылка входа - '.htmlspecialchars_decode(trim($data['url']))
	  		.' Адрес куда перенаправлен запрос | '.htmlspecialchars_decode(trim($data['redirect_url'])).PHP_EOL;
	  	}elseif($data['http_code']==423){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Locked — целевой ресурс из запроса заблокирован от применения к нему указанного метода. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==429){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Страница недоступна, слишком много запросов за короткое время. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==426){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Сайт отказывается выполнять запрос с использованием текущего протокола. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==500){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Сайт донор не может обработать запрос (Internal Server Error, внутренняя ошибка сервера). Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==502){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Донор получил не верны ответ от вышестоящего сервера. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==503){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Сайт донор недоступен на данный момент. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==504){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | Внутренняя ошибка донора (Gateway Timeout), не получен своевременный ответ. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==505){
	  		$text .= 'ОТВЕТ '.$text_proxy .' | HTTP-версия, используемая в запроcе, не поддерживается донором. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}else{
	  		$text .= 'НЕИЗВЕСТНЫЙ ОТВЕТ '.$text_proxy .' | Ответ сервера не распознан. Код ответа ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}

  	} elseif ($data['errno'] > 0){

  		$text .= 'НЕГАТИВНЫЙ ответ '.$text_proxy .' | Код ответа = '.$data['errno'].' | Текст ответа = '.$data['errmsg'].' | Ссылка - '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;

  	}
  	#Записываем, или дозаписываем данные в лог фаил
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Запрос из кеша.
	if($mark == 'log_cache'){
		$text = PHP_EOL.date("Y-m-d H:i:s").'| ';
		$text .='=>[СТРАНИЦА ЗАГРУЖЕН ИЗ КЕША] Ссылка | '.$data['url'].PHP_EOL;
		#Записываем, или дозаписываем данные в лог фаил
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Запрос из кеша.
	if($mark == 'log_gzopen'){
		if($data['errno'] == 0){
			$text = PHP_EOL.date("Y-m-d H:i:s").'| ';
			$text .='=>Сбор ссылок : УСПЕШНЫЙ ЗАПРОС | Ссылка = '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
		}else{
			$text = PHP_EOL.date("Y-m-d H:i:s").'| ';
			$text .= 'Сбор ссылок : НЕГАТИВНЫЙ ответ | Код ответа = '.$data['errno'].' | Текст ответа = '.$data['errmsg'].' | Ссылка - '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
		}
		#Записываем, или дозаписываем данные в лог фаил
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'cache_file_add'){
		$text = PHP_EOL.date("Y-m-d H:i:s").'| ';
		$text .='=>[СОЗДАН КЕШ] Ссылка | '.$data['url'].PHP_EOL;
		$text .= date("Y-m-d H:i:s").'| ->Файл кеша находится по адресу | '.$data['file'].PHP_EOL;
		#Записываем, или дозаписываем данные в лог фаил
		file_put_contents($path, $text, FILE_APPEND);
	}


	######################################### Работа с товаром ###################################
	//Добавления товара.
	if($mark == 'addProduct'){
		foreach ($data as $key => $value) {
			if($key == 0){
				$text .='->[ДОБАВЛЕН ТОВАР] ID = '.$value['pr_id'].' | Идентификатор '.$value['sid'].' = ['.$value['sid_value'].']'.PHP_EOL;
			}else{
				if(!empty($value['value']) || $value['value'] == 0){
					$text .= date("Y-m-d H:i:s").'| -->Данные | '.$value['name'].' = '.$value['value'].PHP_EOL;
				}
			}
		}
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Нету model а действие парсить модель.
	if($mark == 'NoParsModel'){
		$text .='!->[Товар не создан] : Вы выбрали действие {Парсить} в Код товара [model] Код не был найден на сайте доноре. Без кода невозможно создать товар. Рекомендуем поменять значение на {Создавать по умолчанию}. А код товара разместить в поле Артикул [sku]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Парсинг не прошел проверку по определенным границам
	if($mark == 'NoGranPermit'){
		$text .='!->[Страница НЕ обработана ] : Поскольку -'.$data.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//Нет идетификатора при добавлении товар
	if($mark == 'NoSid'){
		$text .='!->[Товар Не создан/Не обновлен] : Не спарсен идентификатора товара, '.$data['sid'].' | По ссылке '.$data['link'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Нет прошло все идентификаторы и не совпало ни с одним.
	if($mark == 'addProductNoSidCheck'){
		$text .='!->[Товар не создан] : Не один из идентификаторов не был обнаружен, возможно ошибка модуля сообщите разработчику модуля SimplePars. За ранние спасибо.'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Нет идетификатора при добавлении товар
	if($mark == 'addProductIsTrue'){
		$text .='!->[Товар не создан] : Товар с '.$data['sid'].'  = ['.$data['sid_value'].'] Уже существует в магазине и модуль его не создавал.'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Не найден товар для обновления.
	if($mark == 'NoFindProductToUpdate'){
		$text .='!->[Товар не обновлен] : В магазине не найден товар с '.$data['sid'].' = ['.$data['sid_value'].']'.' Ссылка | '.$data['link'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавления маски в дизайне товара.
	if($mark == 'addlayout'){
		$text .='->Добавлен макет в товаре, layout_id = '.$data['layout_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавления маски в дизайне товара.
	if($mark == 'uplayout'){
		$text .='->Обновлен макет в товаре на layout_id = '.$data['layout_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//URL
	if($mark == 'badUrl'){
		$text .='!->[SEO_URL не создан] : Отсутствуют данные в поле '.$data['name'].' для создания URL'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'LogAddSeoUrl'){
		$text .='->[SEO_URL Создан] : '.$data['where'].' | SEO_URL= '.$data['url'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}



	////////////////////////////Обновление товара///////////////////////
	if($mark == 'UpdateProduct'){
		$datas = $data;
		foreach($datas as $key => $data){
			if($key == 0){
				$text .='->[ОБНОВЛЕН ТОВАР] ID = '.$data['pr_id'].' | Идентификатор '.$data['sid'].' = ['.$data['sid_value'].']'.PHP_EOL;
			}else{
				$text .= date("Y-m-d H:i:s").'| -->Обновление | '.$data['name'].' = '.$data['value'].PHP_EOL;
			}
		}
		file_put_contents($path, $text, FILE_APPEND);
	}

	//не обновился товар и не добавился.
	if($mark == 'NothingDoProduct'){
		$text .='!-->Действие добавлять и обновлять товар, товар не добавлен и не обновлен. Неизвестная ошибка.'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	################################### Сопутствующие логи при работе с товаром #######################
	//пришле запрос с парсинга фото.
	if($mark == 'curlImg'){
		$text .='->[ИЗОБРАЖЕНИЕ] : ';
		
		if($data['errno'] == 0){
			if($data['http_code']==200){
	  		$text .='Загрузка успешна | Код ответа ['.$data['http_code'].'] Ссылка | '.$data['url'].PHP_EOL;
	  	}elseif($data['http_code']==404){
	  		$text .= 'Изображение НЕ НАЙДЕНО. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==400){
	  		$text .= 'Не загружено | Неправильный запрос. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==403){
	  		$text .= 'Не загружено | Forbidden, доступ запрещен. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==301){
	  		$text .= 'РЕДИРЕКТ | Ваш запрос перенаправлен. Ответ сервера ['.$data['http_code'].'] Ссылка входа - '.htmlspecialchars_decode(trim($data['url']))
	  		.' Адрес куда перенаправлен запрос | '.htmlspecialchars_decode(trim($data['redirect_url'])).PHP_EOL;
	  	}elseif($data['http_code']==302){
	  		$text .= 'РЕДИРЕКТ | Ваш запрос перенаправлен. Ответ сервера ['.$data['http_code'].'] Ссылка входа - '.htmlspecialchars_decode(trim($data['url']))
	  		.' Адрес куда перенаправлен запрос | '.htmlspecialchars_decode(trim($data['redirect_url'])).PHP_EOL;
	  	}elseif($data['http_code']==423){
	  		$text .= 'Не загружено | Locked — целевой ресурс из запроса заблокирован от применения к нему указанного метода. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==429){
	  		$text .= 'Не загружено | Страница недоступна, слишком много запросов за короткое время. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==426){
	  		$text .= 'Не загружено | Сайт отказывается выполнять запрос с использованием текущего протокола. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==500){
	  		$text .= 'Не загружено | Сайт донор не может обработать запрос (Internal Server Error, внутренняя ошибка сервера). Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==502){
	  		$text .= 'Не загружено | Донор получил не верны ответ от вышестоящего сервера. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==503){
	  		$text .= 'Не загружено | Сайт донор недоступен на данный момент. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==504){
	  		$text .= 'Не загружено | Внутренняя ошибка донора (Gateway Timeout), не получен своевременный ответ. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}elseif($data['http_code']==505){
	  		$text .= 'Не загружено | HTTP-версия, используемая в запроcе, не поддерживается донором. Ответ сервера ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}else{
  			$text .= 'НЕИЗВЕСТНЫЙ ОТВЕТ Ответ сервера не распознан. Код ответа ['.$data['http_code'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
	  	}
  	}else{
  		$text .= 'НЕ ЗАГРУЖЕНО Код ответа ['.$data['errno'].'] Сообшение = ['.$data['errmsg'].'] Ссылка | '.htmlspecialchars_decode(trim($data['url'])).PHP_EOL;
  	}
  	#Записываем, или дозаписываем данные в лог фаил
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Пришел запрос с добавления фото в товар.
	if($mark == 'fotoNotData'){
		$text .='->У товара нет фото ID = '.$data['pr_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавление категории.
	if($mark == 'addCat'){
		$text .='->КАТЕГОРИЯ СОЗДАНА : ID='.$data['id'].' Адрес категории = '.$data['cat_way'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавление макета в категории
	if($mark == 'addCatToLayout'){
		$text .='->В категории с id = '.$data['cat_id'].' указан макет layout_id = '.$data['layout_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'LogAddNewOpt'){
		$text .='->ОПЦИЯ СОЗДАНА : ID='.$data['opt_id'].' Имя опции = '.$data['opt_name'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'addOptToProduct'){
		$text .='->Добавлена опция в товар. | option_id='.$data['opt_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'addNewOptionValue'){
		$text .='->Добавлено новое значение в опцию option_id ='.$data['opt_id'].' | Значение = ['.$data['value'].'] | value_id = '.$data['value_id'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

 	if($mark == 'doProductOptValueAdd'){
		$text .='->Добавлена опция в товаре option_id = '.$data['opt_id'].' | Добавлено значение опции value_id = '.$data['value_id'].' | Цена '.$data['pref'].' '.$data['price'].'| Количество = '.$data['quant'].' | Изображение '.$data['img'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

 	if($mark == 'doProductOptValueUp'){
		$text .='->Обновлена опция в товаре option_id = '.$data['opt_id'].' | Обновлено значение опции value_id = '.$data['value_id'].' | Цена '.$data['pref'].' '.$data['price'].'| Количество = '.$data['quant'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавление производителя.
	if($mark == 'addManuf'){
		$text .='->ПРОИЗВОДИТЕЛЬ СОЗДАН : ID='.$data['id'].' Название = '.$data['name'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Создан атрибут
	if($mark == 'AddNewAttr'){
		$text .='->СОЗДАН АТРИБУТ : Добавлен новый атрибут ['.$data['attr_name'].'] в группу id= '.$data['r_attr_group'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Создан атрибут
	if($mark == 'addAttrToProductLog'){
		$text .='->Добавлен атрибут в товар | attribute_id = '.$data['attr_id'].' | ['.$data['name'].'] = '.$data['value'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Добавление акции в товар.
	if($mark == 'addPriceSpecToProduct'){
		$text .='->Добавлена акционная цена = '.$data['price_spec'].' | Для групп(ы) покупателей = '.$data['group'].' | Сроком на = ['.$data['date'].']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Не вышло создать атрибут
	if($mark == 'NoAddNewAttr'){
		$text .='->ОШИБКА : Модуль не смог создать новый атрибут с именем ['.$data['attr_name'].'] в группе id= '.$data['r_attr_group'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Пришел запрос на запись лога в рекомендации товара.
	if($mark == 'relateAddProduct'){
		$text2 = '';
		foreach($data as $value){
			$text2 .= $text.'-> Рекомендованный товары | В товар с id = ['.$value[0].'] В рекомендованный товар добавлен товар с id = ['.$value[1].'] '.PHP_EOL;
			$text2 .= $text.'-> Рекомендованный товары | В товар с id = ['.$value[1].'] В рекомендованный товар добавлен товар с id = ['.$value[0].'] '.PHP_EOL;
		}
		file_put_contents($path, $text2, FILE_APPEND);
	}

	/////////////////////////////////////////////////////
	//              логи xml
	/////////////////////////////////////////////////////

	if($mark == 'cutNewXml'){
		$text .='--> XML Обработчик  | Произведено деление XML файла на отдельные '.$data['culum'].' страниц(ы)'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	/////////////////////////////////////////////////////
	//              логи от чекера прокси
	/////////////////////////////////////////////////////

	//Рабочий прокси
	if($mark == 'ProxyGood'){
		$text .='--> PROXY CHECKER | УСПЕХ | Прокси прошел проверку по вашим требованиям и добавлен в список проверенных | Прокси = [ '.$data['proxy'].' ]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Ошибка при работе с прокси
	if($mark == 'ProxyError'){
		$text .='!-> PROXY CHECKER | ОШИБКА | Номер ответа = '.$data['error'].' | Сообщение об ошибке = [ '.$data['error_msg'].' ] | Прокси = [ '.$data['proxy'].' ]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Ошибка при работе с прокси
	if($mark == 'ProxyErrorHttp'){
		$text .='!-> PROXY CHECKER | ОТВЕТ HTTP | Номер ответа http = '.$data['http_code'].' | Прокси = [ '.$data['proxy'].' ]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Нправильный формат записи прокси
	if($mark == 'ProxyBadFormId'){
		$text .='!-> PROXY CHECKER | ОТВЕТ | Неправильный формат прокси | Прокси = [ '.$data['proxy'].' ]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//Подменяет сайт, отдает не те данные что ожидаются
	if($mark == 'ProxyChangeData'){
		$text .='!-> PROXY CHECKER | ОТВЕТ | Подменяет данные сайта к которому вы обращаетесь | Прокси = [ '.$data['proxy'].' ]'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	/////////////////////////////////////////////////////
	//              работа с авторизацией
	/////////////////////////////////////////////////////
	
	//Запрос на авторизацию отправлен 
	if($mark == 'АuthTrue'){
		$text .='--> АВТОРИЗАЦИЯ | Запрос на авторизацию отправлен по ссылке = '.$data['url'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//Нет проверочной строки.
	if($mark == 'АuthNotStr'){
		$text .='!-> Сбой авторизации | Невозможно проверить авторизацию, не указан проверочный текст в настройках запроса.'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//Не указана ссылка на страницу авторизации
	if($mark == 'АuthNotUrl'){
		$text .='!-> Авторизация НЕ ВЫПОЛНЕНА | Не указана ссылка на страницу авторизации. Проверьте настройки авторизации.'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//сбой авторизации
	if($mark == 'АuthCheckIsFalse'){
		$text .='!-> СБОЙ АВТОРИЗАЦИИ | В коде странице не найден проверочный текст ['.$data.']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}
	//сбой авторизации, второй раз, все вилы, дальше некуда.
	if($mark == 'АuthCheckIsFalseAgain'){
		$text .='!-> СБОЙ АВТОРИЗАЦИИ | [ОСТАНОВКА ПАРСИНГА] В коде странице не найден проверочный текст ['.$data.']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	/////////////////////////////////////////////////////
	//              работа с доп модулями
	/////////////////////////////////////////////////////

	//запись информации по работе с HPM
	if($mark == 'AddProductToHpm'){
		$text .='->[HPM] добавлен товар | id родительского товара - '.$data['parent'].' | id товаров добавленных в группу - '.$data['products'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}	

	/////////////////////////////////////////////////////
	//              Крон
	/////////////////////////////////////////////////////
	//запись информации по ненастроенную форму csv
	if($mark == 'CronCsvNullForm'){
		$text .='!-->[CRON] Парсинг в CSV не выполнен | Не указаны настройки таблицы CSV'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}	

	//сбой авторизации
	if($mark == 'CronАuthCheckIsFalse'){
		$text .='!--> [CRON] СБОЙ АВТОРИЗАЦИИ | В коде странице не найден проверочный текст ['.$data.']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//сбой авторизации, второй раз, все вилы, дальше некуда.
	if($mark == 'CronАuthCheckIsFalseAgain'){
		$text .='!--> [CRON] СБОЙ АВТОРИЗАЦИИ | [ОСТАНОВКА ПАРСИНГА] В коде странице не найден проверочный текст ['.$data.']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	/////////////////////////////////////////////////////
	//              Собственные скрипты
	/////////////////////////////////////////////////////

	//Запус скрипта
	if($mark == 'ScraptStartExecuting'){
		$text .='-> [SCRIPT] Старт выполнения скрипта ['.$data.']'.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	//Ошибки что получается словить.
	if($mark == 'ScraptMyErrorHandler'){
		$text .='!-> [SCRIPT ERROR] '.$data['errstr'].' | '.$data['errfile'].' | Строка ~ '.$data['errline'].PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

	if($mark == 'MyText'){
		$text .= $data.PHP_EOL;
		file_put_contents($path, $text, FILE_APPEND);
	}

}

//Получаем логи для вывода
public function getLogs($dn_id){
	$setting = $this->getSetting($dn_id);
	$log = '';
	$file = DIR_LOGS."simplepars_id-".$dn_id.".log";

	$size_mb = $setting['logs_mb']*1000000;

	//перенес с стандартной фунции
	if (file_exists($file)) {
		$size = filesize($file);

		if ($size >= ($size_mb) ) {

			$hed = PHP_EOL.'##################################################'.PHP_EOL.'# Логи не могут быть показаны полностью, так как его размер превышает ['.($size_mb/1000000).'мб] составляет ['.round($size/1000000, 2).'мб]'.PHP_EOL.'# По этому будет выведены только первые 15 000 строк лога.'.PHP_EOL.'# Если вы хотите просмотреть весь лог вы можете скачать его нажав соответствующий кнопку в правом верхнем углу.'.PHP_EOL.'# После это вы сможете открыть его в текстовом редакторе и все изучить.'.PHP_EOL.'##################################################'.PHP_EOL.PHP_EOL;

			$log = $hed;
			//открываем файл и считаем количество строк.
			$handle = fopen($file, "r");
			$i = 0;
			while (($line = fgets($handle)) !== false) {
			  $i++;
			}
			
			//Возврашаем курсор обратно.
			rewind($handle);
			//определяем количество строк что заберем.
			$pos = $i - 10001;
			//вырезаем последние 10 000 строк ;) 
			$i = 0;
			while (($line = fgets($handle)) !== false) {
			  if($i > $pos){
			    $log .= $line;
			  }
			  $i++;
			}
			fclose($handle);
			
			$log .= $hed;

		} else {
			$log = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
		}
	}
	return $log;
}

############################################################################################
############################################################################################
#						Фунции деление xml на части.
############################################################################################
############################################################################################
public function getSplitXmpPage($dn_id){
	//Получаем настройки поставшика
	$setting = $this->getSetting($dn_id);
	$data['setting'] = $setting;
	//получаем ссылки очереди.
	$links = $this->db->query("SELECT id, link FROM " . DB_PREFIX . "pars_sen_link WHERE `dn_id`=".(int)$dn_id." LIMIT 0,".$setting['page_cou_link']);
	$data['links'] = $links->rows;
	
	$xml = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_xml WHERE `dn_id`=".(int)$dn_id);
	$data['xml'] = $xml->row;
	#$this->wtfarrey($data);
	return $data;
}

//сохранение границ разделения xml на разные товары
public function xmlSaveGran($data, $dn_id){

	$this->db->query("UPDATE `". DB_PREFIX ."pars_xml` SET 
		cat_work ='".$this->db->escape($data['cat_work'])."', 
		pr_start ='".$this->db->escape($data['pr_start'])."', 
		pr_stop ='".$this->db->escape($data['pr_stop'])."', 
		cat_start ='".$this->db->escape($data['cat_start'])."',
		cat_stop ='".$this->db->escape($data['cat_stop'])."',
		cat_name_start ='".$this->db->escape($data['cat_name_start'])."',
		cat_name_stop ='".$this->db->escape($data['cat_name_stop'])."',
		cat_id_start ='".$this->db->escape($data['cat_id_start'])."',
		cat_id_stop ='".$this->db->escape($data['cat_id_stop'])."',
		cat_perent_start ='".$this->db->escape($data['cat_perent_start'])."',
		cat_perent_stop ='".$this->db->escape($data['cat_perent_stop'])."',
		pr_cat_start ='".$this->db->escape($data['pr_cat_start'])."',
		cat_delim ='".$this->db->escape($data['cat_delim'])."',
		pr_cat_stop ='".$this->db->escape($data['pr_cat_stop'])."',

		filter_yes ='".$this->db->escape($data['filter_yes'])."',
		filter_no ='".$this->db->escape($data['filter_no'])."'

		WHERE dn_id =".$dn_id);
	#$this->wtfarrey($data);

}

//получаем код страницы и выводим. 
public function xmlShowPieceCode($data, $dn_id){
	
	//сплошной текст что будет отдан.
	$show_code = '';
	//Получаем код страницы.
	$html = $this->CachePage($data['link'], $dn_id);
	$path_xml = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/0-temp.xml';
	file_put_contents($path_xml, $html);
	unset($html);

	//Указываем декодирование границ
	$pr_start = htmlspecialchars_decode($data['pr_start']);
	$pr_stop = htmlspecialchars_decode($data['pr_stop']);
	$pr_cat_start = htmlspecialchars_decode($data['pr_cat_start']);
	$pr_cat_stop = htmlspecialchars_decode($data['pr_cat_stop']);

	//зашита от дурака
	if(empty($pr_start)){ $pr_start ='rassol2granpars';}
	if(empty($pr_stop)){ $pr_stop='simpleparsgranpars';}
	if(empty($pr_cat_start)){ $pr_cat_start ='rassol2granpars';}
	if(empty($pr_cat_stop)){ $pr_cat_stop='simpleparsgranpars';}

	//получаем кодировку.
	$itr_charset = $this->xmlFileReadGenerator($path_xml);
	$charset = '';
	foreach($itr_charset as $key => $iter){
		if($key < 25){ $charset .= $iter;}else{ break; }
	}
	$chrs = $this->xmlGetCharset($charset);
	#$this->wtfarrey($chrs);
	unset($itr_charset);
	unset($charset);

	//для пред просмотра оставляем 10т строк
	$iterator_html = $this->xmlFileReadGenerator($path_xml);
	$text_xml = '';
	foreach($iterator_html as $key_text => $iter){
		if($key_text < 10001){ $text_xml .= @mb_convert_encoding($iter, "UTF-8", $chrs); }
	}

	//готовим правило
	$reg = '#'. preg_quote($pr_start, '#').'(.*?)'.preg_quote($pr_stop, '#') .'#su';
	preg_match_all($reg, $text_xml, $pre_view);

	///////////////////////////////
	//Если работаем с категориями.
	///////////////////////////////
	if(!empty($data['cat_work'])){
			#$this->wtfarrey($text_xml);
			$cat_forms = $this->xmlSplitToCategory($data, $text_xml);
			#$this->wtfarrey($cat_forms);
			//потратим еше ресурсов для красивого пред просмотра. 
			$show_code .= '!############################## Визульное представления категорий. Только для пред просмотра!!! ##############################!'.PHP_EOL.PHP_EOL;
			if(!empty($cat_forms)){
				foreach($cat_forms as $key_form => $cat_form){
					$show_code .= $key_form.'|'.trim($cat_form).PHP_EOL;
				}
			}else{
				$show_code .= "МОДУЛЬ SimplePars НЕ СМОГ РАЗОБРАТЬ СТРУКТУРУ КАТЕГОРИЙ ПО ВАШИМ НАСТРОЙКАМ.".PHP_EOL."ЕСЛИ ВЫ ЖЕЛАЕТЕ ПОСТРОИТЬ СТРУКТУРУ КАТЕГОРИЙ, ПОЖАЛУЙСТА ПЕРЕПРОВЕРЬТЕ НАСТРОЙКИ ГРАНИЦ РАЗБОРА КАТЕГОРИЙ.".PHP_EOL;
			}

			$show_code .= PHP_EOL.'!######################################### Конец визуального представления категорий #########################################!'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;
		
	}

	#$this->wtfarrey($cat_forms);
	//##################################################
	//Проверяем нужно ли использовать правила допуска.
	//##################################################
	$filter_use = 0;
	if(!empty($data['filter_yes']) || !empty($data['filter_no'])){
		
		$filter_use = 1;
		//приобразовываем правила. Делаю здесь для оптимизации
		$data['filter_yes'] = explode(PHP_EOL, $data['filter_yes']);
		$data['filter_no']  = explode(PHP_EOL, $data['filter_no']);
		#$this->wtfarrey($data['filter_no']);
	}

	
	$i = 1;
	foreach($pre_view[0] as $key => $text){

		if(!empty($data['cat_work']) && !empty($cat_forms)){
			
			$pattern = preg_quote($pr_cat_start, '#').'(.*?)'.preg_quote($pr_cat_stop, '#');
    	if(preg_match('#'.$pattern.'#', $text, $match)){
    		#$this->wtfarrey($match);
    		if(!empty($cat_forms[$match[1]])){
    			$text = str_replace($match[0], $pr_cat_start.$cat_forms[$match[1]].$pr_cat_stop, $text);
    		}
    	
    	}

		}

	 	$show_code .= '!=========================================================== Товар №'.$i.' ========================================================!'.PHP_EOL.PHP_EOL;
	 	//####################################
		//проверяем есть ли работа с допуском.
		//####################################
		if($filter_use){
	 		
			//проверяем допуск к записи товара. 
			$permit = $this->xmlFiltrProduct($data, $text);
			#$this->wtfarrey($text);
			if(empty($permit)){
				$show_code .= "##################################################################################".PHP_EOL."## Данный товар не прошел проверку фильтров и не будет добавлен после обработки.##".PHP_EOL."##################################################################################".PHP_EOL.PHP_EOL.$text.PHP_EOL.PHP_EOL;
			}else{
				$show_code .= $text.PHP_EOL.PHP_EOL;
			}

	 	}else{
	 		$show_code .= $text.PHP_EOL.PHP_EOL;
	 	}
	 	



		$i++;
	}

	$show_code .="#############################################################################".PHP_EOL."## Обратите внимание что в пред просмотре выводится не более 10тысяч строк.##".PHP_EOL."#############################################################################";

	return $show_code;
}

//Эта фунция нужна для красивого пред просмотра :()
public function xmlCutsAnswerFromCurl($urls, $dn_id){

	$curl = $this->multiCurl($urls, $dn_id);

	//производим зяпись лога курл, и паролельно проверяем нужно ли делать дальнейшую работу.
	$curl_error = $this->sentLogMultiCurl($curl[$urls[0]] ,$dn_id);
	
	$itr_charset = $this->xmlTextReadGenerator(substr($curl[$urls[0]]['content'], 0, 10000000));
	$charset = '';
	foreach($itr_charset as $key => $iter){
		if($key < 25){ $charset .= $iter.PHP_EOL;}else{ break; }
	}

	$chrs = $this->xmlGetCharset($charset);
	#$this->wtfarrey($chrs);
	unset($itr_charset);
	unset($charset);

	$iterator = $this->xmlTextReadGenerator(substr($curl[$urls[0]]['content'], 0, 10000000));
	$code = '';
	foreach($iterator as $key => $iter){
		if($key < 10001){ $code .= htmlspecialchars(mb_convert_encoding($iter, "UTF-8", $chrs)).PHP_EOL;}else{ break; }
	}
	
	unset($curl);
	unset($iterator);
	
	if(!empty($code)){
		$code .= PHP_EOL.PHP_EOL."#############################################################################".PHP_EOL."## Обратите внимание что в пред просмотре выводится не более 10тысяч строк.##".PHP_EOL."#############################################################################";
	}else{
		$code = '...';
	}

	return $code;

}

#Фунция перебора категорий. Из xml
public function xmlMadeCatTree($cat_data, $cat_delim, $i=0, $categories=[], $parent_id = 0, $parent_name = ''){
  #$this->wtfarrey($cat_data);
  //моя доработака
  if($i != 0){

    $category_data = array();
    foreach ($cat_data as $row) {
      $category_data[$row['parent_id']][$row['category_id']] = $row;
    }
    $output = array();
    $output += $this->xmlMadeCatTree($cat_data, $cat_delim, 0, $category_data);

  }else{
    //Стандартная фунция ниже
    $output = array();

    if (array_key_exists($parent_id, $categories)) {
      if ($parent_name != '') {
        $parent_name .= $cat_delim;
      }

      foreach ($categories[$parent_id] as $category) {
        $output[$category['category_id']] = $parent_name . trim($category['name']);
        $output += $this->xmlMadeCatTree($cat_data, $cat_delim, 0, $categories, $category['category_id'], $parent_name . $category['name']);
      }
    }
  }

  #$this->wtfarrey($output);
  return $output;
}

//Контроллер разбера xml на разные страницы.
public function controlParsToXml($dn_id){

  //Настройки проэкта
  $setting = $this->getSetting($dn_id);
  //получаем настройки браузера, один раз что бы сократить запросы в базу.
	$browser = $this->getBrowserToCurl($dn_id);

  //получаем настройки xml
  $pars_xml = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_xml WHERE `dn_id`=".(int)$dn_id)->row;
  //путь к хранению порезанных товаров
  $xml_dir = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/';

  $pars_url = $this->getUrlSenToPars($dn_id);
  #$this->wtfarrey($pars_url);
  #Если ссылок нету завершаем работу модуля.
  if(empty($pars_url['links'])){

    $answ['progress'] = 100;
    $answ['clink'] = ['link_scan_count' => $pars_url['total'], 'link_count' => $pars_url['queue'],];
    $this->answjs('finish','Парсинг ссылок закончен, XML/YML поделен на товары. ',$answ);

  }else{

  	//собираем массив ссылок для мульти запроса.
  	$urls = [];
  	foreach($pars_url['links'] as $key => $url){
  		if($key < $setting['thread']) {$urls[] = $url['link']; } else { break; }
  	}

  	#$this->wtfarrey($urls);
  	$datas = $this->requestConstructor(0, $urls, $dn_id, $browser, $setting, 0);
  	#$this->wtfarrey($datas);
	

  	//Далее разбираем данные из мульти курла и делаем все нужные записи.
  	foreach($datas as $key => $data){

  		file_put_contents($xml_dir.'0-temp.xml', $data['content']);
  		//основная фунция по делению файла
  		$this->xmlControlSplitAndCut($pars_xml, $data['url'], $setting, $dn_id);
  	}


    
    #считаем процент для прогрес бара
    $scan = ($pars_url['total']-$pars_url['queue']);
    $progress = $scan/($pars_url['total']/100);
    $answ['progress'] = $progress;
    $answ['clink'] = [
                       'link_scan_count' => $scan,
                       'link_count' => $pars_url['queue'],
                      ];
    #пауза парсинга
    $this->timeSleep($setting['pars_pause']);
    $this->answjs('go','Производится парсинг',$answ);
    #exit(json_encode($answ));
  }
}

//Фунция обработки категорий.
public function xmlSplitToCategory($pars_xml, $xml){
	#$xml = str_replace("'", '"', $xml);
	$cat_start = htmlspecialchars_decode($pars_xml['cat_start']);
	$cat_stop = htmlspecialchars_decode($pars_xml['cat_stop']);
	$cat_name_start = htmlspecialchars_decode($pars_xml['cat_name_start']);
	$cat_name_stop = htmlspecialchars_decode($pars_xml['cat_name_stop']);
	$cat_id_start = htmlspecialchars_decode($pars_xml['cat_id_start']);
	$cat_id_stop = htmlspecialchars_decode($pars_xml['cat_id_stop']);
	$cat_perent_start = htmlspecialchars_decode($pars_xml['cat_perent_start']);
	$cat_perent_stop = htmlspecialchars_decode($pars_xml['cat_perent_stop']);
	$cat_delim = htmlspecialchars_decode($pars_xml['cat_delim']);

	if(empty($cat_start)){ $cat_start ='rassol2granpars';}
	if(empty($cat_stop)){ $cat_stop='simpleparsgranpars';}

	if(empty($cat_name_start)){ $cat_name_start ='rassol2granpars';}
	if(empty($cat_name_stop)){ $cat_name_stop='simpleparsgranpars';}

	if(empty($cat_id_start)){ $cat_id_start ='rassol2granpars';}
	if(empty($cat_id_stop)){ $cat_id_stop='simpleparsgranpars';}

	if(empty($cat_perent_start)){ $cat_perent_start ='rassol2granpars';}
	if(empty($cat_perent_stop)){ $cat_perent_stop='simpleparsgranpars';}

	$cat_data = [];
	$cat_forms = [];
	$cat_reg = '#'. preg_quote($cat_start, '#').'(.*?)'.preg_quote($cat_stop, '#') .'#su';
	preg_match_all($cat_reg, $xml, $cat_view);

	//если под границу категории что то попало работаем дальше.
	if(!empty($cat_view[0])){

		//собираем массив для отправки в рекурсивную фунцию постраения дом дерева.
		foreach($cat_view[0] as $key_arr => $cat_str){
			
			#$this->wtfarrey($cat_str);
			
			$cat_name = '#'. preg_quote($cat_name_start, '#').'(.*?)'.preg_quote($cat_name_stop, '#') .'#su';
			preg_match($cat_name, $cat_str, $name_temp);

			$cat_id = '#'. preg_quote($cat_id_start, '#').'(.*?)'.preg_quote($cat_id_stop, '#') .'#su';
			preg_match($cat_id, $cat_str, $id_temp);

			$cat_parent_id = '#'. preg_quote($cat_perent_start, '#').'(.*?)'.preg_quote($cat_perent_stop, '#') .'#su';
			preg_match($cat_parent_id, $cat_str, $parent_id_temp);

			if(empty($parent_id_temp[1])){$parent_id_temp[1] = 0;}

			if(!empty($name_temp[1]) && !empty($id_temp[1])){
				$cat_data[$key_arr] = ['name' => $name_temp[1], 'category_id'=>$id_temp[1], 'parent_id'=>$parent_id_temp[1]];
			}
			
		}
		#$this->wtfarrey($cat_data);
		$cat_forms = $this->xmlMadeCatTree($cat_data, $cat_delim, 1);
	}

	return $cat_forms;
}

//получить кодировку
public function xmlGetCharset($text){

	if(!empty($text)){
		//если кодировка не равна UTF-8 тогда пытаемся ее определить по тексту
		if(mb_detect_encoding($text, 'UTF-8', true)){
			
			$encoding = 'UTF-8';
		
		}else{
					
			if( preg_match('#encoding\=\"(.*?)\"#', $text, $chrs) ){
				$encoding = trim($chrs[1]);
			}elseif( preg_match('#encoding\=\&quot;(.*?)\&quot\;#', $text, $chrs) ) {
				$encoding = trim($chrs[1]);
			}
		
		}
	}

	if(empty($encoding)){ $encoding = 'UTF-8';}
	#$this->wtfarrey($encoding);
	return $encoding;
}

//фунция генератор, для чтения переменной построчно. 
public function xmlTextReadGenerator($input) {
  foreach (explode("\n", $input) as $line) {
      yield $line;
  }
}

//фунция генератор, для чтения файла построчно. 
public function xmlFileReadGenerator($path) {
    $handle = fopen($path, "r");

    while(!feof($handle)) {
        yield fgets($handle);
    }

    fclose($handle);
}

//Фунция разбиение на файлы. С записью ссылки в базу. И преобразованием категорий в человеко понятный вариант.
public function xmlControlSplitAndCut($pars_xml, $url, $setting, $dn_id){

	//Делим товар на файлы. 

	//начинаем переберать впоисках нужных нам частей.
	$pr_start = htmlspecialchars_decode($pars_xml['pr_start']);
	$pr_stop = htmlspecialchars_decode($pars_xml['pr_stop']);

	$pr_cat_start = htmlspecialchars_decode($pars_xml['pr_cat_start']);
	$pr_cat_stop = htmlspecialchars_decode($pars_xml['pr_cat_stop']);

	//зашита от дурака
	if(empty($pr_start)){ $pr_start ='rassol2granpars';}
	if(empty($pr_stop)){ $pr_stop='simpleparsgranpars';}

	if(empty($pr_cat_start)){ $pr_cat_start ='rassol2granpars';}
	if(empty($pr_cat_stop)){ $pr_cat_stop='simpleparsgranpars';}

	//путь к месту хранения нарезанных файлов
	$xml_dir = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/';
	//путь к файлу для обработки.
	$path_xml = $xml_dir.'0-temp.xml';

	//получаем кодировку.
	$itr_charset = $this->xmlFileReadGenerator($path_xml);
	$charset = '';
	foreach($itr_charset as $key => $iter){
		if($key < 25){ $charset .= $iter;}else{ break; }
	}
	$chrs = $this->xmlGetCharset($charset);
	unset($itr_charset);
	unset($charset);

	//Работа с категориями.
	if(!empty($pars_xml['cat_work'])){
		
		//вырезаем кусочик для обработки категорийю		
		$iterator_cats = $this->xmlFileReadGenerator($path_xml); //итерируемый обьект. 
		$temp_text = "";
		
		foreach ($iterator_cats as $key_cat => $line_cat) {
			
			if($key_cat < 7000){ 
				$temp_text .= mb_convert_encoding($line_cat, "UTF-8", $chrs);
			}else{
				break;
			}	
		}
		unset($iterator_cats);

		$cat_forms = $this->xmlSplitToCategory($pars_xml, $temp_text);
		unset($temp_text);
	}

	#$this->wtfarrey($cat_forms);
	//Все что касается нарезания страницы на отдельные куски. 	
	$iterator = $this->xmlFileReadGenerator($path_xml); //итерируемый обьект. 
	$text = '#[main_url]'.$url.'[/main_url]'.PHP_EOL;
	$pr_num = 1;
	$go = 0;

	//##################################################
	//Проверяем нужно ли использовать правила допуска.
	//##################################################
	$filter_use = 0;
	if(!empty($pars_xml['filter_yes']) || !empty($pars_xml['filter_no'])){
		
		$filter_use = 1;
		//приобразовываем правила. Делаю здесь для оптимизации
		$pars_xml['filter_yes'] = explode(PHP_EOL, $pars_xml['filter_yes']);
		$pars_xml['filter_no']  = explode(PHP_EOL, $pars_xml['filter_no']);

	}

	while( ($iterator->current() !== null) || !empty($text_end)){
		
		$line = '';
		if($iterator->current() !== null){ $line = $iterator->current(); }
		//если с предыдушей итерации остался кусок текста записуев в начало строки.
		if(!empty($text_end)){
			$line = $text_end.$line;
			$text_end = '';
		}
			
		if($go == 0){
			
			if(strpos($line, $pr_start) !== false){ 
				$go = 1;
				//отрезаем из строки все что перед входящим тегом.
				$line = substr($line, strpos($line, $pr_start));
			}
			
		}

		if($go){
      $text .= $line;
      $go++;
    }

    if( (strpos($line, $pr_stop) !== false ) || $go > 6000){ 
      //отрезаем все что идет после тега стоп. Для передачи в следуюшую итерацию
      $text_end = substr($line, strpos($line, $pr_stop)+mb_strlen($pr_stop));

      //отризаем от строки все что идет после конца, оно нам тут не нужно :)
      $text = substr($text, 0, strpos($text, $pr_stop)+mb_strlen($pr_stop));

      //Если включена работа с категориями.
      if(!empty($pars_xml['cat_work']) && !empty($cat_forms)){
      	//переводим в UTF-8
      	$text = @mb_convert_encoding($text, "UTF-8", $chrs);

      	$pattern = preg_quote($pr_cat_start, '#').'(.*?)'.preg_quote($pr_cat_stop, '#');
      	
      	if(preg_match('#'.$pattern.'#', $text, $match)){
      		
      		if(!empty($cat_forms[$match[1]])){
      			$text = str_replace($match[0], $pr_cat_start.$cat_forms[$match[1]].$pr_cat_stop, $text);
      		}
      	
      	}
      	
      }else{
      	//переводим в UTF-8, если не работаем с категориями
      	$text = @mb_convert_encoding($text, "UTF-8", $chrs);
      }

      #$this->wtfarrey($text);

			$xml_name = md5($url).'-'.$pr_num.'.xml';
			$path = $xml_dir.$xml_name;
			$link = HTTP_SERVER.'simplepars/xml_page/'.$dn_id.'/'.$xml_name;

			//####################################
			//проверяем есть ли работа с допуском.
			//####################################
			if($filter_use){

				//проверяем допуск к записи товара. 
				$permit = $this->xmlFiltrProduct($pars_xml, $text);
				
				if($permit){
					file_put_contents($path, $text);
					$this->AddParsLink($link, $dn_id);
				}

			}else{
				file_put_contents($path, $text);
				$this->AddParsLink($link, $dn_id);
			}

			//если в строке что то послеконца было, передаем в следующию итерацию.
			#$text = '';
			$text = '#[main_url]'.$url.'[/main_url]'.PHP_EOL;
      $go = 0;
      $pr_num++;
    }
	
    $iterator->next();
	}

	$log['culum'] = $pr_num;
	//записываем в логи.
	$this->log('cutNewXml', $log, $dn_id);

}

//Фунция удаления нарезанных файлов xml
public function xmlDelFiles($dn_id){

	$files = glob(DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/*'); // получаем все имена файлов в директории.
	foreach($files as $file){ 
    @unlink($file);
	}

}

//Фунция фильтрации товаров.
public function xmlFiltrProduct($pars_xml, $text){
	$data = 1;
	$filter_yes = $pars_xml['filter_yes'];
	$filter_no  = $pars_xml['filter_no'];

	foreach ($filter_yes as $key => $value) {
		if(!empty(trim($value))){
			$filter_yes[$key] = $this->modFilterLinkRules($value);
		}else{
			unset($filter_yes[$key]);
		}
	}

	foreach ($filter_no as $key => $value) {
		if(!empty(trim($value))){
			$filter_no[$key] = $this->modFilterLinkRules($value);;
		}else{
			unset($filter_no[$key]);
		}
	}

	if(!empty($filter_yes)){

		$data = 0;#Если нужно что бы модуль проверил на присуцтвие. Допускаем после присуцтвия.

		foreach($filter_yes as $filter){

			if(preg_match($filter, $text)){
				$data = 1;
				break;
			}

		}

	}
	
	if(!empty($filter_no)){

		foreach($filter_no as $filter){
			preg_match($filter, $text, $r);
			
			if(preg_match($filter, $text)){
				$data = 0;
				break;
			}

		}

	}

	#$this->wtfarrey($filter_yes);
	#$this->wtfarrey($filter_no);
	#$this->wtfarrey($data);

	return $data;
}

############################################################################################
############################################################################################
#						Фунции связанные с браузером
############################################################################################
############################################################################################

//Сохраняем настройки браузера
public function seveBrowser($data, $dn_id){
	#$this->wtfarrey($data);
	//Главная вкладка
	if(empty($data['proxy_use'])) { $data['proxy_use'] = 0;}
  if(empty($data['timeout'])) { $data['timeout'] = 15;}
  //if(empty($data['connect_timeout'])) { $data['connect_timeout'] = 10;}
  if(!isset($data['protocol_version'])) { $data['protocol_version'] = 2;}
  if(empty($data['header_get'])) { $data['header_get'] = 0;}
  if(!isset($data['followlocation'])) { $data['followlocation'] = 1;}
  if(empty($data['coockie_list'])) { $data['coockie_list'] = '';}
  if(empty($data['cookie_use'])) { $data['cookie_use'] = 0;}
  if(empty($data['cookie_up'])) { $data['cookie_up'] = 0;}
  if(empty($data['user_agent_list'])) { $data['user_agent_list'] = '';}else{ $data['user_agent_list'] = preg_replace('#^User-Agent: #mi', '', $data['user_agent_list']);}
  if(!isset($data['user_agent_use'])) { $data['user_agent_use'] = 1;}
  if(empty($data['user_agent_change'])) { $data['user_agent_change'] = 0;}
  if(empty($data['header_list'])) { $data['header_list'] = '';} else { $data['header_list'] = $this->clearHeaders($data['header_list']);}
  if(empty($data['header_use'])) { $data['header_use'] = 0;}
  if(empty($data['header_change'])) { $data['header_change'] = 0;}
  
	//Вкладка чекера прокси
	if(empty($data['ch_connect_timeout'])) { $data['ch_connect_timeout'] = 5; }
	if(empty($data['ch_timeout'])) { $data['ch_timeout'] = 5; }
	if(empty($data['ch_url'])) { $data['ch_url'] = ''; }
	if(empty($data['ch_pattern'])) { $data['ch_pattern'] = ''; }

	if(empty($data['auth_use'])) { $data['auth_use'] = 0;}
	if(empty($data['auth_url'])) { $data['auth_url'] = ''; }
	if(empty($data['auth_data'])) { $data['auth_data'] = ''; }
	if(empty($data['auth_url_check'])) { $data['auth_url_check'] = ''; }
	if(empty($data['auth_str'])) { $data['auth_str'] = ''; }
	if(empty($data['auth_type'])) { $data['auth_type'] = 1; }

	//Если пользователь включает использование авто авторизации то сообшаем о включении работы с куки.
	if(empty($data['auth_use'])) { 
		$data['auth_use'] = 0;
	}elseif($data['auth_use'] == 1){

		if(empty($data['auth_url']) || empty($data['auth_str'])){
			$data['auth_use'] = 0;
			$this->session->data['error'] = ' Не возможно включить парсинг с авторизацией, поскольку не указана ссылка на страницу авторизации или проверочный текст!!!';
		}else{
			$data['cookie_use'] = 1;
			#$data['cookie_up'] = 1;
			$this->session->data['warning'] = ' Внимание!!! При использовании авторизации методом POST автоматически включается работа с Куки!!!';
		}
		
	}

	$this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET
		proxy_use = ".(int)$data['proxy_use'].",
		timeout = ".(int)$data['timeout'].",
		connect_timeout = ".(int)$data['timeout'].", 
		protocol_version = ".(int)$data['protocol_version'].",
		header_get = ".(int)$data['header_get'].",
		followlocation = ".(int)$data['followlocation'].",
		cookie_use = ".(int)$data['cookie_use'].",
		cookie_up = ".(int)$data['cookie_up'].",
		user_agent_use = ".(int)$data['user_agent_use'].",
		user_agent_change = ".(int)$data['user_agent_change'].",
		user_agent_list = '".$this->db->escape($data['user_agent_list'])."',
		header_use = ".(int)$data['header_use'].",
		header_change = ".(int)$data['header_change'].",
		header_list = '".$this->db->escape($data['header_list'])."',
		ch_connect_timeout = ".(int)$data['ch_connect_timeout'].",
		ch_timeout = ".(int)$data['ch_timeout'].",
		ch_url = '".$this->db->escape($data['ch_url'])."',
		ch_pattern = '".$this->db->escape($data['ch_pattern'])."',
		auth_use = '".(int)$data['auth_use']."',
		auth_url = '".$this->db->escape($data['auth_url'])."',
		auth_data = '".$this->db->escape($data['auth_data'])."',
		auth_type = '".$this->db->escape($data['auth_type'])."',
		auth_url_check = '".$this->db->escape($data['auth_url_check'])."',
		auth_str = '".$this->db->escape($data['auth_str'])."'
		WHERE dn_id =".(int)$dn_id);

	//преобразовываем куки в формат Netscape и записываем в файл.
	$this->saveCookiesToFile($data['coockie_list'], $dn_id);

}

//Фунция преобразования данных для хранения
public function saveCookiesToFile($text, $dn_id){

	$file = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
	#$text = str_replace(PHP_EOL, '', $text);
	$text = preg_replace('#(\\r\\n|\\r|\\n)#s', '', $text);
	if(!preg_match('#^Cookie: #', $text)){ $text = 'Cookie: '.trim($text); }
	file_put_contents($file, $text);

}

//получение настроек браузера
public function getSettingBrowser($dn_id){
	$browser = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_browser` WHERE `dn_id` =".(int)$dn_id)->row;

	//Куки лист по умолчани
	$browser['cookie_list'] = $this->getCookiesToPage($dn_id);

	return $browser;
}

//получение настроек для cURL
public function getBrowserToCurl($dn_id){
	$browser = [];
	$browser = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_browser` WHERE `dn_id` =".(int)$dn_id);
	$browser = $browser->row;

	/////////////////////////
	//Работа с прокси
	/////////////////////////

	if($browser['proxy_use'] > 0){
		//выбираем тип прокси, 1 - весь список, 2 - только проверенный
		$browser['proxy'] = [];
		if($browser['proxy_use'] == 1){

			$proxy_list = $this->getProxyList($dn_id);
			$proxys['list'] = $proxy_list['list'];
			$proxys['max'] = $proxy_list['list_count'];

		} elseif ($browser['proxy_use'] == 2){

			$proxy_list = $this->getProxyList($dn_id);
			$proxys['list'] = $proxy_list['list_work'];
			$proxys['max'] = $proxy_list['list_work_count'];

		}

		//проверяем прокси лист пустой или нет.
		if(!empty($proxys['list'])){
			//Получаем рандомный проксик.
			$key_p = rand(0, $proxys['max']);
			$proxy_str = explode(':', $proxys['list'][$key_p]);

			//разбираем прокси на составляющие
			if(!empty($proxy_str[0]) && !empty($proxy_str[1])){

				$browser['proxy']['ip:port'] = $proxy_str[0].":".$proxy_str[1];
				$browser['proxy']['type'] = 0;
				$browser['proxy']['loginpass'] = '';

				//проверяем есть ли тип прокси
				if(!empty($proxy_str[2])) {
					$proxy_type = mb_strtoupper($proxy_str[2]);
					if($proxy_type == 'HTTP'){ 
						$browser['proxy']['type'] = CURLPROXY_HTTP; 
					}elseif($proxy_type == 'HTTPS'){ 
						$browser['proxy']['type'] = CURLPROXY_HTTP; 
					}elseif($proxy_type == 'SOCKS4'){ 
						$browser['proxy']['type'] = CURLPROXY_SOCKS4; 
					}elseif($proxy_type == 'SOCKS5'){ 
						$browser['proxy']['type'] = CURLPROXY_SOCKS5; 
					}
				}

				//Проверяем есть ли логин и пароль.
				if(!empty($proxy_str[3]) && !empty($proxy_str[4])){
					$browser['proxy']['loginpass'] = $proxy_str[3].':'.$proxy_str[4];
				}

			} else {
				#нету или ip или порта
				#такое прокси нельзя записать в модуль но все же оставлю место на обработку таких случаев.
				#Если понадобится
			}

		} else {
			#Если список прокси пустой, тогда не используем прокси
			$browser['proxy_use'] = 0;
		}

	}

	/////////////////////////
	//Работа с куками.
	/////////////////////////
	if($browser['cookie_use']){
		
		$browser['cookies'] = '';
		$cookie_file = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
		
		if(file_exists($cookie_file)){
			$browser['cookies'] = file_get_contents(DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt');
		}

	} else {
		//Если выбранно неиспользовать куки.
		$browser['cookies'] = '';
	}

	///////////////////////////////
	//Работа с юсер агент.
	///////////////////////////////
	if($browser['user_agent_use']){

		$browser['user_agent_list'] = explode(PHP_EOL, $browser['user_agent_list']);

		//проверяем какой юсер агент использовать.
		if ($browser['user_agent_change']) {

			//если выбрано менять определяем диапазон и рандомно выбираем юсер агент.
			$max = count($browser['user_agent_list']) -1;
			//определяем рандомный ключ
			$key_u = rand(0, $max);
			//записываем юсер агент
			if(!empty($browser['user_agent_list'][$key_u])){
				$browser['user_agent_list'] = "User-Agent: ".$browser['user_agent_list'][$key_u];
			}else{
				$browser['user_agent_list'] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";
			}

		} else {
			//Если не выбрано менять берем первый юсер агент из списка. 
			if(!empty($browser['user_agent_list'][0])){
				$browser['user_agent_list'] = "User-Agent: ".$browser['user_agent_list'][0];
			}else{
				$browser['user_agent_list'] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";
			}
		}

	} else {
		$browser['user_agent_list'] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36";
	}

	///////////////////////////////
	//Работа с заголовками
	///////////////////////////////
	if ($browser['header_use']) {

		//разбиваем заголовки на массивы.
		$browser['header_list'] = preg_split("~^#(.*)~im", $browser['header_list']);

		if ($browser['header_change']) {

			//если выбрано менять определяем диапазон и рандомно выбираем.
			$max = count($browser['header_list']) -1;
			//определяем рандомный ключ
			$key_h = rand(0, $max);
			//записываем юсер агент
			$browser['header_list'] = explode(PHP_EOL, trim($browser['header_list'][$key_h]));

		} else {
			$browser['header_list'] = explode(PHP_EOL, trim($browser['header_list'][0]));
		}

	} else {
		$browser['header_list'] = [''];
	}

	///////////////////////////////
	//Работа с авторизацией
	///////////////////////////////
	if($browser['auth_type'] == 1){
		
		if(empty($browser['auth_data'])){ 
			$browser['auth_data'] = [];
		}else{
			$temp_auth = preg_split('#[=&]#', htmlspecialchars_decode($browser['auth_data']));
			$browser['auth_data'] = [];
			foreach($temp_auth as $key_a => $temp_auth){
				$temp_auth = trim($temp_auth);
				if(($key_a + 1) % 2){
					$browser['auth_data'][$temp_auth] = '';
					$temp_key = $temp_auth;
				}else{
					$browser['auth_data'][$temp_key] = rawurldecode($temp_auth);
					$temp_key = '';
				}
			}
		}

	}elseif($browser['auth_type'] == 2){

		if(empty($browser['auth_data'])){ 
			$browser['auth_data'] = '';
		}

	}

	//собираем правильные заголовки
	$browser['header_list']['cookies'] = $browser['cookies'];
	$browser['header_list']['user_agent'] = $browser['user_agent_list'];
	$browser['header_list'] = array_filter($browser['header_list']);
	#$this->wtfarrey($browser);
	return $browser;
}

//Сохраняем прокси лист
public function saveProxyList($data, $dn_id){

	//Удаляем список прокси
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_proxy_list` WHERE `dn_id`=".(int)$dn_id);

	if (!empty($data['proxy_list'])) {

		$proxy_list = explode(PHP_EOL, $data['proxy_list']);

		//составляем запрос на сохранение прокси листа.
		$sql_list = '';
		foreach ($proxy_list as $key => $list) {

			if ($key == 0){
				//проверяем на правильный формат ввода.
				if(strpos($list, ':') != false){
					$sql_list .= "('".$this->db->escape(trim($list))."', ".(int)$dn_id.", 0)";
				}

			} else {

				//проверяем на правильный формат ввода.
				if(strpos($list, ':') != false){
					$sql_list .= ",('".$this->db->escape(trim($list))."', ".(int)$dn_id.", 0)";
				}

			}

		}

		//проверяем что бы строка не была пустой.
		if (!empty($sql_list)) {
			$sql_list = "INSERT IGNORE INTO `".DB_PREFIX."pars_proxy_list`(`proxy`, `dn_id`, `status`) VALUES ".$sql_list;

			//записываем списко прокси
			$this->db->query($sql_list);
		}
	}
}

//Получаем список прокси ждя вывода на сайте
public function getProxyListToPage($dn_id){
	$list = ['list'=>'','list_work'=>'', 'list_count'=>0, 'list_work_count'=>0];
	$proxy_list = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_proxy_list WHERE dn_id=".(int)$dn_id." ORDER BY `id`");

	if($proxy_list->num_rows > 0){
		$wc = 0;
		foreach ($proxy_list->rows as $key => $value) {
			$list['list'] .= $value['proxy'].PHP_EOL;
			if($value['status'] == 1) { $list['list_work'] .= $value['proxy'].PHP_EOL; $wc++;}
		}
		$list['list_count'] = $key+1;
		$list['list_work_count'] = $wc;

	}

	#$this->wtfarrey($list);
	return $list;
}

//Получаем список прокси.
public function getProxyList($dn_id){
	$list = ['list'=>[],'list_work'=>[], 'list_count'=>0, 'list_work_count'=>0];
	$proxy_list = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_proxy_list WHERE dn_id=".(int)$dn_id);

	if($proxy_list->num_rows > 0){
		$wc = 0;
		foreach ($proxy_list->rows as $key => $value) {
			$list['list'][] = $value['proxy'];
			if($value['status'] == 1) { $list['list_work'][] = $value['proxy']; $wc++;}
		}
		$list['list_count'] = $key;
		$list['list_work_count'] = $wc-1;

	}

	#$this->wtfarrey($list);
	return $list;
}

//проверка прокси
public function startCheckProxy($dn_id){
	//Получаем настройки
	$browser = $this->getSettingBrowser($dn_id);

	//Получаем данные расчета прогрес бара.
	$proxy_ip = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_proxy_list WHERE dn_id=".(int)$dn_id." AND `status` = 0 ORDER BY id");
	$lists = $proxy_ip;
	$totals = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_proxy_list WHERE dn_id=".(int)$dn_id);
	$progress = ($totals->num_rows-$lists->num_rows)/($totals->num_rows/100);

	//проверяем есть ли ссылка, и шаблон для проверки.
	if(empty($browser['ch_url'])){
		$answ['progress'] = 100;
		$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
		$answ['proxy_wait'] = $lists->num_rows;
		$this->answjs('finish', 'Проверка прокси остановлена. Укажите ссылку на сайт донор.', $answ);
	}

	if(empty($browser['ch_pattern'])){
		$answ['progress'] = 100;
		$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
		$answ['proxy_wait'] = $lists->num_rows;
		$this->answjs('finish', 'Проверка прокси остановлена. Укажите проверочный текст', $answ);
	}

	//Основной блок работы.
	if ($proxy_ip->num_rows > 0) {
		$proxy_ip = $proxy_ip->row;

		$proxy = explode(':', $proxy_ip['proxy']);

		//проверяем что бы у прокси было и ip и порт
		if (!empty($proxy[0]) && !empty($proxy[1])) {
			//создаем ип и прокси для проверки.
			$ip_port = $proxy[0].':'.$proxy[1];
			$loginpass = '';
			$proxy_type = CURLPROXY_HTTP;

			//проверяем указан ли тип прокси
			if(!empty($proxy[2])){
				#$this->wtfarrey($proxy_type);

				$proxy_type = mb_strtoupper($proxy[2]);
				#$this->wtfarrey($proxy_type);

				if($proxy_type == 'HTTP'){ 
					$proxy_type = CURLPROXY_HTTP; 
				}elseif($proxy_type == 'HTTPS'){ 
					$proxy_type = CURLPROXY_HTTP; 
				}elseif($proxy_type == 'SOCKS4'){ 
					$proxy_type = CURLPROXY_SOCKS4; 
				}elseif($proxy_type == 'SOCKS5'){ 
					$proxy_type = CURLPROXY_SOCKS5; 
				}
			}
			#$this->wtfarrey($proxy_type);
			//если в прокси есть еше логин и пароль, тогда и его применяем.
			if (!empty($proxy[3]) && !empty($proxy[4])) {
				$loginpass = $proxy[3].':'.$proxy[4];
			}

			/////////////////////////////////
			# выполняем проверочный запрос
			/////////////////////////////////
			$uagent = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36";
		  $ch = curl_init($browser['ch_url']);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
		  curl_setopt($ch, CURLOPT_HEADER, 0);           // возвращает заголовки
		  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
		  curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки | Проблемы в понимании этой опции. Отключил
		  curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $browser['ch_connect_timeout']); // таймаут соединения
		  curl_setopt($ch, CURLOPT_TIMEOUT, $browser['ch_timeout']);        // таймаут ответа
		  curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //Отключить проверку сертификата.
		  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //проверяет принадлежность сертификата к сайту.

		  //настройки прокси
		  #$ip_port = '51.79.141.24:8080';
		  curl_setopt($ch, CURLOPT_PROXY, $ip_port);
		  #$this->wtfarrey($ip_port);
		  curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
			#$this->wtfarrey($proxy_type);

		  #Если указан логин и пароль прокси
		  if(!empty($loginpass)){
		  	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpass);
			}
			#$this->wtfarrey($loginpass);
		  $content = curl_exec( $ch );
		  $err     = curl_errno( $ch );
		  $errmsg  = curl_error( $ch );
		  $data  = curl_getinfo( $ch );
		  curl_close( $ch );

		  $data['errno']   = $err;
		  $data['errmsg']  = $errmsg;
		  $data['content'] = $content;
		  //приводит страницу к единой кодировке.
		  $data = $this->findCharsetSite($data, $dn_id);

		  //Если выскачила ошибка сообщаем ее
		  if($data['errno'] > 0) {
		  	$this->db->query("UPDATE ".DB_PREFIX."pars_proxy_list SET `status` = 2 WHERE `id` = ".(int)$proxy_ip['id']);

		  	$logs = ['proxy'=>$proxy_ip['proxy'], 'error'=>$data['errno'], 'error_msg'=>$data['errmsg']];
				$this->log('ProxyError',$logs, $dn_id);

		  	$answ['progress'] = $progress;
		  	$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
				$answ['proxy_wait'] = $lists->num_rows;
				$this->answjs('go', $data['errmsg'], $answ);

		  } elseif($data['http_code'] > 399){

				$this->db->query("UPDATE ".DB_PREFIX."pars_proxy_list SET `status` = 2 WHERE `id` = ".(int)$proxy_ip['id']);

		  	$logs = ['proxy'=>$proxy_ip['proxy'], 'http_code'=>$data['http_code']];
				$this->log('ProxyErrorHttp',$logs, $dn_id);

		  	$answ['progress'] = $progress;
		  	$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
				$answ['proxy_wait'] = $lists->num_rows;
				$this->answjs('go', $data['errmsg'], $answ);

		  }else{

		  	//Ишим указанный текст на странице
		  	$pattern = htmlspecialchars_decode($browser['ch_pattern']);

		  	if (preg_match('#'.preg_quote($pattern, '#').'#su', $data['content'])){

		  		$this->db->query("UPDATE ".DB_PREFIX."pars_proxy_list SET `status` = 1 WHERE `id` = ".(int)$proxy_ip['id']);

			  	$logs = ['proxy'=>$proxy_ip['proxy']];
					$this->log('ProxyGood',$logs, $dn_id);

			  	$answ['progress'] = $progress;
			  	$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
					$answ['proxy_wait'] = $lists->num_rows;
					$this->answjs('go', 'Прокси прошел проверку по вашим требования', $answ);

		  	} else {

		  		$this->db->query("UPDATE ".DB_PREFIX."pars_proxy_list SET `status` = 2 WHERE `id` = ".(int)$proxy_ip['id']);

		  		$logs = ['proxy'=>$proxy_ip['proxy']];
					$this->log('ProxyChangeData',$logs, $dn_id);

		  		$answ['progress'] = $progress;
		  		$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
					$answ['proxy_wait'] = $lists->num_rows;
					$this->answjs('go', 'Подменяет данные сайта к которому вы обращаетесь. Проверочный текст не найден', $answ);
		  	}

		  }

		} else {
			//Кривой прокси поменчаем его как мертвый.
			$this->db->query("UPDATE ".DB_PREFIX."pars_proxy_list SET `status` = 2 WHERE `id` = ".(int)$proxy_ip['id']);

			$logs = ['proxy'=>$proxy_ip['proxy']];
			$this->log('ProxyBadFormId',$logs, $dn_id);

			$answ['progress'] = $progress;
			$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
			$answ['proxy_wait'] = $lists->num_rows;
			$this->answjs('go', 'Неправильный формат прокси', $answ);
		}

	} else {
		$answ['progress'] = 100;
		$answ['proxy_done'] = $totals->num_rows-$lists->num_rows;
		$answ['proxy_wait'] = $lists->num_rows;
		$this->answjs('finish', 'Проверка прокси закончена', $answ);
	}
}

//очистить список прокси
public function clearProxyList($dn_id){

	$this->db->query("DELETE FROM `".DB_PREFIX."pars_proxy_list` WHERE `dn_id`=".(int)$dn_id);

}

//сбросить список проверенных прокси
public function resetProxyList($dn_id){

	$this->db->query("UPDATE `".DB_PREFIX."pars_proxy_list` SET `status` = 0 WHERE `dn_id` =".(int)$dn_id);

}

//Функция чтения файлов куки
public function getCookiesToPage($dn_id){

	//Возврашаемый строку
	$str = '';
	
	//Адресс файла
	$file = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
	if(file_exists($file)){
		$str = file_get_contents($file);
		//преобразуем куки для чтения
		$str = str_replace(['Cookie: ', '; ', ';'], ['', ';', '; '.PHP_EOL], $str);
	}

	return $str;
}

//фунция сохранения куков
public function putCookieInFile($datas, $browser, $dn_id){
	//проверяем нужно ли перезаписывать куки с донора. 
	if($browser['cookie_up'] == 1 && $browser['cookie_use'] == 1){
		//Массив сравнения куков
		$data = [];

		//если куки были не пустые тогда составляем массив
		if(!empty($browser['header_list']['cookies'])){
			$tmp_cookies = explode(';', str_replace('Cookie: ', '', $browser['header_list']['cookies']));
			
			foreach ($tmp_cookies as $tmp_cookie) {
				$tmp_cookie = trim($tmp_cookie);
				$tmp_b = explode('=', $tmp_cookie);
				if(isset($tmp_b[1])){ $data[$tmp_b[0]] = $tmp_b[1]; }
			}
		}

		//теперь приобразовываем новые куки и обновляем старые новыми.
		foreach($datas as $value){

			//отрезаем заголовки.
			$header = substr($value['content'], 0, $value['header_size']);
			#$this->wtfarrey($header);
			//Получаем только куки
			preg_match_all('#set-cookie: (.*?);#im', $header, $cookies);

			//если куки пришли составляем строку для сохранения в файл.

			if(!empty($cookies[1])){
				foreach ($cookies[1] as $cookie) {
					$tmp = explode('=', $cookie);
					if(isset($tmp[1])){ $data[$tmp[0]] = $tmp[1]; }
				}
			}

		}

		if(!empty($data)){ 

			//факел хранения куков
			$file = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
			//строка записи куков в фаел.
			$cookies_str = 'Cookie: ';
			foreach($data as $name_cookie => $value_cookie){
				//пропуск определенной куки
				#if($name_cookie == 'message'){ continue; }
				$cookies_str .= $name_cookie.'='.$value_cookie.'; ';
			}
			//перезаписываем фаил.
			file_put_contents($file, $cookies_str);
			#$this->wtfarrey($cookies_str);

		}
	}
}

//очистка заголовков от ненужных данных. Зашита от дурака.
public function clearHeaders($text){
	#Очишаем при сохранении заголовки от ненужных данных.
	$text = explode(PHP_EOL, $text);

	foreach ($text as $key => $value) {

	  if (empty($value)) {
	    unset($text[$key]);
	  }elseif (preg_match('#^Host:(.*)#im', $value)) {
	    unset($text[$key]);
	  }

	}

	$text = implode(PHP_EOL, $text);
	return $text;
}

//фунция получения кода страницы для просмотра и авторизации
public function getPageToAuth($data, $dn_id){

	$code = $this->CachePage($data['url'], $dn_id);
	return $code;
}

//фунция по сохранению настроек авторизации.
public function saveAuthSettingAjax($data, $dn_id){
	#$this->wtfarrey($data);
	if(empty($data['auth_url'])){ $data['auth_url'] = '';}  
	if(empty($data['auth_type'])){ $data['auth_type'] = 1;}  
	if(empty($data['auth_data'])){ $data['auth_data'] = '';}  
	if(empty($data['auth_url_check'])){ $data['auth_url_check'] = '';}  
	if(empty($data['auth_str'])){ $data['auth_str'] = '';}else{ $data['auth_str'] = trim($data['auth_str']);}  
	$this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET
		auth_url = '".$this->db->escape($data['auth_url'])."',
		auth_data = '".$this->db->escape($data['auth_data'])."',
		auth_type = '".$this->db->escape($data['auth_type'])."',
		auth_url_check = '".$this->db->escape($data['auth_url_check'])."',
		auth_str = '".$this->db->escape($data['auth_str'])."'
		WHERE dn_id =".(int)$dn_id);
}

//Контрольная фуцния по отправке запроса на авторизацию
public function controlBrowserAuth($dn_id, $browser=[]){

	if(empty($browser)){ $browser = $this->getBrowserToCurl($dn_id); }
	//проверяем есть ли ссылка на авторизацию
	if(!empty($browser['auth_url'])){
		$browser = $this->getBrowserToCurl($dn_id);
		$browser['cookie_use'] = 1;
		$browser['cookie_up']  = 1;
		#$this->wtfarrey($browser);
		$urls[] = $browser['auth_url'];
		//факел хранения куков. очишаем
		$file_cookie = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
		file_put_contents($file_cookie, '');
		
		$datas = $this->curlRequestToAuth($urls, $browser, $dn_id);

		//проверка на ошибки
		$code = 0;
		if($datas[$browser['auth_url']]['http_code'] == '200'){
			$code = 1;
			$logs['url'] = $browser['auth_url'];
			$this->log('АuthTrue', $logs, $dn_id);
		}
		return $code;
	}else{
		$this->log('АuthNotUrl', '', $dn_id);
	}

}

//Получения страницы для пред просмотра на авторизацию.
public function controlDownloadPageToAuth($url, $dn_id){
	$urls[] = $url;
	$browser = $this->getBrowserToCurl($dn_id);
	//отключаем кеширование.
	$browser['cache_page'] = 0;
	$datas = $this->multiCurl($urls, $dn_id, $browser);
	#$this->wtfarrey($data);
	$curl_error = $this->sentLogMultiCurl($datas[$url], $dn_id);
	if($curl_error['error']){ 
		$datas[$url]['content'] .= "НЕУДАЧНЫЙ ЗАПРОС!!!".PHP_EOL;
  	$datas[$url]['content'] .= "Код ответа = ".$datas[$url]['errno'].PHP_EOL;
  	$datas[$url]['content'] .= "Текст ответа = ".$datas[$url]['errmsg'].PHP_EOL;
  	$datas[$url]['content'] .= "Ссылка = ".$datas[$url]['url'].PHP_EOL;
  	$datas[$url]['content'] .= "Больше информации можно получить в логах модуля SimplePars";
	}
	#$this->wtfarrey($datas[$url]['content']);
	return $datas[$url]['content'];
}

//фунция проверки авторизации.
public function controlAuthCheck($data, $dn_id){

	$urls[] = $data['auth_url_check'];
	$browser = $this->getBrowserToCurl($dn_id);
	//маячек об авторизации
	$check = 0;
	//отключаем кеширование.
	$browser['cache_page'] = 0;
	$browser['cookie_use'] = 1;

	$cookie_file = DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt';
	
	if(file_exists($cookie_file)){
		$browser['header_list']['cookies'] = file_get_contents(DIR_APPLICATION.'simplepars/cookie/cookie_'.$dn_id.'.txt');
	}

	$datas = $this->multiCurl($urls, $dn_id, $browser);
	#$this->wtfarrey($data);

	$curl_error = $this->sentLogMultiCurl($datas[$data['auth_url_check']], $dn_id);
	//Если нет ошибок. 
	if(empty($curl_error['error'])){ 
		$check = $this->authCheck($datas[$data['auth_url_check']], $browser, $dn_id);
	}

	return $check;
}

//фунция проверки сайта на авторизацию.
public function authCheck($data, $browser, $dn_id){
	#$this->wtfarrey($data);
	$value = 0;
	$browser['auth_str'] = htmlspecialchars_decode($browser['auth_str']);
	if(!empty($browser['auth_str'])){
		if(preg_match('#'.preg_quote($browser['auth_str'], '#').'#sui', $data['content'])){
			$value = 1;
		}
	}else{
		$this->log('АuthNotStr', '', $dn_id);
	}
	
	return $value;
}
############################################################################################
############################################################################################
#						Фунции связанные с СОБСТВЕННЫМИ СКРИПТАМИ.
############################################################################################
############################################################################################

//сохраняем задания по скриптам.
public function scriptSaveTasks($data, $dn_id){

	//Сохраняем настройки в сеттинг.
	if(empty($data['scripts_permit'])){ $data['scripts_permit'] = 0;}
	$this->db->query("UPDATE ".DB_PREFIX."pars_setting SET scripts_permit =".(int)$data['scripts_permit']." WHERE dn_id=".$dn_id);

	//Удаляем все записи про скрипты.
	$this->db->query("DELETE FROM ".DB_PREFIX."pars_phpscripts WHERE dn_id =".$dn_id);
	//записываем задания.
	if(!empty($data['scripts_list'])){
		//Создаем запись задания.
		$temp_arrey = [];
		foreach ($data['scripts_list'] as $key => $script) {
			//исключаем повторения.
			if($key){
				if(in_array($script['name'], $temp_arrey)){ 
					$this->session->data['error'] = 'Внимание!!! Один скрипт нельзя подключать больше одного раза в проекте!!!';
					continue; 
				}
			}
			$temp_arrey[] = $script['name'];
			$this->db->query("INSERT INTO ".DB_PREFIX."pars_phpscripts SET 
				dn_id =".$dn_id.", 
				status =".(int)$script['status'].", 
				name ='".$this->db->escape($script['name'])."', 
				when_do =".(int)$script['when_do'].", 
				sort =".(int)$script['sort'].", 
				comment ='".$this->db->escape($script['comment'])."'
			");

		}
	}
}

//Сохранение и добавление скрипта.
public function scriptAddOrUpdate($data, $dn_id){
	$answer = [];
	//проверяем что бы имя файла не было пустым, и содержало разрешение .php
	if(empty($data['file_name'])){
		$answer['msg'] = 0;
		$answer['error'] = ' Не указано название скрипта. Операция не выполнена.';
		return $answer;
	}else{
		//проверяем что бы было расширение сприпта php
		if(!preg_match('#\.php$#', $data['file_name'])){
			$data['file_name'] = $data['file_name'].'.php';
		}
		//проверяем длину.

		if(iconv_strlen($data['file_name']) < 7){
			$answer['msg'] = 0;
			$answer['error'] = ' Длина имени скрипта не может быть меньше 3 символов + .php';
			return $answer;
		}
	}
 	
 	$file_path = DIR_APPLICATION.'simplepars/scripts/'.$data['file_name'];
 	
 	//проверяем есть ли такой скрипт
 	if(file_exists($file_path)){ $answer['msg'] = 2; }else{ $answer['msg'] = 1; }
 	file_put_contents($file_path, htmlspecialchars_decode($data['code']));
 	
 	$answer['text'] = 'Скрипта успешно сохранен';
	return $answer;
}

//Удаление скрипта.
public function scriptDel($data, $dn_id){
	//Удаляем физически скрипт.
	@unlink(DIR_APPLICATION.'simplepars/scripts/'.$data['file_name']);
	$this->db->query("DELETE FROM ".DB_PREFIX."pars_phpscripts WHERE name ='".$this->db->escape($script['name'])."'");
	$answer['text'] = ' Скрипты был удален, так же были удалены все задания с этим скриптом.';
	return $answer;
}

//Получаем список всех скриптов в директории. 
public function scriptFindAll(){

	$files = glob(DIR_APPLICATION.'simplepars/scripts/*.php'); 
	foreach ($files as &$file) { $file = basename($file);}

	return $files; 
}

//информация об определенном скрипте.
public function scriptGetData($data){
	#$this->wtfarrey($data);
	$script = ['name'=>'', 'code'=>''];
	//путь к хранилищу скриптов. 
	if(!empty($data['script_name'])){ 
		$script['name'] = $data['script_name'];
		$script_path = DIR_APPLICATION.'simplepars/scripts/'.$data['script_name'];
		
		if(file_exists($script_path)){ 
			$script['code'] = file_get_contents($script_path);
		}
	
	}
	#$this->wtfarrey($script);
	return $script;
}

//получаем все задания для этого проекта. 
public function scriptGetAllTask($dn_id){
	$tasks = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_phpscripts WHERE dn_id =".(int)$dn_id." ORDER BY id ASC")->rows;
	return $tasks;
}

//получаем все включенные задания этого проекта. 
public function scriptGetTasksToExe($dn_id){
	$tasks = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_phpscripts WHERE dn_id =".(int)$dn_id." AND status =1 ORDER BY id ASC")->rows;
	return $tasks;
}

//контроллер по запуску собственных скриптов
public function scriptController($when, $dn_id, $script_tasks, $script_data){
	
	if(!empty($script_tasks)){

		$script_path = DIR_APPLICATION.'simplepars/scripts/';
		//если есть такие задания начинаем выполнять.
		foreach ($script_tasks as $task) {
			
			//проверяем есть ли задание в этот момент.
			if($task['when_do'] == $when){
				
				$data = $this->srtiptExecuting($script_path.$task['name'], $dn_id, $script_data);
				#$this->wtfarrey($data);
				
				//если скрипт вернул настройки.
				if(!empty($data['setting'])){ $script_data['setting'] = $data['setting'] ;}
				//если скрипт вернул настройки браузера.
				if(!empty($data['browser'])){ $script_data['browser'] = $data['browser'] ;}

				//проверка что запускали, что бы занть что смотреть а что нет.
				if($when == 1){

					//если скрипт передает ссылки.
					if(!empty($data['urls'])){ $script_data['urls'] = $data['urls'] ;}

				}elseif($when == 2 || $when == 3){
					//если скрипт передает форму.
					if(!empty($data['form'])){ $script_data['form'] = $data['form'] ;}
					if(!empty($data['permit'])){ $script_data['permit'] = $data['permit'] ;}

				}elseif($when == 4){

					//если скрипт передает csv данные
					if(!empty($data['csv'])){ $script_data['csv'] = $data['csv'] ;}

				}

				unset($data);

			}
		}
	}

	return $script_data;
}

//фунция выполняющаяя пользовательский скрипт.
public function srtiptExecuting($path, $dn_id, $script_data){
	
	if(file_exists($path)){

		//Другого способа передать информацию о проекте в обработчик не нешаел.
		//Если знаете подскажите. 
		$_SERVER['dn_id'] = $dn_id;
		//Отправляем отчет об обновлении.
		$this->log('ScraptStartExecuting', $path, $dn_id);
		set_error_handler(array($this, "myErrorHandler"));
		include $path;
		restore_error_handler();

	}

	if(!empty($script_data)){ return $script_data; }
}

// Собственная функция обработки ошибок
public function myErrorHandler($errno, $errstr, $errfile, $errline){
  if(!(error_reporting() & $errno)) {
    // Этот код ошибки не включён в error_reporting,
    // так что пусть обрабатываются стандартным обработчиком ошибок PHP
    return false;
  }

  // может потребоваться экранирование $errstr:
  $errstr = htmlspecialchars($errstr);

  if($errno) {
  	$log = ['errno' =>$errno ,'errstr'=>$errstr ,'errfile'=>$errfile, 'errline'=>$errline];
  	$this->log('ScraptMyErrorHandler', $log, $_SERVER['dn_id']);
  }

  /* Не запускаем внутренний обработчик ошибок PHP */
  return true;
}
############################################################################################
############################################################################################
#						Фунции отвечающие за парсинг. Парсин страницы, разбор данных все здесь
############################################################################################
############################################################################################

//Фунция мульти курл отправление запросов
public function multiCurl($urls, $dn_id, $browser=[]){

  //получаем настройки браузера, ели они не пришли из вне.
  if(empty($browser)){
  	$browser = $this->getBrowserToCurl($dn_id);
	}

  $datac = [];
  $datas = [];

  //Проверяем нужно ли использовать кеш.
  if($browser['cache_page'] == 1){

  	//проверяем есть ли кеш ссылки, если есть удаляем ее из массива на парсинг.
  	foreach ($urls as $key => $link) {
  		$datac[$link] = $this->getCachePageFromFile($link, $browser, $dn_id);
  		if($datac[$link]){ 
  			unset($urls[$key]);
  		}else{
  			unset($datac[$link]);
  		}
  	}

  }

  //проверяем пустой ли массив или нет. Если не пустой отправляем его на выполнение курлом. 
  if($urls){
    $datas = $this->curlRequest($urls, $browser, $dn_id);
  }

  //обьеденяем кеш, и новые ссылки.
  foreach($datac as $key => $value){
  	$datas[$key] = $value;
  }

  return $datas;
}

public function curlRequest($urls, $browser, $dn_id){
	//Формируем массив для ответа.
  $datas = array();
	#$this->wtfarrey($browser);
	//Создаем дескотпьлр запроса
  $mh = curl_multi_init();
  $curl_array = array();
  foreach($urls as $i => $url) {
  	$url = $this->urlEncoding($url);
  	#$this->wtfarrey($url);
   	$curl_array[$i] = curl_init($url);
	  //настраиваем браузер
	  curl_setopt($curl_array[$i], CURLOPT_HTTP_VERSION, $browser['protocol_version']);
	  curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1);                            // возвращает веб-страницу
	  curl_setopt($curl_array[$i], CURLOPT_HEADER, 1);               											// возвращает заголовки
	  curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, $browser['followlocation']);   // переходит по редиректам
	  curl_setopt($curl_array[$i], CURLOPT_MAXREDIRS, 100);                               // останавливаться после 10-ого редиректа
	  curl_setopt($curl_array[$i], CURLOPT_ENCODING, "");                                 // обрабатывает все кодировки 
	  #curl_setopt($curl_array[$i], CURLOPT_USERAGENT, $browser['user_agent_list']);      // useragent ПЕРЕДАЮ ЧЕРЕЗ ЗАГОЛОВКИ
	  curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, $browser['timeout']);     						// Максимально позволенное количество секунд для выполнения cURL-функций.
	  curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, $browser['timeout']);  				// таймаут соединения
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER, FALSE);                        // Отключить проверку сертификата.
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYHOST, FALSE);                        // проверяет принадлежность сертификата к сайту.
	  curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, $browser['header_list']);

	  //Использование прокси
	  if($browser['proxy_use']){
	  	curl_setopt($curl_array[$i], CURLOPT_PROXY, $browser['proxy']['ip:port']);				// Указываем прокси ип и порт
			curl_setopt($curl_array[$i], CURLOPT_PROXYTYPE, $browser['proxy']['type']);				// Тип прокси
			#Если указан логин и пароль прокси
			if(!empty($browser['proxy']['loginpass'])){																				// логин пароль от прокси
			 	curl_setopt($curl_array[$i], CURLOPT_PROXYUSERPWD, $browser['proxy']['loginpass']);
			}
	  }

	  //использование авторизации htpasswd
	  if($browser['auth_type'] == 2){
	  	curl_setopt($curl_array[$i], CURLOPT_USERPWD, $browser['auth_data']);
	  }

	  curl_multi_add_handle($mh, $curl_array[$i]);
  }

  //Волшебные строки мульти запроса.
  $running = NULL;
  do {
    usleep(10000);
    curl_multi_exec($mh,$running);
  } while($running > 0);

  //Формируем ответ.
  foreach($urls as $i => $url) {
    $datas[$url] = curl_getinfo($curl_array[$i]);
    $erno = curl_multi_info_read($mh);
    $datas[$url]['url'] = $url;
    $datas[$url]['errno'] = $erno['result'];
    $datas[$url]['errmsg'] = curl_error($curl_array[$i]);
    $datas[$url]['sp_log'] = 'log_curl';
    $datas[$url]['browser'] = $browser;
    $datas[$url]['content'] = curl_multi_getcontent($curl_array[$i]);
  }
  
  foreach($urls as $i => $url){
    curl_multi_remove_handle($mh, $curl_array[$i]);
  }
  curl_multi_close($mh);
  #$this->wtfarrey($data);
  //отправляем на обработку куки.
  $this->putCookieInFile($datas, $browser, $dn_id);
  #$this->wtfarrey($datas);
  //по очереди обрабатываем каждый ответ из мулти запроса. И дорабатываем нужные элементы
  foreach ($datas as $key => $data) {

  	//Если сайт вернул не пустую страницу добавляем туд ссылку на сайт.
    if(!empty($data['content'])){ 
    	//отрезаем заголовки если они не включены в настройках запроса.
    	if(empty($browser['header_get'])){ $data['content'] = substr($data['content'], $data['header_size']); }
 			//Добавляем в каждое тело ссылку на страницу что парсили. 
    	$data['content'] = '#[url]'.$key.'[/url]'.PHP_EOL.$data['content'];
    }
    #$this->wtfarrey($data['content']);
   	$data = $this->findCharsetSite($data, $dn_id);
	 	$datas[$key] = $data;
  }


  //проверяем нужно ли работать с кешом. И при необходимости записываем его в файл.
  if( ($browser['cache_page'] == 1) || ($browser['cache_page'] == 2) ){
	  foreach($datas as $key => $data){
	  	//делаем проверку что бы не кешировать страницы с ошибками.
	  	if($data['errno'] == 0) {
	   		$this->putCachePageOnFile($data, $key, $dn_id);
	   	}
	  }
	}

	#$this->wtfarrey($datas);
  return $datas;
}

//Запрос на авторизацию.
public function curlRequestToAuth($urls, $browser, $dn_id){
	#$this->wtfarrey($browser);
	#$this->wtfarrey($browser['auth_data']);

  //Формируем массив для ответа.
  $datas = array();
	#$this->wtfarrey($browser);
	//Создаем дескотпьлр запроса
  $mh = curl_multi_init();
  $curl_array = array();
  foreach($urls as $i => $url) {
  	$url = $this->urlEncoding($url);
  	#$this->wtfarrey($url);
   	$curl_array[$i] = curl_init($url);
	  //настраиваем браузер
	  curl_setopt($curl_array[$i], CURLOPT_HTTP_VERSION, $browser['protocol_version']);
	  curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1);                            // возвращает веб-страницу
	  curl_setopt($curl_array[$i], CURLOPT_HEADER, 1);               											// возвращает заголовки
	  curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, 1);   													// переходит по редиректам
	  curl_setopt($curl_array[$i], CURLOPT_MAXREDIRS, 100);                               // останавливаться после 10-ого редиректа
	  curl_setopt($curl_array[$i], CURLOPT_ENCODING, "");                                 // обрабатывает все кодировки 
	  curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, $browser['timeout']);     						// Максимально позволенное количество секунд для выполнения cURL-функций.
	  curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, $browser['timeout']);  				// таймаут соединения
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER, FALSE);                        // Отключить проверку сертификата.
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYHOST, FALSE);                        // проверяет принадлежность сертификата к сайту.
	  curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, $browser['header_list']);
	  #$this->wtfarrey($browser['auth_data']);
	  //данные на авторизацию
	  if($browser['auth_type'] == 1){
		  curl_setopt($curl_array[$i], CURLOPT_POST, 1);
	    curl_setopt($curl_array[$i], CURLOPT_POSTFIELDS, $browser['auth_data']);
  	}elseif($browser['auth_type'] == 2){
	  	//использование авторизации htpasswd
	  	curl_setopt($curl_array[$i], CURLOPT_USERPWD, $browser['auth_data']);
	  }


	  //Использование прокси
	  if($browser['proxy_use']){
	  	curl_setopt($curl_array[$i], CURLOPT_PROXY, $browser['proxy']['ip:port']);				// Указываем прокси ип и порт
			curl_setopt($curl_array[$i], CURLOPT_PROXYTYPE, $browser['proxy']['type']);				// Тип прокси
			#Если указан логин и пароль прокси
			if(!empty($browser['proxy']['loginpass'])){																				// логин пароль от прокси
			 	curl_setopt($curl_array[$i], CURLOPT_PROXYUSERPWD, $browser['proxy']['loginpass']);
			}
	  }

	  curl_multi_add_handle($mh, $curl_array[$i]);
  }

  //Волшебные строки мульти запроса.
  $running = NULL;
  do {
    usleep(10000);
    curl_multi_exec($mh,$running);
  } while($running > 0);

  //Формируем ответ.
  foreach($urls as $i => $url) {
    $datas[$url] = curl_getinfo($curl_array[$i]);
    $erno = curl_multi_info_read($mh);
    $datas[$url]['url'] = $url;
    $datas[$url]['errno'] = $erno['result'];
    $datas[$url]['errmsg'] = curl_error($curl_array[$i]);
    $datas[$url]['sp_log'] = 'log_curl';
    $datas[$url]['browser'] = $browser;
    $datas[$url]['content'] = curl_multi_getcontent($curl_array[$i]);
  }
      
  foreach($urls as $i => $url){
    curl_multi_remove_handle($mh, $curl_array[$i]);
  }
  curl_multi_close($mh);

  //отправляем на обработку куки.
  $this->putCookieInFile($datas, $browser, $dn_id);

	#$this->wtfarrey($datas);
  return $datas;
}

//Фунция скачивания img
public function curlImg($urls,  $browser=[], $dn_id){
	#$this->wtfarrey($urls);
	//Формируем массив для ответа.
  $datas = array();
	#$this->wtfarrey($browser);
	//Создаем дескотпьлр запроса
  $mh = curl_multi_init();
  $curl_array = array();
  $uagent = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36";
  foreach($urls as $i => $url) {
  	#$this->wtfarrey($url);
   	$curl_array[$i] = curl_init($url);
	  //настраиваем браузер
	  curl_setopt($curl_array[$i], CURLOPT_HTTP_VERSION, $browser['protocol_version']);
	  curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1);                            // возвращает веб-страницу
	  curl_setopt($curl_array[$i], CURLOPT_HEADER, 0);               											// возвращает заголовки
		curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, $browser['followlocation']);   // переходит по редиректам
		curl_setopt($curl_array[$i], CURLOPT_MAXREDIRS, 100);                               // останавливаться после 10-ого редиректа
	  curl_setopt($curl_array[$i], CURLOPT_ENCODING, "");                                 // обрабатывает все кодировки 
	  curl_setopt($curl_array[$i], CURLOPT_USERAGENT, $uagent);      											// useragent ПЕРЕДАЮ ЧЕРЕЗ ЗАГОЛОВКИ
	  curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, 20);     															// Максимально позволенное количество секунд для выполнения cURL-функций.
	  curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 20);  													// таймаут соединения
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER, FALSE);                        // Отключить проверку сертификата.
	  curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYHOST, FALSE);  											// проверяет принадлежность сертификата к сайту.
	  if(!empty($browser)){                      
	  	curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, $browser['header_list']);
	  }
	  //Использование прокси
	  if($browser['proxy_use']){
	  	curl_setopt($curl_array[$i], CURLOPT_PROXY, $browser['proxy']['ip:port']);				// Указываем прокси ип и порт
			curl_setopt($curl_array[$i], CURLOPT_PROXYTYPE, $browser['proxy']['type']);				// Тип прокси
			#Если указан логин и пароль прокси
			if(!empty($browser['proxy']['loginpass'])){														// логин пароль от прокси
			 	curl_setopt($curl_array[$i], CURLOPT_PROXYUSERPWD, $browser['proxy']['loginpass']);
			}
	  }

	  //использование авторизации htpasswd
	  if($browser['auth_type'] == 2){
	  	curl_setopt($curl_array[$i], CURLOPT_USERPWD, $browser['auth_data']);
	  }

	  curl_multi_add_handle($mh, $curl_array[$i]);
  }

  //Волшебные строки мульти запроса.
  $running = NULL;
  do {
    usleep(10000);
    curl_multi_exec($mh,$running);
  } while($running > 0);

  //Формируем ответ.
  foreach($urls as $i => $url) {
    $datas[$i] = curl_getinfo($curl_array[$i]);
    #$erno = curl_multi_info_read($mh);
    $datas[$i]['content'] = curl_multi_getcontent($curl_array[$i]);
  }
  
  //Формируем ответ.
  foreach($urls as $i => $url) {
    $datas[$i] = curl_getinfo($curl_array[$i]);
    $erno = curl_multi_info_read($mh);
    $datas[$i]['url'] = $url;
    $datas[$i]['errno'] = $erno['result'];
    $datas[$i]['errmsg'] = curl_error($curl_array[$i]);
    $datas[$i]['content'] = curl_multi_getcontent($curl_array[$i]);

  }

  foreach($urls as $i => $url){
    curl_multi_remove_handle($mh, $curl_array[$i]);
  }
  curl_multi_close($mh);

  #$this->wtfarrey($datas);
  $imgs = [];
  //по очереди обрабатываем каждый ответ из мулти запроса.
  foreach ($datas as $key => $data) {
  	//пишем логи.
   	$this->log('curlImg', $data, $dn_id);
   	//если получи по фото что то больше 302 ответа, не записываем это в массив
  	if($data['http_code'] > 302){ continue; }

   	$imgs[] = [
   							'url'=> $data['url'], 
   							'img' => $data['content']
   						];
  }
  unset($datas);
	#$this->wtfarrey($imgs);
  return $imgs;
}

public function requestConstructor($why, $urls, $dn_id, $browser, $setting, $try=0){
	//why = 1: Обычный парсинга. why = 0 сбор ссылок.
	//проверяем кто вызывает действие, граб или все остальные.
	if($why){

		$table = DB_PREFIX."pars_link";
		$datas = $this->multiCurl($urls, $dn_id, $browser);

	}else{

		$table = DB_PREFIX."pars_sen_link";

		//определяем через что будем парсить, cURL или архиватор.
		if ($setting['type_grab'] == 3) {
			//парсинг через архиватор
			$datas = $this->getPageFromGzip($urls, $dn_id);
			//Доп параметр допуска к авторизации, для отбрасывания сбора ссылок в режиме сжатия.
			$dont_auth = 1;
		}elseif($setting['type_grab'] == 2){
			$datas = $this->multiCurl($urls, $dn_id, $browser);
			//Доп параметр допуска к авторизации, для отбрасывания сбора ссылок в режиме sitemap.xml
			$dont_auth = 1;
		}else{
			$datas = $this->multiCurl($urls, $dn_id, $browser);
		}

	}

	//Далее разбираем данные из мульти курла и делаем все нужные записи.
	foreach($datas as $key => $data){
		//производим зяпись лога курл, и паролельно проверяем нужно ли делать дальнейшую работу.
		$curl_error = $this->sentLogMultiCurl($data ,$dn_id);

		if($why){ $set = ", error='".$curl_error['http_code']."'";}else{ $set = ''; }# Помечаем ссыли ошибками, если это обычный парсинг
		#помечаем ссылку как отсканированная
  	$this->db->query("UPDATE ".$table." SET scan=0".$set." WHERE link='".$this->db->escape($data['url'])."' AND dn_id=".$dn_id);

		//если пришла ошибка заканчиваем эту итерацию и переходим к следующей.
		if(!empty($curl_error['http_code'])){
			//если из ответа сервера страница не прошла проверку удаляем ее из массива. 
			unset($datas[$key]);
			continue;
		}

		//если включена проверка на авторизацию.
		if($browser['auth_use'] == 1 && empty($dont_auth)){
			
			//Маячек на перезапуск парсинга из за авторизации.
			$auth_mark = 1;
			
			//проверяем авторизован ли пользователь.
			$auth_check = $this->authCheck($data, $browser, $dn_id);
			
			//если авторизация не подтверждена отмечаем ссылку как не отсканированая.
			if(empty($auth_check)){
				$auth_mark = 0;
				
				if(empty($try)){ 
					$this->log('АuthCheckIsFalse', $browser['auth_str'], $dn_id);
				}else{
					#если была повторная попытка, значит заканчиваем этот цирк.
					$this->log('АuthCheckIsFalseAgain', $browser['auth_str'], $dn_id);
				}
				
				//Если хоть одна ссылка не прошла проверку, отменяем работу всего пула. 
				foreach($urls as $url){ 
					$this->db->query("UPDATE ".$table." SET scan=1 WHERE link='".$this->db->escape($data['url'])."' AND dn_id=".$dn_id);
				}


				//Завершаем этот цикл если это первый подход. Если второй то вырубаем все это.
				if(empty($try)){
					break;
				}else{
					$pars_url = $this->getUrlToPars($dn_id, $setting['link_list'], $setting['link_error']);
					#считаем процент для прогрес бара
    			$scan = ($pars_url['total']-$pars_url['queue']);
    			if(empty($scan)){ $scan = 1;}
    			if(empty($pars_url['total'])){ $pars_url['total'] = 1;}
    			$progress = $scan/($pars_url['total']/100);
    			$answ['progress'] = $progress;
    			$answ['clink'] = ['link_scan_count' => $scan, 'link_count' => $pars_url['queue'],];

					$this->answjs('finish',' Сбой авторизации',$answ);

				}
				
			}
		}
	}
	// Тут проверяем была ли работа с авторизацией, закончилась ли она удчано.
	// И при необходимости перезапускаем фунцию
	// Перезапускается если
	// 0. В конце пула не может быть ошибки. Если ошибка отправляем на пересмотр.
	// 1. Включена проверка авторизации
	// 2. Пометака неудачной авторизации пустая
	// 3. Пометка что это первый запрос тоже рано 0
	if(empty($curl_error['http_code']) && $browser['auth_use'] == 1 && empty($auth_mark) && empty($try)){
		$this->controlBrowserAuth($dn_id, $browser);
		$browser = $this->getBrowserToCurl($dn_id);
		$datas = $this->requestConstructor($why, $urls, $dn_id, $browser, $setting, 1);
	}

	return $datas;
}

//Конструктор запроса, промежуточная фунция которая делает запросы и проверяет что бы
//ответ соотвецвовал всем параметрам. Небыло ошибок, прошла авторизация.
//при необходимости она можете повторно запустится.
public function requestConstructorCron($why, $urls, $dn_id, $browser, $setting, $task, $try=0){
	//why = 1: Обычный парсинга. why = 0 сбор ссылок.
	//проверяем кто вызывает действие, граб или все остальные.
	if($why){

		$table = DB_PREFIX."pars_link";
		$datas = $this->multiCurl($urls, $dn_id, $browser);

	}else{

		$table = DB_PREFIX."pars_sen_link";

		//определяем через что будем парсить, cURL или архиватор.
		if ($setting['type_grab'] == 3) {
			//парсинг через архиватор
			$datas = $this->getPageFromGzip($urls, $dn_id);
			//Доп параметр допуска к авторизации, для отбрасывания сбора ссылок в режиме сжатия.
			$dont_auth = 1;
		}elseif($setting['type_grab'] == 2){
			$datas = $this->multiCurl($urls, $dn_id, $browser);
			//Доп параметр допуска к авторизации, для отбрасывания сбора ссылок в режиме sitemap.xml
			$dont_auth = 1;
		}else{
			$datas = $this->multiCurl($urls, $dn_id, $browser);
		}

	}

	//Далее разбираем данные из мульти курла и делаем все нужные записи.
	foreach($datas as $key => $data){
		//производим зяпись лога курл, и паролельно проверяем нужно ли делать дальнейшую работу.
		$curl_error = $this->sentLogMultiCurl($data ,$dn_id);

		if($why){ $set = ", error='".$curl_error['http_code']."'";}else{ $set = ''; }# Помечаем ссыли ошибками, если это обычный парсинг

		#помечаем ссылку как отсканированная
  	$this->db->query("UPDATE ".$table." SET scan_cron=0".$set." WHERE link='".$this->db->escape($data['url'])."' AND dn_id=".$dn_id);

		//если пришла ошибка заканчиваем эту итерацию и переходим к следующей.
		if(!empty($curl_error['http_code'])){
			//если из ответа сервера страница не прошла проверку удаляем ее из массива. 
			unset($datas[$key]);
			continue;
		}

		//если включена проверка на авторизацию.
		if($browser['auth_use'] == 1 && empty($dont_auth)){
			
			//Маячек на перезапуск парсинга из за авторизации.
			$auth_mark = 1;
			
			//проверяем авторизован ли пользователь.
			$auth_check = $this->authCheck($data, $browser, $dn_id);
			
			//если авторизация не подтверждена отмечаем ссылку как не отсканированая.
			if(empty($auth_check)){
				$auth_mark = 0;
				
				if(empty($try)){ 
					$this->log('CronАuthCheckIsFalse', $browser['auth_str'], $dn_id);
				}else{
					#если была повторная попытка, значит заканчиваем этот цирк.
					$this->log('CronАuthCheckIsFalseAgain', $browser['auth_str'], $dn_id);
				}
				
				//Если хоть одна ссылка не прошла проверку, отменяем работу всего пула. 
				foreach($urls as $url){ 
					$this->db->query("UPDATE ".$table." SET scan_cron=1 WHERE link='".$this->db->escape($data['url'])."' AND dn_id=".$dn_id);
				}


				//Завершаем этот цикл если это первый подход. Если второй то вырубаем все это.
				if(empty($try)){
					break;
				}else{
					//завершаем выполнение крон задания без выполнения доп фунций.
					$time = time();
					//Ставит состояние старт заданию
					$this->db->query("UPDATE ".DB_PREFIX."pars_cron_list SET `time_end` = '".$this->db->escape($time)."', `status` = 'end' WHERE id = ".(int)$task['id']);
					$this->db->query("UPDATE ".DB_PREFIX."pars_cron_tools SET `scan` = 0 WHERE task_id = ".(int)$task['id']);
					$this->cronUnbloking();
					$this->cronRestart();
				}
				
			}
		}

	}

	// Тут проверяем была ли работа с авторизацией, закончилась ли она удчано.
	// И при необходимости перезапускаем фунцию
	// Перезапускается если
	// 0. Нет ошибок парсинга
	// 1. Включена проверка авторизации
	// 2. Пометака неудачной авторизации пустая
	// 3. Пометка что это первый запрос тоже рано 0
	// 4. Это не запрос на сбор ссылок в режиме gzip
	if(empty($curl_error['http_code']) && $browser['auth_use'] == 1 && empty($auth_mark) && empty($try) && empty($gzip)){
		$this->controlBrowserAuth($dn_id, $browser);
		$browser = $this->getBrowserToCurl($dn_id);
		$datas = $this->requestConstructorCron($why, $urls, $dn_id, $browser, $setting, $task, 1);
	}

	return $datas;
}

//Данная фунция морально устарела. Когду бедут время прокачай ее.
public function CachePage($url, $dn_id){
	$url = str_replace('&amp;', '&', $url);
	$browser = $this->getBrowserToCurl($dn_id);
	//Выполняем запрос
	$urls[] = $url;
	$datas = $this->multiCurl($urls, $dn_id, $browser);
	#$this->wtfarrey($datas);
	//пишем логи, но не проверяем ошибку она не нужна в пред просмотре.
	$curl_error = $this->sentLogMultiCurl($datas[$url], $dn_id);
	if($curl_error['error']){ 
		$datas[$url]['content'] .= "НЕУДАЧНЫЙ ЗАПРОС!!!".PHP_EOL;
  	$datas[$url]['content'] .= "Код ответа = ".$datas[$url]['errno'].PHP_EOL;
  	$datas[$url]['content'] .= "Текст ответа = ".$datas[$url]['errmsg'].PHP_EOL;
  	$datas[$url]['content'] .= "Ссылка = ".$datas[$url]['url'].PHP_EOL;
  	$datas[$url]['content'] .= "Больше информации можно получить в логах модуля SimplePars";
	}

	//если включена проверка на авторизацию.
	if($browser['auth_use'] == 1){
		
		//проверяем авторизован ли пользователь.
		$auth_check = $this->authCheck($datas[$url], $browser, $dn_id);
		
		//если авторизация не подтверждена отмечаем ссылку как не отсканированая.
		if(empty($auth_check)){
			//записываем что авторизация была сброшена донором
			$this->log('АuthCheckIsFalse', $browser['auth_str'], $dn_id);
			//отправляем запрос на авторизацию.
			$this->controlBrowserAuth($dn_id, $browser);
			//получаем новые куки и передаем на повторных запрос
			$browser = $this->getBrowserToCurl($dn_id);
			
			//Дальше делаем все по второму кругу. 
			$datas = $this->multiCurl($urls, $dn_id, $browser);
			$curl_error = $this->sentLogMultiCurl($datas[$url], $dn_id);
			if($curl_error['error']){ 
				$datas[$url]['content'] .= "НЕУДАЧНЫЙ ЗАПРОС!!!".PHP_EOL;
		  	$datas[$url]['content'] .= "Код ответа = ".$datas[$url]['errno'].PHP_EOL;
		  	$datas[$url]['content'] .= "Текст ответа = ".$datas[$url]['errmsg'].PHP_EOL;
		  	$datas[$url]['content'] .= "Ссылка = ".$datas[$url]['url'].PHP_EOL;
		  	$datas[$url]['content'] .= "Больше информации можно получить в логах модуля SimplePars";
			}

			//еше раз проверяем прошла ли авторизация.
			$auth_check = $this->authCheck($datas[$url], $browser, $dn_id);
			//если нет, дописуем пичальноеу уведомления об этом и отдаем то что есть.
			if(empty($auth_check)){
				$datas[$url]['content'] = 
				'#######################################################################################################'.PHP_EOL.
				'# Авторизация на сайте доноре не сработала!!!                                                         #'.PHP_EOL.
				'# В коде страницы не найдет текст для проверки авторизации                                            #'.PHP_EOL.
				'#                                                                                                     #'.PHP_EOL.
				'# Что делать.                                                                                         #'.PHP_EOL.
				'# 1. Убедитесь что у вас не включен кэш                                                               #'.PHP_EOL.
				'# 2. Проверьте настройки авторизации на вкладке Настройки запроса.                                    #'.PHP_EOL.
				'# 3. Убедитесь что на странице которую вы пытаетесь спарсить есть проверочный текст после авторизации.#'.PHP_EOL.
				'#######################################################################################################'.PHP_EOL.PHP_EOL.$datas[$url]['content'];
			}
		}
	}

	#$this->wtfarrey($datas[$url]['content']);
	return $datas[$url]['content'];
}

public function parsParam($html, $param_id, $sql_params){
	#$this->wtfarrey($param);
	$param = ( !empty($sql_params[$param_id]) ) ? $sql_params[$param_id] : [];
	//дабавим немного жару в эту скучную жизнь xD
	$reg_ruls = ['\{skip\}'=>'.*?','\{br\}'=>'\\r?\\n','\{\.\*\}'=>'.*','\{\.\}'=>'.'];

	if($param['type'] == 1){

		//Обычные границы парсинга
	  $start = htmlspecialchars_decode($param['start']);
		$stop = htmlspecialchars_decode($param['stop']);

		//создаем правила для поиска границы. Так же подставляем {skip} !
		$reg = '#'. strtr(preg_quote($start, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop, '#'), $reg_ruls) .'#su';
		preg_match_all($reg, $html, $value);


		//Выбираем нужные элемент, по настройкам пропуска
		$value[$param['with_teg']] = $this->skipEntryParam($value[$param['with_teg']], 1, $param['skip_where'], $param['skip_enter']);

		//Проверяем на присуцтвие.
		if(empty($value[$param['with_teg']])){ $value[$param['with_teg']][0]=''; }

		$pars_data = $value[$param['with_teg']][0];

	}elseif($param['type'] == 2){

		//повторяющиеся границы
		//Получаем данные базовой границы
		if($param['base_id'] != 0){
			#$param_base = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_param WHERE id=".(int)$param['base_id'])->row;
			$param_base = ( !empty($sql_params[$param['base_id']]) ) ? $sql_params[$param['base_id']] : [];

			$start_base = htmlspecialchars_decode($param_base['start']);
	   	$stop_base = htmlspecialchars_decode($param_base['stop']);

	   	$reg = '#'.strtr(preg_quote($start_base, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop_base, '#'), $reg_ruls).'#su';

	   	preg_match_all($reg, $html, $code);

			if(empty($code[$param_base['with_teg']][0])){$code[$param_base['with_teg']][0]=' ';}

	   	$code[$param_base['with_teg']][0] = $this->findReplace($code[$param_base['with_teg']][0], $param['base_id']);

			// определяем порядок вхождения
			$code[$param_base['with_teg']] = $this->skipEntryParam($code[$param_base['with_teg']], 1, $param_base['skip_where'], $param_base['skip_enter']);
			#$this->wtfarrey($code[$param_base['with_teg']]);
			//Если пустой массив тогда делаем в нем пробел. Непомню почему я так решил, но знаю что нужно так.
			if(empty($code[$param_base['with_teg']][0])){ $code[$param_base['with_teg']][0]=''; }

		}else{
				$code[0][0] = $html;
				//Если используется вся страница
				$param_base['with_teg'] = 0;
		}

  	//А теерь повторяющие границы парсинга
	 	$start = htmlspecialchars_decode($param['start']);
	  $stop = htmlspecialchars_decode($param['stop']);
	  
	  //!--Во фикс проблемы с пустой границей. Можено удалить в случаи чего. Не критичный косяк
	  if(empty($start)){ $start = 'simpleparsrassol2';}
	  if(empty($stop)){ $stop = 'simpleparsrassol2';}
	  //--!конец фикса.

	 	$reg = '#'.strtr(preg_quote($start, '#'), $reg_ruls).'(.*?)'.strtr(preg_quote($stop, '#'), $reg_ruls).'#su';

	 	preg_match_all($reg, $code[$param_base['with_teg']][0], $values);

	 	$pars_data = $this->skipEntryParam($values[$param['with_teg']], 2, $param['skip_where'], $param['skip_enter']);

	 	//Используем реверс если он задан
	 	if($param['reverse'] == 1){
	 		$pars_data = array_reverse($pars_data);
	 	}

	}

	#$this->wtfarrey($pars_data);
	return $pars_data;
}

//Подсчет пропуска вхождений. Выбираем какую итерацию пропустить.
public function skipEntryParam($data, $type, $skip_where, $num=0){

	//проверяем данные
	if(!empty($data) && !empty($type) && $num !== 0){

		//Поскольку к каждой границе свой подход определяем тип
		if($type ==1){

			$num = (int)$num;

			if($skip_where==2){
				$num = -($num + 1);
			}

			$data = array_slice($data, $num, 1);


		}elseif($type ==2){

			$num = explode('-', $num);
			#Приводим в порядок диапазоны
			if(empty((int)$num[0])){ $num[0] = 0 ;}

			if(empty($num[1])){
			 $num[1] = null;
			}else{
				$num[1] = (int)$num[1];
			}

			//Если пользователь что то криво написал выходим из общета отдаем как есть.
			if($num[0] == 0 && $num[1] == 0){
				return $data;
			}

			#производим определяем сторону отсчета.
			if($skip_where == 2){

				if($num[1] === null){
					$num[1] = -($num[0]);
					$num[0] = 0;
				}else{
					$num[0] = -($num[0] + $num[1]);
				}
				//производим преобразования массива, откидываем ненужные значения.
				$data = array_slice($data, $num[0], $num[1]);

			}elseif($skip_where == 3){

				$data = array_slice($data, $num[0], null);

				if($num[1] != 0){
					$data = array_slice($data, null, -$num[1]);
				}

			}else{
				//производим преобразования массива, откидываем ненужные значения.
				@$data = array_slice($data, $num[0], $num[1]);
			}
			#$this->wtfarrey($num);
		}

	}


	return $data;
}

//Пределяем кодировку сайта.
public function findCharsetSite($data, $dn_id){
	#$this->wtfarrey($data);
	//если файл больше 10мб не переводим его.
	if($data['size_download'] < 1024000){
		$bad_car = ['UTF8'];

	  #Получаем иходную кодировку из заголовка.
	  preg_match('#charset\=(.*?)$#Ui', $data['content_type'], $chrs);

	  if(!empty($chrs[1])){
	    $charset = $chrs[1];
	  }else{
	    $charset = '';
	  }
	  #$this->wtfarrey($charset);
		$charset = str_replace($bad_car, 'UTF-8', $charset);
	  //Еше один костыль который нужно будет исправлять в будушем.
	  if(empty($charset)){

	      #$reg = '#charset=(.*?)"#U';
	      #$reg = '#charset=(.*?)"#Ui';
	      $reg = '#charset=["]?(.*?)"#i';
				preg_match($reg, $data['content'], $chrs);

				if(!empty($chrs[1])){
					$charset = $chrs[1];
				}else{
					//для xml
					if( preg_match('#encoding\=\"(.*?)\"#', $data['content'], $chrs) ){
						$charset = trim($chrs[1]);
					}elseif( preg_match('#encoding\=\&quot;(.*?)\&quot\;#', $data['content'], $chrs) ) {
						$charset = trim($chrs[1]);
					}

					//если и ту ничего нет, тогда по умолчанию.
					if(empty($charset)){
						$charset = 'UTF-8';
					}
				}
	  }

	  #$this->wtfarrey($charset);
	  //Вычишаем всякий шлак, по мере обнаружения буду увеличивать правила.
	  $charset = str_ireplace('"', '', $charset);
	  #$this->wtfarrey($charset);
	  //Перекодируем страницу в UTF-8
  	$data['content'] = @mb_convert_encoding($data['content'], "UTF-8", $charset);
  	$data['content'] = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', array($this, 'unicode_decode'), $data['content']);
  }

  return $data;
}

//запись лога мульти курла.
public function sentLogMultiCurl($data, $dn_id){
	//маячек об ошибке.
	$value['error'] = 0;
	$value['http_code'] = '';

	//по очереди обрабатываем каждый ответ из мулти запроса.
	if($data['errno'] > 0) {
		//если ошибка
		$value['error'] = 1;
		$value['http_code'] = $data['errno'];
  	$this->log($data['sp_log'], $data, $dn_id);

	} else {

		if(empty($data['content'])){
  		$data['content'] ='Страница не загружена, проверьте ссылку. Если ссылка на сайт открывается у вас в браузере то сообщите разработчику модуля эту ссылку для проверки и устранения проблемы.';
  	}

  	//для деления ссылок по спискам.
  	if($data['http_code'] > 302){ 
  		$value['http_code'] = $data['http_code'];
  	} 

  	$this->log($data['sp_log'], $data, $dn_id);
	}

	#$this->wtfarrey($value);
	return $value;    
}

//Фунциял записи страницы в кеш.
public function putCachePageOnFile($data, $link, $dn_id){
	//директория хранения кеша.
	$cache_dir = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/';
	//Имя конкретной странице кеша, равна ключу ссылок данного донора.
	$file = md5($dn_id.$link).'.txt';
	//Полный путь к файлу. 
	$file = $cache_dir.$file;
   
	//Делаем метку что страница из кеша и записываем в файл.
	$text = '###########################################################'.PHP_EOL.
					'# ВНИМАНИЕ!!! Страница взята из кеша модуля SimplePars!!! #'.PHP_EOL.
					'# Дата создания кеша - '.date("Y-m-d H:i:s").'                #'.PHP_EOL.
					'###########################################################'.PHP_EOL.PHP_EOL.$data['content'];
	file_put_contents($file, $text);
	$logs = ['url' => $link, 'file' => $file];
	//сообшаем о создании файла кеша.
	$this->log('cache_file_add', $logs, $dn_id);
}

//данная фунция проверяет есть ли закешированная страница, если да то возврашает ее. Если нет отдает false
public function getCachePageFromFile($link, $browser, $dn_id){
  //составляем путь к предполагаемому файлу кеша.
  $file = md5($dn_id.$link).'.txt';
  $file = DIR_APPLICATION.'simplepars/cache_page/'.$dn_id.'/'.$file;

  //проверяем есть ли такой файл.
  if (file_exists($file)) {
  	//имитация нормального ответа курла.
  	$data['url'] = $link;
	  $data['content_type'] = 'text/html; charset=utf-8';
	  $data['http_code'] = '200';
		$data['errno'] = '0';
	  $data['errmsg'] = '';
	  $data['sp_log'] = 'log_cache';
	  $data['browser'] = $browser;
  	$data['content'] = file_get_contents($file);
  }else{
  	$data = false;
  }
  #$this->wtfarrey($data);
  return $data;
}

//Функция преобразовывает unicode в обычный текст.
public function unicode_decode($match) {
  //Функция взята тут - https://gist.github.com/aeurielesn/1116358
  return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}


############################################################################################
############################################################################################
#						Другие фунции
############################################################################################
############################################################################################

//Фунция проучения настроек поставшика.
public function getSetting($dn_id){
	$setting = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_setting WHERE `dn_id`=".(int)$dn_id);
  $setting = $setting->row;
  #$this->wtfarrey($setting);
  return $setting;
}

public function checkEngine(){
	$engine = 'opencart';

	//проверяем есть ли таблица
	$table_manuf_d = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."manufacturer_description'");
	
	if($table_manuf_d->num_rows > 0){

		//Начинаем запросы для определения версии движка магазина
		$meta_h1_cat = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."category_description` LIKE 'meta_h1'");

		//если значение в таблице.	
		$meta_h1_manuf = $this->db->query("SHOW COLUMNS FROM ".DB_PREFIX."manufacturer_description LIKE 'meta_h1'");

		if ($meta_h1_cat->num_rows > 0 && $meta_h1_manuf->num_rows > 0) { $engine = 'ocstore'; }

	}


	#опеределяем релиз
	$rl = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."url_alias'");
	if ($rl->num_rows == 0) {
		$engine .= 3;
	} else {
		$engine .= 2;
	}
	#$engine = 'opencart3';
	return $engine;
}

//получение настроек csv 
public function getSettingCsv($dn_id){
	$csv = $this->db->query("SELECT * FROM ". DB_PREFIX ."pars_createcsv WHERE dn_id=".(int)$dn_id." ORDER BY id ASC")->rows;
	return $csv;
}

//получаем язык по умолчанию в админке.
public function getLangDef(){

	$language_id = $this->db->query("SELECT l.language_id FROM ".DB_PREFIX."language l INNER JOIN ".DB_PREFIX."setting s 
																	ON l.code = s.value 
																	WHERE s.key = 'config_admin_language'")->row['language_id'];
	if(empty($language_id)){ $language_id = 0;}
	return $language_id;
}

public function checkCronTable(){
	$cron = $this->db->query("SELECT * FROM `".DB_PREFIX."pars_cron` WHERE `id`= 1");
	if($cron->num_rows != 1){
		$this->db->query("INSERT INTO `".DB_PREFIX."pars_cron` SET `permit` = 'stop'");
	}
}

//проверяем установлен ли модуль опций с фото.
public function checkModuleOption(){
	$value = 0;
	//проверяем есть ли поле в таблице
	$option_mpn2005 = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."product_option_value` LIKE 'image'");
	$option_19th = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."poip_option_image'");

	if($option_mpn2005->num_rows > 0){ 
		$value = 1; 
	}elseif($option_19th->num_rows > 0){
		$value = 2; 
	}

	#$this->wtfarrey($value);
	return $value;
}

//проверяем установлен ли модуль HPM
public function checkModuleHpm(){
	$hpm = $this->db->query("SHOW TABLES LIKE '".DB_PREFIX."hpmodel_links'")->num_rows;
	return $hpm;
}

//Фунция проучения производителей и их id
public function getManufs(){
	$manufs = $this->db->query("SELECT `manufacturer_id` as `id`,`name` FROM `".DB_PREFIX."manufacturer` ORDER BY name ASC");
  $manufs = $manufs->rows;
  #$this->wtfarrey($manufs);
  return $manufs;
}

//Копия фунции чека категорий, скопировал для изучения.
public function repairCategories($parent_id = 0) {
	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int)$parent_id . "'");

	foreach ($query->rows as $category) {
		// Delete the path below the current one
		$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category['category_id'] . "'");

		// Fix for records with no paths
		$level = 0;

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY level ASC");

		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");

			$level++;
		}

		$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$category['category_id'] . "', level = '" . (int)$level . "'");

		$this->repairCategories($category['category_id']);
	}
}

//Создаем базу парсинга в im
public function createDbPrsetup($dn_id){
	$this->db->query("INSERT INTO ". DB_PREFIX ."pars_prsetup
  	SET dn_id=".(int)$dn_id.",
  	model='',
  	sku='',
  	name='',
  	price='',
  	quant='',
  	quant_d='101',
  	manufac='',
  	manufac_d='0',
  	des='',
  	des_d='',
  	cat='',
  	cat_d='',
  	img='',
  	img_d='',
  	img_dir='product',
  	attr=''");
}

//Создаем базу ,браузера
public function createDbBrowser($dn_id){

	$this->db->query("INSERT INTO ". DB_PREFIX ."pars_browser SET	dn_id=".(int)$dn_id);
}

//Создание базу pars_xml 
public function createDbXml($dn_id){

	$this->db->query("INSERT INTO ". DB_PREFIX ."pars_xml SET	dn_id=".(int)$dn_id);
}

//Вспомагательная фунция удаления файлов кеша.
public function delAllPageXml($dn_id){
	//Адерсс директории с фалами.
	$xml_dir = DIR_APPLICATION.'simplepars/xml_page/'.$dn_id.'/*.xml';
	$files = glob($xml_dir);
	//производим удаление
	array_map('unlink', $files);
	
}

//пауза парсинга микро секунды.
public function timeSleep($times){
	#$time = 0;

	if($times !== 0){

		$time = explode('-', $times);
		$time = array_filter($time);

		if(empty($time[0])){
			$time[0] = 0;
		}
		$time[0] = str_replace(',', '.', $time[0]);
		$time[0] = (float)$time[0];
		$time[0] = ($time[0]*1000000);

		if(empty($time[1])){
			usleep($time[0]);
		}else{
			$time[1] = str_replace(',', '.', $time[1]);
			$time[1] = (float)$time[1];
			$time[1] = ($time[1]*1000000);
			$rand_t = rand($time[0], $time[1]);
			usleep($rand_t);
		}


	}
	#$this->wtfarrey($time);
}

//Экспорт формы поставшика.
public function getExportForm($links, $dn_id){

	$finish = '';
	$data['setting'] = $this->getSetting($dn_id);

	$param = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_param WHERE dn_id=".$dn_id." ORDER BY `id` ASC");
  $data['param'] = $param->rows;

	$replace = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_replace WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['replace'] = $replace->rows;

	$createcsv = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_createcsv WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['createcsv'] = $createcsv->rows;

	$prsetup = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_prsetup WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['prsetup'] = $prsetup->row;

	$browser = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_browser WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['browser'] = $browser->row;
	if($links != 4){
		unset($data['browser']['auth_use']);
		unset($data['browser']['auth_url']);
		unset($data['browser']['auth_data']);
		unset($data['browser']['auth_type']);
		unset($data['browser']['auth_url_check']);
		unset($data['browser']['auth_str']);
	}

	//только если выгружаем данные уровня 4
	if($links === 4){
		$proxy_list = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_proxy_list WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
		$data['proxy_list'] = $proxy_list->rows;
	}

	$pars_link_list = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_link_list WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['pars_link_list'] = $pars_link_list->rows;

	$pars_xml = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_xml WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
	$data['pars_xml'] = $pars_xml->row;

	if($links === 0){

	}elseif($links === 2){
		$pars_sen_link = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_sen_link WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
		$data['pars_sen_link'] = $pars_sen_link->rows;
	}elseif($links === 1){
		$pars_link = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_link WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
		$data['pars_link'] = $pars_link->rows;
	}elseif($links === 3 || $links === 4){
		$pars_sen_link = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_sen_link WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
		$data['pars_sen_link'] = $pars_sen_link->rows;

		$pars_link = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_link WHERE `dn_id`=".(int)$dn_id." ORDER BY `id` ASC");
		$data['pars_link'] = $pars_link->rows;
	}

	//Финальный массив на отдачу.
	$finish = json_encode($data);

	return $finish;
}

//фунция очистки проекта. 
public function clearProject($dn_id){

	//получаем значение имение проэкта. 
	$dn_name = $this->db->query("SELECT dn_name FROM `".DB_PREFIX."pars_setting` WHERE dn_id = ".(int)$dn_id)->row['dn_name'];
	
	//очишаем содержимое проекта.
	$this->db->query("REPLACE `".DB_PREFIX."pars_setting` SET dn_id=".(int)$dn_id);
	//присваем обратно имя проекта.
	$this->db->query("UPDATE `".DB_PREFIX."pars_setting` SET dn_name='".$this->db->escape($dn_name)."' WHERE dn_id=".(int)$dn_id);

	//удаляем второястименные записи из таблицы.
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_param` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_replace` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_createcsv` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_prsetup` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_sen_link` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_link` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_link_list` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_proxy_list` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_browser` WHERE `dn_id`=".(int)$dn_id);
	$this->db->query("DELETE FROM `".DB_PREFIX."pars_xml` WHERE `dn_id`=".(int)$dn_id);
	#Создаем таблицу Prsetup
	$this->createDbPrsetup($dn_id);
	#Создаем таблицу Браузера
	$this->createDbBrowser($dn_id);
	#Создаем таблицу обработчика xml
	$this->createDbXml($dn_id);

	//очишаем старые куки
	$file = DIR_APPLICATION.'simplepars/cookie/cookie_'.(int)$dn_id.'.txt';
	if(file_exists($file)){
		file_put_contents($file, '');
	}

	//Очишавем все веш файлы при очишении проекта.
	$this->urlDelAllCache($dn_id);

	$this->session->data['success'] = 'Настройки проекта были очищены.';

}

//ипорт формы
public function importFrom($data, $dn_id){
	$data = json_decode($data, true);
	#$this->wtfarrey($data);
	if(is_array($data)){

		//ну что тут начинаем загружать.
		if(!empty($data['setting'])){

			//проверяем есть ли указание кодировки
			if(!isset($data['setting']['link_list'])){ $data['setting']['link_list']=''; }
			if(!isset($data['setting']['link_error'])){ $data['setting']['link_error']=''; }
			if(!isset($data['setting']['pre_view_param'])){ $data['setting']['pre_view_param']=1; }
			if(!isset($data['setting']['pre_view_syntax'])){ $data['setting']['pre_view_syntax']=1; }
			if(empty($data['setting']['csv_charset'])){ $data['setting']['csv_charset']=1; }
			if(empty($data['setting']['r_made_url'])){ $data['setting']['r_made_url']=1; }
			if(empty($data['setting']['r_made_meta'])){ $data['setting']['r_made_meta']=0; }
			if(empty($data['setting']['r_cat_perent'])){ $data['setting']['r_cat_perent']=0; }
			if(empty($data['setting']['r_cat_made_url'])){ $data['setting']['r_cat_made_url']=1; }
			if(empty($data['setting']['r_cat_made_meta'])){ $data['setting']['r_cat_made_meta']=0; }
			if(empty($data['setting']['r_manufac_made_url'])){ $data['setting']['r_manufac_made_url']=1; }
			if(empty($data['setting']['r_manufac_made_meta'])){ $data['setting']['r_manufac_made_meta']=0; }
			if(empty($data['setting']['page_cou_link'])){ $data['setting']['page_cou_link'] = 5000; }
			if(empty($data['setting']['r_attr_group'])){ $data['setting']['r_attr_group'] = 1; }
			if(empty($data['setting']['r_opt'])){ $data['setting']['r_opt'] = 0; }
			if(empty($data['setting']['r_price_spec'])){ $data['setting']['r_price_spec'] = 0; }
			if(empty($data['setting']['r_price_spec_groups'])){ $data['setting']['r_price_spec_groups'] = ''; }
			if(empty($data['setting']['r_price_spec_date_start'])){ $data['setting']['r_price_spec_date_start'] = ''; }
			if(empty($data['setting']['r_price_spec_date_end'])){ $data['setting']['r_price_spec_date_end'] = ''; }
			if(empty($data['setting']['r_status_zero'])){ $data['setting']['r_status_zero'] = 5; }
			if(empty($data['setting']['filter_round_param'])){ $data['setting']['filter_round_param'] = ''; }
			if(empty($data['setting']['filter_round_depth'])){ $data['setting']['filter_round_depth'] = ''; }
			if(empty($data['setting']['filter_round_slash'])){ $data['setting']['filter_round_slash'] = 0; }
			if(!isset($data['setting']['filter_round_domain'])){ $data['setting']['filter_round_domain'] = 1; }
			if(empty($data['setting']['filter_round_rules'])){ $data['setting']['filter_round_rules'] = ''; }

			if(empty($data['setting']['filter_link_param'])){ $data['setting']['filter_link_param'] = ''; }
			if(empty($data['setting']['filter_link_depth'])){ $data['setting']['filter_link_depth'] = ''; }
			if(empty($data['setting']['filter_link_slash'])){ $data['setting']['filter_link_slash'] = 0; }
			if(!isset($data['setting']['filter_link_domain'])){ $data['setting']['filter_link_domain'] = 1; }
			if(empty($data['setting']['filter_link_rules'])){ $data['setting']['filter_link_rules'] = ''; }

			if(empty($data['setting']['logs_reverse'])){ $data['setting']['logs_reverse'] = 0; }
			if(empty($data['setting']['logs_mb'])){ $data['setting']['logs_mb'] = 25; }

			if(empty($data['setting']['u_manufac'])){ $data['setting']['u_manufac'] = 0;}
			if(empty($data['setting']['u_des'])){ $data['setting']['u_des'] = 0;}
			if(empty($data['setting']['u_cat'])){ $data['setting']['u_cat'] = 0;}
			if(empty($data['setting']['u_img'])){ $data['setting']['u_img'] = 0;}
			if(empty($data['setting']['u_attr'])){ $data['setting']['u_attr'] = 0;}
			if(empty($data['setting']['u_opt'])){ $data['setting']['u_opt'] = 0;}
			if(empty($data['setting']['u_made_meta'])){ $data['setting']['u_made_meta'] = 0;}
			if(empty($data['setting']['u_up_url'])){ $data['setting']['u_up_url'] = 0;}
			
			if(empty($data['setting']['r_upc'])){ $data['setting']['r_upc'] = 0; }
			if(empty($data['setting']['r_ean'])){ $data['setting']['r_ean'] = 0; }
			if(empty($data['setting']['r_jan'])){ $data['setting']['r_jan'] = 0; }
			if(empty($data['setting']['r_isbn'])){ $data['setting']['r_isbn'] = 0; }
			if(empty($data['setting']['r_mpn'])){ $data['setting']['r_mpn'] = 0; }
			if(empty($data['setting']['r_location'])){ $data['setting']['r_location'] = 0; }
			if(empty($data['setting']['r_minimum'])){ $data['setting']['r_minimum'] = 0; }
			if(empty($data['setting']['r_subtract'])){ $data['setting']['r_subtract'] = 0; }
			if(empty($data['setting']['r_length'])){ $data['setting']['r_length'] = 0; }
			if(empty($data['setting']['r_width'])){ $data['setting']['r_width'] = 0; }
			if(empty($data['setting']['r_height'])){ $data['setting']['r_height'] = 0; }
			if(empty($data['setting']['r_length_class_id'])){ $data['setting']['r_length_class_id'] = 0; }
			if(empty($data['setting']['r_weight'])){ $data['setting']['r_weight'] = 0; }
			if(empty($data['setting']['r_weight_class_id'])){ $data['setting']['r_weight_class_id'] = 0; }
			if(empty($data['setting']['r_status'])){ $data['setting']['r_status'] = 0; }
			if(empty($data['setting']['r_sort_order'])){ $data['setting']['r_sort_order'] = 0; }
			if(empty($data['setting']['r_layout_pr'])){ $data['setting']['r_layout_pr'] = 0; }
			if(empty($data['setting']['r_tags'])){ $data['setting']['r_tags'] = 0; }
			if(empty($data['setting']['type_grab'])){ $data['setting']['type_grab'] = 1;}
			if(empty($data['setting']['thread'])){ $data['setting']['thread'] = 1;}
			if(empty($data['setting']['r_des_dir'])){ $data['setting']['r_des_dir'] = 0;}
			if(empty($data['setting']['r_cost'])){ $data['setting']['r_cost'] = 0;}
			if(empty($data['setting']['r_hpm'])){ $data['setting']['r_hpm'] = 0;}
			if(empty($data['setting']['r_related'])){ $data['setting']['r_related'] = 0;}

			//Дополнительные преобразования перед записью в базу
			if(empty($data['setting']['grans_permit'])){ $data['setting']['grans_permit'] = 0;}
			$data['setting']['scripts_permit'] = 0;

			//Если не выбран магазин то все магазины по умолчани.
			if (empty($data['setting']['r_store'])) {
				$data['setting']['r_store'] = '';
				$temp_s = $this->getAllStore();
				foreach ($temp_s as $key_ts => $t_s) {
					if ($key_ts != 0) { $data['setting']['r_store'] .= ','.$t_s['store_id']; } else { $data['setting']['r_store'] = $t_s['store_id']; }
				}
			}

			#Если убрали все галочки в языке тогда записываем выбрать все языки в магазине.
			if(empty($data['setting']['r_lang'])) {
				$data['setting']['r_lang'] = '';
				$temp_l = $this->getAllLang();
				foreach ($temp_l as $key_tl => $t_l) {
					if ($key_tl != 0) { $data['setting']['r_lang'] .= ','.$t_l['language_id']; } else { $data['setting']['r_lang'] = $t_l['language_id']; }
				}
			}
			//Определяем версию движка
			$engine = $this->checkEngine();
			$data['setting']['vers_op'] = $engine;

			$this->db->query("UPDATE `".DB_PREFIX."pars_setting` SET
				`pre_view_param`='".$this->db->escape($data['setting']['pre_view_param'])."',
				`pre_view_syntax`='".$this->db->escape($data['setting']['pre_view_syntax'])."',
				`start_link`='".$this->db->escape($data['setting']['start_link'])."',
				`link_list`='".$this->db->escape($data['setting']['link_list'])."',
				`link_error`='".$this->db->escape($data['setting']['link_error'])."',
				`page_cou_link`='".$this->db->escape($data['setting']['page_cou_link'])."',
				`pars_stop`='".$this->db->escape($data['setting']['pars_stop'])."',
				`csv_name`='".$this->db->escape($data['setting']['csv_name'])."',
				`csv_delim`='".$this->db->escape($data['setting']['csv_delim'])."',
				`csv_escape`='".$this->db->escape($data['setting']['csv_escape'])."',
				`csv_charset`='".$this->db->escape($data['setting']['csv_charset'])."',
				`pars_pause`='".$this->db->escape($data['setting']['pars_pause'])."',
				`type_grab`='".$this->db->escape($data['setting']['type_grab'])."',
				`thread`='".$this->db->escape($data['setting']['thread'])."',
				`filter_round_yes`='".$this->db->escape($data['setting']['filter_round_yes'])."',
				`filter_round_no`='".$this->db->escape($data['setting']['filter_round_no'])."',
				`filter_round_method`='".$this->db->escape($data['setting']['filter_round_method'])."',
				`filter_round_param`='".$this->db->escape($data['setting']['filter_round_param'])."',
				`filter_round_depth`='".$this->db->escape($data['setting']['filter_round_depth'])."',
				`filter_round_slash`='".$this->db->escape($data['setting']['filter_round_slash'])."',
				`filter_round_domain`='".$this->db->escape($data['setting']['filter_round_domain'])."',
				`filter_round_rules`='".$this->db->escape($data['setting']['filter_round_rules'])."',

				`filter_link_yes`='".$this->db->escape($data['setting']['filter_link_yes'])."',
				`filter_link_no`='".$this->db->escape($data['setting']['filter_link_no'])."',
				`filter_link_method`='".$this->db->escape($data['setting']['filter_link_method'])."',
				`filter_link_param`='".$this->db->escape($data['setting']['filter_link_param'])."',
				`filter_link_depth`='".$this->db->escape($data['setting']['filter_link_depth'])."',
				`filter_link_slash`='".$this->db->escape($data['setting']['filter_link_slash'])."',
				`filter_link_domain`='".$this->db->escape($data['setting']['filter_link_domain'])."',
				`filter_link_rules`='".$this->db->escape($data['setting']['filter_link_rules'])."',

				`action`='".$this->db->escape($data['setting']['action'])."',
				`sid`='".$this->db->escape($data['setting']['sid'])."',
				`grans_permit`='".$this->db->escape($data['setting']['grans_permit'])."',
				`scripts_permit`='".$this->db->escape($data['setting']['scripts_permit'])."',

				`u_manufac`='".$this->db->escape($data['setting']['u_manufac'])."',
				`u_des`='".$this->db->escape($data['setting']['u_des'])."',
				`u_cat`='".$this->db->escape($data['setting']['u_cat'])."',
				`u_img`='".$this->db->escape($data['setting']['u_img'])."',
				`u_attr`='".$this->db->escape($data['setting']['u_attr'])."',
				`u_opt`='".$this->db->escape($data['setting']['u_opt'])."',
				`u_made_meta`='".$this->db->escape($data['setting']['u_made_meta'])."',
				`u_up_url`='".$this->db->escape($data['setting']['u_up_url'])."',

				`r_store`='".$this->db->escape($data['setting']['r_store'])."',
				`r_lang`='".$this->db->escape($data['setting']['r_lang'])."',
				`r_model`='".$this->db->escape($data['setting']['r_model'])."',
				`r_sku`='".$this->db->escape($data['setting']['r_sku'])."',
				`r_name`='".$this->db->escape($data['setting']['r_name'])."',
				`r_made_url`='".$this->db->escape($data['setting']['r_made_url'])."',
				`r_made_meta`='".$this->db->escape($data['setting']['r_made_meta'])."',
				`r_price`='".$this->db->escape($data['setting']['r_price'])."',
				`r_price_spec`='".$this->db->escape($data['setting']['r_price_spec'])."',
				`r_price_spec_groups`='".$this->db->escape($data['setting']['r_price_spec_groups'])."',
				`r_price_spec_date_start`='".$this->db->escape($data['setting']['r_price_spec_date_start'])."',
				`r_price_spec_date_end`='".$this->db->escape($data['setting']['r_price_spec_date_end'])."',
				`r_quant`='".$this->db->escape($data['setting']['r_quant'])."',
				`r_status_zero`='".$this->db->escape($data['setting']['r_status_zero'])."',
				`r_status`='".$this->db->escape($data['setting']['r_status'])."',
				`r_manufac`='".$this->db->escape($data['setting']['r_manufac'])."',
				`r_manufac_made_url`='".$this->db->escape($data['setting']['r_manufac_made_url'])."',
				`r_manufac_made_meta`='".$this->db->escape($data['setting']['r_manufac_made_meta'])."',
				`r_des`='".$this->db->escape($data['setting']['r_des'])."',
				`r_des_dir`='".$this->db->escape($data['setting']['r_des_dir'])."',
				`r_cat`='".$this->db->escape($data['setting']['r_cat'])."',
				`r_cat_perent`='".$this->db->escape($data['setting']['r_cat_perent'])."',
				`r_cat_made_url`='".$this->db->escape($data['setting']['r_cat_made_url'])."',
				`r_cat_made_meta`='".$this->db->escape($data['setting']['r_cat_made_meta'])."',
				`r_img`='".$this->db->escape($data['setting']['r_img'])."',
				`r_img_dir`='".$this->db->escape($data['setting']['r_img_dir'])."',
				`r_attr`='".$this->db->escape($data['setting']['r_attr'])."',
				`r_attr_group`='".$this->db->escape($data['setting']['r_attr_group'])."',
				`r_opt`='".$this->db->escape($data['setting']['r_opt'])."',
				`r_upc`='".$this->db->escape($data['setting']['r_upc'])."',
				`r_ean`='".$this->db->escape($data['setting']['r_ean'])."',
				`r_jan`='".$this->db->escape($data['setting']['r_jan'])."',
				`r_isbn`='".$this->db->escape($data['setting']['r_isbn'])."',
				`r_mpn`='".$this->db->escape($data['setting']['r_mpn'])."',
				`r_location`='".$this->db->escape($data['setting']['r_location'])."',
				`r_minimum`='".$this->db->escape($data['setting']['r_minimum'])."',
				`r_subtract`='".$this->db->escape($data['setting']['r_subtract'])."',
				`r_length`='".$this->db->escape($data['setting']['r_length'])."',
				`r_width`='".$this->db->escape($data['setting']['r_width'])."',
				`r_height`='".$this->db->escape($data['setting']['r_height'])."',
				`r_length_class_id`='".$this->db->escape($data['setting']['r_length_class_id'])."',
				`r_weight`='".$this->db->escape($data['setting']['r_weight'])."',
				`r_weight_class_id`='".$this->db->escape($data['setting']['r_weight_class_id'])."',
				`r_status`='".$this->db->escape($data['setting']['r_status'])."',
				`r_sort_order`='".$this->db->escape($data['setting']['r_sort_order'])."',
				`r_layout_pr`='".$this->db->escape($data['setting']['r_layout_pr'])."',
				`r_tags`='".$this->db->escape($data['setting']['r_tags'])."',
				`r_cost`='".$this->db->escape($data['setting']['r_cost'])."',
				`r_hpm`='".$this->db->escape($data['setting']['r_hpm'])."',
				`r_related`='".$this->db->escape($data['setting']['r_related'])."',
				`logs_reverse`='".$this->db->escape($data['setting']['logs_reverse'])."',
				`logs_mb`='".$this->db->escape($data['setting']['logs_mb'])."',
				`vers_op` = '".$this->db->escape($data['setting']['vers_op'])."'
				WHERE `dn_id`=".(int)$dn_id
			);
		}

		//Удаляем старые настройки
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_param` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_replace` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_createcsv` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_prsetup` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_sen_link` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_link` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_link_list` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_proxy_list` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_browser` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_xml` WHERE `dn_id`=".(int)$dn_id);
		$this->db->query("DELETE FROM `".DB_PREFIX."pars_phpscripts` WHERE `dn_id`=".(int)$dn_id);


		#Создаем таблицу Prsetup
		$this->createDbPrsetup($dn_id);
		#Создаем таблицу Браузера
		$this->createDbBrowser($dn_id);
		#Создаем таблицу обработчика xml
		$this->createDbXml($dn_id);

		//Тут посложнее из за того что все привязано к ключам автоинкремент. Начнем плясать от параметров.
		$param_id = [];
		if(!empty($data['param'])){
			//создаем заново.
			foreach($data['param'] as $key => $param){
				//Из за гребанной совместимости версий. Боль в моей заднице.
				if(empty($param['skip_where'])){
					$sql = '';
				}else{
					$sql ="`with_teg`=".$this->db->escape($param['with_teg']).",
					`skip_enter`='".$this->db->escape($param['skip_enter'])."',
					`skip_where`=".$this->db->escape($param['skip_where']).",
					`reverse`=".$this->db->escape($param['reverse']).",";
				}

				$this->db->query("INSERT INTO `".DB_PREFIX."pars_param` SET
					`dn_id`='".$this->db->escape($dn_id)."',
					`name`='".$this->db->escape($param['name'])."',
					`start`='".$this->db->escape($param['start'])."',
					`stop`='".$this->db->escape($param['stop'])."',
					`type`='".$this->db->escape($param['type'])."',
					".$sql."
					`base_id`='".$this->db->escape($param['base_id'])."',
					`delim`='".$this->db->escape($param['delim'])."'
				");

				//Получаем новый id границы парсинга и засовываем в врменный массив.
				$param_id[$key] = ['old_id' => $param['id'], 'new_id' => $this->db->getLastId()];
			}

			//теперь присваеваем правильные id родительских границ.
			$param_bases = $this->db->query("SELECT * FROM ".DB_PREFIX."pars_param WHERE dn_id=".(int)$dn_id." ORDER BY `id` ASC");
			if($param_bases->num_rows > 0){
				$param_bases = $param_bases->rows;

				//обновляем id родителя границы.
				foreach($param_bases as $param_base){
					$base_id=0;
					//Выбираем правильный id радителя.
					foreach($param_id as $value){
						if($param_base['base_id'] == $value['old_id']){
							$base_id = $value['new_id'];
						}
					}

					$this->db->query("UPDATE `".DB_PREFIX."pars_param` SET `base_id`=".(int)$base_id." WHERE `id`=".(int)$param_base['id']);

				}
			}
		}

		//Загружаем таблицу реплейса.
		if(!empty($data['replace'])){

			//Создаем заново.
			foreach($data['replace'] as $replace){

				//Проверяем есть ли правила хеширования
				if(empty($replace['hash'])){ $replace['hash'] = 0; }

				//Есть ли математическая фунция
				if(empty($replace['arithm'])){ $replace['arithm'] = ''; }

				//получаем актуальные id параметров парсинга
				foreach($param_id as $value){
					if($replace['param_id'] == $value['old_id']){
						$replace['param_id'] = $value['new_id'];
					}
					$replace['rules'] = str_replace('{gran_'.$value['old_id'].'}', '{gran_'.$value['new_id'].'}', $replace['rules']);
					$replace['text_start'] = str_replace('{gran_'.$value['old_id'].'}', '{gran_'.$value['new_id'].'}', $replace['text_start']);
					$replace['text_stop'] = str_replace('{gran_'.$value['old_id'].'}', '{gran_'.$value['new_id'].'}', $replace['text_stop']);
				}

				$this->db->query("INSERT INTO `".DB_PREFIX."pars_replace` SET
					`dn_id`='".(int)$dn_id."',
					`param_id`='".$this->db->escape($replace['param_id'])."',
					`text_start`='".$this->db->escape($replace['text_start'])."',
					`text_stop`='".$this->db->escape($replace['text_stop'])."',
					`rules`='".$this->db->escape($replace['rules'])."',
					`hash`=".(int)$replace['hash'].", arithm='".$this->db->escape($replace['arithm'])."'"
				);
			}
		}

		//таблица составления прайса pars_createcsv
		if(!empty($data['createcsv'])){

			//создаем заново
			foreach($data['createcsv'] as $createcsv){

				//получаем актуальные id параметров парсинга
				foreach($param_id as $value){
					#переходная закладка. Удалить через пару месяцев, когда все перейдут на новую версию модуля.
					#########################################################################################
					if(!empty($createcsv['param_id'])){
						if($createcsv['param_id'] == 'link'){
							$createcsv['value'] = '{link}';
						}else{
							$createcsv['value'] = '{gran_'.$createcsv['param_id'].'}';
							unset($createcsv['param_id']);
						}

					}
					#########################################################################################
					$createcsv['value'] = str_replace('{gran_'.$value['old_id'].'}', '{gran_'.$value['new_id'].'}', $createcsv['value']);
				}

				if(empty($createcsv['csv_column'])){ $createcsv['csv_column'] = ''; }
				$this->db->query("INSERT INTO `".DB_PREFIX."pars_createcsv` SET
					`dn_id`=".(int)$dn_id.",
					`name`='".$this->db->escape($createcsv['name'])."',
					`value`='".$this->db->escape($createcsv['value'])."',
					`csv_column`='".$this->db->escape($createcsv['csv_column'])."'");
			}
		}

		//таблица парсинга в им pars_prsetup
		if(!empty($data['prsetup'])){

			//получаем актуальные id параметров парсинга
			foreach($param_id as $value){
				#переделываем данные массива.
				foreach($data['prsetup'] as $key => $prsetup){
					$data['prsetup'][$key] = str_replace('{gran_'.$value['old_id'].'}', '{gran_'.$value['new_id'].'}', $prsetup);
				}
			}


			if(empty($data['prsetup']['price_spec'])){ $data['prsetup']['price_spec'] = ''; }
			if(empty($data['prsetup']['des_dir'])){ $data['prsetup']['des_dir'] = 'description'; }
			if(!isset($data['prsetup']['img_name'])){ $data['prsetup']['img_name'] = ''; }

			//опции
			if(empty($data['prsetup']['opt_name'])){ $data['prsetup']['opt_name'] = ''; }
			if(empty($data['prsetup']['opt_value'])){ $data['prsetup']['opt_value'] = ''; }
			if(empty($data['prsetup']['opt_price'])){ $data['prsetup']['opt_price'] = ''; }
			if(empty($data['prsetup']['opt_quant'])){ $data['prsetup']['opt_quant'] = ''; }
			if(!isset($data['prsetup']['opt_quant_d'])){ $data['prsetup']['opt_quant_d'] = '10'; }
			if(empty($data['prsetup']['opt_imgs'])){ $data['prsetup']['opt_imgs'] = '';}
			if(empty($data['prsetup']['opt_data'])){ 
				$data['prsetup']['opt_data'] = ''; 
			}else{

				$check_option_module = $this->checkModuleOption();
				if($check_option_module == 0){
					$data['prsetup']['opt_data'] = str_replace(['imgs_type_2', 'imgs_type_3'], 'imgs_type_0', $data['prsetup']['opt_data']);
				}elseif($check_option_module == 1){
					$data['prsetup']['opt_data'] = str_replace('imgs_type_3', 'imgs_type_0', $data['prsetup']['opt_data']);
				}elseif($check_option_module == 2){
					$data['prsetup']['opt_data'] = str_replace('imgs_type_2', 'imgs_type_0', $data['prsetup']['opt_data']);
				}

			}
			
			if(empty($data['prsetup']['grans_permit_list'])){ $data['prsetup']['grans_permit_list'] = ''; }

			if(empty($data['prsetup']['seo_url'])){ $data['prsetup']['seo_url'] = ''; }
			if(empty($data['prsetup']['seo_h1'])){ $data['sprsetup']['seo_h1'] = ''; }
			if(empty($data['prsetup']['seo_title'])){ $data['prsetup']['seo_title'] = ''; }
			if(empty($data['prsetup']['seo_desc'])){ $data['prsetup']['seo_desc'] = ''; }
			if(empty($data['prsetup']['seo_keyw'])){ $data['prsetup']['seo_keyw'] = ''; }

			if(empty($data['prsetup']['cat_seo_url'])){ $data['prsetup']['cat_seo_url'] = ''; }
			if(empty($data['prsetup']['cat_seo_h1'])){ $data['sprsetup']['cat_seo_h1'] = ''; }
			if(empty($data['prsetup']['cat_seo_title'])){ $data['prsetup']['cat_seo_title'] = ''; }
			if(empty($data['prsetup']['cat_seo_desc'])){ $data['prsetup']['cat_seo_desc'] = ''; }
			if(empty($data['prsetup']['cat_seo_keyw'])){ $data['prsetup']['cat_seo_keyw'] = ''; }

			if(empty($data['prsetup']['manuf_seo_url'])){ $data['prsetup']['manuf_seo_url'] = ''; }
			if(empty($data['prsetup']['manuf_seo_h1'])){ $data['sprsetup']['manuf_seo_h1'] = ''; }
			if(empty($data['prsetup']['manuf_seo_title'])){ $data['prsetup']['manuf_seo_title'] = ''; }
			if(empty($data['prsetup']['manuf_seo_desc'])){ $data['prsetup']['manuf_seo_desc'] = ''; }
			if(empty($data['prsetup']['manuf_seo_keyw'])){ $data['prsetup']['manuf_seo_keyw'] = ''; }

			if(!isset($data['prsetup']['upc'])){ $data['prsetup']['upc'] = ''; }
			if(!isset($data['prsetup']['ean'])){ $data['prsetup']['ean'] = ''; }
			if(!isset($data['prsetup']['jan'])){ $data['prsetup']['jan'] = ''; }
			if(!isset($data['prsetup']['isbn'])){ $data['prsetup']['isbn'] = ''; }
			if(!isset($data['prsetup']['mpn'])){ $data['prsetup']['mpn'] = ''; }
			if(!isset($data['prsetup']['location'])){ $data['prsetup']['location'] = ''; }
			if(empty($data['prsetup']['minimum'])){ $data['prsetup']['minimum'] = 1; }
			if(!isset($data['prsetup']['subtract'])){ $data['prsetup']['subtract'] = 1; }
			if(empty($data['prsetup']['length'])){ $data['prsetup']['length'] = 0.00; }
			if(empty($data['prsetup']['width'])){ $data['prsetup']['width'] = 0.00; }
			if(empty($data['prsetup']['height'])){ $data['prsetup']['height'] = 0.00; }
			if(empty($data['prsetup']['length_class_id'])){ $data['prsetup']['length_class_id'] = 1; }
			if(empty($data['prsetup']['weight'])){ $data['prsetup']['weight'] = 0.00; }
			if(empty($data['prsetup']['weight_class_id'])){ $data['prsetup']['weight_class_id'] = 1; }
			if(!isset($data['prsetup']['status'])){ $data['prsetup']['status'] = 1; }
			if(empty($data['prsetup']['sort_order'])){ $data['prsetup']['sort_order'] = 0; }
			if(empty($data['prsetup']['layout_pr'])){ $data['prsetup']['layout_pr'] = 0; }
			if(empty($data['prsetup']['layout_cat'])){ $data['prsetup']['layout_cat'] = 0; }
			if(empty($data['prsetup']['tags'])){ $data['prsetup']['tags'] = ''; }
			if(!isset($data['prsetup']['cost'])){ $data['prsetup']['cost'] = ''; }
			if(empty($data['prsetup']['hpm_sku']) && !$this->checkModuleHpm() ){ $data['prsetup']['hpm_sku'] = ''; }
			if(empty($data['prsetup']['related_sku'])){ $data['prsetup']['related_sku'] = ''; }
			

			//Создаем заново.
			$this->db->query("UPDATE `".DB_PREFIX."pars_prsetup` SET
				`model`='".$this->db->escape($data['prsetup']['model'])."',
				`sku`='".$this->db->escape($data['prsetup']['sku'])."',
				`name`='".$this->db->escape($data['prsetup']['name'])."',
				`price`='".$this->db->escape($data['prsetup']['price'])."',
				`price_spec`='".$this->db->escape($data['prsetup']['price_spec'])."',
				`cost`='".$this->db->escape($data['prsetup']['cost'])."',
				`quant`='".$this->db->escape($data['prsetup']['quant'])."',
				`quant_d`='".$this->db->escape($data['prsetup']['quant_d'])."',
				`des`='".$this->db->escape($data['prsetup']['des'])."',
				`des_d`='".$this->db->escape($data['prsetup']['des_d'])."',
				`des_dir`='".$this->db->escape($data['prsetup']['des_dir'])."',
				`manufac`='".$this->db->escape($data['prsetup']['manufac'])."',
				`manufac_d`='".$this->db->escape($data['prsetup']['manufac_d'])."',
				`cat`='".$this->db->escape($data['prsetup']['cat'])."',
				`cat_d`='".$this->db->escape($data['prsetup']['cat_d'])."',
				`img`='".$this->db->escape($data['prsetup']['img'])."',
				`img_d`='".$this->db->escape($data['prsetup']['img_d'])."',
				`img_dir`='".$this->db->escape($data['prsetup']['img_dir'])."',
				`img_name`='".$this->db->escape($data['prsetup']['img_name'])."',
				`attr`='".$this->db->escape($data['prsetup']['attr'])."',
				`opt_name`='".$this->db->escape($data['prsetup']['opt_name'])."',
				`opt_value`='".$this->db->escape($data['prsetup']['opt_value'])."',
				`opt_price`='".$this->db->escape($data['prsetup']['opt_price'])."',
				`opt_quant`='".$this->db->escape($data['prsetup']['opt_quant'])."',
				`opt_quant_d`='".$this->db->escape($data['prsetup']['opt_quant_d'])."',
				`opt_imgs`='".$this->db->escape($data['prsetup']['opt_imgs'])."',
				`opt_data`='".$this->db->escape($data['prsetup']['opt_data'])."',
				`grans_permit_list`='".$this->db->escape($data['prsetup']['grans_permit_list'])."',
				`upc`='".$this->db->escape($data['prsetup']['upc'])."',
				`ean`='".$this->db->escape($data['prsetup']['ean'])."',
				`jan`='".$this->db->escape($data['prsetup']['jan'])."',
				`isbn`='".$this->db->escape($data['prsetup']['isbn'])."',
				`mpn`='".$this->db->escape($data['prsetup']['mpn'])."',
				`location`='".$this->db->escape($data['prsetup']['location'])."',
				`minimum`='".$this->db->escape($data['prsetup']['minimum'])."',
				`subtract`='".$this->db->escape($data['prsetup']['subtract'])."',
				`length`='".$this->db->escape($data['prsetup']['length'])."',
				`width`='".$this->db->escape($data['prsetup']['width'])."',
				`height`='".$this->db->escape($data['prsetup']['height'])."',
				`length_class_id`='".$this->db->escape($data['prsetup']['length_class_id'])."',
				`weight`='".$this->db->escape($data['prsetup']['weight'])."',
				`weight_class_id`='".$this->db->escape($data['prsetup']['weight_class_id'])."',
				`status`='".$this->db->escape($data['prsetup']['status'])."',
				`sort_order`='".$this->db->escape($data['prsetup']['sort_order'])."',
				`layout_pr`='".$this->db->escape($data['prsetup']['layout_pr'])."',
				`tags`='".$this->db->escape($data['prsetup']['tags'])."',
				`hpm_sku`='".$this->db->escape($data['prsetup']['hpm_sku'])."',
				`related_sku`='".$this->db->escape($data['prsetup']['related_sku'])."',
				`layout_cat`='".$this->db->escape($data['prsetup']['layout_cat'])."',
				`seo_url`='".$this->db->escape($data['prsetup']['seo_url'])."',
				`seo_h1`='".$this->db->escape($data['prsetup']['seo_h1'])."',
				`seo_title`='".$this->db->escape($data['prsetup']['seo_title'])."',
				`seo_desc`='".$this->db->escape($data['prsetup']['seo_desc'])."',
				`seo_keyw`='".$this->db->escape($data['prsetup']['seo_keyw'])."',
				`cat_seo_url`='".$this->db->escape($data['prsetup']['cat_seo_url'])."',
				`cat_seo_h1`='".$this->db->escape($data['prsetup']['cat_seo_h1'])."',
				`cat_seo_title`='".$this->db->escape($data['prsetup']['cat_seo_title'])."',
				`cat_seo_desc`='".$this->db->escape($data['prsetup']['cat_seo_desc'])."',
				`cat_seo_keyw`='".$this->db->escape($data['prsetup']['cat_seo_keyw'])."',
				`manuf_seo_url`='".$this->db->escape($data['prsetup']['manuf_seo_url'])."',
				`manuf_seo_h1`='".$this->db->escape($data['prsetup']['manuf_seo_h1'])."',
				`manuf_seo_title`='".$this->db->escape($data['prsetup']['manuf_seo_title'])."',
				`manuf_seo_desc`='".$this->db->escape($data['prsetup']['manuf_seo_desc'])."',
				`manuf_seo_keyw`='".$this->db->escape($data['prsetup']['manuf_seo_keyw'])."'
				WHERE `dn_id`='".(int)$dn_id."'");
		}

		$link_list_id = [];
		//Работа с списсками ссылок
		if(!empty($data['pars_link_list'])){

			//Создаем заново
			foreach($data['pars_link_list'] as $list){
				//переносим списки ссылок
				$this->db->query("INSERT INTO `".DB_PREFIX."pars_link_list` SET `dn_id`=".(int)$dn_id.", `name` = '".$this->db->escape($list['name'])."'");
				$link_list_id[$list['id']] = $this->db->getLastId();
			}
		}

		//Работа с сылками. pars_sen_link
		if(!empty($data['pars_sen_link'])){

			//Создаем заново
			foreach($data['pars_sen_link'] as $sen_link){
				if(!isset($link['scan_cron'])){ $link['scan_cron'] = '1'; }
				//Если импортируюи ссылки из старых версий модуля.
				$sen_link['key_md5'] = md5($dn_id.$sen_link['link']);
				$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."pars_sen_link` SET
					`dn_id`=".(int)$dn_id.",
					`link`='".$this->db->escape($sen_link['link'])."',
					`key_md5`='".$this->db->escape($sen_link['key_md5'])."',
					`scan`='".$this->db->escape($sen_link['scan'])."',
					`scan_cron`='".$this->db->escape($link['scan_cron'])."'
				");
			}
		}

		//Работа с сылками. pars_link
		if(!empty($data['pars_link'])){

			//Создаем заново
			foreach($data['pars_link'] as $link){
				if(!isset($link['scan_cron'])){ $link['scan_cron'] = '1'; }

				if(empty($link['list'])){ 
					$link['list'] = '0'; 
				}else { 
					if(empty($link_list_id[$link['list']])){
						$link['list'] = '0';
					}else{
						$link['list'] = $link_list_id[$link['list']]; 
					}
				}

				if(!isset($link['error'])){ $link['error'] = '0'; }
				//Если импортируюи ссылки из старых версий модуля.
				$link['key_md5'] = md5($dn_id.$link['link']);
				$this->db->query("INSERT IGNORE INTO `".DB_PREFIX."pars_link` SET
					`dn_id`=".(int)$dn_id.",
					`link`='".$this->db->escape($link['link'])."',
					`key_md5`='".$this->db->escape($link['key_md5'])."',
					`scan`='".$this->db->escape($link['scan'])."',
					`scan_cron`='".$this->db->escape($link['scan_cron'])."',
					`list`='".$this->db->escape($link['list'])."',
					`error`='".$this->db->escape($link['error'])."'
				");
			}
		}

		//Записываем браузер в базу данных
		if(!empty($data['browser'])){

			if(!isset($data['browser']['protocol_version'])){ $data['browser']['protocol_version'] = 2;}
			if(empty($data['browser']['cookie_up'])){ $data['browser']['cookie_up'] = 0;}
			if(empty($data['browser']['auth_use'])){ $data['browser']['auth_use'] = 0;}
			if(empty($data['browser']['auth_url'])){ $data['browser']['auth_url'] = '';}
			if(empty($data['browser']['auth_data'])){ $data['browser']['auth_data'] = '';}
			if(empty($data['browser']['auth_type'])){ $data['browser']['auth_type'] = 1;}
			if(empty($data['browser']['auth_url_check'])){ $data['browser']['auth_url_check'] = '';}
			if(empty($data['browser']['auth_str'])){ $data['browser']['auth_str'] = '';}
			if(empty($data['browser']['webp_conv'])){ $data['browser']['webp_conv'] = 0;}


			$this->db->query("UPDATE `".DB_PREFIX."pars_browser` SET
			`proxy_use`='".$this->db->escape($data['browser']['proxy_use'])."',
			`timeout`='".$this->db->escape($data['browser']['timeout'])."',
			`connect_timeout`='".$this->db->escape($data['browser']['timeout'])."',
			`protocol_version`='".$this->db->escape($data['browser']['protocol_version'])."',
			`header_get`='".$this->db->escape($data['browser']['header_get'])."',
			`followlocation`='".$this->db->escape($data['browser']['followlocation'])."',
			`cookie_use`='".$this->db->escape($data['browser']['cookie_use'])."',
			`cookie_up`='".$this->db->escape($data['browser']['cookie_up'])."',
			`user_agent_use`='".$this->db->escape($data['browser']['user_agent_use'])."',
			`user_agent_change`='".$this->db->escape($data['browser']['user_agent_change'])."',
			`user_agent_list`='".$this->db->escape($data['browser']['user_agent_list'])."',
			`header_use`='".$this->db->escape($data['browser']['header_use'])."',
			`header_change`='".$this->db->escape($data['browser']['header_change'])."',
			`header_list`='".$this->db->escape($data['browser']['header_list'])."',
			`cache_page`='".$this->db->escape($data['browser']['cache_page'])."',
			`ch_connect_timeout`='".$this->db->escape($data['browser']['ch_connect_timeout'])."',
			`ch_timeout`='".$this->db->escape($data['browser']['ch_timeout'])."',
			`ch_url`='".$this->db->escape($data['browser']['ch_url'])."',
			`ch_pattern`='".$this->db->escape($data['browser']['ch_pattern'])."',
			`auth_use`='".$this->db->escape($data['browser']['auth_use'])."',
			`auth_url`='".$this->db->escape($data['browser']['auth_url'])."',
			`auth_data`='".$this->db->escape($data['browser']['auth_data'])."',
			`auth_type`='".$this->db->escape($data['browser']['auth_type'])."',
			`auth_url_check`='".$this->db->escape($data['browser']['auth_url_check'])."',
			`auth_str`='".$this->db->escape($data['browser']['auth_str'])."',
			`webp_conv`='".$this->db->escape($data['browser']['webp_conv'])."'

			WHERE `dn_id`=".(int)$dn_id);

		}

		//Запись прокси серверов
		if(!empty($data['proxy_list'])){

			foreach ($data['proxy_list'] as $key => $proxy_list) {
				$this->db->query("INSERT INTO `".DB_PREFIX."pars_proxy_list` SET
				`dn_id`='".(int)$dn_id."',
				`proxy`='".$this->db->escape($proxy_list['proxy'])."',
				`status`='".$this->db->escape($proxy_list['status'])."'");
			}
		}

		//Запись настроек разбора XML
		if(!empty($data['pars_xml'])){
			
			if(empty($data['pars_xml']['filter_yes'])){ $data['pars_xml']['filter_yes'] = '';}
			if(empty($data['pars_xml']['filter_no'])){ $data['pars_xml']['filter_no'] = '';}

			$this->db->query("UPDATE `".DB_PREFIX."pars_xml` SET
			`cat_work`='".$this->db->escape($data['pars_xml']['cat_work'])."',
			`pr_start`='".$this->db->escape($data['pars_xml']['pr_start'])."',
			`pr_stop`='".$this->db->escape($data['pars_xml']['pr_stop'])."',
			`cat_start`='".$this->db->escape($data['pars_xml']['cat_start'])."',
			`cat_stop`='".$this->db->escape($data['pars_xml']['cat_stop'])."',
			`cat_name_start`='".$this->db->escape($data['pars_xml']['cat_name_start'])."',
			`cat_name_stop`='".$this->db->escape($data['pars_xml']['cat_name_stop'])."',
			`cat_id_start`='".$this->db->escape($data['pars_xml']['cat_id_start'])."',
			`cat_id_stop`='".$this->db->escape($data['pars_xml']['cat_id_stop'])."',
			`cat_perent_start`='".$this->db->escape($data['pars_xml']['cat_perent_start'])."',
			`cat_perent_stop`='".$this->db->escape($data['pars_xml']['cat_perent_stop'])."',
			`pr_cat_start`='".$this->db->escape($data['pars_xml']['pr_cat_start'])."',
			`pr_cat_stop`='".$this->db->escape($data['pars_xml']['pr_cat_stop'])."',
			`filter_yes`='".$this->db->escape($data['pars_xml']['filter_yes'])."',
			`filter_no`='".$this->db->escape($data['pars_xml']['filter_no'])."',
			`cat_delim`='".$this->db->escape($data['pars_xml']['cat_delim'])."' 
			WHERE `dn_id`='".(int)$dn_id."'");
			
		}

		$this->session->data['success'] = 'Форма успешно загружена';
	}else{
		$this->session->data['error'] = ' Ошибка файла настроек, настройки не были обновлены!';
	}#is_arrey

}

//Фунция для ajax изменения типа кеширования.
public function changeTypeCaching($cache_page, $dn_id){
	//
	$this->db->query("UPDATE ". DB_PREFIX ."pars_browser SET cache_page =".(int)$cache_page." WHERE dn_id =".(int)$dn_id);
}

//Фунция ajax сохранения параметра подсветки синтаксиса.
public function changeSelectSyntax($data, $dn_id){
	//
	$this->db->query("UPDATE ". DB_PREFIX ."pars_setting SET pre_view_syntax =".(int)$data." WHERE dn_id =".(int)$dn_id);
}

//Фунция ajax сохранения параметра превью пред просмотра кода в настройках парсинга.
public function changeSelectPreview($data, $dn_id){
	//
	$this->db->query("UPDATE ". DB_PREFIX ."pars_setting SET pre_view_param =".(int)$data." WHERE dn_id =".(int)$dn_id);
}

//получение ссылки по ее id
public function getUrlFromId($url_id){
	$url = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `id`=".(int)$url_id);
	return $url->row;
}

//получение id ссылки по ее телу (что ли так написать :))
public function getUrlId($url){
	$url = $this->db->query("SELECT * FROM " . DB_PREFIX . "pars_link WHERE `link`='".$this->db->escape($url)."'");
	return $url->row;
}

//Фунци для скачивания файлов.
public function dwFile($who, $dn_id) {

	//проверяем что нам нужно отдать.
	if($who == 'csv'){
		$setting = $this->getSetting($dn_id);
		$file = './uploads/'.$setting['csv_name'].'.csv';
	}elseif($who == 'logs'){
		$file = DIR_LOGS.'simplepars_id-'.$dn_id.'.log';
	}elseif($who == 'urlPriceList'){
		$file = DIR_APPLICATION.'/uploads/urls_list_'.$dn_id.'.csv';
	}else{
		$file = false;
	}

	if (file_exists($file)) {
	  // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
	  // если этого не сделать файл будет читаться в память полностью!
	  if (ob_get_level()) {
	    ob_end_clean();
	  }

	  // заставляем браузер показать окно сохранения файла
	  header('Content-Description: File Transfer');
	  header('Content-Type: application/octet-stream');
	  header('Content-Disposition: attachment; filename=' . basename($file));
	  header('Content-Transfer-Encoding: binary');
	  header('Expires: 0');
	  header('Cache-Control: must-revalidate');
	  header('Pragma: public');
	  header('Content-Length: ' . filesize($file));
	  // читаем файл и отправляем его пользователю
	  readfile($file);

	  exit;

	}
}

//Фунция удаления файла.
public function delFile($dn_id) {

	//получаем настройки
	$setting = $this->getSetting($dn_id);

	//Формируем путь к прайсу который нужно грохнуть.
	$file = './uploads/'.$setting['csv_name'].'.csv';

	//Проверяем есть ли такой прайс.
	if (file_exists($file)) {
		unlink($file);
	}
}

//Конвертер байты в килобайты, мегабайты, гигбайты , терабаты.
public function convertBytes($bytes){
	
	if ( $bytes < 1000 * 1024 ) {
	  return number_format( $bytes / 1024, 2 ) . " KB";
	}	elseif ( $bytes < 1000 * 1048576 ) {
	  return number_format( $bytes / 1048576, 2 ) . " MB";
	} elseif ( $bytes < 1000 * 1073741824 ) {
	  return number_format( $bytes / 1073741824, 2 ) . " GB";
	} else {
	  return number_format( $bytes / 1099511627776, 2 ) . " TB";
	}
}

//Вспомагательная фунция для колбек. И очишение от html
public function htmlview(&$value){

	$value = htmlspecialchars($value);
}

//Универсальная фунция ответа на ajax запросы. Надесь я смогу развернуть потенциал этой фунции.
public function answjs($status, $msg='', $arrey=''){
	$data['status'] = $status;
	$data['msg'] = $msg;
	$data['other'] = $arrey;
	#$this->wtfarrey($data);
	exit(json_encode($data));
}

public function symbolToEn($text=''){
	//переводим русские символы в латиницу
  $symbol = [ "А"=>"a",	"Б"=>"b", "В"=>"v", "Г"=>"g",
		          "Д"=>"d",	"Е"=>"e", "Ё"=>"e", "Ж"=>"g",
		          "З"=>"z",	"И"=>"i", "Й"=>"J", "К"=>"k",
		          "Л"=>"l",	"М"=>"m", "Н"=>"n", "О"=>"o",
		          "П"=>"p",	"Р"=>"r", "С"=>"s", "Т"=>"t",
		          "У"=>"u",	"Ф"=>"f", "Х"=>"h", "Ц"=>"ts",
		          "Ч"=>"ch", "Ш"=>"sh", "Щ"=>"sch", "Ъ"=>"a",
		          "Ы"=>"y",	"Ь"=>"", "Э"=>"e", "Ю"=>"yu",
		          "Я"=>"ya",	"Ї"=>"ji", "Ґ"=>"g", "І"=>"I",
		          "а"=>"a",	"б"=>"b", "в"=>"v", "г"=>"g",
		          "д"=>"d",	"е"=>"e", "ё"=>"e", "ж"=>"g",
		          "з"=>"z",	"и"=>"i", "й"=>"j", "к"=>"k",
		          "л"=>"l",	"м"=>"m", "н"=>"n", "о"=>"o",
		          "п"=>"p",	"р"=>"r", "с"=>"s", "т"=>"t",
		          "у"=>"u",	"ф"=>"f", "х"=>"h", "ц"=>"ts",
		          "ч"=>"ch", "ш"=>"sh", "щ"=>"sch", "ъ"=>"a",
		          "ы"=>"y", "ь"=>"", "э"=>"e", "ю"=>"yu",
		          "я"=>"ya", "ї"=>"ji", "і"=>"i", "ґ"=>"g",
		          "Є"=>"e", "є"=>"e", "ў"=>"u", "Ў"=>"u",
		          "і"=>"i", "І"=>"i", "«"=>"-", "»"=>"-",
		          "—"=>"-", "–"=>"-", " "=>"-", "“"=>"-",
		          "”"=>"-", "Ā"=>"a", "Č"=>"c", "Ē"=>"e",
		          "Ģ"=>"g", "Ī"=>"i", "Ķ"=>"k", "Ļ"=>"l",
		          "Ņ"=>"n", "Š"=>"s", "Ū"=>"u", "Ž"=>"z",
        			"ā"=>"a", "č"=>"c", "ē"=>"e", "ģ"=>"g",
        			"ī"=>"i", "ķ"=>"k", "ļ"=>"l", "ņ"=>"n",
        			"š"=>"s", "ū"=>"u", "ž"=>"z",	"Ą"=>"a",
        			"Ć"=>"c", "Ę"=>"e", "Ł"=>"l", "Ń"=>"n",
							"Ó"=>"o", "Ś"=>"s", "Ź"=>"z", "Ż"=>"z",
							"ą"=>"a", "ć"=>"c", "ę"=>"e", "ł"=>"l",
							"ń"=>"n", "ó"=>"o", "ś"=>"s", "ź"=>"z",
							"ż"=>"z", "Ä"=>"a", "Ö"=>"o", "ẞ"=>"s",
							"Ü"=>"u", "ä"=>"a", "ö"=>"o", "ß"=>"s",
							"ü"=>"u"
  					];

	$text = strtr($text, $symbol);
	return $text;
}

//Фунция кодирования ссылок на php
public function urlEncoding($url){
	#$restored2 = $url;
	$restored = $url;
	
	$url = parse_url(rawurldecode(trim($url)));
  if(empty($url['scheme'])) { $url['scheme'] = '';} else { $url['scheme'] = $url['scheme'].'://';}

  //преобразования доменных имен в ascii
  if(empty($url['host'])) { 
  	
  	$url['host'] = '';
	
	} else { 
		
		//проверяем есть ли такая фунция на хостинге. Если есть юзаем ее.
		if (function_exists('idn_to_ascii')) {
			@$temp_host = idn_to_ascii($url['host']);
			if(!empty($temp_host)){ $url['host'] = $temp_host;}	
		}
	}

	if(empty($url['port'])) { $url['port'] = '';} else { $url['port'] = ':'.$url['port'];}
  if(empty($url['path'])) { $url['path'] = '';}
  if(empty($url['query'])) { $url['query'] = '';} else { $url['query'] = '?'.rawurlencode($url['query']);}

  $path = explode('/', $url['path']);  

  $path = array_map('rawurlencode', $path);   

  @$restored = $url['scheme'].$url['host'].$url['port'].implode('/', $path).$url['query'];   // Собрать перекодированный url обратно
  $restored = strtr($restored, ['%26amp%3B'=>'&','%26'=>'&','%3F'=>'?','%3D'=>'=','%3A'=>':','%2C'=>',','%2B'=>'+','&amp;'=>'&']);// Ибо rawurlencode заменяет равенство '=' на '%3D'
  # $restored = str_replace('%23', '#', $$restored); // Ибо rawurlencode заменяет якорь '#' на ''%23'
	
  #$this->wtfarrey($restored);
  return $restored;
}

public function fastscr($n = ''){
	//замеряем скорость.
	$this->wtfarrey($n.' => '.(microtime(true)-$_SERVER['REQUEST_TIME_FLOAT']));
}

//Версия модуля.
public function simpleParsVersion(){
	return 'v4.9_stable';
}

public function wtfarrey($data){
	#echo '<pre>';
	#print_r($data);
	#echo '</pre>';
}

}

?>
