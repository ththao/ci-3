<div class="dials">
	<?php if ($mows) { ?>
        <?php foreach ($mows as $mow) { ?>
        <?php
            if (!$mow->clock_ring_path) {
                continue;
            }
            $title = $mow->mp_cat == 1 ? 'greens' : ($mow->mp_cat == 2 ? 'fairways' : ($mow->mp_cat == 3 ? 'tees' : 'approaches'));
        ?>
            <div class="clockface" id="<?= $title ?>">
                <div class="typeTitle"><?= $this->lang->line($title) ?></div>
                <div class="dialImages">
                    <img class="clock" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->clock_ring_path ?>" style="visibility: visible;">
                    <img class="clockDirection" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->clock_face_path ?>" style="visibility: visible;">
        			<?php if ($mow->rotation_img_path) { ?>
    					<img class="rotation" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->rotation_img_path ?>" style="visibility: visible;">
    				<?php } ?>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
</div>
<div class="hole-area">
    <img src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/basehole72.png" class="img-responsive hole-img">
	<?php if ($mows) { ?>
    	<?php foreach ($mows as $mow) { ?>
        	<?php $class = $mow->mp_cat == 1 ? 'green' : ($mow->mp_cat == 2 ? 'fairway' : ($mow->mp_cat == 3 ? 'tees' : 'approach')); ?>
    		<img class="<?= $class ?>Striping" src="<?= $this->config->item('asb_url') ?>/tasktracker/images/holediagram/<?= $mow->img_path ?>">
    	<?php } ?>
    <?php } ?>
</div>