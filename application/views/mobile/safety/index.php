<div id="m-content" class="m-safety">
    <div class="m-title">
        <h1 class="m-title-head">Safety</h1>
    </div>
    <div class="m-safety-wrapper-item">
        <div class="m-safety-header safety-active">
            Active Safety and Training
        </div>
        <div class="m-safety-body safety-active-list" total-count="<?php echo $active_cards_cnt; ?>">
            <div class="m-safety-list">
            	<?php if ($active_cards): ?>
                	<?php foreach ($active_cards as $card): ?>
                        <div class="m-safety-item">
                            <i class="fa fa-check-circle"></i>
                            <p class="safety-card-name"><?php echo $card->safety_name; ?></p>
                            <p class="safety-time">
                            	<?php echo !$card->does_not_expire ? '+' . ceil((strtotime($card->expiration) - time())/86400) : ''; ?>
                            </p>
                        </div>
                	<?php endforeach; ?>
            	<?php endif; ?>
            </div>
            <a class="load-more load-more-active load-btn <?php echo $active_cards_cnt > $item_per_page ? '' : 'hide'; ?>" mobile="1" offset="<?php echo $item_per_page; ?>" href="#">Load more</a>
        </div>
    </div>

    <div class="m-safety-wrapper-item">
        <div class="m-safety-header safety-expire">
            Expired Safety and Training
        </div>
        <div class="m-safety-body safety-expire-list" total-count="<?php echo $expired_cards_cnt; ?>">
            <div class="m-safety-list">
            	<?php if ($expired_cards): ?>
                	<?php foreach ($expired_cards as $card): ?>
                        <div class="m-safety-item">
                            <i class="fa fa-times-circle"></i>
                            <p class="safety-card-name"><?php echo $card->safety_name; ?></p>
                            <p class="safety-time"><?php echo $card->expiration; ?></p>
                        </div>
                	<?php endforeach; ?>
            	<?php endif; ?>
            </div>
            <a class="load-more load-more-expired load-btn <?php echo $expired_cards_cnt > $item_per_page ? '' : 'hide'; ?>" mobile="1" offset="<?php echo $item_per_page; ?>" href="#">Load more</a>
        </div>
    </div>

</div>