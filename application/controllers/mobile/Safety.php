<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Safety extends MY_Controller
{
    const ITEM_PER_PAGE = 10;
    public function __construct()
    {
        parent::__construct();
        $this->data['page'] = 'safety';
    }

    public function index()
    {
        if (!$this->session->has_userdata('worker_id')) {
            redirect('mobile/login');
        }

        $this->template['css_files'] = [
            auto_version('../../assets/css/jquery-ui.css'),
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
        ];

        $this->template['js_files'] = [
            auto_version('../../assets/js/jquery-ui.js'),
            auto_version('../../assets/js/safety.js')
        ];

        $this->data['item_per_page'] = self::ITEM_PER_PAGE;
        $this->db->select('safety_cards.safety_name, worker_safety_cards.does_not_expire, worker_safety_cards.expiration');
        $this->db->from('worker_safety_cards');
        $this->db->join('safety_cards', 'safety_cards.id = worker_safety_cards.safety_card_id', 'INNER');
        $this->db->where('worker_safety_cards.worker_id', $this->session->userdata('worker_id'));
        $this->db->where('(worker_safety_cards.does_not_expire = 1 OR worker_safety_cards.expiration >= "' . date('Y-m-d') . '")');
        $this->db->order_by('worker_safety_cards.start_date DESC');
        $this->db->limit(self::ITEM_PER_PAGE);
        $query = $this->db->get();
        $this->data['active_cards'] = $query->result();

        $this->db->select('COUNT(*) AS card_cnt');
        $this->db->from('worker_safety_cards');
        $this->db->where('worker_safety_cards.worker_id', $this->session->userdata('worker_id'));
        $this->db->where('(worker_safety_cards.does_not_expire = 1 OR worker_safety_cards.expiration >= "' . date('Y-m-d') . '")');
        $query = $this->db->get();
        $cnt_data = $query->result();
        if ($cnt_data) {
            $data = $cnt_data[0];
            $this->data['active_cards_cnt'] = $data->card_cnt;
        }

        $this->db->select('safety_cards.safety_name, worker_safety_cards.does_not_expire, worker_safety_cards.expiration');
        $this->db->from('worker_safety_cards');
        $this->db->join('safety_cards', 'safety_cards.id = worker_safety_cards.safety_card_id', 'INNER');
        $this->db->where('worker_safety_cards.worker_id', $this->session->userdata('worker_id'));
        $this->db->where('(worker_safety_cards.does_not_expire = 0 AND worker_safety_cards.expiration < "' . date('Y-m-d') . '")');
        $this->db->order_by('worker_safety_cards.start_date DESC');
        $this->db->limit(self::ITEM_PER_PAGE);
        $query = $this->db->get();
        $this->data['expired_cards'] = $query->result();

        $this->db->select('COUNT(*) AS card_cnt');
        $this->db->from('worker_safety_cards');
        $this->db->where('worker_safety_cards.worker_id', $this->session->userdata('worker_id'));
        $this->db->where('(worker_safety_cards.does_not_expire = 0 AND worker_safety_cards.expiration < "' . date('Y-m-d') . '")');
        $query = $this->db->get();
        $cnt_data = $query->result();
        if ($cnt_data) {
            $data = $cnt_data[0];
            $this->data['expired_cards_cnt'] = $data->card_cnt;
        }

        $this->main = 'mobile/safety/index';
        $this->mobile_layout();
    }
}