$(document).ready(function() {
	$(document).on('click', '.btn-change', function (e) {
        e.preventDefault();
        $('#mail-phone').slideUp('medium').removeClass('open');
        $('#username-pass').slideDown('medium').addClass('open')
    });
	
	$(document).on('click', '.settings-receive-text', function() {
		if ($(this).find('.fa-check-square-o').length > 0) {
			$(this).find('.fa-check-square-o').addClass('fa-square-o').removeClass('fa-check-square-o');
		} else {
			$(this).find('.fa-square-o').addClass('fa-check-square-o').removeClass('fa-square-o');
		}
	});
	
	$(document).on('blur', '.settings-new-username', function(e) {
		var selected = $(this);
		
		if ($.trim($(selected).val()) == '') {
			$('.username-taken').addClass('hide').removeClass('show');
			$('.check-info-username').html('<i class="fa fa-check-circle" aria-hidden="true"></i>').addClass('hide').removeClass('show');
			return false;
		}
		$.ajax({
    		method: "POST",
    		url: '/mobile/settings/validate_new_username',
    		dataType: 'json',
    		data: {
    			username: $(selected).val()
    		},
    		success: function(response) {
    			if (response.status == 1) {
    				$('.username-taken').addClass('hide').removeClass('show');
    				$('.check-info-username').html('<i class="fa fa-check-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
    			} else {
    				$('.username-taken').addClass('show').removeClass('hide');
    				$('.check-info-username').html('<i class="fa fa-times-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
    			}
    		}
    	});
	});
	
	$(document).on('blur', '.settings-new-password', function(e) {
		if (validate_password($(this).val())) {
			$('.password-require').addClass('hide').removeClass('show');
			$('.check-info-password').html('<i class="fa fa-check-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
		} else {
			$('.password-require').addClass('show').removeClass('hide');
			$('.check-info-password').html('<i class="fa fa-times-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
		}
	});
	
	$(document).on('blur', '.settings-new-password2', function(e) {
		if ($(this).val() == $('.settings-new-password').val()) {
			$('.password-confirm').addClass('hide').removeClass('show');
			$('.check-info-password2').html('<i class="fa fa-check-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
		} else {
			$('.password-confirm').addClass('show').removeClass('hide');
			$('.check-info-password2').html('<i class="fa fa-times-circle" aria-hidden="true"></i>').addClass('show').removeClass('hide');
		}
	});
	
	$(document).on('click', '.btn-save-new-account', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	if ($('.username-taken').hasClass('show') || $('.password-confirm').hasClass('show') || $('.password-require').hasClass('show')) {
    		return false;
    	}
    	if ($.trim($('.settings-new-username').val()) == '' && $.trim($('.settings-new-password').val()) == '') {
    		return false;
    	}
    	
    	$.ajax({
    		method: "POST",
    		url: '/mobile/settings/save_new_account',
    		dataType: 'json',
    		data: {
    			username: $('.settings-new-username').val(),
    			password: $('.settings-new-password').val()
    		},
    		beforeSend: function() {
    			$(selected).html('<img src="/assets/img/loading.gif" />');
    		},
    		success: function(response) {
    			if (response.status == 1) {
    				$('.check-info-username, .check-info-password, .check-info-password2').addClass('hide').removeClass('show');
    				$.notify(response.message, "success");
    			} else {
					$.notify(response.message, "warning");
    			}
    		},
    		complete: function() {
    			$(selected).html('Save');
    		}
    	});
    });
	
    $(document).on('click', '.btn-confirm-current-password', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	
    	$.ajax({
    		method: "POST",
    		url: '/mobile/settings/confirm_current_password',
    		dataType: 'json',
    		data: {
    			password: $('.settings-current-password').val()
    		},
    		beforeSend: function() {
    			$(selected).html('<img src="/assets/img/loading.gif" />');
    		},
    		success: function(response) {
    			if (response.status == 1) {
    		        $('#confirm-form').slideUp('medium').addClass('hide-form');
    		        $('#change-form').slideDown('medium').removeClass('hide-form');
    			} else {
    				if (response.reload) {
						window.location.reload();
					}
					$.notify(response.message, "warning");
    			}
    		},
    		complete: function() {
    			$(selected).html('Submit');
    		}
    	});
    });
    
    $(document).on('click', '.btn-save-settings', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	
    	$.ajax({
    		method: "POST",
    		url: '/mobile/settings/save_settings',
    		dataType: 'json',
    		data: {
    			email: $('.settings-email').val(),
    			ecell: $('.settings-ecell').val(),
    			receive_text_alert: $('.settings-receive-text').find('.fa-check-square-o').length > 0 ? 1 : 0
    		},
    		beforeSend: function() {
    			$(selected).html('<img src="/assets/img/loading.gif" />');
    		},
    		success: function(response) {
    			if (response.status == 1) {
    				if (response.message) {
    					$.notify(response.message, "success");
    				}
    			} else {
    				if (response.reload) {
						window.location.reload();
					}
					$.notify('There was an error while trying to save settings. Please try again.', "error");
    			}
    		},
    		complete: function() {
    			$(selected).html('Save');
    		}
    	});
    });
});

function validate_password(password) {
	if (!hasLowerCase(password) || !hasUpperCase(password) || !hasNumber(password) || password.length < 6 || password.length > 20) {
		return false;
	}
	return true;
}

function hasLowerCase(str) {
    return str.toUpperCase() != str;
}
function hasUpperCase(str) {
    return str.toLowerCase() != str;
}
function hasNumber(myString) {
	return /\d/.test(myString);
}