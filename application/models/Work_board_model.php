<?php
class Work_board_model extends MY_Model
{
    public $table = 'work_board';
    
    public function getWorkboard()
    {
        $workboard = $this->getWorkboardObject();
        
        if ($workboard) {
            return $workboard->work_board_id;
        }
        return null;
    }
    
    public function getWorkboardObject()
    {
        $this->load->model('worker_model');
        $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        
        return $this->getWorkboardByWorker($worker);
    }
    
    public function getWorkboardByWorker($worker, $date = null)
    {
        if (!$worker) {
            $this->load->model(['worker_model']);
            $worker = $this->worker_model->get_by_attributes(['worker_id' => $this->session->userdata('worker_id')]);
        }
        $mobile = $this->session->userdata('mobile');
        if ($mobile == '1') {
            $d_id = $this->session->userdata('department_id');
        } else {
            $this->load->model(['kiosk_model']);
            $kiosk = $this->kiosk_model->get(['k_id' => $this->session->userdata('kiosk_id')]);
            if ($kiosk) {
                $d_id = $kiosk->did;
            }
        }
        if (!isset($d_id) || !$d_id) {
            $d_id = $worker->d_id;
        }
        
        $date = $date ? date_by_timezone(strtotime($date), 'Y-m-d') : date_by_timezone(time(), 'Y-m-d');
        $workboard = $this->get(['c_id' => $worker->c_id, 'd_id' => $d_id, 'w_date' => $date]);
        
        if (!$workboard) {
            $this->db->insert('work_board', [
                'w_date' => $date,
                'c_id' => $worker->c_id,
                'd_id' => $d_id
            ]);
            
            $workboard = $this->get(['c_id' => $worker->c_id, 'd_id' => $d_id, 'w_date' => $date]);
        }
        
        return $workboard;
    }
    
    public function getWorkboardByCompany($c_id)
    {
        $workboards = $this->get_all(['c_id' => $c_id, 'w_date' => date_by_timezone(time(), 'Y-m-d')]);
        
        return $workboards;
    }
}