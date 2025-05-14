<?php

class ControllerExtensionPaymentNirvanaPay extends Controller {

	public function index(){
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              
        

        $secret = 'b2ccff92-b69b-494c-b02d-62b1380971f1';
        $public = 'a8501ae9-50c0-4cd7-87d8-6c1b03cdc3aa';
           
        
        $curl_data = array();
        $sign = hash_hmac('sha512',json_encode($curl_data), $secret);

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => "https://f.nirvanapay.pro/api/currencies",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          CURLOPT_HEADER => 1,
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            "ApiPublic: ".$public,
            "Signature: ".$sign,
            "Content-Type: application/json",
            "Accept: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);

        $err = curl_error($curl);

        curl_close($curl);   
        print_r($response);
        //return $this->load->view('extension/payment/nirvanapay', $data);
	}

    public function pay(){
        
        $secret = 'b2ccff92-b69b-494c-b02d-62b1380971f1';
        $public = 'a8501ae9-50c0-4cd7-87d8-6c1b03cdc3aa';
        //$order_id = $this->session->data['order_id'];
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = md5(rand(323, 8777));

        $desc = "Пополнение баланса #" . $order_id;
        

        $curl_data = array(
            
            "amount" => (int)$order_info['total'],
            "redirectURL" => "https://galaxy-game.ru/index.php?route=checkout/success",
            "header" => "Galaxy Game",
            "callbackURL" => "https://galaxy-game.ru/index.php?route=extension/payment/nirvanapay/callback",
            //"externalID" => "#".$order_id,
            "externalID" => "".$order_id,
            "currency" => "RUB",
            "userInfo" => array(
                "ip" => "string",
                "userAgent" => "string",
                "email" => "string",
                "id" => "string"
                }
            //"description" => $desc,
            //"merchant_id" => "c74f2c7b-f838-4bad-b3bc-85806675ba95",
            
            
            //"fail_url" => "https://galaxy-game.ru/index.php?route=extension/payment/nirvanapay/nirvanapay_fail"
            );
        

        //$sign = hash_hmac('sha512',json_encode($curl_data,JSON_UNESCAPED_SLASHES), $secret);
        $sign = hash_hmac('sha512',json_encode($curl_data), $secret);
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://f.nirvanapay.pro/api/order",          
          //CURLOPT_URL => "https://f.nirvanapay.pro/api/v2/order",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          //CURLOPT_HEADER => 1,
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            "ApiPublic: ".$public,
            "Signature: ".$sign,
            "Content-Type: application/json",
            "Accept: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);

        $err = curl_error($curl);

        curl_close($curl);

        $this->log->write("===_________ nirvanapay ___________===");
        $this->log->write(json_encode($curl_data));
        $this->log->write($response);
        $this->log->write($httpcode);
        //$this->log->write($header);
        $this->log->write("===_________ nirvanapay END___________===");


        
        
        if ($err) {
          //echo "cURL Error #:" . $err;
            $this->log->write("ERROR");
            $this->log->write($err);
            //print_r($err);
        } else {
         
          $result = json_decode($response);
          //echo $response;
          if(isset($result->redirectURL)){
            $this->response->redirect($result->redirectURL);
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
        $this->log->write("RESPONSE nirvanapay");
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
        $this->log->write("======= CALLBACK nirvanapay ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
        $this->log->write("file");
        $body = file_get_contents('php://input');
        $in = json_decode($body, true);
        $this->log->write($in);
        $this->log->write("======= CALLBACK nirvanapay END ==========");

	}

    public function nirvanapay_fail(){
        $this->log->write("======= nirvanapay_FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function nirvanapay_fin(){
        $this->log->write("======= nirvanapay_fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }
}
