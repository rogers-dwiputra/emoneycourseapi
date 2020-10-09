<?php


class Snap extends Coreci
{

    public function __construct()
    {
        parent::__construct();
        $params = array('server_key' => $this->serverKey, 'production' => $this->production);
        $this->load->library('midtrans');
        $this->midtrans->config($params);
        $this->load->helper('url');
    }

    public function index($id_order = null)
    {
        if ($id_order == null) {
            echo 'ID Order Required';
        } else {
            $data = [
                'id_order' => $id_order,
                'clientKey' => $this->clientKey
            ];
            $this->load->view('checkout_snap', $data);
        }
    }

    public function token($id_order = null)
    {
        if ($id_order != null) {
            $this->db->where('id_orders', $id_order);
            $this->db->join('customer', 'customer.id_customer=orders.id_customer');
            $query = $this->db->get('orders');
            $order = $query->row_array();
            unset($order['password_customer']);

            // Required
            $transaction_details = array(
                'order_id' => $order['nomor_order'],
                'gross_amount' => $order['total_bill'], // no decimal allowed for creditcard
            );

            $credit_card['secure'] = true;

            // Optional
            $customer_details = array(
                'first_name'    => $order['nama_customer'],
                'last_name'     => "",
                'email'         => $order['email_customer'],
                'phone'         => $order['nomor_handphone_customer'],
            );

            $time = time();
            $custom_expiry = array(
                'start_time' => date("Y-m-d H:i:s O", $time),
                'unit' => 'minute',
                'duration'  => 5
            );

            $transaction_data = array(
                'transaction_details' => $transaction_details,
                'credit_card'        => $credit_card,
                'expiry'             => $custom_expiry,
                'customer_details'   => $customer_details,
                'callbacks'          => array(
                    'finish' => site_url('snap/finish'),
                )
            );

            $snapToken = $this->midtrans->getSnapToken($transaction_data);
            $this->db->insert('midtranslog', array(
                'midtrans_step' => 'tokenSnap',
                'midtranslog_timestamp' => date('Y-m-d H:i:s'),
                'midtrans_data' => $snapToken
            ));

            echo $snapToken;
        }
    }

    public function finish()
    {
        $this->db->insert('midtranslog', array(
            'midtrans_step' => 'thankYouPage',
            'midtranslog_timestamp' => date('Y-m-d H:i:s'),
            'midtrans_data' => json_encode($_GET)
        ));

        echo 'DONE';
    }
}
