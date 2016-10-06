$(document).ready(function() {
	// Establish ajax processing file location
	var processFile = 'system/utils/process_ajax.inc.php';
	var cxn_error = 'We are sorry, but an error has occurred.  Please try again, or check your internet connection if the problem persists.'
    var timeDelay = 530;

	// Establish validation regex's
	var ronumber_req    = /^[0-9]{1,}$/; // Must be all digits, and must have at least one digit entered
	var ro_date_req		= /^([0-1][0-9])\/([0-3][0-9])\/([0-9]{4})$/;
	//var labor_req   	= /^(?:|\d{1,5}(?:\.\d{2,2})?)$/;
	//var parts_req   	= /^(?:|\d{1,5}(?:\.\d{2,2})?)$/;
	var labor_req       = /^[0-9]+(\.[0-9][0-9])?$/; // New pattern: forces user to at least enter a zero
	var parts_req       = /^[0-9]+(\.[0-9][0-9])?$/; // New patter: forces user to at least enter a zero
	var validName		= /^[A-Za-z]+$/;
	var validEmail		= /(^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$)|(^N\/A$)/;
	var validPass		= /^(?=.*\d)(?=.*[@#\-_$%^&+=§!\?])(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z@#\-_$%^&+=§!\?]{8,}$/;
	var validZip        = /^[0-9]{5,5}$/;
	var validCity		= /^[A-Za-z ]+$/; // Any combination of letters and spaces
	
	// Get dealer info array from server via AJAX call. Will set var dealerInfo (array of dealer info)
	getDealerInfo();
	
	// Initialize change advisor dropdown functionality on initial page load
	initialize_advisor_dropdown();
	
	/*
	for(i=0; i<dealer_info.length; i++) {
		console.log('dealer_info['+i+']: ' + dealer_info[i]);
	}*/

	// The following code processes service entries from the main form via AJAX
	//$('form#service_form').submit(function(event) {
	$('body').on('click', 'form#service_form input[type="submit"]', function (event) {
		event.preventDefault();
		
		$('.form_error').hide();
		$('.ro_success').hide();

		// Define submit 'submit_name' var to ensure correct AJAX function is processed
		var submit_name = $(this).attr("name");
		console.log('submit_name: ' + submit_name);
			
			// Note: had to rewrite the value selectors as the below traditional method no longer would work
			/*
			var ronumber  = $('form#service_form input#ronumber').val();
			var ro_date   = $('form#service_form input#ro_date').val();
			var yearmodel = $('form#service_form select#yearmodel').val();
			var mileage   = $('form#service_form select#mileage').val();
			var vehicle   = $('form#service_form select#vehicle').val();
			var labor     = $('form#service_form input#labor').val();
			var parts     = $('form#service_form input#parts').val();
			var comment   = $('form#service_form textarea#comment').val();
			*/
			var ronumber 	= document.getElementById('ronumber').value	;
			var ro_date		= document.getElementById('ro_date').value	;
			var yearmodel 	= document.getElementById('yearmodel').value;
			var mileage 	= document.getElementById('mileage').value	;
			var vehicle		= document.getElementById('vehicle').value	;
			var labor 		= document.getElementById('labor').value	;
			var parts 		= document.getElementById('parts').value	;
			var comment		= document.getElementById('comment').value	;
			
			console.log('ronumber: ' + ronumber + 'ro_date: ' + ro_date + 'yearmodel: ' + yearmodel + 'mileage: ' + mileage + 'vehicle: ' + vehicle + 'labor: ' + labor + 'parts: ' + parts + 'comment: ' + comment);
			//return false;
			
			var svc_reg = [];
				$('input[id="svc_reg[]"]:checked').each(function(){
					svc_reg.push($(this).val());
				});

			var svc_add = [];
				$('input[id="svc_add[]"]:checked').each(function(){
					svc_add.push($(this).val());
				});

			var svc_dec = [];
				$('input[id="svc_dec[]"]:checked').each(function(){
					svc_dec.push($(this).val());
				});

			var svc_hidden = [];
				$('input[id="svc_hidden[]"]').each(function(){
					svc_hidden.push($(this).val());
				});

			/* Initialize error[] and focus[] arrays for ajax error feedback.
			 * Must leave inside current select so arrays re-initialize with each submit.
			 */
			var errors = [];
			var focus = [];

			if (!ronumber_req.test(ronumber)) {
				errors.push("ro_error");
				focus.push("ronumber");
			}

			if (!ro_date_req.test(ro_date)) {
				errors.push("date_error");
				focus.push("ro_date");
			}

			if (yearmodel == "") {
				errors.push("year_error");
				focus.push("yearmodel");
			}

			if (mileage == "") {
				errors.push("mileage_error");
				focus.push("mileagespreadID");
			}

			if (vehicle == "") {
				errors.push("vehicle_error");
				focus.push("singleissue");
			}

			if (!labor_req.test(labor)) {
				errors.push("labor_error");
				focus.push("labor");
			}

			if (!parts_req.test(parts)) {
				errors.push("parts_error");
				focus.push("parts");
			}

			if (svc_reg == '') {
				errors.push("service_error");
				focus.push("service_error");
			}

			if (labor > 1000 || parts > 1000) {
				if(!confirm('Your labor or parts figure is over $1,000.  Do you want to proceed?')) {
					return false;
				}
			}

			var action = 'ro_entry';

			var dataString = {action:action, submit_name:submit_name, ronumber:ronumber, ro_date:ro_date, yearmodel:yearmodel, mileage:mileage,
							  vehicle:vehicle, labor:labor, parts:parts, comment:comment, svc_reg:svc_reg,
							  svc_add:svc_add, svc_dec:svc_dec, svc_hidden:svc_hidden};

			console.log('datastring: ' , dataString);

			if(errors.length > 0 ){
				for(var i=0;i<errors.length;i++){
					// Had to change due to conflict with RO Search modal.  Reg service error displayed on it instead.
					//document.getElementById(errors[i]).style.display="inline";
					$('form#service_form small.form_error#' + errors[i]).show();
				}
				document.getElementById(focus[0]).focus();
				return false;
			}

			// If the request is a delete, make sure user confirms the action
			if(submit_name == 'delete_ro') {
				if(!confirm('Are you sure you want to delete the order?')) {
					return false;
				}
			}

			// Load the spinner to indicate processing
			$('div.loader_div').html('<div class="spinner">Loading...</div>');

			// The spinner is only removed once the ajax call is complete.
			setTimeout(ajaxCall, timeDelay);
			console.log('timeDelay: ' + timeDelay);

			// Save the ajax call as a function to execute within the setTimeout() function
			function ajaxCall() {
				$.ajax({
					type: "POST",
					url: processFile,
					data: dataString,
					cache: false,
					success: function(returndata){
						console.log(returndata);
						if (returndata == "error_ro_dupe") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_ro_dupe").style.display="inline";
							document.getElementById("ronumber").focus();
							alert('That repair order already exists!');
							console.log(returndata);
						} else if (returndata == "error_ro_insert") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_ro_insert").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_ro_delete") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_ro_delete").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_ro_update") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_ro_update").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_ro_delete_rule") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_ro_delete_rule").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_entry_validation") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_entry_validation").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_svc_insert") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_svc_insert").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_svc_delete") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_svc_delete").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_session_timeout") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							if(confirm('For security purposes, your session has timed out! \n Select \'Okay\' to be directed back to the login page.')) {
								window.location.assign('index.php');
							}
							console.log(returndata);
						} else if (returndata == "error_query") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_query").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else if (returndata == "error_login") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();
							alert('You are no longer logged in!');
							console.log(returndata);
						} else if (returndata == "error_survey_lock") {
							// Remove the loading div before the content is updated
							$('.loader_div').empty();

							document.getElementById("error_survey_lock").style.display="inline";
							document.getElementById("ronumber").focus();
							console.log(returndata);
						} else {
							if (($(window).width() > 767)) {
								if (submit_name == 'insert_ro') {
								
									// Remove the loading div before the content is updated
									$('.loader_div').empty();
									
									/*
									// Update page content with returndata
									$('#update_div1').html($('#update_div1' , returndata).html());
									$('#update_div2').html($('#update_div2' , returndata).html());
									$('#update_div3').html($('#update_div3' , returndata).html());
									$('#update_div4').html($('#update_div4' , returndata).html());
									$('#update_div5').remove();
									$( "#ro_success" ).fadeIn( 300 ).delay( 3500 ).fadeOut( 400 );
									$('input:checkbox').removeAttr('checked');
									$('input:text').val('');
									$('select#yearmodel').val('').prop('selected', true);
									$('select#mileagespreadID').val('').prop('selected', true);
									$('select#vehicle_make_id').val('').prop('selected', true);
									$('textarea').val('');
									*/
									
									// Replace page content with new form (easier than previous methods above and necessary for the update_div1 to show)
									$('div#page').html(returndata);
									
									// Display add msg
									$( "#ro_success" ).fadeIn( 300 ).delay( 3500 ).fadeOut( 400 );
									
									// Make sure that datepicker is initialized
									$('#ro_date').datepicker({
										dateFormat: 'mm/dd/yy',
										maxDate: "+0D"
									});
									
									// Focus cursor on first input element
									document.getElementById("ronumber").focus();
									
									// Note: Could not get to re-initialize without returning an empty <div></div> around all returndata
									$("#enterrotable").DataTable({
										paging: false,
										searching: false
									});
									
									// Make sure that hide/show button still works
									$("#hide_button").on("click", function() {
									  var el = $(this);
									  if (el.text() == el.data("text-swap")) {
										el.text(el.data("text-original"));
									  } else {
										el.data("text-original", el.text());
										el.text(el.data("text-swap"));
									  }
									  $("#hide").toggle(100);
									});
								} else if (submit_name == 'update_ro') {
									// Remove the loading div before the content is updated
									$('.loader_div').empty();

									// Replace page content with new RO form and table
									$('div#page').html(returndata);
									//alert('Ro successfully updated!');
									
									// Display update msg
									$( "#ro_update" ).fadeIn( 300 ).delay( 3500 ).fadeOut( 400 );

									// Make sure that datepicker is initialized
									$('#ro_date').datepicker({
										dateFormat: 'mm/dd/yy',
										maxDate: "+0D"
									});

									// Make sure that enterrotable is sortable etc.
									$("#enterrotable").DataTable({
										paging: false,
										searching: false
									});
									// Make sure that hide/show button still works
									$("#hide_button").on("click", function() {
									  var el = $(this);
									  if (el.text() == el.data("text-swap")) {
										el.text(el.data("text-original"));
									  } else {
										el.data("text-original", el.text());
										el.text(el.data("text-swap"));
									  }
									  $("#hide").toggle(100);
									});
								} else if (submit_name == 'delete_ro') {
									// Remove the loading div before the content is updated
									$('.loader_div').empty();

									// Replace page content with new RO form and table
									$('div#page').html(returndata);
									//alert('Ro successfully deleted!');
									
									// Display delete msg
									$( "#ro_delete" ).fadeIn( 300 ).delay( 3500 ).fadeOut( 400 );

									// Make sure that datepicker is initialized
									$('#ro_date').datepicker({
										dateFormat: 'mm/dd/yy',
										maxDate: "+0D"
									});

									// Make sure that enterrotable is sortable etc.
									$("#enterrotable").DataTable({
										paging: false,
										searching: false
									});
									// Make sure that hide/show button still works
									$("#hide_button").on("click", function() {
									  var el = $(this);
									  if (el.text() == el.data("text-swap")) {
										el.text(el.data("text-original"));
									  } else {
										el.data("text-original", el.text());
										el.text(el.data("text-swap"));
									  }
									  $("#hide").toggle(100);
									});
								}
							} else {
								window.location.reload(true);
							}
						}
					}, // end ajax success
					error: function(jqXHR, textStatus, errorThrown) {
						// Remove the loading div before the content is updated
						$('.loader_div').empty();
						alert(cxn_error);
					} // end ajax error
				});	// end $.ajax
			}	// end function ajaxCall
	}); // end main $('body'.on('click') selector
	
	// If user clicks 'view_ros_link', update page content based on retrieved var action
	$('body').on('click', 'a.view_ros_link', function(event) {
		event.preventDefault();
		
		// Set action based on name attribute
		var action = $(this).attr("name");
		console.log('action: ' + action);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with update RO form
					$('div#page').html(returndata);

					// Make sure that enterrotable is initialized
					$("#enterrotable").DataTable({
						paging: true,
						searching: true
					});
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}	
	});
	
	// If user clicks 'Dealer Comparison' link, perform search and update metrics listing
	$('body').on('click', '#metrics_dlr_comp_submit', function (event) {
		event.preventDefault();
		
		// hide any existing form errors
		$(".form_ro_search_error").hide();
		
		var formData = $("form#metrics_search_form").serialize();
		console.log('formData: ' + formData);
		
		var errors = [];
		var errors_show = [];
		
		// Set search_feedback array for building the search params to display to user
		var search_feedback = 'Filter Params: | ';
		
		// Set array for display of svc search parameters, to be added to the end of search_feedback
		var svc_search_feedback = [];
		
		// Get metrics dates
		var date1_pres = document.getElementById('metrics_dlr_comp_date1').value;
		var date2_pres = document.getElementById('metrics_dlr_comp_date2').value;
		console.log('date1_pres: ' + date1_pres);
		console.log('date2_pres: ' + date2_pres);
		
		// Get custom dealer list values
		var dealer_group = [];
		var dlr_group_id = [];
		var dlr_group_code = [];
		var dlr_group_name = [];
		$('select#metrics_dlr_comp_group :selected').each(function(i,selected){
			dealer_group[i]= $(selected).val();
			if(dealer_group[i] == '') {
				dealer_group = false; // This is dealer_group = false in all other code duplicates
			} else {
				dlr_group = dealer_group[i].split("#");
				dlr_group_id[i] = dlr_group[0];
				dlr_group_code[i] = dlr_group[1];
				dlr_group_name[i] = dlr_group[2];
			}
			console.log('selected dealer: ' + dealer_group[i] + 'dlr_group_id: ' + dlr_group_id[i] + 'dlr_group_code: ' + dlr_group_code[i] + 'dlr_group_name: ' + dlr_group_name[i]);
		});
		//return false;
		
		// Get checkbox attribute (View All Dealers & All History)
		var all_dealers_checkbox = document.getElementById('dlr_comp_checkbox');
		if ($('#dlr_comp_checkbox').prop("checked")) {
			var all_dealers_checkbox = true;
		} else {
			var all_dealers_checkbox = false;
		}
		console.log('all_dealers_checkbox: ' + all_dealers_checkbox);
		
		if (date1_pres == '' && date2_pres == '') {
			date1_pres = false;
			date2_pres = false;
		} else if ((date1_pres == '' && date2_pres != '') || (date1_pres != '' && date2_pres == '')){
			errors.push('You left a date field blank!');
			errors_show.push('metrics_dlr_comp_date_error');
		} else {
			if(!ro_date_req.test(date1_pres) || !ro_date_req.test(date2_pres)) {
				errors.push('You entered an invalid date!');
				errors_show.push('metrics_dlr_comp_date_error');
			} 
		}
		
		// Build date search feedback message
		if (date1_pres != false && date2_pres != false) {
			search_feedback += 'Date Range = ' + date1_pres + ' - ' + date2_pres + ' | ';
		} else {
			search_feedback += 'Showing: All History | ';
		}
		
		// Build dealer code feedback comma-delimited list.  Do not want to show search feedback for dealer group (already in tables)
		if (dealer_group != false) {
			dealer_group_feedback = '';
			for(i=0; i<dealer_group.length; i++) {
				if(i == dealer_group.length-1) {
					dealer_group_feedback += dlr_group_code[i];
				} else {
					dealer_group_feedback += dlr_group_code[i] + " - ";
				}
			}
			search_feedback += 'Dealer Group = ' + dealer_group_feedback + ' | ';
		} else {
			search_feedback += 'Showing: All Dealers | ';
		}
		//return false;
		
		// Display form errors
		var error_msg = '';
		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				error_msg += errors[i] + '\n';
				document.getElementById(errors_show[i]).style.display="inline";
			}
			alert(error_msg);
			return false;
		}
		
		// If no selection was made at all, do not submit the form
		if (date1_pres == false && date2_pres == false && metrics_dealer_group == false && dlr_comp_checkbox == false) {
			alert('You did not make a form selection!');
			return false;
		}
		
		// Set action
		var action = 'metrics_dlr_comp';
		
		// Set dlr_comp POST variable so that correct page heading is displayed
		var dlr_comp = true;
		
		/* Create object array of form input data.  The POST names must match the names given in the Metrics class logic (i.e. region_id)
		 * DO NOT name the dealer group selection as 'dealer_group'. This will get passed to getQueryStmtParams and created incorrect # params error.
		 * In this case, you want to name it something original so that it does not affect subsequent processing.
		 * Each dealer which is selected (if any at all) will be processed inside a loop as $array['dealer_id'] instead for
		 * the production of an array of metrics trend data for each dealer which was selected
		**/
		var Data = { search_feedback:search_feedback, date1_pres:date1_pres, date2_pres:date2_pres,
					 dealer_group:dealer_group, dealer_id_list:dlr_group_id, dealer_code_list:dlr_group_code, 
					 dealer_name_list:dlr_group_name, dlr_comp:dlr_comp
				   };
					 		 
		console.log('JSON.strigify(Data): ' , JSON.stringify(Data));
		//return false;
		
		// Hide the form modal
		$('#metrics_dlr_comp_modal').foundation('reveal', 'close');
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&params=' + JSON.stringify(Data),
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);

					// Re-initialize table functionality for relevant tables
					$('#metrics_dlr_comp_date1').datepicker({
						dateFormat: 'mm/dd/yy',
						maxDate: "+0D"
					});
		
					$('#metrics_dlr_comp_date2').datepicker({
						dateFormat: 'mm/dd/yy',
						maxDate: "+0D"
					});
					
					// Initialize table functionality for all tables
					$("#sales_table").DataTable({
					   "scrollX": true,	
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#close_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#freq_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#req_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#add_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#dec_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#comp_labor_parts_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Filter Metrics' link, perform search and update metrics listing
	$('body').on('click', '#metrics_search_submit', function (event) {
		event.preventDefault();
		
		// hide any existing form errors
		$(".form_ro_search_error").hide();
		
		var formData = $("form#metrics_search_form").serialize();
		console.log('formData: ' + formData);
		
		var errors = [];
		var errors_show = [];
		
		// Set search_feedback array for building the search params to display to user
		var search_feedback = 'Filter Params: | ';
		
		// Set array for display of svc search parameters, to be added to the end of search_feedback
		var svc_search_feedback = [];
		var svc_exclude_feedback = [];
		
		// Get metrics dates
		var metrics_date1_pres = document.getElementById('metrics_date1').value;
		var metrics_date2_pres = document.getElementById('metrics_date2').value;
		console.log('metrics_date1_pres: ' + metrics_date1_pres);
		console.log('metrics_date2_pres: ' + metrics_date2_pres);
		
		// Get advisor values
		var metrics_advisor1       = document.getElementById('metrics_advisor1').value.split(",");
		var metrics_advisor1_id    = metrics_advisor1[0];
		var metrics_advisor1_label = metrics_advisor1[1];
		console.log('metrics_advisor1_id: ' + metrics_advisor1_id)
		console.log('metrics_advisor1_label: ' + metrics_advisor1_label);
		
		// Get custom dealer list values
		var dealer_group = [];
		var dlr_group_id = [];
		var dlr_group_code = [];
		var dlr_group_name = [];
		$('select#metrics_dealer_group :selected').each(function(i,selected){
			dealer_group[i]= $(selected).val();
			if(dealer_group[i] == '') {
				dealer_group = false;
			} else {
				dlr_group = dealer_group[i].split("#");
				dlr_group_id[i] = dlr_group[0];
				dlr_group_code[i] = dlr_group[1];
				dlr_group_name[i] = dlr_group[2];
			}
			console.log('selected dealer: ' + dealer_group[i] + 'dlr_group_id: ' + dlr_group_id[i] + 'dlr_group_code: ' + dlr_group_code[i] + 'dlr_group_name: ' + dlr_group_name[i]);
		});
		//return false;
		
		/*
		var metrics_dealer1       = document.getElementById('metrics_dealer1').value.split(",");
		var metrics_dealer1_id    = metrics_dealer1[0];
		var metrics_dealer1_code = metrics_dealer1[1];
		var metrics_dealer1_name = metrics_dealer1[2];
		console.log('metrics_dealer1_id: ' + metrics_dealer1_id)
		console.log('metrics_dealer1_code: ' + metrics_dealer1_code);
		console.log('metrics_dealer1_name: ' + metrics_dealer1_name);
		*/
		
		// Get region values
		var metrics_region1       = document.getElementById('metrics_region1').value.split(",");
		var metrics_region1_id    = metrics_region1[0];
		var metrics_region1_label = metrics_region1[1];
		console.log('metrics_region1_id: ' + metrics_region1_id)
		console.log('metrics_region1_label: ' + metrics_region1_label);
		
		// Get area values
		var metrics_area1       = document.getElementById('metrics_area1').value.split(",");
		var metrics_area1_id    = metrics_area1[0];
		var metrics_area1_label = metrics_area1[1];
		console.log('metrics_area1_id: ' + metrics_area1_id)
		console.log('metrics_area1_label: ' + metrics_area1_label);
		
		// Get district values
		var metrics_district1       = document.getElementById('metrics_district1').value.split(",");
		var metrics_district1_id    = metrics_district1[0];
		var metrics_district1_label = metrics_district1[1];
		console.log('metrics_district1_id: ' + metrics_district1_id)
		console.log('metrics_district1_label: ' + metrics_district1_label);
		
		// Get checkbox attribute (View All Dealers)
		var all_dealers_checkbox = document.getElementById('metrics_search_checkbox');
		if ($('#metrics_search_checkbox').prop("checked")) {
			var all_dealers_checkbox = true;
		} else {
			var all_dealers_checkbox = false;
		}
		console.log('all_dealers_checkbox: ' + all_dealers_checkbox);
		
		if (metrics_date1_pres == '' && metrics_date2_pres == '') {
			metrics_date1_pres = false;
			metrics_date2_pres = false;
		} else if ((metrics_date1_pres == '' && metrics_date2_pres != '') || (metrics_date1_pres != '' && metrics_date2_pres == '')){
			errors.push('You left a date field blank!');
			errors_show.push('metrics_date_error');
		} else {
			if(!ro_date_req.test(metrics_date1_pres) || !ro_date_req.test(metrics_date2_pres)) {
				errors.push('You entered an invalid date!');
				errors_show.push('metrics_date_error');
			} 
		}
		if (metrics_date1_pres != false && metrics_date2_pres != false) {
			search_feedback += 'Date Range = ' + metrics_date1_pres + ' - ' + metrics_date2_pres + ' | ';
		}
		
		if (metrics_advisor1_id == '') {
			metrics_advisor1_id = false;
		} else {
			search_feedback += 'Advisor = ' + metrics_advisor1_label + ' | ';
		}
		
		/*
		if (metrics_dealer1_id == '') {
			metrics_dealer1_id = false;
		} else {
			search_feedback += 'Dealer = ' + metrics_dealer1_name + ' | ';
		}
		*/
		
		if (metrics_region1_id == '') {
			metrics_region1_id = false;
		} else {
			search_feedback += 'Region = ' + metrics_region1_label + ' | ';
		}
		
		if (metrics_area1_id == '') {
			metrics_area1_id = false;
		} else {
			search_feedback += 'Area = ' + metrics_area1_label + ' | ';
		}
		
		if (metrics_district1_id == '') {
			metrics_district1_id = false;
		} else {
			search_feedback += 'District = ' + metrics_district1_label + ' | ';
		}
		
		// Build dealer code feedback comma-delimited list
		if (dealer_group != false) {
			dealer_group_feedback = '';
			for(i=0; i<dealer_group.length; i++) {
				if(i == dealer_group.length-1) {
					dealer_group_feedback += dlr_group_code[i];
				} else {
					dealer_group_feedback += dlr_group_code[i] + " - ";
				}
			}
			search_feedback += 'Dealer Group = ' + dealer_group_feedback + ' | ';
		}
		//return false;
		
		if (all_dealers_checkbox == true) {
			search_feedback += 'All Dealers | ';
		}
		
		// Display form errors
		var error_msg = '';
		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				error_msg += errors[i] + '\n';
				document.getElementById(errors_show[i]).style.display="inline";
			}
			alert(error_msg);
			return false;
		}
		
		// If no selection was made at all, do not submit the form
		if (metrics_date1_pres == false && metrics_date2_pres == false && metrics_advisor1_id == false &&
			metrics_region1_id == false && metrics_area1_id == false && metrics_district1_id == false &&
			metrics_dealer_group == false ) {
			alert('You did not make a form selection!');
			return false;
		}
		
		// Set action
		var action = 'metrics_search';
		
		// Create object array of form input data.  The POST names must match the names given in the Metrics class logic (i.e. region_id)
		var Data = { search_feedback:search_feedback, date1_pres:metrics_date1_pres, date2_pres:metrics_date2_pres,
					 advisor_id:metrics_advisor1_id, advisor_name:metrics_advisor1_label, 
					 region_id:metrics_region1_id, region_name:metrics_region1_label,
					 area_id:metrics_area1_id, area_name:metrics_area1_label, all_dealers_checkbox:all_dealers_checkbox,
					 district_id:metrics_district1_id, district_name:metrics_district1_label,
					 dealer_group:dlr_group_id
				   };
					 		 
		console.log('JSON.strigify(Data): ' , JSON.stringify(Data));
		//return false;
		
		// Hide the form modal
		$('#metrics_search_modal').foundation('reveal', 'close');
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&metrics_params=' + JSON.stringify(Data),
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);

					// Re-initialize table functionality for both tables
					$("#L1_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#L2_3_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#labor_parts_table").DataTable({
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}				 
	});
	
	// If user clicks 'Filter Stats' link, perform search and update stats listing
	$('body').on('click', '#stats_search_submit', function (event) {
		event.preventDefault();
		
		// hide any existing form errors
		$(".form_ro_search_error").hide();
		
		var formData = $("form#stats_search_form").serialize();
		console.log('formData: ' + formData);
		
		var errors = [];
		var errors_show = [];
		
		// Set search_feedback array for building the search params to display to user
		var search_feedback = 'Filter Params: | ';
		
		// Set array for display of svc search parameters, to be added to the end of search_feedback
		var svc_search_feedback = [];
		var svc_exclude_feedback = [];
		
		// Get metrics dates
		var date1_pres = document.getElementById('stats_date1').value;
		var date2_pres = document.getElementById('stats_date2').value;
		console.log('date1_pres: ' + date1_pres);
		console.log('date2_pres: ' + date2_pres);
		
		// Get advisor values
		var advisor1       = document.getElementById('stats_advisor1').value.split(",");
		var advisor1_id    = advisor1[0];
		var advisor1_label = advisor1[1];
		console.log('advisor1_id: ' + advisor1_id)
		console.log('advisor1_label: ' + advisor1_label);
		
		// Get custom dealer list values
		var dealer_group = [];
		var dlr_group_id = [];
		var dlr_group_code = [];
		var dlr_group_name = [];
		$('select#stats_dealer_group :selected').each(function(i,selected){
			dealer_group[i]= $(selected).val();
			if(dealer_group[i] == '') {
				dealer_group = false;
			} else {
				dlr_group = dealer_group[i].split("#");
				dlr_group_id[i] = dlr_group[0];
				dlr_group_code[i] = dlr_group[1];
				dlr_group_name[i] = dlr_group[2];
			}
			console.log('selected dealer: ' + dealer_group[i] + 'dlr_group_id: ' + dlr_group_id[i] + 'dlr_group_code: ' + dlr_group_code[i] + 'dlr_group_name: ' + dlr_group_name[i]);
		});
		
		// Get dealer values
		/*
		var metrics_dealer1       = document.getElementById('metrics_dealer1').value.split(",");
		var metrics_dealer1_id    = metrics_dealer1[0];
		var metrics_dealer1_code = metrics_dealer1[1];
		var metrics_dealer1_name = metrics_dealer1[2];
		console.log('metrics_dealer1_id: ' + metrics_dealer1_id)
		console.log('metrics_dealer1_code: ' + metrics_dealer1_code);
		console.log('metrics_dealer1_name: ' + metrics_dealer1_name);
		*/
		
		// Get region values
		var region1       = document.getElementById('stats_region1').value.split(",");
		var region1_id    = region1[0];
		var region1_label = region1[1];
		console.log('region1_id: ' + region1_id)
		console.log('region1_label: ' + region1_label);
		
		// Get area values
		var area1       = document.getElementById('stats_area1').value.split(",");
		var area1_id    = area1[0];
		var area1_label = area1[1];
		console.log('area1_id: ' + area1_id)
		console.log('area1_label: ' + area1_label);
		
		// Get district values
		var district1       = document.getElementById('stats_district1').value.split(",");
		var district1_id    = district1[0];
		var district1_label = district1[1];
		console.log('district1_id: ' + district1_id)
		console.log('district1_label: ' + district1_label);
		
		// Get checkbox attribute (View All Dealers)
		var all_dealers_checkbox = document.getElementById('stats_search_checkbox');
		if ($('#stats_search_checkbox').prop("checked")) {
			var all_dealers_checkbox = true;
		} else {
			var all_dealers_checkbox = false;
		}
		console.log('all_dealers_checkbox: ' + all_dealers_checkbox);
		
		if (date1_pres == '' && date2_pres == '') {
			date1_pres = false;
			date2_pres = false;
		} else if ((date1_pres == '' && date2_pres != '') || (date1_pres != '' && date2_pres == '')){
			errors.push('You left a date field blank!');
			errors_show.push('stats_date_error');
		} else {
			if(!ro_date_req.test(date1_pres) || !ro_date_req.test(date2_pres)) {
				errors.push('You entered an invalid date!');
				errors_show.push('stats_date_error');
			} 
		}
		if (date1_pres != false && date2_pres != false) {
			search_feedback += 'Date Range = ' + date1_pres + ' - ' + date2_pres + ' | ';
		}
		
		if (advisor1_id == '') {
			advisor1_id = false;
		} else {
			search_feedback += 'Advisor = ' + advisor1_label + ' | ';
		}
		
		/*
		if (metrics_dealer1_id == '') {
			metrics_dealer1_id = false;
		} else {
			search_feedback += 'Dealer = ' + metrics_dealer1_name + ' | ';
		}
		*/
		
		if (region1_id == '') {
			region1_id = false;
		} else {
			search_feedback += 'Region = ' + region1_label + ' | ';
		}
		
		if (area1_id == '') {
			area1_id = false;
		} else {
			search_feedback += 'Area = ' + area1_label + ' | ';
		}
		
		if (district1_id == '') {
			district1_id = false;
		} else {
			search_feedback += 'District = ' + district1_label + ' | ';
		}
		
		// Build dealer code feedback comma-delimited list
		if (dealer_group != false) {
			dealer_group_feedback = '';
			for(i=0; i<dealer_group.length; i++) {
				if(i == dealer_group.length-1) {
					dealer_group_feedback += dlr_group_code[i];
				} else {
					dealer_group_feedback += dlr_group_code[i] + " - ";
				}
			}
			search_feedback += 'Dealer Group = ' + dealer_group_feedback + ' | ';
		}
		
		if (all_dealers_checkbox == true) {
			search_feedback += 'All Dealers | ';
		}
		
		// Display form errors
		var error_msg = '';
		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				error_msg += errors[i] + '\n';
				document.getElementById(errors_show[i]).style.display="inline";
			}
			alert(error_msg);
			return false;
		}
		
		/* If no selection was made at all, do not submit the form
		 * Thought about allowing only the 'View All Dealers' checkbox to be checked
		 * but this messes up the Service Breakdown numbers.
		 * So decided to require at least one other filter to be selected in order to run report
		**/
		if (date1_pres == false && date2_pres == false && advisor1_id == false &&
			region1_id == false && area1_id == false && district1_id == false && 
			dealer_group == false ) {
			alert('You must select at least one filter!');
			return false;
		}
		
		// Set action
		var action = 'stats_search';
		
		// Create object array of form input data.  The POST names must match the names given in the Metrics class logic (i.e. region_id)
		var Data = { search_feedback:search_feedback, date1_pres:date1_pres, date2_pres:date2_pres,
					 advisor_id:advisor1_id, advisor_name:advisor1_label, all_dealers_checkbox:all_dealers_checkbox,
					 region_id:region1_id, region_name:region1_label,
					 area_id:area1_id, area_name:area1_label,
					 district_id:district1_id, district_name:district1_label,
					 dealer_group:dealer_group
				   };
					 		 
		console.log('JSON.strigify(Data): ' , JSON.stringify(Data));
		//return false;
		
		// Hide the form modal
		$('#stats_search_modal').foundation('reveal', 'close');
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&search_params=' + JSON.stringify(Data),
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);

					// Re-initialize table functionality for all stats tables
					$("#svc_table").DataTable({
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
		
				    $("#lof_table").DataTable({
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
				    
				    $("#vehicle_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#my_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#ms_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#ro_trend_table").DataTable({
				      "scrollX": true,
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}				 
	});
	
	// If user clicks 'Metrics Trending' link, perform trending and display results
	$('body').on('click', '#metrics_trend_submit', function (event) {
		event.preventDefault();
		
		// hide any existing form errors
		$(".form_ro_search_error").hide();
		
		var formData = $("form#metrics_trend_form").serialize();
		console.log('formData: ' + formData);
		
		var errors = [];
		var errors_show = [];
		
		// Set search_feedback array for building the search params to display to user
		var search_feedback = 'Filter Params: | ';
		
		// Set array for display of svc search parameters, to be added to the end of search_feedback
		var svc_search_feedback = [];
		var svc_exclude_feedback = [];
		
		// Get metrics dates
		var date1_pres = document.getElementById('metrics_trend_date1').value;
		var date2_pres = document.getElementById('metrics_trend_date2').value;
		console.log('date1_pres: ' + date1_pres);
		console.log('date2_pres: ' + date2_pres);
		
		// Get advisor values
		var advisor1       = document.getElementById('metrics_trend_advisor1').value.split(",");
		var advisor1_id    = advisor1[0];
		var advisor1_label = advisor1[1];
		console.log('advisor1_id: ' + advisor1_id)
		console.log('advisor1_label: ' + advisor1_label);
		
		// Get dealer values
		/*
		var metrics_dealer1       = document.getElementById('metrics_dealer1').value.split(",");
		var metrics_dealer1_id    = metrics_dealer1[0];
		var metrics_dealer1_code = metrics_dealer1[1];
		var metrics_dealer1_name = metrics_dealer1[2];
		console.log('metrics_dealer1_id: ' + metrics_dealer1_id)
		console.log('metrics_dealer1_code: ' + metrics_dealer1_code);
		console.log('metrics_dealer1_name: ' + metrics_dealer1_name);
		*/
		
		// Get region values
		var region1       = document.getElementById('metrics_trend_region1').value.split(",");
		var region1_id    = region1[0];
		var region1_label = region1[1];
		console.log('region1_id: ' + region1_id)
		console.log('region1_label: ' + region1_label);
		
		// Get area values
		var area1       = document.getElementById('metrics_trend_area1').value.split(",");
		var area1_id    = area1[0];
		var area1_label = area1[1];
		console.log('area1_id: ' + area1_id)
		console.log('area1_label: ' + area1_label);
		
		// Get district values
		var district1       = document.getElementById('metrics_trend_district1').value.split(",");
		var district1_id    = district1[0];
		var district1_label = district1[1];
		console.log('district1_id: ' + district1_id)
		console.log('district1_label: ' + district1_label);
		
		// Get custom dealer list values
		var dealer_group = [];
		var dlr_group_id = [];
		var dlr_group_code = [];
		var dlr_group_name = [];
		$('select#metrics_trend_group :selected').each(function(i,selected){
			dealer_group[i]= $(selected).val();
			if(dealer_group[i] == '') {
				dealer_group = false;
			} else {
				dlr_group = dealer_group[i].split("#");
				dlr_group_id[i] = dlr_group[0];
				dlr_group_code[i] = dlr_group[1];
				dlr_group_name[i] = dlr_group[2];
			}
			console.log('selected dealer: ' + dealer_group[i] + 'dlr_group_id: ' + dlr_group_id[i] + 'dlr_group_code: ' + dlr_group_code[i] + 'dlr_group_name: ' + dlr_group_name[i]);
		});
		
		// Get checkbox attribute (View All Dealers)
		var all_dealers_checkbox = document.getElementById('metrics_trend_checkbox');
		if ($('#metrics_trend_checkbox').prop("checked")) {
			var all_dealers_checkbox = true;
		} else {
			var all_dealers_checkbox = false;
		}
		console.log('all_dealers_checkbox: ' + all_dealers_checkbox);
		
		if (date1_pres == '' && date2_pres == '') {
			date1_pres = false;
			date2_pres = false;
		} else if ((date1_pres == '' && date2_pres != '') || (date1_pres != '' && date2_pres == '')){
			errors.push('You left a date field blank!');
			errors_show.push('metrics_trend_date_error');
		} else {
			if(!ro_date_req.test(date1_pres) || !ro_date_req.test(date2_pres)) {
				errors.push('You entered an invalid date!');
				errors_show.push('metrics_trend_date_error');
			} 
		}
		if (date1_pres != false && date2_pres != false) {
			search_feedback += 'Date Range = ' + date1_pres + ' - ' + date2_pres + ' | ';
		}
		
		if (advisor1_id == '') {
			advisor1_id = false;
		} else {
			search_feedback += 'Advisor = ' + advisor1_label + ' | ';
		}
		
		/*
		if (metrics_dealer1_id == '') {
			metrics_dealer1_id = false;
		} else {
			search_feedback += 'Dealer = ' + metrics_dealer1_name + ' | ';
		}
		*/
		
		if (region1_id == '') {
			region1_id = false;
		} else {
			search_feedback += 'Region = ' + region1_label + ' | ';
		}
		
		if (area1_id == '') {
			area1_id = false;
		} else {
			search_feedback += 'Area = ' + area1_label + ' | ';
		}
		
		if (district1_id == '') {
			district1_id = false;
		} else {
			search_feedback += 'District = ' + district1_label + ' | ';
		}
		
		// Build dealer code feedback comma-delimited list
		if (dealer_group != false) {
			dealer_group_feedback = '';
			for(i=0; i<dealer_group.length; i++) {
				if(i == dealer_group.length-1) {
					dealer_group_feedback += dlr_group_code[i];
				} else {
					dealer_group_feedback += dlr_group_code[i] + " - ";
				}
			}
			search_feedback += 'Dealer Group = ' + dealer_group_feedback + ' | ';
		}
		
		if (all_dealers_checkbox == true) {
			search_feedback += 'All Dealers | ';
		}
		
		// Display form errors
		var error_msg = '';
		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				error_msg += errors[i] + '\n';
				document.getElementById(errors_show[i]).style.display="inline";
			}
			alert(error_msg);
			return false;
		}
		
		// If no date was selected, do not allow form submit (trending must always have a date range)
		if (date1_pres == false && date2_pres == false) {
			alert('You muse enter a date range for trending!');
			return false;
		}
		
		// If no selection was made at all, do not submit the form
		if (date1_pres == false && date2_pres == false && advisor1_id == false &&
			region1_id == false && area1_id == false &&
			district1_id == false ) {
			alert('You did not make a form selection!');
			return false;
		}
		
		// Set action
		var action = 'metrics_trend';
		
		// Create object array of form input data.  The POST names must match the names given in the Metrics class logic (i.e. region_id)
		var Data = { search_feedback:search_feedback, date1_pres:date1_pres, date2_pres:date2_pres,
					 advisor_id:advisor1_id, advisor_name:advisor1_label, 
					 region_id:region1_id, region_name:region1_label,
					 area_id:area1_id, area_name:area1_label, all_dealers_checkbox:all_dealers_checkbox,
					 district_id:district1_id, district_name:district1_label, dealer_group:dlr_group_id
				   };
					 		 
		console.log('JSON.strigify(Data): ' , JSON.stringify(Data));
		//return false;
		
		// Hide the form modal
		$('#metrics_trend_modal').foundation('reveal', 'close');
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);
		
		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&params=' + JSON.stringify(Data),
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);

					// Initialize table functionality for all stats tables
					$("#sales_table").DataTable({
					   "scrollX": true,	
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#close_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#freq_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#req_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#add_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#dec_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#comp_labor_parts_table").DataTable({
				       "scrollX": true,
					   paging: false,
					   searching: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}				 
	});		
		
	// If user clicks 'Advanced Search' link, perform search and update RO listing
	$('body').on('click', '#ro_search_submit', function (event) {
		event.preventDefault();
		
		// hide any existing form errors
		$(".form_ro_search_error").hide();
		
		var formData = $("form#ro_search_form").serialize();
		console.log('formData: ' + formData);
		
		var errors = [];
		var errors_show = [];
		
		// Set search_feedback array for building the search params to display to user
		var search_feedback = 'Search Params: ';
		
		// Set array for display of svc search parameters, to be added to the end of search_feedback
		var svc_search_feedback = [];
		var svc_exclude_feedback = [];
		
		// Get RO numbers
		var ro_num1 = document.getElementById('ro_num1').value;
		var ro_num2 = document.getElementById('ro_num2').value;
		console.log('ro_num1: ' + ro_num1);
		console.log('ro_num2: ' + ro_num2);
		
		// Get RO dates
		var ro_date1 = document.getElementById('ro_date1').value;
		var ro_date2 = document.getElementById('ro_date2').value;
		console.log('ro_date1: ' + ro_date1);
		console.log('ro_date2: ' + ro_date2);
		
		// Get year values
		var year1       = document.getElementById('year1').value.split(" ");
		var year1_id    = year1[0];
		var year1_label = year1[1];
		console.log('year1_id: ' + year1_id);
		console.log('year1_label: ' + year1_label);
		var year2= document.getElementById('year2').value.split(" ");
		var year2_id    = year2[0];
		var year2_label = year2[1];
		console.log('year2_id: ' + year2_id);
		console.log('year2_label: ' + year2_label);
		
		// Get mileage values
		var mileage1       = document.getElementById('mileage1').value.split(",");
		var mileage1_id    = mileage1[0];
		var mileage1_label = mileage1[1];
		console.log('mileage1_id: ' + mileage1_id);
		console.log('mileage1_label: ' + mileage1_label);
		var mileage2= document.getElementById('mileage2').value.split(",");
		var mileage2_id    = mileage2[0];
		var mileage2_label = mileage2[1];
		console.log('mileage2_id: ' + mileage2_id);
		console.log('mileage2_label: ' + mileage2_label);
		
		// Get labor values
		var labor1 = document.getElementById('labor1').value;
		var labor2 = document.getElementById('labor2').value;
		console.log('labor1: ' + labor1);
		console.log('labor2: ' + labor2);
		
		// Get parts values
		var parts1 = document.getElementById('parts1').value;
		var parts2 = document.getElementById('parts2').value;
		console.log('parts1: ' + parts1);
		console.log('parts2: ' + parts2);
		
		// Get vehicle values
		var vehicle1       = document.getElementById('vehicle1').value.split(",");
		var vehicle1_id    = vehicle1[0];
		var vehicle1_label = vehicle1[1];
		console.log('vehicle1_id: ' + vehicle1_id);
		console.log('vehicle1_label: ' + vehicle1_label);
		
		// Get advisor values
		var advisor1       = document.getElementById('advisor1').value.split(",");
		var advisor1_id    = advisor1[0];
		var advisor1_label = advisor1[1];
		console.log('advisor1_id: ' + advisor1_id)
		console.log('advisor1_label: ' + advisor1_label);
		
		// Get inclusive service values. Set to string 'false' if emtpy, so that php will not read "" or null from just false
		var svc_reg = [];
		$('form#ro_search_form input[id="svc_reg"]:checked').each(function(){
			svc_reg.push($(this).val());
			svc_search_feedback.push($(this).val());
		});
		if(svc_reg.length < 1) {
			svc_reg = 'false';
		}
		console.log('svc_reg: ' + svc_reg);

		var svc_add = [];
		$('form#ro_search_form input[id="svc_add"]:checked').each(function(){
			svc_add.push($(this).val());
			svc_search_feedback.push($(this).val() + 'add');
		});
		if(svc_add.length < 1) {
			svc_add = 'false';
		}
		console.log('svc_add: ' + svc_add);

		var svc_dec = [];
		$('form#ro_search_form input[id="svc_dec"]:checked').each(function(){
			svc_dec.push($(this).val());
			svc_search_feedback.push($(this).val() + 'dec');
		});
		if(svc_dec.length < 1) {
			svc_dec = 'false';
		}
		console.log('svc_dec: ' + svc_dec);
		
		// Get exclusive service values
		var svc_exclude = [];
		$('form#ro_search_form input[id="svc_exclude"]:checked').each(function(){
			svc_exclude.push($(this).val());
			svc_exclude_feedback.push($(this).val());
		});
		if(svc_exclude.length < 1) {
			svc_exclude = 'false';
		}
		console.log('svc_exclude: ' + svc_exclude);
		
		if (svc_search_feedback.length > 0) {
			search_feedback += 'Including Svcs = ';
			for(i=0; i<svc_search_feedback.length; i++) {
				if (i == (svc_search_feedback - 1)) {
					search_feedback += svc_search_feedback[i];
				} else {
					search_feedback += svc_search_feedback[i]+', ';
				}
			}
			search_feedback += ' | ';
		}	
		
		if (svc_exclude_feedback.length > 0) {
			search_feedback += 'Excluding Svcs = ';
			for(i=0; i<svc_exclude_feedback.length; i++) {
				if (i == (svc_exclude_feedback - 1)) {
					search_feedback += svc_exclude_feedback[i];
				} else {
					search_feedback += svc_exclude_feedback[i]+', ';
				}
			}
			search_feedback += ' | ';
		}	
		
		console.log('svc_search_feedback: ' , svc_search_feedback);
		console.log('svc_exclude_feedback: ', svc_exclude_feedback);
		
		
		if (ro_num1 == '' && ro_num2 == '') {
			ro_num1 = false;
			ro_num2 = false;
		} else if ((ro_num1 == '' && ro_num2 != '') || (ro_num1 != '' && ro_num2 == '')){
			errors.push('You left an RO field blank!');
			errors_show.push('ro_error1');
		} else {
			if(!ronumber_req.test(ro_num1) || !ronumber_req.test(ro_num2)) {
				errors.push('You entered an invalid RO number!');
				errors_show.push('ro_error1');
			} 
		}
		if (ro_num1 != false && ro_num2 != false) {
			search_feedback += 'RO Range = ' + ro_num1 + ' - ' + ro_num2 + ' | ';
		}
		
		if (ro_date1 == '' && ro_date2 == '') {
			ro_date1 = false;
			ro_date2 = false;
		} else if ((ro_date1 == '' && ro_date2 != '') || (ro_date1 != '' && ro_date2 == '')){
			errors.push('You left a date field blank!');
			errors_show.push('date_error1');
		} else {
			if(!ro_date_req.test(ro_date1) || !ro_date_req.test(ro_date2)) {
				errors.push('You entered an invalid date!');
				errors_show.push('date_error1');
			} 
		}
		if (ro_date1 != false && ro_date2 != false) {
			search_feedback += 'Date Range = ' + ro_date1 + ' - ' + ro_date2 + ' | ';
		}
		
		if (year1_id == '' && year2_id == '') {
			year1_id = false;
			year2_id = false;
		} else if ((year1_id == '' && year2_id != '') || (year1_id != '' && year2_id == '')){
			errors.push('You left a year selection blank!');
			errors_show.push('year_error1');
		}
		if (year1_id != false && year2_id != false) {
			search_feedback += 'Year Range: ' + year1_label + ' - ' + year2_label + ' | ';
		}
		
		if (mileage1_id == '' && mileage2_id == '') {
			mileage1_id = false;
			mileage2_id = false;
		} else if ((mileage1_id == '' && mileage2_id != '') || (mileage1_id != '' && mileage2_id == '')){
			errors.push('You left a mileage selection blank!');
			errors_show.push('mileage_error1');
		}
		if (mileage1_id != false && mileage2_id != false) {
			search_feedback += 'Mileage Range = ' + mileage1_label + ' : ' + mileage2_label + ' | ';
		}
		
		if (labor1 == '' && labor2 == '') {
			labor1 = false;
			labor2 = false;
		} else if ((labor1 == '' && labor2 != '') || (labor1 != '' && labor2 == '')){
			errors.push('You left a labor field blank!');
			errors_show.push('labor_error1');
		} else {
			if(!labor_req.test(labor1) || !labor_req.test(labor2)) {
				errors.push('You entered an invalid labor amount!');
				errors_show.push('labor_error1');
			} 
		}
		if (labor1 != false && labor2 != false) {
			search_feedback += 'Labor Range = $' + labor1 + ' - $' + labor2 + ' | ';
		}
		
		if (parts1 == '' && parts2 == '') {
			parts1 = false;
			parts2 = false;
		} else if ((parts1 == '' && parts2 != '') || (parts1 != '' && parts2 == '')){
			errors.push('You left a parts field blank!');
			errors_show.push('parts_error1');
		} else {
			if(!parts_req.test(parts1) || !parts_req.test(parts2)) {
				errors.push('You entered an invalid parts amount!');
				errors_show.push('parts_error1');
			} 
		}
		if (parts1 != false && parts2 != false) {
			search_feedback += 'Parts Range = $' + parts1 + ' - $' + parts2 + ' | ';
		}
		console.log('errors_show[0]: ' + errors_show[0]);
		
		if (vehicle1_id == '') {
			vehicle1_id = false;
		} else {
			search_feedback += 'Vehicle Make = ' + vehicle1_label + ' ';
		}
		
		if (advisor1_id == '') {
			advisor1_id = false;
		} else {
			search_feedback += 'Advisor = ' + advisor1_label + ' ';
		}
		
		// Display form errors
		var error_msg = '';
		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				error_msg += errors[i] + '\n';
				document.getElementById(errors_show[i]).style.display="inline";
			}
			alert(error_msg);
			return false;
		}
		
		// If no selection was made at all, do not submit the form
		if (ro_num1 == false && ro_num2 == false && ro_date1 == false && ro_date2 == false &&
			year1_id == false && year2_id == false && mileage1_id == false && mileage2_id == false &&
			labor1 == false && labor2 == false && parts1 == false && parts2 == false && vehicle1_id == false &&
			advisor1_id == false && svc_add == false && svc_dec == false && svc_exclude == false) {
			alert('You did not make a form selection!');
			return false;
		}
		
		// Set action
		var action = 'ro_search';
		
		// Set search params feedback message
		
		// Create object array of form input data
		var roData = { search_feedback:search_feedback, ro_num1:ro_num1 , ro_num2:ro_num2 , ro_date1:ro_date1 , ro_date2:ro_date2 ,
						 year1_id:year1_id , year1_label:year1_label , year2_id:year2_id , year2_label:year2_label ,
						 mileage1_id:mileage1_id , mileage1_label:mileage1_label , mileage2_id:mileage2_id , mileage2_label:mileage2_label ,
						 labor1:labor1 , labor2:labor2 , parts1:parts1 , parts2:parts2 ,vehicle1_id:vehicle1_id , vehicle1_label:vehicle1_label, 
						 advisor1_id:advisor1_id, advisor1_label:advisor1_label };
					 		 
		console.log('JSON.strigify(roData): ' , JSON.stringify(roData));
		console.log('svc_reg: ' , svc_reg);	
		console.log('svc_add: ' , svc_add);	
		console.log('svc_dec: ' , svc_dec);	
		console.log('svc_exclude: ' , svc_exclude);			 
		
		// Create dataString to send to server	
		var dataString = 'search_feedback=' + search_feedback + '&action=' + action + '&ro_num1=' + ro_num1 + '&ro_num2=' + ro_num2 + '&ro_date1=' + ro_date1 + '&ro_date2=' + ro_date2 +
						 '&year1_id=' + year1_id + '&year1_label=' + year1_label + '&year2_id=' + year2_id + '&year2_label=' + year2_label +
						 '&mileage1_id=' + mileage1_id + '&mileage1_label=' + mileage1_label + '&mileage2_id=' + mileage2_id + '&mileage2_label=' + mileage2_label +
						 '&labor1=' + labor1 + '&labor2=' + labor2 + '&parts1=' + parts1 + '&parts2=' + parts2 +
						 '&vehicle1_id=' + vehicle1_id + '&vehicle1_label=' + vehicle1_label + '&advisor1_id=' + advisor1_id +
						 '&advisor1_label=' + advisor1_label;
		
		//return false;				 
		console.log('dataString: ' + dataString);	
		
		// Hide the RO search form modal
		$('#ro_search_modal').foundation('reveal', 'close');
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&ro_params=' + JSON.stringify(roData) + '&svc_reg=' + svc_reg + '&svc_add=' + svc_add + '&svc_dec=' + svc_dec + '&svc_exclude=' + svc_exclude,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);

					// Make sure that datatables is initialized
					$("#enterrotable").DataTable({
						paging: true,
						searching: true
					});
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}				 
	});	
	
	// If user clicks 'Enter ROs' link, update page content with view RO entry page
	$('body').on('click', 'a.enter_ros_link', function(event) {
		event.preventDefault();
		
		console.log('clicked enter ros link');
		
		// Set action
		var action = 'enter_ros';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with update RO form
					$('div#page').html(returndata);
					
					// Focus cursor on first input element
					document.getElementById("ronumber").focus();

					// Make sure that datepicker is initialized
					$('#ro_date').datepicker({
						dateFormat: 'mm/dd/yy',
						maxDate: "+0D"
					});

					// Make sure that datatables is initialized
					$("#enterrotable").DataTable({
						paging: false,
						searching: false
					});
					
					// Make sure that hide/show button still works
					$("#hide_button").on("click", function() {
					  var el = $(this);
					  if (el.text() == el.data("text-swap")) {
						el.text(el.data("text-original"));
					  } else {
						el.data("text-original", el.text());
						el.text(el.data("text-swap"));
					  }
					  $("#hide").toggle(100);
					});
					
					// Make sure that advisor dropdown handler is initialized
					initialize_advisor_dropdown();
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}	
	});


	// The following code generates the RO update form and replaces the RO entry form page content with it
	$('body').on('click', 'form#update_ro_form', function (event) {
		event.preventDefault();

		var action = 'update_ro_form';
		var ro_data = $(this).closest('form').find('input[name="update_ro_data"]').val().split(' ');
		var ro_id = ro_data[0];
		var ro_number = ro_data[1];
		console.log('ro_id: ' + ro_id + 'ro_number: ' + ro_number);

		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&ro_id=' + ro_id + '&ro_number=' + ro_number,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with update RO form
					$('div#page').html(returndata);

					// Make sure that datepicker is active on ro_date input
					$('#ro_date').datepicker({
						dateFormat: 'mm/dd/yy',
						maxDate: "+0D"
					});

					// Make sure that enterrotable is initialized
					$("#enterrotable").DataTable({
						paging: false,
						searching: false
					});

					// Focus input on ronumber field
					$('#ronumber').focus();
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Last 30 Days' link for viewing metrics data, run view_metrics_month action
	$('body').on('click', 'a.view_metrics_month', function (event) {
		event.preventDefault();
		
		var action = 'view_metrics_month';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality for both tables
					$("#L1_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#L2_3_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#labor_parts_table").DataTable({
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'All History' link for viewing metrics data, run view_metrics_all action
	$('body').on('click', 'a.view_metrics_all', function (event) {
		event.preventDefault();
		
		var action = 'view_metrics_all';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality for both tables
					$("#L1_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#L2_3_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#labor_parts_table").DataTable({
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Last 30 Days' link for viewing stats data, run view_stats_month action
	$('body').on('click', 'a.view_stats_month', function (event) {
		event.preventDefault();
		
		var action = 'view_stats_month';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality for all stats tables
					// Need to add the ro stats table datable initialization
					$("#svc_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#lof_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#vehicle_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#my_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#ms_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Last 30 Days' link for viewing stats data, run view_stats_month action
	$('body').on('click', 'a.view_stats_all', function (event) {
		event.preventDefault();
		
		var action = 'view_stats_all';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		// Need to add the ro stats table datable initialization
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality for both tables
					$("#svc_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#lof_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#vehicle_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#my_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				    
				    $("#ms_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Dealer Summary' link, update page content
	$('body').on('click', 'a.dealer_summary_link', function (event) {
		event.preventDefault();
		
		var action = 'dealer_summary';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					$("#dealer_summary_table").DataTable({
					   "scrollX" : true,
					   paging: true,
					   searching: true,
					   "order": [[1,'asc']],			
					   "pageLength": 25
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user selects a dealer from the Dealer Summary table, update dealer SESSION data and display metrics data
	$('body').on('click', '#dealer_summary_submit', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'dealer_summary_select';
		
		// Get dealer values from submit.  Will be used for updating dealer SESSION data. Contains ID, code, name.  Split them
		var dealer_info = $(this).closest("form").find("#summary_dealerID").val();
		var dealer_info = dealer_info.split("#");
		var dealer_id   = dealer_info[0];
		var dealer_code = dealer_info[1];
		var dealer_name = dealer_info[2];
		console.log('dealer_id: ' + dealer_id + 'dealer_code: ' + dealer_code + 'dealer_name: ' + dealer_name);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&dealer_id=' + dealer_id + '&dealer_code=' + dealer_code + '&dealer_name=' + dealer_name,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// process_ajax file will load metrics data for chosen dealer.Re-initialize metrics tables functionality
					// Re-initialize table functionality for both tables
					$("#L1_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
		
				    $("#L2_3_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Last 30 Days' link for viewing stats data, run view_stats_month action
	$('body').on('click', 'a.view_dealer_list', function (event) {
		event.preventDefault();
		
		var action = 'view_dealer_list_all';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					$("#dealer_list_table_all").DataTable({
					  // "scrollX": true,
					   paging: true,
					   searching: true,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Add Dealer' link, generate and display Add Dealer table form
	$('body').on('click', 'a.add_dealer_link', function (event) {
		event.preventDefault();
		
		var action = 'get_dealer_add_form';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					$("#dealer_list_table_all").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Add Row' link on Add Dealer table, add a table row
	$('body').on('click', 'a#add_dealer_row', function (event) {
		event.preventDefault();
		
		var action = 'add_dealer_row';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('table#add_new_dealer tbody').append(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user selects a user from the user table, display edit user table with user values
	$('body').on('click', '#table_dealer_edit_select', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'table_dealer_edit_select';
		
		// Get user_id from hidden input
		var dealer_id = $(this).closest('form').find('#update_dealer_id').val();
		console.log('dealer_id: ' + dealer_id);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&dealer_id=' + dealer_id,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Submit" button on add dealer table, validate inputs and insert new users into db
	$('body').on('click', '#add_dealer_submit', function (event) {
		event.preventDefault();
		
		// Serialize the form data for use with $.ajax().  Will send to server for processing.
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		/* Get hidden input values for dealer id and code. 
		 * Pass dealer id to server for execution of UPDATE statement.
		 * Compare hidden code to input code.  If different, run checkDealerDupe() method 
		**/
		var edit_dealer_code = document.getElementById("edit_dealer_code").value;
		var edit_dealer_id = document.getElementById("edit_dealer_id").value;
		console.log('edit_dealer_code: ' + edit_dealer_code + 'edit_dealer_id: ' + edit_dealer_id);
		
		// Validate all inputs using serializeArray() method. Establish error array.
		var errors = [];
		var serialize_array = $("form#dealer_form").serializeArray();
		console.log('serialized form: ' , serialize_array);
		
		// Initialize dealer arrays for holding dealer values
		var dlr_name 		= [];
		var dlr_code 		= [];
		var dlr_address 	= [];
		var dlr_city 		= [];
		var dlr_state_id 	= [];
		var dlr_zip 		= [];
		var dlr_phone 		= [];
		var dlr_dist_id 	= [];
		var dlr_area_id 	= [];
		var dlr_region_id 	= [];
		
		$.each(serialize_array, function(i, field) {
			if (field.name == 'dlr_name') {
				if (field.value == '') {
					errors.push("*You must enter a dealer name!\n");
				} else {
					dlr_name.push(field.value);
				}
			}
			if (field.name == 'dlr_code') {
				if (!ronumber_req.test(field.value)) {
					errors.push("*Dealer code must be all numbers!\n");
				} else {
					dlr_code.push(field.value);
				}
			}
			if (field.name == 'dlr_address') {
				if (field.value == '') {
					errors.push("*You left the dealer address field blank!\n");
				} else {
					dlr_address.push(field.value);
				}
			}
			if (field.name == 'dlr_city') {
				if (!validCity.test(field.value)) {
					errors.push("*You entered an invalid city!\n");
				} else {
					dlr_city.push(field.value);
				}
			}
			if (field.name == 'dlr_state') {
				if (field.value == '') {
					errors.push("*You must enter a state!\n");
				} else { 
					dlr_state_id.push(field.value);
				}
			}
			if (field.name == 'dlr_zip') {
				if (!validZip.test(field.value)) {
					errors.push("*Zip code must contain exactly five numbers!\n");
				} else {
					dlr_zip.push(field.value);
				}
			}
			if (field.name == 'dlr_phone') {
				if (!ronumber_req.test(field.value)) {
					errors.push("*Dealer phone may only contain numbers!\n");
				} else {
					dlr_phone.push(field.value);
				}
			}
			if (field.name == 'dlr_district') {
				if (field.value == ''){
					errors.push("*You left a dealer district field blank!\n");
				} else {
					dlr_dist_id.push(field.value);
				}
			}
			if (field.name == 'dlr_area') {
				if (field.value == ''){
					errors.push("*You left a dealer area field blank!\n");
				} else {
					dlr_area_id.push(field.value);
				}
			}
			if (field.name == 'dlr_region') {
				if (field.value == ''){
					errors.push("*You left a dealer region field blank!\n");
				} else {
					dlr_region_id.push(field.value);
				}
			}
		});
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		var action = 'add_dealers';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Save input values in correct format for submitting to server
		var dlr_name 		= JSON.stringify(dlr_name)		;	
		var dlr_code 		= JSON.stringify(dlr_code)		;	
		var dlr_address 	= JSON.stringify(dlr_address)	;
		var dlr_city 		= JSON.stringify(dlr_city)		;	
		var dlr_state_id 	= JSON.stringify(dlr_state_id)	;
		var dlr_zip 		= JSON.stringify(dlr_zip)		;	
		var dlr_phone 		= JSON.stringify(dlr_phone)		;	
		var dlr_dist_id 	= JSON.stringify(dlr_dist_id)	;	
		var dlr_area_id 	= JSON.stringify(dlr_area_id)	;
		var dlr_region_id 	= JSON.stringify(dlr_region_id)	;
		
		console.log('dlr_name=' , dlr_name + '&dlr_code=' , dlr_code + '&edit_dealer_id=' , edit_dealer_id + '&edit_dealer_code=' + edit_dealer_code  + '&dlr_address=' + dlr_address + '&dlr_city=' + dlr_city + '&dlr_state_id=' + dlr_state_id + '&dlr_zip=' + dlr_zip + '&dlr_phone=' + dlr_phone + '&dlr_dist_id=' + dlr_dist_id + '&dlr_area_id=' + dlr_area_id + '&dlr_region_id=' + dlr_region_id + '&action=' + action);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'dlr_name=' + dlr_name + '&dlr_code=' + dlr_code + '&edit_dealer_id=' + edit_dealer_id + '&edit_dealer_code=' + edit_dealer_code  + '&dlr_address=' + dlr_address + '&dlr_city=' + dlr_city + '&dlr_state_id=' + dlr_state_id + '&dlr_zip=' + dlr_zip + '&dlr_phone=' + dlr_phone + '&dlr_dist_id=' + dlr_dist_id + '&dlr_area_id=' + dlr_area_id + '&dlr_region_id=' + dlr_region_id + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					} else if (returndata.substring(0,10) == "error_dupe") {
						alert(returndata.substring(10));
						return false;
					} else {
						// Insert successful. Replace page content with returndata
						$('div#page').html(returndata);
					}
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Request User Setup" link, run get_user_request_form action
	$('body').on('click', 'a.request_user_setup', function (event) {
		event.preventDefault();
		
		var action = 'get_user_request_form';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality. Had to take off because causes dynamic add row to break
					/*
					$("#user_request_table").DataTable({
					  "scrollX": true,
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });*/
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Add Users' link, run add_new_user_table process action
	$('body').on('click', 'a.add_user_link', function (event) {
		event.preventDefault();
		
		var action = 'add_new_user_table';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Add Row" link on add user table, insert another input row
	$('body').on('click', 'a#add_new_user_row', function (event) {
		event.preventDefault();
		
		var action = 'add_new_user_row';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Append row to User Table
					$('table#add_user_table tbody').append(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Submit" button on add user table, validate inputs and insert new users into db
	$('body').on('click', '#add_user_submit', function (event) {
		event.preventDefault();
		
		// Serialize the form data for use with $.ajax().  Will send to server for processing.
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Get hidden input value for edit_user value.  Will dictate user_pass field validation. Pass this var to server also
		var edit_user_val = document.getElementById("edit_user_val").value;
		var edit_user_id = document.getElementById("edit_user_id").value;
		console.log('edit_user_val: ' + edit_user_val + 'edit_user_id: ' + edit_user_id);
		
		// Validate all inputs using serializeArray() method. Establish error array.
		var errors = [];
		var serialize_array = $("form#add_new_users_form").serializeArray();
		console.log('serialized form: ' , serialize_array);
		
		// Initialize dealer arrays for holding dealer values
		var user_fname  = [];
		var user_lname  = [];
		var user_uname  = [];
		var user_email  = [];
		var user_pass   = [];
		var user_admin  = [];
		var user_active = [];
		var dealer_id   = [];
		var dealer_code = [];
		var dealer_name = [];
		var team_id 	= [];
		var team_name 	= [];
		var type_id 	= [];
		var type_name 	= [];
		
		$.each(serialize_array, function(i, field) {
			if (field.name == 'user_fname') {
				if (!validName.test(field.value)) {
					errors.push("*You entered an invalid first name!\n");
				} else {
					user_fname.push(field.value);
				}
			}
			if (field.name == 'user_lname') {
				if (!validName.test(field.value)) {
					errors.push("*You entered an invalid last name!\n");
				} else {
					user_lname.push(field.value);
				}
			}
			if (field.name == 'user_uname') {
				if (field.value == '') {
					errors.push("*You left a username blank!\n");
				} else {
					user_uname.push(field.value);
				}
			}
			if (field.name == 'user_email') {
				if (!validEmail.test(field.value)) {
					errors.push("*You entered an invalid email address!\n");
				} else {
					user_email.push(field.value);
				}
			}
			/* Note: test hidden input for edit_user_val value, and proceed with user_pass validation based on this value
			 * If edit_user form is being used and the password is blank, proceed without validating user_pass
			 * If edit_user form is being used and the password has been entered, validate it
			 * If edit_user form is not being used, always validate the password input
			**/
			if (field.name == 'user_pass') {
				if (edit_user_val == 0) {
					if (!validPass.test(field.value)) {
						errors.push("*You entered an invalid password!\n");
					} else { 
						user_pass.push(field.value);
					}
				} else {
					if(field.value == "") {
						user_pass.push("false");
					} else {
						user_pass.push(field.value);
					}
				}
			}
			if (field.name == 'user_type_id') {
				if (field.value == '') {
					errors.push("*You must select the user type!\n");
				} else {
					var type = field.value.split("#");
					type_id.push(type[0]);
					type_name.push(type[1]);
				}
			}
			if (field.name == 'user_team_id') {
				if (field.value == '') {
					errors.push("*You must select the user team!\n");
				} else {
					var team = field.value.split("#");
					team_id.push(team[0]);
					team_name.push(team[1]);
				}
			}
			if (field.name == 'user_dealerID') {
				if (field.value == ''){
					errors.push("*You left a dealer field blank!\n");
				} else {
				// This field value contains both dealerID and dealercode, separated by '#'.  Split out and save unique name for addition to formData for submittal.
					var dealer = field.value.split("#");
				    	dealer_id.push(dealer[0]);
				    	dealer_code.push(dealer[1]);
				    	dealer_name.push(dealer[2]);
				    	//console.log('dealer_id[]: ' + dealer_id + 'dealer_code[]: ' + dealer_code + 'dealer_name[]: ' + dealer_name);
				}
			}
			if (field.name == 'user_admin') {
				if (field.value == '') {
					errors.push("*You left an admin selection blank!\n");
				} else {
					user_admin.push(field.value);
				}
			}
			if (field.name == 'user_active') {
				if (field.value == '') {
					errors.push("*You left an active selection blank!\n");
				} else {
					user_active.push(field.value);
				}
			}
		});
		
		/* Run through all type_id's and make sure that dealer_id = 0 if type == 1 (SOS) or == 2(Manuf)
		 * Also make sure that Manuf and Dealer users are not assigned team 'All'
		**/
		for(i=0; i<type_id.length; i++) {
			if(type_id[i] == 1 || type_id[i] == 2) {
				if(dealer_id[i] != 0) {
					errors.push("*SOS and Manuf users must have a dealer assignment of \'N/A\' \n");
				}
			}
			// Also make sure that Manuf and Dealer users do not have the team assignment of 'All'
			if(type_id[i] == 2 || type_id[i] == 3) {
				if(team_id[i] == 0) {
					errors.push("*Manuf and Dealer users may not have the team assignment of \'All\' \n");
				}
			}
		}
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		var action = 'add_new_users';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		
		// Save input values in correct format for submitting to php
		var user_fname  = JSON.stringify(user_fname);
		var user_lname  = JSON.stringify(user_lname);
		var user_uname  = JSON.stringify(user_uname);
		var user_email  = JSON.stringify(user_email);
		var user_pass   = JSON.stringify(user_pass);
		var user_admin  = JSON.stringify(user_admin);
		var user_active = JSON.stringify(user_active);
		var dealer_id   = JSON.stringify(dealer_id);
		var dealer_code = JSON.stringify(dealer_code);
		var dealer_name = JSON.stringify(dealer_name);
		var team_id		= JSON.stringify(team_id);
		var team_name   = JSON.stringify(team_name);
		var type_id     = JSON.stringify(type_id);
		var type_name   = JSON.stringify(type_name);
		console.log('dealer_id: ' , dealer_id + 'dealer_code: ' , dealer_code + 'dealer_name: ' , dealer_name + 'team_id: ' , team_id + 'team_name: ' , team_name + 'type_id: ' , type_id + 'type_name: ' , type_name);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'user_fname=' + user_fname + '&user_lname=' + user_lname + '&user_uname=' + user_uname + '&user_email=' + user_email + '&user_pass=' + user_pass + '&user_admin=' + user_admin + '&user_active=' + user_active + '&dealer_id=' + dealer_id + '&dealer_code=' + dealer_code + '&dealer_name=' + dealer_name + '&team_id=' + team_id + '&team_name=' + team_name + '&type_id=' + type_id + '&type_name=' + type_name + '&edit_user_val=' + edit_user_val + '&edit_user_id=' + edit_user_id + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}
					
					// If there was a duplicate username error, return alert msg
					if (returndata.substring(0,10) == "error_dupe") {
						alert(returndata.substring(10));
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	$('body').on('click', 'a.approve_user_setup', function (event) {
		event.preventDefault();
		
		var action = 'view_user_setup_requests';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality. Had to take off because causes dynamic add row to break
					/*
					$("#user_request_table").DataTable({
					  "scrollX": true,
					   paging: false,
					   searching: false,
					   info: false,
					   order: []
				    });*/
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* Add rows to user setup table when 'Add Row' link is clicked
	 * Below is the original requiring no AJAX call, which works (but problem is making the user_type and user_team dynamic)
	 * Another problem is that the <tr> has to be replicated below.  Every time you make a change to php code, would have
	 * to update the code below as well.
	 * Note the JS sessionStorage variable.  This is the first time it has been utilized.  Works well!
	**
	$('body').on('click', 'a#add_user_req_row', function() {
		// Retrieve js session <option> dealerOpts for dealer <select>
		var dealerOpts = sessionStorage.getItem("dealerOpts");
		$('table#user_request_table tbody').append('<tr><td style="width: 32px;"> <a class="fontello-cancel-circled-outline"></a> </td> <!-- the remove row placeholder --><td><input type="text" name="user_req_fname[]" id="user_req_fname[]"/></td><td><input type="text" name="user_req_lname[]" id="user_req_lname[]"/></td><td><input type="text" name="user_req_uname[]" id="user_req_uname[]"/></td><td><input type="text" name="user_req_email[]" id="user_req_email[]"/><input type="hidden" name="user_team_id[]" id="user_team_id[]" value="1" /><input type="hidden" name="user_type_id[]" id="user_type_id[]" value="3" /></td><td><select id="user_req_dealerID[]" name="user_req_dealerID[]"><option value="">Select...</option>' + dealerOpts + '</select></td><!--<td><select><option value="">Select...</option></select></td>--><td><select id="user_req_admin[]" name="user_req_admin[]"><option value="">Select...</option><option value="1">Yes</option><option value="0">No</option></select></td></tr>');
	});*/
	
	// Add user request rows dynamically via AJAX call
	$('body').on('click', 'a#add_user_req_row', function() {
	
		// Set action
		var action = 'add_user_req_row';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Append row to User Request Table
					$('table#user_request_table tbody').append(returndata);
				
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// Remove dynamic table rows when user clicks x icon
	$('body').on('click', '.fontello-cancel-circled-outline', function() {
		console.log('Remove button clicked!');
		
		// Get name attr to determine further action (if doc table 'x', ask to confirm and then issue AJAX delete instruction)
		var name = $(this).attr("name");
		console.log('name: ' + name);
		
		//return false;
		$(this).parent().parent().remove();
	});
	
	// Remove document from db when user clicks trash icon and confirms document removal
	$('body').on('click', 'a.fontello-trash', function() {
		console.log('Remove button clicked!');
		
		// Get name attr to determine further action (if doc table trash icon, ask to confirm and then issue AJAX delete instruction)
		var name = $(this).attr("name");
		console.log('name: ' + name);
		
		// Get id attr so you can pass document id (file name) to method in Documents class
		var tmp_name = $(this).attr("id");
		console.log('tmp_name: ' + tmp_name);
		
		// If name == 'remove_doc_icon', issue confirm message to confirm document deletion. Then issue AJAX delete instruction
		if(name == 'remove_doc_icon') {
			if(confirm('Are you sure you want to delete the selected file?')) {
				// Load the spinner to indicate processing
				$('div.loader_div').html('<div class="spinner">Loading...</div>');
				
				// Get file_id for db delete action
				var view_doc_id = $(this).closest('tr').find('td form input#view_doc_id').val();
				console.log('view_doc_id: ' + view_doc_id);
				
				// Set action for process_ajax.inc.php process file
				var action = 'delete_doc';
		
				// The spinner is only removed once the ajax call is complete.
				setTimeout(ajaxCall, timeDelay);
				console.log('timeDelay: ' + timeDelay);
		
				// Save the ajax call as a function to execute within the setTimeout() function
				function ajaxCall() {
				 	$.ajax({
						type: "POST",
						url: processFile,
						data: 'action=' + action + '&view_doc_id=' + view_doc_id + '&tmp_name=' + tmp_name,
						success: function(returndata){
							console.log('returndata: ' + returndata);
		
							// Remove the loading div before the content is updated
							$('.loader_div').empty();
		
							if (returndata == "error_login") {
								if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
									// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
									window.location.reload(true);
									return false;
								} else {
									return false;
								}
							}
							
							// Replace page content with returndata
							$('div#page').html(returndata);
							
							// Re-initialize table functionality
							$("#user_doc_table").DataTable({
						   		paging: true,
						   		searching: true,
						   		order: []
				   			});
						},
						error: function(response){
							// Remove the loading div before the content is updated
							$('.loader_div').empty();
		
						 	alert(cxn_error);
						}
					});
				// End ajaxCall() fn
				} 
			// End if confirm
			} else { 
				return false;
			}
		// End if(name == 'remove_doc_icon')
		} else {
			return false;
			//$(this).parent().parent().remove();
		}
	});
	
	// Remove document from db when user clicks trash icon and confirms document removal
	$('body').on('click', 'a.icon-document-edit', function() {
		console.log('Edit doc button clicked!');
		
		// Get name attr to determine further action (if doc table trash icon, ask to confirm and then issue AJAX delete instruction)
		var name = $(this).attr("name");
		console.log('name: ' + name);
		
		// If name == 'remove_doc_icon', issue confirm message to confirm document deletion. Then issue AJAX delete instruction
		if(name == 'edit_doc_icon') {
			// Load the spinner to indicate processing
			$('div.loader_div').html('<div class="spinner">Loading...</div>');
			
			// Get file_id for db delete action
			var edit_doc_id = $(this).closest('tr').find('td form input#view_doc_id').val();
			console.log('edit_doc_id: ' + edit_doc_id);
			
			// Set action for process_ajax.inc.php process file
			var action = 'edit_doc_form';
		
			// The spinner is only removed once the ajax call is complete.
			setTimeout(ajaxCall, timeDelay);
			console.log('timeDelay: ' + timeDelay);
		
			// Save the ajax call as a function to execute within the setTimeout() function
			function ajaxCall() {
			 	$.ajax({
					type: "POST",
					url: processFile,
					data: 'action=' + action + '&edit_doc_id=' + edit_doc_id,
					success: function(returndata){
						console.log('returndata: ' + returndata);
		
						// Remove the loading div before the content is updated
						$('.loader_div').empty();
		
						if (returndata == "error_login") {
							if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
								// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
								window.location.reload(true);
								return false;
							} else {
								return false;
							}
						}
						
						// Replace page content with returndata
						$('div#page').html(returndata);
						
						// Re-initialize table functionality
						/*
						$("#user_doc_table").DataTable({
					   		paging: true,
					   		searching: true,
					   		order: []
			   			});*/
					},
					error: function(response){
						// Remove the loading div before the content is updated
						$('.loader_div').empty();
		
					 	alert(cxn_error);
					}
				});
			// End ajaxCall() fn
			}
		// End if(name == 'remove_doc_icon')
		} else {
			return false;
			//$(this).parent().parent().remove();
		}
	});
	
	// Submit user setup request form table
	$('body').on('click', '#user_req_submit', function (event) {
		event.preventDefault();
		//console.log('clicked user setup request form submit');
		
		// Serialize the form data for use with $.ajax().  Will send to server for user_setup_request table
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Validate all inputs. Establish error array.
		var errors = [];
		var serialize_array = $("form#user_req_form").serializeArray();
		console.log('serialized form: ' , serialize_array);
		
		// Initialize dealer_id and dealer_code arrays for holding dealer values
		var dealer_id = [];
		var dealer_code = [];
		var dealer_name = [];
		
		$.each(serialize_array, function(i, field) {
			if (field.name == 'user_req_fname[]') {
				if (!validName.test(field.value)) {
					errors.push("*You entered an invalid first name!\n");
				}
			}
			if (field.name == 'user_req_lname[]') {
					
				if (!validName.test(field.value)) {
					errors.push("*You entered an invalid last name!\n");
				}
			}
			if (field.name == 'user_req_uname[]') {
				console.log("entered if for user_req_uname");
				if (field.value == '') {
					errors.push("*You left a username blank!\n");
				}
			}
			if (field.name == 'user_req_email[]') {
				if (!validEmail.test(field.value)) {
					errors.push("*You entered an invalid email address!\n");
				}
			}
			if (field.name == 'user_req_pass[]') {
				if (!validPass.test(field.value)) {
					errors.push("*You entered an invalid password!\n");
				}
			}
			
			if (field.name == 'user_req_dealerID[]') {
				if (field.value == ''){
					errors.push("*You left a dealer field blank!\n");
				} else {
					// This field value contains both dealerID and dealercode, separated by '#'.  Split out and save unique name for addition to formData for submittal.
					var dealer = field.value.split("#");
				    	dealer_id.push(dealer[0]);
				    	dealer_code.push(dealer[1]);
				    	dealer_name.push(dealer[2]);
				    	console.log('dealer_id[]: ' + dealer_id + 'dealer_code[]: ' + dealer_code + 'dealer_name[]: ' + dealer_name);
				}
			}
			if (field.name == 'user_req_admin[]') {
				if (field.value == '') {
					errors.push("*You left an admin selection blank!\n");
				}
			}
		});
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		var action = 'process_user_setup_request';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);
		
		// Test dealer values
		var dealer_id = JSON.stringify(dealer_id);
		var dealer_code = JSON.stringify(dealer_code);
		var dealer_name = JSON.stringify(dealer_name);
		console.log('dealer_id: ' , dealer_id + 'dealer_code: ' , dealer_code + 'dealer_name: ' + dealer_name);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData + '&dealer_id=' + dealer_id + '&dealer_code=' + dealer_code + '&dealer_name=' + dealer_name + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
							// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
							window.location.reload(true);
							return false;
						} else {
							return false;
						}
					}
					
					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					/*
					$("#user_setup_table").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });*/
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* Display dealer users when user clicks on any view users link. 
	 * Action is set by <a> 'name' attribute.  This code is used for all three user type views.
	 * Note: action must = <a> attribute and action in process file
	 * <a> name attributes: 'view_dealer_users', 'view_sos_users', 'view_manuf_users'
	**/
	$('body').on('click', 'a.view_users', function (event) {
		event.preventDefault();
		
		// Set form action.  Process file will use this same string for action
		var action = $(this).attr("name");
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Initialize DataTable functionality. Note that custom <div class="table-container"> used instead of scrollX
					$("#user_table").DataTable({
					   //"scrollX": true,
					   paging: true,
					   searching: true,
					   order: []
					});
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user selects 'Help link on main dashboard menu, display inquiry form
	$('body').on('click', 'a.contact_us_link', function (event) {
		event.preventDefault();
		
		// Set form action.  Process file will use this same string for action
		var action = 'contact_us_link';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// Submit user inquiry information to db
	$('body').on('click', '#contact_us_submit', function (event) {
		event.preventDefault();
		
		// Set POST values
		var action  = 'contact_us_submit';
		var fname   = document.getElementById("fname").value;
		var lname   = document.getElementById("lname").value;
		var email   = document.getElementById("email").value;
		var phone   = document.getElementById("phone").value;
		var dealer  = document.getElementById("dealer").value;
		var comment = document.getElementById("comment").value;
		
		// Validate all inputs. Establish error array.
		var errors = [];
		
		if(fname == '') {
			errors.push("*Error: You must enter a first name!\n");
		}
		
		if(lname == '') {
			errors.push("*Error: You must enter a last name!\n");
		}
		
		if(!validEmail.test(email)) {
			errors.push("Error: Please enter a valid email address!\n");
		}
		
		if(phone == '') {
			errors.push("Error: Please enter a contant phone number!\n");
		}
		
		if(dealer == '') {
			errors.push("*Error: Please enter your dealer information, or \'N/A\'\n");
		}
		
		if(comment == '') {
			errors.push("*Error: You must enter a comment!\n");
		}
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&fname=' + fname + '&lname=' + lname + '&email=' + email + '&phone=' + phone + '&dealer=' + dealer + '&comment=' + comment,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user selects a user from the user table, display edit user table with user values
	$('body').on('click', '#table_user_edit_select', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'table_user_edit_select';
		
		// Get user_id from hidden input
		var user_id = $(this).closest('form').find('#update_user_id').val();
		console.log('user_id: ' + user_id);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&user_id=' + user_id,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* Replace main login form with email pass reset input form if user clicks 'forgot_password_link'
	 * Don't forget to send 'no_session' post so that process_ajax.inc.php enters correct if block
	**/
	$('body').on('click', '#forgot_pass_link', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'forgot_pass_link';
		var formData = true;
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'no_session=' + formData + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* Replace main login form with email pass reset input form if user clicks 'forgot_password_link'
	 * Don't forget to send 'no_session' post so that process_ajax.inc.php enters correct if block
	**/
	$('body').on('click', '#return_loginform_link', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'get_login_form';
		var formData = true;
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'no_session=' + formData + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	/* Replace main login form with email pass reset input form if user clicks 'forgot_password_link'
	 * Don't forget to send 'no_session' post so that process_ajax.inc.php enters correct if block
	**/
	$('body').on('click', '#send_reset_link', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'send_reset_link';
		var user_email = document.getElementById("user_email").value;
		console.log('user_email: ' + user_email);
		var formData = true;
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'no_session=' + formData + '&action=' + action + '&user_email=' + user_email,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// Update user's password and return success/fail message to user on login screen
	$('body').on('click', '#update_pass_submit', function (event) {
		event.preventDefault();
		
		// Set POST values
		var action = 'reset_user_pass';
		var pass1 = document.getElementById("pass1").value;
		var pass2 = document.getElementById("pass1").value;
		var user_email = document.getElementById("user_email").value;
		var formData = true;
		
		// Validate all inputs. Establish error array.
		var errors = [];
		
		if(!validPass.test(pass1.value)) {
			errors.push("*Error: Password must be at least 8 characters, contain 1 upper and 1 lower-case letter, 1 number and 1 special character.");
		}
		
		if(pass1 != pass2) {
			errors.push("*Passwords did not match!");
		}
		
		if(!validEmail.test(user_email.value)) {
			errors.push("Please enter a valid email address.");
		}
		
		console.log('pass1: ' + pass1 + 'pass2: ' + pass2 + 'user_email: ' + user_email);
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'no_session=' + formData + '&action=' + action + '&pass1=' + pass1 + '&pass2=' + pass2 + '&user_email=' + user_email,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					// Replace page content with returndata
					$('div#page').html(returndata);
					
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();
				 	alert(cxn_error);
				}
			});
		}
	});
	
	// Check or uncheck all checkboxes for user setup approvals when user clicks on a id="select_all_user_requests"
	$('body').on('click', '#select_all_user_requests', function (event) {
		event.preventDefault();
		//console.log('select all user requests clicked!');
		
		// Set data-text var
		var el = $(this);
		
		// Set prop based on data-text value
		var property = (el.text() == el.data("text-swap")) ? false : true;
		
		// Run through each checkbox and make it checked
		$.each($('input[type="checkbox"]'), function() {
			$(this).prop('checked', property);
		});
		
		// Swap out 'Select All' with 'Unselect All'
		el.text() == el.data("text-swap") ? el.text(el.data("text-original")) : el.text(el.data("text-swap"));
		//console.log('el.data(text-swap): ' + el.data("text_swap"));
	});
	
	// Submit user setup request approvals
	$('body').on('click', '#user_req_approve_submit', function (event) {
		event.preventDefault();
		//console.log('clicked user setup request form submit');
		
		// Serialize the form data for use with $.ajax().  Will send to server for user_setup_request table
		var formData = $(this).parents("form").serialize();
		console.log('formData: ' + formData);
		
		// Validate all inputs. Establish error array. row_ids is only used for testing.  formData is used instead for action
		var errors = [];
		var row_ids = [];
		
		var serialize_array = $("form#user_req_form").serializeArray();
		console.log('serialized form: ' , serialize_array);
		
		$.each(serialize_array, function(i, field) {
			if (field.name == 'user_approve_check[]') {
				if (field.value != '') {
					row_ids.push(field.value);
					console.log('row_ids' + row_ids[i]);
				}
			}
		});
		
		if (row_ids.length == 0) {
			errors.push("*You must select at least one user for approval!\n");
		}
		
		if (errors.length > 0) {
			var error_msg = "";
			for (var i=0; i<errors.length; i++) {
				error_msg += errors[i];
			}
			alert("Your input contains the following errors: \n\n" + error_msg + "\nPlease correct the errors and re-submit the form.");
			return false;
		}
		// Remove
		//return false;
		
		var action = 'approve_user_setup_requests';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData + '&action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						if(confirm('You are no longer logged in! \n Proceed to login screen?')) {
							// If user is no longer logged in, display message and prompt 'okay' for page redirect to login page
							window.location.reload(true);
							return false;
						} else {
							return false;
						}
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					/*
					$("#user_setup_table").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });*/
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// Function to retrieve dealer listing for user setup requests.  Check for browser global (first ever js global!)
	function getDealerInfo() {
		var action = 'get_dealer_info_js';
		$.ajax({
			type: "POST",
			url: processFile,
			data: 'action=' + action,
			success: function(returndata){
				console.log('returndata: ' + returndata);
				
				/*
				if (returndata == "error_login") {
					alert('You are no longer logged in!');
					return false;
				}*/

				// Set returndata as js global.  Will contain an array of all dealer info
				var dealerInfo = returndata;
				sessionStorage.setItem("dealerInfo", dealerInfo);
				
				// Get dealerInfo session var to build dealer options list
				var dealerInfo = sessionStorage.getItem("dealerInfo");
				
				// Convert JSON string (from php json_encode) to array of objects
				var p = JSON.parse(dealerInfo);
				
				// Build the string of dealer <select> options and save as js session var for populating form options
				var dealerOpts = "";
				for(i=0; i<p.length; i++) {
					dealerOpts += "<option value='" + p[i][0] + "#" + p[i][1] + "#" + p[i][2] + "'>" + p[i][2] + " (" + p[i][1] + ") </option>";
				}
				sessionStorage.setItem("dealerOpts", dealerOpts);
			},
			error: function(response){
			 	alert(cxn_error);
			 	return false;
			}
		});
	}
	
	// If user clicks 'add_doc_link', display document add form
	$('body').on('click', 'a.add_doc_link', function (event) {
		event.preventDefault();
		
		var action = 'add_doc_link';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality for all stats tables
					/*
					$("#svc_table").DataTable({
					   paging: false,
					   searching: false,
					   order: []
				    });*/
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'view_doc_table', display relevant document table based on name attribute
	$('body').on('click', 'a.view_doc_table', function (event) {
		event.preventDefault();
		
		var action = 'view_doc_table';
		var doc_type = $(this).attr("name");
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&doc_type=' + doc_type,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					$("#user_doc_table").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'view_doc_link', display document table
	$('body').on('click', 'a.view_doc_link', function (event) {
		event.preventDefault();
		
		var action = 'view_doc_link';
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with metrics data
					$('div#page').html(returndata);
					
					// Re-initialize table functionality
					$("#user_doc_table").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	/* Note: this code block was tested for file downloads via AJAX.  It was determined that this is not possible, so 
	   all code was moved to an outside file in the utils folder: getFile.php
	// If user selects a document from the doc table, display it in the browser with 'Content-Disposition: inline' instruction
	$('body').on('click', '#table_doc_select', function (event) {
		event.preventDefault();
		
		// Set form action
		var action = 'table_doc_select';
		
		// Get user_id from hidden input
		var view_doc_id = $(this).closest('form').find('#view_doc_id').val();
		console.log('view_doc_id: ' + view_doc_id);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);
		console.log('timeDelay: ' + timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&view_doc_id=' + view_doc_id,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}
					
					// Note: page does not need to be updated with anything as it is just a file view
					// Replace page content with returndata
					//$('div#page').html(returndata);
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});*/
	
	// If user clicks 'Upload File' button on add document form, process file addition to db
	$('body').on('click', 'input#file_submit', function (event) {
		event.preventDefault();
		
		// Initialize error array
		var errors = [];
		
		// Set action for process_ajax.inc.php
		var action = 'file_submit';
		
		// Get the document title
		var doc_title = document.getElementById("doc_title").value;
		
		// Get the document description
		var doc_desc = document.getElementById("doc_desc").value;
		
		// Get the document category type
		var doc_category = document.getElementById("doc_category").value;
		
		// Get the file
		var file = document.getElementById("choose_file").files[0];
		console.log('file: ' , file);
		
		// Check to make sure form inputs are not empty
		if(doc_title == "") {
			errors.push("Please enter a document title!");
		}
		
		if(doc_desc == "") {
			errors.push("Please enter a document description!");
		}
		
		if(doc_category == "") {
			errors.push("Please enter a document category!");
		}
		
		if(file == null) {
			errors.push("You must select a file before proceeding!");
		}
		
		// Establish error message string
		var error_msg = "The following input errors have occurred: \n\n";
		
		// Iterate through each error message if errors.length > 0
		if(errors.length > 0) {
			for(i=0; i<errors.length; i++) {
				error_msg += errors[i] + "\n";
			}
			error_msg += "\nPlease correct the errors and try again.";
			alert(error_msg);
			return false;
		}
		
		console.log('doc_title: ' + doc_title + 'doc_desc: ' + doc_desc);
		
		// Use FormData object to send data. This is the only way to send actual files via AJAX
		var formData = new FormData();
		formData.append('choose_file', file);
		formData.append('action', action);
		formData.append('doc_title', doc_title);
		formData.append('doc_desc', doc_desc);
		formData.append('doc_category', doc_category);
		
		// Check the file type
		var type_test = /^.*pdf$/;
  		if (!file.type.match(type_test)) {
    		alert("Error: Only pdf files are allowed!");
    		return false;
  		}
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData,
				processData: false,
				contentType: false,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});
	
	// If user clicks 'Upload File' button on add document form, process file addition to db
	$('body').on('click', 'input#file_update_submit', function (event) {
		event.preventDefault();
		
		// Initialize error array
		var errors = [];
		
		// Set action for process_ajax.inc.php
		var action = 'file_update_submit';
		
		// Get the document title
		var doc_title = document.getElementById("doc_title").value;
		
		// Get the document description
		var doc_desc = document.getElementById("doc_desc").value;
		
		// Get the document type
		var doc_category = document.getElementById("doc_category").value;
		
		// Get the file
		var file_name = document.getElementById("file_name").value;
		
		// Get file id for db update
		var file_id = document.getElementById("edit_doc_id").value;
		
		// Check to make sure form inputs are not empty
		if(doc_title == "") {
			errors.push("Please enter a document title!");
		}
		
		if(doc_desc == "") {
			errors.push("Please enter a document description!");
		}
		
		if(doc_category == "") {
			errors.push("Please enter a document category!");
		}
		
		// If file_name != "", check to make sure the user has not added a '.pdf' file extension to it.  This is automatically added later
		if(file_name == "") {
			errors.push("Please enter a file name!");
		} else if (file_name.slice(-4) == ".pdf") {
			errors.push("Please remove the file extension from the file name!");
		}
		//console.log('file_name.slice(-4,0): ' + file_name.slice(-4));
		
		// Establish error message string
		var error_msg = "The following input errors have occurred: \n\n";
		
		// Iterate through each error message if errors.length > 0
		if(errors.length > 0 ) {
			for(i=0; i<errors.length; i++) {
				error_msg += errors[i] + "\n";
			}
			error_msg += "\nPlease correct the errors and try again.";
			alert(error_msg);
			return false;
		}
		
		console.log('doc_title: ' + doc_title + 'doc_desc: ' + doc_desc + 'file_name: ' + file_name + 'file_id: ' + file_id);
		
		// Use FormData object to send data. This is the only way to send actual files via AJAX
		var formData = new FormData();
		formData.append('file_name', file_name);
		formData.append('action', action);
		formData.append('doc_title', doc_title);
		formData.append('doc_desc', doc_desc);
		formData.append('doc_category', doc_category);
		formData.append('file_id', file_id);
		console.log('formData: ' + formData);
		
		// Load the spinner to indicate processing
		$('div.loader_div').html('<div class="spinner">Loading...</div>');

		// The spinner is only removed once the ajax call is complete.
		setTimeout(ajaxCall, timeDelay);

		// Save the ajax call as a function to execute within the setTimeout() function.
		function ajaxCall() {
		 	$.ajax({
				type: "POST",
				url: processFile,
				data: formData,
				processData: false,
				contentType: false,
				success: function(returndata){
					console.log('returndata: ' + returndata);

					// Remove the loading div before the content is updated
					$('.loader_div').empty();

					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					}

					// Replace page content with returndata
					$('div#page').html(returndata);
					
					// Re-initialize table functionality, as program will return user table after edit is completed
					$("#user_doc_table").DataTable({
					   paging: true,
					   searching: true,
					   order: []
				    });
				},
				error: function(response){
					// Remove the loading div before the content is updated
					$('.loader_div').empty();

				 	alert(cxn_error);
				}
			});
		}
	});

	// The following code processes the cycle time form via AJAX
	$('#cycletime_form').submit(function(event) {

		$('.form_error').hide();
		$('.cycletime_success').hide();

		var sample_date = document.getElementById('sample_date');
		var reception_time = document.getElementById('reception_time');
		var roc_time = document.getElementById('roc_time');
		var bay_time = document.getElementById('bay_time');
		var cycle_time = document.getElementById('cycle_time');

		var sample_date_req	= /^([0-1][0-9])\/([0-3][0-9])\/([0-9]{4})$/;
		//var sample_date_req = /^([0-9]{4})-([0-1][0-9])-([0-3][0-9])$/;
		var time_req = /^([0-9]{3}|[0-9]{2}):([0-5][0-9])$/;

		var errors = [];
		var focus = [];

		if (!sample_date_req.test(sample_date.value)) {
			errors.push("sample_date_error");
			focus.push("sample_date");
		}

		if (!time_req.test(reception_time.value)) {
			errors.push("reception_time_error");
			focus.push("reception_time");
		}

		if (!time_req.test(roc_time.value)) {
			errors.push("roc_time_error");
			focus.push("roc_time");
		}

		if (!time_req.test(bay_time.value)) {
			errors.push("bay_time_error");
			focus.push("bay_time");
		}

		if (!time_req.test(cycle_time.value)) {
			errors.push("cycle_time_error");
			focus.push("cycle_time");
		}

		var dataString = {sample_date:sample_date.value, reception_time:reception_time.value,
						roc_time:roc_time.value, bay_time:bay_time.value, cycle_time:cycle_time.value};

		console.log(dataString);

		if(errors.length > 0 ){
			for(var i=0;i<errors.length;i++){
				document.getElementById(errors[i]).style.display="inline";
			}
			document.getElementById(focus[0]).focus();
			return false;
		} else {
			$.ajax({
				type: "POST",
				url: "cycletime_process.php",
				data: dataString,
				cache: false,
				success: function(returndata){
					console.log(returndata);
					if (returndata == "error") {
						$("#cycletime_response").html("<h6 style='color: red; line-height: 1; text-align: center;'>*Error: the entry was not processed.  See administrator.</h6>");
						$("#cycletime_response").fadeIn(400).delay(5000).fadeOut(600);
						console.log(returndata);
					} else if (returndata == "error_login") {
						$("#cycletime_response").html("<h6 style='color: red; line-height: 1; text-align: center;'>*Error: You are no longer logged in!</h6>");
						$("#cycletime_response").fadeIn(400).delay(5000).fadeOut(600);
					} else if (returndata == "error_session_timeout") {
							if(confirm('For security purposes, your session has timed out due to two hours of inactivity! \n Select \'Okay\' to be directed back to the login page.')) {
							window.location.assign('index.php');
						}
						console.log(returndata);
					} else {
						$('#cycletime_response').html($('#cycletime_response' , returndata).html());
						$("#cycletime_response").fadeIn(400).delay(5000).fadeOut(600);
						$('input:text').val('');
						//document.getElementById("ronumber").focus();
						console.log(returndata);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					var error_msg = "We are sorry, but a processing error has occurred.\nPlease see the administrator with the following:\nError Type: " + errorThrown;
					alert(error_msg);
				}
			});
		}
		event.preventDefault();
	});
	
	function initialize_advisor_dropdown() {
		// The following code changes the $userID for enterrofoundationadd_process_welr via AJAX so that users may change advisor on the fly (this does not affect the $_SESSION['userID'] global)
		$("select#advisor_enterro").change(function(event){
			$('.advisor_success').hide();
			
			// Set action and advisor_enterro vars for passing to server
			var action       = 'change_advisor';
			var advisor      = document.getElementById('advisor_enterro').value.split(",");
			var advisor_id   = advisor[0];
			var advisor_name = advisor[1];
			
			console.log('advisor_id: ' + advisor_id + 'advisor_name: ' + advisor_name);
			
			// AJAX Code To Submit Form.
			$.ajax({
				type: "POST",
				url: processFile,
				data: 'action=' + action + '&advisor_id=' + advisor_id + '&advisor_name=' + advisor_name,
				success: function(returndata){
					console.log('returndata: ' + returndata);
					if (returndata == "error_login") {
						alert('You are no longer logged in!');
						return false;
					} else {
						$("#advisor_success" ).fadeIn( 300 ).delay( 3500 ).fadeOut( 400 );
					}
				},
				error: function() {
					alert("Error: The system was unable to change the advisor! \nPlease try again and see the administrator if the problem persists.");
				}
			});
			event.preventDefault();
		});
	}
});

// The following function ensures that only one checkbox may be checked at a time for each service
function check_checkboxes(i) {
	var svc_reg_array = document.getElementById('service_form')['svc_reg[]'];
	var svc_add_array = document.getElementById('service_form')['svc_add[]'];
	var svc_dec_array = document.getElementById('service_form')['svc_dec[]'];
	if (svc_reg_array[i].checked == true) {
		svc_add_array[i].checked = false;
		svc_dec_array[i].checked = false;
	} else if (svc_add_array[i].checked == true) {
		svc_reg_array[i].checked = false;
		svc_dec_array[i].checked = false;
	} else if (svc_dec_array[i].checked == true) {
		svc_reg_array[i].checked = false;
		svc_add_array[i].checked = false;
	}
}
