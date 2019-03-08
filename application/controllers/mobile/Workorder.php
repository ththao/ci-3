<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->has_userdata('worker_id')) {
            redirect('/mobile/logout');
        }
        $this->data['page'] = 'workorder';
    }

    public function index()
    {
        $this->template['css_files'] = [
            auto_version('../../assets/css/jquery-ui.css'),
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
            auto_version('../../assets/css/select2.css')
        ];
        $this->template['js_files'] = [
            auto_version('../../assets/js/jquery-ui.js'),
            auto_version('../../assets/js/mobile.js'),
            auto_version('../../assets/js/workorder.js'),
            auto_version('../../assets/js/select2.js'),
            auto_version('../../assets/js/autogrow.js')
        ];
        
        $this->getWorkOrderData();
        
        $this->data['add_wo_permission'] = 1;
        $this->db->select('add_new_work_order');
        $this->db->from('worker_permission');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $permissions = $query->result();
        
        if ($permissions) {
            $permission = $permissions[0];
            if (!$permission->add_new_work_order) {
                $this->data['add_wo_permission'] = 0;
            }
        }
        
        $this->main = 'mobile/workorder/index';
        $this->mobile_layout();
    }

    public function detail()
    {
        $this->template['css_files'] = [
            auto_version('../../assets/css/jquery-ui.css'),
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
            auto_version('../../assets/css/select2.css')
        ];
        $this->template['js_files'] = [
            auto_version('../../assets/js/jquery-ui.js'),
            auto_version('../../assets/js/mobile.js'),
            auto_version('../../assets/js/select2.js'),
            auto_version('../../assets/js/workorder.js'),
            auto_version('../../assets/js/autogrow.js')
        ];

        if (!isset($_GET['id']) || !$_GET['id']) {
            redirect('mobile/workorder');
        }

        $this->getWorkOrderData($_GET['id']);

        if (!isset($this->data['work_order']) || !$this->data['work_order']) {
            redirect('mobile/workorder');
        }

        if (isset($_GET['job'])) {
            $this->data['job_id'] = $_GET['job'];
        }

        $this->main = 'mobile/workorder/detail';
        $this->mobile_layout();
    }
    
    public function create()
    {
        $this->template['css_files'] = [
            auto_version('../../assets/css/jquery-ui.css'),
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
            auto_version('../../assets/css/select2.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../../assets/js/jquery-ui.js'),
            auto_version('../../assets/js/mobile.js'),
            auto_version('../../assets/js/workorder.js'),
            auto_version('../../assets/js/select2.js'),
            auto_version('../../assets/js/autogrow.js')
        ];
        
        $this->getEquipmentData();
        
        $this->main = 'mobile/workorder/create';
        $this->mobile_layout();
    }
}