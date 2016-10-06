<?php
/**
 * Program: class.DealerInfo.inc.php
 * Created: 02/26/2016 by Matt Holland
 * Purpose: Retrieve all necessary dealer information
 * Methods: getPageHeading(): Build page heading for dealer info displays
 *			getDealerInfo(): Generates dlr codes/names for dealers currently reporting in system (not all in db)
 *			getDealer(): Verify dealercode existence at login
 *			getDealerListing(): Get listing of all existing dealers in db (including those which are not reporting)
 *			getDealerListingTable(): Build table for listing dealer data
 *			getAddDealerTable(): Build table for adding new dealers to db
 *			getDealerRegions(): Build list of region info
 *			getDealerDistricts(): Build list of district info
 *			getDealerAreas(): Build list of area info
 */

Class DealerInfo extends PDO_Connect {	

	// Establish global vars for defining user access to specific components
    public $user_sos   ;
    public $user_manuf ;
    public $user_dlr   ;
    public $user_admin ;	
	
	public function __construct($dbo) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);

		// Initialize user types
		$this->user_sos   = ($_SESSION['user']['user_type_id'] == 1) ? true : false;
		$this->user_manuf = ($_SESSION['user']['user_type_id'] == 2) ? true : false;
		$this->user_dlr   = ($_SESSION['user']['user_type_id'] == 3) ? true : false;
		$this->user_admin = ($_SESSION['user']['user_admin']   == 1) ? true : false;		
	}

	public function getPageHeading($array) {
		$msg = $array['link_msg'];
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'];
           				// Page section subtitle
           				if($array['title_info']) {
           					$html .='
           					<span class="blue"> '.$array['title_info'].' </span>';
           				}

           				// Only show the Add New Dealer link to SOS admin users
           				if($this->user_sos && $this->user_admin) {
           					if($array['a_id']) {
           						$html .='
           						<a class="'.$array['a_id'].'" id="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$msg.' </a>';
           					}
           				}

           			$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Dealers" href="system/utils/export_dealers.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Dealer Table" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['dealer_count']) {
					  	$html .='
						&nbsp;Total Dealers: '.number_format($_SESSION['dealer_count']);
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
	
	// Get listing of dealers that are reporting in the online system (not comprehensive dealer list)
	public function getDealerInfo() {
		//unset($_SESSION['dealer_list']);
		if(!isset($_SESSION['dlr_list'])) {
			// Generate dealer codes for change dealer dropdown, and also get RO count
			$stmt = "SELECT a.dealerID, b.dealercode, b.dealername FROM repairorder_welr a
					 LEFT JOIN dealer b ON(a.dealerID = b.dealerID)
					 GROUP BY a.dealerID 
					 ORDER BY b.dealername ASC";
					
			// Prepare and execute statement
			if(!($stmt = $this->dbo->prepare($stmt))) {
				sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			}
			if(!($stmt->execute())) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			} else {
				return $result = $stmt->fetchAll();
			}
		} else {
			return $_SESSION['dlr_list'];
		}
	}
	
	// Verify dealercode existence at login. At this point, seems like dealer queries are becoming redundant ....
	public function getDealer($array) {
		$stmt = "SELECT dealerID, dealercode, dealername FROM dealer WHERE dealercode = ?";
			
		// Set dealercode param
		$params = array($array['dealercode']);
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($db->errorInfo(), __LINE__, __FILE__);
		}
		//echo 'stmt: '.var_dump($stmt).'<br>';
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$_SESSION['error'][] = 'Dealer search failed.  See administrator.';
			return;
		} else {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			//echo '$result: '.var_dump($result = $stmt->fetch(PDO::FETCH_ASSOC));
			if(!$result) {
				$_SESSION['error'][] = '*That dealer is not in the system.';
				return;
			} else {
				return $result;
			}
		}
	}
	
	// Get listing of all existing dealers in db (including those which are not reporting)
	public function getDealerListing() {
		//unset($_SESSION['dlr_list_all']);
		if(!isset($_SESSION['dlr_list_all'])) {
			// Generate comprehensive dealer listing for user reference
			$stmt = "SELECT a.dealerID, a.dealercode, a.dealername, a.dealeraddress, a.dealercity, b.state_name,
					a.dealerzip, a.dealerphone, c.region, d.district, e.area FROM dealer a
					LEFT JOIN us_state_list b ON(a.state_ID = b.state_ID)
					LEFT JOIN dealerregion c ON(a.regionID = c.regionID)
					LEFT JOIN dealer_district d ON(a.district_ID = d.district_ID)
					LEFT JOIN dealer_area e ON(a.area_ID = e.area_ID)
					ORDER BY a.dealername ASC";
			
			// Prepare and execute statement
			if(!($stmt = $this->dbo->prepare($stmt))) {
				sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			}
			if(!($stmt->execute())) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			} else {
				return $result = $stmt->fetchAll();
			}
			
			$_SESSION['dlr_list_all'] = $result;
			return $result;
		} else {
			return $_SESSION['dlr_list_all'];
		}
	}
	
	// Get dealer information if user chooses to edit an existing dealer
	public function getDealerEdit($array) {
		$stmt = "SELECT a.dealercode, a.dealername, a.dealeraddress, a.dealercity, a.state_ID,
				        b.state_name, a.dealerzip, a.dealerphone, a.district_ID, c.district,
				        a.area_ID, d.area, a.regionID, e.region  
				        FROM dealer a
				        LEFT JOIN us_state_list b ON(a.state_ID = b.state_ID)
				        LEFT JOIN dealer_district c ON(a.district_ID = c.district_ID)
				        LEFT JOIN dealer_area d ON(a.area_ID = d.area_ID)
				        LEFT JOIN dealerregion e ON(a.regionID = e.regionID)
				        WHERE dealerID = ?";
			
		// Set dealercode param
		$params = array($array['dealer_id']);
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($db->errorInfo(), __LINE__, __FILE__);
		}
		//echo 'stmt: '.var_dump($stmt).'<br>';
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!$result) {
				return false;
			} else {
				return $result;
			}
		}
	}
	
	public function getDealerListingTable() {
		// Get dealer data
		$data = $this->getDealerListing();
		
		// Save dealer count as SESSION var
		$_SESSION['dealer_count'] = count($data);
		
		// Build export title
		$export = MANUF." Dealer Listing ".date("m/d/Y")."\n";
		$export.= "Total Dealers: ".$_SESSION['dealer_count']."\n\n";
		
		// Build html table
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<div class="table-container">
						<table id="dealer_list_table_all" class="original metric dealer_table">
							<thead>
								<tr>';
								 // Only show the 'Action' field if user type is admin
								 if($_SESSION['user']['user_admin'] == 1) {
									 $html .='
									 <th class="first"><a> Action </a></th>';
								 }
								 	$html .='
									<th><a> Dealer Name	</a></th>
									<th><a> Code 	 	</a></th>
									<th><a> Address		</a></th>
									<th><a> City	 	</a></th>
									<th><a> State 		</a></th>
									<th><a> Zip	 		</a></th>
									<th><a> Phone		</a></th>
									<th><a> District	</a></th>
									<th><a> Area		</a></th>
									<th><a> Region		</a></th>
								</tr>
							</thead>
							<tbody>';
							$export .= "Dealer Name, Code, Address, City, State, Zip, Phone, District, Area, Region\n";

							// Build html table body and export data rows based on increments set above
							for($i=0; $i<count($data); $i++) {
								$html .='
								<tr>';
								 // Only provide the select form if user is admin type
								  if($this->user_sos && $this->user_admin) {
									$html .='
									<td class="first">
										<form class="table_form" method="POST" action="">
											<input type="hidden" value="'.$data[$i]['dealerID'].'" id="update_dealer_id" name="update_dealer_id" />
											<input type="submit" id="table_dealer_edit_select" name="table_dealer_edit_select" style="margin: 0px; padding: .2em .3em;" class="tiny button radius" value="Select" />
										</form>
									</td>';
								 	}
								 	$html .='
									<td>'.$data[$i]['dealername'].'</td>
									<td>'.$data[$i]['dealercode'].'</td>
									<td>'.$data[$i]['dealeraddress'].'</td>
									<td>'.$data[$i]['dealercity'].'</td>
									<td>'.$data[$i]['state_name'].'</td>
									<td>'.$data[$i]['dealerzip'].'</td>
									<td>'.$data[$i]['dealerphone'].'</td>
									<td>'.$data[$i]['region'].'</td>
									<td>'.$data[$i]['district'].'</td>
									<td>'.$data[$i]['area'].'</td>
								</tr>';

								// Builde the dealer listing export data
								$export .= $data[$i]['dealername'].",".$data[$i]['dealercode'].",".$data[$i]['dealeraddress'].",".$data[$i]['dealercity'].",".$data[$i]['state_name'].",".$data[$i]['dealerzip'].",".$data[$i]['dealerphone'].",".$data[$i]['region'].",".$data[$i]['district'].",".$data[$i]['area']."\n";	  				  
							}

							$html .='
							</tbody>
						</table>
						</div> <!-- end div table-container -->
					</div><!-- end div large-12 columns -->
				</div><!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		// Save export as SESSION var
		$_SESSION['export_dealer_data'] = $export;
		
		return $html;
	}
	
	public function getAddDealerTable($array) {
		// $array may contain: 'add_dealer_row'
		$add_dealer_row = (isset($array['add_dealer_row'])) ? $array['add_dealer_row'] : false;
		$edit_dealer_val= (isset($array['edit_dealer_val'])) ? $array['edit_dealer_val'] : false;
		$edit_dealer_id = (isset($array['dealer_id'])) ? $array['dealer_id'] : null;
		$submit_colspan = (!$edit_dealer_val) ? 2 : 1;
		
		// Set all possible input values based on whether or not a dealer edit has been requested
		if($edit_dealer_val) {
			$lookup = $this->getDealerEdit(array('dealer_id'=>$edit_dealer_id));
			// There will only be one row for this query result, as FETCH_ASSOC was used in query method
			$dlr_name 		= $lookup['dealername'];
			$dlr_code 		= $lookup['dealercode'];
			$dlr_address 	= $lookup['dealeraddress'];
			$dlr_city 		= $lookup['dealercity'];
			$dlr_state_id 	= $lookup['state_ID'];
			$dlr_state_name = $lookup['state_name'];
			$dlr_zip 		= $lookup['dealerzip'];
			$dlr_phone 		= $lookup['dealerphone'];
			$dlr_dist_id 	= $lookup['district_ID'];
			$dlr_dist_name 	= $lookup['district'];
			$dlr_area_id 	= $lookup['area_ID'];
			$dlr_area_name 	= $lookup['area'];
			$dlr_region_id 	= $lookup['regionID'];
			$dlr_region_name= $lookup['region'];
			
			// Set submit value and id
			$submit_value = 'Update Dealer';
			$submit_id    = 'edit_dealer_submit';
			
			// Set table title
			$table_title = 'Edit Dealer';
			
			// Set border_left style to none on 'Dealer Name' field
			$border = null;
			
		} else {
			$dlr_name 		= false;
			$dlr_code 		= false;
			$dlr_address 	= false;
			$dlr_city 		= false;
			$dlr_state_id 	= false;
			$dlr_state_name = false;
			$dlr_zip 		= false;
			$dlr_phone 		= false;
			$dlr_dist_id 	= false;
			$dlr_dist_name 	= false;
			$dlr_area_id 	= false;
			$dlr_area_name 	= false;
			$dlr_region_id 	= false;
			$dlr_region_name= false;
			
			// Set submit value and id
			$submit_value = 'Add Dealers';
			$submit_id    = 'add_dealer_submit';
			
			// Set table title
			$table_title = 'Add New Dealers';
			
			// Set border-left style to null if no edit
			$border = 'border-left: none;';
		}
		
		$html1 ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> '.$table_title.' <a class="'.$array['a_id'].'" id="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$array['link_msg'].' </a> </h4>
					</div>
					<div class="large-12 columns">
						<form name="dealer_form" id="dealer_form">
							<table id="add_new_dealer" class="original responsive metric">
								<thead>
									<tr>';
									 if(!$edit_dealer_val) {
									 	$html1 .='
										<th style="width: 32px;"></th> <!-- provide space for the remove row sign -->';
									 }
									 	$html1 .='
										<th style="'.$border.'"><a> Dealer Name	</a></th>
										<th><a> Code 	 	</a></th>
										<th><a> Address	 	</a></th>
										<th><a> City	 	</a></th>
										<th><a> State 		</a></th>
										<th><a> Zip	 		</a></th>
										<th><a> Phone		</a></th>
										<th><a> District	</a></th>
										<th><a> Area		</a></th>
										<th><a> Region		</a></th>
									</tr>
								</thead>
								<tbody>';
							$html2 ='<tr>';

								// If user is editing a dealer, do not show the delete row icon.
								// Also include hidden input in form for passing of 'dealer_id' to UPDATE stmt.
								//Also include hidden input for dealer code for comparing to submitted code to ensure code doesn't already exist
								if(!$edit_dealer_val) {
								 	$html2 .='
										<td style="width: 32px;"> <a class="fontello-cancel-circled-outline"></a> </td> <!-- the remove row placeholder -->';
								}

						 		// Also include hidden input for 'edit_dealer_val' to ensure passing of value 
								$html2 .='
										<input type="hidden" name="edit_dealer_id" id="edit_dealer_id" value="'.$edit_dealer_id.'" />
						 				<input type="hidden" name="edit_dealer_code" id="edit_dealer_code" value="'.$dlr_code.'" />
										<input type="hidden" name="edit_dealer_val" id="edit_dealer_val" value="'.$edit_dealer_val.'" />
										<td><input type="text" name="dlr_name"    id="dlr_name"    value="'.$dlr_name.'" /></td>
										<td><input type="text" name="dlr_code"    id="dlr_code"    value="'.$dlr_code.'" /></td>
										<td><input type="text" name="dlr_address" id="dlr_address" value="'.$dlr_address.'" /></td>
										<td><input type="text" name="dlr_city"    id="dlr_city"    value="'.$dlr_city.'" /></td>
										<td>
											<select name="dlr_state" id="dlr_state">';
											// Get list of all possible states
											$states = $this->getStateData();
											if($dlr_state_id) {
												$html2 .='
												<option value="'.$dlr_state_id.'">'.$dlr_state_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
											for($i=0; $i<count($states); $i++) {
												$html2 .='
												<option value="'.$states[$i]['state_ID'].'">'.$states[$i]['state_name'].'</option>';
											}
											$html2 .='
											</select>
										</td>
										<td><input type="text" name="dlr_zip" id="dlr_zip" value="'.$dlr_zip.'" /></td>
										<td><input type="text" name="dlr_phone" id="dlr_phone" value="'.$dlr_phone.'" /></td>
										<td>
											<select name="dlr_district" id="dlr_district">';
											// Get list of all possible states
											$dist = $this->getDealerDistricts();
											if($dlr_dist_id) {
												$html2 .='
												<option value="'.$dlr_dist_id.'">'.$dlr_dist_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
											for($i=0; $i<count($dist); $i++) {
												$html2 .='
												<option value="'.$dist[$i]['district_ID'].'">'.$dist[$i]['district'].'</option>';
											}
											$html2 .='
											</select>
										</td>
										<td>
											<select name="dlr_area" id="dlr_area">';
											// Get list of all possible states
											$area = $this->getDealerAreas();
											if($dlr_area_id) {
												$html2 .='
												<option value="'.$dlr_area_id.'">'.$dlr_area_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
											for($i=0; $i<count($area); $i++) {
												$html2 .='
												<option value="'.$area[$i]['area_ID'].'">'.$area[$i]['area'].'</option>';
											}
											$html2 .='
											</select>
										</td>
										<td>
											<select name="dlr_region" id="dlr_region">';
											// Get list of all possible states
											$rgn = $this->getDealerRegions();
											if($dlr_region_id) {
												$html2 .='
												<option value="'.$dlr_region_id.'">'.$dlr_region_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
											for($i=0; $i<count($rgn); $i++) {
												$html2 .='
												<option value="'.$rgn[$i]['regionID'].'">'.$rgn[$i]['region'].'</option>';
											}
											$html2 .='
											</select>
										</td>
									</tr>';

								$html3 ='
								</tbody>
								<tfoot>
									<tr>
										<td colspan="'.$submit_colspan.'" style="height: 52px;"><input type="submit" class="tiny button radius" id="'.$submit_id.'" value="'.$submit_value.'" style="margin-bottom: 0;"/></td>
										<td colspan="9" style="height: 52px;"></td>	
									</tr>
								</tfoot>	
							</table>
						</form><!-- end form dealer_form -->
					</div><!-- end div large-12 columns -->
				</div><!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		if (!$add_dealer_row) {
			return $html1.$html2.$html3;
		} else {
			return $html2;
		}
	}
	
	// Process add/edit dealers from 'add_dealers' form. Input received from AJAX $_POST values
	public function processAddNewDealers($array) {
		// Run json_decode for the following JSON-encoded array $_POST objects.  Will result in php arrays
		$post_dlr_name 		 = json_decode($_POST['dlr_name'],    	 true);
		$post_dlr_code 		 = json_decode($_POST['dlr_code'],    	 true);
		$post_dlr_address 	 = json_decode($_POST['dlr_address'], 	 true);
		$post_dlr_city 		 = json_decode($_POST['dlr_city'],   	 true);
		$post_dlr_state_id 	 = json_decode($_POST['dlr_state_id'],   true);
		$post_dlr_zip 		 = json_decode($_POST['dlr_zip'],	  	 true);
		$post_dlr_phone 	 = json_decode($_POST['dlr_phone'],   	 true);
		$post_dlr_dist_id 	 = json_decode($_POST['dlr_dist_id'],    true);
		$post_dlr_area_id 	 = json_decode($_POST['dlr_area_id'],    true);
		$post_dlr_region_id  = json_decode($_POST['dlr_region_id'],  true);
		
		// Set edit_dealer_id value to provide id for sql UPDATE statement if $array['edit_dealer'] == true
		// Also store $edit_dealer_code value to compare original code with edited code (in case user edits the code).
		// If the code has been edited, need to perform code duplicate check to ensure code does not already exist.
		if(isset($array['edit_dealer_val'])) {
			$edit_dealer_id    = $_POST['edit_dealer_id'];
			$edit_dealer_code  = $_POST['edit_dealer_code'];
			$edit_dealer_val   = $array['edit_dealer_val']; // boolean
		}

		/* Run through each POST array and save to param variable to be used with insertDealer() or updateDealer() method */
		$dlr_name = array();
		$params = array();
		$i=0;
		foreach($post_dlr_name as $name) {
			$dlr_name[$i] = $name;
			$params[$i]['name'] = $name;
			$i += 1;
		}
		
		$dlr_code = array();
		$i=0;
		foreach($post_dlr_code as $code) {
			$dlr_code[$i] = $code;
			$params[$i]['code'] = $code;
			$i += 1;
		}
		
		$dlr_address = array();
		$i=0;
		foreach($post_dlr_address as $add) {
			$dlr_address[$i] = $add;
			$params[$i]['address'] = $add;
			$i += 1;
		}
		
		$dlr_city = array();
		$i=0;
		foreach($post_dlr_city as $city) {
			$dlr_city[$i] = $city;
			$params[$i]['city'] = $city;
			$i += 1;
		}
		
		$dlr_state = array();
		$i=0;
		foreach($post_dlr_state_id as $state) {
			$dlr_state[$i] = $state;
			$params[$i]['state'] = $state;
			$i += 1;
		}
		
		$dlr_zip = array();
		$i=0;
		foreach($post_dlr_zip as $zip) {
			$dlr_zip[$i] = $zip;
			$params[$i]['zip'] = $zip;
			$i += 1;
		}
		
		$dlr_phone = array();
		$i=0;
		foreach($post_dlr_phone as $phone) {
			$dlr_phone[$i] = $phone;
			$params[$i]['phone'] = $phone;
			$i += 1;
		}
		
		$dlr_dist = array();
		$i=0;
		foreach($post_dlr_dist_id as $dist) {
			$dlr_dist[$i] = $dist;
			$params[$i]['district_id'] = $dist;
			$i += 1;
		}
		
		$dlr_region = array();
		$i=0;
		foreach($post_dlr_region_id as $region) {
			$dlr_region[$i] = $region;
			$params[$i]['region_id'] = $region;
			$i += 1;
		}
		
		$dlr_area = array();
		$i=0;
		foreach($post_dlr_area_id as $area) {
			$dlr_area[$i] = $area;
			$params[$i]['area_id'] = $area;
			// Add create_date param field for tracking of new dealer INSERTs
			$params[$i]['create_date'] = date("Y-m-d H:i:s");
			$i += 1;
		}
		
		// If edit_dealer_val == true and dlr_code[0] val has changed, OR edit_dealer_val == false, run checkDealerDupe() method. 
		// Before inserting into db, you must make sure that dealercode does not already exist.  If it does, return error msg.
		if (($edit_dealer_val && ($dlr_code[0] != $edit_dealer_code)) || !$edit_dealer_val) {
			// Note: $dlr_code is an array of dealer codes
			if($error_stmt = $this->checkDealerDupe(array('dlr_codes'=>$dlr_code))) {
				//echo 'error_stmt: '.$error_stmt;
				return $error_stmt;
			}
		}
		
		// Insert dealer(s) into the dealer table if $edit_dealer_val == false.  Else run update statement.
		if(!$edit_dealer_val) {
			if($this->insertDealer(array('dealers'=>$params))) {
				return true;		
			} else {
				return false;
			}
		} else {
			if($this->updateDealer(array('params'=>$params, 'edit_dealer_id'=>$edit_dealer_id))) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	// Check dealer code to make sure there are no duplicates in the db
	public function checkDealerDupe($array) {
		// Establish easy access to $dealer_code array element.  Note: 'dlr_codes' is an array
		$dlr_code = $array['dlr_codes'];
		
		// Establish $dlr_dupe array for holding db dlr_code results (will drive error msg if dealercode(s) already exist)
		$dlr_dupe = array();
		
		// Check for dealercode duplicates.  If db returns result, $result will contain an array of user info.  Else will be false (boolean)
		for($i=0; $i<count($dlr_code); $i++) {
			$result = $this->getDealer(array('dealercode'=>$dlr_code[$i]));
			if(count($result) > 0) {
				//echo 'dupe count is > 0';
				$dlr_dupe[] = $dlr_code[$i];
			}	
		}
		
		// Return dealercode dupe error statement if there were any duplicates. User should have already been alerted by JS.
		// If dupe is found, substring(0,10) is used in returndata else if statement for 'error_dupe'
		$error_stmt = "";
		if(count($dlr_dupe) > 0) {
			$error_stmt .= "error_dupe*The following errors have occurred: \n\n";
			foreach ($dlr_dupe as $code) {
				$error_stmt .= "Dealer code ".$code." already exists in the system. \n";
			}
			$error_stmt .= "\n Please correct the errors and try again.";
			//echo $error_stmt;
			// ORIG: return true;
			return $error_stmt;
		} else {
			//echo 'dupe count was not > 0';
			return false;
		} 
	}
	
	public function insertDealer($array) {
		$stmt = "INSERT INTO dealer (dealercode, dealername, dealeraddress, dealercity, state_ID, dealerzip, dealerphone,
		  				             district_ID, area_ID, regionID, create_date)
		  		 VALUES (?,?,?,?,?,?,?,?,?,?,?)";
		
		// Establish params array from $array parameter. Note that $array['params'] is a double-array
		$dealers = $array['dealers'];
		  					 
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		// Note that $params[$i] is a double-array. Build single array from received param by removing array name keys.
		$params = array();
		$d = 0;
		foreach($dealers as $dealer) {
			for($i=0; $i<count($dealers[0]); $i++) {
				$params[$d][0] = $dealer['code']	    ;
				$params[$d][1] = $dealer['name']	    ;
				$params[$d][2] = $dealer['address']    	;
				$params[$d][3] = $dealer['city']	   	;
				$params[$d][4] = $dealer['state']	    ;
				$params[$d][5] = $dealer['zip']			;
				$params[$d][6] = $dealer['phone']	    ;
				$params[$d][7] = $dealer['district_id']	;
				$params[$d][8] = $dealer['area_id']  	;
				$params[$d][9] = $dealer['region_id']	;
				$params[$d][10]= $dealer['create_date'] ;
			}

			// Increment $d for double-array index
			$d += 1;
		}
		
		for($i=0; $i<count($dealers); $i++) {
			if(!($stmt->execute($params[$i]))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				$error = true;
			} else {
				$error = false;
			} 
		}
		// Return true or false based on $error value
		return (!$error) ? true : false;
	}
	
	public function updateDealer($array) {
		$stmt = "UPDATE dealer SET dealercode = ?, dealername = ?, dealeraddress = ?, dealercity = ?, state_ID = ?,
				                   dealerzip = ?, dealerphone = ?, district_ID = ?, area_ID = ?, regionID = ?
				 WHERE dealerID = ?";
				        
		// Set $params for execute() statement. First, remove last param element (create_date).
		// Next, add dealerID to last $params array element for target table row for WHERE clause
		$params   = array($array['params']);
		$params   = array_pop($params);
		$params[] = $array['edit_dealer_id'];
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	}
	
	// Generate array of all state data from db
	public function getStateData() {
		$stmt = "SELECT state_ID, state_name FROM us_state_list";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = $stmt->fetchAll();
		}
		
	}
	
	public function getDealerRegions() {
	
		// Generate dealer region array for generation of region option dropdowns
		$stmt = "SELECT regionID, region FROM dealerregion";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = $stmt->fetchAll();
		}
	}
	
	public function getDealerDistricts() {
	
		// Generate dealer district array for generation of district option dropdowns
		$stmt = "SELECT district_ID, district FROM dealer_district";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = $stmt->fetchAll();
		}
	}
	
	public function getDealerAreas() {
	
		// Generate dealer area array for generation of area option dropdowns
		$stmt = "SELECT area_ID, area FROM dealer_area";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = $stmt->fetchAll();
		}
	}
}
?>