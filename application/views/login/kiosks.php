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
        <?= form_open('login/kiosks', ['class' => 'form-horizontal']); ?>
        	<div class="form-group mar0">
        		<?= form_label('Please Choose Your Kiosk', 'username', ['class' => 'control-label login-label']); ?>
        		<?= form_dropdown([
                    'name'        => 'url_id',
                    'id'          => 'url_id',
        		    'options'     => $kiosks,
                    'class'       => 'form-control',
                ]); ?>
            </div>
            
            <div class="login-button">
            	<?= form_submit('submit', $this->lang->line('ok'), ['class' => 'btn-success btn-login']) ?>
            </div>
        <?= form_close(); ?>
    </div>
</div>