<?php
class ControllerCommonSimpleConnector extends Controller {
    public function index() {
        $custom = isset($this->request->get['custom']) ? true : false;
        $method = isset($this->request->get['method']) ? trim($this->request->get['method']) : '';
        $filter = isset($this->request->get['filter']) ? trim($this->request->get['filter']) : '';

        if (!$method) {
            exit;
        }

        if (!$custom) {
            $this->load->model('tool/simpleapimain');

            if ($this->config->get('simple_disable_method_checking')) { 
                $this->response->setOutput(json_encode($this->model_tool_simpleapimain->{$method}($filter)));
            } else {
                if (method_exists($this->model_tool_simpleapimain, $method) || property_exists($this->model_tool_simpleapimain, $method) || (method_exists($this->model_tool_simpleapimain, 'isExistForSimple') && $this->model_tool_simpleapimain->isExistForSimple($method))) {
                    $this->response->setOutput(json_encode($this->model_tool_simpleapimain->{$method}($filter)));
                }
            }            
        } else {
            $this->load->model('tool/simpleapicustom');

            if ($this->config->get('simple_disable_method_checking')) { 
                $this->response->setOutput(json_encode($this->model_tool_simpleapicustom->{$method}($filter)));
            } else {
                if (method_exists($this->model_tool_simpleapicustom, $method) || property_exists($this->model_tool_simpleapicustom, $method) || (method_exists($this->model_tool_simpleapicustom, 'isExistForSimple') && $this->model_tool_simpleapicustom->isExistForSimple($method))) {
                    $this->response->setOutput(json_encode($this->model_tool_simpleapicustom->{$method}($filter)));
                }
            }            
        }
    }

    public function validate() {
        $custom = isset($this->request->get['custom']) ? true : false;
        $method = isset($this->request->get['method']) ? trim($this->request->get['method']) : '';
        $filter = isset($this->request->get['filter']) ? trim($this->request->get['filter']) : '';
        $value = isset($this->request->get['value']) ? trim($this->request->get['value']) : '';

        if (!$method) {
            exit;
        }

        if (!$custom) {
            $this->load->model('tool/simpleapimain');

            if ($this->config->get('simple_disable_method_checking')) { 
                $this->response->setOutput($this->model_tool_simpleapimain->{$method}($value, $filter) ? 'valid' : 'invalid');
            } else {
                if (method_exists($this->model_tool_simpleapimain, $method) || property_exists($this->model_tool_simpleapimain, $method) || (method_exists($this->model_tool_simpleapimain, 'isExistForSimple') && $this->model_tool_simpleapimain->isExistForSimple($method))) {
                    $this->response->setOutput($this->model_tool_simpleapimain->{$method}($value, $filter) ? 'valid' : 'invalid');
                }
            }            
        } else {
            $this->load->model('tool/simpleapicustom');

            if ($this->config->get('simple_disable_method_checking')) { 
                $this->response->setOutput($this->model_tool_simpleapicustom->{$method}($value, $filter) ? 'valid' : 'invalid');
            } else {
                if (method_exists($this->model_tool_simpleapicustom, $method) || property_exists($this->model_tool_simpleapicustom, $method) || (method_exists($this->model_tool_simpleapicustom, 'isExistForSimple') && $this->model_tool_simpleapicustom->isExistForSimple($method))) {
                    $this->response->setOutput($this->model_tool_simpleapicustom->{$method}($value, $filter) ? 'valid' : 'invalid');
                }
            }            
        }
    }

    public function zone() {
        $output = '<option value="">' . $this->language->get('text_select') . '</option>';

        $this->load->model('localisation/zone');

        $results = $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']);

        foreach ($results as $result) {
            $output .= '<option value="' . $result['zone_id'] . '"';

            if (isset($this->request->get['zone_id']) && ($this->request->get['zone_id'] == $result['zone_id'])) {
                $output .= ' selected="selected"';
            }

            $output .= '>' . $result['name'] . '</option>';
        }

        if (!$results) {
            $output .= '<option value="0">' . $this->language->get('text_none') . '</option>';
        }

        $this->response->setOutput($output);
    }

    public function geo() {
        $this->load->model('tool/simplegeo');

        $term = $this->request->get['term'];

        if (utf8_strlen($term) < 2) {
            exit;
        }

        $this->response->setOutput(json_encode($this->model_tool_simplegeo->getGeoList($term)));
    }

    public function upload() {
        $this->language->load('checkout/simplecheckout');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (!empty($this->request->files['file']['name'])) {
                $filename = $this->mb_basename(preg_replace('/[^\w\.\-\s_()+]/ui', '', html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')));

                if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 128)) {
                    $json['error'] = $this->language->get('error_filename');
                }

                // Allowed file extension types
                $extensions_allowed = array();
                $extensions = array();

                $config_extensions = $this->config->get('config_file_extension_allowed');

                if (empty($config_extensions)) {
                    $config_extensions = $this->config->get('config_file_ext_allowed');
                }

                if (!empty($config_extensions)) {
                    $config_extensions = preg_replace('~\r?\n~', "\n", $config_extensions);
                    $extensions = explode("\n", $config_extensions);
                }

                if (empty($config_extensions) || empty($extensions)) {
                    $config_extensions = $this->config->get('config_upload_allowed');
                    $extensions = explode(",", $config_extensions);
                }

                foreach ($extensions as $extension) {
                    $extensions_allowed[] = trim($extension);
                }

                if (!in_array(substr(strrchr($filename, '.'), 1), $extensions_allowed)) {
                    $json['error'] = $this->language->get('error_filetype');
                }

                $mime_allowed = $this->config->get('config_file_mime_allowed');
                
                if (!empty($mime_allowed)) {
                    $allowed_filetypes = array();

                    $mime_allowed = preg_replace('~\r?\n~', "\n", $mime_allowed);

                    $filetypes = explode("\n", $mime_allowed);

                    foreach ($filetypes as $filetype) {
                        $allowed_filetypes[] = trim($filetype);
                    }

                    if (!in_array($this->request->files['file']['type'], $allowed_filetypes)) {
                        $json['error'] = $this->language->get('error_filetype');
                    }
                }

                if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
                    $json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
                }
            } else {
                $json['error'] = $this->language->get('error_upload');
            }

            if (!isset($json['error'])) {
                if (is_uploaded_file($this->request->files['file']['tmp_name']) && file_exists($this->request->files['file']['tmp_name'])) {
                    $path = $this->request->files['file']['tmp_name'];
                    
                    $json['filename'] = $filename;

                    $file = '';

                    if ($this->config->get('simple_file_uploading_type') == '2') {
                        $file = $this->upload_to_dropbox($path, $filename); 
                    } else {
                        $file = $this->upload_to_server($path, $filename);
                    }      

                    if ($this->getOpencartVersion() < 200) {
                        $encryption = new Encryption($this->config->get('config_encryption'));
                        $json['file'] = $encryption->encrypt($file);
                    } else {
                        $this->load->model('tool/upload');
                        $json['file'] = $this->model_tool_upload->addUpload($filename, $file);
                    }   
                }
            }
        }

        $this->response->setOutput(json_encode($json));
    }

    private function mb_basename($path) {
        if (preg_match('@^.*[\\\\/]([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        } else if (preg_match('@^([^\\\\/]+)$@s', $path, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function upload_to_server($path, $filename) {
        $file = $filename . '.' . md5(mt_rand());

        move_uploaded_file($path, ($this->getOpencartVersion() < 200 ? DIR_DOWNLOAD : DIR_UPLOAD) . $file);
        
        return $file;
    }

    private function upload_to_dropbox($path, $filename) {
        $api_url = 'https://content.dropboxapi.com/2/files/upload'; 
       
        $headers = array('Authorization: Bearer '. $this->config->get('simple_file_uploading_dropbox_token'),
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: '.
            json_encode(
                array(
                    'path'       => '/'. basename($filename),
                    'mode'       => 'add',
                    'autorename' => true,
                    'mute'       => false
                )
            )

        );

        $ch = curl_init($api_url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);

        $fp = fopen($path, 'rb');
        $filesize = filesize($path);

        curl_setopt($ch, CURLOPT_POSTFIELDS, fread($fp, $filesize));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $result = @json_decode($response, true);
        
        curl_close($ch);

        if ($http_code != '200' || empty($result['name']) || empty($result['id'])) {
            return '';
        }

        return $result['name'].'.'.str_replace('id:', '', $result['id']);
    }

    public function download() {
        $code = isset($this->request->get['code']) ? trim($this->request->get['code']) : '';

        if (empty($code)) {
            exit('Error: Could not find file'); 
        }

        $filename = '';
        $name = '';

        if ($this->getOpencartVersion() < 200) {
            $encryption = new Encryption($this->config->get('config_encryption'));
            $filename = $encryption->decrypt($code);

            $name = $this->mb_basename(utf8_substr($filename, 0, utf8_strrpos($filename, '.')));
        } else {
            $this->load->model('tool/upload');
            $upload = $this->model_tool_upload->getUploadByCode($code);

            if (!empty($upload)) {
                $filename = $upload['filename'];
                $name = $upload['name'];
            } else {
                exit('Error: Could not find file');
            }
        } 

        if ($filename) {
            if ($this->config->get('simple_file_uploading_type') == '2') {
                $content = $this->download_from_dropbox($filename); 
            } else {
                $content = $this->download_from_server($filename);
            } 

            if (!headers_sent()) {
                header('Content-Type: application/octet-stream');
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . $name);
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit;
            } else {
                exit('Error: Headers already sent out');
            }
        } else {
            exit('Error: Could not find file');
        }
    }

    private function download_from_server($filename) {
        $path = ($this->getOpencartVersion() < 200 ? DIR_DOWNLOAD : DIR_UPLOAD) . $this->mb_basename($filename);

        if (@file_exists($path)) {
            return file_get_contents($path);
        } else {
            exit('Error: Could not find file');
        }
        
        return '';
    }

    private function download_from_dropbox($filename) {
        $api_url = 'https://content.dropboxapi.com/2/files/download'; 

        $id = utf8_substr($filename, utf8_strrpos($filename, '.') + 1);
      
        $headers = array('Authorization: Bearer '. $this->config->get('simple_file_uploading_dropbox_token'),
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: '.
            json_encode(
                array(
                    'path' => 'id:'.$id
                )
            )

        );

        $ch = curl_init($api_url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code != '200') {
            exit('Error: Could not find file');
        }

        return $response;
    }

    public function captcha() {
        $this->session->data['captcha'] = substr(sha1(mt_rand()), 17, 6);

        $image = imagecreatetruecolor(150, 35);

        $width = imagesx($image);
        $height = imagesy($image);

        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocatealpha($image, 255, 0, 0, 75);
        $green = imagecolorallocatealpha($image, 0, 255, 0, 75);
        $blue = imagecolorallocatealpha($image, 0, 0, 255, 75);

        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $red);
        imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $green);
        imagefilledellipse($image, ceil(rand(5, 145)), ceil(rand(0, 35)), 30, 30, $blue);

        imagefilledrectangle($image, 0, 0, $width, 0, $black);
        imagefilledrectangle($image, $width - 1, 0, $width - 1, $height - 1, $black);
        imagefilledrectangle($image, 0, 0, 0, $height - 1, $black);
        imagefilledrectangle($image, 0, $height - 1, $width, $height - 1, $black);

        imagestring($image, 10, intval(($width - (strlen($this->session->data['captcha']) * 9)) / 2), intval(($height - 15) / 2), $this->session->data['captcha'], $black);

        header('Content-type: image/jpeg');

        imagejpeg($image);

        imagedestroy($image);
    }

    public function human() {
        if (isset($this->session->data['get_used'])) {
            $this->session->data['human'] = true;
        }

        echo 'success'; 
    }

    public function header() {
        $opencartVersion = explode('.', VERSION);
        $opencartVersion = floatval($opencartVersion[0].$opencartVersion[1].$opencartVersion[2].'.'.(isset($opencartVersion[3]) ? $opencartVersion[3] : 0));

        if ($opencartVersion < 200) {
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/maintenance.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/common/maintenance.tpl';
            } else {
                $this->template = 'default/template/common/maintenance.tpl';
            }

            $this->data['message'] = '';

            $this->children = array(
                'common/header',
                'common/footer'
            );

            $this->response->setOutput($this->render());
        } else {
            $this->response->setOutput($this->load->controller('common/header'));
        }
    }

    public function mail_abandoned() {
        if (!isset($this->request->get['key']) || (isset($this->request->get['key']) && $this->request->get['key'] != $this->config->get('simple_cron_key'))) {
            echo 'wrong cron key!'; 
            exit;
        }

        $this->load->model('tool/simpleapi');
        $this->language->load('tool/simpleapi');

        $gap = 1; // интервал в часах, после которого корзина считается брошенной

        $results = $this->model_tool_simpleapi->getAbandonedCarts($this->config->get('simple_cron_time'), $gap);

        if (!empty($results)) {
            $message = '';
            $subject = $this->language->get('abandoned_mail_subject');

            $splitter = '';

            for ($i = 0; $i < 30; $i++) {
                $splitter .= '-';
            }

            if ($this->getOpencartVersion() < 200) {
                $datetime_format = $this->language->get('date_format_long');
            } else {
                $datetime_format = $this->language->get('datetime_format');
            }

            foreach ($results as $result) {
                $message .= $this->language->get('abandoned_mail_time') . ': '. date($datetime_format, strtotime($result['date_added'])) . "\n" . "\n";
                $message .= $this->language->get('abandoned_mail_name') . ': '. $result['name'] . "\n";
                $message .= $this->language->get('abandoned_mail_email') . ': '. $result['email'] . "\n";
                $message .= $this->language->get('abandoned_mail_telephone') . ': '. $result['telephone'] . "\n" . "\n";

                $message .= $this->language->get('abandoned_mail_products') . ': ' . "\n" . "\n";

                $products = json_decode($result['products'], true);
                
                foreach ($products as $product) {
                    $message .= $product['quantity'] . ' x ' . $product['name'] . ' (' . $product['model'] . ')' . ' ' . $product['price'] . ' = ' . $product['total'] . "\n";
                    
                    if (!empty($product['option']) && is_array($product['option'])) {
                        foreach ($product['option'] as $option) {
                            $message .= '- ' . $option['name'] . ': ' . $option['value'] . "\n";
                        }   
                    }
                }  
                
                $message .= "\n" . $splitter . "\n" . "\n";          
            }

            $this->send_mail($this->config->get('config_email'), $subject, $message);

            $emails = array();

            if ($this->config->get('config_alert_email')) {
                $emails = explode(',', $this->config->get('config_alert_email'));
            }

            if ($this->config->get('config_alert_emails')) {
                $emails = explode(',', $this->config->get('config_alert_emails'));
            }

            foreach ($emails as $email) {
                if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
                     $this->send_mail($email, $subject, $message);
                }
            }            
        }        

        $this->model_tool_simpleapi->updateLastCronTime($this->getOpencartVersion() <= 200); 
    }

    private function send_mail($to, $subject, $message) {
        $opencartVersion = $this->getOpencartVersion();

        if ($opencartVersion < 200) {
            $mail = new Mail();
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');             
            $mail->setTo($to);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        } elseif($opencartVersion < 203) {
            $mail = new Mail($this->config->get('config_mail'));
            $mail->setTo($to);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject($subject);
            $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        } else {
            if ($opencartVersion < 300) {
                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
            } else {
                $mail = new Mail($this->config->get('config_mail_engine'));
            }          
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
            $mail->setTo($to);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }
    }

    private function getOpencartVersion() {
        $opencartVersion = explode('.', VERSION);
        return floatval($opencartVersion[0].$opencartVersion[1].$opencartVersion[2].'.'.(isset($opencartVersion[3]) ? $opencartVersion[3] : 0));
    }
}
