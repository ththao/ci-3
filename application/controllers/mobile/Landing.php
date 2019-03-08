<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load libraries, helpers, models
        $this->load->model(['worker_model', 'settings_model', 'department_model']);
        $this->load->library(['form_validation']);
    }
    
    public function index()
    {
        if (!$this->session->has_userdata('worker_id')) {
            redirect('mobile?redirect=' . urlencode('mobile/landing'));
        }
        
        $this->template['css_files'] = [
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
        ];
        $this->template['js_script'] = "
            $(document).on('click', '#open-menu', function (e) {
                e.preventDefault();
                
                $('#left-side-menu').slideToggle('medium');
            });
        ";
        
        $this->pullSidebarData();

        $this->main = 'mobile/landing/index';
        $this->mobile_layout();
    }
}