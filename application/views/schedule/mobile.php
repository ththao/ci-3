<div id="m-content" class="m-schedule">
	<input type="hidden" class="current_date" value="<?php echo date_by_timezone(time(), 'm-d-Y'); ?>" />
	<div class="m-title">
        <h1 class="m-title-head"><?= $this->lang->line('schedule') ?></h1>
    </div>
    <div class="m-head-input">
        <?= $this->lang->line('go_to_date') ?>: <input type="text" class="date-picker" value="<?= date_by_timezone(time(), 'm/d/Y') ?>" />
    </div>
    <div class="m-schedule-list">
        <ul class="m-schedule-list-provider" start="<?= date_by_timezone(strtotime('-1 days'), 'm/d/Y') ?>" end="<?= date_by_timezone(strtotime('+9 days'), 'm/d/Y') ?>">
            <?= $schedules_by_days ?>
        </ul>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            
        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModalNew" tabindex="-1" role="dialog" style="top: 40px;">
    <div class="modal-dialog" role="document">
        <div class="modal-content schedule-modal-content">
            <a class="close-modal" href="#" data-dismiss="modal"><i class="fa fa-times"></i></a>
            <div class="schedule-wrapper">

                <!--See Schedule tab-->
                <div class="schedule-container control-content open">
                    <div class="control">
                        <div class="m-schedule-date">
                            <div class="sc-month"></div>
                            <div class="sc-date">
                                <p class="sc-date-num"></p>
                                <p class="sc-day"></p>
                            </div>
                        </div>
                        <div class="navigation-content">
                            <h2 class="tab-title">Schedules</h2>
                            <div class="btn-direction-group">
                                <a class="nav-btn prev-btn" href="#">Previous</a>
                                <a class="nav-btn next-btn" href="#">Next</a>
                            </div>
                        </div>
                    </div>
                    <div id="seeSchedule" class="tab-content">

                    </div>
                </div>

                <!--Time Off tab-->
                <div class="time-off-container control-content">
                    <div class="control">
                        <div class="m-schedule-date">
                            <div class="sc-month"></div>
                            <div class="sc-date">
                                <p class="sc-date-num"></p>
                                <p class="sc-day"></p>
                            </div>
                        </div>
                        <div class="navigation-content">
                            <h2 class="tab-title">Time Off</h2>
                            <div class="btn-direction-group">
                                <a class="nav-btn prev-btn" href="#">Previous</a>
                                <a class="nav-btn next-btn" href="#">Next</a>
                            </div>
                        </div>
                    </div>
                    <div id="timeOff" class="tab-content">

                    </div>
                </div>

                <!--Notes tab-->
                <div class="notes-container control-content">
                    <div class="control">
                        <div class="m-schedule-date">
                            <div class="sc-month"></div>
                            <div class="sc-date">
                                <p class="sc-date-num"></p>
                                <p class="sc-day"></p>
                            </div>
                        </div>
                        <div class="navigation-content">
                            <h2 class="tab-title">Notes</h2>
                            <div class="btn-direction-group">
                                <a class="nav-btn prev-btn" href="#">Previous</a>
                                <a class="nav-btn next-btn" href="#">Next</a>
                            </div>
                        </div>
                    </div>
                    <div id="notes" class="tab-content">

                    </div>
                </div>

                <!--See Alerts tab-->
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
            <div class="scheduler-modal-footer">
                <a class="btn-control btn-see active" data-target="schedule-container">
                    <i class="fa fa-calendar"></i>
                    <p>Schedules</p>
                </a>
                <a class="btn-control btn-time-off" data-target="time-off-container">
                    <i class="fa fa-clock-o"></i>
                    <p>Time Off</p>
                </a>
                <a class="btn-control btn-note" data-target="notes-container">
                    <i class="fa fa-comments-o"></i>
                    <p>Notes</p>
                </a>
                <a class="btn-control btn-alert" data-target="alertRequests-container">
                    <span class="alert-num"></span>
                    <i class="fa fa-bell"></i>
                    <p>Alerts</p>
                </a>
            </div>
        </div>
    </div>
</div>