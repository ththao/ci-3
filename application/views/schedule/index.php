<!-- Left Sidebar-->
<div id="main" class="container-fluid main-content pad0">
    <div class="row mar0">

        <!-- Left Sidebar-->
        <?php $this->load->view("workboard/partials/sidebar");?>

        <!--Main Content-->
        <div class="col-xs-12 col-sm-8 col-md-9 col-lg-10 content new-layout">
            <div id="m-content d-content" class="m-schedule">
                <input type="hidden" class="current_date" value="<?php echo date_by_timezone(time(), 'm-d-Y'); ?>" />
                <div class="m-title d-title">
                    <h1 class="m-title-head d-title-head"><?= $this->lang->line('schedule') ?></h1>
                </div>

                <div class="m-schedule-list d-schedule-list">
                    <ul class="m-schedule-list-provider" start="<?= date_by_timezone(strtotime('-1 days'), 'm/d/Y') ?>" end="<?= date_by_timezone(strtotime('+9 days'), 'm/d/Y') ?>">
                        <?= $schedules_by_days ?>
                    </ul>
                </div>
                <!--Schedule More Info-->
                <div id="moreInfo">
                    <div class="schedule-modal-content">
                        <div class="schedule-more-info">
                            <a class="close-info" href="#"><i class="fa fa-times"></i></a>
                            <div class="schedule-wrapper">

                                <!--See Schedule tab-->
                                <div class="schedule-container control-content open">
                                    <div class="control">
                                        <div class="m-schedule-date">
                                            <div class="sc-month">January</div>
                                            <div class="sc-date">
                                                <p class="sc-date-num">1</p>
                                                <p class="sc-day">Monday</p>
                                            </div>
                                        </div>
                                        <div class="navigation-content">
                                            <h2 class="tab-title">Scheduled Employee</h2>
                                            <div class="btn-direction-group">
                                                <a class="nav-btn prev-btn" href="#"><i class="fas fa-chevron-left"></i></a>
                                                <span>Change Date</span>
                                                <a class="nav-btn next-btn" href="#"><i class="fas fa-chevron-right"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="seeSchedule" class="tab-content">
                                        <div class="schedule-emp-list">
                                            <div class="schedule-emp">
                                                <p class="emp-name" style="background: linear-gradient(to right, #c2c2c2 0px, #fff 30px)">Gerald Flaherty</p>
                                                <p class="emp-time"><span>1:00am - 6:00am</span></p>
                                            </div>
                                            <div class="schedule-emp">
                                                <p class="emp-name" style="background: linear-gradient(to right, #c2c2c2 0px, #fff 30px)">Gerald Flaherty</p>
                                                <p class="emp-time"><span>1:00am - 6:00am</span></p>
                                            </div>
                                        </div>
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
                                                <a class="nav-btn prev-btn" href="#"><i class="fas fa-chevron-left"></i></a>
                                                <span>Change Date</span>
                                                <a class="nav-btn next-btn" href="#"><i class="fas fa-chevron-right"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="timeOff" class="tab-content">
                                        <!--function renderOffRequest-->
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
                                                <a class="nav-btn prev-btn" href="#"><i class="fas fa-chevron-left"></i></a>
                                                <span>Change Date</span>
                                                <a class="nav-btn next-btn" href="#"><i class="fas fa-chevron-right"></i></a>
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
                                <a class="btn-control btn-see active" href="#" data-target="schedule-container">
                                    <i class="fas fa-calendar-alt"></i>
                                    <p>Schedules</p>
                                </a>
                                <a class="btn-control btn-time-off" href="#" data-target="time-off-container">
                                    <i class="far fa-clock"></i>
                                    <p>Time Off</p>
                                </a>
                                <a class="btn-control btn-note" href="#" data-target="notes-container">
                                    <i class="far fa-comments"></i>
                                    <p>Notes</p>
                                </a>
                                <a class="btn-control btn-alert" href="#" data-target="alertRequests-container">
                                    <span class="alert-num">1</span>
                                    <i class="far fa-bell"></i>
                                    <p>Alerts</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            
        </div>
    </div>
</div>