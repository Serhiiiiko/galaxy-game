<?php
class ControllerExtensionPaymentEpay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/epay');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_epay', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['payment_epay_account'])) {
			$data['error_payment_epay_account'] = $this->error['payment_epay_account'];
		} else {
			$data['error_payment_epay_account'] = '';
		}
		if (isset($this->error['payment_epay_key'])) {
			$data['error_payment_epay_key'] = $this->error['payment_epay_key'];
		} else {
			$data['error_payment_epay_key'] = '';
		}
		if (isset($this->error['payment_epay_units'])) {
			$data['error_payment_epay_units'] = $this->error['payment_epay_units'];
		} else {
			$data['error_payment_epay_units'] = '';
		}
        if (isset($this->error['payment_epay_lang'])) {
			$data['error_payment_epay_lang'] = $this->error['payment_epay_lang'];
		} else {
			$data['error_payment_epay_lang'] = '';
		}
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/epay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/epay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_epay_account'])) {
			$data['payment_epay_account'] = $this->request->post['payment_epay_account'];
		} else {
			$data['payment_epay_account'] = $this->config->get('payment_epay_account');
		}

		if (isset($this->request->post['payment_epay_key'])) {
			$data['payment_epay_key'] = $this->request->post['payment_epay_key'];
		} else {
			$data['payment_epay_key'] = $this->config->get('payment_epay_key');
		}
        $data['units_arr'] =array(
        	'USD'=>'USD',
        	'EUR'=>'EUR',
        	'HKD'=>'HKD',
        	'GBP'=>'GBP',
        	'JPY'=>'JPY',
        	'BTC'=>'BTC',
        	'ETH'=>'ETH',
        	'EOS'=>'EOS',
        	'BCH'=>'BCH',
        	'LTC'=>'LTC',
        	'XRP'=>'XRP',
        	'USDT'=>'USDT');
        $data['language']=array(
        	'en_us'=>'English',
        	'zh_cn'=> '中 文',
        	'ja_jp'=>'日 语',
        	'ko_kr'=> '韩 语'
         );
		if (isset($this->request->post['payment_epay_units'])) {
			$data['payment_epay_units'] = $this->request->post['payment_epay_units'];
		} else {
			$data['payment_epay_units'] = $this->config->get('payment_epay_units');
		}
		if (isset($this->request->post['payment_epay_lang'])) {
			$data['payment_epay_lang'] = $this->request->post['payment_epay_lang'];
		} else {
			$data['payment_epay_lang'] = $this->config->get('payment_epay_lang');
		}

		if (isset($this->request->post['payment_epay_total'])) {
			$data['payment_epay_total'] = $this->request->post['payment_epay_total'];
		} else {
			$data['payment_epay_total'] = $this->config->get('payment_epay_total');
		}
		if (isset($this->request->post['payment_epay_order_status_id'])) {
			$data['payment_epay_order_status_id'] = $this->request->post['payment_epay_order_status_id'];
		} else {
			$data['payment_epay_order_status_id'] = $this->config->get('payment_epay_order_status_id');
		}
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		if (isset($this->request->post['payment_epay_geo_zone_id'])) {
			$data['payment_epay_geo_zone_id'] = $this->request->post['payment_epay_geo_zone_id'];
		} else {
			$data['payment_epay_geo_zone_id'] = $this->config->get('payment_epay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		if (isset($this->request->post['payment_epay_status'])) {
			$data['payment_epay_status'] = $this->request->post['payment_epay_status'];
		} else {
			$data['payment_epay_status'] = $this->config->get('payment_epay_status');
		}
		if (isset($this->request->post['payment_epay_sort_order'])) {
			$data['payment_epay_sort_order'] = $this->request->post['payment_epay_sort_order'];
		} else {
			$data['payment_epay_sort_order'] = $this->config->get('payment_epay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/epay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/epay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_epay_account']) {
			$this->error['payment_epay_account'] = $this->language->get('error_payment_epay_account');
		}

		if (!$this->request->post['payment_epay_key']) {
			$this->error['payment_epay_key'] = $this->language->get('error_payment_epay_key');
		}

		if (!$this->request->post['payment_epay_units']) {
			$this->error['payment_epay_units'] = $this->language->get('error_payment_epay_units');
		}

		if (!$this->request->post['payment_epay_lang']) {
			$this->error['payment_epay_lang'] = $this->language->get('error_payment_epay_lang');
		}
		return !$this->error;
	}
}