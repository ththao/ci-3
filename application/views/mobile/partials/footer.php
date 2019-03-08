<div class="bottom-tab">
    <div class="left-bottom-tab">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="">
                <a class="tab-item close-tabs" href="#arrow" aria-controls="home" role="tab" data-toggle="tab">
                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                </a>
            </li>
            <?php if ($this->session->has_userdata('FEATURE_MOW_PATTERNS') && $this->session->userdata('FEATURE_MOW_PATTERNS')): ?>
                <li role="presentation">
                    <a class="tab-item open-tab" href="#mowing" aria-controls="home" role="tab" data-toggle="tab"><?= $this->lang->line('mowing') ?></a>
                </li>
            <?php endif; ?>
            <?php if ($this->session->has_userdata('FEATURE_NOTES') && $this->session->userdata('FEATURE_NOTES')): ?>
            <li role="presentation">
                <a class="tab-item open-tab" href="#notes" aria-controls="messages" role="tab" data-toggle="tab"><?= $this->lang->line('notes') ?></a>
            </li>
            <?php endif; ?>
            <?php if ($this->session->has_userdata('FEATURE_GEONOTES') && $this->session->userdata('FEATURE_GEONOTES')): ?>
            <li role="presentation">
                <a class="tab-item open-tab" href="#geonotes" aria-controls="settings" role="tab" data-toggle="tab"><?= $this->lang->line('geonotes') ?></a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade" id="arrow"></div>

            <!--Mowing tab content-->
            <div role="tabpanel" class="tab-pane fade" id="mowing"></div>
			
            <!--Note tab content-->
            <div role="tabpanel" class="tab-pane fade" id="notes">
                <div class="note-item daily-notes">
                    <h4 class="note-title"><?= $this->lang->line('daily_notes') ?></h4>
                    <div class="note-content">
                    </div>
                </div>

                <div class="note-item general-notes">
                    <h4 class="note-title"><?= $this->lang->line('general_notes') ?></h4>
                    <div class="note-content">
                    </div>
                </div>
            </div>

            <!--Geonote tab content-->
            <div role="tabpanel" class="tab-pane fade" id="geonotes"></div>
        </div>
        
        <div class="mow" id="footer-mowing"></div>
    </div>
    
    <div class="right-bottom-tab">
        <div class="right-tab-header">
            <i class="fa fa-2x fa-caret-down" aria-hidden="true"></i>
        </div>
        <div class="right-tab-content">
        	<?php foreach ($departments as $index => $department): ?>
        		<a href="/mobile/workboard?d=<?= $department->d_id ?>" class="btn <?= ($this->session->userdata('department_id') == $department->d_id) ? 'btn-success' : 'btn-default' ?> m-btn">
        			<?= $department->department_name ?>
        		</a>
        	<?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="geoNoteModal" tabindex="-1" role="dialog" aria-labelledby="geoNoteModalLabel">
    <div class="modal-dialog geonote-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">GeoNotes</h4>
            </div>
            <div class="modal-body">
                <div class="modal-replyicon">
                    <a href="#" class="reply-icon-item"><span class="replyIcon SiteIcon"><i class="fa fa-sitemap"></i></span></a>
                    <a href="#" class="reply-icon-item photo-icon-item hide"><span class="replyIcon PhotoIcon"><i class="fa fa-camera fa-fw"></i></span></a>
                    <a href="#" class="reply-icon-item map-icon-item hide"><span class="replyIcon MapIcon"><i class="fa fa-map-marker fa-fw"></i></span></a>
                </div>
                <div class="modal-note-content">
                    <div class="emp-note-content">
                        <div class="NotePerson"></div>
                        
                        <div class="checkbox-area">
                            <?php foreach ($departments as $index => $department): ?>
                        		<div class="checkbox-item">
                                    <p><?= $department->department_name ?></p>
                                    <div class="slideOne">
                                        <input type="checkbox" value="<?= $department->d_id ?>" id="slide<?= $index ?>" name="valey" checked />
                                        <label for="slide<?= $index ?>"></label>
                                    </div>
                                </div>
                        	<?php endforeach; ?>
                        </div>
                    </div>
                    <div class="note-text"><span></span></div>
                </div>
                <div class="modal-more-content">
                    <div id="carousel-example-generic" class="carousel slide geonote-imgs">
                        <!-- Indicators 
                        <ol class="carousel-indicators">
                            <li data-target="#carousel-example-generic" data-slide-to="0" class="active"></li>
                            <li data-target="#carousel-example-generic" data-slide-to="1"></li>
                        </ol>-->

                        <!-- Wrapper for slides -->
                        <div class="carousel-inner" role="listbox">
                        </div>

                        <!-- Controls -->
                        <a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
                            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                    
                    <div class="geonote-maps">
                    	<div id="geonote-maps"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>