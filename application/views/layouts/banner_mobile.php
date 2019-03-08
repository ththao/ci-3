<header id="m-header">
    <div class="m-logo">
        <img src="<?= asset("img/logo2.png")?>" class="img-responsive">
    </div>
    <div class="m-emp-name">
        <p class="emp-name"><?= $this->session->userdata('worker_name') ?></p>
    </div>
    <div class="m-date">
        <p><?= date_by_timezone(time(), 'l') ?></p>
        <p><?= date_by_timezone(time(), 'm/d/Y') ?></p>
    </div>
</header>