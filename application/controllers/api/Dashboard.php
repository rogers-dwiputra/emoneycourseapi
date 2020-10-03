<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Dashboard extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function index_get()
    {
        $this->db->where('id_user', $this->get('id_user'));
        $query = $this->db->get('users');
        $user = $query->row_array();

        $this->db->where('id_user', $this->get('id_user'));
        $this->db->order_by('waktu_transaksi', 'DESC');
        $this->db->limit(5);
        $query = $this->db->get('transactions');
        $transactions = $query->result_array();

        $this->response(array(
            'status' => 'true',
            'msg' => 'ok',
            'data' => array(
                'saldo' => $user['saldo_user'],
                'transaksi' => $transactions
            )
        ), 200 );
    }
}