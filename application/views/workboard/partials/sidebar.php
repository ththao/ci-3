<div class="col-xs-12 col-sm-4 col-md-3 col-lg-2 employee-menu">
    <div class="sidebar-menu">
        <h1 class="welcome-user">Welcome,<br><?= $this->session->userdata('worker_name') ?></h1>
        <div class="list-menu">
            <div class="sidebar-menu-item sidebar-menu-item-workboard <?php echo $page == 'workboard' ? 'active' : ''; ?>">
                <p class="sidebar-name">Time Clock</p>
                <a class="sidebar-icon" href="/workboard">
                    <i class="far fa-clock"></i>
                </a>
                <?php if ($clocked_in): ?>
                <p class="sidebar-status red-alert sidebar-clocked-in">In</p>
                <?php endif; ?>
            </div>
            <div class="sidebar-menu-item <?php echo $page == 'schedule' ? 'active' : ''; ?>">
                <p class="sidebar-name">Schedule</p>
                <a class="sidebar-icon" href="/schedule">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            </div>
            <div class="sidebar-menu-item <?php echo $page == 'time' ? 'active' : ''; ?>">
                <p class="sidebar-name">Past Time</p>
                <a class="sidebar-icon" href="/time">
                    <i class="fas fa-history"></i>
                </a>
            </div>
            <div class="sidebar-menu-item <?php echo $page == 'workorder' ? 'active' : ''; ?>">
                <p class="sidebar-name">Work Orders</p>
                <a class="sidebar-icon" href="/workorder">
                    <i class="far fa-edit"></i>
                </a>
                <?php if ($wo_cnt): ?>
                <p class="sidebar-status red-alert"><?php echo $wo_cnt; ?></p>
                <?php endif; ?>
            </div>
            <div class="sidebar-menu-item <?php echo $page == 'safety' ? 'active' : ''; ?>">
                <p class="sidebar-name">Safety</p>
                <a class="sidebar-icon" href="/safety">
                    <i class="fas fa-shield-alt"></i>
                </a>
            </div>
            <div class="sidebar-menu-item <?php echo $page == 'profile' ? 'active' : ''; ?>">
                <p class="sidebar-name">Profile</p>
                <a class="sidebar-icon" href="/profile">
                    <i class="fas fa-user-circle"></i>
                </a>
            </div>
            <div class="sidebar-menu-item">
                <p class="sidebar-name">Alert</p>
                <a class="sidebar-icon" href="/schedule">
                    <i class="far fa-bell"></i>
                </a>
                <p class="sidebar-status red-alert hide"></p>
            </div>
        </div>
    </div>
</div>