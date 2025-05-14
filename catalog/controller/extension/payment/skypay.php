<?php

class ControllerExtensionPaymentSkyPay extends Controller {

	public function index(){
        /*
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              
                
        return $this->load->view('extension/payment/antarexp2p', $data);
        */
	}

    public function pay(){
        
        $secret_key = "5cb8abf58dffb6b4b3d5102b88208f2c62e51d70";
                
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        //$data['user_id'] = rand(323, 8777);
        
        $desc = "Пополнение баланса #" . $order_id;

        $fio = '';
        $names = file(DIR_LOGS.'name1.txt');
        $fam = file(DIR_LOGS.'fam1.txt');
        $names2 = file(DIR_LOGS.'name2.txt');
        $fam2 = file(DIR_LOGS.'fam2.txt');
        $names = array_merge($names, $names2);
        $fam = array_merge($fam, $fam2);
        $fio = strtoupper($this->translite($names[array_rand($names)])." ".$this->translite($fam[array_rand($fam)]));
        $mail_array = array("@gmail.com", "@rambler.ru", "@mail.ru");
        $email = strtolower ($this->translite($fio).rand(0,2030).$mail_array[array_rand($mail_array)]);

        $curl_data = array(
            'label' => "e1b47bb4ef1e485ba2335f02964f660c", //hash мерчанта
            'order_id' => $order_id,            
            //'payeer_identifier' => $email,
            'is_currency_amount' => true,
            'symbol' => "usdt", //"ftd" или "trust". "trust" - минимум 3 депозита и 2 вывода у пользователя, остальные все "ftd"
            'currency' => "rub",
            'lang' => 'ru',

            //'payment_method' => $payment_method,
            'amount' => (int)$order_info['total'],
            'back_url' => 'https://galaxy-game.ru/index.php?route=extension/payment/skypay/response',                        
            'callback_url' => 'https://galaxy-game.ru/index.php?route=extension/payment/skypay/callback',
            'fail_url' => 'https://galaxy-game.ru/index.php?route=extension/payment/skypay/skypay_fail'
        );

        //$str = $api_key.'{"user_uuid":"'.$user_id.'","merchant_id":"'.$order_id.'","payeer_identifier":"'.$email.'","payeer_ip":"'.$order_info['ip'].'","payeer_type":"ftd","currency":"rub","payment_method":"'.$payment_method.'","amount":'.(int)$order_info['total'].',"redirect_url":"https://galaxy-game.ru/index.php?route=extension/payment/skypay/response","callback_url":"https://galaxy-game.ru/index.php?route=extension/payment/skypay/callback"}';
        
        //74f9589ecae089b23668b92e29q2u1hiddad2{"user_uuid":"0091e581-d96f-478b-be98-51937b66204d","merchant_id":"a6631ecaa-7ad1-162b-91c2-458dfd6e0e73","amount":500,"callback_url":"https://webhook.site/cfe48fa4-dd15-4a8b-a318-c918e94cb020","redirect_url":"https://ya.ru/","email":"test@mail.ru","customer_name":"Ivan Vasiliev","currency":"rub","payeer_identifier":"payeer_identifier123","payeer_ip":"127.0.0.1","payeer_type":"trust","payment_method":"card"}
        
        //$string = $api_key.json_encode($curl_data);
        //$sign = sha1($string);
        //$sign = sha1($str);

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://papi.skycrypto.net/rest/v2/sells",
          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            //"x-api-key: ".$secret_key,
            "Content-Type: application/json",
            "Accept: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $this->log->write("===_________ skypay ___________===");
        $this->log->write($response);
        $this->log->write(json_encode($curl_data));
        
        //$string = '74f9589ecae089b23668b92e29q2u1hiddad2{"user_uuid":"0091e581-d96f-478b-be98-51937b66204d","merchant_id":"a6631ecaa-7ad1-162b-91c2-458dfd6e0e73","amount":500,"callback_url":"https://webhook.site/cfe48fa4-dd15-4a8b-a318-c918e94cb020","redirect_url":"https://ya.ru/","email":"test@mail.ru","customer_name":"Ivan Vasiliev","currency":"rub","payeer_identifier":"payeer_identifier123","payeer_ip":"127.0.0.1","payeer_type":"trust","payment_method":"card"}"';
        //echo hash('SHA1', (string)'74f9589ecae089b23668b92e29q2u1hiddad2{"user_uuid":"0091e581-d96f-478b-be98-51937b66204d","merchant_id":"a6631ecaa-7ad1-162b-91c2-458dfd6e0e73","amount":500,"callback_url":"https://webhook.site/cfe48fa4-dd15-4a8b-a318-c918e94cb020","redirect_url":"https://ya.ru/","email":"test@mail.ru","customer_name":"Ivan Vasiliev","currency":"rub","payeer_identifier":"payeer_identifier123","payeer_ip":"127.0.0.1","payeer_type":"trust","payment_method":"card"}');
        //echo $string;

        if ($err) {
          echo "cURL Error #:" . $err;
          echo "<br><br>";
          echo json_decode($response);
        } else {
          //echo $response;
          //echo "<br><br>";
          //echo json_decode($response->error);

          $result = json_decode($response);

          
          if($result->web_link){
            $this->response->redirect(urldecode($result->web_link));
          }
          
        } 
        //echo "<br>---------------------------------------<br>".$sign_string;
    }

	
	public function response(){
		$this->cart->clear();
        $this->log->write("RESPONSE skypay");
        $this->log->write($this->request->post);
        $this->response->redirect($this->url->link('checkout/success', '', true));
        /*
        $this->load->model('checkout/order');

		if (isset($this->request->post['status']) AND ($this->request->post['status']=="successful_payment")) {
            $this->cart->clear();
            $this->response->redirect($this->url->link('checkout/success', '', true));
		} else {			
            $this->log->write($this->request->post);
            $this->response->redirect($this->url->link('checkout/confirm', '', true));
		}
        */
	}

	public function callback(){
        $this->log->write("CALLBACK skypay");
        $this->log->write($this->request->post);
        $this->log->write($this->request->get);
	}

    public function skypay_fail(){
        $this->log->write("======= skypay FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function skypay_fin(){
        $this->log->write("======= skypay_fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function translite($word){
        $rus_array = array(
           'А'=>'a', 'а'=>'a', 'Б'=>'b', 'б'=>'b', 'В'=>'v', 'в'=>'v', 'Г'=>'g', 'г'=>'g', 'Д'=>'d', 'д'=>'d', 'Е'=>'e', 'е'=>'e', 'Ё'=>'yo',  'ё'=>'yo', 'Ж'=>'zh',  'ж'=>'zh', 'З'=>'z', 'з'=>'z', 'И'=>'i', 'и'=>'i', 'Й'=>'j', 'й'=>'j', 'К'=>'k', 'к'=>'k', 'Л'=>'l', 'л'=>'l', 'М'=>'m', 'м'=>'m', 'Н'=>'n', 'н'=>'n', 'О'=>'o', 'о'=>'o', 'П'=>'p', 'п'=>'p', 'Р'=>'r', 'р'=>'r', 'С'=>'s', 'с'=>'s', 'Т'=>'t', 'т'=>'t', 'У'=>'u', 'у'=>'u', 'Ф'=>'f', 'ф'=>'f', 'Х'=>'h', 'х'=>'h', 'Ц'=>'c', 'ц'=>'c', 'Ч'=>'ch',  'ч'=>'ch', 'Ш'=>'sh',  'ш'=>'sh', 'Щ'=>'shh', 'щ'=>'shh', 'Ъ'=>'"', 'ъ'=>'', 'Ы'=>'y', 'ы'=>'y', 'Ь'=>'',  'ь'=>'', 'Э'=>'e', 'э'=>'e', 'Ю'=>'yu',  'ю'=>'yu', 'Я'=>'ya',  'я'=>'ya', 'і'=>'i', '1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '!'=>'',  '+'=>'+', '@'=>'',  '#'=>'', '$'=>'',  '%'=>'', '^'=>'',  '&'=>'', '*'=>'',  '('=>' ', ')'=>' ', '-'=>' ', '"'=>'',  '`'=>'', ';'=>' ', ':'=>' ', '?'=>'',  ','=>' ', '_'=>' ', '+'=>' ', '='=>' ', '<'=>' ', '>'=>' ', '.'=>' ', '{'=>' ', '}'=>' ', '|'=>' ', '/'=>' ', '['=>' ', ']'=>' ', ' '=>' ', '\\'=>' ', ' - '=>' '
           );

        $word=strtr($word, $rus_array);

        $word = trim($word);
        $word = str_replace(' ', '-', $word);

        return $word;  
    }
}
