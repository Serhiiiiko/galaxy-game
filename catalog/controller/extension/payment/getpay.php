<?php

class ControllerExtensionPaymentGetpay extends Controller {

	public function index(){
		$order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = rand(323, 8777);
        
        $desc = $this->language->get('order_description') . $order_id;              
                
        return $this->load->view('extension/payment/getpay', $data);
	}

    public function pay(){
                
        $order_id = $this->request->get['order_id'];
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        
        $data['amount'] = (int)$order_info['total'];
        $data['order_id'] = $order_id;
        $data['user_id'] = md5(rand(323, 8777));

        $desc = "Пополнение баланса #" . $order_id;
        
        //**********************
        
        $order_id = $this->request->get['order_id'];      
        $email = $order_info['email'];            
      


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

        $url = "https://getpay.io/api/pay";
        $dataFields = array(
            "secret" => "3149-3da3f86d02d8b965802b2d6a2e16e256",
            "wallet" => "27378", // ID проекта
            "sum" => (int)$order_info['total'],
            "order" => $order_id,
            //"type" => "", // метод оплаты
            "resultUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/getpay/callback",
            "backUrl" => "https://galaxy-game.ru/index.php?route=checkout/success",
            "comment" => $desc
        );

        $result = json_decode(file_get_contents($url . "?" . http_build_query($dataFields)));
        /*
        $curl_data = array(
            "paymentMethod" => 2,
            "paymentDetails" => array(
                "amount" => (int)$order_info['total'],
                "currency" => "RUB"
                ),
            "id" => $this->uuidv4(),
            "description" => $desc,
            "return" => "https://galaxy-game.ru/index.php?route=checkout/success",
            "failedUrl" => "https://galaxy-game.ru/index.php?route=extension/payment/getpay/getpay_fail",                
            );        
        
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://getpay.io/api/pay",          
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
        */

        $this->log->write("===_________ getpay ___________===");
        $this->log->write(json_encode($dataFields));
        $this->log->write($result);
        //$this->log->write($response);
        //$this->log->write($httpcode);


        if($result->status == 'error') {
            $this->log->write($result->error);
        }else{
            $this->response->redirect($result->redirectUrl);
        }
        
        
    }

	public function response(){
		$this->cart->clear();
        $this->log->write("RESPONSE getpay");
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
        $this->log->write("======= CALLBACK getpay ==========");
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
        $this->log->write("======= CALLBACK getpay END ==========");

	}

    public function getpay_fail(){
        $this->log->write("======= getpay_FAIL ==========");
        $this->log->write("post");
        $this->log->write($_POST);
        $this->log->write("get");
        $this->log->write($_GET);
    }

    public function getpay_fin(){
        $this->log->write("======= getpay_fin ==========");
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
