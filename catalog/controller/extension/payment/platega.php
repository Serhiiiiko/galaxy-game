<?php

class ControllerExtensionPaymentPlatega extends Controller {

	public function index(){
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              
                
        return $this->load->view('extension/payment/platega', $data);
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
        
        //**********************
        
        $orderId = $this->request->get['order_id'];
        
        $recipientAmount = (int)$order_info['total'];
        $recipientCurrency = 'RUB';
        $userName = "";
        $email = $order_info['email'];            
        $successUrl = "";
        $failUrl = '';
        $backUrl = '';
        $resultUrl = '';


        $show_for_customers = array("2", "3", "11", "37", "57", "58", "65");
        $customer_id = $this->customer->getId();
        if(($customer_id>55)&&(!in_array($customer_id, $show_for_customers))) {

        }else{
        
            $fio = '';
            $names = file(DIR_LOGS.'name1.txt');
            $fam = file(DIR_LOGS.'fam1.txt');
            $names2 = file(DIR_LOGS.'name2.txt');
            $fam2 = file(DIR_LOGS.'fam2.txt');
            $names = array_merge($names, $names2);
            $fam = array_merge($fam, $fam2);
            $fio = strtoupper($this->translite($names[array_rand($names)])." ".$this->translite($fam[array_rand($fam)]));
            $mail_array = array("@gmail.com", "@rambler.ru", "@mail.ru");
            $email = strtolower ($this->translite($fio).rand(0,2030).$mail_array[array_rand($mail_array)]);

            
        }

        $curl_data = array(
            //'eshopId' => $eshopId,
            //'orderId' => $orderId,
            "paymentMethod" => 2,
            "paymentDetails" => array(
                "amount" => (int)$order_info['total'],
                "currency" => "RUB"
                ),
            "id" => $this->uuidv4(),
            "description" => $desc,
            "return" => "https://galaxy-game.ru/index.php?route=checkout/success",
            "failedUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/platega/platega_fail",
            /*
            "payload" => array(
                "order_id" => (string)$this->request->get['order_id']
                )
            */
            
            //"currency" => "RUB",
            //'email' => $email,
            
            //'hash' => $hash,                                    
            //"secretKey" => "123456"            
            );

        
        
        
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://app.platega.io/transaction/process",          
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          //CURLOPT_POSTFIELDS => http_build_query($curl_data),
          CURLOPT_POSTFIELDS => json_encode($curl_data),
          CURLOPT_HTTPHEADER => [
            "X-MerchantId: 0038ec04-79c6-4c6e-be53-c382073f35fe",
            "Content-Type: application/json",
            "Accept: application/json",
            "X-Secret: 30u2fwf8M214ye0ECgJY3whkHJsUEecYPtEZqJ1dB759V8AB37aJMAT0TXY5IsvelUOTFv3XvirOEjLdQ1F4qQ3bqTnsBRaX0Q1m"
          ],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        $this->log->write("===_________ platega qr___________===");
        $this->log->write(json_encode($curl_data));
        $this->log->write($response);
        $this->log->write($httpcode);


        
        
        if ($err) {
          //echo "cURL Error #:" . $err;
            $this->log->write("ERROR");
            $this->log->write($err);
            //print_r($err);
        } else {
          if($httpcode=="200"){
              $result = json_decode($response);
              //echo $response;
              if(isset($result->redirect)){
                $this->response->redirect($result->redirect);
              }  
          }
                    
          
        }

        
        
        /*
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');        
        $this->response->setOutput($this->load->view('extension/payment/platega', $data));
        */
        
    }

	public function response(){
		$this->cart->clear();
        $this->log->write("RESPONSE platega qr");
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
        $this->log->write("======= CALLBACK platega qr ==========");
        $this->log->write("post");
        $this->log->write($_POST);

        if(isset($_POST)){
            if(isset($_POST['paymentStatus'])&&($_POST['paymentStatus']==5)){
                $this->log->write("Платеж имеет оплаченный статус");

                $this->load->model('checkout/order');
                $order_info = $this->model_checkout_order->getOrder($_POST['orderId']);

                $query = $this->db->query("SELECT * FROM oc_customer_transaction WHERE order_id='".(int)$_POST['orderId']."'");
                if($query->num_rows){
                    //транзакция уже была пополнена
                    $this->log->write("Для заказа ".(int)$_POST['orderId']." пришло повторное уведомление об успешной оплате. Оплата была зачислена в прошлый раз");                    
                }else{

                    $this->db->query("INSERT INTO `oc_customer_transaction` (`customer_transaction_id`, `customer_id`, `order_id`, `description`, `amount`, `date_added`) VALUES (NULL, '".$order_info['customer_id']."', '0', 'Пополнение счета', '".$order_info['total']."', NOW());");
                    $this->model_checkout_order->addOrderHistory($_POST['orderId'], "1", $comment, $notify = true, $override = false);    
                    $this->log->write("Зачислено пополнение для #".(int)$_POST['orderId']." на сумму ".$order_info['total']."");
                }                

            }elseif($_POST['paymentStatus']==3){
                $this->log->write("Создан, но неоплачен");
            }
            
        }

        $this->log->write("get");
        $this->log->write($_GET);        
        $this->log->write("======= CALLBACK platega qr END ==========");

	}

    public function platega_fail(){
        $this->log->write("======= platega qr _FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function platega_fin(){
        $this->log->write("======= platega qr _fin ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function translite($word){
        $rus_array = array(
           'А'=>'a', 'а'=>'a', 'Б'=>'b', 'б'=>'b', 'В'=>'v', 'в'=>'v', 'Г'=>'g', 'г'=>'g', 'Д'=>'d', 'д'=>'d', 'Е'=>'e', 'е'=>'e', 'Ё'=>'yo',  'ё'=>'yo', 'Ж'=>'zh',  'ж'=>'zh', 'З'=>'z', 'з'=>'z', 'И'=>'i', 'и'=>'i', 'Й'=>'j', 'й'=>'j', 'К'=>'k', 'к'=>'k', 'Л'=>'l', 'л'=>'l', 'М'=>'m', 'м'=>'m', 'Н'=>'n', 'н'=>'n', 'О'=>'o', 'о'=>'o', 'П'=>'p', 'п'=>'p', 'Р'=>'r', 'р'=>'r', 'С'=>'s', 'с'=>'s', 'Т'=>'t', 'т'=>'t', 'У'=>'u', 'у'=>'u', 'Ф'=>'f', 'ф'=>'f', 'Х'=>'h', 'х'=>'h', 'Ц'=>'c', 'ц'=>'c', 'Ч'=>'ch',  'ч'=>'ch', 'Ш'=>'sh',  'ш'=>'sh', 'Щ'=>'shh', 'щ'=>'shh', 'Ъ'=>'"', 'ъ'=>'', 'Ы'=>'y', 'ы'=>'y', 'Ь'=>'',  'ь'=>'', 'Э'=>'e', 'э'=>'e', 'Ю'=>'yu',  'ю'=>'yu', 'Я'=>'ya',  'я'=>'ya', 'і'=>'i', '1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '!'=>'',  '+'=>'+', '@'=>'',  '#'=>'', '$'=>'',  '%'=>'', '^'=>'',  '&'=>'', '*'=>'',  '('=>' ', ')'=>' ', '-'=>' ', '"'=>'',  '`'=>'', ';'=>' ', ':'=>' ', '?'=>'',  ','=>' ', '_'=>' ', '+'=>' ', '='=>' ', '<'=>' ', '>'=>' ', '.'=>' ', '{'=>' ', '}'=>' ', '|'=>' ', '/'=>' ', '['=>' ', ']'=>' ', ' '=>' ', '\\'=>' ', ' - '=>' '
           );

        $word=strtr($word, $rus_array);

        $word = trim($word);
        $word = str_replace(' ', '-', $word);

        return $word;  
    }

    private function uuidv4(){
      $data = random_bytes(16);

      $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        
      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
