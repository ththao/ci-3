<ul class="mow-list">
	<?php if ($mows) { ?>
        <?php foreach ($mows as $mow) { ?>
            <?php
                if (!$mow->clock_ring_path) {
                    continue;
                }
                $title = $mow->mp_cat == 1 ? 'greens' : ($mow->mp_cat == 2 ? 'fairways' : ($mow->mp_cat == 3 ? 'tees' : 'approaches'));
            ?>
            <li class="mow-item">
                <div class="mow-clock">
                    <img src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->clock_ring_path ?>" width="35">
                    <img class="mow-between" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->clock_face_path ?>">
                    <?php if ($mow->rotation_img_path) { ?>
    					<img class="rotation" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->rotation_img_path ?>">
    				<?php } ?>
                </div>
                <p><?= $this->lang->line($title) ?></p>
            </li>
        <?php } ?>
    <?php } ?>
</ul>