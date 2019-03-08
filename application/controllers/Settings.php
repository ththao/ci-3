<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        if (!$this->session->has_userdata('worker_id')) {
            redirect('logout');
        }
        
        $this->data['page'] = 'settings';
    }
    
    /**
     * Load workboard
     */
    public function index()
    {
        $this->load->model(['supported_time_zone_model', 'worker_model']);
        
        if ($_POST) {
            $this->worker_model->update(
                [
                    'timeformat' => $this->input->post('timeformat', true),
                    'timezone' => $this->input->post('timezone', true)
                ],
                ['worker_id' => $this->session->userdata('worker_id')]
            );
            $this->session->set_userdata('timezone', $this->input->post('timezone', true));
            $this->session->set_userdata('timeformat', $this->input->post('timeformat', true));
        }


        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        if (!$worker) {
            redirect('/logout');
        }
        $this->data['timezones'] = $this->supported_time_zone_model->get_all();
        $this->data['user_timeformat'] = $worker->timeformat;
        $this->data['user_timezone'] = $worker->timezone;
        
        $this->main = 'settings/index';
        $this->main();
    }
}