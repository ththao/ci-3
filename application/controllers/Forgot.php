<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forgot extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Load libraries, helpers, models
        $this->load->library(['form_validation']);
        $this->load->helper('url');
        $this->load->model(['worker_model', 'settings_model', 'department_model']);
    }
    
    /**
     * Admin login for the first time
     */
    public function index()
    {
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|callback_email_check');

        if ($this->form_validation->run()) {
            $worker = $this->worker_model->get_by_attributes(['email' => $this->input->post('email')]);
            $hash = md5($worker->worker_id . $this->random_string());
            
            $this->worker_model->update(
                ['forgot_hash' => $hash, 'forgot_time' => time()],
                ['worker_id' => $worker->worker_id]
            );
            
            $do_email = array();
            $do_email['from'] = 'tasktracker@asb.club';
            $do_email['to'] = $this->input->post('email');
            $do_email['subject'] = "taskTracker Forgot Password";
            $do_email['html_body'] = $this->forgot_email($worker, $hash);
            $do_email['reply_to'] = 'jaime@advancedscoreboards.com';
            $do_email['Attachments'] = array();
            
            $res = $this->send_email($do_email);
            
            if ($res['status']) {
                $this->template['js_script'] = '
                    $(document).ready(function() {
                        $.notify("Please check your email for reseting password", "success");
                    });
                ';
            } else {
                $this->template['js_script'] = '
                    $(document).ready(function() {
                        $.notify("' . $res['message'] . '", "error");
                    });
                ';
            }
        }
        
        $this->main = 'forgot/index';
        $this->login();
    }
    
    public function update($hash)
    {

        if (!$hash) {
            redirect('mobile');
        }
        $worker = $this->worker_model->get_by_attributes(['forgot_hash' => $hash]);
        if (!$worker) {
            redirect('mobile');
        }
        if ($worker->forgot_time + 30*60 < time()) {
            $this->worker_model->update(
                ['forgot_hash' => '', 'forgot_time' => ''],
                ['worker_id' => $worker->worker_id]
            );
            redirect('mobile');
        }
        
        $settings = $this->settings_model->get([
            'c_id' => $worker->c_id,
            'd_id' => $worker->d_id,
            'settings_name' => 'allow_log_in'
        ]);
        if (!$settings || $settings->settings_value != 1) {
            redirect('login');
        }
        
        $this->form_validation->set_rules('password', 'Password', 'trim|required');
        $this->form_validation->set_rules('c_password', 'Confirm Password', 'trim|required|matches[password]');

        if ($this->form_validation->run()) {
            $this->worker_model->update(
                [
                    //'password' => $this->get_password_hash($this->input->post('password', true)),
                    'forgot_hash' => '',
                    'forgot_time' => ''
                ],
                ['worker_id' => $worker->worker_id]
            );

            $this->db->query('UPDATE workers SET password="' . $this->get_password_hash($this->input->post('password', true) ) . '" WHERE worker_id=' . $worker->worker_id);
            $this->session->set_userdata('signup_message', 'Your account has been updated successfully.');
            redirect('mobile');
        }
        
        $this->data['hash'] = $hash;
        $this->data['worker'] = $worker;
        $this->main = 'forgot/update';
        $this->login();
    }
    
    /**
     * validate $email
     * @param string $email
     * @return boolean
     */
    public function email_check($email)
    {
        if (empty($email)) {
            $this->form_validation->set_message('email_check', 'Email field is required.');
            return FALSE;
        }
        
        $worker = $this->worker_model->get_by_attributes(['email' => $email]);

        if (!$worker) {
            $this->form_validation->set_message('email_check', 'This email is not exist in our system.');
            return FALSE;
        }
        
        return TRUE;
    }
    
    private function random_string($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    private function send_email($email) {
        $this->load->config('email');
        
        $json = json_encode(array(
            'From' => $email['from'],
            'To' => $email['to'],
            //'Cc' => $email['cc'],
            //'Bcc' => $email['bcc'],
            'Subject' => $email['subject'],
            //'Tag' => $email['tag'],
            'HtmlBody' => $email['html_body'],
            //'TextBody' => $email['text_body'],
            'ReplyTo' => $email['reply_to'],
            //'Headers' => $email['headers'],
            //'Attachments' => $email['Attachments']
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.postmarkapp.com/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-Server-Token: '. $this->config->item('POSTMARKKEY'),
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return array('status' => $http_code === 200, 'message' => isset($response['Message']) ? $response['Message'] : '');
    }
    
    private function forgot_email($receiver, $hash) {
        $forgot_url =  base_url() . 'forgot/update/' . $hash;
    
        $message = '';
        $message .="<head>";
    
        $message .="</head>";
    
        $message .= "<body style='font-family: Times New Roman, Georgia, serif; font-size: 12pt; width: 100%;'>";
        $message .= "<h1 style='background-color: #808080; color: white; line-height: 50px; padding-left: 10px;'>Welcome to ASB taskTracker</h1>";
        $message .= "<div style='margin-left: 10px; width: 100%;'>";
        $message .= "<div style=''>Dear " . $receiver->first_name . " " . $receiver->last_name . ",<br /><br /></div>";
        $message .= "<div style='margin-bottom: 20px;'>";
        $message .= "<span>";
        $message .= "Your username for your employee login is: " . $receiver->username . "<br />";
        $message .= "You can follow the <a href='" . $forgot_url . "'>link</a> to reset your password.<br/><br />";
        
        $message .= "Sincerely<br />";
        $message .= "The ASB taskTracker Team.<br />";
    
        $message .= "</span>";
        $message .= "</div>";
        $message .= "</div>";
    
        $message .= "</body>";
        $message .= "</html>";
        return $message;
    }
}