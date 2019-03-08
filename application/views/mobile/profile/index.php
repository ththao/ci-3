<div id="profile" class="container main-content pad0">
	<h2 align="center">Profile</h2>
	<br/>
	
	<?= form_open('/profile', ['class' => 'form-horizontal']); ?>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('first_name') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'first_name',
                        'id'          => 'first_name',
            		    'value'       => $worker->first_name,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('first_name')
                    ]); ?>
                    <?= form_error('first_name', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('last_name') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'last_name',
                        'id'          => 'last_name',
            		    'value'       => $worker->last_name,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('last_name')
                    ]); ?>
                    <?= form_error('last_name', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('email') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'email',
                        'id'          => 'email',
            		    'value'       => $worker->email,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('email')
                    ]); ?>
                    <?= form_error('email', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('home_phone') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'ephone',
                        'id'          => 'ephone',
            		    'value'       => $worker->ephone,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('home_phone')
                    ]); ?>
                    <?= form_error('ephone', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('mobile_phone') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'ecell',
                        'id'          => 'ecell',
            		    'value'       => $worker->ecell,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('mobile_phone')
                    ]); ?>
                    <?= form_error('ecell', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
        <?php if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') { ?>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('username') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'        => 'username',
                        'id'          => 'username',
            		    'value'       => $worker->username,
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('username')
                    ]); ?>
                    <?= form_error('username', '<div class="error username-error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('password') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_password([
                        'name'        => 'password',
                        'id'          => 'password',
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('password')
                    ]); ?>
                    <?= form_error('password', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
    	<div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-4 text-right">
                	<label><?= $this->lang->line('new') ?> <?= $this->lang->line('password') ?></label>
                </div>
                <div class="col-xs-4">
                    <?= form_password([
                        'name'        => 'new_password',
                        'id'          => 'new_password',
                        'class'       => 'form-control',
                        'placeholder' => $this->lang->line('new') . ' ' . $this->lang->line('password')
                    ]); ?>
                    <?= form_error('new_password', '<div class="error">', '</div>'); ?>
                </div>
            </div>
        </div>
        <?php } ?>
        <div class="row form-group">
        	<div class="col-xs-12">
            	<div class="col-xs-8 text-right">
                    <a href="/workboard" class="btn btn-danger"><?= $this->lang->line('back_to_workboard') ?></a>
                    <button type="submit" class="btn btn-success"><?= $this->lang->line('save') ?></button>
                </div>
            </div>
        </div>
    <?= form_close(); ?>
</div>