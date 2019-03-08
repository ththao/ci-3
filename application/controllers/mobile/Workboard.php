<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workboard extends MY_Controller
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
    	    redirect('mobile?redirect=' . urlencode('mobile/workboard'));
    	}
    	
    	$this->load->model(['working_session_model', 'worker_permission_model', 'worker_model']);
    	$d_id = $this->input->get('d');
    	if (!$d_id) {
    		$worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
    		if ($worker) {
    			$this->session->set_userdata('department_id', $worker->d_id);
    		}
    	} else {
    		$this->session->set_userdata('department_id', $d_id);
    	}
    	
    	$old_working_session = $this->working_session_model->missing_punch();
    	if ($old_working_session) {
    		redirect('mobile/missing');
    	}
    	
    	// Get today working session (in case employee clocked in and traking time)
    	$working_session = $this->working_session_model->get_tracked_time(date_by_timezone(time(), 'Y-m-d'));
    	
    	$permissions = $this->worker_permission_model->get(['worker_id' => $this->session->userdata('worker_id')]);
    	
    	$this->template['css_files'] = [
    			auto_version('../../assets/css/jquery.timepicker.css'),
    			auto_version('../../assets/css/style.css'),
    			auto_version('../../assets/css/custom.css'),
    			auto_version('../../assets/css/m.style.css'),
    	];
    	$this->template['js_files'] = [
        	    auto_version('../../assets/js/jquery.timepicker.js'),
        	    auto_version('../../assets/js/momentum.js'),
    			auto_version('../../assets/js/workboard.js'),
    			auto_version('../../assets/js/task.js'),
    			auto_version('../../assets/js/mobile.js')
    	];
    	$this->template['js_script'] = '
            var workboard = new Workboard({
            	taskClass: "task-item",
                mainClockInButton: "btn-main-clock-in",
                mainClockoutButton: "btn-main-clock-out",
                mainClockInUrl: "/ajax/clock_in",
                mainClockOutUrl: "/ajax/clock_out",
                workingUrl: "/ajax/working",
                logoutUrl: "/mobile/logout",
                mobile: 1,
                enable_clock_in: ' . ($permissions->start_work_session ? 1 : 0) . ',
                enable_start_task: ' . ($permissions->start_task ? 1 : 0) . ',
                sessionTimeout: 36000,
                timeFormat: "' . ($this->session->userdata('timeformat') == 2 ? 'H:i:s' : 'h:i:s A') . '",
                worker_id: ' . $this->session->userdata('worker_id') . ',
                clock_in: "' . $working_session['clock_in'] . '",
                hour: ' . $working_session['hour'] . ',
                minute: ' . $working_session['minute'] . ',
                second: ' . $working_session['second'] . ',
                sessionTimer: ' . ($working_session['hour']*3600 + $working_session['minute']*60 + $working_session['second']) . ',
                countdown: ' . $working_session['countdown'] . '
            });
            workboard.init();
        ';
    	
    	$this->load_data();
    	$this->load_mobile_data();
    	
    	$this->main = 'mobile/workboard/index';
    	$this->mobile_layout();
    }
}