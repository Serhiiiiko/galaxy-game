<?php

class ControllerExtensionPaymentAntarex extends Controller {

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
		
	
		$order_id = $this->request->get['order_id'];
		$order_info = $this->db->query("SELECT * FROM oc_order WHERE order_id = '".$order_id."'")->row;
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://antrpay.com/api/api/repayment/create_payment_p2p',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
  "clientOrderID": "'.$order_id.'",
  "payerID": "'.$this->customer->getId().'",
  "sum": '.$order_info['total'].',
  "ttl": 3600,
  "walletID": 1,
  "message": "Order: '.$order_id.'",
  "webhookUrl": "https://nord-gaming.ru/index.php?route=extension/payment/antarex/webhook&order_id='.$order_id.'",
  "redirect": {
    "successURL": "https://nord-gaming.ru/index.php?route=extension/payment/antarex/success&order_id='.$order_id.'",
    "failURL": "https://nord-gaming.ru/index.php?route=extension/payment/antarex/failed&order_id='.$order_id.'"
  }
}',
  CURLOPT_HTTPHEADER => array(
    'api-key: 3DHMDJQF0loCelj8fH6CSAKq29tn117bxIw8WLQ5Hyt1kCfia4IN3bgW7miUlhKcuxHmtXe9XBhExDccQaSnxNmIGNdm9AgBxJfLJFvSamQ5fNyvlW3UY0aCpSino2y4lt1WbNxlwT7KqYidBsvmElKnWHi3Hd3BPCd4bZNjReEUAIom9FskmSAcHz7XEsimSZuboHua4uyZgyrdbJ2NK0GBtCn1S38YXUlbKmYtylIBY87dzZJpls9AUo9kWcmw',
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
$res = json_decode($response, true);
$this->response->redirect($res['link']);
// echo $response;
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
