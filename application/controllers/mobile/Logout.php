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
    	
    	delete_cookie('mobile_hash');
    	
    	delete_cookie('siteAuth');
    	
    	redirect('mobile/login', 'refresh');
    }
}