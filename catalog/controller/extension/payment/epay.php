<?php
class ControllerExtensionPaymentEpay extends Controller {
	public function index() {
		$gateway_url = 'https://api.epay.com/paymentApi/merReceive';
		$data['button_confirm'] = $this->language->get('button_confirm');
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $out_trade_no = trim($order_info['order_id']);
		$subject = trim($this->config->get('config_name'));
		$total_amount =0.01;// trim($this->currency->format($order_info['total'], $this->config->get('payment_epay_units'), '', false));
		$body = '';
        $arr=array(
            'PAYEE_ACCOUNT'=>$this->config->get('payment_epay_account'),
            'PAYEE_NAME'=>$subject,
            'PAYMENT_AMOUNT'=>sprintf("%.2f",$total_amount),
            'PAYMENT_UNITS'=>$this->config->get('payment_epay_units'),
            'PAYMENT_ID'=>$out_trade_no,
            'STATUS_URL'=>HTTPS_SERVER ."payment_callback/epay",
            'PAYMENT_URL'=>HTTPS_SERVER ."payment_callback/epay",
            'NOPAYMENT_URL'=>HTTPS_SERVER ."payment_callback/epay",
            'SUGGESTED_MEMO'=>$body,
            'INTERFACE_LANGUAGE'=>$this->config->get('payment_epay_lang')
        );
        $arr['V2_HASH']=md5($arr['PAYEE_ACCOUNT'].':'.$arr['PAYMENT_AMOUNT'].':'.$arr['PAYMENT_UNITS'].':'.$this->config->get('payment_epay_key'));
        $data['form_params']=$arr;
        $data['action']=$gateway_url;
		return $this->load->view('extension/payment/epay', $data);
	}


  
	public function callback() {
		 $d=$_POST;
		 // check sign
         $sign=md5($d['PAYMENT_ID'].':'.$d['ORDER_NUM'].':'.$d['PAYEE_ACCOUNT'].':'.$d['PAYER_ACCOUNT'].':'.$d['STATUS'].':'.$d['TIMESTAMPGMT'].':'.$this->config->get('payment_epay_key'));
        if($sign!= $d['V2_HASH2']){
            $this->log->write('Epay check failed');
            $this->log->write('POST' . var_export($_POST,true));
            echo "fail"; 
            return;
        }
        $out_trade_no = $d['ORDER_NUM'];
        $trade_no = $d['PAYMENT_ID'];
        $trade_status = $d['STATUS'];
        
        if ($trade_status == 2) {
            $this->load->model('checkout/order');
		    $this->model_checkout_order->addOrderHistory($trade_no, $this->config->get('payment_alipay_order_status_id'));
            echo "success";
            return; 
        }else{
           $this->log->write('Epay not pay');
           $this->log->write('POST' . var_export($_POST,true));
           echo "fail";
           return;   
        } 
		
	}
}