<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Missing extends MY_Controller
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
    	    redirect('mobile?redirect=' . urlencode('mobile/missing'));
    	}
    	
    	$this->load->model(['working_session_model', 'actual_time_keeping_model']);
    	
    	$working_session = $this->working_session_model->missing_punch();
    	if (!$working_session) {
    		redirect('mobile/workboard');
    	}
    	
    	$this->template['css_files'] = [auto_version('../assets/css/jquery.timepicker.css')];
    	$this->template['js_files'] = [
    			auto_version('../../assets/js/jquery.timepicker.js'),
    			auto_version('../../assets/js/missing_punch.js')
    	];
    	$this->template['js_script'] = '
            var missingPunch = new MissingPunch({
                timeFormat: "' . ($this->session->userdata('timeformat') == 2 ? 'H:i:s' : 'h:i:s A') . '"
            });
            missingPunch.init();
        ';
    	
    	$this->data['working_session'] = $working_session;
    	$this->data['working_task'] = $this->actual_time_keeping_model->missing_punch($working_session->working_date);
    	
    	$this->main = 'mobile/missing/index';
    	$this->main();
    }
}