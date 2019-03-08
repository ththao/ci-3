<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logout extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->clearSession();
        
        delete_cookie('kiosk_hash');
        
    	$keycode = get_cookie('keycode');
    	$keycodes = (array)json_decode(get_cookie('keycodes'));
    	if ($keycode && $keycodes && isset($keycodes[(string)$keycode])) {
    		redirect('login/employee?url_id=' . $keycodes[$keycode]);
    	} else {
    		redirect('login');
    	}
    }
}