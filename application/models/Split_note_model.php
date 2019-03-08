<?php
class Split_note_model extends MY_Model
{
    public $table = 'split_notes';
    
    public function get_notes($worker_id)
    {
        $this->db->select('split_notes.split_notes');
        $this->db->from($this->table);
        $this->db->join('work_board', 'work_board.work_board_id = split_notes.workboard_id', 'INNER');
        $this->db->join('workers', 'workers.c_id = work_board.c_id AND workers.d_id = work_board.d_id', 'INNER');
        $this->db->where('workers.worker_id', $worker_id);
        $this->db->where('work_board.w_date', date_by_timezone(time(), 'Y-m-d'));
    
        $query = $this->db->get();
    
        return $query->result();
    }
}