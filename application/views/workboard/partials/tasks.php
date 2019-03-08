<div class="table-responsive task-table">
    <div class="header-task-content clearfix">
        Today's Job
        <button class="btn btn-default btn-add-task add-task-pool"><?= $this->lang->line('add') ?> <?= $this->lang->line('task') ?></button>
    </div>
    <div>
    	<?php foreach ($tasks as $index => $task): ?>
        	<?php
                $show_job = !isset($task->show_job) || 1 == $task->show_job;
        	    $timer = $task->start_time ? ($task->end_time ? $task->end_time : time()) - $task->start_time : 0;
        	    $total_time = $task->total_time + $timer;
        	    $hour = floor(intval($total_time) / 3600);
        	    $minute = floor((intval($total_time) % 3600) / 60);
        	    $second = intval($total_time) - $hour * 3600 - $minute * 60;
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
            <div class="task-item <?= ($active_sb && $active_sb != $task->split_table_id) ? 'grayed-out' : '' ?> <?= $task->show_job ? '' : 'hide'; ?>" id="task-<?= $index ?>"
            	data-hour="<?= $hour ?>" data-minute="<?= $minute ?>" data-second="<?= $second ?>" time_id="<?= $task->time_id ?>" data-timer="<?= $timer ?>">
                <div class="column task-name width70">
                    <p class="company-name">(The Valley Club)</p>
                    <p style="font-size: 20px;">
                    	<span class="job-index-name-<?= $task->wb_task_id ?>"><?= $task_name ?></span>
                    	<?php if ($task->task_notes): ?>
                    		<span class="job-index-notes job-index-notes-<?= $task->wb_task_id ?>"><?= ' - ' . $task->task_notes; ?></span>
                    	<?php endif; ?>
                    	<?php if ($task->sub_task_name): ?>
                    		<span class="job-index-subtask"><?= ' - ' . $task->sub_task_name ?></span>
                    	<?php endif; ?>
                    </p>
                    <span class="hide job-notes-<?= $task->wb_task_id ?>" style="font-weight: normal; font-size: 15px;">
                        <?= $notes ?>
                    </span>
                    
                    <div class="split-name"><?= $task->split_name ?></div>
                    
                   
                	<?php if ($task->equipments): ?>
                	<div class="equipment-wrapper">
                	<?php foreach ($task->equipments as $equipment): ?>
                		<span class="equipment-item"><?php echo $equipment->equipment_model . ' ' . $equipment->equipment_model_id; ?></span>
                	<?php endforeach;?>
                	</div>
                	<?php endif; ?>
                </div>

                <div class="column task-action width20">
                    <p class="job-order">Job 1</p>
                    <?php if ($task->time_id): ?>
                        <a href="#" class="btn btn-stop-task" wb_task_id="<?= $task->wb_task_id ?>"><i class="far fa-stop-circle"></i></a>
                    <?php else: ?>
                        <a href="#" class="btn btn-start-task" wb_task_id="<?= $task->wb_task_id ?>"><i class="far fa-play-circle"></i></a>
                    <?php endif; ?>
                    <p class="act-timer">
                    	<?= !$task->time_id ? $hour . ':' . ($minute < 10 ? '0' . $minute : $minute) . ':' . ($second < 10 ? '0' . $second : $second) : '' ?>
                    </p>
                    <div class="task-action-group-btn">
                        <a class="wb-btn open-note open-modal" href="#" wb_task_id="<?= $task->wb_task_id; ?>">
                            <i class="far fa-edit"></i> Add Note
                        </a>
                        <a class="wb-btn complete-job" href="#" wb_task_id="<?= $task->wb_task_id; ?>" time_id="<?= $task->time_id ?>"><i class="far fa-square"></i> Job Complete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>