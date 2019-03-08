<?php
class Settings_model extends MY_Model
{
    public $table = 'settings';
    
    public function getSettings($c_id, $d_id, $name) {
        $this->db->select('settings_value');
        $this->db->from('settings');
        $this->db->where('settings_name', $name);
        $this->db->where('c_id', $c_id);
        $this->db->where('d_id', $d_id);
        
        $query = $this->db->get();
        
        $data = $query->result();
        if ($data) {
            return $data[0];
        }
        
        return null;
    }
}