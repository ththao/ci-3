<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load libraries, helpers, models
        $this->load->model(['worker_model', 'settings_model', 'department_model']);
        $this->load->library(['form_validation']);
    }
    
    /**
     * Admin login for the first time
     */
    public function index()
    {
    	// User has already logged in
    	if ($this->session->has_userdata('worker_id') || $this->login_by_remember_me()) {
    	    if ($redirect = $this->input->get('redirect')) {
    	        redirect($redirect);
    	    } else {
    	        redirect('mobile/landing');
    	    }
    	}
    	
        $this->form_validation->set_rules('password', 'Password', 'required');
        $this->form_validation->set_rules('username', 'Username', 'callback_mobile_username_check');
        
        if ($this->form_validation->run()) {
            redirect('mobile/landing');
        }

        $this->template['css_files'] = [
            auto_version('../assets/css/m.style.css'),
        ];
        
        if ($this->session->has_userdata('signup_message')) {
            $this->template['js_script'] = '
                $(document).ready(function() {
                    $.notify("' . $this->session->userdata('signup_message') . '", "success");
                });
            ';
            $this->session->unset_userdata('signup_message');
        }
        
        $this->main = 'mobile/login/index';
        $this->login();
    }
    
    /**
     * validate username/password
     * @param string $username
     * @return boolean
     */
    public function mobile_username_check($username)
    {
    	if (empty($username)) {
    		$this->form_validation->set_message('mobile_username_check', 'Username field is required.');
    		return FALSE;
    	}
    	
    	$sql = "
            SELECT * FROM workers WHERE username = '" . $username . "' AND password = '" . $this->get_password_hash($this->input->post('password', true)) . "'
        ";
    	$query = $this->db->query($sql);
    	$workers = $query->result();
    	if (!$workers) {
    	    $this->form_validation->set_message('mobile_username_check', 'Your username/password is not correct.');
    	    return FALSE;
    	}
    	
    	$worker = $workers[0];
    	
		if ($worker->d_id) {
			$settings = $this->settings_model->get([
				'c_id' => $worker->c_id,
				'd_id' => $worker->d_id,
				'settings_name' => 'allow_log_in'
			]);
			
			if (!$settings || $settings->settings_value != 1) {
				$this->form_validation->set_message('mobile_username_check', 'Your administrator has not turned on mobile log in for the department.');
				return FALSE;
            }

        	if (!$this->isDepartmentFeatureActive($worker->d_id, FEATURE_MOBILE_AND_KIOSK)) {
        	    $this->form_validation->set_message('mobile_username_check', 'Your administrator has not turned on mobile and kiosk module.');
				return FALSE;
        	}
		}
		
		if (!$worker->allow_log_in) {
			$this->form_validation->set_message('mobile_username_check', 'Your account is not allowed to log in on mobile version.');
			return FALSE;
		}
		
		$this->save_worker_to_session($worker);
		$this->session->set_userdata('mobile', "1");
		
		$remember = $this->input->post('remember', true);
		if ($remember) {
			$this->save_remember_me($worker->worker_id);
		}
		
		$session_hash = md5($worker->worker_id . '_' . $this->generateRandomString());
		set_cookie('mobile_hash', $session_hash, 30*86400);
		$this->worker_model->update(['mobile_hash' => $session_hash], ['worker_id' => $worker->worker_id]);
		
		return TRUE;
    }
    
    private function login_by_remember_me()
    {
        $siteAuth = get_cookie('siteAuth');
        if ($siteAuth) {
            $this->load->model(['worker_model', 'remember_worker_model']);
            $remember = $this->remember_worker_model->get(['remember_hash' => $siteAuth]);
            
            if ($remember) {
                $worker = $this->worker_model->get_by_attributes(['worker_id' => $remember->worker_id]);
                if ($worker) {
                	$settings = $this->settings_model->get([
                		'c_id' => $worker->c_id,
                		'd_id' => $worker->d_id,
                		'settings_name' => 'allow_log_in'
                	]);
                	if (!$settings || $settings->settings_value != 1) {
                		delete_cookie('siteAuth');
                		return FALSE;
                	}

                	if (!$this->isDepartmentFeatureActive($worker->d_id, FEATURE_MOBILE_AND_KIOSK)) {
                	    delete_cookie('siteAuth');
                	    return FALSE;
                	}
                	
                    $this->save_worker_to_session($worker);
                    
                    $this->session->set_userdata('mobile', "1");
                    
                    set_cookie('siteAuth', $siteAuth, 30*86400);
                    $this->remember_worker_model->update(
                        ['created_at' => time()],
                        ['remember_hash' => $siteAuth]
                    );
                    
                    $session_hash = md5($worker->worker_id . '_' . $this->generateRandomString());
                    set_cookie('mobile_hash', $session_hash, 30*86400);
                    $this->worker_model->update(['mobile_hash' => $session_hash], ['worker_id' => $worker->worker_id]);
                    
                    return true;
                }
            }
        }
        
        delete_cookie('siteAuth');
        return false;
    }
    
    private function save_remember_me($worker_id)
    {
        $siteAuth = md5($worker_id . '_' . $this->generateRandomString());
        set_cookie('siteAuth', $siteAuth, 30*86400);
        
        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $this->load->model(['remember_worker_model']);
        
        $this->remember_worker_model->delete(['worker_id' => $worker_id, 'created_at < ' => (time() - 30*86400)]);
        
        $this->remember_worker_model->insert([
            'worker_id' => $worker_id,
            'browser_hash' => $browser,
            'remember_hash' => $siteAuth,
            'created_at' => time()
        ]);
    }
}