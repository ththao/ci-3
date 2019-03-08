<?php
class Department_model extends MY_Model
{
    public $table = 'department';
    
    public function get_assigned_departments($worker_id)
    {
        $this->db->select('department.d_id, department.department_name');
        $this->db->from($this->table);
        $this->db->join('workers_departments', 'workers_departments.department_id = department.d_id', 'INNER');
        $this->db->where('workers_departments.worker_id', $worker_id);
        $this->db->where('workers_departments.remove', 0);
        
        $query = $this->db->get();
        
        return $query->result();
    }
}