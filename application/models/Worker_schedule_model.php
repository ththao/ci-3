<?php
class Worker_schedule_model extends MY_Model
{
    public $table = 'worker_schedule';
    
    public function pull_schedules($worker_id, $start, $end)
    {
        $result = [];
        $start_loop = strtotime($start);
        while ($start_loop <= strtotime($end)) {
            $result[date('Y-m-d', $start_loop)] = array();
            $start_loop = strtotime('+1 days', $start_loop);
        }
        
        $this->db->select('worker_schedule.*');
        $this->db->from('worker_schedule');
        $this->db->where('worker_id', $worker_id);
        $this->db->where('s_date >= ', $start);
        $this->db->where('s_date <= ', $end);
        $this->db->where('(deleted_at IS NULL OR deleted_at = "")', NULL);
        $this->db->order_by('s_date');
        $query = $this->db->get();
        $schedules = $query->result();
        
        if ($schedules) {
            foreach ($schedules as $schedule) {
                if (isset($result[$schedule->s_date])) {
                    if (!isset($result[$schedule->s_date]['schedules'])) {
                        $result[$schedule->s_date]['schedules'] = array();
                    }
                    
                    $result[$schedule->s_date]['schedules'][] = $schedule;
                }
            }
        }
        
        $this->db->select('work_board.w_date, COUNT(work_board_task.wb_task_id) AS task_cnt');
        $this->db->from('work_board_task');
        $this->db->join('work_board', 'work_board.work_board_id = work_board_task.work_board_id', 'INNER');
        $this->db->where('work_board_task.worker_id', $worker_id);
        $this->db->where('work_board_task.task_id >= ', 0);
        $this->db->where('work_board.w_date >= ', $start);
        $this->db->where('work_board.w_date <= ', $end);
        $this->db->group_by('work_board.w_date');
        $this->db->order_by('work_board.w_date');
        $query = $this->db->get();
        $tasks = $query->result();
        
        if ($tasks) {
            foreach ($tasks as $task) {
                if (isset($result[$task->w_date])) {
                    if (!isset($result[$task->w_date]['tasks'])) {
                        $result[$task->w_date]['tasks'] = 1;
                    }
                }
            }
        }
        
        $this->db->select('id, request_date, status');
        $this->db->from('off_requests');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $this->db->where('request_date >= ', $start);
        $this->db->where('request_date <= ', $end);
        $query = $this->db->get();
        $off_requests = $query->result();
        
        if ($off_requests) {
            foreach ($off_requests as $off_request) {
                if (isset($result[$off_request->request_date])) {
                    if (!isset($result[$off_request->request_date]['off_request'])) {
                        $result[$off_request->request_date]['off_request'] = $off_request;
                    }
                }
            }
        }
        
        $this->db->select('s_date, note');
        $this->db->from('schedule_notes');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $this->db->where('s_date >= ', $start);
        $this->db->where('s_date <= ', $end);
        $query = $this->db->get();
        $schedule_notes = $query->result();
        
        if ($schedule_notes) {
            foreach ($schedule_notes as $schedule_note) {
                if (isset($result[$schedule_note->s_date])) {
                    if (!isset($result[$schedule_note->s_date]['schedule_notes'])) {
                        $result[$schedule_note->s_date]['schedule_notes'] = array();
                    }
                    $result[$schedule_note->s_date]['schedule_notes'][] = $schedule_note;
                }
            }
        }
        
        $this->db->select('id, note_date');
        $this->db->from('off_request_notes');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $this->db->where('note_date >= ', $start);
        $this->db->where('note_date <= ', $end);
        $query = $this->db->get();
        $off_request_notes = $query->result();
        
        if ($off_request_notes) {
            foreach ($off_request_notes as $off_request_note) {
                if (isset($result[$off_request_note->note_date])) {
                    if (!isset($result[$off_request_note->note_date]['notes'])) {
                        $result[$off_request_note->note_date]['notes'] = 1;
                    }
                }
            }
        }
        
        return $result;
    }
}