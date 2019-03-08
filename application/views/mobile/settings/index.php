<div id="m-content">
    <div id="setting-contents">
        <div class="mobile-settings-container">
            <!--Setting Mail and Cell Phone Number-->
            <div id="mail-phone" class="setting-item-wrapper open">
                <div class="mobile-title">
                    <h1>Settings</h1>
                </div>
                <div class="setting-content">
                    <form id="email-phone-form" class="form-horizontal" enctype="multipart/form-data">
                        <div class="form-group">
                            <input class="form-control setting-input settings-email" type="email" title="Email" placeholder="Email" value="<?php echo $worker->email; ?>">
                        </div>
                        <div class="form-group">
                            <input class="form-control setting-input settings-ecell" title="Cell Phone" placeholder="Cell Phone" value="<?php echo $worker->ecell; ?>">
                        </div>
                        <div class="form-group">
                            <div class="show-avatar" <?php echo $worker->worker_img ? 'style="display: block"' : 'style="display: none"'; ?>>
                                <a class="rmv-avatar" href="#"><i class="fa fa-times"></i></a>
                                <img class="user-avatar" src="<?php echo $worker->worker_img ? $this->config->item('asb_url') . '/memberdata/' . $worker->c_id . '/taskTracker/employees/' . $worker->worker_img : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <?php if ($worker->receive_text_alert): ?>
                                <label class="settings-receive-text" for="receive"><i class="fa fa-check-square-o" aria-hidden="true" style="font-size: 20px;"></i>  Receive Text Alerts</label>
                            <?php else: ?>
                                <label class="settings-receive-text" for="receive"><i class="fa fa-square-o" aria-hidden="true" style="font-size: 20px;"></i>  Receive Text Alerts</label>
                            <?php endif; ?>
                        </div>
                        <div class="setting-group-btn">
                            <button class="btn btn-setting btn-save-settings" type="submit">Save</button>
                            <a class="btn btn-setting btn-change" href="#">Change Username/Password</a>
                        </div>
                    </form>
                </div>
            </div>

            <!--Setting Password and Username-->
            <div id="username-pass" class="setting-item-wrapper">
                <div class="mobile-title">
                    <h1>Reset Username/Password</h1>
                </div>
                <div class="setting-content">
                    <form id="confirm-form" class="form-horizontal">
                        <div class="form-group">
                            <input class="form-control setting-input settings-current-password" type="password" title="Current Password" placeholder="Current Password">
                        </div>
                        <div class="setting-group-btn">
                            <button class="btn btn-setting btn-confirm-current-password" type="submit">Submit</button>
                        </div>
                    </form>
                    <form id="change-form" class="form-horizontal hide-form">
                        <div class="form-group">
                            <input class="form-control setting-input settings-new-username" title="Username" placeholder="Username" value="<?php echo $worker->username; ?>">
                            <div class="check-info check-info-username hide"></div>
                        </div>
                        <div class="form-group">
                            <input class="form-control setting-input settings-new-password" type="password" title="New Password" placeholder="New Password">
                            <div class="check-info check-info-password hide"></div>
                        </div>
                        <div class="form-group">
                            <input class="form-control setting-input settings-new-password2" type="password" title="Confirm New Password" placeholder="Confirm New Password">
                            <div class="check-info check-info-password2 hide"></div>
                            <p class="support-text">Password must be 6 characters long and have one Capital letter, one lower case letter and a number.</p>
                        </div>
                        <div class="form-group">
                            <div class="error-alert">
                                <p class="alert-mess hide username-taken">Username is taken</p>
                                <p class="alert-mess hide password-require">Password needs to meet the requirement</p>
                                <p class="alert-mess hide password-confirm">Passwords do not match</p>
                            </div>
                        </div>
                        <div class="setting-group-btn">
                            <button class="btn btn-setting btn-save-new-account" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>