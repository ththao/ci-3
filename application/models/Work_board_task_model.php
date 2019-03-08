<?php
class Work_board_task_model extends MY_Model
{
    public $table = 'work_board_task';
    
    /**
     * Get task list for worker by date
     * @param int $worker_id
     * @param string $working_date
     */
    public function get_worker_tasks($worker_id, $working_date = null)
    {
        if (!is_numeric($worker_id)) {
            return [];
        }
        if (!$working_date) {
            $working_date = date_by_timezone(time(), 'Y-m-d');
        }
        
        $this->db->select('
            workers.worker_id, tasks.task_id, tasks.task_name, employee_notes_translation.trans_note, work_board_task.task_notes, 
            work_board_task.true_est_hr, work_board_task.wb_task_id, actual_time_keeping.time_id, department.department_name,
            actual_time_keeping.start_time, actual_time_keeping.end_time, total.total_time, task_languages.task_translation, 
            split_table.split_name, split_table.split_table_id, sub_tasks.sub_task_name, department.d_id, work_board_task.savedbyID,
            workboard_task_notes.notes AS wb_task_notes, workboard_task_notes_translation.trans_note AS wb_task_notes_tran,
            COALESCE(department_features.active, 1) AS equipment_active'
        );
        $this->db->distinct();
        $this->db->from($this->table);
        $this->db->join('sub_tasks', 'sub_tasks.task_id = work_board_task.task_id AND sub_tasks.id = work_board_task.sub_task_id', 'LEFT OUTER');
        $this->db->join('workers', 'workers.worker_id = work_board_task.worker_id', 'INNER');
        $this->db->join('workers_departments', 'workers.worker_id = workers_departments.worker_id AND work_board_task.workers_department_id = workers_departments.worker_department_id AND workers_departments.remove = 0', 'LEFT OUTER');
        $this->db->join('department_features', 'department_features.d_id = work_board_task.workers_department_id AND department_features.feature_id = ' . FEATURE_EQUIPMENT, 'LEFT OUTER');
        $this->db->join('department', 'department.d_id = workers_departments.department_id', 'LEFT OUTER');
        $this->db->join('tasks', 'tasks.remove = 0 AND tasks.task_id = work_board_task.task_id', 'INNER');
        $this->db->join('task_languages', 'tasks.task_id = task_languages.task_id AND task_languages.lang_code = workers.lang_code', 'LEFT OUTER');
        $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id', 'INNER');
        $this->db->join('split_table', 'split_table.split_table_id = work_board_task.sb_id', 'LEFT OUTER');
        $this->db->join('workboard_task_notes', 'workboard_task_notes.task_id = work_board_task.task_id AND workboard_task_notes.work_board_id = work_board.work_board_id AND workboard_task_notes.job_index = work_board_task.sortorder', 'LEFT OUTER');
        $this->db->join('workboard_task_notes_translation', 'workboard_task_notes_translation.workboard_task_notes_id = workboard_task_notes.id AND workboard_task_notes_translation.lang_code = workers.lang_code', 'LEFT OUTER');
        $this->db->join('employee_notes_translation', 'employee_notes_translation.wb_task_id = work_board_task.wb_task_id AND employee_notes_translation.lang_code = workers.lang_code', 'LEFT OUTER');
        
        // Total tracked sessions, which have end_time IS NOT NULL (past)
        $this->db->join('
            (SELECT worker_id, workboard_task_id, SUM(end_time - start_time) AS total_time
            FROM actual_time_keeping
            WHERE remove = 0 AND worker_id = ' . $worker_id . '
            AND start_time IS NOT NULL AND end_time IS NOT NULL
            GROUP BY worker_id, workboard_task_id) total
        ', 'total.worker_id = workers.worker_id AND total.workboard_task_id = work_board_task.wb_task_id',
            'LEFT OUTER');
        
        // Working session, which has end_time IS NULL (current)
        $this->db->join('actual_time_keeping', '
            actual_time_keeping.remove = 0 AND actual_time_keeping.worker_id = workers.worker_id
            AND actual_time_keeping.workboard_task_id = work_board_task.wb_task_id
            AND actual_time_keeping.start_time IS NOT NULL AND actual_time_keeping.end_time IS NULL
        ', 'LEFT OUTER');
        $this->db->where('workers.worker_id', $worker_id);
        $this->db->where('work_board.w_date', $working_date);
        $this->db->where('work_board_task.completed', 0);
        $this->db->order_by('split_table.split_time, work_board_task.sortorder, work_board_task.wb_task_id');
        $query = $this->db->get();
        
        $data = $query->result();
        
        if ($data) {
            
            foreach ($data as $item) {
                $equipmentActive = $this->isFeatureActive('equipment') && $item->equipment_active;
                
                if ($equipmentActive) {
                    $item->equipments = $this->get_task_equipments($item->wb_task_id);
                } else {
                    $item->equipments = array();
                }
            }
            
            return $data;
        }
        
        return array();
    }
    
    public function get_task_equipments($wb_task_id)
    {
        $this->db->select('COALESCE(equipment_type.short_name, equipment_type.model) AS equipment_model, equipment.equipment_model_id');
        $this->db->from('work_board_task_equipments');
        $this->db->join('equipment', 'equipment.id = work_board_task_equipments.equipment_id', 'INNER');
        $this->db->join('equipment_type', 'equipment.equipment_type_id = equipment_type.id', 'INNER');
        $this->db->where('work_board_task_equipments.wb_task_id', $wb_task_id);
        
        $query = $this->db->get();
        
        return $query->result();
    }
    
    /**
     * Get workboard tasks for next $days
     * @param int $worker_id
     */
    public function get_worker_schedules($worker_id, $days = 4)
    {
        $this->db->select('work_board.w_date, tasks.task_name, tasks.est_hours');
        $this->db->from($this->table);
        $this->db->join('workers', 'workers.worker_id = work_board_task.worker_id', 'INNER');
        $this->db->join('tasks', 'tasks.task_id = work_board_task.task_id', 'INNER');
        $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id', 'INNER');
        $this->db->where('workers.worker_id', $worker_id);
        $this->db->where('work_board.w_date > ', date_by_timezone(time(), 'Y-m-d'));
        $this->db->where('work_board.w_date <= ', date_by_timezone(strtotime('+ ' . $days . ' days'), 'Y-m-d'));
        $query = $this->db->get();
        
        return $query->result();
    }
    
    public function get_total_est_time($worker_id, $work_board_id)
    {
        $this->db->select('SUM(est_hr*3600) AS total_time');
        $this->db->from('work_board_task');
        $this->db->where('worker_id', $worker_id);
        $this->db->where('work_board_id', $work_board_id);
        $this->db->where('task_id > ', 0);
        
        $query = $this->db->get();
        if ($res = $query->result()) {
            $total = $res[0];
            
            return $total->total_time;
        }
        return 0;
    }
    
    public function create_free_task_time($worker_id, $w_date)
    {
        $this->load->model(['worker_model', 'working_session_model', 'work_board_task_model', 'work_board_model', 'worker_department_model']);
        
        $worker = $this->worker_model->get_by_attributes(array('worker_id' => $worker_id));
        
        $workboard = $this->work_board_model->getWorkboardObject();
        if (!$worker || !$workboard || $workboard->c_id != $worker->c_id) {
            return;
        }
        
        $working_time_total = $this->working_session_model->get_total_time($worker_id, $w_date);
        $task_time_model = $this->get_total_est_time($worker_id, $workboard->work_board_id);
        $est_hr = ($working_time_total - $task_time_model) / 3600;
        if ($est_hr == 0) {
            $this->delete([
                'worker_id' => $worker_id,
                'work_board_id' => $workboard->work_board_id,
                'task_id' => -1
            ]);
            $this->delete([
                'worker_id' => $worker_id,
                'work_board_id' => $workboard->work_board_id,
                'task_id' =>-2
            ]);
            return;
        }
        
        $this->delete([
            'worker_id' => $worker_id,
            'work_board_id' => $workboard->work_board_id,
            'task_id' => $est_hr > 0 ? -2 : -1
        ]);
        
        $free_task_time = $this->get([
            'worker_id' => $worker_id,
            'work_board_id' => $workboard->work_board_id,
            'task_id' => $est_hr > 0 ? -1 : -2
        ]);
        
        if ($est_hr > 0) {
            $sortorder = 0;
        } else {
            $sortorder = $this->getMaxSortOrder($worker_id, $workboard->work_board_id, null);
        }
        
        if ($free_task_time) {
            $this->update(
                ['est_hr' => number_format($est_hr, 2), 'sortorder' => $sortorder],
                ['wb_task_id' => $free_task_time->wb_task_id]
            );
            
        } else {
            if ($worker) {
                $worker_department = $this->worker_department_model->get([
                    'worker_id' => $worker->worker_id,
                    'department_id' => $workboard->d_id
                ]);
                
                $this->insert([
                    'worker_id' => $worker_id,
                    'task_id' => $est_hr > 0 ? -1 : -2,
                    'est_hr' => number_format($est_hr, 2),
                    'task_notes' => 'free task working time',
                    'work_board_id' => $workboard->work_board_id,
                    'group_id' => $worker->group_id,
                    'workers_department_id' => $worker_department ? $worker_department->worker_department_id : NULL,
                    'sortorder' => $sortorder
                ]);
            }
        }
    }
    
    public function getMaxSortOrder($worker_id, $work_board_id, $sb_id = null)
    {
        $this->db->select('MAX(sortorder) AS max_sortorder');
        $this->db->from('work_board_task');
        $this->db->where('worker_id', $worker_id);
        $this->db->where('work_board_id', $work_board_id);
        $this->db->where('task_id > 0');
        if ($sb_id) {
            $this->db->where('sb_id', $sb_id);
        }
        $query = $this->db->get();
        
        if ($res = $query->result()) {
            $max_sortorder = $res[0];
            return $max_sortorder->max_sortorder + 1;
        }
        
        return 1;
    }
}