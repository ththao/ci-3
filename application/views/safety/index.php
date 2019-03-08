<!-- Left Sidebar-->
<div id="main" class="container-fluid main-content pad0">
    <div class="row mar0">

        <!-- Left Sidebar-->
        <?php $this->load->view("workboard/partials/sidebar"); ?>

        <!--Main Content-->
        <div class="col-xs-12 col-sm-8 col-md-9 col-lg-10 content new-layout">
            <div class="m-title d-title">
                <h1 class="m-title-head d-title-content-right">Safety</h1>
            </div>
            <div id="m-content" class="m-safety new-layout-safety">
                <div class="m-safety-wrapper-item d-safety-wrapper-item">
                    <div class="m-safety-header d-safety-header safety-active">
                        Active Safety and Training
                    </div>
                    <div class="m-safety-body d-safety-body safety-active-list" total-count="<?php echo $active_cards_cnt; ?>">
                        <div class="m-safety-list">
                            <?php if ($active_cards): ?>
                                <?php foreach ($active_cards as $card): ?>
                                    <div class="m-safety-item d-safety-item">
                                        <i class="fa fa-check-circle clear-button"></i>
                                        <p class="safety-card-name d-safety-card-name"><?php echo $card->safety_name; ?></p>
                                        <p class="safety-time d-safety-time">
                                            <?php echo !$card->does_not_expire ? '+' . ceil((strtotime($card->expiration) - time()) / 86400) : ''; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a class="load-more load-more-active load-btn <?php echo $active_cards_cnt > $item_per_page ? '' : 'hide'; ?>" mobile="0" offset="<?php echo $item_per_page; ?>" href="#">Load more</a>
                    </div>
                </div>

                <div class="m-safety-wrapper-item d-safety-wrapper-item">
                    <div class="m-safety-header d-safety-body d-safety-header safety-expire">
                        Expired Safety and Training
                    </div>
                    <div class="m-safety-body safety-expire-list" total-count="<?php echo $expired_cards_cnt; ?>">
                        <div class="m-safety-list">
                            <?php if ($expired_cards): ?>
                                <?php foreach ($expired_cards as $card): ?>
                                    <div class="m-safety-item d-safety-item">
                                        <i class="fa fa-times-circle clear-button"></i>
                                        <p class="safety-card-name d-safety-card-name"><?php echo $card->safety_name; ?></p>
                                        <p class="safety-time d-safety-time"><?php echo $card->expiration; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a class="load-more load-more-expired load-btn <?php echo $expired_cards_cnt > $item_per_page ? '' : 'hide'; ?>" mobile="0" offset="<?php echo $item_per_page; ?>" href="#">Load more</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
