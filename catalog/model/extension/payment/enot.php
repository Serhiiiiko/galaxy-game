<?php 
class ModelExtensionPaymentEnot extends Model {
	public function getMethod($address, $total) {
		$title = $this->config->get('payment_enot_title');

		return array(
			'code' => 'enot',
			'terms' => '',
			'title' => ($title ? $title : 'Enot'),
			'sort_order' => $this->config->get('payment_enot_sort_order')
		);
	}
}
?>