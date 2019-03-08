<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->session->has_userdata('worker_id')) {
            $relog = $this->loginBySessionHash(isset($_POST['mobile']) ? $_POST['mobile'] : 0);
            if (!$relog) {
                echo json_encode(['status' => 0, 'reload' => 1]);
                exit();
            }
        }
        
        $this->load->model(['work_board_task_model', 'working_session_model', 'actual_time_keeping_model']);
        
        $this->sync_with_cookies();
    }
    
    /**
     * Notify on-working tasks
     */
    public function working()
    {
        echo json_encode($this->working_session_model->working());
    }
    
    /**
     * Click CLOCK IN
     */
    public function clock_in()
    {
        echo json_encode($this->working_session_model->clock_in());
    }
    
    /**
     * Start an individual task
     */
    public function start_task()
    {
        echo json_encode($this->actual_time_keeping_model->start($this->input->post('wb_task_id', true)));
    }
    
    /**
     * Click CLOCK OUT
     */
    public function clock_out()
    {
        echo json_encode($this->working_session_model->clock_out($this->session->userdata('worker_id'), time()));
    }
    
    /**
     * Stop an individual task
     */
    public function stop_task()
    {
        echo json_encode($this->actual_time_keeping_model->stop($this->input->post('time_id', true), $this->input->post('timer', true)));
    }
    
    /**
     * Load Sidebar section
     */
    public function load_sidebar()
    {
        // Get past working sessions for previous 14 days
        $past_times = $this->working_session_model->order_by('start_time', 'DESC')->get_all([
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date < ' => date_by_timezone(time(), 'Y-m-d'),
            'working_date >= ' => date_by_timezone(strtotime('- 14 days'), 'Y-m-d'),
            'start_time IS NOT ' => NULL,
            'end_time IS NOT ' => NULL,
            'remove' => 0
        ]);
        $pasttimes_html = $this->load->view('workboard/partials/past_times', ['past_times' => $past_times], true);
        
        // Get workboard tasks for next 4 days
        $this->load->model('worker_schedule_model');
        $schedules = $this->worker_schedule_model->pull_schedules($this->session->userdata('worker_id'), date_by_timezone(time(), 'Y-m-d'), date_by_timezone(strtotime('+13 days', time()), 'Y-m-d'));
        //echo '<pre>'.print_r($schedules,1).'</pre>';
        $schedules_html = $this->load->view('workboard/partials/schedules', ['schedules' => $schedules], true);
        
        // Get todays sessions
        $todays = $this->working_session_model->order_by('start_time', 'DESC')->get_all([
            'worker_id' => $this->session->userdata('worker_id'),
            'working_date = ' => date_by_timezone(time(), 'Y-m-d'),
            'start_time IS NOT ' => NULL,
            'end_time IS NOT ' => NULL,
            'remove' => 0
        ]);
        $todays_html = $this->load->view('workboard/partials/todays_times', ['todays' => $todays], true);
        
        echo json_encode([
            'status' => 1,
            'pasttimes_html' => $pasttimes_html,
            'schedules_html' => $schedules_html,
            'todays_html' => $todays_html
        ]);
    }
    
    /**
     * Update working session of previous day
     */
    public function save_missing_punch()
    {
        echo json_encode($this->working_session_model->save_missing_punch(
            $this->input->post('working_session_id', true),
            $this->input->post('working_session_end_time', true),
            $this->input->post('time_keeping_id', true),
            $this->input->post('time_keeping_end_time', true)
        ));
    }
    
    /**
     * Update working session of previous day
     */
    public function skip_missing_punch()
    {
        echo json_encode($this->working_session_model->skip_missing_punch(
            $this->input->post('working_session_id', true),
            $this->input->post('time_keeping_id', true)
        ));
    }
    
    /**
     * Add manual time
     */
    public function save_manual_time()
    {
        $start_time = $this->input->post('start_time', true);
        $end_time = $this->input->post('end_time', true);
        $number_of_hours = $this->input->post('number_of_hours', true);
        if (!$start_time && !$end_time && !$number_of_hours) {
            echo json_encode(array('status' => 0, 'message' => 'Please select time to log.'));
            exit();
        }
        
        if (!$start_time && !$end_time && $number_of_hours) {
            $start_time = $this->calculateTime();
            if (!$start_time) {
                echo json_encode(array('status' => 0, 'message' => 'You can not add manual time while having an on-going task.'));
                exit();
            }
            
            // Calculate end time
            $end_time = date_by_timezone(strtotime($start_time) + $number_of_hours * 3600, 'H:i');
            
        }
        
        echo json_encode($this->working_session_model->add_manual_time(
            $this->input->post('wb_task_id', true),
            $start_time, $end_time,
            $this->input->post('note', true)
        ));
    }
    
    /**
     * Find time to add manual log
     * @return NULL|string
     */
    private function calculateTime()
    {
        $this->load->model(['worker_model', 'settings_model', 'work_board_model', 'department_model']);
        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        
        
        // In case Employee add manual time by number of hours, get lastest logged time or start time of working day
        $workboard_id = $this->work_board_model->getWorkboard();
        
        if ($workboard_id) {
            $this->db->select('actual_time_keeping.start_time, actual_time_keeping.end_time');
            $this->db->from('actual_time_keeping');
            $this->db->join('work_board_task', 'actual_time_keeping.workboard_task_id = work_board_task.wb_task_id', 'INNER');
            $this->db->join('(
                    SELECT MAX(start_time) as last_start_time
                    FROM actual_time_keeping
                    INNER JOIN work_board_task ON actual_time_keeping.workboard_task_id = work_board_task.wb_task_id
                    WHERE actual_time_keeping.worker_id = ' . $this->session->userdata('worker_id') . '
                    AND work_board_task.work_board_id = ' . $workboard_id . '
                ) last_logged', 'last_logged.last_start_time = actual_time_keeping.start_time');
            $this->db->where('actual_time_keeping.worker_id', $this->session->userdata('worker_id'));
            $this->db->where('work_board_task.work_board_id', $workboard_id);
            $this->db->limit(1);
            $query = $this->db->get();
            
            $res = $query->result();
            
            if ($res) {
                $last_time = $res[0];
                if (!$last_time->end_time) {
                    return null;
                }
                
                return date_by_timezone($last_time->end_time + 60, 'H:i');
            }
            
            // If worker has working session then use start time of working session
            $ws = $this->working_session_model->get(array(
                'worker_id' => $worker->worker_id,
                'working_date' => date('Y-m-d', time())
            ));
            
            if ($ws) {
                return date_by_timezone($ws->start_time, 'H:i');
            }
            
            // Get start time of work board
            $wordboard = $this->work_board_model->get(['work_board_id' => $workboard_id]);
            if ($wordboard && $wordboard->start_working) {
                return date_by_timezone(strtotime(date('Y-m-d') . ' ' . $wordboard->start_working), 'H:i');
            }
        }
        
        // Get start time of department
        $department = $this->department_model->get(['c_id' => $worker->c_id, 'd_id' => $worker->d_id]);
        if ($department && $department->start_working) {
            return date_by_timezone(strtotime(date('Y-m-d') . ' ' . $department->start_working), 'H:i');
        }
        
        $default_day_start = $this->settings_model->get(array(
            'c_id' => $worker->c_id,
            'd_id' => $worker->d_id,
            'settings_name' => 'default_day_start'
        ));
        
        return $default_day_start ? $default_day_start->settings_value : date_by_timezone(strtotime(date('Y-m-d') . ' 05:00'), 'H:i');
    }
    
    public function load_mobile_tabs()
    {
        echo json_encode(array_merge(
            [
                'status' => 1,
                'geonotes' => $this->load_geo_notes(),
                'daily_notes' => $this->load_daily_notes(),
                'general_notes' => $this->load_general_notes()
            ],
            $this->load_mowing_patterns()
        ));
    }
    
    private function load_daily_notes()
    {
        $html = '';
        if ($department_id = $this->session->userdata('department_id')) {
            $this->load->model(['company_translation_model', 'worker_model']);
            $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
            $c_translation = $this->company_translation_model->get(['cid' => $worker->c_id, 'did' => $worker->d_id]);
            
            $this->db->select('workboard_notes_translation.workboard_note, split_notes.split_notes');
            $this->db->from('split_notes');
            $this->db->join('work_board', 'work_board.work_board_id = split_notes.workboard_id', 'INNER');
            $this->db->join('workers', 'workers.c_id = work_board.c_id', 'INNER');
            $this->db->join('workboard_notes_translation', 'workers.lang_code = workboard_notes_translation.lang_code
                AND workboard_notes_translation.split_board_notes_id = split_notes.sb_notes_id
                AND workboard_notes_translation.`unixupdate` >= split_notes.unixtime_note_modified', 'LEFT OUTER');
            $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
            $this->db->where('work_board.w_date', date_by_timezone(time(), 'Y-m-d'));
            $this->db->where('work_board.d_id', $department_id);
            
            $query = $this->db->get();
            $notes = $query->result();
            foreach ($notes as $note) {
                $html .= $note->split_notes;
                $html .= (($c_translation && $note->workboard_note) ? (($html ? '<br/>' : '') . $note->workboard_note) : '');
            }
        }
        
        return $html;
    }
    
    private function load_general_notes()
    {
        $html = '';
        
        if ($this->session->has_userdata('FEATURE_NOTES') && $this->session->userdata('FEATURE_NOTES')) {
            if ($department_id = $this->session->userdata('department_id')) {
                $this->db->select('general_notes.general_notes');
                $this->db->from('general_notes');
                $this->db->join('workers', 'workers.c_id = general_notes.cid AND workers.d_id = general_notes.did', 'INNER');
                $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
                $this->db->where('general_notes.did', $department_id);
                
                $query = $this->db->get();
                
                $notes = $query->result();
                
                foreach ($notes as $note) {
                    $html .= $note->general_notes;
                }
            }
        }
        
        return $html;
    }
    
    private function load_geo_notes()
    {
        $html = '';
        
        if ($this->session->has_userdata('FEATURE_GEONOTES') && $this->session->userdata('FEATURE_GEONOTES')) {
            $this->load->model(['geonote_model', 'geonote_picture_model', 'geonote_map_model']);
            
            
            if ($department_id = $this->session->userdata('department_id')) {
                $geoNotes = $this->geonote_model->get_notes($this->session->userdata('worker_id'), $department_id);
                
                foreach ($geoNotes as $geoNote) {
                    $pictures = $this->geonote_picture_model->get_all(['geonote_id' => $geoNote->geonote_id]);
                    $markers = $this->geonote_map_model->get_all(['geonote_id' => $geoNote->geonote_id]);
                    $html .= $this->load->view('ajax/geo_notes', ['geoNote' => $geoNote, 'pictures' => $pictures, 'markers' => $markers], true);
                }
            }
        }
        
        return $html;
    }
    
    private function load_mowing_patterns()
    {
        if ($this->session->has_userdata('FEATURE_MOW_PATTERNS') && $this->session->userdata('FEATURE_MOW_PATTERNS')) {
            if ($department_id = $this->session->userdata('department_id')) {
                $this->load->model('workboard_mp_model');
                
                $mows = $this->workboard_mp_model->get_mow_patterns($department_id, date('Y-m-d'));
                
                $mowing = $this->load->view('ajax/mowing', ['mows' => $mows], true);
                
                $footer_mowing = $this->load->view('ajax/footer_mowing', ['mows' => $mows], true);
                
                return ['mowing' => $mowing, 'footer_mowing' => $footer_mowing];
            }
        }
        
        return ['mowing' => '', 'footer_mowing' => ''];
    }
    
    public function update_translation()
    {
        $isUpdated = update_translation($this->session->userdata('worker_id'));
        if (!$isUpdated) {
            echo json_encode(['status' => 1]);
            exit;
        }
        
        $this->load->model(['work_board_task_model', 'company_translation_model', 'worker_model']);
        
        $taskList = $this->work_board_task_model->get_worker_tasks(
            $this->session->userdata('worker_id'),
            date_by_timezone(time(), 'Y-m-d')
        );
        
        $tasks = [];
        if ($taskList) {
            $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
            $c_translation = $this->company_translation_model->get(['cid' => $worker->c_id, 'did' => $worker->d_id]);
            
            foreach ($taskList as $task) {
                $notes = '';
                if ($c_translation->active) {
                    if ($task->task_translation) {
                        $notes = $task->task_translation;
                    }
                    if ($task->wb_task_notes_tran) {
                        $notes .= ' - ' . $task->wb_task_notes_tran;
                    }
                    if ($task->trans_note) {
                        $notes .= ' - ' . $task->trans_note;
                    }
                }
                $tasks['job-notes-' . $task->wb_task_id] = $notes;
            }
        }
        
        echo json_encode(['status' => 1, 'daily_notes' => $this->load_daily_notes(), 'tasks' => $tasks]);
    }
    
    public function check_tasks()
    {
        $this->load->model('split_table_model');
        $active_sb = $this->split_table_model->get_active_split();
        $tasks = $this->get_tasks(null, null, null, false);
        
        foreach ($tasks as $i => $task) {
            if ($active_sb && $task->split_table_id != $active_sb) {
                $task->grayed_out = 1;
            } else {
                $task->grayed_out = 0;
            }
            $tasks[$i] = $task;
        }
        
        if ($this->input->is_ajax_request()) {
            echo json_encode(array('status' => 1, 'tasks' => $tasks));
            exit;
        }
    }
    
    private function employee_safety_cards()
    {
        $sql = '
            SELECT worker_safety_cards.safety_card_id, worker_safety_cards.does_not_expire, worker_safety_cards.start_date, worker_safety_cards.expiration
            FROM worker_safety_cards
            INNER JOIN (
                SELECT MAX(start_date) AS start_date, worker_id, safety_card_id
                FROM worker_safety_cards
                WHERE start_date <= "' . date('Y-m-d') . '" AND worker_id = ' . $this->session->userdata('worker_id') . '
                GROUP BY worker_id, safety_card_id
            ) tmp_wsc
            ON worker_safety_cards.worker_id = tmp_wsc.worker_id AND worker_safety_cards.safety_card_id = tmp_wsc.safety_card_id
            AND worker_safety_cards.start_date = tmp_wsc.start_date
            WHERE worker_safety_cards.worker_id = ' . $this->session->userdata('worker_id') . '
        ';
        
        $query = $this->db->query($sql);
        $data = $query->result();
        
        $res = array();
        if ($data) {
            foreach ($data as $item) {
                $res[$item->safety_card_id] = $item;
            }
        }
        
        return $res;
    }
    
    private function missing_safety_cards($emp_cards, $task_id)
    {
        if (!$task_id) {
            return array();
        }
        
        $sql = '
            SELECT id, safety_name FROM
            (
            SELECT safety_cards.id, safety_cards.safety_name
            FROM safety_cards
            INNER JOIN safety_card_settings ON safety_card_settings.safety_card_id = safety_cards.id
            INNER JOIN tasks ON safety_card_settings.c_id = tasks.c_id AND safety_card_settings.d_id = tasks.d_id AND tasks.task_id = ' . $task_id . '
            WHERE safety_card_settings.apply_all_tasks = 1
            AND NOT EXISTS (
            	SELECT * FROM safety_card_tasks
            	WHERE safety_card_tasks.safety_card_id = safety_cards.id
            	AND safety_card_tasks.include = 2 AND task_id = ' . $task_id . '
            )
            	    
            UNION
            	    
            SELECT safety_cards.id, safety_cards.safety_name
            FROM safety_card_tasks
            INNER JOIN safety_cards ON safety_card_tasks.safety_card_id = safety_cards.id
            WHERE safety_card_tasks.include = 1 AND safety_card_tasks.task_id = ' . $task_id . '
            ) needed_cards
            ORDER BY safety_name
        ';
        
        $query = $this->db->query($sql);
        $data = $query->result();
        
        $res = array();
        if ($data) {
            foreach ($data as $item) {
                if (!isset($emp_cards[$item->id])) {
                    $res[] = array('safety_name' => $item->safety_name, 'reason' => 'Not Completed');
                } else {
                    $card = $emp_cards[$item->id];
                    if (strtotime($card->start_date) <= time() && ($card->does_not_expire == 1 || strtotime($card->expiration) > time())) {
                        continue;
                    }
                    
                    $reason = 'Not Completed';
                    if ($card->does_not_expire && strtotime($card->does_not_expire) < time()) {
                        $reason = 'Expired ' . $card->does_not_expire;
                    }
                    $res[] = array('safety_name' => $item->safety_name, 'reason' => $reason);
                }
            }
        }
        
        
        return $res;
    }
    
    public function render_task_pool()
    {
        $this->load->model(['worker_model']);
        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        
        if (!$worker) {
            echo json_encode(array('status' => 0, 'reload' => 1));
            exit;
        }
        
        $this->db->select('task_pool.id, tasks.task_id, tasks.task_name, tasks.est_hours, task_pool.task_notes, COALESCE(department_features.active, 1) AS safety_active');
        $this->db->from('task_pool');
        
        $this->db->join('tasks', 'tasks.task_id = task_pool.task_id AND tasks.remove = 0', 'INNER');
        $this->db->join('
            (
            SELECT d_id FROM workers WHERE worker_id = ' . $worker->worker_id . '
            UNION
            SELECT department_id AS d_id FROM workers_departments WHERE worker_id = ' . $worker->worker_id . ' AND remove = 0
            ) worker_departments
        ', 'task_pool.d_id = worker_departments.d_id', 'INNER');
        $this->db->join('department_features', 'department_features.d_id = task_pool.d_id AND department_features.feature_id = ' . FEATURE_SAFETY, 'LEFT OUTER');
        
        $this->db->where('task_pool.c_id', $worker->c_id);
        $this->db->where('task_pool.date_completed IS NULL');
        $this->db->where('(EXISTS (
                SELECT task_pool_worker.id
                FROM task_pool_worker
                WHERE task_pool_worker.task_pool_id = task_pool.id AND task_pool_worker.active = 1
                AND task_pool_worker.worker_id = ' . $worker->worker_id .
            ') OR NOT EXISTS(
                SELECT task_pool_worker.id
                FROM task_pool_worker
                WHERE task_pool_worker.task_pool_id = task_pool.id AND task_pool_worker.active = 1
            ))'
            );
        $this->db->order_by('tasks.task_name');
        
        $query = $this->db->get();
        $data = $query->result();
        
        $html = '';
        if ($data) {
            $emp_cards = $this->employee_safety_cards();
            foreach ($data as $item) {
                if ($this->isFeatureActive('safety') && $item->safety_active) {
                    $task_missing_cards = $this->missing_safety_cards($emp_cards, $item->task_id);
                } else {
                    $task_missing_cards = array();
                }
                
                $html .= '
                    <div class="atask-item-area">
                        <div class="atask-name-est">
                            <p class="atask-name">' . $item->task_name . '</p>
                            <p class="atask-est">Est hr: <span>' . number_format($item->est_hours, 2) . '</span></p>';
                
                if ($task_missing_cards) {
                    $html .= '<a class="add-this alert-task-pool" task_pool_id=""><i class="fa fa-exclamation-triangle"></i></a>';
                    $html .= '
                        <div class="alert-popup" style="position: absolute">
                            <div class="alert-header">
                                <i class="fa fa-exclamation-triangle warning-icon"></i>
                                Safety and Training Issues
                                <a href="#" class="right close-alert"><i class="fa fa-times"></i></a>
                            </div>
                            <ul class="alert-expire-list">';
                    foreach ($task_missing_cards as $card) {
                        $html .= '
                                <li class="expire-item">
                                    <div class="expire-name">' . $card['safety_name'] . '</div>
                                    <div class="expire-time">' . $card['reason'] . '</div>
                                </li>
                        ';
                    }
                    $html .= '
                            </ul>
                        </div>
                    ';
                } else {
                    $html .= '<a class="add-this add-this-task-pool" task_pool_id="' . $item->id . '"><i class="fa fa-plus-square"></i></a>';
                }
                $html .= '
                        </div>
                        <div class="atask-note">' . $item->task_notes . '</div>
                    </div>
                ';
            }
        } else {
            $html = 'No tasks available.';
        }
        
        if ($this->input->is_ajax_request()) {
            echo json_encode(array('status' => 1, 'html' => $html));
            exit;
        }
    }
    
    public function add_task_pool()
    {
        $this->db->trans_begin();
        
        $task_pool_id = $this->input->post('task_pool_id');
        if (!$task_pool_id) {
            echo json_encode(array('status' => 1, 'message' => 'Please select task.'));
            exit;
        }
        
        $this->db->select('workers.c_id, workers.d_id, groups.group_color, workers.wage, workers.lang_code');
        $this->db->from('workers');
        $this->db->join('groups', 'workers.group_id = groups.group_id', 'LEFT OUTER');
        $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
        $this->db->limit(1);
        $query = $this->db->get();
        $workers = $query->result();
        
        $worker = $workers[0];
        
        $this->db->select('task_pool.task_id, tasks.est_hours, task_pool.task_notes');
        $this->db->from('task_pool');
        $this->db->join('tasks', 'tasks.task_id = task_pool.task_id AND tasks.remove = 0', 'INNER');
        $this->db->where('task_pool.id', $task_pool_id);
        $this->db->where('
            (EXISTS (SELECT * FROM task_pool_worker WHERE task_pool_worker.task_pool_id = task_pool.id AND task_pool_worker.active = 1 AND task_pool_worker.worker_id = ' . $this->session->userdata('worker_id') . ')
            OR NOT EXISTS (SELECT * FROM task_pool_worker WHERE task_pool_worker.task_pool_id = task_pool.id AND task_pool_worker.active = 1))
        ');
        $this->db->limit(1);
        $query = $this->db->get();
        $tasks = $query->result();
        
        if (!$tasks) {
            echo json_encode(array('status' => 1, 'message' => 'Selected task is no longer available.'));
            exit;
        }
        $task = $tasks[0];
        
        $d_id = $this->session->has_userdata('department_id') ? $this->session->userdata('department_id') : $worker->d_id;
        $this->load->model(array(
            'work_board_model', 'work_board_task_model',
            'actual_time_keeping_model', 'working_session_model',
            'split_table_model', 'worker_department_model'
        ));
        $work_board_id = $this->work_board_model->getWorkboard();
        if (!$work_board_id) {
            $this->db->insert('work_board', array(
                'w_date' => date('Y-m-d'),
                'c_id' => $worker->c_id,
                'd_id' => $d_id,
                'tstamp' => time()
            ));
            $work_board_id = $this->db->insert_id();
        }
        
        $worker_department = $this->worker_department_model->get([
            'worker_id' => $this->session->userdata('worker_id'),
            'department_id' => $d_id
        ]);
        
        $sb_id = $this->split_table_model->get_active_split();
        
        $this->db->insert('work_board_task', array(
            'worker_id' => $this->session->userdata('worker_id'),
            'task_id' => $task->task_id,
            'sub_task_id' => 0,
            'est_hr' => $task->est_hours,
            'true_est_hr' => $task->est_hours,
            'e_color' => $worker->group_color,
            'task_notes' => $task->task_notes,
            'work_board_id' => $work_board_id,
            'wage' => $worker->wage,
            'sb_id' => $sb_id,
            'sortorder' => $this->work_board_task_model->getMaxSortOrder($this->session->userdata('worker_id'), $work_board_id, $sb_id),
            'workers_department_id' => $worker_department ? $worker_department->worker_department_id : null,
            'send_for_translation' => $task->task_notes ? 1 : 0,
            'lang_code' => $worker->lang_code
        ));
        $new_wb_task_id = $this->db->insert_id();
        
        $this->db->insert('task_pool_assigned', array(
            'task_pool_id' => $task_pool_id,
            'wb_task_id' => $new_wb_task_id,
            'hours' => $task->est_hours
        ));
        
        if ($new_wb_task_id) {
            $old_wb_task = $this->actual_time_keeping_model->missing_punch_by_work_board($work_board_id);
            if ($old_wb_task) {
                $this->actual_time_keeping_model->stop($old_wb_task->time_id, null);
            }
            
            $working_session = $this->working_session_model->missing_punch();
            if (!$working_session) {
                $this->working_session_model->clock_in();
            }
            
            $this->actual_time_keeping_model->start($new_wb_task_id);
        }
        
        $this->db->where('work_board_id', $work_board_id);
        $this->db->update('work_board', array('tstamp' => time()));
        
        $file = "/home/asbdev/public_html/memberdata/"  . $worker->c_id . "/info/presets.xml";
        if (is_file($file)) {
            $companyXML = simplexml_load_file($file);
            if ($this->getPresetData($companyXML, 'taskTracker->department_' . $d_id . '->startweek') !== false) {
                $startofWeek = $this->getPresetData($companyXML, 'taskTracker->department_' . $d_id . '->startweek');
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
        
        $this->db->insert('overtime_cron', [
            'c_id' => $worker->c_id,
            'start_date' => $startdate
        ]);
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            echo json_encode(array('status' => 0, 'message' => 'There was an error while trying to add task, please try again!'));
            exit();
            
        } else {
            $this->db->trans_commit();
            
            echo json_encode(array('status' => 1, 'message' => 'Task has been added successfully.'));
            exit();
        }
    }
    
    public function get_wb_task_note()
    {
        $wb_task_id = $this->input->get('wb_task_id');
        if (!$wb_task_id) {
            echo json_encode(array('status' => 0, 'message' => 'Task is no longer available.'));
            exit();
        }
        
        $this->db->select('work_board_task.task_notes, tasks.task_name');
        $this->db->from('work_board_task');
        $this->db->join('tasks', 'work_board_task.task_id = tasks.task_id', 'INNER');
        $this->db->where('work_board_task.wb_task_id', $wb_task_id);
        $this->db->limit(1);
        
        $query = $this->db->get();
        $data = $query->result();
        
        if (!$data) {
            echo json_encode(array('status' => 0, 'message' => 'Task is no longer available.'));
            exit();
        }
        
        $task = $data[0];
        echo json_encode(array('status' => 1, 'task_name' => $task->task_name, 'task_notes' => $task->task_notes));
        exit();
    }
    
    public function save_wb_task_note()
    {
        $wb_task_id = $this->input->post('wb_task_id');
        if (!$wb_task_id) {
            echo json_encode(array('status' => 0, 'message' => 'Task is no longer available.'));
            exit();
        }
        
        $this->db->where('wb_task_id', $wb_task_id);
        $this->db->update('work_board_task', ['task_notes' => $this->input->post('task_notes'), 'send_for_translation' => 1]);
        
        echo json_encode(array('status' => 1, 'message' => 'Task note has been updated.', 'task_notes' => $this->input->post('task_notes')));
        exit();
    }
    
    public function get_mcn_products()
    {
        $wo_item_job_id = $this->input->post('wo_item_job_id');
        
        $this->db->select('
            mcn_product.id, mcn_product.name, COALESCE(mcn_product.average_cost, 0) AS average_cost,
            mcn_product_vari.id AS vari_id, mcn_product_vari.product_number, mcn_product.remove
        ');
        $this->db->from('mcn_product');
        $this->db->join('mcn_product_vari', 'mcn_product_vari.mcn_product_id = mcn_product.id AND mcn_product_vari.remove = 0', 'LEFT OUTER');
        $this->db->join('work_order_item', 'work_order_item.c_id = mcn_product.c_id AND work_order_item.d_id = mcn_product.d_id', 'INNER');
        $this->db->join('work_order_item_job', 'work_order_item.id = work_order_item_job.work_order_item_id', 'INNER');
        $this->db->where('work_order_item_job.id', $wo_item_job_id);
        $this->db->order_by('mcn_product.name, mcn_product_vari.product_number');
        
        $query = $this->db->get();
        $data = $query->result_array();
        
        echo json_encode(array('status' => 1, 'products' => $data));
        exit();
    }
    
    public function delete_mcn_products()
    {
        if (isset($_POST['wo_job_product_id']) && $_POST['wo_job_product_id']) {
            $this->db->where('id', $_POST['wo_job_product_id']);
            $this->db->delete('work_order_item_job_product');
            
            echo json_encode(array('status' => 1, 'message' => 'Part has been deleted successfully.'));
            exit();
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Please select Part to delete.'));
        exit();
    }
    
    public function save_mcn_products()
    {
        if (isset($_POST['wo_job_product_id']) && $_POST['wo_job_product_id']) {
            if (isset($_POST['product_id']) && $_POST['product_id']) {
                $this->db->select('id');
                $this->db->from('work_order_item_job_product');
                $this->db->where('id', $_POST['wo_job_product_id']);
                $query = $this->db->get();
                
                if ($query->result()) {
                    $this->db->where('id', $_POST['wo_job_product_id']);
                    $this->db->update('work_order_item_job_product', [
                        'product_id' => $_POST['product_id'],
                        'quantity' => $_POST['quantity'] ? $_POST['quantity'] : 0,
                        'cost' => $_POST['cost'] ? $_POST['cost'] : 0
                    ]);
                    
                    echo json_encode(array('status' => 1, 'message' => 'Part has been updated successfully.'));
                    exit();
                }
            } else {
                echo json_encode(array('status' => 0, 'message' => 'Please select Part.'));
                exit();
            }
        }
        
        if (isset($_POST['wo_job_id']) && $_POST['wo_job_id']) {
            if (isset($_POST['product_id']) && $_POST['product_id']) {
                $this->db->select('id');
                $this->db->from('work_order_item_job');
                $this->db->where('id', $_POST['wo_job_id']);
                $query = $this->db->get();
                
                if ($query->result()) {
                    $this->db->insert('work_order_item_job_product', [
                        'work_order_item_job_id' => $_POST['wo_job_id'],
                        'product_id' => $_POST['product_id'],
                        'quantity' => $_POST['quantity'] ? $_POST['quantity'] : 0,
                        'cost' => $_POST['cost'] ? $_POST['cost'] : 0
                    ]);
                    
                    $id = $this->db->insert_id();
                    if ($id) {
                        $html = '
                            <div class="part-item">
                                <div class="job-part-qty">' . ($_POST['quantity'] ? $_POST['quantity'] : 0) . '</div>
                                <div class="job-part-name" product_id="' . $_POST['product_id'] . '">' . $_POST['product_name'] . '</div>
                                <div class="job-part-cost">' . number_format($_POST['cost'] ? $_POST['cost'] : 0) . '</div>
                                <div class="job-part-total">' . number_format(($_POST['quantity'] ? $_POST['quantity'] : 0) * ($_POST['cost'] ? $_POST['cost'] : 0)) . '</div>
                                <div class="job-part-edit">
                                	<a class="edit-part" href="#" wo_job_product_id="' . $id . '"><i class="fa fa-edit"></i></a>
                                </div>
                            </div>
                        ';
                        
                        echo json_encode(array('status' => 1, 'message' => 'Part has been added successfully.', 'html' => $html));
                        exit();
                    }
                }
            } else {
                echo json_encode(array('status' => 0, 'message' => 'Please select Part.'));
                exit();
            }
        }
        
        echo json_encode(array('status' => 0, 'message' => 'There was an error while trying to save Part. Please try again later.'));
        exit();
    }
    
    public function delete_job_notes()
    {
        if (isset($_POST['wo_job_note_id']) && $_POST['wo_job_note_id']) {
            $this->db->where('id', $_POST['wo_job_note_id']);
            $this->db->delete('work_order_item_job_notes');
            
            echo json_encode(array('status' => 1, 'message' => 'Notes has been deleted successfully.'));
            exit();
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Please select Notes to delete.'));
        exit();
    }
    
    public function save_job_notes()
    {
        if (isset($_POST['wo_job_note_id']) && $_POST['wo_job_note_id']) {
            $this->db->select('id');
            $this->db->from('work_order_item_job_notes');
            $this->db->where('id', $_POST['wo_job_note_id']);
            $query = $this->db->get();
            
            if ($query->result()) {
                $this->db->where('id', $_POST['wo_job_note_id']);
                $this->db->update('work_order_item_job_notes', [
                    'notes' => $_POST['notes'],
                    'add_time' => time(),
                    'worker_id' => $this->session->userdata('worker_id')
                ]);
                
                echo json_encode(array('status' => 1, 'message' => 'Notes has been updated successfully.'));
                exit();
            }
        }
        
        if (isset($_POST['wo_job_id']) && $_POST['wo_job_id']) {
            $this->db->select('id');
            $this->db->from('work_order_item_job');
            $this->db->where('id', $_POST['wo_job_id']);
            $query = $this->db->get();
            
            if ($query->result()) {
                $this->db->insert('work_order_item_job_notes', [
                    'work_order_item_job_id' => $_POST['wo_job_id'],
                    'worker_id' => $this->session->userdata('worker_id'),
                    'notes' => $_POST['notes'],
                    'add_time' => time(),
                ]);
                
                $id = $this->db->insert_id();
                if ($id) {
                    $html = '
                        <div class="job-note-item">
                            <p class="author">' . $this->session->userdata('worker_name') . ' <span class="date">' . date_by_timezone(time(), 'm/d/Y') . '</span></p>
                            <div class="note-content">
                                <p>' . $_POST['notes'] . '</p>
                                <a class="edit-note" href="#" wo_job_note_id="' . $id . '"><i class="fa fa-pencil"></i></a>
                            </div>
                        </div>
                    ';
                    
                    echo json_encode(array('status' => 1, 'message' => 'Notes has been added successfully.', 'html' => $html));
                    exit();
                }
            }
        }
        
        echo json_encode(array('status' => 0, 'message' => 'There was an error while trying to save Notes. Please try again later.'));
        exit();
    }
    
    public function load_equipment_track()
    {
        $this->db->select('equipment.id, equipment.equipment_type_id, equipment.starting_hour, equipment_type.track_maintenance_by, COALESCE(estimated_hr, 0) AS estimated_hr');
        $this->db->from('equipment');
        $this->db->join('equipment_type', 'equipment_type.id = equipment.equipment_type_id', 'INNER');
        $this->db->join('(
            SELECT equipment_id, SUM(equipment_hours) AS estimated_hr
            FROM equipment_update
            WHERE equipment_id = ' . $_POST['equipment_id'] . '
        ) e_hr', 'e_hr.equipment_id = equipment.id', 'LEFT OUTER');
        $this->db->where('equipment.id', $_POST['equipment_id']);
        $query = $this->db->get();
        
        if ($data = $query->result()) {
            $equipment = $data[0];
            
            $this->db->select('update_date, COALESCE(users.first_name, workers.first_name) AS first_name, COALESCE(users.last_name, workers.last_name) AS last_name');
            $this->db->from('equipment_update');
            $this->db->join('users', 'users.id = equipment_update.user_id', 'LEFT OUTER');
            $this->db->join('workers', 'workers.worker_id = equipment_update.worker_id', 'LEFT OUTER');
            $this->db->where('equipment_id', $_POST['equipment_id']);
            $this->db->order_by('update_date DESC, equipment_update.id DESC');
            $this->db->limit(1);
            
            $query = $this->db->get();
            
            $last_updated = null;
            if ($data = $query->result()) {
                $last_updated = $data[0];
            }
            
            $mobile = $this->input->post('mobile');
            
            $html = '
                <p class="estimate-hour">Estimated ' . ucwords($equipment->track_maintenance_by) . ': <span>' . number_format($equipment->estimated_hr, 2) . '</span></p>
                <div class="form-group">
                    <div class="col-xs-12 update-input-group">
                        <input class="form-control hour-input" title="Update Hours" placeholder="Update ' . ucwords($equipment->track_maintenance_by) . '">
                        <button class="btn btn-update" equipment_id="' . $equipment->id . '">
                        ' . ($mobile ? '<i class="fa fa-refresh"></i>' : '<i class="fas fa-sync-alt"></i>') . '
                        </button>
                    </div>
                </div>
                <p class="update-text">Updated Last: ' . ($last_updated ? date_by_timezone($last_updated->update_date, "m/d/Y") : '') . '</p>
                <p class="update-text">By ' . ($last_updated ? ($last_updated->first_name . ' ' . $last_updated->last_name) : '') . '</p>
            ';
            
            echo json_encode(array('status' => 1, 'html' => $html));
            exit();
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Equipment is no longer available.'));
        exit();
    }
    
    public function update_equipment_hours()
    {
        $this->db->select('edit_equipment_hour');
        $this->db->from('worker_permission');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $permissions = $query->result();
        
        if ($permissions) {
            $permission = $permissions[0];
            if (!$permission->edit_equipment_hour) {
                echo json_encode(['status' => 0, 'message' => 'You do not have permission to change the status of equipment, please have your administrator change your permission in employee management under your name under in the mobile tab.']);
                exit();
            }
        }
        
        $this->db->select('equipment.id, equipment.equipment_type_id, equipment.starting_hour, equipment_type.track_maintenance_by, COALESCE(estimated_hr, 0) AS estimated_hr');
        $this->db->from('equipment');
        $this->db->join('equipment_type', 'equipment_type.id = equipment.equipment_type_id', 'INNER');
        $this->db->join('(
            SELECT equipment_id, SUM(equipment_hours) AS estimated_hr
            FROM equipment_update
            WHERE equipment_id = ' . $_POST['equipment_id'] . '
        ) e_hr', 'e_hr.equipment_id = equipment.id', 'LEFT OUTER');
        $this->db->where('equipment.id', $_POST['equipment_id']);
        $query = $this->db->get();
        
        if ($data = $query->result()) {
            $equipment = $data[0];
            
            $this->db->insert('equipment_update', [
                'equipment_id' => $_POST['equipment_id'],
                'worker_id' => $this->session->userdata('worker_id'),
                'equipment_hours' => floatval($_POST['update_amount']) - floatval($equipment->estimated_hr),
                'actual' => 1,
                'update_date' => time(),
                'starting_hr' => 0
            ]);
            
            echo json_encode(array('status' => 1, 'new_hours' => number_format($_POST['update_amount']) . ' ' . ucwords($equipment->track_maintenance_by)));
            exit();
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Equipment is no longer available.'));
        exit();
    }
    
    public function update_wo_job_status()
    {
        $this->db->select('id, status');
        $this->db->from('work_order_item_job');
        $this->db->where('id', $_POST['wo_job_id']);
        $query = $this->db->get();
        
        if ($data = $query->result()) {
            $job = $data[0];
            
            /*$this->db->insert('work_order_item_job_status', [
                'work_order_item_job_id' => $_POST['wo_job_id'],
                'worker_id' => $this->session->userdata('worker_id'),
                'action_time' => time(),
                'status' => $_POST['status']
            ]);*/
            
            if ($job->status != $_POST['status']) {
                $this->db->where('id', $job->id);
                $this->db->update('work_order_item_job', [
                    'status' => $_POST['status'],
                    'status_approved' => 0
                ]);
            }
            
            if ($_POST['status'] == 2 || $_POST['status'] == 3 || $_POST['status'] == 4) {
                $this->db->select('work_board_task.wb_task_id, actual_time_keeping.time_id');
                $this->db->from('work_order_item_job_task');
                $this->db->join('work_board_task', 'work_order_item_job_task.wb_task_id = work_board_task.wb_task_id', 'INNER');
                $this->db->join('work_board', 'work_board.work_board_id = work_board_task.work_board_id', 'INNER');
                $this->db->join('actual_time_keeping', 'actual_time_keeping.remove = 0 AND actual_time_keeping.workboard_task_id = work_board_task.wb_task_id AND actual_time_keeping.end_time IS NULL', 'INNER');
                $this->db->where('work_order_item_job_task.work_order_item_job_id', $job->id);
                $this->db->where('work_board_task.worker_id', $this->session->userdata('worker_id'));
                $query = $this->db->get();
                
                if ($data = $query->result()) {
                    foreach ($data as $item) {
                        $this->actual_time_keeping_model->stop($item->time_id, null);
                    }
                }
                
                $completed = 1;
            } else {
                $completed = 0;
            }
            
            $newClass = $_POST['status'] == 1 ? 'status-progress' : ($_POST['status'] == 2 ? 'status-complete' : 'status-skip');
            $newCaption = $_POST['status'] == 1 ? 'In Progress' : ($_POST['status'] == 2 ? 'Completed' : 'Skip');
            
            $this->db->select('workers.worker_id, workers.c_id, workers.d_id, companies.timezone');
            $this->db->from('workers');
            $this->db->join('companies', 'companies.company_id = workers.c_id', 'INNER');
            $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
            $query = $this->db->get();
            $workers = $query->result();
            
            $worker = $workers[0];
            if ($worker) {
                $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
            }
            
            echo json_encode(array('status' => 1, 'message' => 'Work Order job status has been updated.', 'newClass' => $newClass, 'newCaption' => $newCaption, 'completed' => $completed));
            exit();
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Work Order job is no longer available.'));
        exit();
    }
    
    public function start_wo_job()
    {
        $wb_task_id = $this->input->post('wb_task_id');
        
        if ($wb_task_id) {
            $this->db->select('wb_task_id');
            $this->db->from('work_board_task');
            $this->db->where('wb_task_id', $wb_task_id);
            $this->db->where('worker_id', $this->session->userdata('worker_id'));
            $query = $this->db->get();
            
            if ($query->result()) {
                echo json_encode($this->actual_time_keeping_model->start($this->input->post('wb_task_id', true)));
                exit();
            }
        }
        
        $wo_item_job_id = $this->input->post('wo_item_job_id');
        if ($wo_item_job_id) {
            $wb_task_id = $this->save_wo_job_to_wbtask($wo_item_job_id, date_by_timezone(time(), 'Y-m-d'), $this->session->userdata('worker_id'));
            if ($wb_task_id) {
                $res = $this->actual_time_keeping_model->start($wb_task_id);
                $res['wb_task_id'] = $wb_task_id;
                
                $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
                
                echo json_encode($res);
                exit();
            }
        }
        
        echo json_encode(array('status' => 0, 'message' => 'Work Order job is no longer available.'));
        exit();
    }
    
    private function save_wo_job_to_wbtask($job_id, $date, $worker_id, $hour = null) {
        $this->db->select('id, work_order_item_id, status, task_id, notes, scheduled');
        $this->db->from('work_order_item_job');
        $this->db->where('id', $job_id);
        $query = $this->db->get();
        
        if ($data = $query->result()) {
            $job = $data[0];
            if (!$hour) {
                $hour = $job->scheduled;
            }
            
            $this->db->select('work_order_item_job_task.id, work_board_task.wb_task_id');
            $this->db->from('work_order_item_job_task');
            $this->db->join('work_board_task', 'work_order_item_job_task.wb_task_id = work_board_task.wb_task_id', 'INNER');
            $this->db->join('work_board', 'work_board.work_board_id = work_board_task.work_board_id', 'INNER');
            $this->db->where('work_order_item_job_task.work_order_item_job_id', $job_id);
            $this->db->where('work_board_task.worker_id', $worker_id);
            $this->db->where('work_board.w_date', $date);
            $query = $this->db->get();
            
            if ($data = $query->result()) {
                $job_task = $data[0];
            } else {
                $job_task = null;
            }
            
            if ($job_task) {
                $wb_task_id = $job_task->wb_task_id;
            } else {
                
                $this->load->model('work_board_model');
                $work_board_id = $this->work_board_model->getWorkboard();
                
                $this->db->select('workers.lang_code, workers.wage, groups.group_color, workers.d_id');
                $this->db->from('workers');
                $this->db->join('groups', 'groups.group_id = workers.group_id', 'LEFT OUTER');
                $this->db->where('workers.worker_id', $worker_id);
                $query = $this->db->get();
                
                if ($data = $query->result()) {
                    $worker = $data[0];
                } else {
                    return false;
                }
                
                $this->db->select('MAX(sortorder) AS so');
                $this->db->from('work_board_task');
                $this->db->where('worker_id', $worker_id);
                $this->db->where('work_board_id', $work_board_id);
                $query = $this->db->get();
                
                if ($data = $query->result()) {
                    $sortorder = $data[0];
                } else {
                    $sortorder = null;
                }
                
                $this->db->select('worker_department_id');
                $this->db->from('workers_departments');
                $this->db->where('worker_id', $worker_id);
                $this->db->where('department_id', $worker->d_id);
                $query = $this->db->get();
                
                if ($data = $query->result()) {
                    $worker_department = $data[0];
                } else {
                    $worker_department = null;
                }
                
                $this->db->insert('work_board_task', [
                    'worker_id' => $worker_id,
                    'task_id' => $job->task_id,
                    'est_hr' => $hour,
                    'true_est_hr' => $hour,
                    'task_notes' => $job->notes,
                    'work_board_id' => $work_board_id,
                    'workers_department_id' => $worker_department->worker_department_id,
                    'send_for_translation' => $job->notes ? 1 : 0,
                    'est_act' => -1,
                    'wage' => $worker->wage,
                    'sortorder' => ($sortorder && $sortorder->so) ? ($sortorder->so + 1) : 1,
                    'lang_code' => $worker->lang_code
                ]);
                
                $wb_task_id = $this->db->insert_id();
                
                $this->db->insert('work_order_item_job_task', [
                    'work_order_item_job_id' => $job_id,
                    'wb_task_id' => $wb_task_id
                ]);
            }
            
            if ($job->status == 0) {
                $this->db->where('id', $job->id);
                $this->db->update('work_order_item_job', [
                    'status' => 1,
                    'status_approved' => 0
                ]);
            }
            
            $this->db->select('id, status');
            $this->db->from('work_order_item');
            $this->db->where('id', $job->work_order_item_id);
            $query = $this->db->get();
            
            if ($data = $query->result()) {
                $wo = $data[0];
                
                if ($wo->status == 0) {
                    $this->db->where('id', $wo->id);
                    $this->db->update('work_order_item', [
                        'status' => 1,
                        'status_approved' => 0
                    ]);
                }
            }
            
            $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
            
            return $wb_task_id;
        }
        
        return false;
    }
    
    public function save_equipment_status() {
        $this->db->select('edit_equipment_status');
        $this->db->from('worker_permission');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $permissions = $query->result();
        
        if ($permissions) {
            $permission = $permissions[0];
            if (!$permission->edit_equipment_status) {
                echo json_encode(['status' => 0, 'message' => 'You do not have permission to change the status of equipment, please have your administrator change your permission in employee management under your name under in the mobile tab.']);
                exit();
            }
        }
        
        if (!isset($_POST['equipment_id']) || !$_POST['equipment_id']) {
            echo json_encode(['status' => 0, 'message' => 'There was an issue while trying to save equipment status.']);
            exit();
        }
        
        $this->db->select('id, equipment_type_id');
        $this->db->from('equipment');
        $this->db->where('id', $_POST['equipment_id']);
        $query = $this->db->get();
        
        $eqs = $query->result_array();
        if (!$eqs) {
            echo json_encode(['status' => 0, 'message' => 'Equipment is no longer available.']);
            exit();
        }
        
        $equipment = $eqs[0];
        
        $duplication_flag = 0;
        $start_date = strtotime(date('Y-m-d H:i:00'));
        $end_date = strtotime(date('Y-m-d 23:59:00'));
        
        $this->db->select('id, start_date, end_date, note, status');
        $this->db->from('equipment_status');
        $this->db->where('equipment_id', $equipment['id']);
        $custom_where = ' (
    		(start_date <= "' . date_format_by_timezone($start_date, 'Y-m-d H:i:00') . '" AND end_date >= "' . date_format_by_timezone($start_date, 'Y-m-d H:i:00') . '")
    		OR (start_date <= "' . date_format_by_timezone($end_date, 'Y-m-d H:i:00') . '" AND end_date >= "' . date_format_by_timezone($end_date, 'Y-m-d H:i:00') . '")
    		OR (start_date >= "' . date_format_by_timezone($start_date, 'Y-m-d H:i:00') . '" AND end_date <= "' . date_format_by_timezone($end_date, 'Y-m-d H:i:00') . '")
    	) ';
        $this->db->where($custom_where, null);
        $this->db->order_by('start_date DESC');
        $query = $this->db->get();
        
        $dup_equipment_status = $query->result_array();
        
        if ($dup_equipment_status) {
            if (count($dup_equipment_status) > 2) {
                echo json_encode(['status' => 0, 'message' => 'There is an duplicate in date range, please check again.']);
                exit();
            }
            if (count($dup_equipment_status) == 2) {
                if ($dup_equipment_status[1]['end_date'] == $dup_equipment_status[0]['start_date'] && $dup_equipment_status[0]['start_date'] == date_format_by_timezone($start_date, 'Y-m-d H:i:00')) {
                    $dup_equipment_status = $dup_equipment_status[0];
                } else {
                    echo json_encode(['status' => 0, 'message' => 'There is an duplicate in date range, please check again.']);
                    exit();
                }
            } else {
                $dup_equipment_status = $dup_equipment_status[0];
            }
            $fieldsUpdate = [
                'equipment_id' => $equipment['id'],
                'worker_id' => $this->session->userdata('worker_id'),
                'start_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00'),
                'end_date' => date_format_by_timezone($end_date, 'Y-m-d H:i:00'),
                'status' => $_POST['status']
            ];
            
            $dup_equipment_status['start_date'] = date_format_by_timezone(strtotime($dup_equipment_status['start_date']), 'Y-m-d H:i:00');
            $dup_equipment_status['end_date'] = date_format_by_timezone(strtotime($dup_equipment_status['end_date']), 'Y-m-d H:i:00');
            
            if ($start_date == strtotime($dup_equipment_status['start_date']) && $end_date == strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['status' => $_POST['status'] ? $_POST['status'] : 1]);
                
            } else if ($start_date == strtotime($dup_equipment_status['start_date']) && $end_date < strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['start_date' => date_format_by_timezone($end_date, 'Y-m-d H:i:00')]);
                
                $this->db->insert('equipment_status', $fieldsUpdate);
                
            } else if ($start_date == strtotime($dup_equipment_status['start_date']) && $end_date > strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', [
                    'end_date' => date_format_by_timezone($end_date, 'Y-m-d H:i:00'),
                    'status' => $_POST['status'] ? $_POST['status'] : 1
                ]);
                
            } else if ($start_date > strtotime($dup_equipment_status['start_date']) && $end_date == strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['end_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00')]);
                
                $this->db->insert('equipment_status', $fieldsUpdate);
                
            } else if ($start_date > strtotime($dup_equipment_status['start_date']) && $end_date < strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['end_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00')]);
                
                $this->db->insert('equipment_status', $fieldsUpdate);
                
                $fieldsUpdate['start_date'] = date_format_by_timezone($end_date, 'Y-m-d H:i:00');
                $fieldsUpdate['end_date'] = $dup_equipment_status['end_date'];
                $fieldsUpdate['status'] = $dup_equipment_status['status'];
                $fieldsUpdate['note'] = $dup_equipment_status['note'];
                $this->db->insert('equipment_status', $fieldsUpdate);
                
            } else if ($start_date > strtotime($dup_equipment_status['start_date']) && $end_date > strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['end_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00')]);
                
                $this->db->insert('equipment_status', $fieldsUpdate);
                
            } else if ($start_date < strtotime($dup_equipment_status['start_date']) && $end_date == strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', [
                    'start_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00'),
                    'status' => $_POST['status'] ? $_POST['status'] : 1
                ]);
                
            } else if ($start_date < strtotime($dup_equipment_status['start_date']) && $end_date < strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', ['start_date' => date_format_by_timezone($end_date, 'Y-m-d H:i:00')]);
                
                $this->db->insert('equipment_status', $fieldsUpdate);
                
            } else if ($start_date < strtotime($dup_equipment_status['start_date']) && $end_date > strtotime($dup_equipment_status['end_date'])) {
                $this->db->where('id', $dup_equipment_status['id']);
                $this->db->update('equipment_status', [
                    'start_date' => date_format_by_timezone($start_date, 'Y-m-d H:i:00'),
                    'end_date' => date_format_by_timezone($end_date, 'Y-m-d H:i:00'),
                    'status' => $_POST['status'] ? $_POST['status'] : 1
                ]);
                
            } else {
                echo json_encode(['status' => 0, 'message' => 'There is an duplicate in date range, please check again.']);
                exit();
            }
            
            $duplication_flag = 1;
        }
        
        if (!$duplication_flag) {
            $this->db->insert('equipment_status', [
                'equipment_id' => $equipment['id'],
                'start_date' => date_format_by_timezone(time(), 'Y-m-d H:i:00'),
                'end_date' => date_format_by_timezone(time(), 'Y-m-d 23:59:00'),
                'worker_id' => $this->session->userdata('worker_id'),
                'status' => $_POST['status'] ? $_POST['status'] : 1
            ]);
        }
        
        $this->refreshMechanicBoard(date('Y-m-d'), 'equipment');
        
        echo json_encode(['status' => 1, 'e_status' => $_POST['status'] ? $_POST['status'] : 1]);
        exit();
    }
    
    public function update_work_order_status() {
        $this->db->select('id, equipment_id, schedule_type');
        $this->db->from('work_order_item');
        $this->db->where('id', $_POST['wo_item_id']);
        $query = $this->db->get();
        $wos = $query->result_array();
        if (!$wos) {
            echo json_encode(['status' => 0, 'message' => 'Work Order is no longer available.']);
            exit();
        }
        $wo_item = $wos[0];
        
        if (isset($_POST['status'])) {
            if ($_POST['status'] == 2 || $_POST['status'] == 3 || $_POST['status'] == 4) {
                $this->db->select('work_order_item_job.id, work_board_task.wb_task_id, work_board_task.est_act, work_board_task.worker_id, actual_time_keeping.time_id, actual_time_keeping.end_time');
                $this->db->from('work_order_item_job_task');
                $this->db->join('work_order_item_job', 'work_order_item_job_task.work_order_item_job_id = work_order_item_job.id', 'INNER');
                $this->db->join('work_board_task', 'work_order_item_job_task.wb_task_id = work_board_task.wb_task_id', 'LEFT OUTER');
                $this->db->join('actual_time_keeping', 'actual_time_keeping.remove = 0 AND actual_time_keeping.workboard_task_id = work_board_task.wb_task_id', 'LEFT OUTER');
                $this->db->where('work_order_item_job.work_order_item_id', $wo_item['id']);
                $query = $this->db->get();
                $job_tasks = $query->result_array();
                
                if ($job_tasks) {
                    foreach ($job_tasks as $job_task) {
                        if ($job_task['time_id'] && !$job_task['end_time']) {
                            if ($job_task['est_act'] == 1) {
                                $this->db->select('SUM(COALESCE(end_time, ' . time() . ') - start_time) AS total');
                                $this->db->from('actual_time_keeping');
                                $this->db->where('remove', 0);
                                $this->db->where('worker_id', $job_task['worker_id']);
                                $this->db->where('wb_task_id', $job_task['wb_task_id']);
                                $query = $this->db->get();
                                $act_hrs = $query->result_array();
                                
                                if ($act_hrs) {
                                    $act_hr = $act_hrs[0];
                                    $est_hr = number_format($act_hr['total'] / 3600, 2);
                                } else {
                                    $est_hr = 0;
                                }
                                
                                $this->db->where('wb_task_id', $job_task['wb_task_id']);
                                $this->db->update('work_board_task', ['est_hr' => $est_hr]);
                            }
                            
                            $this->db->where('time_id', $job_task['time_id']);
                            $this->db->update('actual_time_keeping', ['end_time' => time()]);
                        }
                        
                        $this->db->where('id', $job_task['id']);
                        $this->db->update('work_order_item_job', ['status' => 2]);
                    }
                }
            }
            
            $fields = [
                'status' => $_POST['status'],
                'status_approved' => 0,
                'completed_at' => ($_POST['status'] == 3 || $_POST['status'] == 4) ? time() : ''
            ];
            
            if ($_POST['status'] == 2 || $_POST['status'] == 3 || $_POST['status'] == 4) {
                if ($wo_item['schedule_type'] == 2 || $wo_item['schedule_type'] == 4) {
                    $fields['equipment_update_on_completed'] = date('Y-m-d');
                } else {
                    $this->db->select('SUM(equipment_hours) AS curr');
                    $this->db->from('equipment_update');
                    $this->db->where('equipment_id', $wo_item['equipment_id']);
                    $query = $this->db->get();
                    
                    if ($hours = $query->result()) {
                        $hour = $hours[0];
                        $fields['equipment_update_on_completed'] = $hour->curr;
                    } else {
                        $fields['equipment_update_on_completed'] = 0;
                    }
                }
            } else {
                $fields['equipment_update_on_completed'] = null;
            }
            
            $this->db->where('id', $wo_item['id']);
            $this->db->update('work_order_item', $fields);
        }
        
        if ($_POST['status'] == 0) {
            $status_class = 'wo-status-not';
        } else if ($_POST['status'] == 1) {
            $status_class = 'wo-status-progress';
        } else if ($_POST['status'] == 2) {
            $status_class = 'wo-status-complete';
        } else if ($_POST['status'] == 3) {
            $status_class = 'wo-status-skipped';
        } else if ($_POST['status'] == 4) {
            $status_class = 'wo-status-complete-on-sat';
        }
        
        $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
        
        echo json_encode(['status' => 1, 'message' => 'Work Order status has been updated successfully.', 'status_class' => $status_class]);
        exit();
    }
    
    /**
     * Past Time
     */
    public function load_times()
    {
        $response = [];
        
        $direction = $this->input->post('direction');
        $start = $this->input->post('start');
        $end = $this->input->post('end');
        if ($start && !$end) {
            $start = date('Y-m-d', strtotime($start));
            $end = date('Y-m-d', strtotime('+10 days', strtotime($start)));
        } else if (!$start && $end) {
            $start = date('Y-m-d', strtotime('-10 days', strtotime($end)));
            $end = date('Y-m-d', strtotime($end));
        }
        if ($direction == 1) {
            $response['end'] = date('m/d/Y', strtotime($end));
        } else {
            $response['start'] = date('m/d/Y', strtotime($start));
        }
        
        $html = $this->render_times_by_days($start, $end);
        
        $response['status'] = 1;
        $response['html'] = $html;
        
        echo json_encode($response);
    }
    
    /**
     * Safety
     */
    public function load_more_safety()
    {
        if (!$this->session->has_userdata('worker_id')) {
            $relog = $this->loginBySessionHash(isset($_POST['mobile']) ? $_POST['mobile'] : 0);
            if (!$relog) {
                echo json_encode(['status' => 0, 'reload' => 1]);
                exit();
            }
        }
        
        $this->db->select('safety_cards.safety_name, worker_safety_cards.does_not_expire, worker_safety_cards.expiration');
        $this->db->from('worker_safety_cards');
        $this->db->join('safety_cards', 'safety_cards.id = worker_safety_cards.safety_card_id', 'INNER');
        $this->db->where('worker_safety_cards.worker_id', $this->session->userdata('worker_id'));
        if ($this->input->post('active', 0) == 1) {
            $this->db->where('(worker_safety_cards.does_not_expire = 1 OR worker_safety_cards.expiration >= "' . date('Y-m-d') . '")');
        } else {
            $this->db->where('(worker_safety_cards.does_not_expire = 0 AND worker_safety_cards.expiration < "' . date('Y-m-d') . '")');
        }
        $this->db->order_by('worker_safety_cards.start_date DESC');
        $this->db->offset($this->input->post('offset', 0));
        $this->db->limit(10);
        $query = $this->db->get();
        $cards = $query->result();
        
        $mobile = $this->input->post('mobile', 0);
        
        $html = '';
        $cnt = 0;
        if ($cards) {
            $cnt = count($cards);
            foreach ($cards as $card) {
                $html .= '<div class="m-safety-item ' . ($mobile ? '' : 'd-safety-item') . '">';
                if ($this->input->post('active', 0) == 1) {
                    $html .= '<i class="fa fa-check-circle ' . ($mobile ? '' : 'clear-button') . '"></i>';
                } else {
                    $html .= '<i class="fa fa-times-circle ' . ($mobile ? '' : 'clear-button') . '"></i>';
                }
                $html .= '<p class="safety-card-name ' . ($mobile ? '' : 'd-safety-card-name') . '">' . $card->safety_name . '</p>';
                if ($this->input->post('active', 0) == 1) {
                    $html .= '<p class="safety-time ' . ($mobile ? '' : 'd-safety-time') . '">';
                    $html .= !$card->does_not_expire ? '+' . ceil((strtotime($card->expiration) - time())/86400) : '';
                    $html .= '</p>';
                } else {
                    $html .= '<p class="safety-time ' . ($mobile ? '' : 'd-safety-time') . '">' . $card->expiration . '</p>';
                }
                $html .= '</div>';
            }
        }
        
        echo json_encode(['status' => 1, 'html' => $html, 'count' => $cnt]);
    }
    
    public function search_part()
    {
        $term = $this->input->post('term', '');
        if (!$term) {
            echo json_encode(['status' => 1]);
            exit();
        }
        
        $this->db->select('mcn_product.id, mcn_manufacturer.name AS manufacturer, mcn_product.name AS product_name, mcn_product.part_number, mcn_product.average_cost');
        $this->db->from('mcn_product');
        $this->db->join('mcn_manufacturer', 'mcn_manufacturer.id = mcn_product.mcn_manufacturer_id AND mcn_manufacturer.remove = 0', 'INNER');
        $this->db->join('mcn_product_vari', 'mcn_product.id = mcn_product_vari.mcn_product_id AND mcn_product_vari.remove = 0', 'LEFT OUTER');
        $this->db->where('mcn_product.remove', 0);
        $this->db->where('(mcn_manufacturer.name LIKE "%' . $term . '%" OR mcn_product.name LIKE "%' . $term . '%" OR mcn_product.part_number LIKE "%' . $term . '%" OR mcn_product_vari.product_number LIKE "%' . $term . '%")', null);
        
        $query = $this->db->get();
        $products = $query->result_array();
        
        if (!$products) {
            echo json_encode(['status' => 1]);
            exit();
            
        } else {
            $html = '';
            foreach ($products as $product) {
                $html .= '
                    <a class="part-result-item" href="#" product_id="' . $product['id'] . '" average_cost="' . $product['average_cost'] . '">
                        <p class="part-info">
                            <span class="part-name">' . $product['product_name'] . '</span>:
                            OEM: <span class="oem-number">' . $product['part_number'] . '</span>
                            Part: <span class="part-number">' . $product['part_number'] . '</span><br/>
                            Vendor: <span class="vendor-name">' . $product['manufacturer'] . '</span>
                        </p>
                        <p class="part-cost">$' . number_format($product['average_cost']) . '</p>
                    </a>
                ';
            }
            
            echo json_encode(['status' => 1, 'html' => $html, 'count' => count($products)]);
            exit();
        }
        
        echo json_encode(['status' => 1]);
        exit();
    }
    
    public function add_part()
    {
        echo json_encode(['status' => 0, 'message' => 'You have no permissions to create part!']);
        exit;
        
        $product_name = $this->input->post('name', '');
        if (!$product_name) {
            echo json_encode(['status' => 0, 'message' => 'Please input product name.']);
            exit();
        }
        $vendor_name = $this->input->post('vendor', '');
        if (!$vendor_name) {
            echo json_encode(['status' => 0, 'message' => 'Please input vendor.']);
            exit();
        }
        
        $this->db->trans_begin();
        
        $this->db->select('c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result_array();
        if (!$workers) {
            echo json_encode(['status' => 0, 'message' => 'There was an error while trying to add part.']);
            exit();
        }
        $worker = $workers[0];
        
        $fields = [
            'c_id' => $worker['c_id'],
            'd_id' => $worker['d_id'],
            'name' => $product_name,
            'part_number' => $this->input->post('part_number', ''),
            'notes' => $this->input->post('notes', ''),
            'location'  => $this->input->post('location', ''),
            'last_cost' => $this->input->post('average_cost', 0),
            'average_cost' => $this->input->post('average_cost', 0)
        ];
        
        $this->db->select('id');
        $this->db->from('mcn_manufacturer');
        $this->db->where('remove', 0);
        $this->db->where('id', $this->input->post('vendor_id', ''));
        $this->db->where('LOWER(name) = "' . strtolower($vendor_name) . '"', null);
        $query = $this->db->get();
        $vendors = $query->result_array();
        
        if ($vendors) {
            $vendor = $vendors[0];
            $fields['mcn_manufacturer_id'] = $vendor['id'];
        } else {
            $this->db->select('id');
            $this->db->from('mcn_manufacturer');
            $this->db->where('remove', 0);
            $this->db->where('LOWER(name) = "' . strtolower($vendor_name) . '"', null);
            $query = $this->db->get();
            $vendors = $query->result_array();
            
            if ($vendors) {
                $vendor = $vendors[0];
                $fields['mcn_manufacturer_id'] = $vendor['id'];
            } else {
                
                $this->db->insert('mcn_manufacturer', [
                    'c_id' => $worker['c_id'],
                    'd_id' => $worker['d_id'],
                    'name' => $this->input->post('vendor', '')
                ]);
                
                $fields['mcn_manufacturer_id'] = $this->db->insert_id();
            }
        }
        
        $this->db->insert('mcn_product', $fields);
        $product_id = $this->db->insert_id();
            
        if (!$product_id || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'message' => 'There was an error while trying to add part, please try again!']);
            exit;
        } else {
            $this->db->trans_commit();
            echo json_encode([
                'status' => 1,
                'message' => 'Product has been added successfully.',
                'product_id' => $product_id,
                'average_cost' => $this->input->post('average_cost', 0),
                'product_name' => $this->input->post('name', ''),
                'part_number' => $this->input->post('part_number', '')
            ]);
            exit;
        }
    }
    
    public function complete_job()
    {
        $res = $this->actual_time_keeping_model->stop($this->input->post('time_id', true), $this->input->post('timer', true));
        
        if ($res['status'] == 1) {
            $this->db->where('wb_task_id', $this->input->post('wb_task_id', ''));
            $this->db->update('work_board_task', ['completed' => 1]);
            
            echo json_encode(['status' => 1, 'message' => 'Job has been marked as completed!']);
            exit();
        } else {
            echo json_encode(['status' => 0, 'message' => 'There was an error while trying to mark task as completed, please try again!']);
        }
    }
}