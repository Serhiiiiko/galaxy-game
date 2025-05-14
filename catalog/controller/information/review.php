<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerInformationReview extends Controller {
	public function index() {
		$this->load->language('information/information');

		$this->load->model('catalog/review');
        $this->load->model('tool/image');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$start = 0;
        $end = 150;
		$reviews = $this->model_catalog_review->getAllReviews($start, $end);
        $data['reviews'] = array();

        foreach ($reviews as $review) {

            $image = $this->model_tool_image->resize($review['image'], 50, 60);
            //$image = '';

            $data['reviews'][] = array(
                "author" => $review['author'],
                "text" => $review['text'],
                "rating" => $review['rating'],
                "date_added" => date($this->language->get('date_format_short'), strtotime($review['date_added'])),
                "image" => $image
                );
        }

		$this->document->setTitle("Отзывы");
        
		$this->document->setRobots('noindex,follow');

        $data['heading_title'] = "Отзывы";
			
		//$this->document->setDescription($information_info['meta_description']);
		//$this->document->setKeywords($information_info['meta_keyword']);

		$data['breadcrumbs'][] = array(
			'text' => "Отзывы",
			'href' => $this->url->link('information/review')
		);
		
		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/review', $data));
		
	}

	public function agree() {
		$this->load->model('catalog/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$output = '';

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$output .= html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8') . "\n";
		}

		$this->response->addHeader('X-Robots-Tag: noindex');

		$this->response->setOutput($output);
	}
}
