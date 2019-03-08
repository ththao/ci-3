<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Time extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        if (! $this->session->has_userdata('worker_id')) {
            redirect('mobile?redirect=' . urlencode('time'));
        }

        $this->data['page'] = 'time';
    }
    
    public function index()
    {
        $this->template['css_files'] = [
            auto_version('../../assets/css/jquery-ui.css'),
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
        ];

        $this->template['js_files'] = [
            auto_version('../../assets/js/jquery-ui.js'),
            auto_version('../../assets/js/time.js')
        ];

        $this->data['times_by_days'] = $this->render_times_by_days(date_by_timezone(strtotime('-3 days'), 'Y-m-d'), date_by_timezone(strtotime('+1 days'), 'Y-m-d'));
        $this->main = 'mobile/time/index';
        $this->mobile_layout();
    }
}