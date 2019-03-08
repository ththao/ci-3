$(document).ready(function() {
	calculatePartTotal();
	
	$('.note-textarea, .add-wo-textarea').autogrow();
	$('#partModal').find('.part-select').select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
	
	var timer = null;
	timer = setupTimer(timer);
	
	$(document).on('click', '#open-menu', function (e) {
        e.preventDefault();
        
        $('#left-side-menu').slideToggle('medium');
    });
	
	$(document).on('click', '.wo-action-btn.start-btn', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}
		
		var startButton = $(this);
		var caption = $(startButton).html();
		$.ajax({
			method: "POST",
			url: "/ajax/start_wo_job",
			dataType: 'json',
			data: {
				wb_task_id: $(startButton).attr('wb_task_id'),
				wo_item_job_id: $(startButton).attr('wo_item_job_id')
			},
			beforeSend: function() {
				$(startButton).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$(".stop-btn").each(function() {
						$(this).parents(".wo-job-item").find(".stop-btn").html("Start").addClass("start-btn").removeClass("stop-btn").removeAttr('time_id');
						$(this).parents(".wo-job-item").find(".act-hour").removeClass("hide");
						$(this).parents(".wo-job-item").find(".job-countdown").addClass("hide");
					});
					
					$(startButton).html("Stop").removeClass("start-btn").addClass("stop-btn").attr('time_id', response.task.time_id);
					$(startButton).parents(".wo-job-item").find(".act-hour").addClass("hide");
					$(startButton).parents(".wo-job-item").find(".job-countdown").attr('time', response.task.time).removeClass("hide");
					
					if (response.wb_task_id) {
						$(startButton).attr('wb_task_id', response.wb_task_id);
						
						if ($(startButton).parents('.wo-job-item').find('.wo-job-status').hasClass('status-not')) {
							$(startButton).parents('.wo-job-item').find('.wo-job-status').removeClass('status-not').addClass('status-progress').html('In Progress');
						}
						
						if ($(startButton).parents('.m-wo-detail').find('.eqm-change-status').find('p').hasClass('wo-status-not')) {
							$(startButton).parents('.m-wo-detail').find('.eqm-change-status').find('p').attr('class', 'wo-status wo-status-progress');
						}
					}
					
					timer = setupTimer(timer);
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(startButton).html(caption);
					$.notify(response.message, "warn");
				}
			},
			error: function() {
				$(startButton).html(caption);
				$.notify("There was an error when trying to start task, please try again!", "error");
			},
			complete: function() {
				$(startButton).removeClass('disabled');
			}
		});
	});
	
	$(document).on('click', '.wo-action-btn.stop-btn', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var stopButton = $(this);
		var caption = $(stopButton).html();
		$.ajax({
			method: "POST",
			url: "/ajax/stop_task",
			dataType: 'json',
			data: {
				time_id: $(stopButton).attr('time_id')
			},
			beforeSend: function() {
				$(stopButton).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$(".stop-btn").each(function() {
						$(this).parents(".wo-job-item").find(".stop-btn").html("Start").addClass("start-btn").removeClass("stop-btn").removeAttr('time_id');
						$(this).parents(".wo-job-item").find(".act-hour").removeClass("hide");
						$(this).parents(".wo-job-item").find(".job-countdown").addClass("hide");
					});
					
					timer = setupTimer(timer);
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(stopButton).html(caption);
					$.notify(response.message, "warn");
				}
			},
			error: function() {
				$(stopButton).html(caption);
				$.notify("There was an error when trying to stop task, please try again!", "error");
			},
			complete: function() {
				$(stopButton).removeClass('disabled');
			}
		});
	});
	
	$(document).on('click', '.edit-part', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		$.ajax({
			method: "POST",
			url: "/ajax/get_mcn_products",
			dataType: 'json',
			data: {
				wo_item_job_id: $(selected).parents(".wo-job-item").find(".wo-action-btn").attr("wo_item_job_id")
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					
					$("#partModal .modal-title").html("Edit Part");
					$("#partModal #quty").val($(selected).parents(".part-item").find(".job-part-qty").html());
					$("#partModal #part").html(productOptions(response.products, $(selected).parents(".part-item").find(".job-part-name").attr("product_id")));
					$("#partModal #cost").val($(selected).parents(".part-item").find(".job-part-cost").html());
					var total = parseInt($('#partModal #quty').val()) * parseFloat($('#partModal #cost').val());
					$('#partModal .modal-total-cost').html('$' + total.toFixed(2));
					
					$("#partModal .save-part").html('<i class="fa fa-pencil"></i> Edit Part').attr("wo_job_product_id", $(selected).attr("wo_job_product_id")).removeAttr("wo_job_id");
					$("#partModal .delete-part").removeClass("hide").attr("wo_job_product_id", $(selected).attr("wo_job_product_id"));
					$("#partModal").modal("show");
					
					$(selected).addClass("editing");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '.job-part-name', function(e) {
		e.preventDefault();
		
		$(this).parents('.part-item').find('.add-new-part').trigger('click');
	});
	
	$(document).on('click', '.add-new-part', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		$.ajax({
			method: "POST",
			url: "/ajax/get_mcn_products",
			dataType: 'json',
			data: {
				wo_item_job_id: $(selected).parents(".wo-job-item").find(".wo-action-btn").attr("wo_item_job_id")
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$("#partModal .modal-title").html("Add Part");
					$("#partModal #quty").val(1);
					$("#partModal #part").html(productOptions(response.products, null));
					$("#partModal #cost").val("");
					$('#partModal .modal-total-cost').html('$0');
					
					$("#partModal .save-part").html('<i class="fa fa-plus"></i> Add Part').attr("wo_job_id", $(selected).attr("wo_job_id")).removeAttr("wo_job_product_id");
					$("#partModal .delete-part").addClass("hide").removeAttr("wo_job_product_id");
					$("#partModal").modal("show");
					
					$(selected).addClass("editing");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('change', '#partModal #part', function() {
		if ($.trim($('#partModal #cost').val()) == "" || parseFloat($('#partModal #cost').val()) == 0) {
			var option = $(this).find("option:selected");
			var cost = parseFloat($(option).attr("average_cost"));
			
			$('#partModal #cost').val(cost.toFixed(2));
			var total = parseInt($('#partModal #quty').val()) * cost;
			$('#partModal .modal-total-cost').html('$' + total.toFixed(2));
		}
	});
	
	$(document).on('change', '#partModal #quty', function() {
		var total = parseInt($('#partModal #quty').val()) * parseFloat($('#partModal #cost').val());
		$('#partModal .modal-total-cost').html('$' + total.toFixed(2));
	});
	
	$(document).on('change', '#partModal #cost', function() {
		var total = parseInt($('#partModal #quty').val()) * parseFloat($('#partModal #cost').val());
		$('#partModal .modal-total-cost').html('$' + total.toFixed(2));
	});
	
	$(document).on('click', '#partModal .save-part', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
			method: "POST",
			url: "/ajax/save_mcn_products",
			dataType: 'json',
			data: {
				wo_job_id: $(selected).attr("wo_job_id"),
				wo_job_product_id: $(selected).attr("wo_job_product_id"),
				quantity: $("#partModal #quty").val(),
				product_id: $("#partModal #part").val(),
				product_name: $("#partModal #part").find("option:selected").html(),
				cost: $("#partModal #cost").val()
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					if (response.html) {
						$(response.html).insertBefore($(".editing").parents(".part-item"));
					} else {
						var part_item = $(".editing").parents(".part-item");
						$(part_item).find(".job-part-qty").html($("#partModal #quty").val());
						$(part_item).find(".job-part-name").html($("#partModal #part").find("option:selected").html()).attr("product_id", $("#partModal #part").val());
						$(part_item).find(".job-part-cost").html($("#partModal #cost").val());
						var total = parseFloat($("#partModal #cost").val()) * parseInt($("#partModal #quty").val());
						$(part_item).find(".job-part-total").html(total.toFixed(2));
					}
					$(".editing").removeClass("editing");
					
					calculatePartTotal();
					$("#partModal").modal("hide");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '#partModal .delete-part', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
			method: "POST",
			url: "/ajax/delete_mcn_products",
			dataType: 'json',
			data: {
				wo_job_product_id: $(selected).attr("wo_job_product_id")
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$(".editing").parents(".part-item").slideUp('medium', function() {
						$(".editing").parents(".part-item").remove();
					});
					
					calculatePartTotal();
					$("#partModal").modal("hide");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '.edit-note', function(e) {
		e.preventDefault();
		
		$("#noteModal .modal-title").html("Edit Note");
		$("#noteModal .note-textarea").val($(this).parents(".note-content").find("p").html());
		$("#noteModal .save-notes").html('<i class="fa fa-pencil"></i> Edit Notes').removeAttr("wo_job_id").attr("wo_job_note_id", $(this).attr("wo_job_note_id"));
		$("#noteModal .delete-notes").removeClass("hide").removeAttr("wo_job_id").attr("wo_job_note_id", $(this).attr("wo_job_note_id"));
		$("#noteModal").modal("show");
		
		$(this).addClass("editing");
	});
	
	$(document).on('click', '.add-new-note', function(e) {
		e.preventDefault();
		
		$("#noteModal .modal-title").html("Add Note");
		$("#noteModal .note-textarea").val("");
		$("#noteModal .save-notes").html('<i class="fa fa-plus"></i> Add Notes').removeAttr("wo_job_note_id").attr("wo_job_id", $(this).attr("wo_job_id"));
		$("#noteModal .delete-notes").addClass("hide").removeAttr("wo_job_note_id").attr("wo_job_id", $(this).attr("wo_job_id"));
		$("#noteModal").modal("show");
		
		$(this).addClass("editing");
	});
	
	$(document).on('click', '#noteModal .save-notes', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
			method: "POST",
			url: "/ajax/save_job_notes",
			dataType: 'json',
			data: {
				wo_job_id: $(selected).attr("wo_job_id"),
				wo_job_note_id: $(selected).attr("wo_job_note_id"),
				notes: $("#noteModal .note-textarea").val()
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					if (response.html) {
						$(response.html).insertBefore($(".editing"));
					} else {
						var note_item = $(".editing").parents(".job-note-item");
						$(note_item).find(".note-content p").html($("#noteModal .note-textarea").val())
					}
					$(".editing").removeClass("editing");
					
					$("#noteModal").modal("hide");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '#noteModal .delete-notes', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
			method: "POST",
			url: "/ajax/delete_job_notes",
			dataType: 'json',
			data: {
				wo_job_note_id: $(selected).attr("wo_job_note_id")
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$(".editing").parents(".job-note-item").slideUp('medium', function() {
						$(".editing").parents(".job-note-item").remove();
					});
					
					$("#noteModal").modal("hide");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '.equipment-update', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}
		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
			method: "POST",
			url: "/ajax/load_equipment_track",
			dataType: 'json',
			data: {
				equipment_id: $(selected).attr("equipment_id"),
				mobile: $(selected).attr('mobile')
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$("#updateModal .update-form").html(response.html);
					$("#updateModal").modal("show");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
		});
	});
	
	$(document).on('click', '#updateModal .btn-update', function(e) {
		e.preventDefault();
		
		if ($(this).hasClass('disabled')) {
			return false;
		}
		
		var update_amount = $('#updateModal .hour-input').val();
		if ($.trim(update_amount) == '') {
			alert('Please input Update Hours');
			return false;
		}

		var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
		    type: "POST",
			url: "/ajax/update_equipment_hours",
		    dataType: 'json',
		    data: {
			    equipment_id: $(selected).attr('equipment_id'),
			    update_amount: update_amount
		    },
		    beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					$(".equipment-hour-" + $(selected).attr('equipment_id')).html(response.new_hours);
					$("#updateModal").modal("hide");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
	    });
	});

    $(document).on('focus.note-textarea', 'textarea.note-textarea', function(){
        var savedValue = this.value;
        this.value = '';
        this.baseScrollHeight = this.scrollHeight;
        this.value = savedValue;
    }).on('input.note-textarea', 'textarea.note-textarea', function(){
        var minRows = this.getAttribute('data-min-rows')|0, rows;
        this.rows = minRows;
        rows = Math.ceil((this.scrollHeight - this.baseScrollHeight) / 16);
        this.rows = minRows + rows;
    });
    
    $(document).on('click', '.wo-job-item .wo-job-status', function(e) {
    	e.preventDefault();
    	
    	$('#statusModal .wo-job-status').attr('wo_job_id', $(this).attr('wo_job_id'));
    	$('#statusModal').modal('show');
    });
    
    $(document).on('click', '#statusModal .wo-job-status', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
			return false;
		}
    	
    	var selected = $(this);
		var caption = $(selected).html();
		
		$.ajax({
		    type: "POST",
			url: "/ajax/update_wo_job_status",
		    dataType: 'json',
		    data: {
		    	wo_job_id: $(selected).attr('wo_job_id'),
		    	status: $(selected).hasClass('status-complete') ? 2 : ($(selected).hasClass('status-skip') ? 3 : 1)
		    },
		    beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>').addClass('disabled');
			},
			success: function(response) {
				if (response.status) {
					if (response.message) {
						$.notify(response.message, "success");
					}
					$("#statusModal").modal("hide");
					
					if (response.completed) {
						var wo_item = $('.wo-job-status-' + $(selected).attr('wo_job_id')).parents('.wo-job-item');
						var job_list = $(wo_item).parents('.wo-job-list');
						
						$(wo_item).slideUp('medium', function() {
							$(wo_item).remove();
							
							if ($(job_list).find('.wo-job-item').length == 0) {
								$(job_list).parents('.m-wo-item').slideUp('medium', function() {
									$(job_list).parents('.m-wo-item').remove();
								});
							}
						});
					} else {
						$('.wo-job-status-' + $(selected).attr('wo_job_id')).removeClass('status-not').removeClass('status-progress')
							.removeClass('status-complete').removeClass('status-skip')
							.addClass(response.newClass).html(response.newCaption);
					}
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$(selected).html(caption);
					$.notify(response.message, "warn");
				}
			},
			complete: function() {
				$(selected).removeClass('disabled').html(caption);
			}
	    });
    });

    $(document).on('click', '.eqm-status', function(e) {
        e.preventDefault();

        if ($(this).hasClass('open')) {
            $(this).parents('.wo-header-action').find('.select-status-area').slideUp();
            $(this).removeClass('open');
        } else {
        	$('.eqm-status.open').removeClass('open');
        	$('.wo-status-select-area').slideUp();
        	
            $(this).parents('.wo-header-action').find('.select-status-area').slideDown('medium');
            $(this).addClass('open');
        }
	});
    
    $(document).on('click', '.eqm-change-status', function(e) {
    	e.preventDefault();

    	if ($(this).hasClass('open')) {
    		$(this).parents('.wo-detail-equipment').find('.select-status-area').slideUp();
    		$(this).removeClass('open');
		} else {
            $(this).parents('.wo-detail-equipment').find('.select-status-area').slideDown('medium');
            $(this).addClass('open');
		}
	});

    $(document).on('click', '.select-status-area .status-select', function(e) {
        e.preventDefault();

        var selected = $(this);
		
		$.ajax({
		    type: "POST",
			url: "/ajax/save_equipment_status",
		    dataType: 'json',
		    data: {
			    equipment_id: $(selected).attr('equipment_id'),
			    status: $(selected).attr('val')
		    },
			success: function(response) {
				if (response.status) {
					$(selected).parents('.select-status-area').slideUp();
					$(selected).parents('.select-status-area').find('.status-select').removeClass('selected');
					var s_class = 'status-color ' + (response.e_status == 3 ? 'status-disable' : (response.e_status == 2 ? 'status-issue' : 'status-ready'));
					
					if ($(selected).parents('.m-wo-item').length > 0) {
						$(selected).parents('.m-wo-item').find('.eqm-status').find('span').attr('class', s_class);
			            $(selected).parents('.m-wo-item').find('.eqm-status').removeClass('open');
					} else {
						$(selected).parents('.m-wo-detail').find('.eqm-change-status').find('p').attr('class', s_class);
			            $(selected).parents('.m-wo-detail').find('.eqm-change-status').removeClass('open');						
					}
		            
		            $(selected).addClass('selected');
		            
		            $.notify('Equipment Status has been updated.', "success");
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$.notify(response.message, "warn");
				}
			}
	    });
	});

    $(document).on('click', '.m-create-wo .add-equipment-btn', function(e) {
    	e.preventDefault();

    	$('#addEqmModal').modal('show');
	});

    $(document).on('click', '#addEqmModal .equipment-search-btn', function (e) {
    	e.preventDefault();
    	
    	var search_text = $.trim($('#addEqmModal .equipment-search-text').val());
    	
    	if (search_text == '') {
    		$('#addEqmModal .list-equipment-item').removeClass('hide');
    	} else {
    		$('#addEqmModal .list-equipment-item').each(function() {
    			var equip_type = $(this).find('.list-eqm-name').html();
    			equip_type = equip_type.toLowerCase();
    			if (equip_type.indexOf(search_text.toLowerCase()) >= 0) {
    				$(this).removeClass('hide');
    			} else {
    				$(this).addClass('hide');
    			}
    		});
    	}
    });

    $(document).on('click', '#addEqmModal .list-eqm-name', function(e) {
    	e.preventDefault();

    	$(this).parents('.list-equipment-item').find('.list-eqm-body').slideDown('medium');
	});
    
    $(document).on('click', '#addEqmModal .eqm-more-info', function(e) {
        e.preventDefault();

        if ($(this).hasClass('open')) {
        	$(this).parents('.eqm-item').find('.more-info-popup').slideUp();
        	$(this).removeClass('open');
		} else {
        	$('.eqm-more-info').removeClass('open');
        	$('.more-info-popup').slideUp();
            $(this).parents('.eqm-item').find('.more-info-popup').slideDown('medium');
            $(this).addClass('open');
		}
	});
    
    $(document).on('click', '#addEqmModal .eqm-info', function (e) {
    	e.preventDefault();
    	
    	var eqm_item = $(this).parents('.eqm-item');
    	var equipment_id = $(eqm_item).find('.equipment_id').val();
    	
    	if ($(eqm_item).hasClass('selected')) {
    		$('.equip-added-list').find('.equip-item-' + equipment_id).find('.rmv-equipment').trigger('click');
    		$(eqm_item).removeClass('selected');
    	} else {
    		if ($('.equip-added-list').find('.equipment-item-' + equipment_id).length == 0) {
	        	var equipment_name = $(eqm_item).find('.eqm-name').html();
	        	
	    		var html = '<div class="equip-item equip-item-' + equipment_id + '">';
	    		html += '<input type="hidden" class="equipment_id" name="equipment_id[]" value="' + equipment_id + '" />';
	    		html += '<p class="equipment-name">' + equipment_name + '</p>';
    			html += '<a class="rmv-equipment"><i class="fa fa-trash-o"></i></a>';
				html += '</div>';
	            
				$('.equip-added-list').append(html);
    		}
    		
    		$(eqm_item).addClass('selected');
    	}
    	
    	$('.save-new-wo').addClass('red');
	});
    
    $(document).on('click', '.m-create-wo .rmv-equipment', function (e) {
    	e.preventDefault();
    	
    	var equipment_id = $(this).parents('.equip-item').find('.equipment_id').val();
    	
    	$('#addEqmModal .eqm-item-' + equipment_id).removeClass('selected');
    	
    	$(this).parents('.equip-item').remove();
    	
    	$('.save-new-wo').addClass('red');
    });
    
    $(document).on('change', '.m-create-wo .add-wo-input, .m-create-wo .add-wo-textarea', function(e) {
    	$('.save-new-wo').addClass('red');
    });
    
    $(document).on('change', '.job-select', function(e) {
    	var option = $(this).find('option:selected');
    	
    	$(this).parents('.job-item').find('.est_hr').val($(option).attr('est_hr'));
    	
    	$('.save-new-wo').addClass('red');
    	$(this).parents('.new-wo-job-form').find('.save-this-new-job').addClass('red');
    });

    $(document).on('click', '.m-create-wo .add-job-btn', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	var filled = true;
    	$('.add-job-area .job-item').each(function() {
    		if ($.trim($(this).find('.job-name').val()) == '' || $(this).find('.job-select').val() == '') {
    			filled = false;
    		}
    	});
    	
    	if (!filled) {
    		return false;
    	}
    	
    	var job_index = 1;
    	var lastjob = $('.m-create-wo').find('.job-item').last();
    	if ($(lastjob).length > 0) {
    		job_index = parseInt($(lastjob).attr('job_index')) + 1;
    	}
		
		$.ajax({
		    type: "POST",
			url: "/workorder/add_wo_job",
		    dataType: 'json',
		    data: {
		    	wo_name: $('#wo-name').val(),
		    	job_index: job_index
		    },
			success: function(response) {
				if (response.status) {
					$('.add-job-area').append(response.html);
					$('.new-job-item').find('.job-select').select2({matcher: matchCustom}).on("select2:close", function(evt) { onTabSelect2(evt); });
					$('.new-job-item').find('.wo-job-notes').autogrow();
					$('.new-job-item').find('.part-item .part-select').select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
					//$('.chosen-container').css('width', '100%');
					$('.new-job-item').slideDown('medium').removeClass('new-job-item');
					
					$('.save-new-wo').addClass('red');
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$.notify(response.message, "warn");
				}
			}
	    });
	});

    $(document).on('click', '.add-new-wo-job', function(e) {
    	e.preventDefault();
    	var selected = $(this);
    	var job_item = $(selected).parents('.wo-job-item');
    	
		$.ajax({
		    type: "POST",
			url: "/workorder/add_wo_job",
		    dataType: 'json',
		    data: {
		    	wo_name: $(selected).parents('.m-wo-item').find('.wo-name').html(),
		    	wo_item_id: $(selected).attr('wo_item_id')
		    },
			success: function(response) {
				if (response.status) {
					$(job_item).html(response.html);
					$(job_item).find('.job-select').select2({matcher: matchCustom}).on("select2:close", function(evt) { onTabSelect2(evt); });
					$(job_item).find('.wo-job-notes').autogrow();
					$(job_item).find('.part-item .part-select').select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
					$(job_item).find('.new-job-item').slideDown('medium');
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$.notify(response.message, "warn");
				}
			}
	    });
	});
    
    $(document).on('change', '.new-wo-job-form .job-name, .new-wo-job-form .short-job-input, .new-wo-job-form .wo-job-notes', function() {
    	$('.new-wo-job-form .save-this-new-job').addClass('red');
    });
    
    $(document).on('click', '.delete-this-new-job', function(e) {
    	e.preventDefault();
    	
    	if (confirm('Are you sure you want to remove this job?')) {
    		var wo_item_id = $(this).attr('wo_item_id');
    		var job_item = $(this).parents('.wo-job-item');
    		$(job_item).slideUp('medium', function() {
    			$(job_item).html('<a href="#" class="add-new-wo-job wo-main-btn" wo_item_id="' + wo_item_id + '"><i class="fa fa-plus"></i> Add Job</a>');
    			$(job_item).slideDown('medium');
    		});
    	}
    });

    $(document).on('click', '.save-this-new-job', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	var job_list = $(this).parents('.wo-job-list');
    	var job_item = $(this).parents('.wo-job-item');
    	
    	$.ajax({
    	    type: "POST",
    	    url: "/workorder/create_wo_item_new_job",
    	    dataType: 'json',
    	    data: $(selected).parents('form.new-wo-job-form').serialize(),
    	    success: function(data) {
    		    if (data.status == 1) {
    		    	window.location.reload();
    		        
    		    } else {
    		    	if (data.reload) {
    		    		window.location.reload();
    		    	}
    		    	if (data.message) {
    		    		$.notify(data.message, {className: 'error', position: "right"});
    		    	}
    		    }
    	    }
        });
    });
    
    $(document).on('click', '.delete-this-job', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	$(selected).parents('.job-item').slideUp('medium', function() {
    		$(selected).parents('.job-item').remove();
    	});
	});
    
    $(document).on('change', '.part-select', function(e) {
    	var option = $(this).find('option:selected');
    	if ($.trim($(this).parents('.part-item').find('.add-part-qty').val()) == '') {
    		$(this).parents('.part-item').find('.add-part-qty').val(1);
    	}
    	
    	var cost = parseFloat($(option).attr('average_cost'));
    	if ($.trim(cost) == '' || !$.isNumeric(cost)) {
    		cost = 0;
    	}
    	$(this).parents('.part-item').find('.add-part-cost').val(cost.toFixed(2));
    	$(this).parents('.part-item').find('.add-part-total').val(($(this).parents('.part-item').find('.add-part-qty').val() * cost).toFixed(2));
    	
    	if ($(this).parents('.part-item').next().hasClass('add-new-part')) {
	    	$(this).parents('.part-item').find('.rmv-part-item').removeClass('hide');
	    	$('<div class="part-item added-new-part"></div>').insertBefore($('.add-new-part'));
	    	$('.added-new-part').html($('.add-new-part').html());
	    	$('.added-new-part').find('.part-select').select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
	    	$('.added-new-part').find('.part-select').focus();
	    	$('.added-new-part').removeClass('added-new-part');
    	}
    	
    	calculatePartTotal();
    	
    	$('.save-new-wo').addClass('red');
    	$(this).parents('.new-wo-job-form').find('.save-this-new-job').addClass('red');
    });
    
    $(document).on('focus', '.part-select', function (e) {
    	var select2 = $(this).data('select2');
    	select2.open();
	});
    
    $(document).on('change', '.add-part-cost, .add-part-qty', function(e) {
    	var qty = $(this).parents('.part-item').find('.add-part-qty').val();
    	var cost = $(this).parents('.part-item').find('.add-part-cost').val();
    	if ($.isNumeric(cost) && $.isNumeric(qty)) {
    		$(this).parents('.part-item').find('.add-part-total').val((parseFloat(qty) * parseFloat(cost)).toFixed(2));
    	}
    	
    	calculatePartTotal();
    	
    	$('.save-new-wo').addClass('red');
    	$(this).parents('.new-wo-job-form').find('.save-this-new-job').addClass('red');
    });
    
    $(document).on('click', '.rmv-part-item', function(e) {
    	e.preventDefault();
    	
    	$(this).parents('.part-item').remove();
    	
    	calculatePartTotal();
    	
    	$('.save-new-wo').addClass('red');
    	$(this).parents('.new-wo-job-form').find('.save-this-new-job').addClass('red');
    });
    
    $(document).on('click', '.m-create-wo .save-new-wo', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	var mobile = $(selected).attr('mobile');
		
		$.ajax({
		    type: "POST",
			url: "/workorder/create_wo_item",
		    dataType: 'json',
		    data: $('.create-wo-form').serialize(),
			success: function(response) {
				if (response.status) {
					$.notify(response.message, "success");
					$(selected).removeClass('red');
					
					setTimeout(function() {
						window.location = mobile == 1 ? '/mobile/workorder' : '/workorder';
                    }, 500);
					
				} else {
					if (response.timed_out) {
						window.location.reload();
					}
					$.notify(response.message, "warn");
				}
			}
	    });
	});
    
    $(document).on('click', '.specs-eqm', function(e) {
    	e.preventDefault();
    	
    	$(this).parents('.m-wo-item').find('.wo-list-specs').toggle('slide');
    });
    
 // Barcode scanner modal
	$(document).on('click', '.bar-scan', function(e) {
		e.preventDefault();
		
		var part_content = $(this).parents('.part-content');
		
		if ($(part_content).length > 0) {
			$(this).addClass('scanning');
			$('#scanBarCodeModal').find('.selected-parts').addClass('showing').show();
			
			var selected_part_list = '';
			$(part_content).find('.part-item').each(function() {
				var part_select = $(this).find('.part-select');
				if ($(part_select).val() != '') {
					var option = $(part_select).find('option:selected');
					var part_name = $(option).html() + '(' + $(option).attr('part_number') + ')';
					selected_part_list += renderSelectedPart($(part_select).val(), part_name, $(this).find('.add-part-qty').val(), $(this).find('.add-part-cost').val());
				}
			});
			$('#scanBarCodeModal').find('.selected-parts .list-part').html(selected_part_list);
			
			$('#scanBarCodeModal').find('.scan-list .part-list-result').html('');
			$('#scanBarCodeModal').find('.scan-list').removeClass('showing').hide();
					
			$('#scanBarCodeModal').find('.no-result').removeClass('showing').hide();
			
			$('#scanBarCodeModal').modal('show');
			$('#scanBarCodeModal .input-search-part').val('').focus();
		}
	});
	

	$(document).on('click', '#scanBarCodeModal .btn-rescan', function(e) {
		e.preventDefault();
		
		$('#scanBarCodeModal .selected-parts').html('');
		
    	$('#scanBarCodeModal .input-search-part').val('').focus();
	});
	
	$(document).on('click', '#scanBarCodeModal .btn-search-part', function(e) {
		e.preventDefault();
		
		onSearchPart();
	});
	
	$(document).on('keypress', '#scanBarCodeModal .input-search-part', function(e) {
		if (e.which == 13) {
			onSearchPart();
		}
	});
	
	$(document).on('click', '#scanBarCodeModal .part-result-item', function(e) {
		e.preventDefault();
		
		var part_id = $(this).attr('product_id');
		var part_name = $(this).find('.part-name').html();
		var oem_number = $(this).find('.oem-number').html();
		var average_cost = parseFloat($(this).attr('average_cost'));
		var found = false;
		
		$('#scanBarCodeModal .selected-parts .selected-part-item').each(function() {
			if ($(this).find('.part-id').val() == part_id) {
				found = true;
				var qty = parseInt($(this).find('input.part-qty').val());
				if (!qty || !$.isNumeric(qty)) {
					qty = 1;
				} else {
					qty = qty + 1;
				}
				$(this).find('input.part-qty').val(qty);
			}
		});
		
		if (!found) {
			if (oem_number) {
				part_name = part_name + ' (' + oem_number + ')';
			}
			var html = renderSelectedPart(part_id, part_name, 1, average_cost);
			$('#scanBarCodeModal .selected-parts .list-part').append(html);
		}
		
		$('#scanBarCodeModal').find('.selected-parts').show();
		$('#scanBarCodeModal').find('.scan-list').hide();
		$('#scanBarCodeModal').find('.no-result').hide();
		$('#scanBarCodeModal .input-search-part').val('').focus();
	});
	
	$(document).on('change', '#scanBarCodeModal input.part-qty', function(e) {
		if (!$.isNumeric($(this).val())) {
			$(this).val(1);
		}
		var qty = parseInt($(this).val());
		var cost = parseFloat($(this).parents('.selected-part-item').find('.part-cost').val());
		var total = qty * cost;
		//$(this).parents('.selected-part-item').find('.part-total').val(total.toFixed(2));
	});
	
	$(document).on('click', '#scanBarCodeModal .part-rmv', function(e) {
		e.preventDefault();
		
		$(this).parents('.selected-part-item').remove();
	});
	
	$(document).on('click', '#scanBarCodeModal .selected-parts .btn-cancel', function(e) {
		e.preventDefault();
		
		$('#scanBarCodeModal').modal('hide');
	});
	
	$(document).on('click', '#scanBarCodeModal .no-result .btn-cancel', function(e) {
		e.preventDefault();
		
		$('#scanBarCodeModal').find('.selected-parts').show();
		$('#scanBarCodeModal').find('.scan-list').hide();
		$('#scanBarCodeModal').find('.no-result').hide();
		$('#scanBarCodeModal .input-search-part').val('').focus();
	});
	
	$(document).on('click', '#scanBarCodeModal .btn-submit', function(e) {
		e.preventDefault();
		
		var part_content = $('.bar-scan.scanning').parents('.part-content');
		$(part_content).find('.part-item').each(function() {
			if (!$(this).hasClass('add-new-part')) {
				$(this).remove();
			}
		});
		
		var index = 1;
		$('#scanBarCodeModal .selected-parts .selected-part-item').each(function() {
			clonePartItem(part_content, index, $(this).find('.part-id').val(), $(this).find('.part-qty').val(), $(this).find('.part-cost').val());
			index ++;
		});
		
		$('<div class="part-item adding-by-scanner">' + $(part_content).find('.add-new-part').html() + '</div>').insertBefore($(part_content).find('.add-new-part'));
		$('.adding-by-scanner').find('.part-select').val('').select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
		$('.adding-by-scanner').removeClass('adding-by-scanner');
		
		calculatePartTotal($(part_content).find('.part-item-list'));
		
		$('.save-new-wo').addClass('red');
    	$(part_content).parents('.new-wo-job-form').find('.save-this-new-job').addClass('red');
		
		$('#scanBarCodeModal').modal('hide');
	});
	
	$(document).on('click', '#scanBarCodeModal .btn-add-part', function(e) {
		e.preventDefault();
		
		var selected = $(this);
    	$.ajax({
    	    type: "POST",
    	    url: "/ajax/add_part",
    	    dataType: 'json',
    	    data: $('#scanBarCodeModal').find('.new-part-form').serialize(),
    	    success: function(data) {
    		    if (data.status == 1) {
    		    	if (data.product_id) {
    		    		var product_name = data.product_name;
    		    		if (data.part_number) {
    		    			product_name = product_name + ' (' + data.part_number + ')';
    		    		}
    		    		var html = renderSelectedPart(data.product_id, product_name, 1, data.average_cost);
    		    		
    		    		var new_option = '<option value="' + data.product_id + '" average_cost="' + data.average_cost + '" part_number="' + data.part_number + '" product_numbers="">' + data.product_name + '</option>';
    		    		$('.part-select').append(new_option);
    		    		
    					$('#scanBarCodeModal .selected-parts .list-part').append(html);
    					$('#scanBarCodeModal').find('.selected-parts').show();
    					$('#scanBarCodeModal').find('.scan-list').hide();
    					$('#scanBarCodeModal').find('.no-result').hide();
    					$('#scanBarCodeModal .input-search-part').val('').focus();
    		    	}
    		    } else {
    		    	if (data.reload) {
    		    		window.location.reload();
    		    	}
    		    	if (data.message) {
    		    		$.notify(data.message, {className: 'error', position: "right"});
    		    	}
    		    }
    	    }
        });
	});
});

function clonePartItem(part_content, index, part_id, qty, cost) {
	var from_part = $(part_content).find('.add-new-part');
	var to_part = '<div class="part-item adding-by-scanner' + index + '">';
	to_part += $(from_part).html();
	to_part += '</div>';
	
	var total = cost * qty;
	$(to_part).insertBefore($(from_part));
	$('.adding-by-scanner' + index).find('.add-part-qty').val(qty);
	$('.adding-by-scanner' + index).find('.part-select').val(part_id).select2({matcher: matchPart}).on("select2:close", function(evt) { onPartSelect2(evt); });
	$('.adding-by-scanner' + index).find('.add-part-cost').val(cost);
	$('.adding-by-scanner' + index).find('.add-part-total').val(total.toFixed(2));
	$('.adding-by-scanner' + index).removeClass('adding-by-scanner' + index);
}

function renderSelectedPart(part_id, part_name, part_qty, part_cost) {
	part_cost = parseFloat(part_cost);
	if (!$.isNumeric(part_qty)) {
		part_qty = 1;
	}
	if (!part_cost || !$.isNumeric(part_cost)) {
		part_cost = 0;
	}
	var html = '';
	html += '<div class="selected-part-item">';
	html += '<input type="hidden" class="part-id" value="' + part_id + '" />';
	html += '<input type="text" class="selected-part-item-info part-qty" value="' + part_qty + '" />';
	html += '<p class="selected-part-item-info part-name">' + part_name + '</p>';
	html += '<input type="text" class="selected-part-item-info part-cost" value="' + part_cost.toFixed(2) + '" />';
	html += '<a class="part-rmv" href="#"><i class="fa fa-trash-o"></i></a>';
	html += '</div>';
	return html;
}

function onSearchPart() {
	$('#scanBarCodeModal').find('.selected-parts').hide();
	$('#scanBarCodeModal').find('.scan-list').show();
	$('#scanBarCodeModal').find('.no-result').hide();
	
	$.ajax({
	    type: "POST",
	    url: "/ajax/search_part",
	    dataType: "json",
	    data: {
	    	action: 'searchPart',
	    	term: $.trim($('#scanBarCodeModal .input-search-part').val())
	    },
	    beforeSend: function() {
	    	$('#scanBarCodeModal').find('.scan-list .part-list-result').html('<div align="center" style="height: 20%;"><img style="height: 100%;" src="/assets/img/loader.gif" alt="Loading data ..."/></div>');
	    },
	    success: function(response) {
		    if (response.status == 1) {
		    	if (response.html) {
		    		$('#scanBarCodeModal').find('.scan-list .part-list-result').html(response.html);
		    		
		    		if (response.count == 1) {
		    			$('#scanBarCodeModal').find('.scan-list .part-list-result .part-result-item').trigger('click');
		    		}
		    	} else {
		    		$('#scanBarCodeModal').find('.selected-parts').hide();
		    		
		    		$('#scanBarCodeModal').find('.scan-list .part-list-result').html('No data found.');
		    		$('#scanBarCodeModal').find('.scan-list').show();
		    		//$('#scanBarCodeModal').find('.no-result').show();
		    	}
		    	$('#scanBarCodeModal .input-search-part').val('').focus();
		    }
	    }
    });
}

function calculatePartTotal()
{
	$(".wo-job-item").each(function() {
		var total = 0;
		
		$(this).find(".job-part-total").each(function() {
			if ($.isNumeric($(this).html())) {
				total += parseFloat($(this).html());
			}
		});
		
		$(this).find(".part-total").html("Total Parts: " + total.toFixed(2));
	});
	
	$(".job-item").each(function() {
		var total = 0;
		
		$(this).find('.part-item').each(function() {
    		if ($.isNumeric($(this).find('.add-part-total').val())) {
				total += parseFloat($(this).find('.add-part-total').val());
			}
    	});
		
		$(this).find(".job-part-total").html(total.toFixed(2));
	});
}

function productOptions(products, selected_id)
{
	var html = '';
	for (var i in products) {
		var product = products[i];
		var product_id = product.id + (product.vari_id ? ('-' + product.vari_id) : '');
		var product_name = product.name + (product.product_number ? (' (' + product.product_number + ')') : '');
		
		if (product.remove == 0 || (selected_id && selected_id == product_id)) {
			html += '<option value="' + product_id + '" average_cost="' + product.average_cost + '"' 
				+ ((selected_id && (selected_id == product_id || selected_id == product.id)) ? ' selected' : '') + '>' 
				+ product_name + '</option>';
		}
	}
	return html;
}

function setupTimer(timer)
{	
	if (timer) {
		clearInterval(timer);
		timer = null;
	}
	
	if ($(".stop-btn").length > 0) {
		var timer_container = $(".stop-btn").parents(".wo-job-item").find(".job-countdown");
		
		timer = setInterval(function() {
			var time = parseInt($(timer_container).attr('time')) + 1;
			var minute = Math.floor((time%3600)/60);
			var second = Math.floor(time%60);
    		$(timer_container).attr('time', time).html(Math.floor(time/3600) + ':' + (minute >= 10 ? minute : ('0' + minute)) + ':' + (second >= 10 ? second : ('0' + second)));
    	}, 1000);
	}
	
	return timer;
}