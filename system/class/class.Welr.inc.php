<?php
/**
 * Program: class.Welr.inc.php
 * Created: 02/29/2016 by Matt Holland
 * Purpose: Process RO form entries etc
 * Methods: getPageHeading($update_form): Creates top of page with title, dealer, and RO count
 *			getRoCount($dealer_id): Gets RO count for $dealer_id
 *			getAdvisorDropdown(): Creates advisor selection menu
 *			getYearModelOpts(): Gets options for year model selection menu
 *			getMileageOpts(): Gets options for mileage selection menu
 *			getVehicleOpts(): Gets otpions for vehicle selection menu
 *			getRoEntryForm($dealer_id, $update_form, $update_ro_id): Creates main entry form 
 *			getRegBox($svc_id, $i, $svc_reg, $svc_add, $svc_dec): Creates Req checkboxes
 *			getAddBox($svc_id, $i, $svc_add, $svc_reg): Creates Add checkboxes
 *			getDecBox($svc_id, $i, $svc_dec, $svc_reg): Creates Dec checkboxes
 *			getRoEntryTable(): Creates RO table at bottom of page
 *			getRos($array(dealer_id, entry_form, update_form, update_ro_): Gets all RO data for params
 *			checkRoDuplicate($ronumber,$dealer_id): Checks for RO duplicates for case of insert, update, delete functions
 *			processRoEntry($array): Processes main RO entry form.  Returns insert, update, delete functions
 *			insertSvcrenderedRecord($array): Inserts service records into servicerendered_welr table.  Uses in multiple methods.
 *			deleteSvcrenderedRecord($array): Deletes service records from servicerendered_welr table
 *			deleteRo($array): Deletes RO from repairorder_welr and servicerendered_welr tables
 *			updateRo($array): Updates RO records in repairorder_welr and servicerendered_welr tables
 *			insertRo($array): Inserts RO records into repairorder_welr and servicerendered_welr tables
 * Updates:
 */

Class Welr extends DB_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}
	
	public function getPageHeading($array) {
		
		// Build page heading markup
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'].' - 
           				<span class="blue"> '.$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].') </span>';
           				if($array['update_form']) {
						   $html .='<a class="enter_ros_link" style="color: green; font-size: 15px;"> &nbsp; Cancel</a>';
						  }
					$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export ROs" href="system/utils/export_ros.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print RO Table" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['ro_count']) {
					  	$html .='
						&nbsp;Total ROs: '.number_format($this->getRoCount($array));
					  }
					$html .='
					</p>
				</div>
           	</div>
        </div>
        <!-- Container Begin -->
        <div class="row" style="margin-top:-20px">';
		return $html;
	}
	
	public function getRoCount($array) {
		// orig params: $dealer_id, $date_range
		
		// Set vars for easy access
		$dealer_id = $_SESSION['dealer_id'];
		
		// Set SESSION vars for time ranges
		$ro_date_range1 = (!isset($_SESSION['ro_date_range1'])) ? $_SESSION['ro_date_range1'] = date("Y-m-01") : $_SESSION['ro_date_range1'];
		$ro_date_range2 = (!isset($_SESSION['ro_date_range2'])) ? $_SESSION['ro_date_range2'] = date("y-m-d")  : $_SESSION['ro_date_range2'];
	
		$sql = "SELECT ronumber FROM repairorder_welr WHERE dealerID = $dealer_id";
		
		// If date_range == true, add BETWEEN statement for count
		if($array['date_range']) {
			$sql .= " AND ro_date BETWEEN '$ro_date_range1' AND '$ro_date_range2' ";
		}
		$result = $this->dbo->query($sql);
		if(!$result) {
			$_SESSION['error'][] = 'System failed to provide RO count.  See administrator.';
			return false;
		} else {
			$rows = $result->num_rows;
			return $rows;
		}
	}
	
	public function getAdvisorDropdown() {
		$html ='
		<form method="POST" action="#" class="advisor_form">
			<div class="row collapse">
				<div class="small-3 large-1 columns">
					<span class="prefix">Select Advisor</span>
				</div>
				<div class="small-9 large-2 columns">
					<select id="advisor_enterro" name="advisor_enterro">';
						$advisors = new UserInfo($dbo=null);
						$adv_array = $advisors->getAdvisors($_SESSION['dealer_id']);
						$user_id = $adv_array['user_id'];
						$user_name = $adv_array['user_name'];
						if (isset($_SESSION['advisor_id'])) {
							$html.='<option value="'.$_SESSION['advisor_id'].','.$_SESSION['advisor_name'].'">'.$_SESSION['advisor_name'].'</option>';
						} else {
							$html.='<option value="'.$_SESSION['user']['user_id'].','.$_SESSION['user']['user_name'].'">'.$_SESSION['user']['user_name'].'</option>';
						}
					for($i=0; $i<sizeof($user_id); $i++) {
						$html.='<option value="'.$user_id[$i]['user_id'].','.$user_name[$i]['user_name'].'">'.$user_name[$i]['user_name'].'</option>';
					}		
					$html.='
					</select>
				</div>
				<div class="advisor_success_div small-12 large-9 columns end">
					<h4 class="advisor_success" id="advisor_success">*Advisor was updated!</h4>
				</div>
			</div>
		</form>';
		return $html;
	}
	
	// If user changes the advisor on the main enterro form, update the advisor SESSION vars for ro entry records
	public function processAdvisorSelection() {
		// Set advisor SESSION var so that user may choose advisor on the fly
		$_SESSION['advisor_id']   = $_POST['advisor_id'];
		$_SESSION['advisor_name'] = $_POST['advisor_name'];
		return;
	}
	
	// Generate dropdown options for Year Model selection
	public function getYearModelOpts() {
		if(!isset($_SESSION['ym_opts'])) {
			// Use server year for $currentyear -> menu will always need to be current because the data collection is on-going
			$currentyear = date('Y');
			$month = date('m');
			// Add one to year to allow for next year's models
			if ($month > 6) {
				$currentyear = $currentyear+1;
			}
			
			// Find $currentyear in yearmodel table and fetch descending results for menu dropdown.  
			// Had to add 1 to $currentyear because query would only return 1 year less than $currentyear
			$sql = "SELECT yearmodelID, modelyear FROM yearmodel WHERE modelyear < $currentyear+1
					ORDER BY yearmodelID DESC";
					
			$result = $this->dbo->query($sql);
			if (!$result) {
				$_SESSION['error'][] = "Year Model query failed.  See administrator.";
			} else {
				$rows = $result->num_rows;
				/*$test = $result->fetch_assoc();
				return '$test: '.$test.'<br>';
				exit();*/
				
				$row = array();
				$i = 0;
				// Execute while loop to fetch results so can echo inside of <select> dropdown
				while ($value = $result->fetch_assoc()) {
					$row[$i]['yearmodelID'] = $value['yearmodelID'];
					$row[$i]['modelyear']   = $value['modelyear']  ;
					$i += 1;
				}
			}
			// Note: pass both the yearmodelID and the modelyear into the value field so they are both available.  Will split these using ' ' as delimiter later for processing.
			$html = '';
			for ($i = 0; $i < $rows; $i++) {
				if ($i == $rows-1) {
					$html .= '<option value="'.$row[$i]['yearmodelID'].' '.$row[$i]['modelyear'].'">'.$row[$i]['modelyear'].' & Older</option><br>';
				} else {
					$html .= '<option value="'.$row[$i]['yearmodelID'].' '.$row[$i]['modelyear'].'">'.$row[$i]['modelyear'].'</option><br>';
				}
			}
			$_SESSION['ym_opts'] = $html;
			return $html;
		} else {
			return $_SESSION['ym_opts'];
		}
	}
	
	public function getMileageOpts() {
		//unset($_SESSION['mileage_opts']);
		if(!isset($_SESSION['mileage_opts'])) {
			// Retrieve id's and labels from table
			$sql = "SELECT mileagespreadID, carmileage FROM mileagespread";
			$result = $this->dbo->query($sql);
			if (!$result) {
				$_SESSION['error'][] = 'Mileage query failed.  See administrator.';
			} else {
				$rows   = $result->num_rows;
				$row = array();
				$i = 0;
				while ($value = $result->fetch_assoc()) {
					$row[$i]['mileagespreadID'] = $value['mileagespreadID']	;
					$row[$i]['carmileage']	    = $value['carmileage']		;
					$i += 1;
				}
				
				$html = '';
				for ($i = 0; $i < $rows; $i++) {
					$html .='<option value= "'.$row[$i]['mileagespreadID'].','.$row[$i]['carmileage'].'">' .$row[$i]['carmileage']. '</option><br>';
				}
				$_SESSION['mileage_opts'] = $html;
				return $html;
			}
		} else {
			return $_SESSION['mileage_opts'];
		}
	}
	
	public function getVehicleOpts() {
		//unset($_SESSION['vehicle_opts']);
		if(!isset($_SESSION['vehicle_opts'])) {
			// Retrieve id's and labels from table
			$sql = "SELECT vehicle_make_id, vehicle_make FROM vehicle_make";
			$result = $this->dbo->query($sql);
			if (!$result) {
				$_SESSION['error'][] = "Vehicle query failed.  See administrator.";
			} else {
				$rows = $result->num_rows;
				
				$row = array();
				$i = 0;
				while ($value = $result->fetch_assoc()) {
					$row[$i]['vehicle_make_id'] = $value['vehicle_make_id']	;
					$row[$i]['vehicle_make']	= $value['vehicle_make']	;
					$i += 1;
				}
			}
			$html = '';
			for ($i = 0; $i < $rows; $i++) {
				$html .='<option value= "'.$row[$i]['vehicle_make_id'].','.$row[$i]['vehicle_make'].'">' .$row[$i]['vehicle_make']. '</option><br>';
			}
			$_SESSION['vehicle_opts'] = $html;
			return $html;
		} else {
			return $_SESSION['vehicle_opts'];
		}
	}
	
	// Create service checkbox listing for RO search parameters
	public function getSvcSearchTable() {
	
		// Get service info from ServicesInfo class.  Will return associative array of service items.
		$svc_obj = new ServicesInfo($dbo=null);
		$svc = $svc_obj->getServiceInfo($sort_welr = false, $sort_metrics = true);
		
		// Build checkbox table
		//echo var_dump($svc['svc_info']);
		$html ='
			<div class="large-12 columns">
				 <table class="svc_search_table responsive">
					 <tbody>
						 <tr>';
							 for($i=0; $i<5; $i++) {
								 $html .='
								 <td><input class="search_checkbox" type="checkbox" id="svc_exclude" name="svc_exclude" value="'.$svc['svc_info'][$i]['serviceID'].'"></td>
								 <td>'.$svc['svc_info'][$i]['service_nickname'].'</td>';
							 }
						 $html .='	
						 </tr>
						 
						 <tr>';
							 for($i=5; $i<10; $i++) {
								 $html .='
								 <td><input class="search_checkbox" type="checkbox" id="svc_exclude" name="svc_exclude" value="'.$svc['svc_info'][$i]['serviceID'].'"></td>
								 <td>'.$svc['svc_info'][$i]['service_nickname'].'</td>';
								 }
						 $html .='		
						 </tr>
						 
						 <tr>';
							 for($i=10; $i<15; $i++) {
								 $html .='
								 <td><input class="search_checkbox" type="checkbox" id="svc_exclude" name="svc_exclude" value="'.$svc['svc_info'][$i]['serviceID'].'"></td>
								 <td>'.$svc['svc_info'][$i]['service_nickname'].'</td>';
								 }
						 $html .='
						 </tr>
						 
						 <tr>';
							 for($i=15; $i<20; $i++) {
							 	if($i != null) {
								 	$html .='
								 	<td><input class="search_checkbox" type="checkbox" id="svc_exclude" name="svc_exclude" value="'.$svc['svc_info'][$i]['serviceID'].'"></td>
								 	<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>';
							 	} else {
							 		$html .='
								 	<td></td>
								 	<td></td>';
							 	}
							 }
						 $html .='	
						 </tr>
						 
						 <tr>';
							 for($i=20; $i<25; $i++) {
							 	if($i != '') {
								 	$html .='
								 	<td><input class="search_checkbox" type="checkbox" id="svc_exclude" name="svc_exclude" value="'.$svc['svc_info'][$i]['serviceID'].'"></td>
								 	<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>';
							 	} else {
							 		$html .='
								 	<td></td>
								 	<td></td>';
							 	}
							 }
						$html .='	 		
						 </tr>		
					 </tbody>
				 </table>
			</div>';
		return $html;
	}
	
	// New checkbox generate function testing
	public function getRoEntryForm($update_form, $update_ro_id, $search_params) {
		//$update_form, $update_ro_id, $search_params) {
	
		// Establish variables
		$dealer_id = $_SESSION['dealer_id'];
		
		// If $search_params == true, specify div column size for modal service selection
		$large_cols = ($search_params) ? 'large-12' : 'large-8';
		
		$html = '
		<div class="box enterro-form">
		<div class="box-body">
		<div class="row">
		<div class="large-12 columns">
			<form method="POST" id="service_form" action="#">
			<input type="hidden" name="submitted" value="true" />
			<div class="row">
				<div class="small-12 large-4 columns">
					<div class="row">';
						if($update_form) {
							$submit = 'Update Order';
							$array = $this->getRos(array('dealer_id' =>$dealer_id, 'entry_form' => false, 'update_form' => $update_form, 'update_ro_id' => $update_ro_id, 'search_params' => $search_params));
							$ro_num = $array['repairorder_welr'][0]['ronumber'];
							$date= $array['repairorder_welr'][0]['ro_date'];
							$date= new DateTime($date);
							$ro_date = $date->format('m/d/Y');
							$ym_opt = '<option value="'.$array['repairorder_welr'][0]['yearmodelID'].' '.$array['repairorder_welr'][0]['modelyear'].'">'.$array['repairorder_welr'][0]['modelyear'].'</option>';
							$mile_opt = '<option value="'.$array['repairorder_welr'][0]['mileagespreadID'].','.$array['repairorder_welr'][0]['carmileage'].'">'.$array['repairorder_welr'][0]['carmileage'].'</option>';
							$veh_opt = '<option value="'.$array['repairorder_welr'][0]['vehicle_make_id'].','.$array['repairorder_welr'][0]['vehicle_make'].'">'.$array['repairorder_welr'][0]['vehicle_make'].'</option>';
							$labor = $array['repairorder_welr'][0]['labor'];
							$parts = $array['repairorder_welr'][0]['parts'];
							$comment = $array['repairorder_welr'][0]['comment'];
						} else {
							$submit = 'Submit Repair Order';
							$ro_num = null;
							$ro_date= null;
							$empty_opt = '<option value="">Select...</option>';
							$ym_opt = $empty_opt;
							$mile_opt = $empty_opt;
							$veh_opt = $empty_opt;
							$labor = null;
							$parts = null;
							$comment = null;
						}
						
						// Set permanent insert message feedback if new RO has been inserted
						$insert_msg = (isset($_SESSION['insert_ronumber'])) ? 'Repair Order '.$_SESSION['insert_ronumber'].' added successfully' : null;
						
						$html .='
						<div class="small-12 medium-12 large-12 columns">
							<div id="update_div1">'.$insert_msg.'</div>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<div class="number-field">
								<label>RO Number 
										<small class="form_error" id="ro_error">*Enter a valid RO number</small>
										<small class="form_error" id="error_login">*Error: You are no longer logged in!</small>
										<small class="form_error" id="error_entry_validation">*Error: You entered invalid data!</small>
										<small class="form_error" id="error_query">*Query error!  Please see administrator</small>
										<small class="form_error" id="error_ro_dupe">*Error: Repair order already exists!</small>
										<small class="form_error" id="error_survey_lock">*Survey is locked! Entry denied.</small>
										<small class="form_error" id="error_survey_startyear">*Error: Please select the start year!</small>
										<small class="form_error" id="error_ro_insert">*Error: Unable to enter order!</small>
										<small class="form_error" id="error_ro_update">*Error: RO was not updated!</small>
										<small class="form_error" id="error_ro_delete">*Error: RO was not deleted!</small>
										<small class="form_error" id="error_ro_delete_rule">*Error: You cannot change the delete number!</small>
										<small class="form_error" id="error_svc_insert">*Error: RO entered, but services insert failed!</small>
										<small class="form_error" id="error_svc_delete">*Error: RO entered, but services delete failed!</small>
										<small class="ro_success" id="ro_success">*Repair order was added!</small>
										<small class="ro_success" id="ro_update">*Repair order was updated!</small>
										<small class="ro_success" id="ro_delete">*Repair order was deleted!</small>
								<input class="text_input_error" type="text" id="ronumber" name="ronumber" placeholder="Enter Repair Order Number" value="'.$ro_num.'" autofocus>  
								</label>
							</div>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<label>RO Date <small class="form_error" id="date_error">*Enter a date (MM/DD/YYYY)</small>
								<input type="text" id="ro_date" name="ro_date" placeholder="Enter RO date" value="'.$ro_date.'">
							</label>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<label>Year Model <small class="form_error" id="year_error">*Please select a year</small>
								<select id="yearmodel" name="yearmodel">'.
									$ym_opt.
									$this->getYearModelOpts().'
								</select>
							</label>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<label>Mileage Spread <small class="form_error" id="mileage_error">*Please select the mileage</small>
								<select id="mileage" name="mileage">'.
									$mile_opt.
									$this->getMileageOpts().'
								</select>
							</label>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<label>Vehicle Make <small class="form_error" id="vehicle_error">*Please select the make</small>
								<select id="vehicle" name="vehicle">'.
									$veh_opt.
									$this->getVehicleOpts().'
								</select>
							</label>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<label>Labor <small class="form_error" id="labor_error">*Enter a valid dollar amount</small>
								<input type="text" id="labor" name="labor" placeholder="Enter Dollar Amount" value="'.$labor.'">
							</label>
						</div>
						<div class="small-12 medium-6 medium-pull-6 large-12 large-reset-order columns">
							<label>Parts <small class="form_error" id="parts_error">*Enter a valid dollar amount</small>
								<input type="text" id="parts" name="parts" placeholder="Enter Dollar Amount" value="'.$parts.'">
							</label>
						</div>';
				if($update_form) {
					$html .='
						<div class="small-12 medium-6 large-12 columns">
							<textarea id="comment" name="comment" placeholder="Any Comments?">'.$comment.'</textarea>
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<input id="delete" type="submit" name="delete_ro" class="small alert button radius" value="Delete Order">
						</div>
						<div class="small-12 medium-6 large-6 columns">
							<input id="submit" type="submit" name="update_ro" class="small success button radius" value="'.$submit.'">
						</div> ';
				} else {	
					$html .='
						<div class="small-12 medium-6 large-12 columns">
							<label> Comments?
								<textarea id="comment" name="comment" placeholder="Any Comments?">'.$comment.'</textarea>
							</label>
						</div>
						<div class="small-12 medium-6 large-12 columns">
							<input id="submit" type="submit" name="insert_ro" class="tiny success button radius" value="'.$submit.'">
						</div>';
				}
				$html .='
					</div><!-- end div row -->
				</div><!-- end div small-12 large-4 columns -->';
				$svc_tables ='
				<div class="small-12 medium-12 '.$large_cols.' columns">
				<label class="svc_table_heading"> Light Maintenance <small class="form_error" id="service_error">*Please select at least one regular service</small> </label>
						<div class="row">
							<div class="table-container">
							<table class="service_table">
								<thead>
									<tr class="tr_first">
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title"> Add </th>
										<th class="th_add_dec_title"> Dec </th>
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title">Add</th>
										<th class="th_add_dec_title">Dec</th>
									</tr>
								</thead>
								<tbody>';
									// Get service info from ServicesInfo class.  Will return associative array of service items.
									$svc_obj = new ServicesInfo($dbo=null);
									$svc = $svc_obj->getServiceInfo($sort_welr = true, $sort_metrics = false);
									
									// Initialize empty arrays for each checkbox if $update_form == false
									if (!$update_form) {
										$array = array('svc_reg'=>array(), 'svc_add'=>array(), 'svc_dec'=>array());
										//echo "update_form == false <br>";
										//echo 'array[svc_reg]: <br>'.var_dump($array['svc_reg']);
										//echo '$array: '.var_dump($array).'<br>';
									}
									
									// Get counts for service levels for loop actions below
									$l1_count = 0;
									$l2_count = 0;
									$l3_count = 0;
									foreach($svc['svc_level'] as $level) {
										if($level == 1) {
											$l1_count += 1;
										}
										if($level == 2) {
											$l2_count += 1;
										}
										if($level == 3) {
											$l3_count += 1;
										}
									}
									// Loop through services arrays for form checkbox creations
									for ($i=0; $i < $l1_count; $i++) {
										// Use modulus to determine if count is even or odd
										if($l1_count%2 == 0) {
											$svc_tables .='
													<tr>
														<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
												$i = $i + 1;
												$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
													</tr>';
										} else {
											if ($i < ($l1_count - 1)) {
												//echo 'array[svc_reg] in checkbox loop: '.var_dump($array['svc_reg']);
												$svc_tables .='
													<tr>
														<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
												$i = $i + 1;
												$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
													</tr>';
											} else {
												$svc_tables .='
													<tr>
														<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
														<td></td>
														<td></td>
														<td></td>
														<td></td>
													</tr>';
											}
										}
									}
								$svc_tables .='
								</tbody>
							</table>
							</div><!-- end div table-container -->
						</div><!-- end div row -->
				</div><!-- end div small-12 medium-12 $large columns -->
				<div class="small-12 medium-12 '.$large_cols.' columns">
				<label class="svc_table_heading"> Medium Maintenance </label>
						<div class="row">
							<div class="table-container">
							<table class="service_table">
								<thead>
									<tr class="tr_first">
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title">Add</th>
										<th class="th_add_dec_title">Dec</th>
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title">Add</th>
										<th class="th_add_dec_title">Dec</th>
									</tr>
								</thead>
								<tbody>';
									for ($i=$l1_count; $i < ($l1_count+$l2_count); $i++) {
										// Use modulus to determine if count is even or odd. Had to add -1 as it was duplicating Warranty for Acura
										if(((($l1_count+$l2_count)-1)%2) == 0) {
											$svc_tables .='
												<tr>
													<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
												$i = $i + 1;
											$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
												</tr>';
										} else {
											if ($i<(($l1_count+$l2_count)-1)) {
												$svc_tables .='
												<tr>
													<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
												$i = $i + 1;
											$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
												</tr>';
											} else {
												$svc_tables .='
												<tr>
													<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
													<td></td>
													<td></td>
													<td></td>
													<td></td>
												</tr>';	
											}
										}
									}
								$svc_tables .='
								</tbody>
							</table>
							</div><!-- end div table-container -->
						</div><!-- end div row -->
				</div><!-- end div small-12 medium-12 $large columns -->
				<div class="small-12 medium-12 '.$large_cols.' columns">
				<label class="svc_table_heading"> Repair Service </label>
						<div class="row">
							<div class="table-container">
							<table class="service_table">
								<thead>
									<tr class="tr_first">
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title"> Add </th>
										<th class="th_add_dec_title"> Dec </th>
										<th class="svc_th_title"> Service </th>
										<th class="th_req_title"> Req </th>
										<th class="th_add_dec_title"> Add </th>
										<th class="th_add_dec_title"> Dec </th>
									</tr>
								</thead>
								<tbody>';
									for ($i=($l1_count+$l2_count); $i < count($svc['svc_info']); $i++) {
										// Use modulus to determine if count is even or odd
										if((count($svc['svc_info']))%2 == 0) {
											$svc_tables .='
												<tr>
													<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
											$i = $i + 1;
											$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
													$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
													$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
													$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
													$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
												</tr>';
										} else {
											if ($i < (count($svc['svc_info'])-1)) {
												$svc_tables .='
													<tr>
														<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params);
												$i = $i + 1;
												$svc_tables.='<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
													</tr>';
											} else {
												$svc_tables .='
													<tr>
														<td>'.$svc['svc_info'][$i]['service_nickname'].'</td>'.
														$this->getRegBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_reg'], $array['svc_add'], $array['svc_dec'], $search_params).
														$this->getAddBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_add'], $array['svc_reg'], $search_params).
														$this->getDecBox($svc['svc_info'][$i]['serviceID'], $i, $array['svc_dec'], $array['svc_reg'], $search_params).
														$this->getHiddenInput($svc['svc_info'][$i]['serviceID'], $search_params).'
														<td></td>
														<td></td>
														<td></td>
														<td></td>
													</tr>';
											}
										}	
									}
								$svc_tables .='
								</tbody>
							</table>
							</div><!-- end div table-container -->
						</div> <!-- end div row -->
					</div> <!-- end div small-12 medium-12 $large columns -->';
				$html2 ='	
				</div> <!-- end div row -->
			</form> <!-- end form #service_form -->
			</div> <!-- end div large-12 columns -->
			</div> <!-- end div row -->
			</div> <!-- end div box-body -->
			</div> <!-- end div box -->';
		// If $search_params == false, return entire form.  Else just return service tables for ro filters	
		return (!$search_params) ? $html.$svc_tables.$html2 : $svc_tables;
	}
	
	public function getRegBox ($svc_id, $i, $svc_reg, $svc_add, $svc_dec, $search_params) {
		//echo '$svc_reg in getRegBox(): '.var_dump($svc_reg).'<br>';
		//$svc_reg = array();
		//echo '$svc_reg: '.var_dump($svc_reg);
		if($svc_reg == null) {
			$svc_reg = array();
		}
		
		// Set names and ids based on search_params (so that modal and main forms do not conflict with ids and names)
		$id = $name = (!$search_params) ? 'svc_reg[]' : 'svc_reg';
		
		$key = array_search($svc_id, $svc_reg);	/* see if this service box is in services rendered table */
		//echo 'svc_id: '.$svc_id.'<br>';
		//echo '$key: '.var_dump($key).'<br>';
		if ($key == FALSE) {
			$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>';
		} else {
			if ($svc_add[$key] == 0 && $svc_dec[$key] == 0) {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'" checked></td>';
			} else {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>';
			}
		}
		return $html;
	}
	
	public function getAddBox ($svc_id, $i, $svc_add, $svc_reg, $search_params) {
		// Set names and ids based on search_params (so that modal and main forms do not conflict with ids and names)
		$id = $name = (!$search_params) ? 'svc_add[]' : 'svc_add';
		//echo '$svc_reg in getAddBox(): '.var_dump($svc_reg);
		//$svc_reg = array();
		if($svc_reg == null) {
			$svc_reg = array();
		}
		
			$key = array_search($svc_id, $svc_reg);	/* see if this add box is associated with row in services rendered table */
		if ($key == FALSE) {
			$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>'; 
		} else {
			if ($svc_add[$key] == 0) {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>';  
			} else {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'" checked></td>'; 
			}
		}
		return $html;
	}
	
	public function getDecBox ($svc_id, $i, $svc_dec, $svc_reg, $search_params) {
		// Set names and ids based on search_params (so that modal and main forms do not conflict with ids and names)
		$id = $name = (!$search_params) ? 'svc_dec[]' : 'svc_dec';
		//echo '$svc_reg in getDecBox(): '.var_dump($svc_reg);
		if($svc_reg == null) {
			$svc_reg = array();
		}
		
		//$svc_reg = array();
		$key = array_search($svc_id, $svc_reg);	/* see if this add box is associated with row in services rendered table */
		if ($key == FALSE) {
			$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>'; 
		} else {
			if ($svc_dec[$key] == 0) {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'"></td>';  
			} else {
				$html ='<td><input type="checkbox" id="'.$id.'" name="'.$name.'" onclick="check_checkboxes('.$i.');" value="'.$svc_id.'" checked></td>'; 
			}
		}
		return $html;
	}
	
	public function getHiddenInput ($svc_id, $search_params) {
		$html ='
		<input type="hidden" id="svc_hidden[]" name="svc_hidden[]" value="'.$svc_id.'">';
		return (!$search_params) ? $html : null;
	}
	
	public function getRoEntryTable($array) {
		// Establish easy access to vars
		$entry_form = $array['entry_form'];
		$date_range = $array['date_range'];
		$search_params = $array['search_params'];
		
		$search = json_decode($search_params['ro_params'], true);
		//echo '$search: '.var_dump($search).'<br>';
		
		// Get table data. Returns array('repairorder_welr'=>$ro, 'servicerendered_welr'=>$svc) and sets $_SESSION['current_ro_count']
		$data = $this->getRos(array('dealer_id' => $_SESSION['dealer_id'], 'entry_form' => $entry_form, 
								    'update_form' => false, 'update_ro_id' => null, 'date_range' => $date_range, 
								    'search_params' => $search_params));
		
		// Set RO count based on params
		$ro_count = ($entry_form) ? $this->getRoCount(array('dealer_id'=>$_SESSION['dealer_id'], 'date_range'=>false)) : $_SESSION['current_ro_count'];
		
		// Build export function
		$export= "";
		$export .= "Export Date: " .date("l F d Y");
		$export .= "\n";
		$export .= "\n";
		$export .= MANUF ." - ".ENTITY. " ".$_SESSION['dealer_code'];
		$export .= "\n";
		$export .= "Repair Order Export - Total ROs: ".$ro_count;
		$export .= "\n";
		// Build html
		$html ='
		<div class="box">
		<div class="box-body">
		<div class="row">
			<div class="medium-12 large-12 columns">
			<!--<hr>-->
				<div id="update_div4">
					<div class="row">';
					if($search['search_feedback']) {
						$html .='
						<div class="small-12 medium-12 large-12 columns">
							<p class="search_feedback">'.$search['search_feedback'].' (Results: '.$ro_count.')</p>
						</div>';
					}
					//$this->getRoCount($_SESSION['dealer_id'], $date_range)
					if ($entry_form) {
						$html .='
						<div class="small-12 medium-10 large-10 columns">
							<p class="medium-title">Total Repair Orders: '.$ro_count.'
								<span class="ro_table_subtitle blue"> (Showing last 5 entries) </span>	
							</p>
						</div>
						<div class="small-12 medium-2 large-2 columns">
							<p class="medium-title right-align">
								<a class="view_ros_link" name="view_ros_month" href="viewall_ros_welr.php">View Recent</a>
							</p>
						</div>';
					}
					$html .='
					</div>
				</div>
			</div>
		</div>
		<div id="update_div3">
			<div class="row">
				<div class="small-12 medium-12 large-12 columns">
					<div class="table-container">
					<table id="enterrotable" class="original">
						<thead>
							<tr>
								<th><a>	Action			</a></th>
								<th><a>	RO #			</a></th>
								<th><a>	RO Date			</a></th>
								<th><a>	Model			</a></th>
								<th><a>	Mileage			</a></th>
								<th><a>	Make			</a></th>
								<th><a>	Labor			</a></th>
								<th><a>	Parts			</a></th>
								<th><a>	Services		</a></th>
								<th><a>	Comments		</a></th>
							</tr>
						</thead>
						<tbody>';
						
						$export .= "\n";
						$export .= "\n";
						$export .= "RO Number,";
						$export .= "RO Date,";
						$export .= "Model Year,";
						$export .= "Mileage Spread,";
						$export .= "Vehicle Make,";
						$export .= "Labor,";
						$export .= "Parts,";
						$export .= "Services,";
						$export .= "Comments,";
						$export .= "Date Entered,";
						$export .= "User";
						$export .= "\n";
						
						for($i=0; $i<sizeof($data['repairorder_welr']); $i++) {
							// Easy access to roID and ronumber fields
							$ro_id[$i] = $data['repairorder_welr'][$i]['roID'];
							$ro_number[$i] = $data['repairorder_welr'][$i]['ronumber'];
							
							// Customize ro date using PHP DateTime object
							$date = new DateTime($data['repairorder_welr'][$i]['ro_date']);
							$date = $date->format("m/d/Y");
							$ro_date[$i] = $date;
							
							// Customize year.  If year == 2000, concatenate a '+' to the end of it
							$ro_yearmodel[$i] = $data['repairorder_welr'][$i]['modelyear'];
							$ro_yearmodel[$i] = ($ro_yearmodel[$i] == '2000') ? $ro_yearmodel[$i].'+' : $ro_yearmodel[$i];
							
							// Easy access to mileage spread
							$ro_mileage[$i] = $data['repairorder_welr'][$i]['carmileage'];
							
							// Easy access to vehicle make
							$ro_make[$i] = $data['repairorder_welr'][$i]['vehicle_make'];
							
							// Customize parts and labor.  If value == NULL, show as blank (allows for filtering).  Else add a $ sign to value
							$ro_labor[$i] = $data['repairorder_welr'][$i]['labor'];
							$ro_labor[$i] = ($ro_labor[$i] == NULL) ? '' : '$'.$ro_labor[$i];
							$ro_parts[$i] = $data['repairorder_welr'][$i]['parts'];
							$ro_parts[$i] = ($ro_parts[$i] == NULL) ? '' : '$'.$ro_parts[$i];
							
							// Easy acces to ro comment
							$ro_comment[$i] = $data['repairorder_welr'][$i]['comment'];
							
							// Get create date for export
							$ro_create_date[$i] = $data['repairorder_welr'][$i]['create_date'];
							
							// Access username
							$ro_user_name[$i] = $data['repairorder_welr'][$i]['user_name'];
							
							// Customize and build services display for easy access
							$ro_svc = array();
							$ro_services = array();
							$added = array(); // Reset the $added and $declined arrays.  Otherwise, will concatenate additional +'s and *'s
							$declined = array();
							$svc_array_size = sizeof($data['servicerendered_welr'][$i]);
							for($s=0; $s<$svc_array_size; $s++) {
								// Set easy access variable names for array elements
								$ro_svc[$i][$s] = $data['servicerendered_welr'][$i][$s]['servicedescription'];
								// Format added services sign
								$add_svc[$i][$s] = $data['servicerendered_welr'][$i][$s]['addsvc'];
								$added[$i][$s] = ($add_svc[$i][$s] == 1) ? "+" : "";
								// Format declined services sign
								$dec_svc[$i][$s] = $data['servicerendered_welr'][$i][$s]['decsvc'];
								$declined[$i][$s] = ($dec_svc[$i][$s] == 1) ? "*" : "";
							}
							
							$x = 0;
							$list = '';
							foreach ($ro_svc as $service) {
								foreach($service as $desc) {
									if($x == (sizeof($service) - 1)) { 
										$list .= $desc.$added[$i][$x].$declined[$i][$x];
									} else { 
										$list .= $desc.$added[$i][$x].$declined[$i][$x].", ";
									}
									$x += 1;
								}
							}
							$ro_services[$i] = $list;
							
							// Build table row data.  Make sure that form contains both ro_id and ro_number for passing
							$html .='
							<tr>
								<td class="submit_td">
									<form id="update_ro_form" action="'.PROCESS_FILE.'" method="post">
										<input type="hidden" name="action" value="ro_update" />
										<input type="hidden" name="update_ro_data" value="'.$ro_id[$i].' '.$ro_number[$i].'"/>
										<input type="submit" value="Select" class= "tiny button radius"/>
									</form>
								</td>
								<td>'.$ro_number[$i].'</td>
								<td>'.$ro_date[$i].'</td>
								<td>'.$ro_yearmodel[$i].'</td>
								<td>'.$ro_mileage[$i].'</td>
								<td>'.$ro_make[$i].'</td>
								<td>'.$ro_labor[$i].'</td>
								<td>'.$ro_parts[$i].'</td>
								<td>'.$ro_services[$i].'</td>
								<td>'.$ro_comment[$i].'</td>
							</tr>';
							
							// Build remaining export data
							$export .= '"'.$ro_number[$i].		'",';
							$export .= '"'.$ro_date[$i].		'",';  
							$export .= '"'.$ro_yearmodel[$i].	'",';
							$export .= '"'.$ro_mileage[$i].		'",';
							$export .= '"'.$ro_make[$i].		'",';
							$export .= '"'.$ro_labor[$i].		'",';
							$export .= '"'.$ro_parts[$i].		'",';
							$export .= '"'.$ro_services[$i].	'",';
							$export .= '"'.$ro_comment[$i].		'",';
							$export .= '"'.$ro_create_date[$i].	'",';
							$export .= $ro_user_name[$i].	"\n";
						}
						//echo '$ro_services: '.var_dump($ro_services).'<br>'; string "....."
						// Save $export as SESSION var so it is ready for export when called by user
						//$matts = "matt, matt, matt, matt";
						//$export .= '"'.$matts.'"'; this also works
						//$export .= '"matt, matt, matt, matt"'; this works
						$_SESSION['export_ros'] = $export;
						
						// Build remaining table structure
						$html .='
						</tbody>
					</table>
					</div> <!-- end div table-container -->
				</div><!-- end div small-12 medium-12 large-12 columns -->
			</div><!-- end div row -->
		</div><!-- end div update_div3 -->
		</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		return $html;
	}
	
	public function RoSvcSearchQuery($search_params) {
		$dealer_id = (isset($_SESSION['dealer_id'])) ? $_SESSION['dealer_id'] : null;
		//return 'svc_reg: ' . var_dump($svc_reg).'<br>';
		// Set variables from array
		$ro_range = $search_params['ro_params'];
		$ro_range1 = $ro_range['ro_num1']; // Don't know what to do with these yet
		$ro_range2 = $ro_range['ro_num2'];
		if($ro_range1) {
			$ro_between_stmt = " AND ronumber BETWEEN ".$ro_range1." AND ".$ro_range2;
		} else {
			$ro_between_stmt = "";
		}
		
		// Each of these is a string of serviceIDs. Will be transformed into arrays later on with explode function
		$svc_exclude = $search_params['svc_exclude'];
		$svc_reg = $search_params['svc_reg'];
		$svc_add = $search_params['svc_add'];
		$svc_dec = $search_params['svc_dec'];
		
	 	// First, run excluded services to get list of excluded RO numbers (if count($array['svc_exclude']) > 0)
	 	if($svc_exclude == 'false') {
	 		$svc_exclude = "";
	 	} else {
			$sql = "SELECT ronumber FROM servicerendered_welr
					WHERE dealerID = $dealer_id AND serviceID IN($svc_exclude) ";
			$result = $this->dbo->query($sql);
			if (!$result) {
				return 'Services query error. See administrator: '.$this->dbo->error;
			} else {
				$index = 0;
				$rows = $result->num_rows;
				while($item = $result->fetch_assoc()) {
					// Build ronumber data array
					$ronumber[$index] = $item['ronumber'];
					$index += 1;
				}
			}
			// Build list for next query NOT IN statement
			$ro_string = '';
			for($i=0; $i<$rows; $i++) {
				if($i == $rows-1) {
					$ro_string .= $ronumber[$i];
				} else {
					$ro_string .= $ronumber[$i].', ';
				}	
			}
			//return 'ro_string: ' . var_dump($ro_string);
			$svc_exclude = " AND ronumber NOT IN($ro_string) ";
		}
		
		/* Initialize sql array to hold sql statements, and begin first statement.  
		 * Last item must have text removed 'AND ronumber IN'
		**/
		$sql = array();
		$sql[] = "SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND ronumber IN ";
		
		// Build serviceID arrays if != 'false'
		if($svc_reg != 'false' || $svc_add != 'false' || $svc_dec != 'false') {
			
			// Build arrays from checkbox strings
			if($svc_reg != 'false') $svc_reg = explode(',', $svc_reg);
			if($svc_add != 'false') $svc_add = explode(',', $svc_add);
			if($svc_dec != 'false') $svc_dec = explode(',', $svc_dec);
			
			// Case 1: see if there are matches in all three arrays
			if(is_array($svc_reg) && is_array($svc_add) && is_array($svc_dec)) {
				$svc_reg_add_dec = array_intersect($svc_reg, $svc_add, $svc_dec);
				// If commonalities were found, create SQL statement(s) and then remove serviceIDs from each array
				if(count($svc_reg_add_dec) > 0) {
					//echo '$svc_reg b4 remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_add b4 remove: '.var_dump($svc_add).'<br>';
					//echo '$svc_dec b4 remove: '.var_dump($svc_dec).'<br>';
					
					foreach($svc_reg_add_dec as $svc) {
					  $sql[] = 
						" (SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc)
					  	  AND ronumber IN ";
					  	  
						// Now remove serviceID from each array
						$key = array_search($svc, $svc_reg);
						unset($svc_reg[$key]);
						
						$key = array_search($svc, $svc_add);
						unset($svc_add[$key]);
				
						$key = array_search($svc, $svc_dec);
						unset($svc_dec[$key]);
						
					}
					
					//echo '$svc_reg_add_dec: '.var_dump($svc_reg_add_dec).'<br>';
					//echo '$svc_reg after remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_add after remove: '.var_dump($svc_add).'<br>';
					//echo '$svc_dec after remove: '.var_dump($svc_dec).'<br>';	
				}
				
			}
			
			// Case 2: see if there are matches between reg and add arrays
			if(is_array($svc_reg) && is_array($svc_add)) {
				$svc_reg_add = array_intersect($svc_reg, $svc_add);
				// If commonalities were found, create SQL statement(s) and then remove serviceIDs from each array
				if(count($svc_reg_add) > 0) {
					//echo '$svc_reg b4 remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_add b4 remove: '.var_dump($svc_add).'<br>';
					
					foreach($svc_reg_add as $svc) {
						$sql[] =
						 "(SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND addsvc = 0 AND decsvc = 0
					  	   UNION
					  	   SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND addsvc = 1
					  	  ) 
					  	  AND ronumber IN ";
											  	  
					  	// Now remove serviceID from each array
						$key = array_search($svc, $svc_reg);
						unset($svc_reg[$key]);
						
						$key = array_search($svc, $svc_add);
						unset($svc_add[$key]);
					}
					//echo '$svc_reg_add: '.var_dump($svc_reg_add).'<br>';
					//echo '$svc_reg after remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_add after remove: '.var_dump($svc_add).'<br>';
				}
			}
			
			// Case 3: see if there are matches between reg and dec arrays
			if(is_array($svc_reg) && is_array($svc_dec)) {
				$svc_reg_dec = array_intersect($svc_reg, $svc_dec);
				// If commonalities were found, create SQL statement(s) and then remove serviceIDs from each array
				if(count($svc_reg_dec) > 0) {
					//echo '$svc_reg b4 remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_dec b4 remove: '.var_dump($svc_dec).'<br>';
					
					foreach($svc_reg_dec as $svc) {
						$sql[] =
						 "(SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND addsvc = 0 AND decsvc = 0
					  	   UNION
					  	   SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND decsvc = 1
					  	  ) 
					  	  AND ronumber IN ";
											  	  
					  	// Now remove serviceID from each array
						$key = array_search($svc, $svc_reg);
						unset($svc_reg[$key]);
						
						$key = array_search($svc, $svc_dec);
						unset($svc_dec[$key]);
					}
					//echo '$svc_reg_dec: '.var_dump($svc_reg_dec).'<br>';
					//echo '$svc_reg after remove: '.var_dump($svc_reg).'<br>';
					//echo '$svc_dec after remove: '.var_dump($svc_dec).'<br>';
				}
			}
			
			// Case 4: see if there are matches between add and dec arrays
			if(is_array($svc_add) && is_array($svc_dec)) {
				$svc_add_dec = array_intersect($svc_add, $svc_dec);
				// If commonalities were found, create SQL statement(s) and then remove serviceIDs from each array
				if(count($svc_add_dec) > 0) {
					//echo '$svc_add b4 remove: '.var_dump($svc_add).'<br>';
					//echo '$svc_dec b4 remove: '.var_dump($svc_dec).'<br>';
					
					foreach($svc_add_dec as $svc) {
						$sql[] =
						 "(SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND addsvc = 1
					  	   UNION
					  	   SELECT ronumber FROM servicerendered_welr WHERE dealerID = $dealer_id AND serviceID = $svc AND decsvc = 1
					  	  ) 
					  	  AND ronumber IN ";
											  	  
					  	// Now remove serviceID from each array
						$key = array_search($svc, $svc_add);
						unset($svc_add[$key]);
						
						$key = array_search($svc, $svc_dec);
						unset($svc_dec[$key]);
					}
					//echo '$svc_add_dec: '.var_dump($svc_add_dec).'<br>';
					//echo '$svc_add after remove: '.var_dump($svc_add).'<br>';
					//echo '$svc_dec after remove: '.var_dump($svc_dec).'<br>';
				}
			}
			//exit;
			
			/* Build the remaining query statements based on remaining service array values.
			 * $svc_reg, $svc_add and $svc_dec at this point only have unique values.  Dupes have been removed.
			**/
			if (is_array($svc_reg)) {
				foreach($svc_reg as $svc) {
					$sql[] = " (SELECT ronumber FROM servicerendered_welr WHERE serviceID = $svc AND addsvc = 0 AND decsvc = 0) AND ronumber IN ";
				}		
			}
			if (is_array($svc_add)) {
				foreach($svc_add as $svc) {
					$sql[] = " (SELECT ronumber FROM servicerendered_welr WHERE serviceID = $svc AND addsvc = 1) AND ronumber IN ";
				}		
			}
			if (is_array($svc_dec)) {
				foreach($svc_dec as $svc) {
					$sql[] = " (SELECT ronumber FROM servicerendered_welr WHERE serviceID = $svc AND decsvc = 1) AND ronumber IN ";
				}		
			}
			
			// Print sql statement from $sql array
			$stmt = "";
			$count = count($sql);
			for($i=0; $i<$count; $i++) {
				if($i == $count-1) {
					$stmt .= substr($sql[$i], 0, -17);
				} else {
					$stmt .= $sql[$i];
				}
			}
			
			// Now concatenate remaining statements	
			$stmt .= $ro_between_stmt.$svc_exclude." GROUP BY ronumber ORDER By ronumber ";	
			//echo 'stmt: '.$stmt;
		}
		//exit;
		
		// Now execute the query
		$result = $this->dbo->query($stmt);
		if (!$result) {
			$_SESSION['error'][] = 'Repair Order search error.  See administrator.';
			return 'Repair Order search error.  See administrator.';
		} else {
			$rows = $result->num_rows;
			$ronumbers = array();
			$ro_list = "";
			$index = 0;
			// Build $ronumber array. Test successful
			while ($item = $result->fetch_assoc()) {
				$ronumbers[$index] = $item['ronumber'];
				if($index == $rows-1) {
					$ro_list .= $ronumbers[$index];
				} else {
					$ro_list .= $ronumbers[$index].', ';
				}
				$index += 1;
			}
		}
		// Test successful
		//echo '$ro_list: ' .var_dump($ro_list).'<br>';
		return $ro_list = ($rows > 0) ? $ro_list : false;
	}
	
	public function getRos($array) {
		/* Note if $entry_form = false, then supply the whole list.  Else limit result to five entries.
		 * If $update is true, add AND clause for one ro selection
		 */
		 
		//echo 'does entry_form key exist?: '.var_dump(array_key_exists('entry_form', $array));
		 
		// Set date range to SESSION vars. As fail-safe, set beginning and end dates to current month
		$ro_date_range1 = (isset($_SESSION['ro_date_range1'])) ? $_SESSION['ro_date_range1'] : date("Y-m-01");
		$ro_date_range2 = (isset($_SESSION['ro_date_range2'])) ? $_SESSION['ro_date_range2'] : date("Y-m-d");
		
		// Set $dealer_id and $update_ro_id from $array to prevent apostrophe SQL conflicts
		$dealer_id = $array['dealer_id'];
		$update_ro_id = $array['update_ro_id'];
		
		$sql = "SELECT r.roID, r.ronumber, r.ro_date, r.yearmodelID, y.modelyear, r.model_age, r.mileagespreadID, 
					   m.carmileage, r.vehicle_make_id, v.vehicle_make, r.singleissue, r.labor, r.parts, r.comment, r.create_date, r.userID, u.user_name
				FROM repairorder_welr r
				LEFT JOIN yearmodel y ON(r.yearmodelID = y.yearmodelID)
				LEFT JOIN mileagespread m ON(r.mileagespreadID = m.mileagespreadID)
				LEFT JOIN vehicle_make v ON(r.vehicle_make_id = v.vehicle_make_id)
				LEFT JOIN user u ON(r.userID = u.user_id)
				WHERE r.dealerID = $dealer_id ";
		
		// If update_form is true, set filter to update_ro_id. Else include ro_date filter		
		if($array['update_form']) {
			$sql .=" AND r.roID = $update_ro_id ";
		} elseif ($array['date_range']) {
			$sql .=" AND r.ro_date BETWEEN '$ro_date_range1' AND '$ro_date_range2' ";
		}
		
		// Initialize $ro_svc_search_stmt variable
		$ro_svc_in_stmt = "";
		
		if($array['search_params']) {
			// Turn search_params items into regular array if true
			$search = $array['search_params'];
			$ro_params = json_decode($search['ro_params'], true);
			$search_feedback = $ro_params['search_feedback'];
			$svc_reg = $search['svc_reg'];
			$svc_add = $search['svc_add'];
			$svc_dec = $search['svc_dec'];
			$svc_exclude = $search['svc_exclude'];
			
			/* If any service search boxes were checked and the ro range was entered, 
			 * remove the below ro range as it is already accounted for.
			 * Also set $svc_ro_search = true to add nested SELECT statement
			**/
			if($svc_reg != 'false' || $svc_add != 'false' || $svc_dec != 'false' || $svc_exclude != 'false') {
				/* If any services were checked on the RO search form, run the servicerendered_welr search queries
			 	 * Returns a list of RO numbers for filtering in below queries
				**/
				$ro_svc_search_list = $this->RoSvcSearchQuery($search_params = array('search_feedback'=>$search_feedback, 'ro_params'=>$ro_params, 'svc_reg'=>$svc_reg, 'svc_add'=>$svc_add, 'svc_dec'=>$svc_dec, 'svc_exclude'=>$svc_exclude));
				//echo 'ro_svc_search_list: '.$ro_svc_search_list.'<br>';
				if(!$ro_svc_search_list) {
					$ro_svc_search_list = 0;
				}
				//return 'ro_svc_search_list: '.$ro_svc_search_list.'<br>';
				$ro_svc_in_stmt = " AND r.ronumber IN (SELECT ronumber FROM repairorder_welr WHERE ronumber IN($ro_svc_search_list)) ";
				$ro_range = false;
			} else {
				$ro_range = true;
				$ro_svc_in_stmt = "";
			}
			
			//return 'msg: '.$search_feedback.'<br> ro_params: '.var_dump($ro_params).'<br> svc_reg: '.$svc_reg.'<br> svc_add: '.$svc_add.'<br> svc_exclude: '.$svc_exclude.'<br>';
			
			//$array = $array['search_params'];
			if($ro_range) {
				if($ro_params['ro_num1']) {
					$sql .=" AND r.ronumber BETWEEN ".$ro_params['ro_num1']." AND ".$ro_params['ro_num2'];
				}
			}
			if($ro_params['ro_date1']) {
				// Place dates back in SQL format
				$date1 = new DateTime($ro_params['ro_date1']);
				$date1 = $date1->format('Y-m-d');
				$date2 = new DateTime($ro_params['ro_date2']);
				$date2 = $date2->format('Y-m-d');
				$sql .=" AND r.ro_date BETWEEN '$date1' AND '$date2' ";
			}
			// Since year 2000 id == 0, cannot just check for false
			if(is_numeric($ro_params['year1_id'])) {
				//echo '$ro_params[year1_id]: '.$ro_params['year1_id'].'<br>';
				$sql .=" AND r.yearmodelID BETWEEN ".$ro_params['year1_id']." AND ".$ro_params['year2_id'];
			}
			if($ro_params['mileage1_id']) {
				$sql .=" AND r.mileagespreadID BETWEEN ".$ro_params['mileage1_id']." AND ".$ro_params['mileage2_id'];
			}
			if($ro_params['labor1']) {
				$sql .=" AND r.labor BETWEEN ".$ro_params['labor1']." AND ".$ro_params['labor2'];
			}
			if($ro_params['parts1']) {
				$sql .=" AND r.parts BETWEEN ".$ro_params['parts1']." AND ".$ro_params['parts2'];
			}
			if($ro_params['vehicle1_id']) {
				$sql .=" AND r.vehicle_make_id = ".$ro_params['vehicle1_id'];
			}
			if($ro_params['advisor1_id']) {
				$sql .=" AND r.userID = ".$ro_params['advisor1_id'];
			}
		}
		
		$sql .=$ro_svc_in_stmt." ORDER BY r.roID DESC ";
		
		if($array['entry_form']) {
			$sql .=" LIMIT 5 ";
		}
		
		//echo '$sql: ' . $sql. '<br>';
		  
		$result = $this->dbo->query($sql);
		if (!$result) {
			$_SESSION['error'][] = 'Repair Order read error.  See administrator.';
			//return false;
		} else {
			$rows = $result->num_rows;
			// Save $rows as SESSION var for display of RO count
			$_SESSION['current_ro_count'] = $rows;
			
			$ro = array();
			$index = 0;
			// Build $ro associative array. Test successful
			while ($item = $result->fetch_assoc()) {
				$ro[$index]['roID'] = $item['roID'];
				$ro[$index]['ronumber'] = $item['ronumber'];
				$ro[$index]['ro_date'] = $item['ro_date'];
				$ro[$index]['yearmodelID'] = $item['yearmodelID'];
				$ro[$index]['modelyear'] = $item['modelyear'];
				$ro[$index]['model_age'] = $item['model_age'];
				$ro[$index]['mileagespreadID'] = $item['mileagespreadID'];
				$ro[$index]['carmileage'] = $item['carmileage'];
				$ro[$index]['vehicle_make_id'] = $item['vehicle_make_id'];
				$ro[$index]['vehicle_make'] = $item['vehicle_make'];
				$ro[$index]['singleissue'] = $item['singleissue'];
				$ro[$index]['labor'] = $item['labor'];
				$ro[$index]['parts'] = $item['parts'];
				$ro[$index]['comment'] = $item['comment'];
				$ro[$index]['create_date'] = $item['create_date'];
				$ro[$index]['userID'] = $item['userID'];
				$ro[$index]['user_name'] = $item['user_name'];
				$index += 1;
			}
		}
		
		// Now get associated RO service data from servicerendered_welr table by stepping through each ronumber from above query
		$svc = array();
		$index2 = 0;
		for($i=0; $i < $rows; $i++) {
			$sql = "SELECT b.servicedescription, a.serviceID, a.ronumber, a.addsvc, a.decsvc FROM servicerendered_welr a
					NATURAL JOIN services b
					WHERE a.dealerID = $dealer_id AND a.ronumber = ".$ro[$i]['ronumber']." ORDER By b.servicesort_metrics ";
			$result2 = $this->dbo->query($sql);
			if (!$result2) {
				$_SESSION['error'][] = 'Services query error. See administrator.';
			} else {
				$rows2 = $result2->num_rows;
				$stuff = 0;
				$svc_inc = 1;
				while($item = $result2->fetch_assoc()) {
					// Build services data array.  Test successful.
					$svc_reg[$svc_inc] = $item['serviceID'];
					$svc_add[$svc_inc] = $item['addsvc'];
					$svc_dec[$svc_inc] = $item['decsvc'];
					$svc[$index2][$stuff]['ronumber'] = $item['ronumber'];
					$svc[$index2][$stuff]['servicedescription'] = $item['servicedescription'];
					$svc[$index2][$stuff]['addsvc'] = $item['addsvc'];
					$svc[$index2][$stuff]['decsvc'] = $item['decsvc'];
					$stuff += 1;
					$svc_inc += 1;
				}
			}
			$index2 += 1;
		}
		return array('repairorder_welr'=>$ro, 'servicerendered_welr'=>$svc, 'svc_reg'=>$svc_reg, 'svc_add'=>$svc_add, 'svc_dec'=>$svc_dec);
	}
	
	public function checkRoDuplicate($ronumber,$dealer_id) {
		$sql = "SELECT ronumber FROM repairorder_welr WHERE ronumber = $ronumber AND dealerID = $dealer_id";
		$result = $this->dbo->query($sql);
		$rows = $result->num_rows;
		if ($rows > 0) {
			// Error - duplicate repair order, deny request
			return false;
		} else {
			return true;
		}
	}
	
	// Process input of all RO data
	public function processRoEntry($array) {
		
		// If delete process, execute delete instruction before running validation etc. Script will stop due to return value.
		if($array['submit_name'] == 'delete_ro') {
			$this->deleteRo($array);
		}
		
		// Instaniate ErrorHandling class for emailing of important admin errors
		$error_obj = new ErrorHandling;
	
		// Set dealer_id variable
		$dealer_id = $array['dealer_id'];
		
		// Instantiate Validation class for input validation and
		$valid = new Validation;
		
		// Validate and capture ro number
		if(!($valid->validWholeNumber($this->dbo->real_escape_string($array['ronumber'])))) { $_SESSION['error'][] = 'Invalid RO number was entered.'; }
		
		// Validate, capture and reformat RO date using DateTime object
		if(!($valid->validDate($this->dbo->real_escape_string($array['ro_date'])))) { $_SESSION['error'][] = 'Invalid date format was entered.'; }
		// If date was valid, now change back into sql-compatible format
		$date = new DateTime($array['ro_date']);
		$array['ro_date'] = $date->format('Y-m-d');
		
		/* No regex validation necessary for these as they are dropdown options
		 * Explode yearmodel values into an array
		 */
		$yearmodel = explode(' ', $this->dbo->real_escape_string($array['yearmodel']));
		$array['yearmodelID'] = $yearmodel[0];
		$array['modelyear']   = $yearmodel[1];
		
		$mileage = explode(',', $this->dbo->real_escape_string($array['mileage']));
		$array['mileagespreadID'] = $mileage[0];
		$array['carmileage'] = $mileage[1];
		
		$vehicle = explode(',', $this->dbo->real_escape_string($array['vehicle']));
		$array['vehicle_make_id'] = $vehicle[0];
		$array['vehicle_make'] = $vehicle[1];
		
		/*
		echo 'yrarray: ' . var_dump($yearmodel) . '<br> milearray: ' . var_dump($mileage) . '<br> veharray: ' . var_dump($vehicle);
		echo '<br>veh_id: ' . $array['vehicle_make_id'] . '<br> veh: ' . $array['vehicle_make'] . '<br> year: ' . $array['yearmodelID'] . '<br> modyear: ' . $array['modelyear'] . '<br> mile_id: ' . $array['mileagespreadID'] . '<br> carmile: ' . $array['carmileage'];
		exit;
		*/
		
		// Validate and capture labor and parts amounts
		if(!($valid->validDollarValue($this->dbo->real_escape_string($array['labor'])))) { $_SESSION['error'][] = 'Invalid labor amount was entered.'; }
		$array['labor'] = ($array['labor'] == '') ? NULL : $array['labor'];
		
		if(!($valid->validDollarValue($this->dbo->real_escape_string($array['parts'])))) { $_SESSION['error'][] = 'Invalid parts amount was entered.'; }
		$array['parts'] = ($array['parts'] == '') ? NULL : $array['parts'];
		
		$array['comment'] = trim($this->dbo->real_escape_string($array['comment']));
		//preg_replace( "/\r|\n/", "", $array['comment']);  // Make sure that carriage lines etc are removed. This doesn't work!
		
		// Make sure that $svc_reg has at least one service checked before inserting RO and services.  Also set $singleissue value based on this.
		if(count($array['svc_reg']) == 0) {
			$_SESSION['error'][] = 'At least one regular service must be selected.';
		} elseif (count($array['svc_reg']) == 1) {
			$array['singleissue'] = 1;
		} else {
			$array['singleissue'] = 0;
		}
		
		/* If there were entry errors, prevent db insert and return generic entry error message
		 * Note:  This should never execute because you have performed js validation on client side
		 */
		/*
		if(is_array($_SESSION['error'])) {	
			foreach($_SESSION['error'] as $error) {
				echo 'error: '.$error.'<br>';
			}
			return 'error_entry_validation';
			unset($_SESSION['error']);
		}
		*/
		
		// Set user_id. If user changes advisor on the fly using the selection dropdown, change $user_id to SESSION variable
		if (isset($_SESSION['advisor_id'])) {
			$array['user_id'] = $_SESSION['advisor_id'];
		} else {
			$array['user_id'] = $_SESSION['user']['user_id'];
		}
		
		// Calculate current auto age relative to $currentyear.  This value will be used for comparison and aggregate data
		$curr_year = date('Y');
		$array['model_age'] = ($curr_year - $array['modelyear']);
		// If year model selected from main form = next year (would be the case if month < 7), make sure this is not recorded as a negative.  Would be 0, or most recent model (same as 2015).
		if ($array['model_age'] < 0) {
			$array['model_age'] = 0;
		}
		if ($array['model_age'] > 9) {
			$array['model_age'] = 9;
		}
		$array['manuf_id'] = MANUF_ID;
		
		// Set $create_date for sql prepared statements using constant from init.inc.php
		$array['create_date'] = CREATE_DATE;
		
		// Complete DB action based on submit_name instruction
		switch($array['submit_name']) {
			CASE 'insert_ro':
				return $this->insertRo($array);
				break;
			CASE 'update_ro':
				return $this->updateRo($array);
				break;
			CASE 'delete_ro':
				return $this->deleteRo($array);
				break;
			default:
				return 'error_ro_insert';
		}
	}
	
	/* Function to insert records into servicerendered_welr table
	 * Code is used for both insertRO() and updateRo() methods
	 * Separated into own method to conform to DRY
	 */
	public function insertSvcrenderedRecord($array) {
		// Initialize $services_error to true.  Will become false if error occurs;
		$services_success = true;
		
		/*
		echo '$svc_hidden: '.var_dump($array['svc_hidden']).'<br>';
		echo '$svc_reg: '.var_dump($array['svc_reg']).'<br>';
		echo '$svc_add: '.var_dump($array['svc_add']).'<br>';
		echo '$svc_dec: '.var_dump($array['svc_dec']).'<br>';
		*/
		
		// Create prepared statement for servicerendered_welr insert
		$sql  = "INSERT INTO servicerendered_welr 
				 (ronumber, ro_date, singleissue, serviceID, addsvc, decsvc, dealerID, manuf_id, create_date, userID)
				 VALUES (?,?,?,?,?,?,?,?,?,?)";
		if(!($stmt = $this->dbo->prepare($sql))) {
			$error = "Prepare failed: (" . $this->dbo->errno . ") " . $this->dbo->error;
			sendError($error, __LINE__, __FILE__);
			return 'error_svc_insert';
		}
		if(!($stmt->bind_param("isiiiiiisi", $array['ronumber'], $array['ro_date'], $array['singleissue'], $service, $addsvc, $decsvc, $array['dealer_id'], $array['manuf_id'], $array['create_date'], $array['user_id']))) {
			$error = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			return 'error_svc_insert';
		}
		
		// Are any of the Add's unmatched with services?  If so, not valid.
		foreach ($array['svc_hidden'] as $service) {
			if (count($array['svc_reg']) > 0) {
				foreach ($array['svc_reg'] as $reg) {
					if ($service == $reg) {
						$service = $service;
						$addsvc  = 0;
						$decsvc  = 0;
						// Insert service into servicerendered table
						if(!$stmt->execute()) {
							$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
							sendError($error, __LINE__, __FILE__);
							$services_success = false;
						}
					}
				}
			}
			if (count($array['svc_add']) > 0) {
				foreach ($array['svc_add'] as $add) {
					if ($service == $add) {
						$service = $service;
						$addsvc = 1;
						$decsvc = 0;
						// Insert service into servicerendered table
						if(!$stmt->execute()) {
							$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
							sendError($error, __LINE__, __FILE__);
							$services_success = false;
						}
					}
				}
			}
			if (count($array['svc_dec']) > 0) {
				foreach ($array['svc_dec'] as $dec) {
					if ($service == $dec) {
						$service = $service;
						$addsvc  = 0;
						$decsvc  = 1;
						// Insert service into servicerendered table
						if(!$stmt->execute()) {
							$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
							sendError($error, __LINE__, __FILE__);
							$services_success = false;
						}
					}
				}
			}
		}
		$stmt->close();
		return (!$services_success) ? false : true;
	}
	
	public function deleteSvcrenderedRecord($array) {
		// Initialize $svc_delete to true.  Will become false if error occurs;
		$svc_delete = true;
		
		// Check SESSION vars to see if $_SESSION['update_ronumber'] == true
		$ronumber = (isset($_SESSION['update_ronumber'])) ? $_SESSION['update_ronumber'] : $array['ronumber'];
		
		// Prepare statement
			$sql = "DELETE FROM servicerendered_welr WHERE ronumber = ? AND dealerID = ?";
			if(!($stmt = $this->dbo->prepare($sql))) {
				$error = "Prepare failed: (" . $this->dbo->errno . ") " . $this->dbo->error;
				sendError($error, __LINE__, __FILE__);
				$svc_delete = false;
			}
			
			// Bind parameters
			if(!($stmt->bind_param("ii", $ronumber, $array['dealer_id']))) {
				$error = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
				sendError($error, __LINE__, __FILE__);
				$svc_delete = false;
			}
			
			// Execute Statement
			if(!$stmt->execute()) {
				$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
				sendError($error, __LINE__, __FILE__);
				$svc_delete = false;
			}
			$stmt->close();
			return (!$svc_delete) ? false : true;
	}
	
	public function updateRo($array) {
	
		/* First check to see if $array['ronumber'] was changed from original POST.  
		 * If so, run checkRoDuplicate() method
		 * Return 'error_ro_dupe' if method returns false
		 * Unset SESSION var
		 */
		if(isset($_SESSION['update_ronumber'])) {
			if($_SESSION['update_ronumber'] != $array['ronumber']) {
				if(!$this->checkRoDuplicate($array['ronumber'], $array['dealer_id'])) {
					return 'error_ro_dupe';
				}
			}
		}
	
		// Set status variable
		$ro_update = true;
			
		// Prepare statement
		$sql = "UPDATE repairorder_welr 
				SET ronumber = ?, ro_date = ?, yearmodelID = ?, model_age = ?, mileagespreadID = ?, vehicle_make_id = ?,
				    singleissue = ?, labor = ?, parts = ?, comment = ?
				WHERE roID = ? AND dealerID = ?";
		if(!($stmt = $this->dbo->prepare($sql))) {
			$error = "Prepare failed: (" . $this->dbo->errno . ") " . $this->dbo->error;
			sendError($error, __LINE__, __FILE__);
			$ro_update = false;
			return 'error_ro_update';
		}
		
		// Bind parameters
		if(!($stmt->bind_param("isiiiiiiisii", $array['ronumber'], $array['ro_date'], $array['yearmodelID'], $array['model_age'], 
			 $array['mileagespreadID'], $array['vehicle_make_id'], $array['singleissue'], $array['labor'], $array['parts'],
			 $array['comment'], $_SESSION['update_ro_id'], $array['dealer_id']))) {
			$error = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$ro_update = false;
			return 'error_ro_update';
		}
		
		// Execute statement
		if(!$stmt->execute()) {
			$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$ro_update = false;
			return 'error_ro_update';
		}
		
		// Close statement
		$stmt->close();
		
		// If repairorder_welr update was successful, now update servicerendered_welr table
		if($this->deleteSvcrenderedRecord($array)) {
			
			// Proceed with servicerendered_welr INSERT if delete succeeded
			if($this->insertSvcrenderedRecord($array)) {
				// Unset SESSION var
				unset($_SESSION['update_ronumber']);
				unset($_SESSION['update_ro_id']);
				
				// Return html for page update
				$html = $this->getPageHeading(array('page_title'=>'Enter Repair Orders', 'entry_form'=>true, 'update_form'=>false)).
						$this->getRoEntryForm($update_form = false, $update_ro_id = null, $search_params = false).
						$this->getRoEntryTable(array('entry_form'=>true, 'date_range'=>false, 'search_params'=>false, 'export'=>false));
				return $html;
			} else {
				return 'error_svc_insert';
			}
		} else {
			return 'error_svc_delete';
		}	
	}
	
	public function deleteRo($array) {
		
		// Check to make sure that user has not changed the ronumber if delete action is taking place. Disallowed.
		if(isset($_SESSION['update_ronumber'])) {
			if($_SESSION['update_ronumber'] != $array['ronumber']) {
				return 'error_ro_delete_rule';
			}
		}
		
		// Set status variable
		$delete_ro = true;
	
		// Prepare statement
		$sql = "DELETE FROM repairorder_welr WHERE ronumber = ? AND dealerID = ?";
		if(!($stmt = $this->dbo->prepare($sql))) {
			$error = "Prepare failed: (" . $this->dbo->errno . ") " . $this->dbo->error;
			sendError($error, __LINE__, __FILE__);
			$delete_ro = false;
			return 'error_ro_delete';
		}
		
		// Bind parameters
		if(!($stmt->bind_param("ii", $array['ronumber'], $array['dealer_id']))) {
			$error = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$delet_ro = false;
			return 'error_ro_delete';
		}
		
		// Execute statement
		if(!$stmt->execute()) {
			$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$delete_ro = false;
			return 'error_ro_delete';
		} 
		$stmt->close();
		
		if($this->deleteSvcrenderedRecord($array)) {
			// Unset SESSION var
			unset($_SESSION['update_ronumber']);
			
			// Return html for page update
			$html = $this->getPageHeading(array('page_title'=>'Enter Repair Orders', 'entry_form'=>true, 'update_form'=>false)).
					$this->getRoEntryForm($update_form = false, $update_ro_id = null, $search_params = false).
					$this->getRoEntryTable(array('entry_form'=>true, 'date_range'=>false, 'search_params'=>false, 'export'=>false));
			return $html;
		} else {
			return 'error_svc_delete';
		}
	}
	
	public function insertRo($array) {
	
		// First, make sure RO does not already exist before proceeding
		if(!$this->checkRoDuplicate($array['ronumber'], $array['dealer_id'])) {
			return 'error_ro_dupe';
		}
		
		// Save ronumber as SESSION var for permanent success message feedback on entry form.  Do not forget to unset later.
		$_SESSION['insert_ronumber'] = $array['ronumber'];
		
		// Initialize status variable
		$ro_insert = true;
		
		$sql = "INSERT INTO repairorder_welr
			    (ronumber, ro_date, yearmodelID, model_age, mileagespreadID, vehicle_make_id, singleissue, labor, parts, dealerID, manuf_id, create_date, userID, comment)
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		if(!($stmt = $this->dbo->prepare($sql))) {
			$error = "Prepare failed: (" . $this->dbo->errno . ") " . $this->dbo->error;
			sendError($error, __LINE__, __FILE__);
			$ro_insert = false;
			return 'error_ro_insert';
		}
		if(!($stmt->bind_param("isiiiiiiiiisis", $array['ronumber'], $array['ro_date'], $array['yearmodelID'], $array['model_age'], $array['mileagespreadID'], $array['vehicle_make_id'], $array['singleissue'], $array['labor'], $array['parts'], $array['dealer_id'], $array['manuf_id'], $array['create_date'], $array['user_id'], $array['comment']))) {
			$error = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$ro_insert = false;
			return 'error_ro_insert';
		}
		if(!$stmt->execute()) {
			$error = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			sendError($error, __LINE__, __FILE__);
			$ro_insert = false;
			return 'error_ro_insert';
		}
		$stmt->close();
		
		// If RO was successfully inserted, proceed with servicerendered_welr INSERT statement and return 
		if($this->insertSvcrenderedRecord($array)) {
			$html = $this->getPageHeading(array('page_title'=> 'Enter Repair Orders', 'ro_count'=>true, 'entry_form'=>true)).
					$this->getRoEntryForm($update_form = false, $update_ro_id = null, $search_params = false).
					$this->getRoEntryTable(array('entry_form'=>true, 'date_range'=>false, 'search_params'=>false, 'export'=>false));
					
			// Make sure that $_SESSION['insert_ronumber'] is unset for future actions
			unset($_SESSION['insert_ronumber']);
			
			return $html;
		} else {
			return 'error_svc_insert';
		}
	}	
}
?>