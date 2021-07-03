<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library(array('form_validation', 'session', 'cart'));
		$this->load->database();
		//$this->load->library('cart');
		//$this->load->library('session');
		$this->load->model('Crud_model');
	}

	public function cart() {

		$final_arr = $this->cart->contents();
		$data['cart_arrr'] = $final_arr;
		$this->load->view('cart', $data);

	}

	public function placeorder() {

		$final_arr = $this->cart->contents();

		$name = $this->input->post('name');
		$email = $this->input->post('email');
		$phone = $this->input->post('phone');
		$address = $this->input->post('address');
		$placeord = $this->input->post('placeord');

		$this->form_validation->set_rules('name', 'Name', 'required');
		$this->form_validation->set_rules('email', 'email', 'required');
		$this->form_validation->set_rules('phone', 'phone', 'required');
		$this->form_validation->set_rules('address', 'address', 'required');

		if ($this->form_validation->run() == FALSE) {
			$datass['checkout_arr'] = $final_arr;
			$this->load->view('checkout', $datass);
		} else {

			if (!empty($this->input->post('placeord'))) {

				if (is_array($final_arr) && count($final_arr) > 0) {

					$data['generated_id'] = strtotime("now");
					$data['dates'] = date("Y-m-d H:i:s");
					$data['status'] = 'placed';
					$lastid = $this->Crud_model->savegenric('order', $data);

					$data1['name'] = $name;
					$data1['email'] = $email;
					$data1['phone'] = $phone;
					$data1['address'] = $address;
					$data1['ord_id'] = $lastid;
					$this->Crud_model->savegenric('customers', $data1);

					foreach ($final_arr as $key => $value) {

						$data2['prd_id'] = $value['id'];
						$data2['qty'] = $value['qty'];
						$data2['price'] = $value['price'];
						$data2['name'] = $value['name'];
						$data2['ord_id'] = $lastid;
						$this->Crud_model->savegenric('orderitems', $data2);
					}

					$this->cart->destroy();
					redirect('welcome/productlist');

				}

			}

		}

		/*	$final_arr = $this->cart->contents();
			$data['cart_arrr'] = $final_arr;
		*/

	}

	public function checkout() {

		$final_arr = $this->cart->contents();
		$data['checkout_arr'] = $final_arr;
		$this->load->view('checkout', $data);

	}

	public function productlist() {

		//print_r($this->cart->contents());

		$ss = $this->cart->contents();
		$finalids = array_column($ss, 'id');
		//print_r($finalids);
		$data['present_arr'] = $finalids;
		//echo "productlist";exit();
		$data['allproducts'] = $this->Crud_model->fetchrecords();

		$this->load->view('productlist', $data);

	}

	function addToCart() {
		$proID = $this->input->post('pid');
		$product = $this->Crud_model->getRows($proID);
		$imges = explode(',', $product[0]['img']);
		//print_r($imges);exit;
		$data = array(
			'id' => $product[0]['id'],
			'qty' => 1,
			'price' => $product[0]['price'],
			'name' => $product[0]['name'],
			'image' => $imges[0],
		);
		$this->cart->insert($data);
		return 1;

	}

	function updateItemQty() {

		$rowid = $this->input->post('rowid');
		$qty = $this->input->post('qty');

		if (!empty($rowid) && !empty($qty)) {
			$data = array(
				'rowid' => $rowid,
				'qty' => $qty,
			);
			$update = $this->cart->update($data);
		}

		// Return response
		return 1;

	}

	function removeItem() {
		$proID = $this->input->post('pid');
		$remove = $this->cart->remove($proID);

	}

	public function index() {
		$flag = 0;
		$data = array();
		// Check form submit or not
		if ($this->input->post('save') != NULL) {

			$data = array();

			// Count total files
			$countfiles = count($_FILES['prodimg']['name']);

			// Looping all files
			for ($i = 0; $i < $countfiles; $i++) {

				if (!empty($_FILES['prodimg']['name'][$i])) {

					// Define new $_FILES array - $_FILES['file']
					$_FILES['file']['name'] = $_FILES['prodimg']['name'][$i];
					$_FILES['file']['type'] = $_FILES['prodimg']['type'][$i];
					$_FILES['file']['tmp_name'] = $_FILES['prodimg']['tmp_name'][$i];
					$_FILES['file']['error'] = $_FILES['prodimg']['error'][$i];
					$_FILES['file']['size'] = $_FILES['prodimg']['size'][$i];

					// Set preference
					$config['upload_path'] = 'uploads/';
					$config['allowed_types'] = 'jpg|jpeg|png|gif';
					$config['max_size'] = '5000'; // max_size in kb
					$config['file_name'] = $_FILES['prodimg']['name'][$i];

					//Load upload library
					$this->load->library('upload', $config);

					// File upload
					if ($this->upload->do_upload('file')) {
						// Get data about the file
						$uploadData = $this->upload->data();
						$filename = $uploadData['file_name'];
						//print_r($filename);

						if ($filename == '') {
							$flag = 0;
						} else {
							$flag = 2;
						}
						//print_r($filename);
						// Initialize array
						$data['filenames'][] = $filename;
					} else {
						$flag = 1;
						//echo "please upload valid image files";
					}
				}

			}

		}

		//print_r($data);
		$this->form_validation->set_rules('name', 'Name', 'required');
		$this->form_validation->set_rules('sdesc', 'Short Description', 'required');
		$this->form_validation->set_rules('desc', 'Description', 'required');
		$this->form_validation->set_rules('price', 'price', 'required');
		$this->form_validation->set_rules('status', 'status', 'required');
		//$this->form_validation->set_rules('prodimg', 'Image', 'required');
		if ($flag == 1) {
			$this->form_validation->set_rules('imgg', 'image file give valid', 'required');
		}
		if ($flag == 0) {
			$this->form_validation->set_rules('prodimg', 'Image', 'required');
		}

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('add');
		} else {
			//$this->load->view('add');
			$data1['cate_id'] = $this->input->post('cate');
			$data1['name'] = $this->input->post('name');
			$data1['sdesc'] = $this->input->post('sdesc');
			$data1['description'] = $this->input->post('desc');
			$data1['price'] = $this->input->post('price');
			$data1['status'] = $this->input->post('status');
			$data1['img'] = implode(',', $data['filenames']);
			$this->Crud_model->saverecords($data1);
			redirect('/');
		}

		//$this->load->view('add');
	}

}
