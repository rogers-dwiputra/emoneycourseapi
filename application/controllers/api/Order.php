<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Order extends Corecust
{
    const STATUS_ORDER = [
        'Belum Memilih Metode Bayar',
        'Menunggu Pembayaran',
        'Pembayaran Terverifikasi'
    ];

    const STATUS_MERCHANT = [
        'Menunggu Di Proses Merchant',
        'Dalam Proses',
        'Dalam Pengiriman',
        'Selesai'
    ];

    const JENIS_ORDER = [
        'order',
        'lelang'
    ];

    const METODE_ORDER = [
        'transfer',
        'cod'
    ];


    protected $cart = [];


    public function __construct()
    {
        parent::__construct();
        $this->cart = $this->get_cart(true);
    }


    public function checkout_post()
    {
        $post = $this->post();

        // $form = [
        //     'metode_order' => 'metode_order'
        // ];

        // $check_form = $this->check_form($form, $post);

        // setting default metode order
        $check_form = true;
        $post['metode_order'] = isset($post['metode_order']) ? $post['metode_order'] : 'transfer';

        if ($check_form) {
            if (in_array($post['metode_order'], self::METODE_ORDER)) {

                if (!empty($this->cart['cart'])) {
                    $clear = $this->clear_cart();

                    $data_order = [
                        'nomor_order' => random_string('numeric', 8),
                        'waktu_order' => date('Y-m-d H:i:s'),
                        'total_order' => $this->cart['total_cart'],
                        'total_ongkir' => $this->cart['total_ongkir'],
                        'total_bill' => $this->cart['total_bill'],
                        'status_order' => self::STATUS_ORDER[0],
                        'id_customer' => $this->SESSION_USER['id_customer'],
                        'alamat_pengiriman' => $this->SESSION_USER['alamat_customer'],
                        'latitude_pengiriman' => $this->SESSION_USER['latitude_customer'],
                        'longitude_pengiriman' => $this->SESSION_USER['longitude_customer'],
                        'jenis_order' => self::JENIS_ORDER[0],
                        'metode_order' => $post['metode_order']
                    ];

                    $this->db->trans_begin();
                    $this->db->insert(parent::TABLE_ORDERS, $data_order);
                    $id_order = $this->db->insert_id();

                    foreach ($this->cart['cart'] as $idx => $val) {
                        //penambahan suffix pada kode order merchant
                        $kode = random_string('numeric', 8) . '-' . str_pad(($idx + 1), 2, "0", STR_PAD_LEFT);
                        $data_cart = [
                            'id_orders' => $id_order,
                            'id_merchant' => $val['id_merchant'],
                            'kode_order_merchant' => $kode,
                            'total_order_merchant' => $val['total_ongkir_merchant'],
                            'total_ongkir_merchant' => $val['total_items_merchant'],
                            'total_bill_merchant' => $val['total_bill_merchant'],
                            'total_jarak_km' => $val['total_jarak'],
                            'ongkir_per_km' => $val['ongkir_per_km'],
                        ];
                        $this->db->insert(parent::TABLE_ORDERS_MERCHANT, $data_cart);
                        $id_order_merchant = $this->db->insert_id();

                        //add history order
                        $this->update_history_order($id_order_merchant, 'Order Dibuat');
                        //

                        $data_items = [];
                        foreach ($val['items'] as $itm) {
                            $this->db->where('id_product', $itm['id_product']);
                            $items = $this->db->get(parent::TABLE_PRODUCT)->row_array();

                            $data_items[] = [
                                'id_orders_merchant' => $id_order_merchant,
                                'product_sku' => $items['product_sku'],
                                'nama_product' => $items['nama_product'],
                                'harga_product' => $items['harga_product'],
                                'berat_product_kg' => $items['berat_product_kg'],
                                'kondisi_product' => $items['kondisi_product'],
                                'desc_product' => $items['desc_product'],
                                'product_photo' => $itm['product_image'],
                                'qty_product' => $itm['qty_product']
                            ];
                        }

                        $this->db->insert_batch(parent::TABLE_ORDERS_ITEMS, $data_items);
                    }

                    if ($this->db->trans_status() !== FALSE) {
                        $this->db->trans_commit();
                        $response = [
                            'status' => 'true',
                            'msg' => 'Success'
                        ];
                        if ($post['metode_order'] == self::METODE_ORDER[0]) {
                            $response['data'] = [
                                'url' => site_url('snap/index/' . $id_order)
                            ];
                        }
                        else {
                            //notif order baru ke merchant
                            // $token = array();
                            // $this->db->where('id_order', $id_order);
                            // $query = $this->db->get('orders_merchant');
                            // $merchant = $query->result_array();
                            // foreach($merchant as $rowMerchant){
                            //     $token[] = $rowMerchant['fcm_token'];
                            // }
                            // sendFcm($token, 'Order Cash on Delivery Diterima', 'No Order '.$kode);
                        }
                        $this->response($response, 200);
                    } else {
                        $this->db->trans_rollback();
                    }
                } else {
                    $this->response([
                        'status' => false,
                        'msg' => 'Cart is empty'
                    ], 400);
                }
            } else {
                $this->response([
                    'status' => false,
                    'msg' => 'Metode order not registered'
                ], 400);
            }
        } else {
            $this->response([
                'status' => false,
                'msg' => 'Missing Parameter'
            ], 400);
        }
    }

    public function index_get()
    {
        $id_customer = $this->SESSION_USER['id_customer'];

        $this->db->select('orders_merchant.*, merchant.nama_merchant, merchant.alamat_merchant, merchant.foto_merchant, merchant.no_handphone_merchant, orders.*');
        if ($this->get('status') != '') {
            $this->db->where('status_order_merchant', $this->get('status'));
        }

        $this->db->order_by('orders.waktu_order', 'DESC');
        $this->db->where('id_customer', $id_customer);
        $this->db->group_start();
        $this->db->where('orders.status_order', 'Pembayaran Terverifikasi');
        $this->db->or_where('orders.metode_order', 'cod');
        $this->db->group_end();
        $this->db->join(parent::TABLE_ORDERS, 'orders.id_orders = orders_merchant.id_orders');
        $this->db->join(parent::TABLE_MERCHANT, 'merchant.id_merchant = orders_merchant.id_merchant');
        $query = $this->db->get(parent::TABLE_ORDERS_MERCHANT);
        $orders = $query->result_array();

        foreach ($orders as $i => $key) {
            $where = [
                'id_orders_merchant' => $key['id_orders_merchant']
            ];
            $this->db->where($where);
            $query = $this->db->get(parent::TABLE_ORDERS_ITEMS);
            $orders_items = $query->result_array();

            $this->db->where($where);
            $query = $this->db->get(parent::TABLE_ORDERS_HISTORY);
            $orders_history = $query->result_array();

            $orders[$i]['orders_items'] = $orders_items;
            $orders[$i]['orders_history'] = $orders_history;
            $orders[$i]['status_order'] = 'Cash on Delivery';
            $orders[$i]['durasi_pengiriman'] = $key['tanggal_pengiriman'] != '0000-00-00' ? date('Y-m-d', strtotime($key['tanggal_pengiriman'] . ' +' . $key['durasi_pengiriman'] . ' Days')) : '-';
            $orders[$i]['tanggal_pengiriman'] = $key['tanggal_pengiriman'] != '0000-00-00' ? $key['tanggal_pengiriman'] : '-';
        }

        $this->response([
            'status' => true,
            'msg' => 'Success',
            'data' => $orders
        ], 200);
    }

    public function unpaid_get()
    {
        $id_customer = $this->SESSION_USER['id_customer'];

        if ($this->get('status') != '') {
            $this->db->where('status_order', $this->get('status'));
        }

        $this->db->order_by('orders.waktu_order', 'DESC');
        $this->db->where('metode_order', 'transfer');
        $this->db->where('id_customer', $id_customer);
        $this->db->where('status_order != "Pembayaran Terverifikasi"');
        $query = $this->db->get(parent::TABLE_ORDERS);
        $orders = $query->result_array();

        $orderIndex = 0;
        foreach ($orders as $key) {
            $this->db->select('orders_merchant.*, merchant.nama_merchant, merchant.alamat_merchant, merchant.foto_merchant, merchant.no_handphone_merchant');
            $this->db->where('id_orders', $key['id_orders']);
            $this->db->join(parent::TABLE_MERCHANT, 'merchant.id_merchant = orders_merchant.id_merchant');
            $query = $this->db->get(parent::TABLE_ORDERS_MERCHANT);
            $order_merchant = $query->result_array();
            $orders[$orderIndex]['merchants'] = $order_merchant;

            $orderMerchantIndex = 0;
            foreach ($order_merchant as $keyMerchant) {
                $this->db->where('id_orders_merchant', $keyMerchant['id_orders_merchant']);
                $query = $this->db->get(parent::TABLE_ORDERS_ITEMS);
                $orders_items = $query->result_array();

                $orders[$orderIndex]['merchants'][$orderMerchantIndex]['orders_items'] = $orders_items;
                $this->db->where('id_orders_merchant', $keyMerchant['id_orders_merchant']);
                $query = $this->db->get(parent::TABLE_ORDERS_HISTORY);
                $orders_history = $query->result_array();
                $orders[$orderIndex]['merchants'][$orderMerchantIndex]['orders_history'] = $orders_history;
                $orderMerchantIndex++;
            }

            $orders[$orderIndex]['url'] = site_url('snap/index/' . $key['id_orders']);
            $orderIndex++;
        }

        $this->response([
            'status' => true,
            'msg' => 'Success',
            'data' => $orders
        ], 200);
    }


    public function updateselesai_post()
    {
        $post = $this->post();

        $form = [
            'id_orders_merchant' => 'id_orders_merchant'
        ];

        $check_form = $this->check_form($form, $post);

        if ($check_form) {
            $this->db->where('id_orders_merchant', $post['id_orders_merchant']);
            if ($this->db->update(parent::TABLE_ORDERS_MERCHANT, ['status_order_merchant' => 'Selesai'])) {

                //update history order
                $this->update_history_order($post['id_orders_merchant'], 'Selesai');

                //Kirim Notifikasi ke Merchant
                $this->db->where('id_orders_merchant', $post['id_orders_merchant']);
                $this->db->join('merchant', 'merchant.id_merchant = orders_merchant.id_merchant');
                $query = $this->db->get('orders_merchant');
                $order_merchant = $query->row_array();

                $token = array();
		        $token[] = $order_merchant['fcm_token'];
                sendFcm($token, 'Cat-Fish', 'Order '.$order_merchant['kode_order_merchant'].' Telah Diterima Customer (Selesai)');

                $this->response([
                    'status' => true,
                    'msg' => 'Success',
                ], 200);
            } else {
                $this->response([
                    'status' => false,
                    'msg' => 'Error',
                ], 400);
            }
        } else {
            $this->response([
                'status' => false,
                'msg' => 'Missing Parameter',
            ], 400);
        }
    }
}
