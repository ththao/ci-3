<?php
class Worker_model extends MY_Model
{
    public $table = 'workers';
    
    /**
     * Find worker by kiosk
     * @param string $login_pins
     * @param string $url_id
     * @return Object
     */
    public function get_kiosk_worker($login_pins, $url_id)
    {
        $this->db->select('workers.*');
        $this->db->from($this->table);
        //$this->db->join('department', 'department.d_id = workers.d_id', 'INNER');
        $this->db->join('companies', 'companies.company_id = workers.c_id', 'INNER');
        $this->db->join('kiosk', 'companies.company_id = kiosk.c_id', 'INNER');
        $this->db->where('workers.login_pins', $login_pins);
        $this->db->where('kiosk.url_id', $url_id);
        $this->db->where('kiosk.removed', 0);
        $this->db->where('workers.remove', 0);
        $query = $this->db->get();
        $rs = $query->result();
        
        return !empty($rs) ? $rs[0] : null;
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