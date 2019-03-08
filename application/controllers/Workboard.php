<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->has_userdata('worker_id')) {
            redirect('logout');
        }
        
        $this->data['page'] = 'workboard';
        
        $this->sync_with_cookies();
    }
    
    /**
     * Load workboard
     */
    public function index()
    {
    	$this->load->model('working_session_model');
        $old_working_session = $this->working_session_model->missing_punch();
        if ($old_working_session) {
            redirect('workboard/update');
        }
        
        if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == 1) {
        	redirect('mobile/workboard');
        }
        
        // Get today working session (in case employee clocked in and traking time)
        $working_session = $this->working_session_model->get_tracked_time(date_by_timezone(time(), 'Y-m-d'));
        
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery.timepicker.css'),
            auto_version('../assets/css/new-style.css')
		];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery.timepicker.js'),
            auto_version('../assets/js/momentum.js'),
            auto_version('../assets/js/workboard.js'),
            auto_version('../assets/js/task.js')
        ];
        $this->template['js_script'] = '
            var workboard = new Workboard({
            	taskClass: "task-item",
                mainClockInButton: "btn-main-clock-in",
                mainClockoutButton: "btn-main-clock-out",
                mainClockInUrl: "/ajax/clock_in",
                mainClockOutUrl: "/ajax/clock_out",
                workingUrl: "/ajax/working",
                logoutUrl: "/logout",
                mobile: 0,
                enable_clock_in: 1,
                enable_start_task: 1,
                sessionTimeout: 60,
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
        
        $this->pullSidebarData();
        
        $this->main = 'workboard/index';
        $this->main();
    }
    
    public function update()
    {
        $this->load->model(['working_session_model', 'actual_time_keeping_model']);
        
        $working_session = $this->working_session_model->missing_punch();
        if (!$working_session) {
            redirect('workboard');
        }

        $this->template['css_files'] = [auto_version('../assets/css/jquery.timepicker.css')];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery.timepicker.js'),
            auto_version('../assets/js/missing_punch.js')
        ];
        $this->template['js_script'] = '
            var missingPunch = new MissingPunch({
                timeFormat: "' . ($this->session->userdata('timeformat') == 2 ? 'H:i:s' : 'h:i:s A') . '"
            });   
            missingPunch.init();
        ';
        
        $this->data['working_session'] = $working_session;
        $this->data['working_task'] = $this->actual_time_keeping_model->missing_punch($working_session->working_date);
        
        $this->main = 'workboard/update';
        $this->main();
    }
}