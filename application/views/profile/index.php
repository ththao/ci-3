<!-- Left Sidebar-->
<div id="main" class="container-fluid main-content pad0">
    <div class="row mar0">

        <!-- Left Sidebar-->
        <?php $this->load->view("workboard/partials/sidebar"); ?>


        <div class="col-xs-12 col-sm-8 col-md-9 col-lg-10 content new-layout new-profile">
            <h2 class="d-title-content-right" align="center">Profile</h2>
            <div id="profile" class="container main-content pad0 main-profile"><br/>

                <?= form_open('/profile', ['class' => 'form-horizontal']); ?>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="col-md-4 col-xs-5 text-right">
                            <label><?= $this->lang->line('first_name') ?></label>
                        </div>
                        <div class="col-md-4 col-xs-7">
                            <?= form_input([
                                'name' => 'first_name',
                                'id' => 'first_name',
                                'value' => $worker->first_name,
                                'class' => 'form-control',
                                'placeholder' => $this->lang->line('first_name')
                            ]); ?>
                            <?= form_error('first_name', '<div class="error">', '</div>'); ?>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="col-md-4 col-xs-5 text-right">
                            <label><?= $this->lang->line('last_name') ?></label>
                        </div>
                        <div class="col-md-4 col-xs-7">
                            <?= form_input([
                                'name' => 'last_name',
                                'id' => 'last_name',
                                'value' => $worker->last_name,
                                'class' => 'form-control',
                                'placeholder' => $this->lang->line('last_name')
                            ]); ?>
                            <?= form_error('last_name', '<div class="error">', '</div>'); ?>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="col-md-4 col-xs-5 text-right">
                            <label><?= $this->lang->line('email') ?></label>
                        </div>
                        <div class="col-md-4 col-xs-7">
                            <?= form_input([
                                'name' => 'email',
                                'id' => 'email',
                                'value' => $worker->email,
                                'class' => 'form-control',
                                'placeholder' => $this->lang->line('email')
                            ]); ?>
                            <?= form_error('email', '<div class="error">', '</div>'); ?>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="col-md-4 col-xs-5 text-right">
                            <label><?= $this->lang->line('home_phone') ?></label>
                        </div>
                        <div class="col-md-4 col-xs-7">
                            <?= form_input([
                                'name' => 'ephone',
                                'id' => 'ephone',
                                'value' => $worker->ephone,
                                'class' => 'form-control',
                                'placeholder' => $this->lang->line('home_phone')
                            ]); ?>
                            <?= form_error('ephone', '<div class="error">', '</div>'); ?>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="col-md-4 col-xs-5 text-right">
                            <label><?= $this->lang->line('mobile_phone') ?></label>
                        </div>
                        <div class="col-md-4 col-xs-7">
                            <?= form_input([
                                'name' => 'ecell',
                                'id' => 'ecell',
                                'value' => $worker->ecell,
                                'class' => 'form-control',
                                'placeholder' => $this->lang->line('mobile_phone')
                            ]); ?>
                            <?= form_error('ecell', '<div class="error">', '</div>'); ?>
                        </div>
                    </div>
                </div>
                <?php if ($this->session->has_userdata('mobile') && $this->session->userdata('mobile') == '1') { ?>
                    <div class="row form-group">
                        <div class="col-xs-12">
                            <div class="col-md-4 col-xs-5 text-right">
                                <label><?= $this->lang->line('username') ?></label>
                            </div>
                            <div class="col-md-4 col-xs-7">
                                <?= form_input([
                                    'name' => 'username',
                                    'id' => 'username',
                                    'value' => $worker->username,
                                    'class' => 'form-control',
                                    'placeholder' => $this->lang->line('username')
                                ]); ?>
                                <?= form_error('username', '<div class="error username-error">', '</div>'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-xs-12">
                            <div class="col-md-4 col-xs-5 text-right">
                                <label><?= $this->lang->line('password') ?></label>
                            </div>
                            <div class="col-md-4 col-xs-7">
                                <?= form_password([
                                    'name' => 'password',
                                    'id' => 'password',
                                    'class' => 'form-control',
                                    'placeholder' => $this->lang->line('password')
                                ]); ?>
                                <?= form_error('password', '<div class="error">', '</div>'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-xs-12">
                            <div class="col-md-4 col-xs-5 text-right">
                                <label><?= $this->lang->line('new') ?> <?= $this->lang->line('password') ?></label>
                            </div>
                            <div class="col-md-4 col-xs-7">
                                <?= form_password([
                                    'name' => 'new_password',
                                    'id' => 'new_password',
                                    'class' => 'form-control',
                                    'placeholder' => $this->lang->line('new') . ' ' . $this->lang->line('password')
                                ]); ?>
                                <?= form_error('new_password', '<div class="error">', '</div>'); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="row form-group">
                    <div class="col-xs-12">
                        <div class="text-right button-confirm">
                            <a href="/workboard"
                               class="btn btn-danger"><?= $this->lang->line('back_to_workboard') ?></a>
                            <button type="submit" class="btn btn-success"><?= $this->lang->line('save') ?></button>
                        </div>
                    </div>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
