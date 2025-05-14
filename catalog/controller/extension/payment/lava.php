<?php

class ControllerExtensionPaymentLava extends Controller {

	public function index() 
	{
		$this->language->load('extension/payment/lava');
		$order_id = $this->session->data['order_id'];
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$merchant_id = $this->config->get('payment_lava_merchant_id');
		$secret_word = $this->config->get('payment_lava_secret_word');
		$amount = number_format($order_info['total'], 2, '.', '');
		
		$desc = $this->language->get('order_description') . $order_id;

		$form_data = [
			'm' => $merchant_id,
			'oa' => $amount,
			'cr' => $this->session->data['currency'],
			'o' => $order_id,
			'c' => $desc,
			's' => md5($merchant_id.':'.$amount.':'.$secret_word.':'.$order_id)
		];
		
		$data['url'] = 'https://lava.io/_/pay';
		$data['form_data'] = $form_data;

		$this->load->model('checkout/order');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/lava')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/lava', $data);
		} else {
			return $this->load->view('/extension/payment/lava', $data);
		}
	}



	public function pay() 
	{
		$order_id = $this->request->get['order_id'];
		$order_info = $this->db->query("SELECT * FROM oc_order WHERE order_id = '".$order_id."'")->row;
		
		$data = [
				"comment"=>"Заказ №".$order_id,
				"expire"=>300,
				"failUrl"=>"https://galaxy-game.ru/index.php?route=extension/payment/lava/failed&order_id=".$order_id,
				"hookUrl "=>"https://galaxy-game.ru/index.php?route=extension/payment/lava/webhook&order_id=".$order_id,
				"includeService"=>["card","sbp","qiwi"],
				"orderId"=>$order_id,
				"shopId"=>"c63fe0d1-3615-46a8-a737-6db5f6958e2d",
				"successUrl"=>"https://galaxy-game.ru/index.php?route=extension/payment/lava/success&order_id=".$order_id,
				"sum"=>$order_info['total']
		];
		
		
		$secretKey = '11171ec8846a058f40200f74f471842326b3f6fc';
		
		$data = json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		
		$signature = hash_hmac('sha256', $data, $secretKey);
		
		$curl = curl_init();
		
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.lava.ru/business/invoice/create', 
			CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_FOLLOWLOCATION => true, 
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, 
			CURLOPT_CUSTOMREQUEST => 'POST', 
			CURLOPT_POSTFIELDS => $data, 
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json', 'Content-Type: application/json', 'Signature: ' . $signature
				), ));
		
				$response = curl_exec($curl);
		
				curl_close($curl);
		// $secretKey = 'ef3a0f17bd6987a80a46a582bb4c25cf6fd6d3d5';
		
		// $data = [
		// 	"comment"=>"test",
		// 	"customFields"=>"test",
		// 	"expire"=>300,
		// 	"failUrl"=>"https://lava.ru",
		// 	"hookUrl "=>"https://lava.ru",
		// 	"includeService"=>["card","sbp","qiwi"],
		// 	"orderId"=>"123123123",
		// 	"shopId"=>"f5683849-a786-4e7e-869e-334b8ec6ee7a",
		// 	"successUrl"=>"https://lava.ru",
		// 	"sum"=>1
		// ];
		
		// $data = json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		
		// $signature = hash_hmac('sha256', $data, $secretKey);

		// $ch = curl_init();


		// $data = [
		// 	"comment"=>"test",
		// 	"customFields"=>"test",
		// 	"expire"=>300,
		// 	"failUrl"=>"https://lava.ru",
		// 	"hookUrl "=>"https://lava.ru",
		// 	"includeService"=>["card","sbp","qiwi"],
		// 	"orderId"=>"123123123",
		// 	"shopId"=>"f5683849-a786-4e7e-869e-334b8ec6ee7a",
		// 	"successUrl"=>"https://lava.ru",
		// 	"sum"=>1,
		// 	"signature" => $signature
		// ];

		// $jsonData = json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

		// curl_setopt($ch, CURLOPT_URL, "https://api.lava.ru/business/invoice/create");
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		// curl_setopt($ch, CURLOPT_POST, 1);

		// $headers = array(
		// 	"Content-Type: application/json",
		// 	"Accept: application/json"
		// );
		// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// $result = curl_exec($ch);

		// if (curl_errno($ch)) {
		// 	echo "Error: " . curl_error($ch);
		// }

		// curl_close($ch);
        $this->log->write("====== LAVA =======");
        $this->log->write("send");
        $this->log->write($data);
        $res = json_decode($response, true);
        $this->log->write("response");
        $this->log->write($res);
        //echo 111;
        //print_r($res);
		$this->response->redirect($res['data']['url']);
		
	}

	public function response(){

        $this->log->write("====== LAVA RESPONSE =======");
        $this->log->write("post");
        $this->log->write($this->request->post);
        $this->log->write("get");
        $this->log->write($this->request->get);

		$this->load->model('checkout/order');

		if (isset($this->request->get['fail']) AND $this->request->get['fail']) {
			$this->response->redirect($this->url->link('checkout/confirm', '', true));
		} else {
			$this->cart->clear();
			$this->response->redirect($this->url->link('checkout/success', '', true));
		}
	}

	public function callback(){

        $this->log->write("====== LAVA callback =======");
        $this->log->write("post");
        $this->log->write($this->request->post);
        $this->log->write("get");
        $this->log->write($this->request->get);

        $body = file_get_contents('php://input');
        $in = json_decode($body, true);
        $this->log->write("file");
        $this->log->write($in);        


		$order_id = isset($this->request->post['merchant_id'])
            ? (int) $this->request->post['merchant_id']
            : 0;

		if (!$order_id) {
            exit;
        }

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$amount = number_format($order_info['total'], 2, '.', '');

		$merchant_id = $this->config->get('payment_lava_merchant_id');
		$secret_word2 = $this->config->get('payment_lava_secret_word2');

		$sign2 = isset($this->request->post['sign_2'])
            ? $this->request->post['sign_2']
            : '';

        if(!$sign2) {
            exit;
        }

		$check_sign2 = md5($merchant_id.':'.$amount.':'.$secret_word2.':'.$order_id);

		if(hash_equals($sign2, $check_sign2)) {
			$comment = 'lava transaction id: ' . $this->request->post['intid'];
			$this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_lava_order_status_id'), $comment, $notify = true, $override = false);
		}
	}

	public function success() 
	{
        $this->log->write("====== LAVA success =======");
        $this->log->write("post");
        $this->log->write($this->request->post);
        $this->log->write("get");
        $this->log->write($this->request->get);
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_lava_order_status_id'), $comment, $notify = true, $override = false);
		//echo 'Оплата прошла успешно, вернитесь в личный кабинет для покупки';
	}

	public function failed() 
	{
        $this->log->write("====== LAVA failed =======");
        $this->log->write("post");
        $this->log->write($this->request->post);
        $this->log->write("get");
        $this->log->write($this->request->get);
		echo 'Оплата не прошла';
	}

	public function webhook()
	{	
        $this->log->write("====== LAVA webhook =======");
        $this->log->write("post");
        $this->log->write($this->request->post);
        $this->log->write("get");
        $this->log->write($this->request->get);

		$order_id = $this->request->get['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$comment = 'lava transaction id: ' . $this->request->post['intid'];
		$this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
		$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_lava_order_status_id'), $comment, $notify = true, $override = false);
	
	}
}
