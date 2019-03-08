<?php
class Working_session_model extends MY_Model
{
    public $table = 'working_session';
    
    /**
     * Main CLOCK IN
     * @return []
     */
    public function clock_in()
    {
        $this->db->trans_begin();
        
        $mobile = $this->session->userdata('mobile');
        if ($mobile) {
            $this->load->model('worker_permission_model');
            $permission = $this->worker_permission_model->get(['worker_id' => $this->session->userdata('worker_id')]);
            if (!$permission->start_work_session) {
                return ['status' => 0, 'message' => 'You have to clock in from an employee kiosk.'];
            }
        }
        
        $this->end_working_session($this->session->userdata('worker_id'), null);
        
        $this->start_working_session();
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to clock in, please try again!'];
        } else {
            $this->db->trans_commit();
            
            return array_merge(['status' => 1], $this->get_tracked_time(date_by_timezone(time(), 'Y-m-d')));
        }
    }
    
    /**
     * Start a working session
     */
    private function start_working_session()
    {
        $this->db->select('c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        
        if ($workers) {
            $worker = $workers[0];
            $this->db->select('work_board_id');
            $this->db->from('work_board');
            $this->db->where('c_id', $worker->c_id);
            $this->db->where('d_id', $worker->d_id);
            $this->db->where('w_date', date_by_timezone(time(), 'Y-m-d'));
            $query = $this->db->get();
            
            if (!$query->result()) {
                $this->db->insert('work_board', [
                    'c_id' => $worker->c_id,
                    'd_id' => $worker->d_id,
                    'w_date' => date_by_timezone(time(), 'Y-m-d')
                ]);
            }
        }
        
        $this->insert([
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'start_time' => time(),
            'start_time_input_type' => 0
        ]);
    }
    
    /**
     * on_going is a field to temporarily track working time
     * this will be used in the cases that connection is lost
     * @return []
     */
    public function working()
    {
        // Get working session, if can not find then reload page
        $ws = $this->get([
            'remove' => 0,
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'worker_id' => $this->session->userdata('worker_id'),
            'end_time' => NULL,
            'start_time IS NOT ' => NULL,
            'start_time <= ' => time()
        ]);
        if (!$ws) {
            return ['status' => 0, 'reload' => 1];
        }
        
        // Update on working
        $this->update(['on_working' => time()], [
            'remove' => 0,
            'working_session_id' => $ws->working_session_id
        ]);
        
        return ['status' => 1];
    }
    
    /**
     * Main CLOCK OUT
     */
    public function clock_out($worker_id, $clock_out = null)
    {
        $mobile = $this->session->userdata('mobile');
        if ($mobile) {
            $this->load->model('worker_permission_model');
            $permission = $this->worker_permission_model->get(['worker_id' => $this->session->userdata('worker_id')]);
            if (!$permission->start_work_session) {
                return ['status' => 0, 'message' => 'You have to clock out from an employee kiosk.'];
            }
        }
        
        if (!$worker_id) {
            return ['status' => 1];
        }
        $this->db->trans_begin();
        
        $this->end_working_session($worker_id, $clock_out);
        
        $this->load->model('work_board_task_model');
        $this->work_board_task_model->create_free_task_time($worker_id, date_by_timezone(time(), 'Y-m-d'));
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to clock out, please try again!'];
        } else {
            $this->db->trans_commit();
            
            if ($this->session->has_userdata('worker_id')) {
                return array_merge(['status' => 1], $this->get_tracked_time(date_by_timezone(time(), 'Y-m-d')));
            }
            
            return ['status' => 1];
        }
    }
    
    /**
     * Stop a working session
     * @param int $clock_out
     */
    private function end_working_session($worker_id, $clock_out = null)
    {
        $clock_out = $clock_out ? $clock_out : time();
        
        // End all working tasks
        $this->load->model(['actual_time_keeping_model']);
        $this->actual_time_keeping_model->update(
            ['end_time' => $clock_out, 'end_time_input_type' => 0],
            ['worker_id' => $worker_id, 'end_time' => NULL, 'start_time IS NOT ' => NULL, 'remove' => 0]
        );
        
        $this->actual_time_keeping_model->useActualTime(true);
        
        // End working session
        $this->update(
            ['end_time' => $clock_out, 'end_time_input_type' => 0],
            [
                'remove' => 0,
                'worker_id' => $worker_id,
                'working_date = ' => date_by_timezone(time(), 'Y-m-d'),
                'start_time IS NOT ' => NULL,
                'end_time' => NULL
            ]
        );
        
        if ($this->isFeatureActive('overtime')) {
            $this->db->select('work_board.c_id, work_board.d_id');
            $this->db->from('work_board');
            $this->db->join('workers', 'workers.c_id = work_board.c_id');
            $this->db->where('work_board.w_date', date('Y-m-d'));
            $this->db->where('workers.worker_id', $worker_id);
            
            $query = $this->db->get();
            $work_boards = $query->result();
            
            if ($work_boards) {
                foreach ($work_boards as $work_board) {
                    $c_id = $work_board->c_id;
                    $file = "/home/asbdev/public_html/memberdata/$c_id/info/presets.xml";
                    if (is_file($file)) {
                        $companyXML = simplexml_load_file($file);
                        
                        if ($this->getPresetData($companyXML, 'taskTracker->department_' . $work_board->d_id . '->startweek') !== false) {
                            $startofWeek = $this->getPresetData($companyXML, 'taskTracker->department_' . $work_board->d_id . '->startweek');
                        } else {
                            $startofWeek = $this->getPresetData($companyXML, 'taskTracker->startweek');
                        }
                        if (! $startofWeek) {
                            $startofWeek = 0;
                        }
                    } else {
                        $startofWeek = 0;
                    }
                    
                    $dayoftheWeek = date('w', time());
                    $dayoftheWeek = $dayoftheWeek < $startofWeek ? ($dayoftheWeek + 7) : $dayoftheWeek;
                    $startdate = date('Y-m-d', strtotime(($startofWeek - $dayoftheWeek) . " day", time()));
                    
                    $this->db->select('id');
                    $this->db->from('overtime_clock_cron');
                    $this->db->where('c_id', $c_id);
                    $this->db->where('start_date', $startdate);
                    
                    $query = $this->db->get();
                    $crons = $query->result();
                    if (!$crons) {
                        $this->db->insert('overtime_clock_cron', [
                            'c_id' => $c_id,
                            'start_date' => $startdate
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Get total tracked time for a day $date
     * @param $date
     * @return []
     */
    public function get_tracked_time($date)
    {
        $data = [
            'countdown' => strtotime(date_by_timezone(time(), 'Y-m-d 23:59:59')) -
            strtotime(date_by_timezone(time(), 'Y-m-d H:i:s')),
            'clock_in' => '',
            'hour' => 0,
            'minute' => 0,
            'second' => 0
        ];
        
        $this->default_select('SUM(COALESCE(end_time, ' . time() . ') - start_time) AS total');
        $tracker = $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date' => $date,
            'start_time IS NOT ' => NULL
        ]);
        
        if ($tracker) {
            $data['hour'] = floor(intval($tracker->total) / 3600);
            $data['minute'] = floor((intval($tracker->total) % 3600) / 60);
            $data['second'] = intval($tracker->total) - $data['hour'] * 3600 - $data['minute'] * 60;
            
            $working = $this->get([
                'remove' => 0,
                'worker_id' => $this->session->userdata('worker_id'),
                'working_date' => $date,
                'start_time IS NOT ' => NULL,
                'end_time' => NULL
            ]);
            
            if ($working) {
                $data['clock_in'] = date_format_by_timezone($working->start_time, 'h:i:s A');
            }
        }
        
        return $data;
    }
    
    /**
     * Add time manually
     * @param int $wb_task_id
     * @param int $start_time
     * @param int $end_time
     * @param string $note
     * @return []
     */
    public function add_manual_time($wb_task_id, $start_time, $end_time, $note)
    {
        if (!$wb_task_id) {
            return ['status' => 0, 'message' => 'There was an error while trying to add manual time. Please try again.'];
        }
        
        // Check available task
        $this->load->model('work_board_task_model');
        $wb_task = $this->work_board_task_model->get([
            'wb_task_id' => $wb_task_id,
            'worker_id' => $this->session->userdata('worker_id')
        ]);
        if (!$wb_task) {
            return ['status' => 0, 'message' => 'This task is no longer available'];
        }
        
        // Validate required start time and end time
        if (!$start_time || !$end_time) {
            $message = '';
            $message = !$start_time ? 'Start time' : '';
            $message = $message . ($message ? ', ' : '') . (!$end_time ? 'End time' : '') . ' can not be blank.';
            return ['status' => 0, 'message' => $message];
        }
        
        // Validate start time <= end time
        $start_time = strtotime(date_by_timezone(time(), 'Y-m-d') . ' ' . $start_time);
        $end_time = strtotime(date_by_timezone(time(), 'Y-m-d') . ' ' . $end_time);
        
        if ($start_time > $end_time) {
            return ['status' => 0, 'message' => 'Start time must be less than End time.'];
        }
        
        if ($start_time > time()) {
            return array('status' => 0, 'message' => "It's " . date_by_timezone(time(), 'H:i:s') . " now. Please do not add time in future.");
        } else if ($end_time > time()) {
            $having_hours = number_format((time() - $start_time)/3600, 2);
            return array('status' => 0, 'message' => "It's " . date_by_timezone(time(), 'H:i:s') . " now. You have " . $having_hours . "hr(s) only. Please do not add time in future.");
        }
        
        // Check overlap task times
        $this->load->model('actual_time_keeping_model');
        $logged_times = $this->actual_time_keeping_model->get_logged_times(date_by_timezone(time(), 'Y-m-d'));
        foreach ($logged_times as $record) {
            if (($record->start_time <= $start_time && (!$record->end_time || $record->end_time > $start_time)) ||
                ($record->start_time < $end_time && (!$record->end_time || $record->end_time > $end_time)) ||
                ($start_time <= $record->start_time && $end_time > $record->start_time) ||
                ($start_time < $record->end_time && $end_time >= $record->end_time)) {
                    return ['status' => 0, 'message' => 'Please check overlap with logged time.'];
                }
        }
        
        $this->db->trans_begin();
        
        // Update working sessions
        $this->add_manual_working_session($start_time, $end_time);
        
        // Insert manual actual keeping
        $this->load->model(['actual_time_keeping_model']);
        $this->actual_time_keeping_model->insert([
            'worker_id' => $this->session->userdata('worker_id'),
            'workboard_task_id' => $wb_task_id,
            'start_time_input_type' => 1,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'manual_hours' => number_format(($end_time - $start_time)/3600, 2),
            'clock_type' => 2  //Manual
        ]);
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to add manual time, please try again!'];
        } else {
            $this->db->trans_commit();
            
            return ['status' => 1];
        }
    }
    
    /**
     * Create working session manually
     * @param int $start_time
     * @param int $end_time
     */
    private function add_manual_working_session($start_time, $end_time)
    {
        $working = $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'start_time <= ' => $start_time,
            'end_time IS NULL OR end_time >= ' => $end_time
        ]);
        if ($working) {
            return;
        }
        
        $this->update(
            ['remove' => 1],
            [
                'worker_id' => $this->session->userdata('worker_id'),
                'working_date' => date_by_timezone(time(), 'Y-m-d'),
                'start_time >= ' => $start_time,
                'end_time <= ' => $end_time,
                'end_time IS NOT ' => NULL
            ]
            );
        
        $this->update(
            ['end_time' => $start_time - 1],
            [
                'remove' => 0,
                'worker_id' => $this->session->userdata('worker_id'),
                'working_date' => date_by_timezone(time(), 'Y-m-d'),
                'start_time < ' => $start_time,
                'end_time >= ' => $start_time,
                'end_time IS NOT ' => NULL
            ]
            );
        
        $this->update(
            ['start_time' => $end_time + 1],
            [
                'remove' => 0,
                'worker_id' => $this->session->userdata('worker_id'),
                'working_date' => date_by_timezone(time(), 'Y-m-d'),
                'start_time <= ' => $end_time,
                'end_time IS NULL OR end_time > ' => $end_time
            ]
            );
        
        $this->insert([
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'start_time_input_type' => 1,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'clock_type' => '2'  //Manual
        ]);
    }
    
    /**
     * Get missing punch working session
     * @return Object
     */
    public function missing_punch()
    {
        return $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date < ' => date_by_timezone(time(), 'Y-m-d'),
            'start_time IS NOT ' => NULL,
            'end_time' => NULL
        ]);
    }
    
    /**
     * Update missing punch
     */
    public function save_missing_punch($working_session_id, $working_session_end, $time_keeping_id, $time_keeping_end)
    {
        if (!$working_session_end) {
            return ['status' => 0, 'message' => 'End time of working session can not be blank.'];
        }
        if ($working_session_id) {
            $working_session = $this->get(['working_session_id' => $working_session_id]);
        } else {
            $working_session = $this->missing_punch();
        }
        if (!$working_session) {
            return ['status' => 1];
        }
        
        $working_session_end = strtotime($working_session->working_date . ' ' . $working_session_end);
        
        if ($working_session_end <= $working_session->start_time) {
            return ['status' => 0, 'message' => 'End time of working session must be after Start time.'];
        }
        
        $this->load->model('actual_time_keeping_model');
        if ($time_keeping_id) {
            $time_keeping = $this->actual_time_keeping_model->get(['time_id' => $time_keeping_id, 'remove' => 0]);
        } else {
            $time_keeping = $this->actual_time_keeping_model->missing_punch($working_session->working_date);
        }
        if ($time_keeping) {
            if (!$time_keeping_end) {
                return ['status' => 0, 'message' => 'End time of task can not be blank.'];
            }
            
            $time_keeping_end = strtotime($working_session->working_date . ' ' . $time_keeping_end);
            if ($time_keeping_end <= $time_keeping->start_time) {
                return ['status' => 0, 'message' => 'End time of task must be after Start time.'];
            }
            
            if ($time_keeping_end > $working_session_end) {
                return ['status' => 0, 'message' => 'End time of task must be less than End time of working session.'];
            }
        }
        
        $this->db->trans_begin();
        
        $this->update(
            ['end_time' => $working_session_end, 'end_time_input_type' => 1],
            ['working_session_id' => $working_session->working_session_id, 'remove' => 0]
            );
        
        if ($time_keeping) {
            $this->actual_time_keeping_model->update(
                ['end_time' => $time_keeping_end, 'end_time_input_type' => 1],
                ['time_id' => $time_keeping->time_id, 'remove' => 0]
                );
        }
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to update missing punch, please try again!'];
        } else {
            $this->db->trans_commit();
            
            return ['status' => 1];
        }
    }
    
    /**
     * Update missing punch
     */
    public function skip_missing_punch($working_session_id, $time_keeping_id)
    {
        $this->load->model(['work_board_model', 'actual_time_keeping_model']);
        if ($working_session_id) {
            $working_session = $this->get(['working_session_id' => $working_session_id]);
        } else {
            $working_session = $this->missing_punch();
        }
        if (!$working_session) {
            return ['status' => 1];
        }
        
        if ($time_keeping_id) {
            $time_keeping = $this->actual_time_keeping_model->get(['time_id' => $time_keeping_id, 'remove' => 0]);
        } else {
            $time_keeping = $this->actual_time_keeping_model->missing_punch($working_session->working_date);
        }
        
        $working_session_end = strtotime($working_session->working_date . ' 23:59:59');
        $time_keeping_end = $working_session_end;
        
        $this->db->trans_begin();
        
        $this->update(
            ['end_time' => $working_session_end, 'end_time_input_type' => 1],
            ['working_session_id' => $working_session->working_session_id, 'remove' => 0]
            );
        
        if ($time_keeping) {
            $this->actual_time_keeping_model->update(
                ['end_time' => $time_keeping_end, 'end_time_input_type' => 1],
                ['time_id' => $time_keeping->time_id, 'remove' => 0]
                );
        }
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to update missing punch, please try again!'];
        } else {
            $this->db->trans_commit();
            
            return ['status' => 1];
        }
    }
    
    public function get_total_time($worker_id, $w_date)
    {
        $this->db->select('SUM(end_time - start_time) AS total_time');
        $this->db->from('working_session');
        $this->db->where('worker_id', $worker_id);
        $this->db->where('remove', 0);
        $this->db->where('end_time IS NOT ', NULL);
        $this->db->where('working_date', date_by_timezone(strtotime($w_date), 'Y-m-d'));
        
        $query = $this->db->get();
        if ($res = $query->result()) {
            $total = $res[0];
            
            return $total->total_time;
        }
        return 0;
    }
    
    public function pull_clock_in_times($worker_id, $start, $end)
    {
        $result = [];
        $start_loop = strtotime($start);
        while ($start_loop < strtotime($end)) {
            $result[date('Y-m-d', $start_loop)] = [];
            $start_loop = strtotime('+1 days', $start_loop);
        }
        
        $times = $this->get_all([
            'worker_id' => $worker_id,
            'working_date >= ' => $start,
            'working_date < ' => $end,
            'remove' => 0
        ]);
        if ($times) {
            foreach ($times as $time) {
                $result[$time->working_date][] = $time;
            }
        }
        return $result;
    }
}