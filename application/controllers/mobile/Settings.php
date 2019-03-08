<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
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
    	    redirect('mobile?redirect=' . urlencode('mobile/settings'));
    	}
        
        $this->template['css_files'] = [
            auto_version('../../assets/css/style.css'),
            auto_version('../../assets/css/custom.css'),
            auto_version('../../assets/css/m.style.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../../assets/js/settings.js')
        ];
        $this->template['js_script'] = "
            $(document).on('click', '#open-menu', function (e) {
                e.preventDefault();
                
                $('#left-side-menu').slideToggle('medium');
            });
        ";
        
        $this->db->select('first_name, last_name, email, ecell, receive_text_alert, username, c_id, worker_img');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        
        if ($workers) {
            $this->data['worker'] = $workers[0];
        }
        
        $this->main = 'mobile/settings/index';
        $this->mobile_layout();
    }
    
    public function confirm_current_password()
    {
        $sql = "
            SELECT worker_id FROM workers WHERE worker_id = " . $this->session->userdata('worker_id') . "
            AND password = '" . $this->get_password_hash($this->input->post('password', true)) . "'
        ";
        $query = $this->db->query($sql);
        $workers = $query->result();
        
        if ($workers) {
            echo json_encode(array('status' => 1));
            exit;
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Your password does not match.'));
    }
    
    public function save_settings()
    {
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $this->db->update('workers', array(
            'email' => $this->input->post('email'),
            'ecell' => $this->input->post('ecell'),
            'receive_text_alert' => $this->input->post('receive_text_alert')
        ));
        
        echo json_encode(array('status' => 1, 'message' => 'Your settings has been saved successfully.'));
    }
    public function validate_new_username()
    {
        if ($this->check_new_username()) {
            echo json_encode(array('status' => 1));
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Username is taken'));
        }
    }
    
    private function check_new_username()
    {
        $sql = "SELECT worker_id, c_id FROM workers WHERE worker_id = " . $this->session->userdata('worker_id');
        $query = $this->db->query($sql);
        $workers = $query->result();
        
        if ($workers) {
            $worker = $workers[0];
            
            $this->db->select('worker_id');
            $this->db->from('workers');
            $this->db->where('username', $this->input->post('username'));
            $this->db->where('c_id', $worker->c_id);
            $this->db->where('worker_id <> ', $this->session->userdata('worker_id'));
            $query = $this->db->get();
            $n_workers = $query->result();
            
            if ($n_workers) {
                return false;
            }
        }
        
        return true;
    }
    
    public function save_new_account()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        if ($username && !$this->check_new_username()) {
            echo json_encode(array('status' => 0, 'message' => 'Username is taken'));
            exit();
        }
        
        $update = array();
        if ($username) {
            $update['username'] = $username;
        }
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $this->db->update('workers', $update);
        
        
        
        if ($password) {
            $sql = 'UPDATE workers SET password = "' . $this->get_password_hash($password) . '" WHERE worker_id = ' . $this->session->userdata('worker_id');
            $this->db->query($sql);
        }
        
        echo json_encode(array('status' => 1, 'message' => 'Your credentials has been saved successfully.'));
    }
}