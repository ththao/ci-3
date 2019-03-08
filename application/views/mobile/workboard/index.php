<?php
$countJobNum = 0;
?>

<div id="m-content">
    <div class="m-clock-in">
        <button class="btn m-clock-btn btn-main-clock-in"><?= $this->lang->line('clock_in') ?></button>
    </div>
    <div class="m-task">
        <p class="today-task"><?= $this->lang->line('todays_jobs') ?><a class="add-task add-task-pool" href="#">Add Task</a></p>
        <div class="table-responsive m-table">
            <div class="table">
            	<?php foreach ($tasks as $index => $task): ?>
            		<?php
                	    $timer = $task->start_time ? ($task->end_time ? $task->end_time : time()) - $task->start_time : 0;
                	    $total_time = $task->total_time + $timer;
                	    $hour = floor(intval($total_time) / 3600);
                	    $minute = floor((intval($total_time) % 3600) / 60);
                	    $second = intval($total_time) - $hour * 3600 - $minute * 60;
                        $show_job = (!isset($task->show_job) || $task->show_job);
                        $task_name = $task->task_name;
                        if ($task->wb_task_notes) {
                            $task_name .= ' - ' . $task->wb_task_notes;
                        }
                        if ($task->task_notes) {
                            //$task_name .= ' - ' . $task->task_notes;
                        }
                        
                        $notes = '';
                        if ($need_translate) {
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
                	?>
					
                    <?php $this->load->view('mobile/partials/task_item', array(
                        'index'         => $index,
                        'timer'         => $timer,
                        'time_id'       => $task->time_id,
                        'wb_task_id'    => $task->wb_task_id,
                        'task_name'     => $task_name,
                        'task_notes'    => $task->task_notes,
                        'sub_task_name' => $task->sub_task_name,
                        'dept_name'     => $task->department_name,
                        'notes'         => $notes,
                        'hour'          => $hour,
                        'minute'        => $minute,
                        'second'        => $second,
                        'show_job'      => $show_job,
                        'sb_id'         => $task->split_table_id,
                        'active_sb'     => $active_sb,
                        'split_name'    => $task->split_name,
                        'job_index_num' => $show_job ? ++$countJobNum : $index,
                        'savedbyID'     => $task->savedbyID,
                        'equipments'    => $task->equipments
                    )); ?>
            	<?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php $this->load->view("mobile/partials/footer", [
        'departments' => $departments
    ]);?>
</div>

<div class="modal fade" id="manual-time-modal" tabindex="-1" role="dialog" aria-labelledby="manual-time-modal-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="manual-time-modal-label"><?= $this->lang->line('add') ?> <?= $this->lang->line('manual_time') ?></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal manual-time-form">
                	<div class="form-group mar0">
                		<div class="alert alert-danger hide" id="manual-time-error"></div>
                	</div>
                	
                	<input type="hidden" name="mobile" value="1" />
                	
                	<?php if ($logged_times): ?>
                	<div class="form-group mar0" style="padding-bottom: 10px;">
                		<div class="row">
                        	<div class="col-xs-12">
                        		<label>Please notice logged time:</label>
                				<?= $logged_times ?>
                			</div>
                		</div>
                	</div>
                	<?php endif; ?>
                	
                    <div class="form-group mar0">
                    	<input type="hidden" name="wb_task_id" class="wb_task_id" />
                    	<div class="row">
                        	<div class="col-xs-4">
                            	<label>Start</label>
                                <input type="text" name="start_time" class="time-picker form-control manual-start_time">
                            </div>
                            <div class="col-xs-4">
                                <label>End</label>
                                <input type="text" name="end_time" class="time-picker form-control manual-end_time">
                            </div>
                        	<div class="col-xs-4">
                            	<label>Number of Hours</label>
                                <input type="text" name="number_of_hours" class="form-control manual-hours" placeholder="Format: 2 or 2.5">
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-right note-button">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success btn-save-manual-time">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="aliveModal" tabindex="-1" role="dialog" aria-labelledby="aliveModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="aliveModalLabel">Your will be logged out in 30 secs. Do you want to stay logged in?</h4>
            </div>
            <div class="modal-body">
                <div class="form-group text-right note-button">
                    <a type="button" class="btn btn-danger" href="/logout">Log Out</a>
                    <button type="button" class="btn btn-success">Stay Logged In</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Add Task</h4>
            </div>
            <div class="modal-body">
                <div class="list-atask-wrapper">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="note-modal" tabindex="-1" role="dialog" aria-labelledby="noteModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Task Name</h4>
            </div>
            <div class="modal-body">
                <div class="note-modal-content">
                    <form id="taskNoteForm">
                        <div class="form-group">
                            <label class="control-label" for="aTaskNote">
                                Note:
                            </label>
                            <input type="hidden" class="wb_task_id" value="" />
                            <textarea class="form-control note-content"></textarea>
                        </div>
                        <div class="form-group-btn">
                            <button class="btn btn-default btn-exit"><i class="fa fa-window-close-o"></i> Exit</button>
                            <button class="btn btn-default btn-save"><i class="fa fa-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>