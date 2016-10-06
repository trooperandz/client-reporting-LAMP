<?php
/**
 * Program: class.Modal.inc.php
 * Created: 03/01/2016 by Matt Holland
 * Purpose: Produce modal for advanced search options etc
 * Methods: getRoSearchModal(): Create modal for RO search params
 			getMetricsDlrCompModal(): Create modal for dealer metrics comparison params
 			getMetricsSearchModal(): Create modal for filtering metrics data
 			getMetricsTrendModal(): Create modal for metrics trend params
 			getStatsSearchModal(): Create modal for filtering stats data
 * Updates:
 */

Class Modal extends DB_Connect {

	// Establish global vars for defining user access to specific modal components
    public $user_sos   ;
    public $user_manuf ;
    public $user_dlr   ;
    public $user_admin ;
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);

		// Initialize user types
		$this->user_sos   = ($_SESSION['user']['user_type_id'] == 1) ? true : false;
		$this->user_manuf = ($_SESSION['user']['user_type_id'] == 2) ? true : false;
		$this->user_dlr   = ($_SESSION['user']['user_type_id'] == 3) ? true : false;
		$this->user_admin = ($_SESSION['user']['user_admin']   == 1) ? true : false;
	}

	/**
	 * Build the RO Search Modal
	 * @param {string} $title The main modal title
	 * @return {string} $html The modal html
	 */
	public function getRoSearchModal($title) {
		// Instantiate Welr class for dropdown options
		$welr = new Welr($dbo=null);
		
		// Build html
		$html ='
	    <div id="ro_search_modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">'.$title.'</h2>
		 	<form id="ro_search_form">
		 	<div class="row">
		 		<div class="large-6 columns">
		 			<h4> Select RO Range: </h4>
		 			<fieldset>
						<label>RO Start Number <small class="form_ro_search_error" id="ro_error1">*You entered an invalid RO number</small>
							<input type="text" id="ro_num1" name="ro_num1" placeholder="Enter RO number">
						</label>
					
						<label>RO End Number
							<input type="text" id="ro_num2" name="ro_num2" placeholder="Enter RO number">
						</label>
					</fieldset>
		 		</div>
		 		
		 		<div class="large-6 columns">
		 			<h4> Select Dates: </h4>
		 			<fieldset>
						<label>Start Date <small class="form_ro_search_error" id="date_error1">*Please enter a valid date (mm/dd/yyyy)</small>
							<input type="text" id="ro_date1" name="ro_date1" placeholder="Enter RO date">
						</label>
					
						<label>End Date
							<input type="text" id="ro_date2" name="ro_date2" placeholder="Enter RO date">
						</label>
					</fieldset>
		 		</div>
		 	
		 		<div class="large-6 columns">
		 			<h4> Select Year Model Range </h4>
		 			<fieldset>
		 				<label>Year Start <small class="form_ro_search_error" id="year_error1">*You are missing a form selection</small>
							<select id="year1" name="year1">
								<option value="">Select...</option>'.
								$welr->getYearModelOpts().'
							</select>
						</label>
						
						<label>Year End 
							<select id="year2" name="year2">
								<option value="">Select...</option>'.
								$welr->getYearModelOpts().'
							</select>
						</label>
					</fieldset>	
		 		</div>
		 	
		 		<div class="large-6 columns">
		 			<h4> Select Mileage Range </h4>
		 			<fieldset>
		 				<label>Mileage Start <small class="form_ro_search_error" id="mileage_error1">*You are missing a form selection</small>
							<select id="mileage1" name="mileage1">
								<option value="">Select...</option>'.
								$welr->getMileageOpts().'
							</select>
						</label>
						
						<label>Mileage End
							<select id="mileage2" name="mileage2">
								<option value="">Select...</option>'.
								$welr->getMileageOpts().'
							</select>
						</label>
					</fieldset>	
		 		</div>
		 
		 		<div class="large-6 columns">
					 <h4> Select Labor Range: </h4>
					 <fieldset>
						<label>Labor Start Rate <small class="form_ro_search_error" id="labor_error1">*You entered an incorrect labor amount!</small>
							<input type="text" id="labor1" name="labor1" placeholder="Enter labor dollars">
						</label>
					 
						<label>Labor End Rate
							<input type="text" id="labor2" name="labor2" placeholder="Enter labor dollars">
						</label>
					 </fieldset>	
		 		</div>
		 		
		 		<div class="large-6 columns">
					 <h4> Select Parts Range: </h4>
					 <fieldset>
						<label>Parts Start <small class="form_ro_search_error" id="parts_error1">*You entered an incorrect parts amount!</small>
							<input type="text" id="parts1" name="parts1" placeholder="Enter parts dollars">
						</label>
					 
						<label>Parts End
							<input type="text" id="parts2" name="parts2" placeholder="Enter parts dollars">
						</label>
					 </fieldset>	
		 		</div>
		 
		 		<div class="large-6 columns">
					<h4> Select Vehicle Make </h4>
					<fieldset>
						<label>Vehicle Make
							<select id="vehicle1" name="vehicle1">
								<option value="">Select...</option>'.
								$welr->getVehicleOpts().'
							</select>
						</label>
					</fieldset>	
		 		</div>';
		 
		 	// Get advisor data for advisor dropdown, and show advisor <select>, only if user is SOS, or (dealer + admin), or manuf user
		 	if($this->user_sos || ($this->user_dealer && $this->user_admin) || $this->user_manuf) {
			 	$advisors = new UserInfo($dbo=null);
			 	$adv_array = $advisors->getAdvisors($_SESSION['dealer_id']);
			 	$adv_id = $adv_array['user_id'];
			 	$adv_name = $adv_array['user_name'];
			 	$html .='
			 	<div class="large-6 columns">
			 	<h4> Select Advisor: </h4>
			 	<fieldset>
					<label>Service Advisor
						<select id="advisor1" name="advisor1">
							<option value="">Select...</option>';
							for($i=0; $i<count($adv_array['user_id']); $i++) {
								$html .='
								<option value="'.$adv_id[$i]['user_id'].','.$adv_name[$i]['user_name'].'">'.$adv_name[$i]['user_name'].'</option>';
							}
						$html .='
						</select>
					</label>
			 	</fieldset>	
			 	</div>';
		  	}

				$html .='
				<div class="large-12 columns">
					<h4> Select Services To Include: </h4>
				</div>'.
				// Generate services tables for sql inclusive svc search criteria
				$welr->getRoEntryForm($update_form = false, $update_ro_id = null, $search_params = true).'

				<div class="large-12 columns">
					<h4> Select Services To Exclude: </h4>
				</div>'.
				// Generate service options table for exclusive svc search criteria
				$welr->getSvcSearchTable().'
		
				<div class="large-12 columns">
					<input type="submit" id="ro_search_submit" name="ro_search_submit" class="small success button radius" />
				</div>
			</div> <!-- end div row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div> <!-- end div modal -->';
   
	   return $html;
   }
   
   /**
    * Build the Dealer Comparison html
    * @param {string} $title The main modal title
    * @return {string} $html The modal html
    */
   public function getMetricsDlrCompModal($title) {
		
	   	// Build html
	   	$html ='
	   	<div id="metrics_dlr_comp_modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">'.$title.'</h2>
		 	<form id="metrics_dlr_comp_form">
		 	<div class="row">
		 		<div class="large-12 columns">
		 			<h4> Select Date Range: </h4>
		 			<fieldset>
						<label>Start Date <small class="form_ro_search_error" id="metrics_dlr_comp_date_error">*Please enter a valid date (mm/dd/yyyy)</small>
							<input type="text" id="metrics_dlr_comp_date1" name="metrics_dlr_comp_date1" placeholder="Enter RO date">
						</label>
					
						<label>End Date
							<input type="text" id="metrics_dlr_comp_date2" name="metrics_dlr_comp_date2" placeholder="Enter RO date">
						</label>
					</fieldset>
		 		</div>';
		 
		 		// Get dealer data for dealer multiple select dropdown
		 		$obj = new DealerInfo($dbo=null);
		 		$array = $obj->getDealerInfo();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Dealer Group: </h4>
					 <fieldset>
						 <label>Select Dealers
							 <select id="metrics_dlr_comp_group" name="metrics_dlr_comp_group" class="mult_select" multiple>
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	
		 	// Only show the View All Dealers Checkbox option to SOS admin, & Manuf users
		 	if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
		 		// Provide a 'View All Dealers' option checkbox so that all dealers and all history may be selected by user; will allow form to proceed 		without displaying entry requirement error
		 		$html .='
		 		<div class="large-12 columns">
      				<input id="dlr_comp_checkbox" name="dlr_comp_checkbox" type="checkbox">
      				<label for="dlr_comp_checkbox">View All Dealers & All History</label>
      				<p></p>
		 		</div>';
		 	}	
		 		$html .='
		 		<div class="large-12 columns">
		 			<input type="submit" id="metrics_dlr_comp_submit" name="metrics_dlr_comp_submit" class="small success button radius" />
		 		</div>
			</div> <!-- end div row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div> <!-- end div modal -->';
   
	   return $html;
   }
   
   /**
    * Build the html for the Metrics Search modal
    * @param {string} $title The main modal title
    * @return {string} $html The modal html 
    */
   public function getMetricsSearchModal($title) {
		// Build html
		$html ='
	   	<div id="metrics_search_modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">'.$title.'</h2>
		 	<form id="metrics_search_form">
		 	<div class="row">
		 		<div class="large-12 columns">
		 			<h4> Select Date Range: </h4>
		 			<fieldset>
						<label>Start Date <small class="form_ro_search_error" id="metrics_date_error">*Please enter a valid date (mm/dd/yyyy)</small>
							<input type="text" id="metrics_date1" name="metrics_date1" placeholder="Enter RO date">
						</label>
					
						<label>End Date
							<input type="text" id="metrics_date2" name="metrics_date2" placeholder="Enter RO date">
						</label>
					</fieldset>
		 		</div>';
		 
			// Get advisor data for advisor dropdown, and build advisor selection options, only if user is SOS || (Dealer + Admin)  || Manuf
			if($this->user_sos || ($this->user_dealer && $this->user_admin) || $this->user_manuf) {
			 	$advisors = new UserInfo($dbo=null);
			 	$adv_array = $advisors->getAdvisors($_SESSION['dealer_id']);
			 	$adv_id = $adv_array['user_id'];
			 	$adv_name = $adv_array['user_name'];
			 	$html .='
			 	<div class="large-6 columns">
					 <h4> Select Advisor: </h4>
					 <fieldset>
						 <label>Service Advisor
							 <select id="metrics_advisor1" name="metrics_advisor1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($adv_array['user_id']); $i++) {
								 	$html .='
								 	<option value="'.$adv_id[$i]['user_id'].','.$adv_name[$i]['user_name'].'">'.$adv_name[$i]['user_name'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
			 	</div>';
			}
		
			// Only show global data if SOS admin, or Manuf user
			if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
		 		// Get region data for region dropdown
		 		$obj 	= new DealerInfo($dbo=null);
		 		$array = $obj->getDealerRegions();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Region: </h4>
					 <fieldset>
						 <label>Dealer Region
							 <select id="metrics_region1" name="metrics_region1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['regionID'].','.$array[$i]['region'].'">'.$array[$i]['region'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	
		 		// Get area data for area dropdown
		 		$array = $obj->getDealerAreas();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Area: </h4>
					 <fieldset>
						 <label>Dealer Area
							 <select id="metrics_area1" name="metrics_area1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['area_ID'].','.$array[$i]['area'].'">'.$array[$i]['area'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	
		 		// Get area data for district dropdown
		 		$array = $obj->getDealerDistricts();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select District: </h4>
					 <fieldset>
						 <label>Dealer District
							 <select id="metrics_district1" name="metrics_district1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['district_ID'].','.$array[$i]['district'].'">'.$array[$i]['district'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
			}

			// Only show multiple select dropdown to SOS & Manuf users
			if($this->user_sos || $this->user_manuf) {
			 	// Get dealer data for dealer multiple select dropdown
			 	$array = $obj->getDealerInfo();
			 	$html .='
			 	<div class="large-6 columns">
					 <h4> Select Dealer Group: </h4>
					 <fieldset>
						 <label>Select Dealers
							 <select id="metrics_dealer_group" name="metrics_dealer_group" class="mult_select" multiple>
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['		dealername'].'('.$array[$i]['dealercode'].')</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
			 	</div>';
			}

			// Only show 'View All Dealers' checkbox option to SOS admin & manuf users
			if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
				// Provide a 'View All Dealers' option check box so that all dealers may be selected by user; will bypass $array['dealer_id'] assignment if 		only date filters have been entered
				$html .='
				<div class="large-12 columns">
      				<input id="metrics_search_checkbox" name="metrics_search_checkbox" type="checkbox">
      				<label for="metrics_search_checkbox">View All Dealers</label>
      				<p></p>
				</div>';
			}
				$html .='	 
				<div class="large-12 columns">
				 	<input type="submit" id="metrics_search_submit" name="metrics_search_submit" class="small success button radius" />
				</div>
			</div> <!-- .row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div> <!-- .modal -->';
   
	   return $html;
   	}
   
   	/**
   	 * Build the Stats filter modal
   	 * @param {string} $title Modal main title
   	 * @return {string} $html The modal html
   	 */
   	public function getStatsSearchModal($title) {
		// Build html
		$html ='
	   	<div id="stats_search_modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">'.$title.'</h2>
		 	<form id="stats_search_form">
		 	<div class="row">
		 		<div class="large-12 columns">
		 			<h4> Select Date Range: </h4>
		 			<fieldset>
						<label>Start Date <small class="form_ro_search_error" id="stats_date_error">*Please enter a valid date (mm/dd/yyyy)</small>
							<input type="text" id="stats_date1" name="stats_date1" placeholder="Enter start date">
						</label>
					
						<label>End Date
							<input type="text" id="stats_date2" name="metrics_date2" placeholder="Enter end date">
						</label>
					</fieldset>
		 		</div>';

		 	// Show advisor dropdown if SOS user, Dealer admin user, or Manuf user
		 	if($this->user_sos || ($this->user_dealer && $this->user_admin) || $this->user_manuf) {
		 		// Get advisor data for advisor dropdown
		 		$advisors = new UserInfo($dbo=null);
		 		$adv_array = $advisors->getAdvisors($_SESSION['dealer_id']);
		 		$adv_id = $adv_array['user_id'];
		 		$adv_name = $adv_array['user_name'];
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Advisor: </h4>
					 <fieldset>
						 <label>Service Advisor
							 <select id="stats_advisor1" name="stats_advisor1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($adv_array['user_id']); $i++) {
								 	$html .='
								 	<option value="'.$adv_id[$i]['user_id'].','.$adv_name[$i]['user_name'].'">'.$adv_name[$i]['user_name'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	}

		 	// Show global data if SOS admin user, or Manuf user
		 	if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
		 		// Get region data for region dropdown
		 		$obj 	= new DealerInfo($dbo=null);
		 		$array = $obj->getDealerRegions();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Region: </h4>
					 <fieldset>
						 <label>Dealer Region
							 <select id="stats_region1" name="stats_region1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['regionID'].','.$array[$i]['region'].'">'.$array[$i]['region'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 		
		 		// Get area data for area dropdown
		 		$array = $obj->getDealerAreas();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Area: </h4>
					 <fieldset>
						 <label>Dealer Area
							 <select id="stats_area1" name="stats_area1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['area_ID'].','.$array[$i]['area'].'">'.$array[$i]['area'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 
		 		// Get area data for district dropdown
		 		$array = $obj->getDealerDistricts();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select District: </h4>
					 <fieldset>
						 <label>Dealer District
							 <select id="stats_district1" name="stats_district1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['district_ID'].','.$array[$i]['district'].'">'.$array[$i]['district'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	}

		 	// Show dealer multiple select if SOS user, or Manuf user
		 	if($this->user_sos || $this->user_manuf) {
		 		// Get dealer data for dealer multiple select dropdown
		 		$array = $obj->getDealerInfo();
		 		$html .='
		 		<div class="large-6 columns">
					<h4> Select Dealer Group: </h4>
					<fieldset>
						<label>Select Dealers
							<select id="stats_dealer_group" name="stats_dealer_group" class="mult_select" multiple>
								<option value="">Select...</option>';
								for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['		dealername'].'('.$array[$i]['dealercode'].')</option>';
								}
							$html .='
							</select>
						</label>
					</fieldset>	
		 		</div>';
		 	}

		 	// Show 'View All Dealers' checkbox if SOS admin or Manuf user
		 	if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
				// Provide a 'View All Dealers' option check box so that all dealers may be selected by user; will bypass $array['dealer_id'] assignment if only date filters have been entered
				$html .='
				<div class="large-12 columns">
      				<input id="stats_search_checkbox" name="stats_search_checkbox" type="checkbox">
      				<label for="stats_search_checkbox">View All Dealers</label>
      				<p></p>
				</div>';
		 	}
		 		$html .='
				<div class="large-12 columns">
					<input type="submit" id="stats_search_submit" name="stats_search_submit" class="small success button radius" />
				</div>
			</div> <!-- .row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div> <!-- .modal -->';
   
	   return $html;
   }
   
   /**
    * Build the html for the Metrics Trending modal
    * @param {string} $title The main modal title
    * @return {string} $html The modal html
    */
   public function getMetricsTrendModal($title) {
   		// Build html
		$html ='
	   	<div id="metrics_trend_modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">'.$title.'</h2>
		 	<form id="metrics_trend_form">
		 	<div class="row">
		 		<div class="large-12 columns">
		 			<h4> Select Date Range: </h4>
		 			<fieldset>
						<label>Start Date <small class="form_ro_search_error" id="metrics_trend_date_error">*Please enter a valid date (mm/dd/yyyy)</small>
							<input type="text" id="metrics_trend_date1" name="metrics_trend_date1" placeholder="Enter start date">
						</label>
					
						<label>End Date
							<input type="text" id="metrics_trend_date2" name="metrics_trend_date2" placeholder="Enter end date">
						</label>
					</fieldset>
		 		</div>';
		 
		 	// Get advisor data for advisor dropdown, and build select dropdown, only if user is sos, or dealer admin, or manuf
		 	if(($this->user_sos) || ($this->user_dealer && $this->user_admin) || $this->user_manuf) {
		 		$advisors = new UserInfo($dbo=null);
		 		$adv_array = $advisors->getAdvisors($_SESSION['dealer_id']);
		 		$adv_id = $adv_array['user_id'];
		 		$adv_name = $adv_array['user_name'];
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Advisor: </h4>
					 <fieldset>
						 <label>Service Advisor
							 <select id="metrics_trend_advisor1" name="metrics_trend_advisor1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($adv_array['user_id']); $i++) {
								 	$html .='
								 	<option value="'.$adv_id[$i]['user_id'].','.$adv_name[$i]['user_name'].'">'.$adv_name[$i]['user_name'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	}

		 	// Only show global data to SOS admin && Manuf users
		 	if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
		 		// Get region data for region dropdown
		 		$obj 	= new DealerInfo($dbo=null);
		 		$array = $obj->getDealerRegions();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Region: </h4>
					 <fieldset>
						 <label>Dealer Region
							 <select id="metrics_trend_region1" name="metrics_trend_region1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['regionID'].','.$array[$i]['region'].'">'.$array[$i]['region'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 		
		 		// Get area data for area dropdown
		 		$array = $obj->getDealerAreas();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Area: </h4>
					 <fieldset>
						 <label>Dealer Area
							 <select id="metrics_trend_area1" name="metrics_trend_area1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['area_ID'].','.$array[$i]['area'].'">'.$array[$i]['area'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 
		 		// Get area data for district dropdown
		 		$array = $obj->getDealerDistricts();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select District: </h4>
					 <fieldset>
						 <label>Dealer District
							 <select id="metrics_trend_district1" name="metrics_trend_district1">
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['district_ID'].','.$array[$i]['district'].'">'.$array[$i]['district'].'</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	}

		 	// Build the dealer multiple select dropdown, and View All Dealers checkbox only if user is SOS (all), or manuf
		 	if($this->user_sos || $this->user_manuf) {
		 		$array = $obj->getDealerInfo();
		 		$html .='
		 		<div class="large-6 columns">
					 <h4> Select Dealer Group: </h4>
					 <fieldset>
						 <label>Select Dealers
							 <select id="metrics_trend_group" name="metrics_trend_group" class="mult_select" multiple>
								 <option value="">Select...</option>';
								 for($i=0; $i<count($array); $i++) {
								 	$html .='
								 	<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';
								 }
							 $html .='
							 </select>
						 </label>
					 </fieldset>	
		 		</div>';
		 	}

		 	// Build the View All Dealers checkbox option, only if user is SOS admin, or Manuf
		 	if(($this->user_sos && $this->user_admin) || $this->user_manuf) {
		 		// Provide a 'View All Dealers' option check box so that all dealers may be selected by user; will bypass $array['dealer_id'] assignment if 		only date filters have been entered
		 		$html .='
		 		<div class="large-12 columns">
      				<input id="metrics_trend_checkbox" name="metrics_trend_checkbox" type="checkbox">
      				<label for="metrics_trend_checkbox">View All Dealers</label>
      				<p></p>
		 		</div>';
		 	}

		 		$html .='
				<div class="large-12 columns">
					<input type="submit" id="metrics_trend_submit" name="metrics_trend_submit" class="small success button radius" />
				</div>
			</div> <!-- .row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">&#215;</a>
		</div> <!-- .modal -->';
   
		return $html;
   }

   public function getUserProfileModal() {
   		// Set vars for easy access
   		$name 		= $_SESSION['user']['user_fname']. ' '.$_SESSION['user']['user_lname'];
   		$username 	= $_SESSION['user']['user_name'];
   		$email 		= $_SESSION['user']['user_email'];
   		$user_type 	= $_SESSION['user']['user_type_name'];
   		$user_team 	= $_SESSION['user']['user_team_name'];

   		// If user type is SOS or Manuf, there is no dealer affiliation.  Show 'N/A' if so.  
   		$dealer_info = ($user_type == 'SOS' || $user_type == 'Manuf') ? 'N/A' : $_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')';

   		// Set admin text display based on admin value
   		$admin = ($_SESSION['user']['user_admin'] == 1) ? 'Yes' : 'No';

   		// Build modal structure, filling in dynamic user data
   		$html =  
   		'<div id="user-profile-modal" class="reveal-modal" style="background: #f5f5f5;" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
		 	<h2 class="blue" id="modalTitle">User Profile</h2>
		 	<form>
		 		<div class="row">
		 			<div class="large-12 columns">
		 				<h4> General Information </h4>
		 				<fieldset>
		 					<p> Name: <span> '.$name.' </span> </p>
							<p> User Name: <span> '.$username.' </span> </p>
							<p> Email Address: <span> '.$email.' </span> </p>
						</fieldset>
		 			</div>
		 			<div class="large-12 columns">
						<h4> System Information </h4>
						<fieldset>
							<p> User Type: <span> '.$user_type.' </span> </p>
							<p> User Team: <span> '.$user_team.' </span> </p>
							<p> Dealer Affiliation: <span> '.$dealer_info.' </span> </p>
							<p> Admin Status: <span> '.$admin.' </span> </p>
						</fieldset>	
		 			</div>
				</div> <!-- end div row -->
			</form>
			<a class="close-reveal-modal" aria-label="Close">×</a>
		</div> <!-- .user-profile-modal -->';

		return $html;
   }	
}