<?php
class Workboard_mp_model extends MY_Model
{
    public $table = 'workboard_mp';
    
    public function get_mow_patterns($department_id, $date)
    {
        $this->load->model(['work_board_model', 'worker_model']);
        
        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        $workboard = $this->work_board_model->get([
            'c_id' => $worker->c_id,
            'd_id' => $department_id,
            'w_date' => date_by_timezone(strtotime($date), 'Y-m-d')
        ]);
        
        if ($workboard) {
            $this->db->select('
                mow_pattern_options.mp_cat, workboard_mp.rotation, mow_pattern_options.clock_face_path,
                COALESCE(company_mow_patterns.new_clock_face_path, mow_pattern_options.clock_ring_path) AS clock_ring_path,
                mow_pattern_options.img_path, mow_rotations.img_path AS rotation_img_path
            ');
            $this->db->from($this->table);
            $this->db->join('mow_pattern_options', 'mow_pattern_options.mow_pattern_id = workboard_mp.mow_pattern_id AND mow_pattern_options.remove = 0', 'INNER');
            $this->db->join('mow_rotations', 'mow_rotations.mow_rotation_id = workboard_mp.rotation', 'LEFT OUTER');
            $this->db->join(
                'company_mow_patterns',
                'company_mow_patterns.mow_pattern_id = mow_pattern_options.mow_pattern_id
                 AND company_mow_patterns.is_use = 1 AND company_mow_patterns.c_id = ' . $worker->c_id . ' AND company_mow_patterns.d_id = ' . $worker->d_id,
                'LEFT OUTER'
            );
            $this->db->where('workboard_mp.workboard_id', $workboard->work_board_id);
            $this->db->order_by('mow_pattern_options.mp_cat, mow_pattern_options.sort_order');
            $query = $this->db->get();
            return $query->result();
        }
        return null;
    }
}