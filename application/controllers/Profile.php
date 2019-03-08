<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        if (!$this->session->has_userdata('worker_id')) {
            redirect('logout');
        }

        $this->data['page'] = 'profile';

        $this->load->library(['form_validation']);
    }
    
    /**
     * Load workboard
     */
    public function index()
    {
        $this->load->model('worker_model');

        $this->template['css_files'] = [
            auto_version('../assets/css/new-style.css'),
        ];

        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        if (!$worker) {
            redirect('/logout');
        }

        $this->form_validation->set_rules('first_name', 'First Name', 'required');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

        if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') {
            $this->form_validation->set_rules('username', 'Username', 'required|callback_username_check['.$worker->worker_id.']');
            $new_password = $this->input->post('new_password', true);

            if ($new_password) {
                $this->form_validation->set_rules('password', 'Password', 'trim|callback_password_check['.$worker->password.']');
                $this->form_validation->set_rules('new_password', 'New Password', 'trim|callback_new_password_check');
            }
        }

        if ($this->form_validation->run()) {
            $fields = [
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'ephone' => $this->input->post('ephone'),
                'ecell' => $this->input->post('ecell'),
                'email' => $this->input->post('email')
            ];
            if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') {
                $fields['username'] = $this->input->post('username');
                if ($new_password) {
                    $fields['password'] = $this->get_password_hash($new_password);
                }
            }
            $this->worker_model->update($fields, ['worker_id' => $worker->worker_id]);

            $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
            $this->template['js_script'] = '
                $(document).ready(function() {
                    $.notify("Your profile has been updated successfully.", "success");
                });
            ';
        }

        $this->pullSidebarData();

        $this->data['worker'] = $worker;
        $this->main = 'profile/index';
        $this->main();
    }

    public function mobile()
    {
        $this->load->model('worker_model');
        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        if (!$worker) {
            redirect('/logout');
        }

        $this->form_validation->set_rules('first_name', 'First Name', 'required');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

        if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') {
            $this->form_validation->set_rules('username', 'Username', 'required|callback_username_check['.$worker->worker_id.']');
            $new_password = $this->input->post('new_password', true);

            if ($new_password) {
                $this->form_validation->set_rules('password', 'Password', 'trim|callback_password_check['.$worker->password.']');
                $this->form_validation->set_rules('new_password', 'New Password', 'trim|callback_new_password_check');
            }
        }

        if ($this->form_validation->run()) {
            $fields = [
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'ephone' => $this->input->post('ephone'),
                'ecell' => $this->input->post('ecell'),
                'email' => $this->input->post('email')
            ];
            if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') {
                $fields['username'] = $this->input->post('username');
                if ($new_password) {
                    $fields['password'] = $this->get_password_hash($new_password);
                }
            }
            $this->worker_model->update($fields, ['worker_id' => $worker->worker_id]);

            $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
            $this->template['js_script'] = '
                $(document).ready(function() {
                    $.notify("Your profile has been updated successfully.", "success");
                });
            ';
        }

        $this->data['worker'] = $worker;
        $this->main = 'profile/mobile';
        $this->mobile_layout();
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
     * validate old password
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    public function password_check($password, $hash)
    {
        if (empty($password)) {
            $this->form_validation->set_message('password_check', 'Old Password field is required.');
            return FALSE;
        }

        if (hash_hmac('sha256', $password, 'ghis$53^', true) != $hash) {
            $this->form_validation->set_message('password_check', 'Old password does not match.');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate password
     * @param string $password
     * @return boolean
     */
    public function new_password_check($password)
    {
        if (!preg_match('/^(\w*(?=\w*\d)(?=\w*[a-z])(?=\w*[A-Z])\w*){6,20}$/', $password)) {
            $this->form_validation->set_message('new_password_check', 'Password must be between 6 and 20 characters long, with at least one lowercase letter, one uppercase letter, and one number.');
            return FALSE;
        }

        return TRUE;
    }
}