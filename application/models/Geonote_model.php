<?php
class Geonote_model extends MY_Model
{
    public $table = 'geonotes';
    
    public function get_notes($worker_id, $department_id)
    {
        $this->db->select('
            geonotes.geonote_id, geonotes.geonote_note, geonote_permissions.geonote_d_id,
            department.department_name, geonotes.date_modified, geonotes.live, geonotes.c_id,
            users.first_name, users.last_name, geonotes.complete,geonotes.created_by, geonotes.show_on_db
        ');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id = geonotes.created_by', 'INNER');
        $this->db->join('geonote_permissions', 'geonotes.geonote_id = geonote_permissions.geonote_id AND geonote_permissions.geonote_d_id = geonotes.d_id', 'LEFT OUTER');
        $this->db->join('department', 'geonote_permissions.geonote_d_id = department.d_id', 'LEFT OUTER');
        $this->db->join('workers', 'workers.c_id = geonotes.c_id', 'INNER');
        $this->db->where('geonotes.live', 1);
        $this->db->where('geonotes.complete', 0);
        $this->db->where('workers.worker_id', $worker_id);
        $this->db->where('geonotes.d_id', $department_id);
        $this->db->order_by('geonotes.date_modified DESC, geonotes.geonote_id DESC');
        $this->db->limit(50);
        
        $query = $this->db->get();
        
        return $query->result();
    }
}