<?php

/**
 * @category   OpenCart
 * @package    Branched Sitemap
 * @copyright  © Serge Tkach, 2018–2022, http://sergetkach.com/
 */

// Heading
$_['heading_title'] = 'Branched Sitemap';

// Text
$_['text_author']         = 'Author';
$_['text_author_support'] = 'Support';
$_['text_module_version'] = 'Extension version';
$_['text_system_version'] = 'For OpenCart version';

$_['text_feed']      = 'Extensions';
$_['text_success']   = 'Success: You have modified Branched Sitemap feed!';
$_['text_edit']      = 'Edit Branched Sitemap';
$_['text_extension'] = 'Extensions';
$_['text_yes']       = 'Yes';
$_['text_no']        = 'No';

// Entry
$_['fieldset_main']          = 'Basic settings ';
$_['entry_licence']          = 'Licence code:';
$_['entry_status']           = 'Status:';
$_['entry_system']           = 'Following System of OpenCart:';
$_['entry_cachetime']        = 'Cache sitemap for:';
$_['cachetime_values_0']     = 'Do not cache';
$_['cachetime_values_1hour'] = '1 hour';
$_['cachetime_values_6hours'] = '6 hours';
$_['cachetime_values_12hours'] = '12 hours';
$_['cachetime_values_24hours'] = 'A day';
$_['cachetime_values_1week'] = 'A week';
$_['entry_limit']            = 'Products limit per one sitemap branch:';
$_['help_limit']             = 'The weaker the server, the less the value should be';
$_['entry_multishop']        = 'I use the multistore?';


$_['fieldset_feed']							 = 'Feed setting';
//$_['entry_priority_category_level_1']	= 'The value of the priority tag for top-level categories';
$_['entry_priority_category_level_1']	= 'The value of the priority tag for categories';
//$_['entry_priority_category_level_2']	= 'The value of the priority tag for 2nd level categories';
//$_['entry_priority_category_level_more']	= 'The value of the priority tag for 3nd and more level categories';
$_['entry_priority_product']	   = 'The value of the priority tag for products';
$_['entry_priority_manufacturer']	= 'The value of the priority tag for manufacturers';
$_['entry_priority_blog']	       = 'The value of the priority tag for blog';
$_['entry_priority_other']	     = 'The value of the priority tag for information';
$_['entry_data_feed']						 = 'Data Feed Url:';
$_['entry_sitemapindex_status']	 = 'Use Sitemapindex';
$_['help_sitemapindex_status']	 = 'Sitemapindex is a special sitemap format that splits the sitemap into multiple files. This reduces the load on the server. Do not disable this setting if you have more than 10 thousand products.';
$_['entry_off_description']			 = 'For the sake of acceleration, skip checking for the text of the goods';
$_['help_off_description']			 = 'When enabled, the request for goods will work a little faster, but there may be “broken” goods in the map';
$_['entry_blogs']								 = 'Require blog:';
$_['text_blogs_ocstore_default'] = 'ocStore 3+ Default Blog';
$_['text_blogs_newsblog']				 = 'NewsBlog by netruxa';
$_['text_blogs_octemplates']		 = 'OCTemplates theme\'s Blog';
$_['text_blogs_aridius']				 = 'Aridius theme\'s Blog';
$_['text_blogs_technics']				 = 'Technics theme\'s Blog';

$_['fieldset_feed_image']  = 'Image in sitemaps (optional)';
$_['alert_feed_image']  = 'Image in sitemap are needed in specific cases. This method will allow robots to find hard-to-reach photos, for example, if they are loaded using JavaScript. See <a href="https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps?hl=en" target="_blank">Google instruction</a> where it is all told officially.  Use image in sitemap only if you know why you need it. In other cases, I highly recommend not using this setting or consult with your SEO specialist.';
$_['entry_image_status']           = 'Image Status';
$_['entry_off_check_image_file']  = 'Optimize image processing';
$_['help_off_check_image_file']   = 'Significantly reduced server load when opening the sitemap for images. But non-existent image links may appear in the sitemap. In general, by enabling this option, follow Google reports :)';
$_['entry_webp_status']	 = 'The site uses WebP';
$_['help_webp_status']		 = 'Using this format requires different image processing';
$_['entry_require_image_caption'] = 'Include a caption for the goods for the pictures';
$_['help_require_image_caption'] = 'This is a little slow down your sitemap';

// Sitemap Rewrite Url
$_['rewrite_url_btn_1'] = 'Set address';
$_['rewrite_url_btn_2'] = 'Set other address';

$_['modal_title'] = 'Set up a sitemap link';
$_['modal_input_seo_url'] = 'CNC for sitemap';
$_['modal_language_url'] = 'Specify the address of the main page of the site for each language';
$_['modal_btn'] = 'Apply';

$_['error_empty_language_url'] = 'Fill in the addresses of the main page of the site for each language';
$_['error_equals_url'] = 'It is not allowed to specify the same addresses of the main page of the site for 2 or more languages';
$_['error_page_response_code'] = 'Page <a href="%s" target="_blank">%s</a> doesn\'t exist on your site';
$_['error_writable'] = 'The file %s is not writable. ';
$_['error_file_exist'] = 'The file %s not found in the root directory of the site.';
$_['error_url_format'] = 'Invalid <b>CNC for sitemap</b> field format. It is allowed to use letters of the Latin alphabet in lower case, numbers, dashes and dots.';
$_['error_absent_line'] = 'No string found to hook on';
$_['error_todo'] = 'What to do? Enter the required rules manually. See help in the Knowledge Base — <a href="https://support.sergetkach.com/knowledge/details/44/" target="_blank">https://support.sergetkach.com/knowledge/details/44/</a>';

//$_['success_added'] = 'The following rules have been added to the file %s:';
$_['success_todo_1'] = 'To check, open the Sitemap in a browser using the link:';
$_['success_todo_2'] = 'To check, open next Sitemaps in a browser using the links:';

// Button
$_['button_save_licence'] = 'Save Licence';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Google Sitemap feed!';
