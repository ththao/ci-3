$(document).ready(function() {
	$.ajax({
		method: "POST",
		url: '/schedule/renderAlerts',
		dataType: 'json',
		success: function(response) {
			if (response.status == 1) {
				$('.schedule-alert .alert-count').html(response.alerts ? response.alerts : '');
				$('#footerAlertModal #alertRequests').html(response.request_html);
			}
		}
	});
	
	$(document).on('click', '.schedule-alert .ring-bell', function(e) {
		e.preventDefault();

		$('#footerAlertModal').modal('show');
		$.ajax({
    		method: "POST",
    		url: '/schedule/viewed_alerts',
    		dataType: 'json',
    		success: function(response) {
    			if (response.status == 1) {
    				$('#scheduleModalNew .alert-num').html(response.alerts ? response.alerts : '');
    				$('.schedule-alert .alert-count').html(response.alerts ? response.alerts : '');
    			}
    		}
    	});
	});
	
	$(document).on('click', '.btn-alert', function(e) {
		e.preventDefault();
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/viewed_alerts',
    		dataType: 'json',
    		success: function(response) {
    			if (response.status == 1) {
    				$('#scheduleModalNew .alert-num').html(response.alerts ? response.alerts : '');
    				$('.schedule-alert .alert-count').html(response.alerts ? response.alerts : '');
    			}
    		}
    	});
	});
    
    $(document).on('click', '.btn-agree-swap, .btn-dismiss-swap', function(e) {
    	e.preventDefault();
    	var selected = $(this);
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/response_swap_request',
    		dataType: 'json',
    		data: {
    			swap_request_id: $(selected).attr('swap_request_id'),
    			status: $(selected).hasClass('btn-agree-swap') ? 1 : 2
    		},
    		beforeSend: function() {
    			$(selected).addClass('disabled');
    		},
    		success: function(response) {
    			if (response.status) {
    				if (response.message) {
        				$.notify(response.message, "success");
    				}
    				
    				$('#scheduleModalNew #seeSchedule').html(response.schedules);
    				$('#scheduleModalNew #alertRequests').html(response.alert_html);
    				$('#footerAlertModal #alertRequests').html(response.alert_html);
    				$('#scheduleModalNew .alert-num').html(response.alerts ? response.alerts : '');
    				$('.schedule-alert .alert-count').html(response.alerts ? response.alerts : '');
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
});