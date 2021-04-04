<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Snap extends RestController {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */

	public function __construct()
    {
        parent::__construct();
        $params = array('server_key' => 'SB-Mid-server-CSOJn8FQBvXZjQCcRwlVZlsY', 'production' => false);
		$this->load->library('midtrans');
		$this->midtrans->config($params);
		$this->load->helper('url');	
    }

    public function token_post()
    {
		$order_id = time();
		$midtrans_order = array(
			'id_user' => $this->post('id_user'),
			'order_id' => $order_id,
			'nominal_transaction' => $this->post('nominal_topup'),
			'latitude_transaksi' => $this->post('latitude_transaksi'),
			'longitude_transaksi' => $this->post('longitude_transaksi')
		);
		$this->db->insert('midtrans_order', $midtrans_order);
		
		// Required
		$transaction_details = array(
			'order_id' => $order_id,
			'gross_amount' => $this->post('nominal_topup'), // no decimal allowed for creditcard
		);

		// Data yang akan dikirim untuk request redirect_url.
		$credit_card['secure'] = true;
		//ser save_card true to enable oneclick or 2click
		//$credit_card['save_card'] = true;

		$time = time();
		$custom_expiry = array(
			'start_time' => date("Y-m-d H:i:s O",$time),
			'unit' => 'minute', 
			'duration'  => 5
		);
		
		$transaction_data = array(
			'transaction_details'=> $transaction_details,
			'credit_card'        => $credit_card,
			'expiry'             => $custom_expiry,
			'finish' => 'https://basicteknologi.co.id'
		);

		// echo '<pre>';
		// print_r($transaction_data);
		// echo '</pre>';

		$snapToken = $this->midtrans->getSnapToken($transaction_data);
		// echo $snapToken;
		$response = [
			'status' => 'true',
			'msg' => 'Success',
			'data' => array(
				'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$snapToken,
				'order_id' => $order_id
			)
		];
		$this->response($response, 200);
    }

    public function finish()
    {
    	// $this->db->insert('midtranslog', array(
		// 	'midtrans_step' => 'thankYouPage',
		// 	'midtranslog_timestamp' => date('Y-m-d H:i:s'),
		// 	'midtrans_data' => json_encode($_GET)
		// ));

		// $this->db->where('id_midtrans_order', $this->input->get('transaction_id'));
		// $query = $this->db->get('midtrans_order');
		// $midtrans_order = $query->result_array();

		// $this->db->insert('transactions',
        //     array(
        //     'id_user' => $midtrans_order['id_user'],
        //     'waktu_transaksi' => date('Y-m-d H:i:s'),
        //     'nominal_transaksi' => $midtrans_order['nominal_transaction'],
        //     'berita_transaksi' => 'Top Up Melalui VA '.$_GET['va_numbers']['bank'],
        //     'jenis_transaksi' => 'kredit',
        //     'latitude_transaksi' => $midtrans_order['latitude_transaksi'],
        //     'longitude_transaksi' => $midtrans_order['longitude_transaksi'])
        // );

		// $this->load->view('thankyou');
    }

    public function notification_post(){

		$midtransdata = file_get_contents('php://input');

    	$this->db->insert('midtranslog', array(
			'midtrans_step' => 'notification',
			'midtranslog_timestamp' => date('Y-m-d H:i:s'),
			'midtrans_data' => $midtransdata
		));

		$midtransdata_array = json_decode($midtransdata, true);

		$this->db->where('order_id', $midtransdata_array['order_id']);
		$this->db->update('midtrans_order', array(
			'va_number' => $midtransdata_array['va_numbers'][0]['va_number'],
			'bank' => $midtransdata_array['va_numbers'][0]['bank'],
			'transaction_status' => $midtransdata_array['transaction_status']
		));

		if($midtransdata_array['transaction_status'] == 'settlement'){
			$this->db->where('order_id', $midtransdata_array['order_id']);
			$query = $this->db->get('midtrans_order');
			$midtrans_order = $query->row_array();

			$this->db->insert('transactions',
				array(
				'id_user' => $midtrans_order['id_user'],
				'waktu_transaksi' => date('Y-m-d H:i:s'),
				'nominal_transaksi' => $midtrans_order['nominal_transaction'],
				'berita_transaksi' => 'Top Up Melalui VA '.$midtransdata_array['va_numbers'][0]['bank'],
				'jenis_transaksi' => 'kredit',
				'latitude_transaksi' => $midtrans_order['latitude_transaksi'],
				'longitude_transaksi' => $midtrans_order['longitude_transaksi'])
			);

			$this->db->where('id_user', $midtrans_order['id_user']);
			$query = $this->db->get('users');
			$user = $query->row_array();

			$this->db->where('id_user', $midtrans_order['id_user']);
			$this->db->update('users', array(
				'saldo_user' => $user['saldo_user'] + $midtrans_order['nominal_transaction']
			));
		}
		else {
			$this->db->where('order_id', $midtransdata_array['order_id']);
			$this->db->update('midtrans_order', array(
				'va_number' => $midtransdata_array['va_numbers'][0]['va_number'],
				'bank' => $midtransdata_array['va_numbers'][0]['bank']
			));
		}
		
		echo 'OK';
	}
	
	public function transactionstatus_get(){
		$order_id = $this->get('order_id');
		$this->db->where('order_id', $order_id);
		$query = $this->db->get('midtrans_order');
		$midtrans_order = $query->row_array();

		$response = [
			'status' => 'true',
			'msg' => 'Ok',
			'data' => array(
				'nominal_topup' => number_format($midtrans_order['nominal_transaction']),
				'transaction_time' => date('d F Y H:i', strtotime($midtrans_order['midtrans_order_timestamp'])),
				'bank' => $midtrans_order['bank'],
				'va_number' => $midtrans_order['va_number'],
				'transaction_status' => $midtrans_order['transaction_status']
			)
		];
		$this->response($response, 200);
	}
}
