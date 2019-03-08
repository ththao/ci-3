<footer id="m-footer">
    <div class="menu-hambuger">
        <a href="#" id="open-menu"><i class="fa fa-bars" aria-hidden="true"></i></a>
    </div>
    <div class="back-to-home">
        <a class="back-home" href="/mobile/landing"><i class="fa fa-home"></i></a>
    </div>
    <div class="schedule-alert">
        <a href="#" class="ring-bell"><i class="fa fa-bell"></i></a>
        <p class="alert-count"></p>
    </div>
    <div class="sign-out"><a href="/mobile/logout/" title="<?= $this->lang->line('log_out') ?>"><i class="fa fa-sign-out" aria-hidden="true"></i></a></div>
</footer>

<div class="modal fade" id="footerAlertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content footer-alert-content">
            <a class="close-modal" href="#" data-dismiss="modal"><i class="fa fa-times"></i></a>
            <div class="modal-content-wrapper">
                <div class="alertRequests-container control-content">
                    <div class="control">
                        <div class="navigation-content">
                            <h2 class="tab-title">Alert</h2>
                        </div>
                    </div>
                    <div id="alertRequests" class="tab-content">
						
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="left-side-menu" class="left-side-menu">
    <ul class="menu">
        <li><a href="/mobile/landing" class="l-menu-item"><?= $this->lang->line('home') ?></a></li>
        <li><a href="/mobile/workboard" class="l-menu-item"><?= $this->lang->line('todays_jobs') ?></a></li>
        <li><a href="/schedule/mobile" class="l-menu-item"><?= $this->lang->line('schedule') ?></a></li>
        <li><a href="/mobile/time" class="l-menu-item"><?= $this->lang->line('clock_in_times') ?></a></li>
        <li><a href="/mobile/workorder" class="l-menu-item">Work Order</a></li>
        <li><a href="/mobile/safety" class="l-menu-item"><?= $this->lang->line('safety') ?></a></li>
        <li><a href="/mobile/settings" class="l-menu-item">Settings</a></li>
    </ul>
</div>

<script type="text/javascript" src="<?= auto_version('../../assets/js/jquery-3.1.1.min.js'); ?>"></script>
<script src="<?= auto_version('../../assets/js/bootstrap.min.js') ?>"></script>
<script language="javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBbCYByoNP5Y_tKz8yBZJ8kC-_AR48mzrU&sensor=true"></script>
<script src="<?= auto_version('../../assets/js/notify.min.js') ?>"></script>
<script src="<?= auto_version('../../assets/js/js.cookie.js') ?>"></script>
<script src="<?= auto_version('../../assets/js/alert.js') ?>"></script>