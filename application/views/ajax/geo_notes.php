<div class="iNote clickable" img-path="<?= $this->config->item('asb_url') ?>/memberdata/<?= $geoNote->c_id ?>/geonotes/images/">
	<div title="<?= $geoNote->first_name . ' ' . $geoNote->last_name ?>" class="NotePerson">
		<?= substr($geoNote->first_name, 0, 1) . substr($geoNote->last_name, 0, 1) ?>
	</div>
    <div class="m-note-content">
        <div class="options">
            <span class="replyIcon SiteIcon"><i class="fa fa-sitemap"></i></span>
            <span class="replyIcon PhotoIcon <?= empty($pictures) ? 'hide' : '' ?>"><i class="fa fa-camera fa-fw"></i></span>
			<span class="replyIcon MapIcon <?= empty($markers) ? 'hide' : '' ?>"><i class="fa fa-map-marker fa-fw"></i></span>
        </div>
        <div class="noteBody">
            <span class="noteBodyText"><?= stripslashes($geoNote->geonote_note) ?></span>
            <?php if ($pictures) { ?>
                <div class="noteimgs">
                	<?php foreach ($pictures as $picture): ?>
                		<input type="hidden" value="<?= $picture->geonote_picturelink ?>" />
                	<?php endforeach; ?>
                </div>
            <?php } ?>
            <?php if ($markers) { ?>
                <div class="mapMarkers ">
                    <?php foreach ($markers as $marker): ?>
                        <div class="inMarkers ">
                            <input class="mapLat" type="hidden" value="<?= $marker->geonote_lat ?>">
                            <input class="mapLng" type="hidden" value="<?= $marker->geonote_long ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php } ?>
        </div>
	</div>
</div>