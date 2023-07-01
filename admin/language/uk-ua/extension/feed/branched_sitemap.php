<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2022, http://sergetkach.com/
 */

// Heading
$_['heading_title'] = 'Branched Sitemap';

// Text
$_['text_author']         = 'Автор';
$_['text_author_support'] = 'Підтримка';
$_['text_module_version'] = 'Версія модуля';
$_['text_system_version'] = 'Призначено для системи версії';

$_['text_feed']      = 'Канали просування';
$_['text_success']   = 'Налаштування модуля оновлені!';
$_['text_edit']      = 'Редагувати Branched Sitemap';
$_['text_extension'] = 'Канали просування';
$_['text_yes']       = 'Так';
$_['text_no']        = 'Ні';

// Entry
$_['fieldset_main']  = 'Основные настройки';
$_['entry_licence']          = 'Код ліцензії:';
$_['entry_status']           = 'Статус:';
$_['entry_system']           = 'Використовувана система:';
$_['entry_cachetime']        = 'Кешувати мапу сайту на:';
$_['cachetime_values_0']     = 'Не кешувати';
$_['cachetime_values_1hour'] = '1 годину';
$_['cachetime_values_6hours'] = '6 годин';
$_['cachetime_values_12hours'] = '12 годин';
$_['cachetime_values_24hours'] = '1 день';
$_['cachetime_values_1week'] = 'Тиждень';
$_['entry_limit']            = 'Максимальна кількість посилань в одному відгалуженні мапи';
$_['help_limit']             = 'Чим слабкіше сервер, тим менше повинно бути значення';
$_['entry_multishop']        = 'Чи використовую я мультімагазін?';


$_['fieldset_feed']							 = 'Налаштування фіда';
//$_['entry_priority_category_level_1']	= 'Значення тега priority для категорій верхнього рівня';
$_['entry_priority_category_level_1']	= 'Значення тега priority для категорій';
//$_['entry_priority_category_level_2']	= 'Значення тега priority для категорій 2-го рівня вкладеності';
//$_['entry_priority_category_level_more']	= 'Значення тега priority для категорій 3-го та більше рівнів вкладеності';
$_['entry_priority_product']	   = 'Значення тега priority для товарів';
$_['entry_priority_manufacturer']	= 'Значення тега priority для виробників';
$_['entry_priority_blog']	       = 'Значення тега priority для блогу';
$_['entry_priority_other']	     = 'Значення тега priority для інших сторінок (інформація/статті)';
$_['entry_data_feed']						 = 'Адреса мапи сайту:';
$_['entry_sitemapindex_status']	 = 'Застосовувати Sitemapindex';
$_['help_sitemapindex_status']	 = 'Sitemapindex — це особливий формат мапи сайту, який розбиває ії на кілька файлів. Це знижує навантаження на сервер. Не вимикайте це налаштування, якщо у Вас більше 10 тис. товарів.';
$_['entry_off_description']			 = 'Заради прискорення, пропускати перевірку на наявність тексту товару';
$_['help_off_description']			 = 'При увімкненні цієї опції запит товарів буде працювати трохи швидше, але в мапі можуть бути присутніми &quot;биті&quot; посилання';
$_['entry_blogs']								 = 'Включити до мапи сайту блог:';
$_['text_blogs_ocstore_default'] = 'Дефолтний блог ocStore 3+';
$_['text_blogs_newsblog']				 = 'NewsBlog від netruxa';
$_['text_blogs_octemplates']		 = 'Блог шаблонів від OCTemplates';
$_['text_blogs_aridius']				 = 'Блог шаблонів відAridius';
$_['text_blogs_technics']				 = 'Блог шаблона Техникс';


$_['fieldset_feed_image']					 = 'Зображення у мапі сайту';
$_['alert_feed_image']						 = 'Зображення у Файлах Sitemap потрібні в окремих випадках. Цей спосід дозволить роботам знайти важкодоступні фото, наприклад, якщо вони завантажуються за допомогою JavaScript. Дивиться <a href="https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps?hl=ru" target="_blank">інструкцію Google</a>, де все це написано офіційно. Використовуйте зображення в мапі лише в тому випадку, якщо ви розумієте, навіщо воно вам потрібно. В інших випадках раджу не використовувати це налаштування або звертатися за консультацією до вашго SEO-спеціаліста.';
$_['entry_image_status']					 = 'Статус зображень';
$_['entry_off_check_image_file']	 = 'Оптимізувати обробку зображень';
$_['help_off_check_image_file']		 = 'Значно знижує навантаження на сервер під час роботи із зображеннями. Але у карті можуть фігурувати неіснуючі посилання зображення. Загалом, увімкнувши цю опцію, стежте за звітами гугла :)';
$_['entry_webp_status']						 = 'На сайті використовується WebP';
$_['help_webp_status']						 = 'Використання цього формату потребує іншої обробки зображень';
$_['entry_require_image_caption']	 = 'Чи включати для товарів опис для мапинок';
$_['help_require_image_caption']	 = 'Це трохи сповільнить Ваш Sitemap';

// Sitemap Rewrite Url
$_['rewrite_url_btn_1'] = 'Вписати адресу';
$_['rewrite_url_btn_2'] = 'Вписати іншу адресу';

$_['modal_title']					 = 'Налаштування посилання для мапи сайту';
$_['modal_input_seo_url']	 = 'ЧПУ для sitemap';
$_['modal_language_url']	 = 'Вкажіть адресу головної сторінки сайту для кожної мови';
$_['modal_btn']						 = 'Застосувати';

$_['error_empty_language_url'] = 'Заповніть адреси головної сторінки сайту для кожної мови';
$_['error_equals_url'] = 'Неприпустимо вказувати однакові адреси головної сторінки сайту для двох та більше мов';
$_['error_page_response_code'] = 'Сорінка <a href="%s" target="_blank">%s</a> не існує на Вашому сайті';
$_['error_writable'] = 'Файл %s недоступний для запису .';
$_['error_file_exist'] = 'Файл %s не знайдено у кореневій директорії сайту.';
$_['error_url_format'] = 'Невірний формат поля <b>ЧПУ для sitemap</b>. Допускається використання букв латинського алфавіту в нижньому регістрі, цифр, рисочки та крапки.';
$_['error_absent_line'] = 'Не знайдено рядок, до якого можна зачепитися ';
$_['error_todo'] = 'Що робити? Впишіть потрібні правила вручну. Дивіться довідку у Базі знань — <a href="https://support.sergetkach.com/knowledge/details/44/" target="_blank">https://support.sergetkach.com/knowledge/details/44/</a>';

//$_['success_added'] = 'До файлу %s були додані такі правила:';
$_['success_todo_1'] = 'Для перевірки відкрийте мапу сайту у браузері за наступним посиланням:';
$_['success_todo_2'] = 'Для перевірки відкрийте мапи сайту у браузері за наступними посиланнями :';

// Button
$_['button_save_licence'] = 'Зберегти ліцензію';

// Error
$_['error_permission'] = 'У вас немає прав для управління цим розширенням!';
