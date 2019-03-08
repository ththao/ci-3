<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Schedule extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->session->has_userdata('worker_id')) {
            redirect('mobile?redirect=' . urlencode('schedule'));
        }
        
        $this->data['page'] = 'schedule';
    }

    /**
     * Load workboard
     */
    public function index()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/new-style.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/schedule.js')
        ];
        
        $this->pullSidebarData();
        
        $this->data['schedules_by_days'] = $this->render_schedules_by_days(date_by_timezone(strtotime('-1 days'), 'Y-m-d'), date_by_timezone(strtotime('+9 days'), 'Y-m-d'));
        $this->main = 'schedule/index';
        $this->main();
    }

    /**
     * Load workboard
     */
    public function mobile()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/m.style.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/schedule_mobile.js')
        ];
        
        $this->data['schedules_by_days'] = $this->render_mobile_schedules_by_days(date_by_timezone(strtotime('-1 days'), 'Y-m-d'), date_by_timezone(strtotime('+9 days'), 'Y-m-d'));

        $this->main = 'schedule/mobile';
        $this->mobile_layout();
    }

    public function load_schedules()
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

        $html = $this->render_schedules_by_days($start, $end);

        $response['status'] = 1;
        $response['html'] = $html;

        echo json_encode($response);
    }
    
    public function load_mobile_schedules()
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
        
        $html = $this->render_mobile_schedules_by_days($start, $end);
        
        $response['status'] = 1;
        $response['html'] = $html;
        
        echo json_encode($response);
    }
    
    private function render_schedules_by_days($start, $end)
    {
        $this->load->model(['worker_schedule_model', 'department_model', 'off_request_model', 'off_request_note_model']);
        $days = $this->worker_schedule_model->pull_schedules($this->session->userdata('worker_id'), $start, $end);
        $html = '';
        foreach ($days as $day => $day_data) {
            $schedules = isset($day_data['schedules']) ? $day_data['schedules'] : array();
            $tasks = isset($day_data['tasks']) ? $day_data['tasks'] : 0;
            
            $html .= '
                <li id="m-schedule-item-' . date('m-d-Y', strtotime($day)) . '" class="d-schedule-item ' . ($day == date_by_timezone(time(), 'Y-m-d') ? 'current-date' :  (date('N', strtotime($day)) == 6 || date('N', strtotime($day)) == 7 ? 'off-date' : '')) . '">
                <div class="m-date-info d-date-info">
                    <div class="m-schedule-date d-schedule-date">
                        <div class="sc-month">' . date('F', strtotime($day)) . '</div>
                        <div class="sc-date">
                            <p class="sc-date-num">' . date('d', strtotime($day)) . '</p>
                            <p class="sc-day">' . date('l', strtotime($day)) . '</p>
                        </div>
                    </div>
                    <div class="m-schedule-task d-schedule-task">
                        <ul class="d-task-list">';
            
            if ($schedules) {
                foreach ($schedules as $schedule) {
                    $deparment = $this->department_model->get(['d_id' => $schedule->d_id]);
                    if ($schedule->day_off) {
                        $html .= '
                            <li class="task-list-item">
                                <span>' . $this->lang->line('day_off') . '</span>
                            </li>
                        ';
                    } else {
                        $html .= '
                            <li class="task-list-item">
                                <span>' . date_format_by_timezone($schedule->start_time, 'g:ia') . ' - ' . date_format_by_timezone($schedule->end_time, 'g:ia') . ' ' . ($deparment ? '(' . $deparment->department_name . ')' : '') . '</span>
                            </li>
                        ';
                    }
                }
            } else {
                if ($tasks == 1) {
                    $html .= '
                        <li class="task-list-item">
                            <span>' . $this->lang->line('working') . '</span>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li class="task-list-item">
                            <span>' . $this->lang->line('no_schedule') . '</span>
                        </li>
                    ';
                }
            }
            
            $off_request = isset($day_data['off_request']) ? $day_data['off_request'] : array();
            if ($off_request) {
                $html .= '
                    <li class="task-list-item task-list-item-request">
                        <span>' . $this->lang->line('day_off') . ' ' . $this->lang->line('request') . ' ' . ($off_request->status == 0 ? $this->lang->line('pending') : ($off_request->status == 1 ? $this->lang->line('approved') : $this->lang->line('denied'))) . '</span>
                    </li>
                ';
            }
            
            $schedule_notes = isset($day_data['schedule_notes']) ? $day_data['schedule_notes'] : array();
            if ($schedule_notes) {
                foreach ($schedule_notes as $schedule_note) {
                    $html .= '
                        <li class="m-task-note">
                            <span>' . $schedule_note->note . '</span>
                        </li>
                    ';
                }
            }
            
            $html .= '</ul>';
            
            $notes = isset($day_data['notes']) ? $day_data['notes'] : 0;
            if ($notes) {
                $html .= '<a href="#" class="task-note">' . $this->lang->line('notes') . '</a>';
            }
            
            $html .= '
                    </div>
                    <div class="m-schedule-more">
                        <button class="btn btn-default more-info" date="' . date('Y-m-d', strtotime($day)) . '">
                            <i class="fas fa-info-circle"></i>
                            <p>More info</p>
                        </button>
                    </div>
                </div>
            </li>
            ';
        }
        
        return $html;
    }

    private function render_mobile_schedules_by_days($start, $end)
    {
        $this->load->model(['worker_schedule_model', 'department_model', 'off_request_model', 'off_request_note_model']);
        $days = $this->worker_schedule_model->pull_schedules($this->session->userdata('worker_id'), $start, $end);
        $html = '';
        foreach ($days as $day => $day_data) {
            $schedules = isset($day_data['schedules']) ? $day_data['schedules'] : array();
            $tasks = isset($day_data['tasks']) ? $day_data['tasks'] : 0;

            $html .= '
                <li id="m-schedule-item-' . date('m-d-Y', strtotime($day)) . '" class="m-schedule-item ' . ($day == date_by_timezone(time(), 'Y-m-d') ? 'current-date' :  (date('N', strtotime($day)) == 6 || date('N', strtotime($day)) == 7 ? 'off-date' : '')) . '">
                <div class="m-date-info">
                    <div class="m-schedule-date">
                        <div class="sc-month">' . date('F', strtotime($day)) . '</div>
                        <div class="sc-date">
                            <p class="sc-date-num">' . date('d', strtotime($day)) . '</p>
                            <p class="sc-day">' . date('l', strtotime($day)) . '</p>
                        </div>
                    </div>
                    <div class="m-schedule-task">
                        <ul class="m-task-list">';

            if ($schedules) {
                foreach ($schedules as $schedule) {
                    $deparment = $this->department_model->get(['d_id' => $schedule->d_id]);
                    if ($schedule->day_off) {
                        $html .= '
                            <li class="task-list-item">
                                <span>' . $this->lang->line('day_off') . '</span>
                            </li>
                        ';
                    } else {
                        $html .= '
                            <li class="task-list-item">
                                <span>' . date_format_by_timezone($schedule->start_time, 'g:ia') . ' - ' . date_format_by_timezone($schedule->end_time, 'g:ia') . ' ' . ($deparment ? '(' . $deparment->department_name . ')' : '') . '</span>
                            </li>
                        ';
                    }
                }
            } else {
                if ($tasks == 1) {
                    $html .= '
                        <li class="task-list-item">
                            <span>' . $this->lang->line('working') . '</span>
                        </li>
                    ';
                } else {
                    $html .= '
                        <li class="task-list-item">
                            <span>' . $this->lang->line('no_schedule') . '</span>
                        </li>
                    ';
                }
            }

            $off_request = isset($day_data['off_request']) ? $day_data['off_request'] : array();
            if ($off_request) {
                $html .= '
                    <li class="task-list-item task-list-item-request">
                        <span>' . $this->lang->line('day_off') . ' ' . $this->lang->line('request') . ' ' . ($off_request->status == 0 ? $this->lang->line('pending') : ($off_request->status == 1 ? $this->lang->line('approved') : $this->lang->line('denied'))) . '</span>
                    </li>
                ';
            }

            $schedule_notes = isset($day_data['schedule_notes']) ? $day_data['schedule_notes'] : array();
            if ($schedule_notes) {
                foreach ($schedule_notes as $schedule_note) {
                    $html .= '
                        <li class="m-task-note">
                            <span>' . $schedule_note->note . '</span>
                        </li>
                    ';
                }
            }

            $html .= '</ul>';

            $notes = isset($day_data['notes']) ? $day_data['notes'] : 0;
            if ($notes) {
                $html .= '<a href="#" class="task-note">' . $this->lang->line('notes') . '</a>';
            }

            $html .= '
                    </div>
                    <div class="m-schedule-more">
                        <button class="btn btn-default more-info" date="' . date('Y-m-d', strtotime($day)) . '">?</button>
                    </div>
                </div>
            </li>
            ';
        }

        return $html;
    }
    
    public function render_schedule_modal()
    {
        $this->load->model(['off_request_model', 'off_request_note_model', 'user_model', 'worker_model']);
        
        $date = $this->input->post('requested_date');
        $mobile = $this->input->post('mobile');
        
        $tabs = $this->renderSchedules($date, $mobile);
        $requests = $this->renderRequests(null, $mobile);
        echo json_encode([
            'status' => 1,
            'html' => $this->renderOffRequest($date),
            'notes' => $this->renderNotes($date),
            'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html'],
            'alert_html' => $mobile == 1 ? $requests['request_html'] : $requests['alert_html'],
            'alerts' => $requests['alerts'],
            'month' => date('F', strtotime($date)),
            'date' => date('d', strtotime($date)),
            'day' => date('l', strtotime($date)),
            'prev' => date('Y-m-d', strtotime('-1 day', strtotime($date))),
            'next' => date('Y-m-d', strtotime('+1 day', strtotime($date)))
        ]);
    }
    
    private function renderSchedules($date, $mobile = 1)
    {
        $schedule_html = '';
        $requests = array();
        $pending_html = '';

        $this->db->select('worker_id, c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        if ($workers) {
            $w = $workers[0];
            
            $data = $this->pullSchedules($w->c_id, $w->d_id, $date, $this->session->userdata('worker_id'), '');
            if ($data) {
                $schedule_html .= '<div class="schedule-emp-list">';    
                $pending_html .= '<div class="waiting-swap-list">';
                
                foreach ($data as $worker) {
                    $schedule_html .= '
                        <div class="schedule-emp">
                            <p class="emp-name" style="background: linear-gradient(to right, #' . $worker['group_color'] . ' 0px, #fff 30px)">' . 
                                $worker['first_name'] . ' ' . $worker['last_name'] . '
                            </p>
                            <p class="emp-time">';
                    if ($worker['schedules']) {
                        foreach ($worker['schedules'] as $schedule) {
                            if ($schedule->day_off) {
                                $schedule_html.= '<span style="color: #F00;">Off</span>';
                            } else {
                                $schedule_html.= '<span>' . date_format_by_timezone($schedule->start_time, 'g:ia') . ' - ' . date_format_by_timezone($schedule->end_time, 'g:ia') . '</span>';
                            }
                        }
                    }
                    
                    $swap_request_status = '';
                    if ($worker['swap_request_status'] == 0) {
                        $swap_request_status = 'pending';
                    } else if ($worker['swap_request_status'] == 1) {
                        $swap_request_status = 'approved';
                    } else if ($worker['swap_request_status'] == 2) {
                        $swap_request_status = 'denied';
                    }
                    
                    $schedule_html .= '</p>';
                    
                    if ($mobile == 1) {
                        $schedule_html .= '<a style="' . ($worker['worker_id'] == $this->session->userdata('worker_id') ? 'visibility: hidden;' : '') . '" class="emp-btn-swap ' . $swap_request_status . '" href="#" worker_id="' . $worker['worker_id'] . '">Swap</a>';
                    }
                    $schedule_html .= '</div>';
                    
                    if ($worker['swap_request_id'] && $worker['swap_request_status'] == 0) {
                        $pending_html .= '
                            <div class="waiting-item">
                                <p>Pending Swap with <span class="target-swap">' . $worker['first_name'] . ' ' . $worker['last_name'] . '</span></p>
                                <a class="withdraw-swap" swap_request_id="' . $worker['swap_request_id'] . '" href="#">Withdraw</a>
                            </div>
                        ';
                    }
                }
                
                $schedule_html .= '</div>';
                $pending_html .= '</div>';
            }
            
            $requests = $this->renderRequests($date, $mobile);
        }
        
        
        return array_merge($requests, array(
            'pending_html' => $mobile == 1 ? $pending_html : '',
            'schedule_html' => $schedule_html
        ));
    }
    
    public function renderAlerts()
    {
        $res = $this->renderRequests(null, 1);
        $res['status'] = 1;
        
        echo json_encode($res);
    }
    
    private function renderRequests($date = null, $mobile = 1)
    {
        $this->db->select('worker_id, c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        if (!$workers) {
            return array('request_html' => '', 'alerts' => 0);
        }
        $worker = $workers[0];
    
        $request_html = '';
        $alerts = 0;
        $requests = $this->pullSwapRequests($worker->c_id, $worker->d_id, $worker->worker_id, $date);
        
        if ($requests) {
            $request_html .= '<div class="accept-swap-list">';
            $cur_date = null;
            foreach ($requests as $request) {
                if (!$date) {
                    if ($request->request_date != $cur_date) {
                        if ($cur_date) {
                            $request_html .= '';
                        }
                        $request_html .= '<h5 class="date-request">' . date('m-d-Y', strtotime($request->request_date)) . '</h5>';
                    }
                    $cur_date = $request->request_date;
                }
    
                if ($request->swap_request_status == 0) {
                    $request_html .= '
                        <div class="accept-swap-item">';
                    if ($this->session->userdata('worker_id') == $request->to_worker_id) {
                        $request_html .= '
                            <div class="bell-alert">
                                <i class="fa fa-bell"></i>
                            </div>
                        ';
                    }
                    $request_html .= '
                            <div class="accept-swap-content">
                                <div class="user-want">
                                    <p class="user-name">' . $request->from_fn . ' ' . $request->from_ln . '</p>';
    
                    if ($request->from_schedule) {
                        $request_html .= '<p class="time-leap">' . $request->from_schedule . '</p>';
                    }
    
                    $request_html .= '
                            </div>
                                <i class="fa ' . ($mobile == 1 ? 'fa-arrows-h' : 'fa-arrows-alt-h') . '"></i>
                                <div class="user-mine">
                                    <p class="user-name">' . $request->to_fn . ' ' . $request->to_ln . '</p>';
    
                    if ($request->to_schedule) {
                        $request_html .= '<p class="time-leap">' . $request->to_schedule . '</p>';
                    }
    
                    $request_html .= '
                                </div>
                            </div>
                            <div class="accept-swap-btngroup">
                                <a href="#" class="btn-agree-swap" swap_request_id="' . $request->id . '"><i class="fa fa-check"></i></a>
                                <a href="#" class="btn-dismiss-swap" swap_request_id="' . $request->id . '"><i class="fa fa-times"></i></a>
                            </div>
                        </div>
                    ';
    
                    if ($this->session->userdata('worker_id') == $request->to_worker_id && $request->alert == 1) {
                        $alerts ++;
                    }
                } else if ($request->swap_request_status == 1) {
                    $request_html .= $this->renderResponsedSwapRequest($request);
                    if ($this->session->userdata('worker_id') == $request->from_worker_id && $request->alert == 1) {
                        $alerts ++;
                    }
                } else {
                    $request_html .= $this->renderResponsedSwapRequest($request);
                    if ($this->session->userdata('worker_id') == $request->from_worker_id && $request->alert == 1) {
                        $alerts ++;
                    }
                }
            }
            $request_html .= '</div>';
        }
    
        return array(
            'request_html' => $mobile == 1 ? $request_html : '',
            'alert_html' => $mobile == 0 ? $request_html : '',
            'alerts' => $alerts
        );
    }
    
    private function pullSwapRequests($c_id, $d_id, $worker_id, $date = null) {
        $this->db->select('
            worker_swap_request.request_date, worker_swap_request.id, worker_swap_request.status AS swap_request_status,
            to_worker.worker_id AS to_worker_id, to_worker.first_name AS to_fn, to_worker.last_name AS to_ln,
            from_worker.worker_id AS from_worker_id, from_worker.first_name AS from_fn, from_worker.last_name AS from_ln,
            worker_swap_request.alert, worker_swap_request.from_schedule, worker_swap_request.to_schedule
        ');
        $this->db->from('worker_swap_request');
        $this->db->join('workers AS to_worker', 'worker_swap_request.to_worker_id = to_worker.worker_id', 'INNER');
        $this->db->join('workers AS from_worker', 'worker_swap_request.from_worker_id = from_worker.worker_id', 'INNER');
        $this->db->where(
            '(worker_swap_request.to_worker_id = ' . $this->session->userdata('worker_id') . ' OR
            (worker_swap_request.status <> 0 AND worker_swap_request.from_worker_id = ' . $this->session->userdata('worker_id') . '))'
            );
    
        if ($date) {
            $this->db->where('worker_swap_request.request_date', date_by_timezone(strtotime($date), 'Y-m-d'));
        } else {
            $this->db->where('worker_swap_request.request_date >= ', date_by_timezone(time(), 'Y-m-d'));
        }
        $this->db->order_by('worker_swap_request.request_date, from_worker.first_name, from_worker.last_name');
        $query = $this->db->get();
        $requests = $query->result();
        
        return $requests;
    }
    
    private function renderResponsedSwapRequest($request)
    {
        $request_html = '';
        $request_html .= '
            <div class="accept-swap-item answer-request ' . ($request->swap_request_status == 1 ? 'confirm' : 'reject') . '-request">
                <div class="' . ($request->swap_request_status == 1 ? 'confirm' : 'reject') . '-request-header">';
        
        if ($request->alert == 1) {

            if ($this->session->userdata('worker_id') == $request->from_worker_id && $request->swap_request_status <> 0) {
                $request_html .= '
                    <div class="bell-alert">
                        <i class="fa fa-bell"></i>
                    </div>
                ';
            } else if ($this->session->userdata('worker_id') == $request->to_worker_id && $request->swap_request_status == 0) {
                $request_html .= '
                    <div class="bell-alert">
                        <i class="fa fa-bell"></i>
                    </div>
                ';
            }
            
        }
        $request_html .= '
                    <div class="' . ($request->swap_request_status == 1 ? 'btn-agree-swap' : 'btn-dismiss-swap') . '">
                        <i class="fa ' . ($request->swap_request_status == 1 ? 'fa-check' : 'fa-times') . '"></i>
                    </div>
        ';
        
        if ($this->session->userdata('worker_id') == $request->from_worker_id) {
            $request_html .= '<p class="user-name">' . $request->to_fn . ' ' . $request->to_ln . '</p>';
        } else {
            $request_html .= '<p class="user-name">' . $request->from_fn . ' ' . $request->from_ln . '</p>';
        }
                    
        $request_html .= '
                    <a class="btn-direct btn-up" href="#"><i class="fa fa-caret-up"></i></a>
                </div>
                <div class="answer-request-content ' . ($request->swap_request_status == 1 ? 'confirm' : 'reject') . '-request-content">
                    <p class="day-reason">
                        <span class="day-type-request">' . date_by_timezone(strtotime($request->request_date), 'm-d-Y') . '</span>: <span class="type-request">Original Schedule</span>
                    </p>
                    <div class="user-info-wrapper">
                        <div class="user-request">
                            <p class="user-name">' . $request->from_fn . ' ' . $request->from_ln . '</p>';
        
        if ($request->from_schedule) {
            $request_html .= '<p class="time-request">' . $request->from_schedule . '</p>';
        }
        $request_html .= '
                        </div>
                        <div class="user-receive">
                            <p class="user-name">' . $request->to_fn . ' ' . $request->to_ln . '</p>';
        
        if ($request->to_schedule) {
            $request_html .= '<p class="time-request">' . $request->to_schedule . '</p>';
        }
        $request_html .= '
                        </div>
                    </div>
                    <p class="propose-by">Proposed by: <span class="user-propose">' . $request->from_fn . ' ' . $request->from_ln . '</span></p>
                </div>
            </div>
        ';
        
        return $request_html;
    }
    
    private function renderNotes($date)
    {
        $html = '
            <div class="schedule-note-list">' . $this->renderNoteItems($date) . '</div>
            <div class="add-note-content">
                <form id="add-note-form">
                    <textarea class="schedule-textarea form-control" title="Add Notes" rows="3" placeholder="Note"></textarea>
                    <div class="schedule-btn-group">
                        <button id="remove-notes" class="btn submit-btn" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">Remove Notes</button>
                        <a href="#" class="btn submit-btn add-note" style="color: #333;" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">Submit</a>
                    </div>
                </form>
            </div>
        ';
        
        return $html;
    }
    
    private function renderOffRequest($date)
    {
        $off_request = $this->off_request_model->get([
            'worker_id' => $this->session->userdata('worker_id'),
            'request_date' => date('Y-m-d', strtotime($date))
        ]);
        
        $html = '
            <div class="modal-body m-detail-content">
                <input type="hidden" name="request_date" class="request_date" value="' . date('Y-m-d', strtotime($date)) . '" />
                <div class="m-request">';
        
        if (!$off_request) {
            $html .= '
                <button class="btn btn-request request" id="submit-off-request" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">
                    Request Day Off
                </button>
            ';
        } else {
            $html .= '<input class="off_request_id" type="hidden" name="off_request_id" value="' . $off_request->id . '" />';
            if ($off_request->status == 0) {
                $html .= '
                    <button class="btn btn-request pending" id="cancel-off-request" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">' .
                        $this->lang->line('day_off') . ' ' . $this->lang->line('request') . ' ' . $this->lang->line('pending') .
                    '</button>
                ';
            } else if ($off_request->status == 1) {
                $html .= '
                    <button class="btn btn-request approved">' .
                        $this->lang->line('day_off') . ' ' . $this->lang->line('request') . ' ' . $this->lang->line('approved') .
                    '</button>
                ';
            } else if ($off_request->status == 2) {
                $html .= '
                    <button class="btn btn-request denied">' .
                        $this->lang->line('day_off') . ' ' . $this->lang->line('request') . ' ' . $this->lang->line('denied') .
                    '</button>
                ';
            }
        }
        
        $html .= '
                </div>
                <div class="comment-container">
                    <div class="schedule-note-list">
        ';
        
        $html .= $this->renderNoteItems($date);
        
        $html .= '
                    </div>
                </div>
                <div class="request-note">
                    <form class="row form-horizontal" style="margin: 0;">
                        <textarea class="form-control schedule-textarea" rows="3" title="Notes" placeholder="Request notes here"></textarea>
                        <div class="schedule-btn-group">
                                <button id="remove-off-request" class="btn submit-btn" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">Remove Request</button>
                                <a href="#" class="btn submit-btn add-off-request" style="color: #333;" for-item="m-schedule-item-' . date('m-d-Y', strtotime($date)) . '">Submit</a>
                        </div>
                    </form>
                </div>
            </div>
        ';
        return $html;
    }
    
    private function renderNoteItems($date)
    {
        $html = '';
        
        $notes = $this->off_request_note_model->order_by('created_at')->get_all([
            'worker_id' => $this->session->userdata('worker_id'),
            'note_date' => date('Y-m-d', strtotime($date))
        ]);
        
        if (!empty($notes)) {
            foreach ($notes as $note) {
                $html .= $this->renderNoteItem($note);
            }
        }
        return $html;
    }
    
    private function renderNoteItem($note)
    {
        $html = '';
        if ($note->created_by_role != 'worker') {
            $created_man = $this->user_model->get_by_attributes(['id' => $note->created_by_id]);
        } else {
            $created_man = $this->worker_model->get_by_attributes(['worker_id' => $note->created_by_id]);
        }
        if ($this->session->userdata('worker_id') == $note->created_by_id) {
            $html .= '
                <div class="schedule-note-item gm-note">
                    <div class="schedule-note-content">' . $note->note . '</div>
                </div>
            ';
        } else {
            $html .= '
                <div class="schedule-note-item user-note">
                    <div class="short-name">' .
                        ((isset($created_man) && $created_man) ? (strtoupper(substr($created_man->first_name, 0, 1)) . strtoupper(substr($created_man->last_name, 0, 1))) : '') .
                    '</div>
                    <div class="schedule-note-content">' . $note->note . '</div>
                </div>
            ';
        }
        
        return $html;
    }
    
    public function remove_notes()
    {
        $date = $this->input->post('requested_date');
        
        if (!$date) {
            echo json_encode(['status' => 0, 'message' => 'Please try again.']);
            exit;
        }
        
        $this->load->model(['off_request_note_model']);
        $this->off_request_note_model->delete(array(
            'note_date' => date('Y-m-d', strtotime($date)),
            'worker_id' => $this->session->userdata('worker_id')
        ));
        
        echo json_encode(['status' => 1, 'message' => 'Notes has been removed.']);
        exit;
    }
    
    public function request_day_off()
    {
        $date = $this->input->post('requested_date');
        
        if (!$date) {
            echo json_encode(['status' => 0, 'message' => 'Please select date.']);
            exit;
        }
        
        $this->load->model(['off_request_model']);
        $off_request = $this->off_request_model->get([
            'worker_id' => $this->session->userdata('worker_id'),
            'request_date' => date('Y-m-d', strtotime($date))
        ]);
        
        if (!$off_request) {
            $this->off_request_model->insert([
                'worker_id' => $this->session->userdata('worker_id'),
                'request_date' => date('Y-m-d', strtotime($date)),
                'status' => 0
            ]);
            
            echo json_encode(['status' => 1, 'message' => 'Day off request has been sent.']);
            exit;
            
        } else {
            if ($off_request->status == 0) {
                $this->off_request_model->delete(['id' => $off_request->id]);
                
                echo json_encode(['status' => 1, 'message' => 'Day off request has been canceled.']);
                exit;
            }
        }
        
        echo json_encode(['status' => 1]);
    }
    
    public function add_note()
    {
        $date = $this->input->post('requested_date');
        $note = $this->input->post('request_note');
        
        if (!$date) {
            echo json_encode(['status' => 0, 'message' => 'Please select date that you want to add note.']);
            exit;
        }
        if (!$note) {
            echo json_encode(['status' => 0, 'message' => 'Please input note.']);
            exit;
        }
        
        $this->load->model(['off_request_note_model', 'worker_model']);
        $id = $this->off_request_note_model->insert([
            'worker_id' => $this->session->userdata('worker_id'),
            'note_date' => date('Y-m-d', strtotime($date)),
            'created_by_id' => $this->session->userdata('worker_id'),
            'created_by_role' => 'worker',
            'note' => $note,
            'created_at' => time()
        ]);
        
        $note = $this->off_request_note_model->order_by('created_at')->get(['id'=> $id]);
        $new_note = $this->renderNoteItem($note);
        
        echo json_encode(['status' => 1, 'new_note' => $new_note]);
    }
    
    private function pullSchedules($c_id, $d_id, $date, $worker_id, $status = '')
    {
    	$this->db->select('
    	    workers.worker_id, workers.first_name, workers.last_name, groups.group_color, 
    	    lastest_swap.id AS swap_request_id, COALESCE(lastest_swap.status, -1) AS swap_request_status,
    	    worker_schedule.s_date, worker_schedule.start_time, worker_schedule.end_time, worker_schedule.day_off
	    ');
    	$this->db->from('workers');
    	$this->db->join('groups', 'workers.group_id = groups.group_id', 'LEFT OUTER');
    	$this->db->join(
    	    'worker_schedule',
    	    'workers.worker_id = worker_schedule.worker_id AND workers.c_id = worker_schedule.c_id 
    	    AND workers.d_id = worker_schedule.d_id AND worker_schedule.deleted_at IS NULL',
    	    'LEFT OUTER'
	    );
    	$this->db->join(
    	    '
    	    (
        	    SELECT id, request_date, status, to_worker_id, from_worker_id
        	    FROM worker_swap_request
        	    WHERE (id, from_worker_id, to_worker_id, request_date) IN
        	    (
            	    SELECT MAX(id), from_worker_id, to_worker_id, request_date FROM worker_swap_request
            	    WHERE from_worker_id = ' . $worker_id . '
            	    GROUP BY request_date, from_worker_id, to_worker_id
        	    )
    	    ) lastest_swap
    	    ',
    	    'worker_schedule.s_date = lastest_swap.request_date AND lastest_swap.to_worker_id = workers.worker_id AND lastest_swap.from_worker_id = ' . $worker_id,
    	    'LEFT OUTER'
	    );
    	$this->db->where('workers.c_id', $c_id);
    	$this->db->where('workers.d_id', $d_id);
    	$this->db->where('worker_schedule.s_date = "' . date('Y-m-d', strtotime($date)) . '" AND (workers.remove = 0 OR worker_schedule.worker_schedule_id IS NOT NULL)', null);
    	if ($status == 'pending') {
    	    $this->db->where('worker_swap_request.status', 0);
    	}
    	$this->db->order_by('workers.group_id, worker_schedule.day_off, worker_schedule.start_time, workers.first_name, workers.last_name');
    	$query = $this->db->get();
    	$schedules = $query->result();
    	
    	$workers = array();
    	foreach ($schedules as $schedule) {
    	    if (!isset($workers[$schedule->worker_id])) {
    	        $workers[$schedule->worker_id] = array(
    	            'worker_id' => $schedule->worker_id,
    	            'first_name' => $schedule->first_name,
    	            'last_name' => $schedule->last_name,
    	            'group_color' => $schedule->group_color,
    	            'swap_request_status' => $schedule->swap_request_status,
    	            'swap_request_id' => $schedule->swap_request_id,
    	            'schedules' => array()
    	        );
    	    }
    	    
    	    $workers[$schedule->worker_id]['schedules'][] = $schedule;
    	}
    	return $workers;
    }
    
    public function swap_request()
    {
        $date = $this->input->post('requested_date');
        $to_worker_id = $this->input->post('to_worker_id');
        $mobile = $this->input->post('mobile');
        
        $this->db->select('id, status');
        $this->db->from('worker_swap_request');
        $this->db->where('from_worker_id', $this->session->userdata('worker_id'));
        $this->db->where('to_worker_id', $to_worker_id);
        $this->db->where('status', 0);
        $this->db->where('request_date', date('Y-m-d', strtotime($date)));
        $this->db->limit(1);
        
        $query = $this->db->get();
        $requests = $query->result();
        
        if ($requests) {
            $request = $requests[0];
        }
        
        if (isset($request)) {
            if ($request->status == 1) {
                echo json_encode(['status' => 0, 'message' => 'You can not cancel approved request.', 'request_status' => 1]);
            } else {
                $this->db->where('id', $request->id);
                $this->db->delete('worker_swap_request');
                
                $tabs = $this->renderSchedules($date, $mobile);
                echo json_encode([
                    'status' => 1,
                    'message' => 'Request has been canceled successfully.',
                    'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html']
                ]);
            }
        } else {
            $schedules = $this->renderOriginalSchedules($this->session->userdata('worker_id'), $to_worker_id, $date);
            $this->db->insert('worker_swap_request', array(
                'from_worker_id' => $this->session->userdata('worker_id'),
                'from_schedule' => $schedules['from'],
                'to_worker_id' => $to_worker_id,
                'to_schedule' => $schedules['to'],
                'request_date' => date('Y-m-d', strtotime($date)),
                'status' => 0,
                'alert' => 1
            ));
            
            $this->db->select('ecell, receive_text_alert');
            $this->db->from('workers');
            $this->db->where('worker_id', $to_worker_id);
            $query = $this->db->get();
            $to_workers = $query->result();
            
            if ($to_workers) {
                $to_worker = $to_workers[0];
                if ($to_worker->receive_text_alert && $to_worker->ecell) {
                    $this->db->select('first_name, last_name');
                    $this->db->from('workers');
                    $this->db->where('worker_id', $this->session->userdata('worker_id'));
                    $query = $this->db->get();
                    $from_workers = $query->result();
                    
                    if ($from_workers) {
                        $from_worker = $from_workers[0];
                        $text = 'You have new swap request on ' . date('m/d/Y', strtotime($date)) . ' from ' . $from_worker->first_name . ' ' . $from_worker->last_name;
                        $this->sendSms($to_worker->ecell, $text);
                    }
                }
            }
            
            $tabs = $this->renderSchedules($date, $mobile);
            echo json_encode([
                'status' => 1,
                'message' => 'Request has been sent successfully.',
                'request_status' => 0,
                'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html']
            ]);
        }
    }
    
    private function renderOriginalSchedules($from_worker_id, $to_worker_id, $request_date)
    {
        $this->db->select('start_time, end_time, day_off');
        $this->db->from('worker_schedule');
        $this->db->where('worker_id', $from_worker_id);
        $this->db->where('s_date', date('Y-m-d', strtotime($request_date)));
        $this->db->where('deleted_at IS NULL', null);
        $query = $this->db->get();
        $from_schedules = $query->result();
    
        $from_html = '';
        foreach ($from_schedules as $from_schedule) {
            if ($from_schedule->day_off) {
                $from_html .= '<span style="color: #F00;">Off</span>';
            } else {
                $from_html .= '<span>' . date_format_by_timezone($from_schedule->start_time, 'g:ia') . ' - ' . date_format_by_timezone($from_schedule->end_time, 'g:ia') . '</span>';
            }
        }
    
        $this->db->select('start_time, end_time, day_off');
        $this->db->from('worker_schedule');
        $this->db->where('worker_id', $to_worker_id);
        $this->db->where('s_date', date('Y-m-d', strtotime($request_date)));
        $this->db->where('deleted_at IS NULL', null);
        $query = $this->db->get();
        $to_schedules = $query->result();
    
        $to_html = '';
        foreach ($to_schedules as $to_schedule) {
            if ($to_schedule->day_off) {
                $to_html .= '<span style="color: #F00;">Off</span>';
            } else {
                $to_html .= '<span>' . date_format_by_timezone($to_schedule->start_time, 'g:ia') . ' - ' . date_format_by_timezone($to_schedule->end_time, 'g:ia') . '</span>';
            }
        }
    
        return array('from' => $from_html, 'to' => $to_html);
    }
    
    public function withdraw_swap()
    {
        $swap_request_id = $this->input->post('swap_request_id');
        $date = $this->input->post('requested_date');
        $mobile = $this->input->post('mobile');
    
        $this->db->where('id', $swap_request_id);
        $this->db->delete('worker_swap_request');
        
        $tabs = $this->renderSchedules($date, $mobile);
        echo json_encode([
            'status' => 1,
            'message' => 'Request has been withdrawn successfully.',
            'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html']
        ]);
    }
    
    public function response_swap_request()
    {
        $swap_request_id = $this->input->post('swap_request_id');
        $status = $this->input->post('status');
        $mobile = $this->input->post('mobile');
        
        $this->db->select('
            id, request_date, 
            from_worker_id, from_worker.receive_text_alert AS from_receive_text_alert, from_worker.ecell AS from_ecell,
            to_worker_id, to_worker.first_name AS to_fn, to_worker.last_name AS to_ln
        ');
        $this->db->from('worker_swap_request');
        $this->db->join('workers AS from_worker', 'from_worker.worker_id = worker_swap_request.from_worker_id', 'INNER');
        $this->db->join('workers AS to_worker', 'to_worker.worker_id = worker_swap_request.to_worker_id', 'INNER');
        $this->db->where('id', $swap_request_id);
        $this->db->where('status', 0);
        $query = $this->db->get();
        $requests = $query->result();
        
        if ($requests) {
            $request = $requests[0];
            
            $this->db->where('id', $request->id);
            $this->db->update('worker_swap_request', array('status' => $status, 'alert' => 1));
            
            if ($status == 1) {
                // Withdaw all requests from to_worker
                $this->db->where('request_date', $request->request_date);
                $this->db->where('from_worker_id', $request->to_worker_id);
                $this->db->where('status', 0);
                $this->db->where('id <> ' . $request->id, null);
                $this->db->delete('worker_swap_request');
                
                // Deny all requests to to_worker
                $this->db->where('request_date', $request->request_date);
                $this->db->where('to_worker_id', $request->to_worker_id);
                $this->db->where('status', 0);
                $this->db->where('id <> ' . $request->id, null);
                $this->db->update('worker_swap_request', array('status' => 2, 'alert' => 1));
                

                // Withdaw all requests from from_worker
                $this->db->where('request_date', $request->request_date);
                $this->db->where('from_worker_id', $request->from_worker_id);
                $this->db->where('status', 0);
                $this->db->where('id <> ' . $request->id, null);
                $this->db->delete('worker_swap_request');
                
                // Deny all requests to from_worker
                $this->db->where('request_date', $request->request_date);
                $this->db->where('to_worker_id', $request->from_worker_id);
                $this->db->where('status', 0);
                $this->db->where('id <> ' . $request->id, null);
                $this->db->update('worker_swap_request', array('status' => 2, 'alert' => 1));
                
                // Swap schedules between 2 workers
                $this->db->select('worker_schedule_id');
                $this->db->from('worker_schedule');
                $this->db->where('s_date', $request->request_date);
                $this->db->where('worker_id', $request->to_worker_id);
                $query = $this->db->get();
                $to_schedules = $query->result();

                $this->db->select('worker_schedule_id');
                $this->db->from('worker_schedule');
                $this->db->where('s_date', $request->request_date);
                $this->db->where('worker_id', $request->from_worker_id);
                $query = $this->db->get();
                $from_schedules = $query->result();
                
                if ($to_schedules) {
                    foreach ($to_schedules as $to_schedule) {
                        $this->db->where('worker_schedule_id', $to_schedule->worker_schedule_id);
                        $this->db->update('worker_schedule', array('worker_id' => $request->from_worker_id));
                    }
                }
                if ($from_schedules) {
                    foreach ($from_schedules as $from_schedule) {
                        $this->db->where('worker_schedule_id', $from_schedule->worker_schedule_id);
                        $this->db->update('worker_schedule', array('worker_id' => $request->to_worker_id));
                    }
                }
                
                if ($request->from_receive_text_alert && $request->from_ecell) {
                    $text = 'Your swap request on ' . date('m/d/Y', strtotime($request->request_date)) . ' has been accepted by ' . $request->to_fn . ' ' . $request->to_ln;
                    $this->sendSms($request->from_ecell, $text);
                }
                
                $tabs = $this->renderSchedules($request->request_date, $mobile);
                $requests = $this->renderRequests(null, $mobile);
                echo json_encode([
                    'status' => 1,
                    'message' => 'Request has been accepted.',
                    'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html'],
                    'alert_html' => $requests['request_html'],
                    'alerts' => $requests['alerts']
                ]);
            } else {
                if ($request->from_receive_text_alert && $request->from_ecell) {
                    $text = 'Your swap request on ' . date('m/d/Y', strtotime($request->request_date)) . ' has been denied by ' . $request->to_fn . ' ' . $request->to_ln;
                    $this->sendSms($request->from_ecell, $text);
                }
                
                $tabs = $this->renderSchedules($request->request_date, $mobile);
                $requests = $this->renderRequests(null, $mobile);
                echo json_encode([
                    'status' => 1,
                    'message' => 'Request has been denied.',
                    'schedules' => $tabs['pending_html'] . $tabs['request_html'] . $tabs['schedule_html'],
                    'alert_html' => $requests['request_html'],
                    'alerts' => $requests['alerts']
                ]);
            }
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'Request is no longer available.'
            ]);
        }
    }
    
    public function viewed_alerts()
    {
        $this->db->where('from_worker_id', $this->session->userdata('worker_id'));
        $this->db->where('status <> 0', null);
        $this->db->where('alert', 1);
        $this->db->update('worker_swap_request', array('alert' => 0));
        
        $res = $this->renderRequests(null, 1);
        
        echo json_encode(['status' => 1, 'alerts' => $res['alerts']]);
    }
    
    private function sendSms($to = '+84909391808', $text)
    {
        $text = 'TaskTracker Alert: ' . $text;
        if (strlen($to) == 10) {
            $to = '1' . $to;
        }
        $this->load->library('Plivo');
        $p = new Plivo();
        $params = array(
            'powerpack' => true,
            'dst' => $to,
            'text' => $text,
            'type' => 'sms'
        
        );
        $response = $p->send_message($params);
    }
}