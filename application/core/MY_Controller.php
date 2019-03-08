<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    //set the class variable.
    var $template  = array();
    var $data      = array();
    

    public function __construct()
    {
        parent::__construct();
        
        $this->lang->load('general', 'english');
    }

    //Load layout
    public function main()
    {
        // making temlate and send data to view.
        $this->template['header']   = $this->load->view('layouts/header', $this->data, true);
        $this->template['banner']     = $this->load->view('layouts/banner', $this->data, true);
        $this->template['main']   = $this->load->view($this->main, $this->data, true);
        $this->template['footer']   = $this->load->view('layouts/footer', $this->data, true);

        $this->load->view('layouts/index', $this->template);
    }

    public function login()
    {
        $this->template['header']   = $this->load->view('layouts/header', $this->data, true);
        $this->template['main']   = $this->load->view($this->main, $this->data, true);
        $this->template['footer']   = $this->load->view('layouts/footer', $this->data, true);

        $this->load->view('layouts/login', $this->template);
    }
    
    public function mobile_layout()
    {
        $this->template['header']   = $this->load->view('layouts/header_mobile', $this->data, true);
        $this->template['banner']     = $this->load->view('layouts/banner_mobile', $this->data, true);
        $this->template['main']   = $this->load->view($this->main, $this->data, true);
        $this->template['footer']   = $this->load->view('layouts/footer_mobile', $this->data, true);
        
        $this->load->view('layouts/mobile', $this->template);
    }
    
    protected function pullSidebarData()
    {
        $this->load->model('working_session_model');
        $ws = $this->working_session_model->get([
            'remove' => 0,
            'working_date' => date_by_timezone(time(), 'Y-m-d'),
            'worker_id' => $this->session->userdata('worker_id'),
            'end_time' => NULL,
            'start_time IS NOT ' => NULL,
            'start_time <= ' => time()
        ]);
        
        if ($ws) {
            $this->data['clocked_in'] = 1;
        } else {
            $this->data['clocked_in'] = 0;
        }
        
        $this->db->select('COUNT(DISTINCT work_order_item.id) AS wo_cnt');
        
        $this->db->from('work_order_item');
        $this->db->join('work_order_item_job', 'work_order_item_job.work_order_item_id = work_order_item.id', 'INNER');
        $this->db->join('work_order_item_job_worker', 'work_order_item_job.id = work_order_item_job_worker.work_order_item_job_id', 'INNER');
        
        $this->db->where('(work_order_item_job.status IN (0, 1) AND work_order_item.status IN (0,1))');
        $this->db->where('work_order_item_job_worker.worker_id', $this->session->userdata('worker_id'));
        
        $query = $this->db->get();
        
        $res = $query->result();
        if ($res) {
            $item = $res[0];
            $this->data['wo_cnt'] = $item->wo_cnt;
        }
    }
    
    public function createDropdownData($objs, $key, $value)
    {
        if (empty($objs)) {
            return [];
        }
        
        $res = [];
        foreach ($objs as $obj) {
            $res[$obj->$key] = $obj->$value;
        }
        return $res;
    }
    
    /**
     * For some cases, employee can not stop a task due to connection issue, cookie is used to save end_time
     */
    protected function sync_with_cookies()
    {
        $this->load->model(['working_session_model', 'actual_time_keeping_model']);
        
        if ($tasks = json_decode(get_cookie('tasks'))) {
            $this->db->trans_begin();
            
            foreach ($tasks as $task) {
                $this->actual_time_keeping_model->stop($task->time_id, $task->timer);
            }
            
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                
            } else {
                $this->db->trans_commit();
            
                delete_cookie('tasks');
            }
        }
        
        // For case that employee does not clock out within a day
        $working = $this->working_session_model->get([
            'worker_id' => $this->session->userdata('worker_id'),
            'start_time IS NOT ' => NULL,
            'end_time' => NULL,
            'working_date < ' => date_by_timezone(time(), 'Y-m-d')
        ]);
        
        if ($working) {
            // Because of the difference between timezones of employee and server,
            // We calculate end_time = start_time + number of seconds from clock_in (employee timezone) to 23:59:59 (employee timezone)
            $start_time = date_by_timezone($working->start_time, 'Y-m-d H:i:s');
            $end_time = date_by_timezone($working->start_time, 'Y-m-d 23:59:59');
            
            $this->working_session_model->clock_out($working->start_time + (strtotime($end_time) - strtotime($start_time)));
        }
    }

    public function get_password_hash($password)
    {
        $db = (array)get_instance()->db;
        $dbc = mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);
        return mysqli_real_escape_string($dbc, hash_hmac('sha256', $password, 'ghis$53^', true));
    }
    
    protected function save_worker_to_session($worker)
    {
        $this->session->set_userdata('company_id', $worker->c_id);
        $this->session->set_userdata('worker_id', $worker->worker_id);
        $this->session->set_userdata('worker_name', $worker->first_name . ' ' . $worker->last_name);
        $this->session->set_userdata('timeformat', $worker->timeformat);
        
        $this->load->model('company_model');
        
        $timezone = $worker->timezone;
        if (!$timezone && $this->session->has_userdata('kiosk_id')) {
            $this->load->model('kiosk_model');
            $kiosk = $this->kiosk_model->get(['k_id' => $this->session->userdata('kiosk_id')]);
            if ($kiosk) {
                $timezone = $kiosk->timezone;
                
                $company = $this->company_model->get(array('company_id' => $kiosk->c_id));
                if ($company) {
                    $this->session->set_userdata('company_name', $company->c_name);
                }
            }
        }
        
        $company = $this->company_model->get(array('company_id' => $worker->c_id));
        if ($company && !$this->session->has_userdata('company_name')) {
            $this->session->set_userdata('company_name', $company->c_name);
        }
        if ($company && !$timezone) {
            $timezone = $company->timezone;
        }
        if (!$timezone) {
            $file = "/home/advan215/public_html/memberdata/"  . $worker->c_id . "/info/presets.xml";
            if (is_file($file)) {
                $companyXML = simplexml_load_file($file);
                $timezone = $this->getPresetData($companyXML, 'default->weather->timezone');
            }
        }
        $this->session->set_userdata('timezone', $timezone ? $timezone : 'America/Denver');
    }
    
    protected function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function getPresetData($xml, $data) {
        $data;
        $data=explode('->',$data);
        $D= $xml;
        foreach ($data as $e){
            if($D->$e){
                $D= $D->$e;
            }else{
                $D =false;
                break;
            }
             
        }
        if ($D){
            $D =(string)$D;
        }
        return $D;
    }
    
    protected function load_data()
    {
    	$this->load->model(['worker_model', 'work_board_task_model', 'actual_time_keeping_model', 'company_translation_model', 'default_display_time_model', 'split_table_model', 'settings_model']);
    	
    	$worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);

    	if ($worker) {
	    	$c_translation = $this->company_translation_model->get(['cid' => $worker->c_id, 'did' => $worker->d_id]);
	    	$this->data['need_translate'] = $c_translation ? $c_translation->active : 0;
	    	$this->data['active_sb'] = $this->split_table_model->get_active_split();
	    	$this->data['tasks'] = $this->get_tasks($worker->worker_id, $worker->c_id, $worker->d_id, false);
	    	
	    	$settings = $this->settings_model->getSettings($worker->c_id, $worker->d_id, 'edit_job_notes');
	    	if ($settings) {
	    	    $this->data['edit_job_notes'] = $settings->settings_value;
	    	} else {
	    	    $this->data['edit_job_notes'] = 1;
	    	}
    	} else {
    		$this->data['need_translate'] = 0;
    		$this->data['active_sb'] = 0;
    		$this->data['tasks'] = [];
    		$this->data['edit_job_notes'] = 0;
    	}
    	
    	// Get logged times
    	$times = '';
    	$logged_times = $this->actual_time_keeping_model->get_logged_times(date_by_timezone(time(), 'Y-m-d'));
    	if ($logged_times) {
	    	foreach ($logged_times as $record) {
	    		$times .= ('<br/>' . date_format_by_timezone($record->start_time, 'h:i:s A') . ' - ' . date_format_by_timezone($record->end_time, 'h:i:s A'));
	    	}
    	}
    	$this->data['logged_times'] = $times;
    }
    
    protected function load_mobile_data()
    {
    	$this->load->model(['department_model']);
    	
    	$this->data['departments'] = $this->department_model->get_assigned_departments($this->session->userdata('worker_id'));
    	
    	$sql = '
        SELECT feature.id, COALESCE(department_features.active, COALESCE(department_type_features.active, 1)) AS active
        FROM department
        INNER JOIN feature ON 1 = 1
        LEFT OUTER JOIN department_features ON feature.id = department_features.feature_id AND department.d_id = department_features.d_id
        LEFT OUTER JOIN department_type_features ON department_type_features.department_type_id = department.department_type_id AND department_type_features.feature_id = feature.id
        WHERE department.d_id = ' . $this->session->userdata('department_id');
    	
    	$query = $this->db->query($sql);
    	
    	$features = $query->result();
    	foreach ($features as $feature) {
    	    if ($feature->id == FEATURE_NOTES) {
    	       $this->session->set_userdata('FEATURE_NOTES', $feature->active);
    	    }
    	    if ($feature->id == FEATURE_GEONOTES) {
    	       $this->session->set_userdata('FEATURE_GEONOTES', $feature->active);
    	    }
    	    if ($feature->id == FEATURE_MOW_PATTERNS) {
    	       $this->session->set_userdata('FEATURE_MOW_PATTERNS', $feature->active);
    	    }
    	    if ($feature->id == FEATURE_CALENDAR) {
    	       $this->session->set_userdata('FEATURE_CALENDAR', $feature->active);
    	    }
    	}
    }
    
    protected function get_tasks($worker_id = null, $c_id = null, $d_id = null, $remove_hidden_jobs = false)
    {
    	$this->load->model(['worker_model', 'work_board_task_model', 'default_display_time_model', 'company_translation_model']);
    	
    	// Set current worker_id
    	if (null === $worker_id) {
    		$worker_id = $this->session->userdata('worker_id');
    	}
    	
    	// Set c_id && d_id by worker
    	if (null === $c_id || null === $d_id) {
    		$worker = $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
    		
    		if ($worker) {
    			$c_id = $worker->c_id;
    			$d_id = $worker->d_id;
    		}
    	}
    	
    	// Get workboard tasks for today
    	$tasks = $this->work_board_task_model->get_worker_tasks($worker_id, date_by_timezone(time(), 'Y-m-d'));
    	
    	// Filter Tasks
    	$tasks = $this->default_display_time_model->filter_tasks_by_display_time($tasks, $c_id, $d_id, $remove_hidden_jobs);
    	
    	//Load display time
    	return $tasks;
    }
    
    /**
     * Clear session data
     */
    protected function clearSession()
    {
    	$this->session->unset_userdata('worker_id');
    	$this->session->unset_userdata('worker_name');
    	$this->session->unset_userdata('timezone');
    	$this->session->unset_userdata('timeformat');
    	$this->session->unset_userdata('company_id');
    	$this->session->unset_userdata('company_name');
    	$this->session->unset_userdata('kiosk_name');
    	$this->session->unset_userdata('mobile');
    }
    
    protected function loginBySessionHash($mobile)
    {
        if ($mobile) {
            if ($hash = get_cookie('mobile_hash')) {
                $where = array('mobile_hash' => $hash);
            }
        } else {
            if ($hash = get_cookie('kiosk_hash')) {
                $where = array('kiosk_hash' => $hash);
            }
        }
        if (isset($where)) {
            $this->load->model(['worker_model']);
            $worker = $this->worker_model->get_by_attributes($where);
            
            if ($worker) {
                $this->save_worker_to_session($worker);
                if ($mobile) {
                    $this->session->set_userdata('mobile', "1");
                } else {
                    $this->session->set_userdata('mobile', "0");
                }
                
                return true;
            }
        }
        
        if ($mobile) {
            delete_cookie('mobile_hash');
        } else {
            delete_cookie('kiosk_hash');
        }
        return false;
    }
    
    protected function isFeatureActive($feature)
    {
        $this->db->select('active');
        $this->db->from('application_features');
        $this->db->where('feature', $feature);
        $query = $this->db->get();
        
        $features = $query->result();
        if (!$features) {
            return true;
        }
        $feature = $features[0];
        if (!$feature->active) {
            return false;
        }
        
        return true;
    }
    
    protected function isDepartmentFeatureActive($d_id, $feature)
    {
        $this->db->select('active');
        $this->db->from('department_features');
        $this->db->where('d_id', $d_id);
        $this->db->where('feature_id', $feature);
        $query = $this->db->get();
        
        $data = $query->result();
        if ($data) {
            $item = $data[0];
            if (!$item->active) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function refreshMechanicBoard($date, $type)
    {
        $this->db->select('workers.worker_id, workers.c_id, workers.d_id, companies.timezone');
        $this->db->from('workers');
        $this->db->join('companies', 'companies.company_id = workers.c_id', 'INNER');
        $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        
        $worker = $workers[0];
        if ($worker) {
            $c_id = $worker->c_id;
            $d_id = $worker->d_id;
            
            $this->db->select('id');
            $this->db->from('refresh_mechanic_board');
            $this->db->where('c_id', $c_id);
            $this->db->where('d_id', $d_id);
            $this->db->where('w_date', date('Y-m-d', strtotime($date)));
            $query = $this->db->get();
            
            $data = $query->result();
            
            $fields = [
                'work_order' => 0,
                'mow_pattern' => 0,
                'height_of_cut' => 0,
                'equipment' => 0,
                'updated_time' => time()
            ];
            
            $fields[$type] = 1;
            
            if ($data) {
                $item = $data[0];
                $this->db->where('id', $item->id);
                $this->db->update('refresh_mechanic_board', $fields);
            } else {
                $fields['c_id'] = $c_id;
                $fields['d_id'] = $d_id;
                $fields['w_date'] = date('Y-m-d', strtotime($date));
                
                $this->db->insert('refresh_mechanic_board', $fields);
            }
        }
    }
    
    
    /**
     * Past Time
     */
    protected function render_times_by_days($start, $end)
    {
        $this->load->model(['working_session_model']);
        $days = $this->working_session_model->pull_clock_in_times($this->session->userdata('worker_id'), $start, $end);
        
        $html = '';
        foreach ($days as $day => $times) {
            $html .= '
                <li id="m-time-item-' . date('m-d-Y', strtotime($day)) . '" class="m-time-item ' . ($day == date_by_timezone(time(), 'Y-m-d') ? 'current-date' :  (date('N', strtotime($day)) == 6 || date('N', strtotime($day)) == 7 ? 'off-date' : '')) . '">
                <div class="m-date-info">
                    <div class="m-time-date">
                        <div class="sc-month">' . date('F', strtotime($day)) . '</div>
                        <div class="sc-date">
                            <p class="sc-date-num">' . date('d', strtotime($day)) . '</p>
                            <p class="sc-day">' . date('l', strtotime($day)) . '</p>
                        </div>
                    </div>
                    <div class="m-time-task">
                        <ul class="m-task-list">';
            
            foreach ($times as $time) {
                $html .= '
                    <li class="task-list-item">
                        <span>' . date_format_by_timezone($time->start_time, 'g:ia') . ' - ' . date_format_by_timezone($time->end_time, 'g:ia') . '</span>
                    </li>
                ';
            }
            
            $html .= '
                        </ul>
                    </div>
                </div>
            </li>
            ';
        }
        
        return $html;
    }
    
    /**
     * WorkOrder
     * @param $work_order_item_id
     */
    protected function getWorkOrderData($work_order_item_id = null)
    {
        $this->db->select('
            work_order_item.id, work_order_item.name, mcn_manufacturer.name AS manufacturer, equipment_type.model, work_order_item.status,
            work_order_item.description, equipment_type.short_name, equipment.equipment_model_id, work_order_item_job.name AS job_name,
            work_order_item_job.notes, work_order_item_job.scheduled, COALESCE(wb_task.act_hr, 0) AS act_hr,
            work_order_item_job.status AS job_status, wb_task.wb_task_id, wb_task.time_id, equipment.id AS equipment_id,
            work_order_item_job.id AS work_order_item_job_id, COALESCE(e_hours.equipment_hours, 0) AS equipment_hours,
            COALESCE(status_tbl_1.status, status_tbl.status) AS equipment_status, equipment_type.id AS equipment_type_id
        ');
        $this->db->distinct();
        
        $this->db->from('work_order_item');
        $this->db->join('equipment', 'work_order_item.equipment_id = equipment.id', 'INNER');
        $this->db->join('equipment_type', 'equipment_type.id = equipment.equipment_type_id', 'INNER');
        $this->db->join('mcn_manufacturer', 'mcn_manufacturer.id = equipment_type.manufacturer_id', 'INNER');
        $this->db->join('work_order_item_job', 'work_order_item_job.work_order_item_id = work_order_item.id', 'INNER');
        $this->db->join('work_order_item_job_worker', 'work_order_item_job.id = work_order_item_job_worker.work_order_item_job_id', 'INNER');
        
        $this->db->join('(
            SELECT equipment_status.equipment_id, equipment_status.status
            FROM equipment_status
            WHERE start_date <= "' . date_by_timezone(time(), 'Y-m-d H:i:s') . '" AND end_date >= "' . date_by_timezone(time(), 'Y-m-d H:i:s') . '"
            GROUP BY equipment_id
        ) status_tbl_1', 'status_tbl_1.equipment_id = equipment.id', 'LEFT OUTER');
        
        $this->db->join('(
            SELECT equipment_status.equipment_id, equipment_status.status FROM equipment_status
			INNER JOIN (
				SELECT equipment_id, MAX(end_date) AS end_date FROM equipment_status
				WHERE (
                    (start_date >= "' . date_by_timezone(time(), 'Y-m-d 00:00:00') . '" AND start_date <= "' . date_by_timezone(time(), 'Y-m-d 23:59:59') . '")
                    OR end_date <= "' . date_by_timezone(time(), 'Y-m-d 23:59:59') . '"
                )
				GROUP BY equipment_id
			) max_status ON equipment_status.equipment_id = max_status.equipment_id AND equipment_status.end_date = max_status.end_date
        ) status_tbl', 'status_tbl.equipment_id = equipment.id', 'LEFT OUTER');
        
        $this->db->join('(
            SELECT work_order_item_job_task.id AS wo_job_task_id, work_order_item_job_task.work_order_item_job_id, work_board_task.wb_task_id,
                work_board_task.est_hr, act.act_hr, actual_time_keeping.time_id, work_board_task.worker_id
            FROM work_order_item_job_task
            INNER JOIN work_board_task ON work_board_task.wb_task_id = work_order_item_job_task.wb_task_id
            INNER JOIN work_board ON work_board_task.work_board_id = work_board.work_board_id
            LEFT OUTER JOIN actual_time_keeping ON actual_time_keeping.workboard_task_id = work_board_task.wb_task_id AND actual_time_keeping.remove = 0 AND actual_time_keeping.end_time IS NULL
            LEFT OUTER JOIN (
                SELECT workboard_task_id, SUM(COALESCE(end_time, UNIX_TIMESTAMP()) - start_time) AS act_hr
                FROM actual_time_keeping
                GROUP BY workboard_task_id
            ) act ON act.workboard_task_id = work_board_task.wb_task_id
            WHERE work_board.w_date = "' . date_by_timezone(time(), 'Y-m-d') . '"
        ) wb_task', 'wb_task.work_order_item_job_id = work_order_item_job.id AND wb_task.worker_id = work_order_item_job_worker.worker_id', 'LEFT OUTER');
        
        $this->db->join('(
            SELECT equipment_id, SUM(equipment_hours) AS equipment_hours
            FROM equipment_update
            GROUP BY equipment_id
        ) e_hours', 'e_hours.equipment_id = equipment.id', 'LEFT OUTER');
        
        $this->db->where('(work_order_item.status IN (0,1) AND work_order_item_job.status IN (0, 1))');
        $this->db->where('work_order_item_job_worker.worker_id', $this->session->userdata('worker_id'));
        
        if ($work_order_item_id) {
            $this->db->where('work_order_item.id', $work_order_item_id);
        }
        
        $this->db->order_by('work_order_item_job_worker.sort_order');
        
        $query = $this->db->get();
        $data = $query->result();
        
        $wos = [];
        if ($data) {
            foreach ($data as $item) {
                if ($item->status == 0) {
                    $status_class = 'wo-status-not';
                } else if ($item->status == 1) {
                    $status_class = 'wo-status-progress';
                } else if ($item->status == 2) {
                    $status_class = 'wo-status-complete';
                } else if ($item->status == 3) {
                    $status_class = 'wo-status-skipped';
                } else if ($item->status == 4) {
                    $status_class = 'wo-status-complete-on-sat';
                }
                
                if (!isset($wos[$item->id])) {
                    $wos[$item->id] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'status' => $item->status,
                        'status_class' =>  $status_class,
                        'equipment_type_id' => $item->equipment_type_id,
                        'equipment_type' => $item->manufacturer . ' ' . $item->model,
                        'equipment' => ($item->short_name ? $item->short_name : $item->model) . ' ' . $item->equipment_model_id,
                        'equipment_hours' => $item->equipment_hours,
                        'equipment_id' => $item->equipment_id,
                        'equipment_status' => $item->equipment_status,
                        'description' => $item->description,
                        'jobs' => []
                    ];
                }
                
                $job = [
                    'work_order_item_job_id' => $item->work_order_item_job_id,
                    'job_name' => $item->job_name,
                    'scheduled' => $item->scheduled,
                    'act_hr' => $item->act_hr,
                    'job_notes' => $item->notes,
                    'wb_task_id' => $item->wb_task_id,
                    'status' => $item->job_status,
                    'time_id' => $item->time_id
                ];
                
                if ($work_order_item_id) {
                    $job['products'] = $this->getWoJobProducts($item->work_order_item_job_id);
                    $job['notes'] = $this->getWoJobNotes($item->work_order_item_job_id);
                }
                
                $wos[$item->id]['jobs'][] = $job;
                
                $wos[$item->id]['e_specs'] = $this->pullEquipmentSpecs($wos[$item->id]['equipment_type_id']);
            }
        }
        
        if ($work_order_item_id) {
            if (isset($wos[$work_order_item_id])) {
                $this->data['work_order'] = $wos[$work_order_item_id];
            } else {
                $this->data['work_order'] = null;
            }
        } else {
            $this->data['work_orders'] = $wos;
        }
    }
    
    /**
     * WorkOrder
     */
    protected function getEquipmentData()
    {
        $viewdate = date_by_timezone(time(), 'Y-m-d');
        
        $this->db->select('worker_id, c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        $worker = $workers[0];
        
        $this->db->select('
            equipment_type.id, mcn_manufacturer.name AS manufacturer, equipment_type.model, equipment_type.short_name,
            equipment.equipment_model_id, equipment.id AS equipment_id, equipment.short_description
        ');
        
        $this->db->distinct(1);
        $this->db->from('equipment_type');
        $this->db->join('equipment', 'equipment.equipment_type_id = equipment_type.id', 'INNER');
        $this->db->join('mcn_manufacturer', 'mcn_manufacturer.id = equipment_type.manufacturer_id', 'INNER');
        $this->db->join('equipment_department', 'equipment.id = equipment_department.equipment_id AND equipment_department.active = 1', 'INNER');
        $this->db->where('equipment_type.remove', 0);
        $this->db->where('equipment.remove', 0);
        $this->db->where('equipment_type.c_id', $worker->c_id);
        $this->db->where('equipment_department.c_id', $worker->c_id);
        $this->db->where('equipment_department.d_id', $worker->d_id);
        $this->db->order_by('mcn_manufacturer.name, equipment_type.model, equipment.equipment_model_id');
        $query = $this->db->get();
        
        $equipments = $query->result_array();
        
        $query = $this->db->query('
            SELECT equipment_status.equipment_id, equipment_status.status, equipment_status.note,
                CASE WHEN (start_date <= "' . $viewdate . ' ' . date('H:i:00') . '" AND end_date >= "' . $viewdate . ' ' . date('H:i:00') . '") THEN 1
                ELSE 0 END AS prior
            FROM equipment_status
            INNER JOIN equipment ON equipment.id = equipment_status.equipment_id
            INNER JOIN equipment_type ON equipment.equipment_type_id = equipment_type.id AND equipment_type.c_id = ' . $worker->c_id . '
            WHERE (start_date <= "' . $viewdate . ' ' . date('H:i:00') . '" AND end_date >= "' . $viewdate . ' ' . date('H:i:00') . '")
                OR (start_date >= "' . $viewdate . ' 00:00:00" AND start_date <= "' . $viewdate . ' 23:59:59")
                OR end_date <= "' . $viewdate . ' 23:59:59"
            ORDER BY equipment_id, prior DESC, end_date DESC
        ');
        
        $status_data = $query->result();
        
        $statuses = [];
        if ($status_data) {
            foreach ($status_data as $status_item) {
                if (!isset($statuses[$status_item->equipment_id])) {
                    $statuses[$status_item->equipment_id] = ['status' => $status_item->status, 'status_note' => $status_item->note];
                }
            }
        }
        
        $equipment_types = [];
        foreach ($equipments as $equipment) {
            if (!isset($equipment_types[$equipment['id']])) {
                $equipment_types[$equipment['id']] = [
                    'id' => $equipment['id'],
                    'type_name' => $equipment['manufacturer'] . ' ' . $equipment['model'],
                    'equipments' => []
                ];
            }
            
            $equipment_types[$equipment['id']]['equipments'][] = [
                'equipment_id' => $equipment['equipment_id'],
                'equipment_name' => ($equipment['short_name'] ? $equipment['short_name'] : $equipment['model']) . ' ' . $equipment['equipment_model_id'],
                'short_description' => $equipment['short_description'],
                'status' => isset($statuses[$equipment['equipment_id']]) ? $statuses[$equipment['equipment_id']]['status'] : 1,
                'status_note' => isset($statuses[$equipment['equipment_id']]) ? $statuses[$equipment['equipment_id']]['status_note'] : ''
            ];
        }
        
        $this->data['equipment_types'] = $equipment_types;
    }
    
    /**
     * WorkOrder
     * @param $work_order_item_job_id
     * @return array|string[][]|NULL[][]
     */
    protected function getWoJobNotes($work_order_item_job_id)
    {
        if (!$work_order_item_job_id) {
            return [];
        }
        
        $this->db->select('
            work_order_item_job_notes.id, COALESCE(workers.first_name, users.first_name) AS first_name, COALESCE(workers.last_name, users.last_name) AS last_name,
            work_order_item_job_notes.add_time, work_order_item_job_notes.notes'
            );
        $this->db->from('work_order_item_job_notes');
        $this->db->join('workers', 'workers.worker_id = work_order_item_job_notes.worker_id', 'LEFT OUTER');
        $this->db->join('users', 'users.id = work_order_item_job_notes.user_id', 'LEFT OUTER');
        $this->db->where('work_order_item_job_notes.work_order_item_job_id', $work_order_item_job_id);
        
        $query = $this->db->get();
        $data = $query->result();
        
        $res = [];
        if ($data) {
            foreach ($data as $item) {
                $res[] = [
                    'id' => $item->id,
                    'worker' => $item->first_name . ' ' . $item->last_name,
                    'add_time' => date_by_timezone($item->add_time, 'm/d/Y'),
                    'notes' => $item->notes
                ];
            }
        }
        
        return $res;
    }
    
    /**
     * WorkOrder
     * @param $work_order_item_job_id
     * @return array|number[][]|NULL[][]
     */
    protected function getWoJobProducts($work_order_item_job_id)
    {
        if (!$work_order_item_job_id) {
            return [];
        }
        
        $this->db->select('
            work_order_item_job_product.id, work_order_item_job_product.product_id, work_order_item_job_product.quantity, work_order_item_job_product.cost,
            CASE WHEN CONCAT("", work_order_item_job_product.product_id) = CONCAT("", mcn_product.id) THEN mcn_product.name
            WHEN CONCAT("", work_order_item_job_product.product_id) = CONCAT(mcn_product.id, "-", mcn_product_vari.id)
            THEN CONCAT(mcn_product.name, IF (mcn_product_vari.product_number IS NULL, "", CONCAT(" (", mcn_product_vari.product_number, ")"))) END AS product_name
        ');
        $this->db->from('mcn_product');
        $this->db->join('mcn_product_vari', 'mcn_product_vari.mcn_product_id = mcn_product.id AND mcn_product_vari.remove = 0', 'LEFT OUTER');
        $this->db->join(
            'work_order_item_job_product',
            '(CONCAT("", work_order_item_job_product.product_id) = CONCAT("", mcn_product.id)
            OR CONCAT("", work_order_item_job_product.product_id) = CONCAT(mcn_product.id, "-", mcn_product_vari.id))',
            'INNER'
            );
        $this->db->where('work_order_item_job_product.work_order_item_job_id', $work_order_item_job_id);
        $this->db->where('work_order_item_job_product.active', 1);
        
        $query = $this->db->get();
        $data = $query->result();
        
        $res = [];
        if ($data) {
            foreach ($data as $item) {
                $res[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'name' => $item->product_name,
                    'cost' => $item->cost,
                    'total' => $item->cost * $item->quantity
                ];
            }
        }
        
        return $res;
    }
    
    /**
     * WorkOrder
     */
    protected function check_wo_permissions() {
        $this->db->select('add_new_work_order');
        $this->db->from('worker_permission');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $permissions = $query->result();
        
        if ($permissions) {
            $permission = $permissions[0];
            if (!$permission->add_new_work_order) {
                return null;
            }
        }
        
        $this->db->select('workers.worker_id, workers.c_id, workers.d_id, companies.timezone');
        $this->db->from('workers');
        $this->db->join('companies', 'companies.company_id = workers.c_id', 'INNER');
        $this->db->where('workers.worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        
        $worker = $workers[0];
        
        $timezone = $worker->timezone;
        if (!$timezone) {
            $file = "/home/asbdev/public_html/memberdata/"  . $worker->c_id . "/info/presets.xml";
            if (is_file($file)) {
                $companyXML = simplexml_load_file($file);
                $timezone = $this->getPresetData($companyXML, 'default->weather->timezone');
            }
        }
        if (!$timezone) {
            $timezone = 'America/Denver';
        }
        date_default_timezone_set($timezone);
        
        return $worker;
    }
    
    /**
     * WorkOrder
     * @param $work_order_item_id
     * @param $index
     * @param $task_id
     */
    protected function create_wo_job_item($work_order_item_id, $index, $task_id) {
        $this->db->select('task_id, est_hours');
        $this->db->from('tasks');
        $this->db->where('task_id', $task_id);
        $query = $this->db->get();
        $tasks = $query->result();
        if (!$tasks) {
            return null;
        }
        $task = $tasks[0];
        
        $this->db->insert('work_order_item_job', [
            'work_order_item_id' => $work_order_item_id,
            'name' => (isset($_POST['job_name']) && isset($_POST['job_name'][$index])) ? $_POST['job_name'][$index] : $_POST['wo_name'],
            'notes' => (isset($_POST['notes']) && isset($_POST['notes'][$index])) ? $_POST['notes'][$index] : '',
            'repair_type' => (isset($_POST['repair_type']) && isset($_POST['repair_type'][$index])) ? $_POST['repair_type'][$index] : 'repair',
            'task_id' => $task_id,
            'scheduled' => (isset($_POST['est_hr']) && isset($_POST['est_hr'][$index]) && $_POST['est_hr'][$index]) ? $_POST['est_hr'][$index] : $task->est_hours,
            'book_rate' => (isset($_POST['book_rate']) && isset($_POST['book_rate'][$index])) ? $_POST['book_rate'][$index] : '',
            'status' => 0
        ]);
        $work_order_item_job_id = $this->db->insert_id();
        
        if (isset($_POST['part_id']) && isset($_POST['part_id'][$index])) {
            $part_ids = $_POST['part_id'][$index];
            foreach ($part_ids as $part_index => $part_id) {
                if (!$part_id) {
                    continue;
                }
                
                $part_id_els = explode('-', $part_id);
                $this->db->select('id, average_cost');
                $this->db->from('mcn_product');
                $this->db->where('id', $part_id_els[0]);
                $query = $this->db->get();
                $parts = $query->result_array();
                if (!$parts) {
                    continue;
                }
                $part = $parts[0];
                
                $this->db->insert('work_order_item_job_product', [
                    'work_order_item_job_id' => $work_order_item_job_id,
                    'product_id' => $part_id,
                    'quantity' => (isset($_POST['quantity']) && isset($_POST['quantity'][$index]) && isset($_POST['quantity'][$index][$part_index])) ? $_POST['quantity'][$index][$part_index] : 1,
                    'cost' => (isset($_POST['unit_price']) && isset($_POST['unit_price'][$index]) && isset($_POST['unit_price'][$index][$part_index])) ? $_POST['unit_price'][$index][$part_index] : $part['average_cost'],
                    'active' => 1
                ]);
            }
        }
        
        $this->db->select('MAX(sort_order) AS sort_order');
        $this->db->from('work_order_item_job_worker');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        
        $data = $query->result_array();
        if ($data) {
            $so = $data[0];
        } else {
            $so = null;
        }
        
        $this->db->insert('work_order_item_job_worker', [
            'work_order_item_job_id' => $work_order_item_job_id,
            'worker_id' => $this->session->userdata('worker_id'),
            'sort_order' => $so ? $so['sort_order'] + 1 : 1,
            'active' => 1
        ]);
        
        return $work_order_item_job_id;
    }
    
    /**
     * WorkOrder
     * @param  $equipment_type_id
     */
    protected function pullEquipmentSpecs($equipment_type_id)
    {
        $this->db->select('equipment_type_specs.id, specs.spec_name, equipment_type_specs.spec_measure_id, equipment_type_specs.value, spec_measure.measure');
        $this->db->from('equipment_type_specs');
        $this->db->join('specs', 'specs.id = equipment_type_specs.spec_id', 'INNER');
        $this->db->join('spec_measure', 'spec_measure.id = equipment_type_specs.spec_measure_id', 'INNER');
        $this->db->where('equipment_type_id', $equipment_type_id);
        $query = $this->db->get();
        
        return $query->result_array();
    }
}