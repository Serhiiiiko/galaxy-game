<?php

class ControllerExtensionPaymentBankForm extends Controller {

	public function index() 
	{
        /*
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
        */
	}



	public function pay() 
	{  
        
        //echo 'bank test qr<br>';
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
<Description>Пополнение счета #".$this->request->get['order_id']."</Description>
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
    
    $this->log->write(" ====================== BANK FORM INFO ====================");
    $this->log->write($xml);

    $this->response->redirect($this->url->link('account/account/bank_form', 'order_id='.$this->request->get['order_id'], true));
    /*
    $opts = array(
        'http'=>array(
            'method'=>'POST',            
            'header'=>"Content-Type: text/xml\r\n" ,
            'content'=>$xml
        )
    );

    $context = stream_context_create($opts);

    $url = "https://acquiring.dc.tj/pay/form.php";
    $fp = fopen($url, 'r', false, $context);
    fpassthru($fp);
    fclose($fp);

    $result=simplexml_load_string($fp);
    echo "<br>";
    print_r($result);
    echo "<br>";
    */

    /*
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://acquiring.dc.tj/pay/form.php',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array('Request' => $xml),
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    */

    //$this->log->write($result);
    $this->log->write(" ====================== BANK FORM END  ====================");

    //var_dump($result);
    
    //echo $result;
    $data['url']='https://acquiring.dc.tj/pay/form.php';
    $data['xml']=$xml;
    return $this->load->view('/extension/payment/bank_form', $data);

    
    /*
    if((string)$result->status=='200'){
        echo "Статус подходит, переходим к оплате: ".$result->URL3DS;
    }else{
        echo "Статус не подошел: ".$result->status.". Ошибка: ".$result->description;
    }
    */
     
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
        /*
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
        */
	}
}
