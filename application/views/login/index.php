<div class="banner login-banner">
    <a href="#">
        <img src="<?php echo asset('img')?>/ttk_logo.png" width="120">
    </a>
</div>
<div id="login-page">
    <div class="login-title">
        <h1><?= isset($company_name) ? $company_name : '' ?></h1>
        <h1><?= $this->lang->line('') ?> <?= $this->lang->line('') ?>Kiosk Authentication</h1>
    </div>
    <div class="login-form">
        <?= form_open($url_id ? '/login?url_id=' . $url_id : '/login', ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
                <div class="username-div">
                    <?= form_label('User Name', 'username', ['class' => 'control-label login-label']); ?>
                    <?= form_input([
                        'name'        => 'username',
                        'id'          => 'username',
                        'value'       => set_value('username'),
                        'class'       => 'form-control login-control',
                        'placeholder' => $this->lang->line('username')
                    ]); ?>
                    <?= form_error('username', '<div class="error">', '</div>'); ?>
                </div>
        		<div class="password-div">
                    <?= form_label('Password', 'password', ['class' => 'control-label login-label']); ?>
                    <?= form_password([
                        'name'        => 'password',
                        'id'          => 'password',
                        'class'       => 'form-control login-control',
                        'placeholder' => 'Password',
                    ]); ?>
                    <?= form_error('password', '<div class="error">', '</div>'); ?>
                </div>
                </div>
            <div class="login-button">
            	<?= form_submit('submit', $this->lang->line('log_in'), ['class' => 'btn-success btn-login']) ?>
            </div>
        <?= form_close(); ?>
    </div>
    <div class="login-warning">
        <h5 class="lw-text">Please enter your taskTracker administrative user name and password to authenticate the use of this kiosk on this device.</h5>
    </div>
</div>