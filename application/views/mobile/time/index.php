<div id="m-content" class="m-time">
    <div class="m-title">
        <h1 class="m-title-head"><?= $this->lang->line('clock_in_times') ?></h1>
    </div>
    <div class="m-head-input">
        <?= $this->lang->line('go_to_date') ?>: <input type="text" class="date-picker" value="<?= date_by_timezone(time(), 'm/d/Y') ?>" />
    </div>
    <div class="m-time-list">
        <ul class="m-time-list-provider" start="<?= date_by_timezone(strtotime('-3 days'), 'm/d/Y') ?>" end="<?= date_by_timezone(strtotime('+1 days'), 'm/d/Y') ?>">
            <?= $times_by_days ?>
        </ul>
    </div>
</div>