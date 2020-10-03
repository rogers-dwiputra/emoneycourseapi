<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Topup extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function index_post()
    {
        $this->db->insert('transactions',
            array(
            'id_user' => $this->post('id_user'),
            'waktu_transaksi' => date('Y-m-d H:i:s'),
            'nominal_transaksi' => $this->post('nominal_topup'),
            'berita_transaksi' => 'Top Up Melalui VA Mandiri',
            'jenis_transaksi' => 'kredit',
            'latitude_transaksi' => $this->post('latitude_transaksi'),
            'longitude_transaksi' => $this->post('longitude_transaksi'))
        );

        $this->db->where('id_user', $this->post('id_user'));
        $query = $this->db->get('users');
        $user = $query->row_array();

        $this->db->where('id_user', $this->post('id_user'));
        $this->db->update('users', array(
            'saldo_user' => $user['saldo_user'] + $this->post('nominal_topup')
        ));

        $this->response(array(
            'status' => 'true',
            'msg' => 'ok',
            'data' => array(
                'nominal_topup' => $this->post('nominal_topup'),
                'waktu_transaksi' => date('H:i d F Y'),
                'payment_channel' => 'VA Mandiri'
            )
        ), 200 );
    }
}