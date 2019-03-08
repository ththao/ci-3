<div id="main" class="settings container main-content pad0">
	<h2 align="center"><?= $this->lang->line('settings') ?></h2>
	<br/>

    <!-- Left Sidebar-->
    <?php $this->load->view("workboard/partials/sidebar");?>
	
	<form class="form-horizontal" method="post">
        <div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('time_format') ?></label>
                </div>
                <div class="col-xs-4">
                    <select name="timeformat" class="form-control">
                    	<option value="1" <?= $user_timeformat == 1 ? 'selected' : '' ?>>American Time</option>
                    	<option value="2" <?= $user_timeformat == 2 ? 'selected' : '' ?>>Military Time</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('timezone') ?></label>
                </div>
                <div class="col-xs-4">
                    <select name="timezone" class="form-control">
                    	<?php foreach ($timezones as $timezone): ?>
                    		<option value="<?= $timezone->time_zone ?>" <?= $timezone->time_zone == $user_timezone ? 'selected' : '' ?>>
                    			<?= $timezone->time_zone ?>
                    		</option>
                    	<?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        
        <div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-8 text-right">
                    <a href="/workboard" class="btn btn-danger"><?= $this->lang->line('back_to_workboard') ?></a>
                    <button type="submit" class="btn btn-success"><?= $this->lang->line('save') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>