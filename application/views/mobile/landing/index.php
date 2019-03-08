<div id="m-content">
    <div class="m-landing">
        <div class="landing-company">
            <p><?php echo $this->session->has_userdata('company_name') ? $this->session->userdata('company_name') : 'taskTracker'; ?></p>
        </div>
        <div class="row landing-content">
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/mobile/workboard" class="landing-link">
                    <i class="fa fa-clock-o fa-5x"></i><br>
                    <span>Time Clock</span>
                </a>
                <?php if ($clocked_in): ?>
                	<div class="landing-announcement">Clocked In</div>
                <?php endif; ?>
            </div>
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/schedule/mobile" class="landing-link">
                    <i class="fa fa-calendar fa-5x"></i><br>
                    <span><?= $this->lang->line('schedule') ?></span>
                </a>
            </div>
            <div class="clearfix"></div>
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/mobile/time" class="landing-link">
                    <i class="fa fa-history fa-5x"></i><br>
                    <span><?= $this->lang->line('past_times') ?></span>
                </a>
            </div>
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/mobile/workorder" class="landing-link">
                    <i class="fa fa-pencil-square-o fa-5x"></i><br>
                    <span>Work Orders</span>
                </a>
                <?php if ($wo_cnt): ?>
                	<div class="landing-announcement"><?php echo $wo_cnt; ?></div>
                <?php endif; ?>
            </div>
            <div class="clearfix"></div>
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/mobile/safety" class="landing-link">
                    <i class="fa fa-shield fa-5x"></i><br>
                    <span><?= $this->lang->line('safety') ?></span>
                </a>
            </div>
            <div class="col-xs-6 col-sm-4 landing-item">
                <a href="/mobile/settings" class="landing-link">
                    <i class="fa fa-user-circle fa-5x"></i><br>
                    <span>Profile</span>
                </a>
            </div>
        </div>
    </div>
</div>