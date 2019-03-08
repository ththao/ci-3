<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Workorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->has_userdata('worker_id')) {
            redirect('/logout');
        }
        $this->data['page'] = 'workorder';
    }

    public function index()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/select2.css'),
            auto_version('../assets/css/new-style.css')
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/mobile.js'),
            auto_version('../assets/js/workorder.js'),
            auto_version('../assets/js/select2.js'),
            auto_version('../assets/js/autogrow.js')
        ];

        $this->getWorkOrderData();
        
        $this->data['add_wo_permission'] = 1;
        $this->db->select('add_new_work_order');
        $this->db->from('worker_permission');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $permissions = $query->result();
        
        if ($permissions) {
            $permission = $permissions[0];
            if (!$permission->add_new_work_order) {
                $this->data['add_wo_permission'] = 0;
            }
        }

        $this->pullSidebarData();

        $this->main = 'workorder/index';
        $this->main();
    }
    
    public function detail()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/select2.css'),
            auto_version('../assets/css/new-style.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/mobile.js'),
            auto_version('../assets/js/select2.js'),
            auto_version('../assets/js/workorder.js'),
            auto_version('../assets/js/autogrow.js')
        ];
        
        if (!isset($_GET['id']) || !$_GET['id']) {
            redirect('workorder');
        }
        
        $this->getWorkOrderData($_GET['id']);
        
        if (!isset($this->data['work_order']) || !$this->data['work_order']) {
            redirect('workorder');
        }
        
        if (isset($_GET['job'])) {
            $this->data['job_id'] = $_GET['job'];
        }

        $this->pullSidebarData();

        $this->main = 'workorder/detail';
        $this->main();
    }
    
    public function create()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/select2.css'),
            auto_version('../assets/css/new-style.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/mobile.js'),
            auto_version('../assets/js/workorder.js'),
            auto_version('../assets/js/select2.js'),
            auto_version('../assets/js/autogrow.js')
        ];

        $this->pullSidebarData();
        $this->getEquipmentData();
        
        $this->main = 'workorder/create';
        $this->main();
    }

    public function mobile_create()
    {
        $this->template['css_files'] = [
            auto_version('../assets/css/jquery-ui.css'),
            auto_version('../assets/css/style.css'),
            auto_version('../assets/css/custom.css'),
            auto_version('../assets/css/m.style.css'),
            auto_version('../assets/css/select2.css'),
        ];
        $this->template['js_files'] = [
            auto_version('../assets/js/jquery-ui.js'),
            auto_version('../assets/js/mobile.js'),
            auto_version('../assets/js/workorder.js'),
            auto_version('../assets/js/select2.js'),
            auto_version('../assets/js/autogrow.js')
        ];

        $this->getEquipmentData();

        $this->main = 'workorder/mobile_create';
        $this->mobile_layout();
    }
    
    public function add_wo_job()
    {
        $job_index = isset($_POST['job_index']) ? $_POST['job_index'] : 1;
        $wo_item_id = isset($_POST['wo_item_id']) ? $_POST['wo_item_id'] : null;
        
        $this->db->select('worker_id, c_id, d_id');
        $this->db->from('workers');
        $this->db->where('worker_id', $this->session->userdata('worker_id'));
        $query = $this->db->get();
        $workers = $query->result();
        $worker = $workers[0];
        
        $this->db->select('task_id, task_name, est_hours');
        $this->db->from('tasks');
        $this->db->where('c_id', $worker->c_id);
        $this->db->where('d_id', $worker->d_id);
        $this->db->where('task_name', 'Maintenance Unassigned');
        $query = $this->db->get();
        $default_tasks = $query->result();
        
        if (!$default_tasks) {
            $this->db->insert('tasks', [
                'task_name' => 'Maintenance Unassigned',
                'c_id' => $worker->c_id,
                'd_id' => $worker->d_id,
                'est_hours' => 1
            ]);
        }
        
        $this->db->select('task_id, task_name, est_hours');
        $this->db->from('tasks');
        $this->db->where('c_id', $worker->c_id);
        $this->db->where('d_id', $worker->d_id);
        $this->db->order_by('strikethrough, core, task_name');
        $query = $this->db->get();
        $tasks = $query->result();
        
        $this->db->select('mcn_product.id, mcn_product.name, mcn_product.last_cost, mcn_product.average_cost, mcn_product_vari.id AS vari_id, mcn_product_vari.product_number, mcn_product.part_number');
        $this->db->from('mcn_product');
        $this->db->join('mcn_product_vari', 'mcn_product_vari.mcn_product_id = mcn_product.id AND mcn_product_vari.remove = 0', 'LEFT OUTER');
        $this->db->where('mcn_product.c_id', $worker->c_id);
        $this->db->where('mcn_product.d_id', $worker->d_id);
        $this->db->where('mcn_product.remove', 0);
        $this->db->order_by('mcn_product.name, mcn_product_vari.product_number');
        $query = $this->db->get();
        $parts = $query->result_array();
        
        $html = '<div class="add-job-area">';
        if ($wo_item_id) {
            $html .= '
                <form class="new-wo-job-form">
                    <input type="hidden" name="wo_item_id" value="'. $wo_item_id . '" />
            ';
        }
        $html .= '<div class="job-item add-job-form new-job-item" job_index="' . $job_index . '">';
        
        if (!$wo_item_id) {
            $html .= '<a class="delete-this-job" href="#"><i class="fa fa-trash-o"></i></a>';
        }
        
        $html .= '
                <div class="form-job-group">
                    <label class="control-label">Name:</label>
                    <input class="form-control job-name" title="Job Name" name="job_name[' . $job_index . ']" placeholder="Name" value="' . $_POST['wo_name'] . '">
                </div>
                <div class="form-job-group">
                    <label class="control-label">Job:</label>
                    <select class="form-control medium-job-input job-select" title="Job" name="task_id[' . $job_index . ']">
                        <option value="">Select Task</option>';
        
        if ($tasks) {
            foreach ($tasks as $task) {
                $html .= '<option value="' . $task->task_id . '" est_hr="' . $task->est_hours . '"' . ($task->task_name == 'Maintenance Unassigned' ? 'selected' : '') . '>' . $task->task_name . '</option>';
            }
        }
        
        $html .= '
                    </select>
                </div>
                <div class="form-job-group">
                    <label class="control-label">Est Time:</label>
                    <input class="form-control short-job-input est_hr" title="Est Time" placeholder="Est Time" name="est_hr[' . $job_index . ']">
                </div>
                <div class="form-job-group">
                    <label class="control-label">Book Rate:</label>
                    <input class="form-control short-job-input" title="Book Rate" placeholder="Book Rate" name="book_rate[' . $job_index . ']">
                </div>
                <div class="form-job-group">
                    <label class="control-label">Repair</label>
                    <select class="form-control" title="Repair Type" name="repair_type[' . $job_index . ']">
                        <option value="repair">Repair</option>
                        <option value="preventative">Preventative</option>
                    </select>
                </div>
                <div class="form-job-group">
                    <label class="control-label">Notes:</label>
                    <textarea class="form-control wo-job-notes" title="Job Note" name="notes[' . $job_index . ']"></textarea>
                </div>
                <div class="part-tool-area">
                    <div class="part-tool-header">
                        <a class="part-tool-btn add-part" href="#"><i class="fa fa-plus"></i> Parts</a>
                        <a class="part-tool-btn add-tool" href="#"><i class="fa fa-plus"></i> Tools</a>
                    </div>
                    <div class="part-tool-body">
                        <div class="part-area part-content">
                            <p class="text-right"><a class="bar-scan" href="#" data-toggle="modal" data-target="#scanBarCodeModal"><i class="fa fa-barcode"></i> Scan</a></p>
                            <div class="part-item-header">
                                <div class="part-qty-content">Qnty</div>
                                <div class="part-name-content">Parts Name</div>
                                <div class="part-cost-content">Cost</div>
                                <div class="part-total-content">Total</div>
                                <div class="part-remove-content"></div>
                            </div>
                            <div class="part-item-list">
                                <div class="part-item">
                                    <div class="part-qty-content"><input class="add-part-input add-part-qty" name="quantity[' . $job_index . '][]" placeholder="Qty"></div>
                                    <div class="part-name-content">
                                        <select class="part-select" name="part_id[' . $job_index . '][]">
                                            <option value="">Select Part</option>';
        if ($parts) {
            $products = [];
            foreach ($parts as $part) {
                if (!isset($products[$part['id']])) {
                    $products[$part['id']] = [
                        'id' => $part['id'],
                        'name' => $part['name'],
                        'part_number' => $part['part_number'],
                        'average_cost' => $part['average_cost'],
                        'product_numbers' => []
                    ];
                }
                $products[$part['id']]['product_numbers'][] = $part['product_number'];
            }
            $parts = $products;
            
            foreach ($parts as $part) {
                $html .= '<option value="' . $part['id'] . '" average_cost="' . $part['average_cost'] . '" part_number="' . $part['part_number'] . 
                    '" product_numbers="' . ($part['product_numbers'] ? implode(',', $part['product_numbers']) : '') . '">' . $part['name'] . '</option>';
            }
        }
        $html .= '
                                        </select>
                                    </div>
                                    <div class="part-cost-content"><input class="add-part-input add-part-cost" placeholder="Cost" name="unit_price[' . $job_index . '][]"></div>
                                    <div class="part-total-content"><input readonly class="add-part-input add-part-total" placeholder="Total"></div>
                                    <div class="part-remove-content"><a class="rmv-part-item hide" href="#"><i class="fa fa-trash-o"></i></a></div>
                                </div>
                                <div class="add-new-part">
                                    <div class="part-qty-content"><input class="add-part-input add-part-qty" name="quantity[' . $job_index . '][]" placeholder="Qty"></div>
                                    <div class="part-name-content">
                                        <select class="part-select" name="part_id[' . $job_index . '][]">
                                            <option value="">Select Part</option>';
        if ($parts) {
            foreach ($parts as $part) {
                $html .= '<option value="' . $part['id'] . '" average_cost="' . $part['average_cost'] . '" part_number="' . $part['part_number'] .
                    '" product_numbers="' . ($part['product_numbers'] ? implode(',', $part['product_numbers']) : '') . '">' . $part['name'] . '</option>';
            }
        }
        $html .= '
                                        </select>
                                    </div>
                                    <div class="part-cost-content"><input class="add-part-input add-part-cost" placeholder="Cost" name="unit_price[' . $job_index . '][]"></div>
                                    <div class="part-total-content"><input readonly class="add-part-input add-part-total" placeholder="Total"></div>
                                    <div class="part-remove-content"><a class="rmv-part-item hide" href="#"><i class="fa fa-trash-o"></i></a></div>
                                </div>
                                <div class="part-total-wrapper">
                                    <div class="part-total-title">Total:</div>
                                    <div class="part-total-content job-part-total">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="add-job-footer">';
        if ($wo_item_id) {
            $html .= '
                <a class="delete-this-new-job footer-btn" href="#"><i class="fa fa-trash-o"></i> Remove</a>
                <a class="save-this-new-job footer-btn" href="#"><i class="fa fa-save"></i> Save</a>
            ';
        }
        
        $html .= '</div></div>';
        
        if ($wo_item_id) {
            $html .= '</form>';
        }
        $html .= '</div>';
        
        echo json_encode(['status' => 1, 'html' => $html]);
        exit();
    }
    
    public function create_wo_item() 
    {
        $worker = $this->check_wo_permissions();
        if (!$worker) {
            echo json_encode(['status' => 0, 'message' => 'You do not have permission to create work order, please have your administrator change your permission in employee management under your name under in the mobile tab.']);
            exit();
        }
        
        $this->db->trans_begin();
        
        $equipment_ids = isset($_POST['equipment_id']) ? $_POST['equipment_id'] : [];
        if (!$equipment_ids) {
            echo json_encode(['status' => 0, 'message' => 'Please select equipment.']);
            exit();
        }
        if (!isset($_POST['wo_name']) || !$_POST['wo_name']) {
            echo json_encode(['status' => 0, 'message' => 'Please input Work Order name.']);
            exit();
        }
        
        $task_ids = isset($_POST['task_id']) ? $_POST['task_id'] : [];
        if (!$task_ids) {
            echo json_encode(['status' => 0, 'message' => 'Please add jobs.']);
            exit();
        } else {
            $has_task = false;
            foreach ($task_ids as $task_id) {
                if ($task_id) {
                    $has_task = true;
                    break;
                }
            }
            if (!$has_task) {
                echo json_encode(['status' => 0, 'message' => 'Please select jobs.']);
                exit();
            }
        }
        
        foreach ($equipment_ids as $equipment_id) {
            $this->db->insert('work_order_item', [
                'c_id' => $worker->c_id,
                'd_id' => $worker->d_id,
                'equipment_id' => $equipment_id,
                'work_order_id' => null,
                'name' => $_POST['wo_name'],
                'description' => $_POST['description'],
                'status' => 0,
                'scheduled_at' => date('Y-m-d'),
                'schedule_type' => 2
            ]);
            $work_order_item_id = $this->db->insert_id();
            
            if ($work_order_item_id) {
                if ($task_ids) {
                    foreach ($task_ids as $index => $task_id) {
                        $this->create_wo_job_item($work_order_item_id, $index, $task_id);
                    }
                }
            }
        }
        
        $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            
            echo json_encode(['status' => 0, 'message' => 'There was an error while trying to start task, please try again!']);
        } else {
            $this->db->trans_commit();
            
            echo json_encode(['status' => 1, 'message' => 'Work Order has been created successfully.']);
        }
    }
    
    public function create_wo_item_new_job()
    {
        $worker = $this->check_wo_permissions();
        if (!$worker) {
            echo json_encode(['status' => 0, 'message' => 'You do not have permission to create work order, please have your administrator change your permission in employee management under your name under in the mobile tab.']);
            exit();
        }
        
        $wo_item_id = (isset($_POST['wo_item_id']) && $_POST['wo_item_id']) ? $_POST['wo_item_id'] : null;
        $this->db->select('work_order_item.id, work_order_item.name, mcn_manufacturer.name AS manufacturer, equipment_type.model');
        $this->db->from('work_order_item');
        $this->db->join('equipment', 'equipment.id = work_order_item.equipment_id AND equipment.remove = 0', 'INNER');
        $this->db->join('equipment_type', 'equipment_type.id = equipment.equipment_type_id AND equipment_type.remove = 0', 'INNER');
        $this->db->join('mcn_manufacturer', 'mcn_manufacturer.id = equipment_type.manufacturer_id', 'INNER');
        $this->db->where('work_order_item.id', $wo_item_id);
        $query = $this->db->get();
        
        $wos = $query->result();
        if (!$wos) {
            echo json_encode(['status' => 0, 'message' => 'Work Order is no longer available. Please refresh page to see changes.']);
            exit();
        }
        $wo = $wos[0];
        
        $task_ids = isset($_POST['task_id']) ? $_POST['task_id'] : [];
        if (!$task_ids || !$task_ids[1]) {
            echo json_encode(['status' => 0, 'message' => 'Please select job.']);
            exit();
        }
        
        $job_names = isset($_POST['job_name']) ? $_POST['job_name'] : [];
        if (!$job_names || !$job_names[1]) {
            echo json_encode(['status' => 0, 'message' => 'Please enter job name.']);
            exit();
        }
        
        foreach ($task_ids as $index => $task_id) {
            if (!isset($_POST['notes']) || !isset($_POST['notes'][$index]) || !$_POST['notes'][$index]) {
                $job_name = (isset($_POST['job_name']) && isset($_POST['job_name'][$index])) ? $_POST['job_name'][$index] : $_POST['wo_name'];
                $_POST['notes'][$index] = $wo->manufacturer . ' ' . $wo->model . ': ' . $wo->name . ' - ' . $job_name;
            }
            $this->create_wo_job_item($wo_item_id, $index, $task_id);
        }
        
        $this->refreshMechanicBoard(date('Y-m-d'), 'work_order');
        
        echo json_encode(['status' => 1]);
        exit();
    }
}