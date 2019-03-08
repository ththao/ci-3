<div class="banner login-banner m-login-banner">
    <a href="#">
        <img src="<?php echo asset('img')?>/logo.png" width="50">
        <span>Advanced Scoreboards LLC</span>
    </a>
</div>
<div id="m-login-page">
    <div class="m-login-title">
        <h1><?= $this->lang->line('employee') ?> <?= $this->lang->line('mobile') ?></h1>
    </div>
    <div class="m-login-form">
    	<?php if (isset($signup_message)): ?>
        <div class="form-group mar0">
        	<div class="alert alert-success"><?php echo $signup_message; ?></div>
        </div>
        <?php endif; ?>
        <?= form_open('/mobile', ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
        		<?= form_label('Username', 'username', ['class' => 'control-label']); ?>
        		<?= form_input([
                    'name'        => 'username',
                    'id'          => 'username',
        		    'value'       => set_value('username'),
                    'class'       => 'form-control m-input',
                    'placeholder' => $this->lang->line('username') . ' or ' . $this->lang->line('email')
                ]); ?>
                <?= form_error('username', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="margin: 15px 0 0;">
                <?= form_label('Password', 'password', ['class' => 'control-label']); ?>
                <?= form_password([
                    'name'        => 'password',
                    'id'          => 'password',
                    'class'       => 'form-control m-input',
                    'placeholder' => $this->lang->line('password')
                ]); ?>
                <?= form_error('password', '<div class="error">', '</div>'); ?>
            </div>
            <div class="form-group" style="text-align: left; margin: 15px 0 0;">
                <?= form_checkbox([
                    'name'        => 'remember',
                    'id'          => 'remember',
                    'class'       => 'form-control m-checkbox',
                    'checked'     => true,
                    'value'       => 1,
                ]); ?>
                <?= form_label('Remember Me', 'remember', ['class' => 'control-label remember-me']); ?>
            </div>
            
            <div class="m-login-button">
            	<?= form_submit('submit', $this->lang->line('log_in'), ['class' => 'btn-success btn-login m-btn-login']) ?><br/>
            	<a class="m-forgot" href="/forgot"><?= $this->lang->line('forgot_password') ?></a>
            </div>
        <?= form_close(); ?>
    </div>
</div>