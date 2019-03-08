<div id="m-content" class="m-workorder">
    <div class="m-wo-header">
        <div class="m-wo-title">Work Orders</div>
    </div>
    <div class="m-wo-list">
    	<?php if ($add_wo_permission) { ?>
        <div class="emp-add-wo-area">
            <a class="add-wo" href="/mobile/workorder/create">Create Work Order</a>
        </div>
        <?php } ?>
		<?php if ($work_orders): ?>
    		<?php foreach ($work_orders as $wo): ?>
    		<?php $e_specs = $wo['e_specs']; ?>
                <div class="m-wo-item">
                    <div class="wo-item-header">
                        <div class="wo-header-name">
                            <a class="wo-name" href="workorder/detail?id=<?php echo $wo['id']; ?>"><?php echo $wo['name']; ?></a>
                            <a class="open-wo" href="workorder/detail?id=<?php echo $wo['id']; ?>"><i class="fa fa-eye"></i></a>
                        </div>
                        <div class="wo-header-info">
                            <div class="wo-equipment">
                                <p class="wo-equipment-name"><?php echo $wo['equipment_type']; ?></p>
                                <p class="wo-equipment-parent"><?php echo $wo['equipment']; ?></p>
                            </div>
                            <div class="wo-header-action">
                                <a class="wo-header-btn update-hour equipment-update" mobile="1" href="#" equipment_id="<?php echo $wo['equipment_id']; ?>"><i class="fa fa-clock-o"></i> Hours</a>
                                <a class="wo-header-btn eqm-status" href="#"><span class="status-color <?php echo $wo['equipment_status'] == 3 ? 'status-disable' : ($wo['equipment_status'] == 2 ? 'status-issue' : 'status-ready'); ?>"></span> Status</a>
                                <div class="select-status-area">
                                    <div class="select-status-header">Change Status</div>
                                    <div class="select-status-body">
                                        <a href="#" class="status-select select-ready <?php echo $wo['equipment_status'] == 1 ? 'selected' : ''; ?>" equipment_id="<?php echo $wo['equipment_id']; ?>" val="1"><span class="status-color status-ready"></span> Ready</a>
                                        <a href="#" class="status-select select-issue <?php echo $wo['equipment_status'] == 2 ? 'selected' : ''; ?>" equipment_id="<?php echo $wo['equipment_id']; ?>" val="2"><span class="status-color status-issue"></span> Issue</a>
                                        <a href="#" class="status-select select-disable <?php echo $wo['equipment_status'] == 3 ? 'selected' : ''; ?>" equipment_id="<?php echo $wo['equipment_id']; ?>" val="3"><span class="status-color status-disable"></span> Disabled</a>
                                    </div>
                                </div>
                                <?php if ($e_specs): ?>
                                	<a class="wo-header-btn specs-eqm" href="#"><span class="fa fa-exclamation-circle"></span> Specs</a>
                                <?php endif; ?>
                            </div>
                            <?php if ($e_specs): ?>
                                <div class="wo-list-specs" style="display: none;">
                                    <?php foreach ($e_specs as $e_spec): ?>
                                    <div class="specs-item">
                                        <span class="specs-name"><?php echo $e_spec['spec_name']; ?></span>:
                                        <span class="specs-measure"><?php echo $e_spec['value'] . ' ' . $e_spec['measure']; ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="wo-item-body">
                        <div class="wo-job-list">
                        	
                        	<?php $jobs = $wo['jobs']; ?>
                        	<?php if ($jobs): ?>
                        		<?php foreach ($jobs as $job): ?>
                        			<div class="wo-job-item">
                                        <div class="wo-job-content">
                                            <a class="open-wo-job" href="workorder/detail?id=<?php echo $wo['id']; ?>&job=<?php echo $job['work_order_item_job_id']; ?>"><i class="fa fa-eye"></i></a>
                                            <div class="wo-job-info">
                                                <p class="wo-job-name"><?php echo $job['job_name']; ?></p>
                                                <div class="wo-hour-container">
                                                    <p class="wo-hour-item est-hour">Est: <?php echo number_format($job['scheduled'], 2); ?> hrs</p>
                                                    <p class="wo-hour-item act-hour <?php echo $job['time_id'] ? 'hide' : ''; ?>">Act: <?php echo number_format($job['act_hr']/3600, 2); ?> hrs</p>
                                                </div>
                                            </div>
                                            <div class="wo-action">
                                            	<?php if ($job['time_id']): ?>
                                                	<a class="wo-action-btn stop-btn" href="#" wo_item_job_id="<?php echo $job['work_order_item_job_id']; ?>" wb_task_id="<?php echo $job['wb_task_id']; ?>" time_id="<?php echo $job['time_id']; ?>">Stop</a>
                                                	<p class="job-countdown" time="<?php echo $job['act_hr']; ?>"></p>
                                                <?php else: ?>
                                                	<a class="wo-action-btn start-btn" href="#" wo_item_job_id="<?php echo $job['work_order_item_job_id']; ?>" wb_task_id="<?php echo $job['wb_task_id']; ?>">Start</a>
                                                	<p class="job-countdown hide" time="<?php echo $job['act_hr']; ?>"></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($job['status'] == 0): ?>
                                        	<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-not" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Not Started</a>
                                        <?php elseif ($job['status'] == 1): ?>
                                        	<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-progress" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">In Progress</a>
                                    	<?php elseif ($job['status'] == 2): ?>
                                    		<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-complete" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Completed</a>
                                    	<?php elseif ($job['status'] == 3): ?>
                                    		<a class="wo-job-status wo-job-status-<?php echo $job['work_order_item_job_id']; ?> status-skip" href="#" wo_job_id="<?php echo $job['work_order_item_job_id']; ?>">Skip</a>
                                        <?php endif; ?>
                                        
                                        <p class="wo-job-note"><?php echo $job['job_notes']; ?></p>
                                    </div>
                        		<?php endforeach; ?>
                        		
                        		<div class="wo-job-item add-job-field">
                        			<a href="#" class="add-new-wo-job" wo_item_id="<?php echo $wo['id']; ?>"><i class="fa fa-plus"></i> Add Job</a>
                        		</div>
                        	<?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
		<?php endif; ?>
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

<div class="modal fade" id="scanBarCodeModal" role="dialog" aria-labelledby="scanBarCode">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                <h4 class="modal-title" id="myModalLabel">WO 0117</h4><!--Job name-->
                <p>Part Scanner</p>
                <div class="search-part">
                    <input class="form-control part-input input-search-part" title="Search Part">
                    <a class="btn btn-search-part" href="#"><i class="fa fa-search"></i></a>
                </div>
            </div>
            <div class="modal-body">
                <div class="selected-parts" style="display: block">
                    <div class="list-part"></div>
                    <div class="group-btn">
                        <a class="modal-btn btn-cancel" href="#">Cancel</a>
                        <a class="modal-btn btn-submit" href="#">Apply</a>
                    </div>
                </div>

                <div class="scan-list">
                    <p class="scan-text">Please Select a Part</p>
                    <div class="part-list-result"></div>
                    <div class="modal-footer">
                        <a class="modal-btn btn-rescan" href="#">Rescan</a>
                    </div>
                </div>

                <div class="no-result">
                    <p class="scan-text">No Parts Found: Add Part</p>
                    <div class="form-add-part">
                        <form class="new-part-form">
                            <input type="hidden" name="action" value="saveProduct">
                            <div class="form-group">
                                <label class="control-label" for="part-name">Part Name:</label>
                                <input class="form-control part-modal-input" name="name" placeholder="Part Name">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="part-number">OEM Number:</label>
                                <input class="form-control part-modal-input" placeholder="OEM Number" name="part_number">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="location">Location:</label>
                                <input class="form-control part-modal-input" placeholder="Location" name="location">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="vendor">Vendor:</label>
                                <input type="hidden" name="product_vari_id[]" />
                                <input type="hidden" class="vendor_id" name="vendor_id" />
                                <input class="form-control part-modal-input vender-input" name="vendor" placeholder="Vendor" >
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="part-cost">Cost:</label>
                                <input class="form-control part-modal-input" placeholder="Cost" name="average_cost">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="part-note">Notes:</label>
                                <textarea class="form-control part-modal-input" placeholder="Notes" name="notes"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="group-btn">
                        <a class="modal-btn btn-cancel" href="#">Cancel</a>
                        <a class="modal-btn btn-add-part" href="#">Add Part</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>