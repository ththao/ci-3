$(document).ready(function() {
	jQuery.curCSS = function(element, prop, val) {
	    return jQuery(element).css(prop, val);
	};
	
	if ($("#m-schedule-item-" + $('.current_date').val()).length) {
		$('.m-schedule-list').animate({
			scrollTop: $("#m-schedule-item-" + $('.current_date').val()).offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
	    }, 1000);
	}
	
	$(document).on('click', '#open-menu', function (e) {
        e.preventDefault();
        
        $('#left-side-menu').slideToggle('medium');
    });
	
	$('.date-picker').datepicker({
		format:'m/d/Y',
		onSelect: function(formated) {
			var date = formated.split('/').join('-');
			if ($("#m-schedule-item-" + date).length) {
				$('.m-schedule-list').animate({
					scrollTop: $("#m-schedule-item-" + date).offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
		        }, 1000);
			} else {
				if (formated > $('.m-schedule-list-provider').attr('end')) {
					var dates = new Date(formated);
					dates.setDate(dates.getDate() + 10);
					var dd = dates.getDate();
					var mm = dates.getMonth() + 1;
					var y = dates.getFullYear();

					var end = (mm < 10 ? ('0' + mm) : mm) + '/' + (dd < 10 ? ('0' + dd) : dd) + '/'+ y;
					
					loadSchedules(1, $('.m-schedule-list-provider').attr('end'), end, formated);
				} else if (formated < $('.m-schedule-list-provider').attr('start')) {
					loadSchedules(-1, formated, $('.m-schedule-list-provider').attr('start'), formated);
				}
			}
		}
	});
    
    $('.m-schedule-list').scroll(function(){
        if ($(this)[0].scrollHeight - $(this).scrollTop() === $(this).outerHeight()) {
        	load_on_scroll(1);
        } else if ($(this).scrollTop() == 0) {
        	load_on_scroll(-1);
        }
    });
    
    $(document).on('click', '.task-note', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	$('.m-schedule-list').animate({
    		scrollTop: $(selected).parents('.m-schedule-item').offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
        }, 100);
    	
    	renderScheduleModal($(selected).parents('.m-schedule-item').find('.more-info').attr('date'), 3);
    });
    
    $(document).on('click', '.more-info', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	$('.m-schedule-list').animate({
    		scrollTop: $(selected).parents('.m-schedule-item').offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
        }, 100);
    	
    	renderScheduleModal($(selected).attr('date'), 1);
    });
    
    $(document).on('click', '.navigation-content .prev-btn', function(e) {
    	e.preventDefault();
    	
    	var tab = $('.btn-see').hasClass('active') ? 1 : ($('.btn-time-off').hasClass('active') ? 2 : 3);
    	renderScheduleModal($(this).attr('date'),  tab);
    });
    
    $(document).on('click', '.navigation-content .next-btn', function(e) {
    	e.preventDefault();
    	
    	var tab = $('.btn-see').hasClass('active') ? 1 : ($('.btn-time-off').hasClass('active') ? 2 : 3);
    	renderScheduleModal($(this).attr('date'), tab);
    });
    
    $(document).on('click', '#submit-off-request', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
    	sendRequest($(this));
    });
    
    $(document).on('click', '.add-off-request', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
    	$('#submit-off-request').trigger('click');
    });
    
    $(document).on('click', '#cancel-off-request', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
		if (confirm('Do you want to cancel day off request?')) {
			sendRequest($(this));
		}
    });
    
    $(document).on('click', '#remove-off-request', function(e) {
    	e.preventDefault();
    	
    	$('#cancel-off-request').trigger('click');
    });
    
    $(document).on('click', '.add-note', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	var note = $(this).parents('.control-content').find('.schedule-textarea').val();
    	if ($.trim(note) == '') {
    		$.notify('Please add note.', "warn");
    		return false;
    	}
		
		var selected = $(this);
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/add_note',
    		dataType: 'json',
    		data: {
    			requested_date: $('#scheduleModalNew .request_date').val(),
    			request_note: $.trim(note)
    		},
    		beforeSend: function() {
    			$(selected).addClass('disabled').html('<img src="/assets/img/loading.gif" atl="Please wait..."/>');
    			$('#remove-notes').addClass('disabled');
    		},
    		success: function(response) {
    			if (response.status) {
    				$.notify('Note has been added.', "success");
    				$('#scheduleModalNew #timeOff .schedule-note-list').append(response.new_note);
    				$('#scheduleModalNew #notes .schedule-note-list').append(response.new_note);
    				$('#scheduleModalNew .schedule-textarea').val('');
    				if ($('#' + $(selected).attr('for-item')).find('.task-note').length <= 0) {
    					$('<a href="#" class="task-note">Notes</a>').insertAfter($('#' + $(selected).attr('for-item') + ' .m-task-list'));
    				}    				
    			} else {
    				if (response.timed_out) {
    					window.location.reload();
    				}
    				$.notify(response.message, "warn");
    			}
    		},
    		complete: function() {
    			$(selected).removeClass('disabled').html('Submit');
    			$('#remove-notes').removeClass('disabled');
    		}
    	});
    });
    
    $(document).on('click', '#remove-notes', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
    	if (confirm('Are you sure you want to remove all notes?')) {
    		var selected = $(this);
    		
    		$.ajax({
        		method: "POST",
        		url: '/schedule/remove_notes',
        		dataType: 'json',
        		data: {
        			requested_date: $('#scheduleModalNew .request_date').val()
        		},
        		beforeSend: function() {
        			$('#remove-notes').addClass('disabled');
        		},
        		success: function(response) {
        			if (response.status) {
        				$.notify('Notes has been removed.', "success");
        				$('#scheduleModalNew #timeOff .schedule-note-list').html('');
        				$('#scheduleModalNew #notes .schedule-note-list').html('');
        				$('#' + $(selected).attr('for-item')).find('.task-note').remove();
        			} else {
        				if (response.timed_out) {
        					window.location.reload();
        				}
        				$.notify(response.message, "warn");
        			}
        		},
        		complete: function() {
        			$('#remove-notes').removeClass('disabled');
        		}
        	});
    	}
    });
    
    $(document).on('click', '.emp-btn-swap', function(e) {
    	e.preventDefault();
    	var selected = $(this);
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/swap_request',
    		dataType: 'json',
    		data: {
    			requested_date: $('#scheduleModalNew .request_date').val(),
    			to_worker_id: $(selected).attr('worker_id'),
    			mobile: 1
    		},
    		beforeSend: function() {
    			$(selected).addClass('disabled');
    		},
    		success: function(response) {
    			if (response.status) {
    				if (response.message) {
        				$.notify(response.message, "success");
    				}
    				if (typeof response.request_status !== 'undefined' && response.request_status == 0) {
						$(selected).addClass('pending');
    				} else {
    					$(selected).attr('class', 'emp-btn-swap');
    				}
    				$('#scheduleModalNew #seeSchedule').html(response.schedules);
    			} else {
    				if (response.timed_out) {
    					window.location.reload();
    				}
    				$.notify(response.message, "warn");
    			}
    		},
    		complete: function() {
    			$(selected).removeClass('disabled');
    		}
    	});
    });
    
    $(document).on('click', '.withdraw-swap', function(e) {
    	e.preventDefault();
    	var selected = $(this);
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/withdraw_swap',
    		dataType: 'json',
    		data: {
    			requested_date: $('#scheduleModalNew .request_date').val(),
    			swap_request_id: $(selected).attr('swap_request_id'),
    			mobile: 1
    		},
    		beforeSend: function() {
    			$(selected).addClass('disabled');
    		},
    		success: function(response) {
    			if (response.status) {
    				if (response.message) {
        				$.notify(response.message, "success");
    				}
    				$(selected).parents('.waiting-item').slideUp('medium', function() {
    					$(selected).parents('.waiting-item').remove();
    				});
    				$('#scheduleModalNew #seeSchedule').html(response.schedules);
    			} else {
    				if (response.timed_out) {
    					window.location.reload();
    				}
    				$.notify(response.message, "warn");
    			}
    		},
    		complete: function() {
    			$(selected).removeClass('disabled');
    		}
    	});
    });
    
    $(document).on('click', '#scheduleModalNew .btn-control', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-target');
		$('#scheduleModalNew .btn-control').removeClass('active');
		$(this).addClass('active');
		
		$('#scheduleModalNew .control-content').slideUp('medium').removeClass('open');
		$('#scheduleModalNew .'+ target).slideDown('medium').addClass('open');
    });
    
    $(document).on('click', '.btn-direct', function(e) {
    	e.preventDefault();
    	if ($(this).hasClass('btn-up')) {
    		$(this).removeClass('btn-up').addClass('btn-down');
    		$(this).find('.fa').removeClass('fa-caret-up').addClass('fa-caret-down');
			$(this).parents('.answer-request').find('.answer-request-content').slideDown('medium').addClass('open');
		} else if ($(this).hasClass('btn-down')){
            $(this).removeClass('btn-down').addClass('btn-up');
            $(this).find('.fa').removeClass('fa-caret-down').addClass('fa-caret-up');
            $(this).parents('.answer-request').find('.answer-request-content').slideUp('medium').removeClass('open');
		}
	})
});

function renderScheduleModal(requested_date, tab)
{
	$.ajax({
		method: "POST",
		url: '/schedule/render_schedule_modal',
		dataType: 'json',
		data: {
			requested_date: requested_date,
			mobile: 1
		},
		beforeSend: function() {
			$('#scheduleModalNew #timeOff').html('Loading...');
		},
		success: function(response) {
			if (response.status) {
				$('#scheduleModalNew .sc-month').html(response.month);
				$('#scheduleModalNew .sc-date-num').html(response.date);
				$('#scheduleModalNew .sc-day').html(response.day);
				$('#scheduleModalNew #timeOff').html(response.html);
				$('#scheduleModalNew #notes').html(response.notes);
				$('#scheduleModalNew #seeSchedule').html(response.schedules);
				$('#scheduleModalNew #alertRequests').html(response.alert_html);
				$('#footerAlertModal #alertRequests').html(response.alert_html);
				$('#scheduleModalNew .alert-num').html(response.alerts ? response.alerts : '');
				$('.schedule-alert .alert-count').html(response.alerts ? response.alerts : '');
				$('#scheduleModalNew .prev-btn').attr('date', response.prev);
				$('#scheduleModalNew .next-btn').attr('date', response.next);
				if (tab == 1) {
					$('#scheduleModalNew .btn-see').trigger('click');
				} else if (tab == 2) {
					$('#scheduleModalNew .btn-time-off').trigger('click');
				} else {
					$('#scheduleModalNew .btn-note').trigger('click');
				}
                $('#scheduleModalNew').modal('show');
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		}
	});
}

function sendRequest(selected)
{
	$.ajax({
		method: "POST",
		url: '/schedule/request_day_off',
		dataType: 'json',
		data: {
			requested_date: $('.request_date').val()
		},
		beforeSend: function() {
			$(selected).addClass('disabled').html('<img src="/assets/img/loading.gif" atl="Please wait..."/>');
			$('#remove-off-request').addClass('disabled');
		},
		success: function(response) {
			if (response.status) {
				$.notify(response.message, "success");
				
				if ($(selected).hasClass('pending')) {
    				$('#scheduleModalNew .btn-request').addClass('request').removeClass('pending').html('Request Day Off').attr('id', 'submit-off-request');
    				$('#' + $(selected).attr('for-item') + ' .m-task-list .task-list-item-request').remove();
				} else {
    				$('#scheduleModalNew .btn-request').removeClass('request').addClass('pending').html('Request Off Pending').attr('id', 'cancel-off-request');
    				$('#' + $(selected).attr('for-item') + ' .m-task-list').append('<li class="task-list-item task-list-item-request"><span>Day Off request Pending</span></li>');
				}
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		},
		complete: function() {
			$(selected).removeClass('disabled');
			$('#remove-off-request').removeClass('disabled');
		}
	});
}

function loadSchedules(direction, start, end, scrollto)
{
	if ($('.m-schedule-list-provider').find('.loading').length > 0) {
		return false;
	}
	$.ajax({
		method: "POST",
		url: '/schedule/load_mobile_schedules',
		dataType: 'json',
		data: {
			direction: direction,
			start: start,
			end: end,
		},
		beforeSend: function() {
			if (direction == 1) {
				$('.m-schedule-list-provider').append('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			} else {
				$('.m-schedule-list-provider').prepend('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			}
		},
		success: function(response) {
			if (response.status) {
				if (direction == 1) {
					$('.m-schedule-list-provider').append(response.html);
				} else {
				    //need to hold scroll position when prepending without jumping to the top
                    //get current hight 
                    
                    var hbefore = $('.m-schedule-list')[0].scrollHeight;
                    $('.m-schedule-list-provider').prepend(response.html);
                    var hafter = $('.m-schedule-list')[0].scrollHeight;
                    var hloading = $('.m-schedule-list .loading').height();
                    $('.m-schedule-list').scrollTop(hafter-hbefore-hloading-9);
                    
				}
				if (response.start) {
					$('.m-schedule-list-provider').attr('start', response.start);
				}
				if (response.end) {
					$('.m-schedule-list-provider').attr('end', response.end);
				}
				
				if (scrollto) {
					var date = scrollto.split('/').join('-');
					if ($("#m-schedule-item-" + date).length) {
						$('.m-schedule-list').animate({
							scrollTop: $("#m-schedule-item-" + date).offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
				        }, 1000);
					}
				}
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		},
		complete: function() {
			$('.m-schedule-list-provider').find('.loading').remove();
		}
	});
}

function load_on_scroll(direction) {
    var start = direction == 1 ? addDays($('.m-schedule-list .m-schedule-list-provider').attr('end'), 1) : '';
	var end = direction == -1 ? addDays($('.m-schedule-list .m-schedule-list-provider').attr('start'),-1) : '';
	
	loadSchedules(direction, start, end, null);
}

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    var dd = result.getDate();
    var mm = result.getMonth()+1; //January is 0!

        var yyyy = result.getFullYear();
        if(dd<10){
            dd='0'+dd;
        } 
        if(mm<10){
            mm='0'+mm;
        } 
        var result = mm+'/'+dd+'/'+yyyy;
    
    return result;
}