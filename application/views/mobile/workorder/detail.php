<div id="m-content" class="m-workorder">
<?php if ($work_order) { ?>
    <div class="m-wo-header">
        <div class="m-wo-title"><a class="back-work-order" href="/mobile/workorder"><i class="fa fa-arrow-left"></i></a><?php echo $work_order['name']; ?></div>
    </div>
    <div class="m-wo-detail">
        <div class="wo-detail-equipment">
            <div class="equipment-info">
                <p class="equipment-name"><?php echo $work_order['equipment_type']; ?></p>
                <p class="equipment-parent"><?php echo $work_order['equipment']; ?></p>
            </div>
            <a class="eqm-change-status" href="#">Status<br>
                <p class="status-color <?php echo $work_order['equipment_status'] == 3 ? 'status-disable' : ($work_order['equipment_status'] == 2 ? 'status-issue' : 'status-ready'); ?>"></p>
            </a>
            <div class="equipment-action">
                <a class="equipment-update" mobile="1" href="#" equipment_id="<?php echo $work_order['equipment_id']; ?>">Update</a>
                <p class="equipment-hour equipment-hour-<?php echo $work_order['equipment_id']; ?>"><?php echo $work_order['equipment_hours']; ?> Hours</p>
            </div>
            <div class="select-status-area">
                <div class="select-status-header">Change Status</div>
                <div class="select-status-body">
                    <a href="#" class="status-select select-ready <?php echo $work_order['equipment_status'] == 1 ? 'selected' : ''; ?>" equipment_id="<?php echo $work_order['equipment_id']; ?>" val="1"><span class="status-color status-ready"></span> Ready</a>
                    <a href="#" class="status-select select-issue <?php echo $work_order['equipment_status'] == 2 ? 'selected' : ''; ?>" equipment_id="<?php echo $work_order['equipment_id']; ?>" val="2"><span class="status-color status-issue"></span> Issue</a>
                    <a href="#" class="status-select select-disable <?php echo $work_order['equipment_status'] == 3 ? 'selected' : ''; ?>" equipment_id="<?php echo $work_order['equipment_id']; ?>" val="3"><span class="status-color status-disable"></span> Disabled</a>
                </div>
            </div>
        </div>
        
        <div class="wo-detail-notes"><?php echo $work_order['description']; ?></div>
        
        <div class="wo-detail-job">
        
        	<?php $jobs = $work_order['jobs']; ?>
        	<?php if ($jobs): ?>
        		<?php foreach ($jobs as $job): ?>
        			<?php if (isset($job_id) && $job['work_order_item_job_id'] != $job_id) { continue; }?>
                	<div class="wo-job-item">
                        <div class="job-header">
                            <p class="job-name"><?php echo $job['job_name']; ?></p>
                            <div class="job-action">
                            	<?php if ($job['time_id']): ?>
                                	<a class="wo-action-btn stop-btn" href="#" wo_item_job_id="<?php echo $job['work_order_item_job_id']; ?>" wb_task_id="<?php echo $job['wb_task_id']; ?>" time_id="<?php echo $job['time_id']; ?>">Stop</a>
                            		<p class="job-countdown" time="<?php echo $job['act_hr']; ?>"></p>
                                <?php else: ?>
                                	<a class="wo-action-btn start-btn" href="#" wo_item_job_id="<?php echo $job['work_order_item_job_id']; ?>" wb_task_id="<?php echo $job['wb_task_id']; ?>">Start</a>
                                	<p class="job-countdown hide" time="<?php echo $job['act_hr']; ?>"></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="job-detail"><?php echo $job['job_notes']; ?></p>
                        <div class="wo-job-status-content">
                            <?php if ($job['status'] == 0): ?>
                            	<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-not" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Not Started</a>
                            <?php elseif ($job['status'] == 1): ?>
                            	<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-progress" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">In Progress</a>
                        	<?php elseif ($job['status'] == 2): ?>
                        		<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-complete" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Completed</a>
                        	<?php elseif ($job['status'] == 3): ?>
                        		<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-skip" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Skip</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-part">
                            <div class="job-part-header">
                                <div class="job-part-qty">Qnty</div>
                                <div class="job-part-name">Part</div>
                                <div class="job-part-cost">Cost</div>
                                <div class="job-part-total">Total</div>
                                <div class="job-part-edit">&nbsp;</div>
                            </div>
                            <?php $products = $job['products']; ?>
                        	<?php $total = 0; ?>
                            <?php if ($products): ?>
                            	<?php foreach ($products as $product): ?>
                                    <div class="part-item">
                                        <div class="job-part-qty"><?php echo $product['quantity']; ?></div>
                                        <div class="job-part-name" product_id="<?php echo $product['product_id']; ?>"><?php echo $product['name']; ?></div>
                                        <div class="job-part-cost"><?php echo number_format($product['cost'], 2); ?></div>
                                        <div class="job-part-total"><?php echo number_format($product['total'], 2); ?></div>
                                        <div class="job-part-edit">
                                        	<a class="edit-part" href="#" wo_job_product_id="<?php echo $product['id']; ?>"><i class="fa fa-edit"></i></a>
                                        </div>
                                    </div>
                            	<?php endforeach; ?>
                            	<?php $total += $product['total']; ?>
                            <?php endif; ?>
                            <div class="part-item add-part-row">
                                <div class="job-part-qty"></div>
                                <div class="job-part-name" style="cursor: pointer;">Add New Part</div>
                                <div class="job-part-cost"></div>
                                <div class="job-part-total"></div>
                                <div class="job-part-edit">
                                    <a class="add-new-part" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">
                                    	<i class="fa fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="part-total">
                                Total Parts:  <?php echo number_format($total, 2); ?>
                            </div>
                        </div>
                        
                        <div class="job-note-list">
                        	<?php $notes = $job['notes']; ?>
                        	<?php if ($notes): ?>
                            	<?php foreach ($notes as $note): ?>
                                    <div class="job-note-item">
                                        <p class="author"><?php echo $note['worker']; ?> <span class="date"><?php echo $note['add_time']; ?></span></p>
                                        <div class="note-content">
                                            <p><?php echo $note['notes']; ?></p>
                                            <a class="edit-note" href="#" wo_job_note_id="<?php echo $note['id']; ?>"><i class="fa fa-pencil"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <a class="add-new-note" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">
                            	<i class="fa fa-pencil"></i> Add Note
                            </a>
                        </div>
                    </div>
        		<?php endforeach; ?>
        	<?php endif; ?>
        </div>
    </div>
    
<?php } ?>
</div>

<div class="modal fade" id="partModal" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="part-modal modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Add Part</h4>
            </div>
            <div class="modal-body">
                <form class="part-form form-horizontal">
                    <div class="form-group row">
                        <label class="control-label col-xs-3" for="quty">Qnty</label>
                        <div class="col-xs-9">
                            <input class="form-control" id="quty">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-xs-3" for="part">Part</label>
                        <div class="col-xs-9">
                            <select class="form-control part-select" id="part"></select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-xs-3" for="cost">Cost</label>
                        <div class="col-xs-9">
                            <input class="form-control" id="cost">
                        </div>
                    </div>
                    <p class="modal-total-cost"></p>
                    <div class="button-group">
                        <button class="btn btn-delete delete-part pull-left"><i class="fa fa-trash"></i> Delete</button>
                        <button class="btn btn-edit save-part pull-right"><i class="fa fa-pencil"></i> Edit Part</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="noteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="note-modal modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Edit Note</h4>
            </div>
            <div class="modal-body">
                <form class="note-form form-horizontal">
                    <div class="form-group">
                        <div class="col-xs-12">
                            <textarea class="form-control note-textarea" title="Note Content"></textarea>
                        </div>
                    </div>
                    <div class="button-group">
                        <button class="btn btn-delete delete-notes pull-left"><i class="fa fa-trash"></i> Delete</button>
                        <button class="btn btn-edit save-notes pull-right"><i class="fa fa-pencil"></i> Edit Notes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="note-modal modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Set Hour</h4>
            </div>
            <div class="modal-body">
                <form class="update-form form-horizontal">
                    <p class="estimate-hour">Estimated Hours: <span>56.00</span></p>
                    <div class="form-group">
                        <div class="col-xs-12 update-input-group">
                            <input class="form-control hour-input" title="Update Hours" placeholder="Update Hours">
                            <button class="btn btn-update"><i class="fa fa-refresh"></i></button>
                        </div>
                    </div>
                    <p class="update-text">Updated Last: 07/06/2018</p>
                    <p class="update-text">By Tran Pham</p>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="note-modal modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Work Order Status</h4>
            </div>
            <div class="modal-body">
                <div class="status-select-content">
                    <a class="wo-job-status status-progress" href="#">In Progress</a>
                    <a class="wo-job-status status-complete" href="#">Completed</a>
                    <a class="wo-job-status status-skip" href="#">Skip</a>
                </div>
            </div>
        </div>
    </div>
</div>