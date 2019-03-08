<div id="main" class="container-fluid main-content pad0">
    <div class="row mar0">

        <!-- Left Sidebar-->
        <?php $this->load->view("workboard/partials/sidebar");?>

        <!--Main Content-->
        <div class="col-xs-12 col-sm-8 col-md-9 col-lg-10 content new-layout">
            <div id="m-content" class="m-workorder m-create-wo">
                <form class="create-wo-form">
                    <div class="add-wo-header">
                        <a class="back-to-home" href="/workorder"><i class="fa fa-arrow-left"></i> Back</a>
                        Work Orders
                        <a class="save-new-wo" mobile="0" href="#"><i class="fa fa-save"></i> Save</a>
                    </div>
                    <div class="add-wo-body">
                        <div class="form-add-wo">
                            <div class="form-group">
                                <label class="control-label" for="wo-name">Work Order Name:</label>
                                <input class="form-control add-wo-input" id="wo-name" name="wo_name" placeholder="Work Order Name">
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="wo-desc">Description:</label>
                                <textarea class="form-control add-wo-textarea" id="wo-desc" name="description"></textarea>
                            </div>
                            <div class="equipment-area">
                                <a class="add-equipment-btn" href="#"><i class="fa fa-plus"></i> Equip.</a>
                                <div class="equip-added-list"></div>
                            </div>

                            <div class="add-job-area">
                                <a class="add-job-btn" href="#"><i class="fa fa-plus"></i> Jobs</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addEqmModal" tabindex="-1" role="dialog" aria-labelledby="equipmentModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Equipment</h4>
            </div>
            <div class="modal-body">
                <div class="equipment-search">
                    <input type="text" class="equipment-search-text form-control" />
                    <a class="equipment-search-btn" href="#"><i class="fa fa-search"></i></a>
                </div>
                <div class="all-list-equipment">
            		<?php if ($equipment_types) { ?>
            			<?php $status_classes = [1 => 'status-ready', 2 => 'status-issue', 3 => 'status-error']; ?>
            			<?php foreach ($equipment_types as $equipment_type_id => $equipment_type) { ?>
            				<div class="list-equipment-item list-equipment-item-<?php echo $equipment_type_id; ?>">
                				<div class="list-eqm-header">
                                    <a class="list-eqm-name" href="#"><?php echo $equipment_type['type_name']; ?></a>
                                </div>
                                <div class="list-eqm-body">
                                    <div class="list-eqm-wrapper">
                                    	<?php if ($equipments = $equipment_type['equipments']) { ?>
                                    		<?php foreach ($equipments as $equipment) { ?>
                                                <div class="eqm-item eqm-item-<?php echo $equipment['equipment_id']; ?>">
                                                    <input type="hidden" class="equipment_id" value="<?php echo $equipment['equipment_id']; ?>">
                                                    <div class="eqm-status <?php echo $status_classes[$equipment['status']]; ?>"></div>
                                                    <a class="eqm-info" href="#">
                                                        <p class="eqm-name"><?php echo $equipment['equipment_name']; ?></p>
                                                        <p class="eqm-short-desc"><?php echo $equipment['short_description']; ?></p>
                                                    </a>
                                                    <a class="eqm-more-info <?php echo $equipment['status_note'] ? '' : 'hide'; ?>" href="#">
                                                    	<i class="fa fa-info-circle"></i>
                                                    </a>
                                                    <div class="more-info-popup">
                                                        <div class="more-info-content">
                                                            <p class="info-text"><?php echo $equipment['status_note']; ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                        	<?php } ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
            			<?php } ?>
            		<?php } ?>
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