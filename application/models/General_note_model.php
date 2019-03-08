<?php
class General_note_model extends MY_Model
{
    public $table = 'general_notes';
    
    public function get_notes($worker_id)
    {
        $this->db->select('general_notes.general_notes');
        $this->db->from($this->table);
        $this->db->join('workers', 'workers.c_id = general_notes.cid AND workers.d_id = general_notes.did', 'INNER');
        $this->db->where('workers.worker_id', $worker_id);
        
        $query = $this->db->get();
        
        return $query->result();
    }
}