<?php

/**
 * Class Default_display_time_model
 */

class Default_display_time_model extends MY_Model
{
    public $table = 'default_display_time';

    public function get_display_times($cid, $did, $wdate = null)
    {
        $cid = intval($cid);
        $did = intval($did);

        if (!$cid || !$did) {
            return array();
        }

        if (!$wdate) {
            $wdate = date_by_timezone(time(), 'Y-m-d');
        }

        $wid = $this->db
            ->select('work_board_id')
            ->where('c_id', $cid)
            ->where('d_id', $did)
            ->where('w_date', $wdate)
            ->get('work_board')
            ->row('work_board_id');

        $wid = intval($wid);

        $times = $this->db
            ->select('t.job_number, t.time_in_seconds AS default_time, wdto.time_in_seconds AS override_time, COALESCE(wdto.show_job, 0) AS show_job')
            ->from($this->table . ' t')
            ->join('workboard_display_time_override wdto', 'wdto.job_number = t.job_number AND wdto.workboard_id = ' . $wid, 'left outer')
            ->where('t.c_id', $cid)
            ->where('t.d_id', $did)
            ->get()->result();

        foreach ($times as $i => $time) {
            $times[$i]->time_in_seconds = intval($time->override_time ? $this->getSeconds($time->override_time) : $time->default_time);
        }

        return $times;
    }

    public function filter_tasks_by_display_time($tasks, $cid, $did, $remove_hidden_jobs = false)
    {
        $this->load->model('working_session_model');

        if (!is_array($tasks) || !($totalTasks = count($tasks))) {
            return $tasks;
        }

        $times = $this->get_display_times($cid, $did);
        if (!is_array($times) || !count($times)) {
            return $tasks;
        }

        $working_session = $this->working_session_model->get_tracked_time(date_by_timezone(time(), 'Y-m-d'));
        $show_next_job   = $this->db
                ->select('settings_value')
                ->where('c_id', $cid)
                ->where('d_id', $did)
                ->where('settings_name', 'dbj_show_next_job_mobile_koisk')
                ->get('settings')
                ->row('settings_value');

        $show_next_job = 1 == $show_next_job && isset($working_session['clock_in']);

        $show_all = array_shift($times);
        $cur_secs = $this->getSeconds();
        $del_task_keys = array();

        foreach ($tasks as $i => $task) {
            $show_time = isset($times[$i]) ? $times[$i]->time_in_seconds : $show_all->time_in_seconds;
            $tasks[$i]->show_time_in_seconds = $show_time;

            //Convert to str
            $datetime = strtotime(date('Y-m-d', time()) . ' 00:00:00') + $show_time;
            $tasks[$i]->show_time_str = date_by_timezone($datetime, 'H:i');

            // Visible
            $show_job  = 1 == $show_all->show_job || (isset($times[$i]) && 1 == $times[$i]->show_job);

            //Show All time is passed
            if (!$show_job && ($cur_secs > $show_all->time_in_seconds))
            $show_job = true;

            //Show Time is passed
            if (!$show_job && $cur_secs >= $show_time) {
                $show_job = true;
            }

            //Always show activated job
            if (!$show_job && ($task->total_time > 0 || $task->start_time > 0)) {
                $show_job = true;
            }

            //Show job if [show_next_job] setting is ON and previous job is clocked in and out
            if (!$show_job && $show_next_job) {
                if (isset($tasks[$i - 1])) {
                    $prev_task = $tasks[$i - 1];

                    if ($prev_task->total_time > 0 && null == $prev_task->start_time) {// Is clocked in and out
                        $show_job = true;
                    }
                }
            }

            if ($remove_hidden_jobs && !$show_job) {
                $del_task_keys[] = $i;
            }
            if (!$remove_hidden_jobs) {
                $task->show_job = $show_job;
                $tasks[$i] = $task;
            }
        }
        // Remove hidden tasks
        foreach ($del_task_keys as $k) {
            unset($tasks[$k]);
        }

        return array_values($tasks);
    }

    public function getSeconds($time = null, $gmt = false)
    {
        if (!$time) {
            $time = time();
        }

        $curHour = $gmt ? gmdate('H') : date_by_timezone($time, 'H');
        $curMin  = $gmt ? gmdate('i') : date_by_timezone($time, 'i');
        $curSecs = ((intval($curHour) * 60) + intval($curMin)) * 60;

        if ($curSecs < 0 || $curSecs >= 86400) {
            $curSecs = 0;
        }

        return $curSecs;
    }
}