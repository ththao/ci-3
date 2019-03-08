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
        <?= form_open('/forgot', ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
        		<?= form_label('Please Enter Your Email', 'email', ['class' => 'control-label login-label']); ?>
        		
                <?= form_input([
                    'name'        => 'email',
                    'id'          => 'email',
        		    'value'       => set_value('username'),
                    'class'       => 'form-control',
                    'placeholder' => 'Email',
                    'style'       => 'margin-top: 20px;'
                ]); ?>
                <?= form_error('email', '<div class="error">', '</div>'); ?>
            </div>
            
            <div class="login-button">
            	<?= form_submit('submit', 'Submit', ['class' => 'btn-success btn-login']) ?><br/>
            	<a href="/mobile">Login</a>
            </div>
        <?= form_close(); ?>
    </div>
</div>