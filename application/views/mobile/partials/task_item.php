<?php
/**
 * @var $index
 * @var $timer
 * @var $time_id
 * @var $wb_task_id
 * @var $task_name
 * @var $dept_name
 * @var $notes
 * @var $hour
 * @var $minute
 * @var $second
 */
?>

<div class="row task-item <?= ($active_sb && $active_sb != $sb_id) ? 'grayed-out' : '' ?> <?= $show_job ? '' : 'hide'; ?>" data-wb_task_id="<?= $wb_task_id; ?>" id="task-<?= $index; ?>" 
data-hour="<?= $hour; ?>" data-minute="<?= $minute; ?>" data-second="<?= $second; ?>" time_id="<?= $time_id; ?>" data-timer="<?= $timer; ?>">
    <div class="col-xs-7 pad-l0">
        <a href="#" class="open-modal" data-target="#manual-time-modal" title="Add manual time" wb_task_id="<?= $wb_task_id; ?>">
            <i class="fa fa-clock-o" aria-hidden="true"></i>
        </a>
        <span class="job-name">
        	<span class="job-index-num"><?= isset($job_index_num) ? $job_index_num : ($index + 1); ?></span>.
        	<span class="job-index-name-<?= $wb_task_id ?>"><?= $task_name; ?></span>
        	<?php if ($task_notes): ?>
        	<span class="job-index-notes-<?= $wb_task_id ?>"><?= ' - ' . $task_notes; ?></span>
        	<?php endif; ?>
        	<?php if ($sub_task_name): ?>
        	<span class="job-index-subtask"><?= ' - ' . $sub_task_name ?></span>
        	<?php endif; ?>
        </span>
        
        <?php if ($dept_name): ?>
        	<p class="job-address">(<?= $dept_name; ?>)</p>
        <?php endif; ?>
        
        <p class="job-address job-notes-<?= $wb_task_id; ?>"><?= $notes; ?></p>
        	
    </div>
    <div class="col-xs-5 pad-r0 text-center">
        <?php if ($time_id): ?>
            <a class="btn tbl-log-time btn-stop-task" wb_task_id="<?= $wb_task_id; ?>">
                <i class="fa fa-stop-circle-o" aria-hidden="true"></i>
            </a>
        <?php else: ?>
            <a class="btn tbl-log-time btn-start-task" wb_task_id="<?= $wb_task_id; ?>">
                <i class="fa fa-play-circle-o" aria-hidden="true"></i>
            </a>
        <?php endif; ?>
        <p class="job-log-time act-timer">
            <?= !$time_id ? $hour . ':' . ($minute < 10 ? '0' . $minute : $minute) . ':' . ($second < 10 ? '0' . $second : $second) : '' ?>
        </p>
        <div class="task-action-group-btn">
            <?php if ($edit_job_notes || !$savedbyID): ?>
                <a class="wb-btn open-note open-modal" href="#" wb_task_id="<?= $wb_task_id; ?>">
                    <i class="fa fa-pencil-square-o"></i> Add Note
                </a>
            <?php endif; ?>
            <a class="wb-btn complete-job" href="#" wb_task_id="<?= $wb_task_id; ?>" time_id="<?= $time_id; ?>"><i class="far fa-square"></i> Job Complete</a>
        </div>
    </div>
    <div class="col-xs-12">
    <?php if ($equipments): ?>
        	<label class="eqm-label">Equipments</label>
        	<?php foreach ($equipments as $equipment): ?>
        		<div class="equipment-item"><?php echo $equipment->equipment_model . ' ' . $equipment->equipment_model_id; ?></div>
        	<?php endforeach;?>
    	<?php endif; ?></div>
    <div class="col-xs-12 split-name"><?= $split_name ?></div>
</div>