$(document).ready(function() {
	$(document).on('click', '.sidebar-clocked-in', function(e) {
		e.preventDefault();
		
		var selected = $(this);
		
		$.ajax({
			method: "POST",
			url: "/ajax/clock_out",
			dataType: 'json',
			data: {
				mobile: 0
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" />').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					window.location.reload();
				}
			},
			error: function() {
				$.notify("There was an issue while trying to clock out, please try again", "error");
				$(selected).html('In');
			}
		});
	});

	/*Numpad login*/
    $(document).on('click', '.numpad-item', function (e) {
        e.preventDefault();

        var numValue = $(this).attr('data-value');
        console.log(numValue);
        if (numValue === "clr") {
            $('#code').val("");
        } else if (numValue === "delete") {
            $('#code').val($('#code').val().slice(0, -1));
        } else {
            $('#code').val($('#code').val() + numValue);
        }
    });
});