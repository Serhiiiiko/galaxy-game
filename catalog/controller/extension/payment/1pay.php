<?php

class ControllerExtensionPayment1Pay extends Controller {

	public function index() 
	{
		$this->language->load('extension/payment/enot');
		$order_id = $this->session->data['order_id'];
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$merchant_id = $this->config->get('payment_enot_merchant_id');
		$secret_word = $this->config->get('payment_enot_secret_word');
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
		
		$data['url'] = 'https://enot.io/_/pay';
		$data['form_data'] = $form_data;

		$this->load->model('checkout/order');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/enot')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/enot', $data);
		} else {
			return $this->load->view('/extension/payment/enot', $data);
		}
	}



	public function pay() 
	{
		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, "https://api.1pay.uz/payin/getAvailablePs");
		// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer slJ9WBE5RdR9FSWUTewFcgw0RtfW6TXC'));
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// $result = curl_exec($ch);
		$oper_id = $this->request->get['order_id'];
		$order_info = $this->db->query("SELECT * FROM oc_order WHERE order_id = '".$oper_id."'")->row;
		$pass1 = 'BGtGReb^W!TOH@6X';
		$ch = curl_init();
		$paysystem_id = 15;
		$curr_id = 643;
		curl_setopt($ch, CURLOPT_URL, "https://api.1pay.uz/payin/create");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer sBtIEcTsSTgUovblVE0DgDQCgifwE2Se'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$sign = md5($curr_id.'-'.$paysystem_id.'-' . $oper_id. '-' . $order_info['total'] . '-' . $pass1);
		$postfields = 
			'currency='.$curr_id.'&'.
			'paysystem='.$paysystem_id.'&'.
			'user_id=1&'.
			'userId=1&'.
			'ip=88.169.177.13&'.
			'oper_id='.$oper_id.'&'.
			'amount='.$order_info['total'].'&'.
			'sign='.$sign;
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		$result = curl_exec($ch);

			

// $jsonData = json_encode($data);

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, "https://api.1pay.uz/v1/payment/invoices"); // Пример URL, проверьте документацию для точного URL
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$apiKey));
// curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

// $response = curl_exec($ch);
// curl_close($ch);

// $result = json_decode($response, true);
		$res = json_decode($result, true);
		$this->response->redirect($res['data']['url']);
		
		// print_r($result);
	}

	public function response() 
	{
		$this->load->model('checkout/order');

		if (isset($this->request->get['fail']) AND $this->request->get['fail']) {
			$this->response->redirect($this->url->link('checkout/confirm', '', true));
		} else {
			$this->cart->clear();
			$this->response->redirect($this->url->link('checkout/success', '', true));
		}
	}

	public function callback()
	{
		$order_id = isset($this->request->post['merchant_id'])
            ? (int) $this->request->post['merchant_id']
            : 0;

		if (!$order_id) {
            exit;
        }

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$amount = number_format($order_info['total'], 2, '.', '');

		$merchant_id = $this->config->get('payment_enot_merchant_id');
		$secret_word2 = $this->config->get('payment_enot_secret_word2');

		$sign2 = isset($this->request->post['sign_2'])
            ? $this->request->post['sign_2']
            : '';

        if(!$sign2) {
            exit;
        }

		$check_sign2 = md5($merchant_id.':'.$amount.':'.$secret_word2.':'.$order_id);

		if(hash_equals($sign2, $check_sign2)) {
			$comment = 'Enot transaction id: ' . $this->request->post['intid'];
			$this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_enot_order_status_id'), $comment, $notify = true, $override = false);
		}
	}

	public function success() 
	{
		echo 'Оплата прошла успешно, вернитесь в личный кабинет для покупки';
	}

	public function failed() 
	{
		echo 'Оплата не прошла';
	}

	public function webhook()
	{	
		$order_id = $this->request->get['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$comment = 'Enot transaction id: ' . $this->request->post['intid'];
		$this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
		$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_enot_order_status_id'), $comment, $notify = true, $override = false);
	
	}
}
