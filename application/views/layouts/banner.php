<div class="banner">
    <div class="container-fluid">
        <div class="row banner-content">
            <div class="col-xs-4">
                <a href="/workboard" class="banner-logo">
                    <img src="<?= asset("img/ttk_logo.png")?>" width="120">
                </a>
            </div>
            
            <div class="col-sm-4" align="center">
            	<label class="kiosk-name">
            	   <?= $this->session->has_userdata('kiosk_name') ? $this->session->userdata('kiosk_name') : '' ?>
            	</label>
            </div>
            
            <div class="col-xs-8 col-sm-4">
    			<a href="/logout" class="btn btn-warning btn-logout pull-right">Log Out</a>
			</div>
        </div>
    </div>
</div>