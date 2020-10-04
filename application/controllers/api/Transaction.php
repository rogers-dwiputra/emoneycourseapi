<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Transaction extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function index_get()
    {
        $this->db->where('id_user', $this->get('id_user'));
        $this->db->order_by('waktu_transaksi', 'desc');
        $query = $this->db->get('transactions');
        $transaction = $query->result_array();

        $this->response(array(
            'status' => 'true',
            'msg' => 'ok',
            'data' => $transaction
        ), 200 );
    }

    public function registrasi_post()
    {
        //cek email
        $this->db->where('email_user', $this->post('email'));
        $query = $this->db->get('users');
        if($query->num_rows() == 0){
            //cek no handphone
            $this->db->where('nomor_handphone', $this->post('nomor_handphone'));
            $query = $this->db->get('users');
            if($query->num_rows() == 0){
                $users = array(
                    'email_user' => $this->post('email'),
                    'password_user' => md5($this->post('password')),
                    'nama_user' => $this->post('nama'),
                    'nomor_handphone' => $this->post('nomor_handphone'),
                    'saldo_user' => 0
                );
        
                $this->db->insert('users', $users);

                $this->response(array(
                    'status' => 'true',
                    'msg' => 'ok'
                ), 200 );
            }
            else {
                $this->response(array(
                    'status' => 'false',
                    'msg' => 'Nomor Handphone Sudah Terdaftar'
                ), 200 );
            }
        }
        else {
            $this->response(array(
                'status' => 'false',
                'msg' => 'Email Sudah Terdaftar'
            ), 200 );
        }
    }
}