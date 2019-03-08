<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load libraries, helpers, models
        $this->load->model(['kiosk_model', 'user_model', 'worker_model', 'company_model', 'working_session_model']);
        $this->load->library(['form_validation']);
        
        // User has already logged in
        if ($this->session->has_userdata('worker_id')) {
            redirect('workboard');
        }
        
        $this->load_worker_data();
    }
    
    /**
     * Load data from parameter url_id and session
     */
    private function load_worker_data()
    {
        $this->data['url_id'] = $this->input->get('url_id', true);
        $kiosk = $this->kiosk_model->get(['url_id' => $this->data['url_id'], 'removed' => 0]);
        if ($kiosk) {
            $this->data['keycode'] = $kiosk->keycode;
            $this->data['kiosk_name'] = $kiosk->kiosk_name;
            $this->session->set_userdata('kiosk_name', $kiosk->kiosk_name);
            $this->session->set_userdata('kiosk_id', $kiosk->k_id);
        } else {
            $this->session->unset_userdata('kiosk_name');
            $this->session->unset_userdata('kiosk_id');
        }
        
        $company = $this->company_model->get(['company_id' => $kiosk ? $kiosk->c_id : $this->session->userdata('company_id')]);
        $this->data['company_name'] = $company ? $company->c_name : '';
    }
    
    private function check_cookie()
    {
        $keycodes = (array)json_decode(get_cookie('keycodes'));
        if (isset($this->data['keycode']) && $keycodes && isset($keycodes[(string)$this->data['keycode']])) {
            redirect('login/employee?url_id=' . $keycodes[$this->data['keycode']]);
        }
    }
    
    /**
     * Admin login for the first time
     */
    public function index()
    {
        $this->check_cookie();
        
        $this->form_validation->set_rules('username', 'Username', 'callback_username_check');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if ($this->form_validation->run()) {
            if (isset($this->data['keycode']) && $this->data['keycode']) {
                $kiosk = $this->kiosk_model->get(['keycode' => $this->data['keycode'], 'removed' => 0]);
                if ($kiosk) {
                    $keycodes = (array)json_decode(get_cookie('keycodes'));
                    
                    $keycodes[$kiosk->keycode] = $kiosk->url_id;
                    set_cookie('keycodes', json_encode($keycodes), 30*86400);
                }
            }
            
            redirect($this->data['url_id'] ? 'login/employee?url_id=' . $this->data['url_id'] : 'login/kiosks');
        }
        
        $this->main = 'login/index';
        $this->login();
    }
    
    /**
     * Select kiosk
     */
    public function kiosks()
    {
        $this->check_cookie();
        
        if (!$this->session->has_userdata('company_id')) {
            redirect('login');
        }
        $this->data['kiosks'] = $this->createDropdownData(
            $this->kiosk_model->get_all([
                'c_id' => $this->session->userdata('company_id'),
                'removed' => 0
            ]),
            'url_id',
            'kiosk_name'
            );
        
        $this->form_validation->set_rules('url_id', 'Kiosk', 'required');
        if ($this->form_validation->run()) {
            $kiosk = $this->kiosk_model->get(['url_id' => $this->input->post('url_id', true), 'removed' => 0]);
            
            if (!$kiosk) {
                redirect('login/kiosks');
            }
            
            set_cookie('keycode', $kiosk->keycode, 30*86400);
            $keycodes[$kiosk->keycode] = $kiosk->url_id;
            set_cookie('keycodes', json_encode($keycodes), 30*86400);
            
            redirect('login/employee?url_id=' . $this->input->post('url_id', true));
        }
        
        
        $this->main = 'login/kiosks';
        $this->login();
    }
    
    /**
     * Show employee login form
     */
    public function employee()
    {
        $keycodes = (array)json_decode(get_cookie('keycodes'));
        if (!isset($this->data['keycode']) || !$keycodes || !isset($keycodes[(string)$this->data['keycode']])) {
            redirect('login?url_id=' . $this->data['url_id']);
        }
        if (!isset($this->data['url_id']) || !$this->data['url_id']) {
            redirect('login');
        }
        
        $this->form_validation->set_rules('code', 'Login pins', 'callback_pins_check');
        
        if ($this->form_validation->run()) {
            set_cookie('keycode', $this->data['keycode'], 30*86400);
            $keycodes[$this->data['keycode']] = $this->data['url_id'];
            set_cookie('keycodes', json_encode($keycodes), 30*86400);
            
            redirect('workboard');
        }
        
        $this->data['url_id'] = $this->input->get('url_id', true);
        $this->main = 'login/employee';
        $this->login();
    }
    
    /**
     * Validate login pins
     * @param string $login_pins
     * @return boolean
     */
    public function pins_check($login_pins)
    {
        if (empty($login_pins)) {
            $this->form_validation->set_message('pins_check', 'Login pins field is required.');
            return FALSE;
        }
        
        $kiosk = $this->kiosk_model->get([
            'url_id' => $this->input->get('url_id', true),
            'removed' => 0
        ]);
        if (!$kiosk) {
            $this->form_validation->set_message('pins_check', 'Your kiosk is not available.');
            return FALSE;
        }
        
        $worker = $this->worker_model->get_kiosk_worker($login_pins, $this->input->get('url_id', true));
        
        if ($worker) {
            if ($worker->remove) {
                $this->form_validation->set_message('pins_check', 'Your account has already been deactivated.');
                return FALSE;
            }
            
            if (!$this->isDepartmentFeatureActive($kiosk->did, FEATURE_MOBILE_AND_KIOSK)) {
                $this->form_validation->set_message('pins_check', 'Your administrator has not turned on mobile and kiosk module.');
                return FALSE;
            }
            
            $this->save_worker_to_session($worker);
            
            $this->session->set_userdata('kiosk_name', $kiosk->kiosk_name);
            $this->session->set_userdata('kiosk_id', $kiosk->k_id);
            $this->session->set_userdata('mobile', "0");
            
            $session_hash = md5($worker->worker_id . '_' . $this->generateRandomString());
            set_cookie('kiosk_hash', $session_hash, 30*86400);
            delete_cookie('mobile_hash');
            $this->worker_model->update(['kiosk_hash' => $session_hash], ['worker_id' => $worker->worker_id]);
            
            return TRUE;
        } else {
            $this->form_validation->set_message('pins_check', 'Your login pins is not correct.');
            return FALSE;
        }
    }
    
    private function do_login($username, $password)
    {
        $sql = "SELECT * FROM users WHERE remove = 0 AND username = '" . $username . "' AND pass = '" . $this->get_password_hash($password) . "'";
        $query = $this->db->query($sql);
        $users = $query->result();
        
        if ($users) {
            return $users[0];
        }
        
        $sql = "SELECT * FROM users WHERE remove = 0 AND username = '" . $username . "'";
        $query = $this->db->query($sql);
        $users = $query->result();
        
        if ($users) {
            $user = $users[0];
            if ($user->password) {
                if (password_verify($password, $user->password)) {
                    return $user;
                }
            } else {
                if ($user->pass && ($user->pass == $this->get_password_hash($password) || $user->pass == substr($this->get_password_hash($password), 0, 32))) {
                    return $user;
                }
            }
        }
        
        return null;
    }
    
    /**
     * validate username/password
     * @param string $username
     * @return boolean
     */
    public function username_check($username)
    {
        if (empty($username)) {
            $this->form_validation->set_message('username_check', 'Username field is required.');
            return FALSE;
        }
        
        $user = $this->do_login($username, $this->input->post('password', true));
        if (!$user) {
            $this->form_validation->set_message('username_check', 'Your username/password is not correct.');
            return FALSE;
        }
        
        $url_id = $this->input->get('url_id', true);
        if ($user && $url_id && $user->type != 'v_admin') {
            $this->load->model('kiosk_model');
            $kiosk = $this->kiosk_model->get(array('c_id' => $user->c_id, 'removed' => 0, 'url_id' => $url_id));
            
            if (!$kiosk) {
                $this->form_validation->set_message('username_check', 'Your username/password is not correct.');
                return FALSE;
            }
        }
        
        $this->session->set_userdata('company_id', $user->c_id);
        return TRUE;
    }
}