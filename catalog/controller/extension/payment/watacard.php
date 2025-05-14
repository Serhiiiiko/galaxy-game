<?php

class ControllerExtensionPaymentWatacard extends Controller {

	public function index(){
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              
                
        return $this->load->view('extension/payment/wata', $data);
	}

    public function pay(){
        
        $secret_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJQdWJsaWNJZCI6IjNhMTk0NzU4LTg3N2UtMDYzZi05NWM2LTllYjM5YjE4YzYzMSIsIlRva2VuVmVyc2lvbiI6IjEiLCJleHAiOjE3NzcwMzU4NTUsImlzcyI6Imh0dHBzOi8vYXBpLndhdGEucHJvIiwiYXVkIjoiaHR0cHM6Ly9hcGkud2F0YS5wcm8vYXBpL2gyaCJ9.tkXBrMYab4QeuLz15VuBNVG6phlabpqcwB5jbLRGOME";
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
            "merchantUrl" => "https://nord-gaming.ru/index.php?route=extension/payment/wata/callback",
            "webhookUrl" => "https://nord-gaming.ru/index.php?route=extension/payment/wata/callback",
            "callbackURL" => "https://nord-gaming.ru/index.php?route=extension/payment/wata/callback",
            "redirect" => array(
                "successURL" => "https://nord-gaming.ru/index.php?route=extension/payment/wata/wata_fin",
                "failURL" => "https://nord-gaming.ru/index.php?route=extension/payment/wata/wata_fail"
            )
        );
        */
        
        
        $curl_data = array(
            "amount" => (int)$order_info['total'],
            "currency" => "RUB",
            "orderId" => "#".$order_id,
            "successRedirectUrl" => "https://galaxy-game.ru/index.php?route=checkout/success"
            );
        
        
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.wata.pro/api/h2h/links",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            //"Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJQdWJsaWNJZCI6IjNhMTZmNzUwLTUyN2YtNjZmMi05MzQ2LTFlZTBlMWRjN2FkNyIsImV4cCI6MTc2NjI1NTg1NywiaXNzIjoiaHR0cHM6Ly9hcGkud2F0YS5wcm8iLCJhdWQiOiJodHRwczovL2FwaS53YXRhLnByby9hcGkvaDJoIn0.Kq2J9d_EpDVSq7-vDiFfUziwnQfQsS_kDXxkYY6d2G0",
            "Authorization: Bearer ".$secret_key,
            "Content-Type: application/json"
          ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        $this->log->write("===_________ wata card ___________===");
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
          if(isset($result->url)){
            $this->response->redirect($result->url);
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
        $this->log->write("RESPONSE watacard");
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
        $this->log->write("======= CALLBACK watacard ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
        $this->log->write("======= CALLBACK watacard END ==========");

	}

    public function wata_fail(){
        $this->log->write("======= watacard_FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function wata_fin(){
        $this->log->write("======= watacard_fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }
}
