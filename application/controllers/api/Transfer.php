<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Transfer extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function checknumber_get()
    {
        $this->db->select('id_user, nama_user, nomor_handphone');
        $this->db->where('nomor_handphone', $this->get('nomor_handphone'));
        $query = $this->db->get('users');
        if($query->num_rows() == 1){
            $user = $query->result_array();
            $this->response(array(
                'status' => 'true',
                'msg' => 'ok',
                'data' => $user
            ), 200 );
        }
        else {
            $this->response(array(
                'status' => 'false',
                'msg' => 'Nomor Handphone '.$this->get('nomor_handphone').' Tidak Terdaftar'
            ), 200 );
        }
    }

    public function process_post(){
        $this->db->where('id_user', $this->post('id_pengirim'));
        $query = $this->db->get('users');
        $pengirim = $query->row_array();

        $this->db->select('id_user, nama_user, nomor_handphone, saldo_user');
        $this->db->where('id_user', $this->post('id_penerima'));
        $query = $this->db->get('users');
        $penerima = $query->row_array();

        $this->db->insert('transactions',
            array(
            'id_user' => $this->post('id_pengirim'),
            'waktu_transaksi' => date('Y-m-d H:i:s'),
            'nominal_transaksi' => $this->post('nominal_transfer'),
            'berita_transaksi' => 'Kirim uang ke '.$penerima['nama_user'],
            'jenis_transaksi' => 'debet',
            'latitude_transaksi' => $this->post('latitude_transaksi'),
            'longitude_transaksi' => $this->post('longitude_transaksi'))
        );

        $this->db->insert('transactions',
            array(
            'id_user' => $this->post('id_penerima'),
            'waktu_transaksi' => date('Y-m-d H:i:s'),
            'nominal_transaksi' => $this->post('nominal_transfer'),
            'berita_transaksi' => 'Menerima uang dari '.$pengirim['nama_user'],
            'jenis_transaksi' => 'kredit',
            'latitude_transaksi' => $this->post('latitude_transaksi'),
            'longitude_transaksi' => $this->post('longitude_transaksi'))
        );

        $this->db->where('id_user', $this->post('id_pengirim'));
        $this->db->update('users', array(
            'saldo_user' => $pengirim['saldo_user'] - $this->post('nominal_transfer')
        ));

        $this->db->where('id_user', $this->post('id_penerima'));
        $this->db->update('users', array(
            'saldo_user' => $penerima['saldo_user'] + $this->post('nominal_transfer')
        ));

        $this->response(array(
            'status' => 'true',
            'msg' => 'ok',
            'data' => array(
                'nominal_transfer' => $this->post('nominal_transfer'),
                'waktu_transaksi' => date('H:i d F Y'),
                'data' => $penerima
            )
        ), 200 );
    }
}