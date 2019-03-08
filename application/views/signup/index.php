<div class="banner login-banner m-login-banner">
    <a href="#">
        <img src="<?php echo asset('img')?>/logo.png" width="50">
        <span>Advanced Scoreboards LLC</span>
    </a>
</div>
<div id="m-login-page">
    <div class="m-login-title">
        <h1 class="signup-title"><?= $this->lang->line('employee') ?> <?= $this->lang->line('sign_up') ?></h1>
    </div>
    <div class="m-login-form">
        <?= form_open('/signup/' . $hash, ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
                <p class="control-label login-label signup-info">Please Enter Your Information</p>
                <?= form_label('First Name', 'first_name', ['class' => 'control-label']); ?>
        		<?= form_input([
                    'name'        => 'first_name',
                    'id'          => 'first_name',
        		    'value'       => set_value('first_name') == false ? $worker->first_name: set_value('first_name'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('first_name')
                ]); ?>
                <?= form_error('first_name', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Last Name', 'last_name', ['class' => 'control-label']); ?>
                <?= form_input([
                    'name'        => 'last_name',
                    'id'          => 'last_name',
                    'value'       => set_value('last_name') == false ? $worker->last_name: set_value('last_name'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('last_name')
                ]); ?>
                <?= form_error('last_name', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Email', 'email', ['class' => 'control-label']); ?>
                <?= form_input([
                    'name'        => 'email',
                    'id'          => 'email',
                    'value'       => set_value('email') == false ? $worker->email: set_value('email'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('email')
                ]); ?>
                <?= form_error('email', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Home Phone', 'ephone', ['class' => 'control-label']); ?>
                <?= form_input([
                    'name'        => 'ephone',
                    'id'          => 'ephone',
                    'value'       => set_value('ephone') == false ? $worker->ephone: set_value('ephone'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('home_phone')
                ]); ?>
                <?= form_error('ephone', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Mobile Phone', 'ecell', ['class' => 'control-label']); ?>
                <?= form_input([
                    'name'        => 'ecell',
                    'id'          => 'ecell',
                    'value'       => set_value('ecell') == false ? $worker->ecell: set_value('ecell'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('mobile_phone')
                ]); ?>
                <?= form_error('ecell', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Username', 'username', ['class' => 'control-label']); ?>
                <?= form_input([
                    'name'        => 'username',
                    'id'          => 'username',
                    'value'       => set_value('username') == false ? $worker->username: set_value('username'),
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('username'),
                    'signup_hash' => $hash
                ]); ?>
                <?= form_error('username', '<div class="error username-error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Password', 'password', ['class' => 'control-label']); ?>
                <?= form_password([
                    'name'        => 'password',
                    'id'          => 'password',
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('password')
                ]); ?>
                <div align="left">Hint: Password must be between 6 and 20 characters long, with at least one lowercase letter, one uppercase letter, and one number.</div>
                <?php //echo form_error('password', '<div class="error">', '</div>'); ?>
                <?= form_label('Confirm Password', 'c_password', ['class' => 'control-label']); ?>
                <?= form_password([
                    'name'        => 'c_password',
                    'id'          => 'c_password',
                    'class'       => 'form-control',
                    'placeholder' => $this->lang->line('confirm') . ' ' . $this->lang->line('password')
                ]); ?>
                <?= form_error('c_password', '<div class="error">', '</div>'); ?>
            </div>
            
            <div class="m-login-button">
            	<?= form_submit('submit', $this->lang->line('submit'), ['class' => 'btn-success btn-login m-btn-login']) ?>
            </div>
        <?= form_close(); ?>
    </div>
</div>