<div class="banner login-banner">
    <a href="#">
        <img src="<?php echo asset('img')?>/ttk_logo.png" width="120">
    </a>
</div>
<div id="login-page">
    <div class="login-title">
        <h1><?= isset($company_name) ? $company_name : '' ?></h1>
        <h1><?= $this->lang->line('employee') ?> <?= $this->lang->line('log_in') ?></h1>
    </div>
    <div class="login-form">
        <?= form_open('login/employee?url_id=' . $url_id, ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
        		<?= form_label($kiosk_name, '', ['class' => 'control-label login-label']); ?>
                <?= form_label('Please Enter Your Log In Code', 'code', ['class' => 'control-label login-label']); ?>
                <?= form_password([
                    'name'        => 'code',
                    'id'          => 'code',
                    'class'       => 'form-control',
                    'placeholder' => 'PIN'
                ]); ?>
                <?= form_error('code', '<div class="error">', '</div>'); ?>
            </div>

            <div class="login-numpad">
                <a class="numpad-item" href="#" data-value="1">1</a>
                <a class="numpad-item" href="#" data-value="2">2</a>
                <a class="numpad-item" href="#" data-value="3">3</a>
                <a class="numpad-item" href="#" data-value="4">4</a>
                <a class="numpad-item" href="#" data-value="5">5</a>
                <a class="numpad-item" href="#" data-value="6">6</a>
                <a class="numpad-item" href="#" data-value="7">7</a>
                <a class="numpad-item" href="#" data-value="8">8</a>
                <a class="numpad-item" href="#" data-value="9">9</a>
                <a class="numpad-item clear-input" href="#" data-value="clr">Clear</a>
                <a class="numpad-item" href="#" data-value="0">0</a>
                <a class="numpad-item" href="#" data-value="delete"><i class="fas fa-backspace"></i></a>
            </div>

            <div class="login-button">
            	<?= form_submit('submit', $this->lang->line('log_in'), ['class' => 'btn-success btn-login']) ?>
            </div>
        <?= form_close(); ?>
    </div>
</div>