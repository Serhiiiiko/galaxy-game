<?php

class ControllerExtensionPaymentPayin extends Controller {

	public function index(){
		//$this->language->load('extension/payment/payin');
		$order_id = $this->session->data['order_id'];
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$api_key = $this->config->get('payment_payin_key');		
		$amount = (int)$order_info['total'];
		
		$desc = $this->language->get('order_description') . $order_id;		

        $curl_data = array(            
                "amount" => $amount,
                "merchant_order_id" => $order_id,
                "email" => $order_info['email'],
                "country" => "RU",                                                
                "fail_url" => $this->url->link('checkout/error', '', true),
                "success_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/response&order_id=".$order_id."",
                "notice_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/callback&order_id=".$order_id."",                
                "api_key" => $this->config->get('payment_payin_key'),
                "use_card_payment" => "1"
            );
		
		$data['url'] = 'https://lk.payin-payout.net/api/shop';
		$data['form_data'] = $curl_data;
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payin')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payin', $data);
		} else {
			return $this->load->view('/extension/payment/payin', $data);
		}
	}

    public function pay(){
        //$this->language->load('extension/payment/payin');
        //$order_id = $this->session->data['order_id'];
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $api_key = $this->config->get('payment_payin_key');       
        $amount = (int)$order_info['total'];
        
        $desc = $this->language->get('order_description') . $order_id;      

        /*
        $curl_data = array(            
                "amount" => $amount,
                "merchant_order_id" => $order_id,
                "email" => $order_info['email'],
                "country" => "RU",                                                
                "fail_url" => $this->url->link('checkout/error', '', true),
                "success_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/response&order_id=".$order_id."",
                "notice_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/callback&order_id=".$order_id."",                
                "api_key" => $this->config->get('payment_payin_key'),
                "use_card_payment" => "1"
            );
        
        $data['url'] = 'https://lk.payin-payout.net/api/shop';
        */
        $data['form_data'] = $curl_data;

        $this->response->redirect($this->url->link('account/account/payin', '&order_id='.$order_id , true));
        
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payin')) {
            return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payin', $data);
        } else {
            return $this->load->view('/extension/payment/payin', $data);
        }
    }

	public function pay2(){
		//$this->language->load('extension/payment/payin');
		$order_id = $this->request->get['order_id'];
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);

		$amount = (int)$order_info['total'];
		
		// $desc = $this->language->get('order_description') . $order_id;

		// $form_data = [
		// 	'm' => $merchant_id,
		// 	'oa' => $amount,
		// 	'cr' => $this->session->data['currency'],
		// 	'o' => $order_id,
		// 	'c' => $desc,
		// 	's' => md5($merchant_id.':'.$amount.':'.$secret_word.':'.$order_id)
		// ];
		
		// $data['url'] = 'https://enot.io/_/pay';
		// $data['form_data'] = $form_data;

		// $query_params = http_build_query($form_data);
		// $url = 'https://enot.io/_/pay?' . $query_params;
		
		// $this->response->redirect($url);
        //echo $amount."<br>";
        $curl_data = array(            
                "amount" => $amount,
                "merchant_order_id" => $order_id,
                "email" => $order_info['email'],
                "country" => "RU",                                                
                "fail_url" => $this->url->link('checkout/error', '', true),
                "success_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/response&order_id=".$order_id."",
                "notice_url" => "https://nord-gaming.ru/index.php?route=extension/payment/payin/callback&order_id=".$order_id."",                
                "api_key" => $this->config->get('payment_payin_key'),
                "use_card_payment" => "1"
            );

		$curl = curl_init();

        curl_setopt_array($curl, array(
            //CURLOPT_URL => 'https://morepaymentss.club/api/request/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($curl_data),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        //print_r(json_decode($response, true));
        $this->log->write("PAY2");
        $this->log->write($response);

        //echo json_decode($response, true)['data']['url'];
        $this->response->redirect(json_decode($response, true)['data']['url']);


		$this->load->model('checkout/order');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/payin')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/payin', $data);
		} else {
			return $this->load->view('/extension/payment/payin', $data);
		}
	}

	public function response(){
		$this->cart->clear();
        $this->log->write("RESPONSE EPAY");
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
        $this->log->write("CALLBACK epay");
        $this->log->write($this->request->post);

		$order_id = isset($this->request->post['merchant_order_id'])
            ? (int) $this->request->post['merchant_order_id']
            : 0;

		if (!$order_id) {
            exit;
        }

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$amount = (int)$order_info['total'];

		$api_key = $this->config->get('payment_payin_key');				

        if($this->request->post['status']=="successful_payment"){
            $comment = 'PAYIN transaction id: ' . $this->request->post['transaction_id'];
            $this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_payin_order_status_id'), $comment, $notify = false, $override = false);
        }
	}
}
