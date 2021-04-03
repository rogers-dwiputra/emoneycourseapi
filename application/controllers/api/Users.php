<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Users extends RestController {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    public function login_post()
    {
        if($this->post('email') != '' && $this->post('password') != ''){
            $this->db->select('id_user, email_user, nama_user, nomor_handphone');
            $this->db->where('email_user', $this->post('email'));
            $this->db->where('password_user', md5($this->post('password')));
            $query = $this->db->get('users');
            if($query->num_rows() == 1){
                $user = $query->row_array();
                $this->response(array(
                    'status' => 'true',
                    'msg' => 'ok',
                    'data' => $user
                ), 200 );
            }
            else {
                $this->response(array(
                    'status' => 'false',
                    'msg' => 'Login Gagal'
                ), 200 );
            }
        }
        else {
            $this->response(array(
                'status' => 'false',
                'msg' => 'Login Gagal'
            ), 200 );
        }
    }

    public function registrasi_post()
    {
        if($this->post('email') != '' && $this->post('password') != '' && $this->post('nama') != '' && $this->post('nomor_handphone') != ''){
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
        else {
            $this->response(array(
                'status' => 'false',
                'msg' => 'Pendaftaran Gagal, Email/Password/Nama/No Handphone Tidak Boleh Kosong'
            ), 200 );
        }
    }
}