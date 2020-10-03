<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Merchant extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function index_get()
    {
        $this->db->where('kode_merchant', $this->get('kode_merchant'));
        $query = $this->db->get('merchants');
        if($query->num_rows() == 1){
            $merchant = $query->row_array();
            $this->response(array(
                'status' => 'true',
                'msg' => 'ok',
                'data' => $merchant
            ), 200 );
        }
        else {
            $this->response(array(
                'status' => 'false',
                'msg' => 'Merchant Tidak Terdaftar'
            ), 200 );
        }
    }

    public function pay_post(){
        $this->db->where('id_merchant', $this->post('id_merchant'));
        $query = $this->db->get('merchants');
        $merchant = $query->row_array();

        $this->db->insert('transactions',
            array(
            'id_user' => $this->post('id_user'),
            'waktu_transaksi' => date('Y-m-d H:i:s'),
            'nominal_transaksi' => $this->post('nominal_bayar'),
            'berita_transaksi' => 'Pembayaran ke Merchant '.$merchant['nama_merchant'],
            'jenis_transaksi' => 'debet',
            'latitude_transaksi' => $this->post('latitude_transaksi'),
            'longitude_transaksi' => $this->post('longitude_transaksi'))
        );

        $this->response(array(
            'status' => 'true',
            'msg' => 'ok',
            'data' => array(
                'nominal_bayar' => $this->post('nominal_bayar'),
                'waktu_transaksi' => date('H:i d F Y'),
                'merchant' => $merchant
            )
        ), 200 );
    }
}