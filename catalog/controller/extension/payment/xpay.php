<?php

class ControllerExtensionPaymentXpay extends Controller {

	public function index(){
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              

        /*
        $curl = curl_init();

        curl_setopt_array($curl, [
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
        print_r($response);
        */                
        //return $this->load->view('extension/payment/xpay', $data);
	}

    public function pay(){
        
        
        //$order_id = $this->session->data['order_id'];
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = md5(rand(323, 8777));

        $desc = "Пополнение баланса #" . $order_id;
        
        /*
        $curl_data = array(
            "clientOrderID" => $order_id,
            "payerID" => ''.$data['user_id'],
            //"clientIP" => "102.38.251.124",
            "clientIP" => $order_info['ip'],
            "amount" => (int)$order_info['total'],
            //"sum" => (int)$order_info['total'],
            "comment" => "Пополнение баланса #".$order_id,
            "expireAt" => 600,
            "currencyID" => 1,
            "merchantUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/callback",
            "webhookUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/callback",
            "callbackURL" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/callback",
            "redirect" => array(
                "successURL" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/xpay_fin",
                "failURL" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/xpay_fail"
            )
        );
        */

        $curl_data = array(
            "external_id" => "#".$order_id,
            "amount" => (int)$order_info['total'],
            "currency" => "rub",
            //"description" => $desc,
            "merchant_id" => "c74f2c7b-f838-4bad-b3bc-85806675ba95",
            "callback_url" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/callback",
            "success_url" => "https://galaxy-game.ru/index.php?route=checkout/success"
            //"fail_url" => "https://galaxy-game.ru/index.php?route=extension/payment/xpay/xpay_fail"
            );
        
        
        $curl = curl_init();

        curl_setopt_array($curl, [
          //CURLOPT_URL => "https://xpayfree.dev/api/h2h/order",    
          CURLOPT_URL => "https://xpayfree.dev/api/merchant/order",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
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

        $this->log->write("===_________ xpay ___________===");
        $this->log->write(json_encode($curl_data));
        $this->log->write($response);
        $this->log->write($httpcode);
        
        
        if ($err) {
          //echo "cURL Error #:" . $err;
            $this->log->write("ERROR");
            $this->log->write($err);
            //print_r($err);
        } else {
            $result = json_decode($response);
            //echo $response;
            if(isset($result->data->payment_link)){
                $this->response->redirect($result->data->payment_link);
            }else{
                echo $result->message;
            }
            //echo "Внимание! Оплатить можно только в течение 10 минут! <br><br>";
            //echo "Банк: ".$result->method_name."<br>Сумма: ".$result->amount."<br>Получатель: ".$result->holder_name."<br>".$result->holder_account;
        }

        
        
        /*
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');        
        $this->response->setOutput($this->load->view('extension/payment/antrpay', $data));
        */
        
    }

	public function response(){
		$this->cart->clear();
        $this->log->write("RESPONSE xpay");
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
        $this->log->write("======= CALLBACK xpay ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
        $this->log->write("file");
        $body = file_get_contents('php://input');
        $in = json_decode($body, true);
        $this->log->write($in);
        if($in['status']=='fail'){
            $this->log->write("Заказ ".$in["external_id"]." не оплачен");
        }
        if($in['status']=='success'){
            $this->log->write("Заказ ".$in["external_id"]." успешно оплачен");
        }
        $this->log->write("======= CALLBACK xpay END ==========");

	}

    public function xpay_fail(){
        $this->log->write("======= xpay_FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function xpay_fin(){
        $this->log->write("======= xpay_fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }
}
