<?php
class Actual_time_keeping_model extends MY_Model
{
    public $table = 'actual_time_keeping';
    
    
    /**
     * Start time tracking
     * @param int $wb_task_id
     * @return []
     */
    public function start($wb_task_id)
    {
        $this->db->trans_begin();
        
        $mobile = $this->session->userdata('mobile');
        if ($mobile) {
            $this->load->model('worker_permission_model');
            $permission = $this->worker_permission_model->get(['worker_id' => $this->session->userdata('worker_id')]);
            if (!$permission->start_task) {
                return ['status' => 0, 'message' => 'In order to clock in and out of daily tasks you will need to use an employee kiosk.'];
            }
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
        
        // Check available working session
        $this->load->model('working_session_model');
        $ws = $this->working_session_model->get([
            'remove' => 0,
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'worker_id' => $this->session->userdata('worker_id'),
            'end_time' => NULL,
            'start_time IS NOT ' => NULL,
            'start_time <= ' => time()
        ]);
        if (!$ws) {
            $ws_ret = $this->working_session_model->clock_in();
            if (!$ws_ret['status']) {
                return $ws_ret;
            }
        }
        
        // Employee is working on a task
        $tracker = $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'end_time' => NULL,
            'start_time IS NOT ' => NULL
        ]);
        if ($tracker) {
            if ($tracker->workboard_task_id == $wb_task_id) {
                $ret = [
                    'status' => 1,
                    'workboard' => $this->working_session_model->get_tracked_time(date_by_timezone(time(), 'Y-m-d')),
                    'task' => array_merge(['time_id' => $tracker->time_id], $this->get_tracked_time($wb_task_id))
                ];
                return $ret;
            } else {
                //return ['status' => 0, 'message' => "You having an on-going task, please stop before starting new task."];
                $stop_track_ret = $this->stop($tracker->time_id);
                if (!$stop_track_ret['status']) {
                    return ['status' => 0, 'message' => 'There was an error while trying to stop on-going task. Please try again.'];
                }
            }
        }
        
        // Start new time keeping for a task
        $time_id = $this->insert([
            'worker_id' => $this->session->userdata('worker_id'),
            'workboard_task_id' => $wb_task_id,
            'start_time_input_type' => 0,
            'start_time' => time(),
            'clock_type' => 1
        ]);
        
        $this->useActualTime(false);
        
        if (!$time_id || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            return ['status' => 0, 'message' => 'There was an error while trying to start task, please try again!'];
        } else {
            $this->db->trans_commit();
            
            $ret = [
                'status' => 1,
                'workboard' => $this->working_session_model->get_tracked_time(date_by_timezone(time(), 'Y-m-d')),
                'task' => array_merge(['time_id' => $time_id], $this->get_tracked_time($wb_task_id))
            ];
            return $ret;
        }
    }
    
    public function useActualTime($update_time = false)
    {
        if ($update_time) {
            $this->db->select('work_board_task.wb_task_id');
            $this->db->from('work_board_task');
            $this->db->join('work_board', 'work_board.work_board_id = work_board_task.work_board_id', 'INNER');
            $this->db->join('settings', 'settings.c_id = work_board.c_id AND settings.d_id = work_board.d_id AND settings.settings_name = "use_actual" AND settings.settings_value = "1"', 'INNER');
            $this->db->where('work_board_task.worker_id', $this->session->userdata('worker_id'));
            $this->db->where('work_board.w_date', date('Y-m-d'));
            
            $query = $this->db->get();
            $tasks = $query->result();
            
            if ($tasks) {
                foreach ($tasks as $task) {
                    $this->db->select('SUM(end_time - start_time) AS total_time');
                    $this->db->from('actual_time_keeping');
                    $this->db->where('worker_id', $this->session->userdata('worker_id'));
                    $this->db->where('workboard_task_id', $task->wb_task_id);
                    $this->db->where('remove', 0);
                    $this->db->limit(1);
                    $query1 = $this->db->get();
                    
                    $time = $query1->result();
                    if ($time) {
                        $time = $time[0];
                        if ($time->total_time >= 0) {
                            $this->db->where('wb_task_id', $task->wb_task_id);
                            $this->db->update('work_board_task', array('est_act' => 1, 'est_hr' => number_format($time->total_time / 3600, 2)));
                            
                            $this->equipmentHours($task->wb_task_id);
                        }
                    }
                }
                
            }
        } else {
            $sql = '
                UPDATE work_board_task
                INNER JOIN work_board ON work_board.work_board_id = work_board_task.work_board_id
                INNER JOIN settings ON settings.c_id = work_board.c_id AND settings.d_id = work_board.d_id 
                AND settings.settings_name = "use_actual" AND settings.settings_value = "1"
                SET work_board_task.est_act = 1
                WHERE work_board_task.worker_id = ' . $this->session->userdata('worker_id') . '
                AND work_board.w_date = "' . date('Y-m-d') . '"
            ';
            $this->db->query($sql);
        }
    }
    
    private function equipmentHours($wb_task_id)
    {
        $this->db->distinct();
        $this->db->select('id, equipment_id, wb_task_id');
        $this->db->from('work_board_task_equipments');
        $this->db->where('wb_task_id', $wb_task_id);
        
        $query = $this->db->get();
        $data = $query->result();
        
        if ($data) {
            foreach ($data as $item) {
                $this->updateEquipmentHours($item->equipment_id, $item->wb_task_id);
            }
        }
    }
    
    private function updateEquipmentHours($equipment_id, $wb_task_id)
    {
        $this->db->select('work_per_hour');
        $this->db->from('equipment');
        $this->db->join('equipment_type', 'equipment_type.id = equipment.equipment_type_id', 'INNER');
        $this->db->where('equipment.id', $equipment_id);
        $this->db->limit(1);
        $query = $this->db->get();
        $e_data = $query->result();
        
        $this->db->select('est_hr, before_est_hr');
        $this->db->from('work_board_task');
        $this->db->where('wb_task_id', $wb_task_id);
        $this->db->limit(1);
        $query = $this->db->get();
        $t_data = $query->result();
        
        if ($e_data && $t_data) {
            $equipment = $e_data[0];
            $wb_task = $t_data[0];
            
            if ($wb_task->est_hr != $wb_task->before_est_hr) {
                $this->db->select('id');
                $this->db->from('work_board_task_equipments');
                $this->db->where('wb_task_id', $wb_task_id);
                $this->db->where('equipment_id', $equipment_id);
                $this->db->limit(1);
                $query = $this->db->get();
                $te_data = $query->result();
                
                if ($te_data) {
                    $equipment_hours = floatval($wb_task->est_hr) * floatval($equipment->work_per_hour);
                    if ($wb_task->before_est_hr) {
                        $equipment_hours = $equipment_hours - floatval($wb_task->before_est_hr) * floatval($equipment->work_per_hour);
                    }
                } else {
                    $equipment_hours = 0 - floatval($wb_task->est_hr) * floatval($equipment->work_per_hour);
                }
                
                $this->db->insert('equipment_update', [
                    'equipment_id' => $equipment_id,
                    'worker_id' => $this->session->userdata('worker_id'),
                    'equipment_hours' => $equipment_hours,
                    'actual' => 0,
                    'update_date' => time(),
                    'starting_hr' => 0
                ]);
                
                $this->db->where('wb_task_id', $wb_task_id);
                $this->db->update('work_board_task', ['before_est_hr' => $wb_task->est_hr]);
            }
        }
    }
    
    /**
     * Stop time tracking
     * @param int $time_id
     * @param int $timer
     * @return []
     */
    public function stop($time_id, $timer = null)
    {
        $this->db->trans_begin();
        
        $mobile= $this->session->userdata('mobile');
        if ($mobile) {
            $this->load->model('worker_permission_model');
            $permission = $this->worker_permission_model->get(['worker_id' => $this->session->userdata('worker_id')]);
            if (!$permission->start_task) {
                return ['status' => 0, 'message' => 'In order to clock in and out of daily tasks you will need to use an employee kiosk.'];
            }
        }
        
        $tracker = $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'time_id' => $time_id,
            'end_time' => NULL,
            'start_time IS NOT ' => NULL
        ]);
        
        if ($tracker) {
            $this->load->model(['work_board_task_model', 'work_board_model']);
            $work_board_task = $this->work_board_task_model->get(['wb_task_id' => $tracker->workboard_task_id]);
            if ($work_board_task && $work_board_task->est_act == 1) {
                $actual_time = $this->get_tracked_time($work_board_task->wb_task_id, 0);
                $est_hr = number_format($actual_time / 3600, 2);
                $this->work_board_task_model->update(['est_hr' => $est_hr], ['wb_task_id' => $work_board_task->wb_task_id]);
            }
            
            if ($timer) {
                $this->update(['end_time' => $tracker->start_time + $timer, 'end_time_input_type' => 0], ['time_id' => $time_id]);
            } else {
                $this->update(['end_time' => time(), 'end_time_input_type' => 0], ['time_id' => $time_id]);
            }
            

            if ($this->isFeatureActive('overtime')) {
                $work_board = $this->work_board_model->get(['work_board_id' => $work_board_task->work_board_id]);
                if ($work_board) {
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
        } else {
            return ['status' => 1];
        }
        
        $this->useActualTime(true);
        
        if (!$time_id || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        
            return ['status' => 0, 'message' => 'There was an error while trying to stop task, please try again!'];
        } else {
            $this->db->trans_commit();
        
            return ['status' => 1];
        }
    }
    
    /**
     * Get total tracked time
     * @param int $wb_task_id
     * @param int $by_array
     * @return []
     */
    public function get_tracked_time($wb_task_id, $by_array = 1)
    {
        $this->default_select('SUM(COALESCE(end_time, ' . time() . ') - start_time) AS total');
        $tracker = $this->get([
            'remove' => 0,
            'worker_id' => $this->session->userdata('worker_id'),
            'workboard_task_id' => $wb_task_id,
            'start_time IS NOT ' => NULL
        ]);
        
        if ($tracker) {
            if ($by_array) {
                $hour = floor(intval($tracker->total) / 3600);
                $minute = floor((intval($tracker->total) % 3600) / 60);
                
                return ['hour' => $hour, 'minute' => $minute, 'second' => intval($tracker->total) - $hour * 3600 - $minute * 60, 'time' => $tracker->total];
            } else {
                return intval($tracker->total);
            }
        }
        
        if ($by_array) {
            return ['hour' => 0, 'minute' => 0, 'second' => 0, 'time' => 0];
        } else {
            return 0;
        }
    }
    
    /**
     * Get missing punch actual time keeping
     * @param string $working_date
     * @return Object
     */
    public function missing_punch($working_date)
    {
        $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time, tasks.task_name');
        $this->db->from('actual_time_keeping');
        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id');
        $this->db->join('tasks', 'tasks.task_id = work_board_task.task_id');
        $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id');
        $this->db->where(['actual_time_keeping.worker_id' => $this->session->userdata('worker_id')]);
        $this->db->where(['actual_time_keeping.start_time IS NOT ' => NULL]);
        $this->db->where(['actual_time_keeping.end_time' => NULL]);
        $this->db->where(['work_board.w_date' => $working_date, 'actual_time_keeping.remove' => 0]);
        $query = $this->db->get();
        $res = $query->result();
        
        return $res ? $res[0] : null;
    }
    
    public function get_logged_times($view_date)
    {
        $this->db->from('actual_time_keeping');
        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
        $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id', 'INNER');
        $this->db->where(['actual_time_keeping.worker_id' => $this->session->userdata('worker_id'), 'actual_time_keeping.remove' => 0]);
        $this->db->where(['work_board.w_date' => $view_date]);
        $this->db->order_by('actual_time_keeping.start_time');
        $query = $this->db->get();
        
        return $query->result();
    }
    
    public function missing_punch_by_work_board($work_board_id)
    {
        $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time, tasks.task_name');
        $this->db->from('actual_time_keeping');
        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id');
        $this->db->join('tasks', 'tasks.task_id = work_board_task.task_id');
        $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id');
        $this->db->where(['actual_time_keeping.worker_id' => $this->session->userdata('worker_id')]);
        $this->db->where(['actual_time_keeping.start_time IS NOT ' => NULL]);
        $this->db->where(['actual_time_keeping.end_time' => NULL]);
        $this->db->where(['work_board.work_board_id' => $work_board_id, 'actual_time_keeping.remove' => 0]);
        $query = $this->db->get();
        $res = $query->result();
    
        return $res ? $res[0] : null;
    }
}