<?php

class ControllerExtensionPaymentEnot extends Controller {

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
		$this->language->load('extension/payment/enot');
		$order_id = $this->request->get['order_id'];
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);

		 $amount = number_format($order_info['total'], 2, '.', '');
		
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



		$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.enot.io/invoice/create',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
 "amount": '.$amount.',
 "order_id": "'.$order_id.'",
 "email": "'.$order_info['email'].'",
 "currency": "RUB",
 "custom_fields": "{\\"orderId\\": \\"'.$order_id.'\\"}",
 "comment": "Заказ №'.$order_id.'",
 "fail_url": "'.$this->url->link('checkout/error', '', true).'",
 "success_url": "https://nord-gaming.ru/index.php?route=extension/payment/enot/response&order_id='.$order_id.'",
 "hook_url": "https://nord-gaming.ru/index.php?route=extension/payment/enot/callback&order_id='.$order_id.'",
 "shop_id": "239a433b-d6d1-4e02-80e7-bb6f2878bb33",
 "expire": 300,
 "include_service": [
  "card"
 ],
 "exclude_service": [
  "qiwi"
 ]
}',
  CURLOPT_HTTPHEADER => array(
    'x-api-key: 30634261288d2e639c27196eff498b81237027b7',
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
//print_r(json_decode($response, true));
//echo json_decode($response, true)['data']['url'];
$this->response->redirect(json_decode($response, true)['data']['url']);


		$this->load->model('checkout/order');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/enot')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/enot', $data);
		} else {
			return $this->load->view('/extension/payment/enot', $data);
		}
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
}
