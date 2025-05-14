<?php

class ControllerExtensionPaymentEnot extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/enot');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_enot', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$arr = array("warning", "merchant_id", "secret_word", "secret_word2");
		foreach ($arr as $v) $data['error_' . $v] = (isset($this->error[$v])) ? $this->error[$v] : "";

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/enot', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/payment/enot', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$array_data = array(
			'payment_enot_merchant_id',
			'payment_enot_secret_word',
			'payment_enot_secret_word2',
			'payment_enot_title',
			'payment_enot_order_status_id',
			'payment_enot_status',
			'payment_enot_sort_order',
		);

		foreach ($array_data as $v) {
			$data[$v] = (isset($this->request->post[$v])) ? $this->request->post[$v] : $this->config->get($v);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/enot', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/enot')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_enot_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}
		if (!$this->request->post['payment_enot_secret_word']) {
			$this->error['secret_word'] = $this->language->get('error_secret_word');
		}
		if (!$this->request->post['payment_enot_secret_word2']) {
			$this->error['secret_word2'] = $this->language->get('error_secret_word2');
		}
		return (!$this->error) ? true : false;
	}
}

?>