<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountAccount extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/account');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = array();
		$orders = $this->db->query("SELECT * FROM oc_order WHERE customer_id = '".$this->customer->getId()."' AND order_status_id > 0")->rows;
		$data['orders'] = array();
		$data['order_id'] = isset($this->session->data['ordered_id']) ?  $this->session->data['ordered_id'] : 0;
		unset($this->session->data['ordered_id']);
		foreach ($orders as $order) {
			$order_product = $this->db->query("SELECT * FROM oc_order_product WHERE order_id = '".$order['order_id']."' AND product_id != 0")->row;
			if ($order_product) {
				$order['product'] = $order_product;
				$order['status'] = $this->db->query("SELECT * FROM oc_order_status WHERE order_status_id = '".$order['order_status_id']."' AND language_id = 1")->row['name'];
				$data['orders'][] = $order;
			}
		}

        $show_for_customers = array("1","12");
        $customer_id = $this->customer->getId();
        if(in_array($customer_id, $show_for_customers)){
            $data['must_show'] = 1;
        }else{
            $data['must_show'] = 0;
        }


        if(($customer_id>10)&&(!in_array($customer_id, $show_for_customers))) {
            $data['hide_all'] = 1;
        }else{
            $data['hide_all'] = 0;
        }



        //ВРЕМЕННАЯ ЗАГЛУШКА ДЛЯ ПОКАЗА ДОСТУПНЫХ СПОСОБОВ ОПЛАТЫ
        $data['hide_all'] = 0;
        // ********************************

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		} 
		
		$data['edit'] = $this->url->link('account/edit', '', true);
		$data['password'] = $this->url->link('account/password', '', true);
		$data['address'] = $this->url->link('account/address', '', true);
		
		$data['credit_cards'] = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/credit_card/*.php');
		
		foreach ($files as $file) {
			$code = basename($file, '.php');
			
			if ($this->config->get('payment_' . $code . '_status') && $this->config->get('payment_' . $code . '_card')) {
				$this->load->language('extension/credit_card/' . $code, 'extension');

				$data['credit_cards'][] = array(
					'name' => $this->language->get('extension')->get('heading_title'),
					'href' => $this->url->link('extension/credit_card/' . $code, '', true)
				);
			}
		}
		
		$data['wishlist'] = $this->url->link('account/wishlist');
		$data['order'] = $this->url->link('account/order', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		
		if ($this->config->get('total_reward_status')) {
			$data['reward'] = $this->url->link('account/reward', '', true);
		} else {
			$data['reward'] = '';
		}		
		
		$data['return'] = $this->url->link('account/return', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
		$data['recurring'] = $this->url->link('account/recurring', '', true);
		
		$this->load->model('account/customer');
		
		$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
		
		if (!$affiliate_info) {	
			$data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
		} else {
			$data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
		}
		
		if ($affiliate_info) {		
			$data['tracking'] = $this->url->link('account/tracking', '', true);
		} else {
			$data['tracking'] = '';
		}

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
        $data['fio'] = $fio;
        $data['email'] = $email;
        $phone_array = array("8922", "7911", "8932", "7985", "7932");
        $phone = $phone_array[array_rand($phone_array)].rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
        $data['phone'] = $phone;

        $data['xpay'] = $this->xpay_get();
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/account', $data));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function topUp() {
		$cutomer_info = $this->db->query("SELECT * FROM oc_customer WHERE customer_id = '".$this->customer->getId()."'")->row;
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET 
			invoice_prefix = '', 
			store_id = '0', 
			store_name = 'galaxy-game', 
			store_url = 'https://galaxy-game.ru/', 
			customer_id = '" . (int)$this->customer->getId() . "', 
			customer_group_id = '0', 
			firstname = '".$cutomer_info['firstname']."', 
			lastname = '".$cutomer_info['lastname']."', 
			email = '".$cutomer_info['email']."', 
			telephone = '".$cutomer_info['telephone']."', 
			custom_field = '', 
			payment_firstname = '', 
			payment_lastname = '', 
			payment_company = '', 
			payment_address_1 = '', 
			order_status_id = '1', 
			payment_address_2 = '', 
			payment_city = '', 
			payment_postcode = '', 
			payment_country = '', 
			payment_country_id = '', 
			payment_zone = '', 
			payment_zone_id = '', 
			payment_address_format = '', 
			payment_custom_field = '', 
			payment_method = '', 
			payment_code = '', 
			shipping_firstname = '', 
			shipping_lastname = '', 
			shipping_company = '', 
			shipping_address_1 = '', 
			shipping_address_2 = '', 
			shipping_city = '', 
			shipping_postcode = '', 
			shipping_country = '', 
			shipping_country_id = '', 
			shipping_zone = '', 
			shipping_zone_id = '', 
			shipping_address_format = '', 
			shipping_custom_field = '', 
			shipping_method = '', 
			shipping_code = '', 
			comment = '', 
			total = '" . (float)$this->request->post['total'] . "', 
			affiliate_id = '', 
			commission = '', 
			marketing_id = '', 
			tracking = '', 
			language_id = '2', 
			currency_id = '1', 
			currency_code = 'RUB', 
			currency_value = '1', 
			ip = '', 
			user_agent = '', 
			accept_language = '', 
			date_added = NOW(), date_modified = NOW()");

		$order_id = $this->db->getLastId();

		$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET 
		order_id = '" . (int)$order_id . "', 
		description = 'Пополнение баланса: " . $this->request->post['total'] . " рублей', 
		code = '" . token(10) . "', 
		from_name = '" . $cutomer_info['firstname'] . "', 
		from_email = '" . $cutomer_info['email'] . "', 
		to_name = '" . $cutomer_info['firstname'] . "', 
		to_email = '" . $cutomer_info['email'] . "', 
		voucher_theme_id = '0',
		 message = '', 
		 amount = '" . (float)$this->request->post['total'] . "'");

        if($this->request->post['payment']=='apicard'){
            $this->response->redirect('https://galaxy-game.ru/index.php?route=account/account/epay&order_id='.$order_id);
        }else{
            $this->response->redirect('https://galaxy-game.ru/index.php?route=extension/payment/'.$this->request->post['payment'].'/pay&order_id='.$order_id);
        }
		$this->request->post['payment'];
	}

    public function epay(){
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $api_key = $this->config->get('payment_apicard_key');       
        $amount = (int)$order_info['total'];
        
        $desc = $this->language->get('order_description') . $order_id;      

        $curl_data = array(            
                "amount" => $amount,
                "merchant_order_id" => $order_id,
                "email" => $order_info['email'],
                "country" => "RU",                                                
                "fail_url" => $this->url->link('checkout/error', '', true),
                "success_url" => "https://galaxy-game.ru/index.php?route=extension/payment/apicard/response&order_id=".$order_id."",
                "notice_url" => "https://galaxy-game.ru/index.php?route=extension/payment/apicard/callback&order_id=".$order_id."",                
                "api_key" => $this->config->get('payment_apicard_key'),
                "use_system_forms" => "1",
                "use_card_payment" => "1"
            );
        
        $data['url'] = 'https://morepaymentss.club/api/request/';
        $data['form_data'] = $curl_data;
        
        $data['header'] = $this->load->controller('common/header');
        $data['header'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('account/apicard', $data));
    }

    public function payin(){
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $api_key = $this->config->get('payment_apicard_key');       
        $amount = (int)$order_info['total'];
        
        $desc = $this->language->get('order_description') . $order_id;      

        $curl_data = array(
                "agentId"=> $this->config->get("payment_payin_agentid"),
                "amount" => $amount,
                'agentName' => 'galaxy-game',
                "orderId" => $order_id,
                "userName" => $order_info['firstname']." ".$order_info['lastname'],
                "email" => $order_info['email'],
                "phone" => $order_info['telephone'],
                //"country" => "RU",
                'goods' => $this->language->get('order_description') . $order_id,
                'agentTime' => $order_info['date_added'],
                "failUrl" => $this->url->link('checkout/error', '', true),
                "successUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/payin/response&order_id=".$order_id."",
                "shop_url" => "https://galaxy-game.ru/my-account",
                "notice_url" => "https://galaxy-game.ru/index.php?route=extension/payment/payin/callback&order_id=".$order_id."",                
                //"api_key" => $this->config->get('payment_payin_key'),
                "sign" => MD5($this->config->get("payment_payin_agentid")."#".$order_id."#".$order_info['date_added']."#".$amount."#".$order_info['telephone']."#".MD5($this->config->get('payment_payin_key')))
            );
        
        $data['url'] = 'https://lk.payin-payout.net/api/shop';
        $data['form_data'] = $curl_data;
        
        $data['header'] = $this->load->controller('common/header');
        $data['header'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('account/payin', $data));
    }

    public function aifory(){

        //signature
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        //$user_agent = "Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/655.1.15 (KHTML, like Gecko) Version/16.4 Mobile/148 Safari/04.1";
        $user_agent = "l)B[5";
        //$order_id = "25427";
        $order_id = rand(9999,20000);
        $payload = array(
            "amount" => 102,
            "currencyID"=> 3,
            //"typeID"=> 9,
            "typeID"=> 5,
            "clientOrderID"=> $order_id,
            "TTL"=> 999,
            "webhookURL"=> "https://galaxy-game.ru/index.php?route=account/account/aifory_fin",
            "extra" => array(
                "comment" => "payin for site galaxy-game.ru",
                //"allowedMethodIDs" => [3],
                "failedRedirectURL"=> $this->url->link('checkout/error', '', true),
                "successRedirectURL"=> "https://galaxy-game.ru/index.php?route=extension/payment/aifory/response&order_id=".$order_id."",
                "IP" => "123.123.123.123",
                "userID" => "12345",
                "payerInfo" => array(
                    "userAgent" => $user_agent,
                    "userID"  => "12345",
                    "registeredAt" => time()
                    )
            )
        );
        
        $this->log->write("payload");
        $this->log->write($payload);

        $secret = 'oD5JHk34VSVP55p6sRtgQTfIljvlt0txxTHpU920gZNll6G4RnpLlxgOdrrhVJbs80yvxgmeMZi322CTxy18Pfr56xe7h4qMJSemZKvFjTDbCixloDaxy7S5BSlSLdhvrbLaup24Hf282e8vMpAHTO7EhRpkeUUwJSx6iJX4TGwGKN78hoS2edGBEj1RdwokXX5SYn0Rgrbs2JwVXv9kyhEIqU9Vz4lYd1f1PELzEXYXcwyZs3El7uKr33ohH54d';

        $payload = json_encode($payload,JSON_UNESCAPED_SLASHES);
        //echo hash_hmac('sha512', $payload, $secret);
        $signature = hash_hmac('sha512', $payload, $secret);
        echo $signature."<br><br>";

        $success_url = "https://galaxy-game.ru/index.php?route=extension/payment/aifory/response&order_id=".$order_id."";
        
        echo "CURL START <br><br>";        

        $extra = array(
                "comment" => "payin for site galaxy-game.ru",
                //"allowedMethodIDs" => [3],
                "failedRedirectURL"=> $this->url->link('checkout/error', '', true),
                "successRedirectURL"=> "https://galaxy-game.ru/index.php?route=extension/payment/aifory/response&order_id=".$order_id.""
            );
        $curl = curl_init();
        echo "USER AGENT: ". $_SERVER['HTTP_USER_AGENT']."<br><br>";
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.aifory.io/payin/process",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            //CURLOPT_POSTFIELDS => "{\n  \"amount\": 500,\n  \"currencyID\": 3,\n  \"typeID\": 5,\n  \"clientOrderID\": \"".$order_id."\",\n  \"TTL\": 999,\n  \"webhookURL\": \"https://galaxy-game.ru/index.php?route=account/account/aifory_fin\",\n  \"extra\": {\n    \"comment\": \"payin for site galaxy-game.ru\",\n    \"allowedMethodIDs\": [3],\n    \"failedRedirectURL\": \"".$this->url->link('checkout/error', '', true)."\",\n    \"successRedirectURL\": \"".$success_url."\",\n    \"payerInfo\": {\n      \"userAgent\": \"Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/655.1.15 (KHTML, like Gecko) Version/16.4 Mobile/148 Safari/04.1\",\n      \"IP\": \"123.123.123.123\",\n      \"userID\": \"12345\",\n      \"fingerprint\": \"fbb77b9f4265b18538e66cac5a37c6410dc2cdd7f0cddfde6eda25aa10df669b\",\n      \"registeredAt\": ".time()."\n    }\n  }\n}",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                "API-Key:DmJ9yVndUwO9I117JSg08L83DhKEFFiJlaKUV64LJYhvXOwdmDIBiTYma6ueAZEfcQp2nd605eT6PlALLbDYqXblI4eb1FXAFUqSUGyMqRbB4uCF3ji9m6tfSXHdK7ObA87QplTgAtx27HNDhdhHlNNWksQRSkmExZwET3GiQSYWNPAgsEObIHO4uSguqFxXElgUCcfRgWXBRyqApnGEeU5279Lq7LA9Bk8Q1ReRehWhsIcEp31LQHcr9YXnvCx3",
                "Content-Type: application/json",
                //"Signature: ".$signature
                "Signature: ".$signature,
                "user-agent: ".$user_agent
                //"user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/655.1.15 (KHTML, like Gecko) Version/16.4 Mobile/148 Safari/04.1"
            ),
            //CURLOPT_USERAGENT => "Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/655.1.15 (KHTML, like Gecko) Version/16.4 Mobile/148 Safari/04.1"
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function aifory_fin(){
        $this->log->write("======= aifory_fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function bank_form(){
        if(!isset($this->request->get['order_id'])){exit;}
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);
        $amount = number_format($order_info['total'], 2, '.', '');
        //echo (int)$amount;
        $sign = md5($this->request->get['order_id']."100221"."f2Y7eWhpSV2W");

        $xml="<TKKPG>
<Request>
<Operation>CreateOrder</Operation>
<Language>RU</Language>
<Order>
<Merchant>100221</Merchant>
<OrderId>".$this->request->get['order_id']."</OrderId>
<Articul>30</Articul>
<Amount>".(int)($amount*100)."</Amount>
<Currency>810</Currency>
<Description>Оплата заказа #".$this->request->get['order_id']."</Description>
<ApproveURL>".$this->url->link('checkout/success', '', true)."</ApproveURL>
<DeclineURL>".$this->url->link('checkout/err/decline', '', true)."</DeclineURL>
<CancelURL>".$this->url->link('checkout/err/cancel', '', true)."</CancelURL>
<AddParams>
<phone>".$order_info['telephone']."</phone>
<SenderName>galaxy-game</SenderName>
<Sign>".$sign."</Sign>
</AddParams>
<Fee>0</Fee>
</Order>
</Request>
</TKKPG>";

    $data['url']='https://acquiring.dc.tj/pay/form.php';
    $data['xml']=$xml;

    $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
    $this->response->setOutput($this->load->view('/extension/payment/bank_form', $data));

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

    private function xpay_get(){
        $curl = curl_init();

        curl_setopt_array($curl, [
          //CURLOPT_URL => "https://xpayfree.dev/api/h2h/order",    
          CURLOPT_URL => "https://xpayfree.dev/api/payment-gateways",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            "Access-Token: e8bnfk62cqtbfz5tknio8kxr3xjo3rao",
            "Authorization: Bearer e8bnfk62cqtbfz5tknio8kxr3xjo3rao",
            "Content-Type: application/json",
            "Accept: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        $result = json_decode($response);
        $this->log->write($response);
        return $result;
    }
}
