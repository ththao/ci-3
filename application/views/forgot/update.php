<div class="banner login-banner">
    <a href="#">
        <img src="<?php echo asset('img')?>/logo.png" width="70">
        <span>Advanced Scoreboards LLC</span>
    </a>
</div>
<div id="login-page">
    <div class="login-title">
        <h1>Employee Forgot Password</h1>
    </div>
    <div class="login-form">
        <?= form_open('/forgot/update/' . $hash, ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
        		<?= form_label('Please Enter Your Password', 'username', ['class' => 'control-label login-label']); ?>
                <?= form_password([
                    'name'        => 'password',
                    'id'          => 'password',
                    'class'       => 'form-control',
                    'placeholder' => 'Password',
                    'style'       => 'margin-top: 20px;'
                ]); ?>
                <?= form_error('password', '<div class="error">', '</div>'); ?>
                <?= form_password([
                    'name'        => 'c_password',
                    'id'          => 'c_password',
                    'class'       => 'form-control',
                    'placeholder' => 'Confirm Password',
                    'style'       => 'margin-top: 20px;'
                ]); ?>
                <?= form_error('c_password', '<div class="error">', '</div>'); ?>
            </div>
            
            <div class="login-button">
            	<?= form_submit('submit', 'Submit', ['class' => 'btn-success btn-login']) ?></br>
            	<a href="/mobile">Login</a>
            </div>
        <?= form_close(); ?>
    </div>
</div>