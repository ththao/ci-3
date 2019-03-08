<?php
class User_model extends MY_Model
{
    public $table = 'users';
    
    /**
     * Find user by kiosk and username
     * @param string $username
     * @param string $url_id
     * @return Object
     */
    public function get_kiosk_user($username, $url_id)
    {
    	$user = $this->get_by_attributes(array('username' => $username, 'remove' => 0));
    	
    	if ($user && $url_id) {
    		if ($user->type == 'v_admin') {
    			return $user;
    		} else {
    			$this->load->model('kiosk_model');
    			$kiosk = $this->kiosk_model->get(array('c_id' => $user->c_id, 'removed' => 0, 'url_id' => $url_id));
    			
    			if ($kiosk) {
    				return $user;
    			} else {
    				return null;
    			}
    		}
    	}
        
        return $user;
    }

    public function get_by_attributes($attributes)
    {
        $this->db->from($this->table);
        $this->db->where('remove', 0);
        foreach ($attributes as $prop => $val) {
            $this->db->where($prop, $val);
        }
        $this->db->limit(1);
        $query = $this->db->get();
    
        if ($result = $query->result()) {
            return $result[0];
        }
        return null;
    }
}