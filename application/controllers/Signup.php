<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load libraries, helpers, models
        $this->load->library(['form_validation']);
        $this->load->model(['worker_model', 'settings_model', 'department_model']);
    }
    
    /**
     * Admin login for the first time
     */
    public function index($hash)
    {
        if (!$hash) {
            redirect('mobile');
        }
        $worker = $this->worker_model->get_by_attributes(['signup_hash' => $hash]);
        if (!$worker) {
            redirect('mobile');
        }
        
        $settings = $this->settings_model->get([
            'c_id' => $worker->c_id,
            'd_id' => $worker->d_id,
            'settings_name' => 'allow_log_in'
        ]);
        if (!$settings || $settings->settings_value != 1) {
            $this->session->set_userdata('signup_message', 'Your administrator has not turned on mobile log in for the department.');
            redirect('mobile');
        }
        
        $this->form_validation->set_rules('first_name', 'First Name', 'required');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('username', 'Username', 'required|callback_username_check['.$worker->worker_id.']');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|callback_password_check');
        $this->form_validation->set_rules('c_password', 'Confirm Password', 'trim|required|matches[password]');

        if ($this->form_validation->run()) {
            $this->worker_model->update(
                [
                    'first_name' => $this->input->post('first_name'),
                    'last_name' => $this->input->post('last_name'),
                    'ephone' => $this->input->post('ephone'),
                    'ecell' => $this->input->post('ecell'),
                    'email' => $this->input->post('email'),
                    'username' => $this->input->post('username'),
                    'password' => $this->get_password_hash($this->input->post('password', true)),
                    'signup_hash' => ''
                ],
                ['worker_id' => $worker->worker_id]
            );
            
            $this->session->set_userdata('signup_message', 'Your account has been updated successfully.');
            redirect('mobile');
        }
        
        $this->template['js_files'] = [auto_version('../assets/js/signup.js')];
        $this->template['css_files'] = [
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/m.style.css'),
        ];
        $this->data['hash'] = $hash;
        $this->data['worker'] = $worker;
        $this->main = 'signup/index';
        $this->login();
    }
    
    /**
     * validate username is_unique
     * @param string $username
     * @param int $worker_id
     * @return boolean
     */
    public function username_check($username, $worker_id)
    {
        if (empty($username)) {
            $this->form_validation->set_message('username_check', 'Username field is required.');
            return FALSE;
        }
        
        $worker = $this->worker_model->get_by_attributes([
            'username' => $username,
            'worker_id <> ' => $worker_id
        ]);

        if ($worker) {
            $this->form_validation->set_message('username_check', 'This username has been taken, please try another one.');
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * Ajax check unique username
     */
    public function unique_username()
    {
        $worker = $this->worker_model->get_by_attributes([
            'username' => $this->input->post('username', true),
            'signup_hash <> ' => $this->input->post('signup_hash', true)
        ]);
        $this->db->from('workers');
        $this->db->where('remove', 0);
        $this->db->where('username', $this->input->post('username', true));
        $this->db->where('(signup_hash IS NULL OR signup_hash <> "' . $this->input->post('signup_hash', true) . '")');
        $this->db->limit(1);
        $query = $this->db->get();
        
        if ($result = $query->result()) {
            echo json_encode(['status' => 0]);
        } else {
            echo json_encode(['status' => 1]);
        }
    }
    
    /**
     * Validate password
     * @param string $password
     * @return boolean
     */
    public function password_check($password)
    {
        /*if (!preg_match('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $password)) {
            $this->form_validation->set_message('password_check', 'Password must be between 6 and 20 characters long, with at least one lowercase letter, one uppercase letter, and one number.');
            return FALSE;
        }*/
        
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        if (!$uppercase || !$lowercase || !$number || strlen($password) < 6 || strlen($password) > 20) {
            $this->form_validation->set_message('password_check', 'Password must be between 6 and 20 characters long, with at least one lowercase letter, one uppercase letter, and one number.');
            return FALSE;
        }
        
        return TRUE;
    }
}