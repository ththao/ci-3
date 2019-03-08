<?php
class Split_table_model extends MY_Model
{
    public $table = 'split_table';
    
    public function get_active_split($working_date = null)
    {
        if (!$working_date) {
            $working_date = date('Y-m-d');
        }
        $this->load->model('work_board_model');
        $workboard_id = $this->work_board_model->getWorkboard();
        
        $this->db->select('split_table_id, split_name, split_time, manual_override');
        $this->db->from($this->table);
        $this->db->where('split_table.workboard_id', $workboard_id);
        $this->db->order_by('split_time');
    
        $query = $this->db->get();
        $splitboards = $query->result();
        
        if ($splitboards) {
            $overrideNumber = 0;
            $activeNumber = 0;
            foreach ($splitboards as $index => $splitboard) {
                $getcurrentTime = 0;
                if ($splitboard->manual_override == 1) {
                    $overrideNumber = 1;
                    $activeNumber = $index;
                }
                if ((strtotime($working_date) + $splitboard->split_time) <= strtotime($working_date . ' ' . date('H:i')) && $overrideNumber == 0) {
                    $activeNumber = $index;
                }
            }
            
            return $splitboards[$activeNumber]->split_table_id;
        }
        
        return 0;
    }
}