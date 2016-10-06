<?php
/**
 * Program: class.Stats.inc.php
 * Created: 04/13/2016 by Matt Holland
 * Purpose: Display stats data etc
 * Methods: getServiceLevelStats(): Create data arrays for each service level (1,2,3)
 			getServiceLevelTable(): Build service level stats table
 			getLofStats(): Create data array of LOF stats
 			getLofTable(): Build LOF stats table
 			getVehicleStats(): Create data array of vehicle stats
 			getVehicleTable(): Build vehicle stats table
 			getYearModelStats(): Create data array of year model stats
 			getYearModelTable(): Build year model stats table
 			getMileageStats(): Create data array of mileage stats
 			getMileageTable(): Build mileage stats table
 			getRoStartEndDates(): Get first and last RO date
 			getRoEntryStats(): Create data array of RO entry stats (uses above method)
 			getRoEntryStatsTable(): Build RO entry stats table
 			getPageHeading(): Generate page heading and title for stats interfaces
 			getRoCount(): Get RO count (used for many methods in this class)
 			getRoTrendData(): Create data array of RO trend data by month (ROs entered per month)
 			getRoTrendTable(): Build table for RO trend display
 * Updates:
 */

Class Stats extends PDO_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}
	
	public function getServiceLevelStats($array) {
		
		// Get service info from ServicesInfo class
		$obj = new ServicesInfo($dbo=null);
		$result = $obj->getServiceInfo($sort_welr=false, $sort_metrics=true);
		$result = $result['svc_info'];
		
		// Save each serviceID inside of its perspective array
		$l1 = array();
		$l2 = array();
		$l3 = array();
		for($i=0; $i<count($result); $i++) {
			if ($result[$i]['servicelevel'] == 1) {
				$l1[] = $result[$i]['serviceID'];
			}
			if ($result[$i]['servicelevel'] == 2) {
				$l2[] = $result[$i]['serviceID'];
			}
			if ($result[$i]['servicelevel'] == 3) {
				$l3[] = $result[$i]['serviceID'];
			}
		}
		
		// Now put each array level inside of a holding array as an associative element for running the execute loop
		$svc_ids['l1_ids'] = $l1;
		$svc_ids['l2_ids'] = $l2;
		$svc_ids['l3_ids'] = $l3;
		
		// Establish arrays for query and param creation
		$stmt = array();
		$params = array();
		
		// Begin query statement
		$stmt[] = "SELECT COUNT(DISTINCT(a.ronumber)) FROM servicerendered_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
				   
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
				   
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		$query .= " AND a.decsvc = ? ";
		$params[] = 0;
		
		// Save $query as first $sql array element, then add remaining IN statement by looping through each $svc_ids level
		$result = array();
		$sql = array($query);
		foreach($svc_ids as $key=>$ids) {
			// Initialize $query statement
			$query = "";
			
			// Prepare last query statement by making ? for IN operator dynamic, then concatenate to $query string
			$sql[] = " AND a.serviceID IN(".rtrim(str_repeat('?,', count($svc_ids[$key])), ',').") ";
			foreach($sql as $item) {
				$query .= $item;
			}
			
			// Prepare the query statement.  Must do inside of the loop as the IN(?) will change based on serviceIDs in each service level
			if(!($stmt = $this->dbo->prepare($query))) {
				sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			}
			
			// Add the remaining params for the IN operator based off of each array size
			foreach($svc_ids[$key] as $id) {
				$params[] = $id;
			}
			
			// Now that the dynamic params have been bound for the IN operator, execute the $stmt with the params
			if(!$stmt->execute($params)) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			} else {
				// Save each result as an associative array based on $key ('l1_ids', 'l2_ids', 'l3_ids')
				$result[$key] = $stmt->fetchColumn();
			}
			
			// Now pop off the IN operator params to prepare for next loop iteration
			for($i=0; $i<(count($svc_ids[$key])); $i++) {
				array_pop($params);
			}
			
			// Now pop off the last query $stmt to make room for next IN clause
			array_pop($sql);
		}
		return $result;
	}
	
	public function getServiceLevelTable($array) {
		// Get RO count
		$ro_count = $this->getRoCount($array);
		
		// Get Vehicle data
		$data = $this->getServiceLevelStats($array);
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';
		
		// Build stats export data.  Starts here.
		$export = MANUF." Statistics Export \n\n";
		$export .= ($array['dealer_id']) ? "Dealer ".$_SESSION['dealer_code']."\n\n" : "Filtered Results \n";
		$export .= "Total ROs:, ".$ro_count."\n";
		$export .= ($array['search_feedback']) ? $array['search_feedback']."\n" : null;
		
		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Service Breakdown </h4>
					</div>
				</div>';
		$export .= "\n Service Breakdown \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
				//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "Category, Light Maintenance, Medium Maintenance, Repair Service \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="svc_table" class="original responsive stat">
							<thead>
								<tr>
									<th><a>	 Category			</a></th>
									<th><a>	 Light Maintenance	</a></th>
									<th><a>	 Medium Maintenance	</a></th>
									<th><a>  Repair Service		</a></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Total ROs</td>';
									$export .= "Total ROs,";
									$perc = array();
									foreach ($data as $value) {
										$html .='
										<td>'.number_format($value).'</td>';
										$export .= $value.",";
										// Save percentage
										$perc[] = ($ro_count>0) ? ($value/$ro_count) : 0;
									}
								$export .= "\n";
								$html .='
								</tr>
									<td>Percentage</td>';
									$export .= "Percentage,";
									foreach ($perc as $value) {
										$html .='
										<td>'.number_format($value*100,2).'%</td>';
										$export .= ($value*100)."%,";
									}
								$html .='
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->'; 
		$export .="\n\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = $export;
		
		return $html;
	}
	
	public function getLofStats($array) {
		$stmt = array();
		$params = array();
		
		// Get RO Count for results
		$ro_count = $this->getRoCount($array);
		// return $ro_count;
		
		// Query servicerendered_welr for all ROs with LOF stats
		$stmt[] = "SELECT COUNT(a.ronumber) FROM servicerendered_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
				   
		$stmt[] = "serviceID = ? ";
		$params[] = 1;
		
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		//return 'query: '.$query.'<br>';
		//return 'params: '.var_dump($params).'<br>';
				   
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$lof_ros = (int)$stmt->fetchColumn();
			$lof_ros_perc = ($ro_count > 0) ? $lof_ros/$ro_count : 0;
			//return $lof_rows;
			//echo '$stmt: '.var_dump($stmt).'<br>';
			//echo '$result: '.$result.'<br>';
		}
		
		// Now extend original query to get ROs with only LOF (including those which have LOF + only dec services)                                    1                1
		$query .= " AND (SELECT COUNT(*) FROM servicerendered_welr b WHERE b.serviceID > ? AND b.decsvc = ? AND b.ronumber = a.ronumber) = (SELECT COUNT(*) FROM servicerendered_welr c WHERE c.serviceID > ? AND c.ronumber = a.ronumber) ";
		$params[] = 1;
		$params[] = 1;
		$params[] = 1;
		
		//return 'query: '.$query.'<br>';
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$lof_only_ros = (int)$stmt->fetchColumn();
			$lof_only_perc = ($ro_count > 0) ? $lof_only_ros/$ro_count : 0;
			//return $lof_only;
			//echo '$stmt: '.var_dump($stmt).'<br>';
			//echo '$result: '.$result.'<br>';
		}
		return array('lof_ros'=>$lof_ros, 'lof_ros_perc'=>$lof_ros_perc, 
					 'lof_only_ros'=>$lof_only_ros, 'lof_only_perc'=>$lof_only_perc
					);
	}
	
	public function getLofTable($array) {
	
		// Retrieve LOF data
		$data = $this->getLofStats($array);
		
		// Establish variables for easy access
		$lof_ros 	  = $data['lof_ros'];
		$lof_ros_perc = $data['lof_ros_perc'];
		$lof_only_ros = $data['lof_only_ros'];
		$lof_only_perc= $data['lof_only_perc'];
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';

		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> LOF Demand Breakdown </h4>
					</div>
				</div>';
		$export = "LOF Demand Breakdown \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
				//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "Category, Total ROs, Percentage \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="lof_table" class="original responsive stat">
							<thead>
								<tr>
									<th><a> Category   </a></th>
									<th><a> Total ROs  </a></th>
									<th><a> Percentage </a></th>
								</tr>
							</thead>
							<tbody>
									<tr>
										<td> ROs With LOF	 </td>
										<td>'.number_format($lof_ros).'</td>
										<td>'.number_format(($lof_ros_perc)*100,2).'%</td>
									</tr>
									<tr>
										<td> ROs With Only LOF	 </td>
										<td>'.number_format($lof_only_ros).'</td>
										<td>'.number_format(($lof_only_perc)*100,2).'%</td>
									</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		$export .= "ROs With LOF,".$lof_ros.",".($lof_ros_perc*100)."% \n";
		$export .= "ROs With Only LOF,".$lof_only_ros.",".($lof_only_perc*100)."% \n\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
		
		return $html;
	}
	
	public function getVehicleStats($array) {
		
		// Establish arrays
		$stmt = array();
		$params = array();
		
		// Get RO Count for results
		$ro_count = $this->getRoCount($array);
		// return $ro_count;
		
		$stmt[] = "SELECT c.vehicle_make, COUNT(a.vehicle_make_id)
				 FROM repairorder_welr a
				 LEFT JOIN dealer b ON(a.dealerID = b.dealerID)
				 LEFT JOIN vehicle_make c ON(a.vehicle_make_id = c.vehicle_make_id) ";
				 
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
				 
		$query .= " GROUP BY a.vehicle_make_id ";
		
		//return 'query: '.$query.'<br>';
		//return 'params: '.var_dump($params).'<br>';
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		//return 'stmt: '.print_r($stmt);

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$result = $stmt->fetchAll();
			//return '$result: '.var_dump($result).'<br>';
		}
		return $result;
	}
	
	public function getVehicleTable($array) {
		
		// Get RO count
		$ro_count = $this->getRoCount($array);
		
		// Get Vehicle data
		$data = $this->getVehicleStats($array);
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';
		
		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Vehicle Make Breakdown </h4>
					</div>
				</div>';
		$export .= "Vehicle Make Breakdown \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
			//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "Vehicle Make, Total ROs, Percentage \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="vehicle_table" class="original responsive stat">
							<thead>
								<tr>
									<th><a>	 Vehicle Make 	</a></th>
									<th><a>	 Total ROs		</a></th>
									<th><a>  Percentage		</a></th>
								</tr>
							</thead>
							<tbody>';
								for ($i=0; $i<count($data); $i++) {
									$perc = ($ro_count>0) ? ($data[$i]['COUNT(a.vehicle_make_id)']/$ro_count) : 0;
									$html .='
									<tr>
										<td>'.$data[$i]['vehicle_make'].'</td>
										<td>'.number_format($data[$i]['COUNT(a.vehicle_make_id)']).'</td>
										<td>'.number_format($perc*100,2).'%</td>
									</tr>';
									$export .= $data[$i]['vehicle_make'].",";
									$export .= $data[$i]['COUNT(a.vehicle_make_id)'].",";
									$export .= ($perc*100)."% \n";
								}
							$html .='
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		$export .="\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
		
		return $html;
	}
	
	public function getYearModelStats($array) {
	
		// Establish arrays
		$stmt = array();
		$params = array();
		
		// Begin query statement
		$stmt[] = "SELECT c.modelyear, COUNT(a.ronumber)
				   FROM repairorder_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID)
				   LEFT JOIN yearmodel c ON (a.yearmodelID = c.yearmodelID) ";
				   
		$stmt[] = "c.yearmodelID BETWEEN ? AND ? ";
		$params[] = 0;
		$params[] = (int)(date("m")>6) ? date("y")+1 : date("y");
				  
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
				 
		$query .= " GROUP BY c.yearmodelID ORDER BY c.yearmodelID DESC ";
		//return 'query: '.$query;
		
		//return 'params: '.var_dump($params).'<br>';
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		//return 'stmt: '.print_r($stmt);

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$result = $stmt->fetchAll();
			//return '$result: '.var_dump($result).'<br>';
		}
		return $result;
		
	}
	
	public function getYearModelTable($array) {
		// Get RO count
		$ro_count = $this->getRoCount($array);
		
		// Get Vehicle data
		$data = $this->getYearModelStats($array);
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';
		
		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Year Model Breakdown </h4>
					</div>
				</div>';
		$export = "Year Model Breakdown \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
			//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "Model Year, Total ROs, Percentage \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="my_table" class="original responsive stat">
							<thead>
								<tr>
									<th><a>	 Model Year 	</a></th>
									<th><a>	 Total ROs		</a></th>
									<th><a>  Percentage		</a></th>
								</tr>
							</thead>
							<tbody>';
								for ($i=0; $i<count($data); $i++) {
									$modelyear = ($i == count($data)-1) ? $data[$i]['modelyear'].' & Older' : $data[$i]['modelyear'];
									$perc = ($ro_count>0) ? ($data[$i]['COUNT(a.ronumber)']/$ro_count) : 0;
									
									$html .='
									<tr>
										<td>'.$modelyear.'</td>
										<td>'.number_format($data[$i]['COUNT(a.ronumber)']).'</td>
										<td>'.number_format($perc*100,2).'%</td>
									</tr>';
									$export .= $modelyear.",";
									$export .= $data[$i]['COUNT(a.ronumber)'].",";
									$export .= ($perc*100)."% \n";
								}
							$html .='
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->'; 
		$export .= "\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
		
		return $html;
	}
	
	public function getMileageStats($array) {
	
		// Establish arrays
		$stmt = array();
		$params = array();
		
		// Begin query statement
		$stmt[] = "SELECT c.carmileage, COUNT(a.ronumber)
				   FROM repairorder_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID)
				   LEFT JOIN mileagespread c ON(a.mileagespreadID = c.mileagespreadID) ";
				  
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		$query .= " GROUP BY c.carmileage ORDER BY c.mileagespreadID";
		//return 'query: '.$query;
		//return 'params: '.var_dump($params).'<br>';
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		//return 'stmt: '.print_r($stmt);

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$result = $stmt->fetchAll();
			//return '$result: '.var_dump($result).'<br>';
		}
		return $result;
	}
	
	public function getMileageTable($array) {
	
		// Get RO count
		$ro_count = $this->getRoCount($array);
		
		// Get Vehicle data
		$data = $this->getMileageStats($array);
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';
		
		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Mileage Breakdown </h4>
					</div>
				</div>';
		$export = "Mileage Breakdown \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
			//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "Mileage Spread, Total ROs, Percentage \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="ms_table" class="original responsive stat">
							<thead>
								<tr>
									<th><a>	 Mileage Spread	</a></th>
									<th><a>	 Total ROs		</a></th>
									<th><a>  Percentage		</a></th>
								</tr>
							</thead>
							<tbody>';
								for ($i=0; $i<count($data); $i++) {
									$perc = ($ro_count>0) ? ($data[$i]['COUNT(a.ronumber)']/$ro_count) : 0;
									$html .='
									<tr>
										<td>'.$data[$i]['carmileage'].'</td>
										<td>'.number_format($data[$i]['COUNT(a.ronumber)']).'</td>
										<td>'.number_format($perc*100,2).'%</td>
									</tr>';
									$export .= $data[$i]['carmileage'].",";
									$export .= $data[$i]['COUNT(a.ronumber)'].",";
									$export .= ($perc*100)."% \n";
								}
							$html .='
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->'; 
		$export .= "\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
		
		return $html;
	}
	
	public function getRoStartEndDates($array) {
		// Establish arrays
		$stmt = array();
		$params = array();
		
		//echo 'getRoStartEndDates $array param: '.var_dump($array).'<br>';
		
		// Begin query statement
		$stmt[] = "SELECT MIN(a.ro_date), MAX(a.ro_date) FROM repairorder_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
				   
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
				   
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		//echo 'query: '.$query.' params: '.var_dump($params).'<br>';
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		
		if(!$stmt->execute($params)) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$result = $stmt->fetch();
			$min_date = $result[0];
			$max_date = $result[1];
			
			if($min_date == null) {
				$min_date = date("Y-m-01");
				$max_date = date("Y-m-d");
				// Set $no_ro_entries = true so that First RO and Last RO presentation can say 'N/A'
				$no_ro_entries = true;
			} else {
				$no_ro_entries = false;
			}
			
			//echo 'min_date: '.$min_date.' max_date: '.$max_date.'<br>';
		}
		// Return array containing first entry date and last entry date
		return array('min_date'=>$min_date, 'max_date'=>$max_date, 'no_ro_entries'=>$no_ro_entries);
	}
	
	public function getRoEntryStats($array) {
	
		// Get first and last ro dates entered
		$ro_dates = $this->getRoStartEndDates($array);
		$min_date = $ro_dates['min_date'];
		$max_date = $ro_dates['max_date'];
		
		// Get RO count
		$ro_count = $this->getRoCount($array);
		
		// Establish arrays
		$stmt = array();
		$params = array();
		
		$stmt[] = "SELECT COUNT(DISTINCT(a.ro_date)) FROM repairorder_welr a 
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
		
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
				   
		// Build remaining query statement based on params.  Will reuse these later on.  Save unique name.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		// Add last GROUP BY clause
		//$query .= " GROUP BY a.ro_date ";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		//return 'stmt: '.print_r($stmt);

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			$result = $stmt->fetchColumn();
			$ros_per_day = ($result>0) ? number_format(($ro_count / $result),1) : 0;
		}
		return array('min_date'=>$min_date, 'max_date'=>$max_date, 'ros_per_day'=>$ros_per_day);
	}
	
	public function getRoEntryStatsTable($array) {
	
		// Get data
		$data = $this->getRoEntryStats($array);
		
		// Initialize $html (will not exist yet if $array['search_feedback'] is false
		$html = '';
		
		// Build table
		$html .='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Entry Stats: </h4>
					</div>
				</div>';
		$export = "Entry Stats \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
			//$export .= $array['search_feedback']."\n";
		}
		
		$export .= "First RO, Last RO, Avg ROs Per Day \n";
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="stats_table" style="margin-bottom: 6px;">
							<tr>
								<td><h6>First RO: <span class="misc_span">'.$data['min_date'].'</span></h6></td>
								<td><h6>Last RO: <span class="misc_span">'.$data['max_date'].'</span></h6></td>
								<td><h6>Avg ROs Per Day: <span class="misc_span">'.number_format($data['ros_per_day']).'</span></h6></td>
							</tr>
						</table>
						<p style="color: blue; font-size: 12px;">**Note:  Total days are based on actual RO dates, NOT total time elapsed</p>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->'; 
		$export .= $data['min_date'].",".$data['max_date'].",".$data['ros_per_day']."\n";
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
		
		return $html;
	}
	
	public function getPageHeading($array) {
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'];
           				if($array['title_info']) {
           					$html .='
           					<span class="blue"> '.$array['title_info'].' </span>';
           				} 
           				if($array['a_id']) {
           					$html .='
           					<a id="'.$array['a_id'].'" style="color: green; font-size: 15px;"> &nbsp; '.$array['link_msg'].' </a>';
           				}
           			$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Stats" href="system/utils/export_stats.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Stats Tables" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['ro_count']) {
					  	$html .='
						&nbsp;Total ROs: '.number_format($this->getRoCount($array));
					  }
					$html .='
					</p>
				</div>';
			$html .='
           	</div>
        </div>
        <!-- Container Begin -->
        <div class="row" style="margin-top:-20px">';
		return $html;
	}
	
	// Get RO count for metrics computations.  Note that this method is duplicated in Welr class due to difficulties in design
	public function getRoCount($array) {
		//echo '$array from getRoCount: '.var_dump($array).'<br>';
		
		// Initialize $stmt and $params and build query dynamically
		$stmt 	= array();
		$params = array();
		$stmt[] = "SELECT COUNT(ronumber) FROM repairorder_welr a
				  LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
				  
		// Add $stmt and $params to $array
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];

		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}

		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = (int)$stmt->fetchColumn();
			//echo '$stmt: '.var_dump($stmt).'<br>';
			//echo '$result: '.$result.'<br>';
		}
	}
	
	// Get array of RO totals per reporting month
	public function getRoTrendData($array) {
		
		// First, get beginning and ending RO dates (ro_date from repairorder_welr table)
		$ro_dates = $this->getRoStartEndDates($array);
		$min_date = $ro_dates['min_date'];
		$max_date = $ro_dates['max_date'];
		$no_ro_entries = $ro_dates['no_ro_entries']; // This will be used for 'First RO' and 'Last RO' titles if no entries exists
		
		// Get average ROs entered per day
		$ros_per_day = $this->getRoEntryStats($array);
		$ros_per_day = $ros_per_day['ros_per_day'];
		
		// Next, create range of dates using getMonthRanges() function.  Will loop through these for each month range
		$date_array = DateTimeCalc::getMonthRanges(array('date1_sql'=>$min_date, 'date2_sql'=>$max_date));
		
		/* Take $date_array and create an array of months (month name format) for table row generation
		 * Will return an array of month names
		**/
		$month_names = array();
		for($i=0; $i<count($date_array); $i++) {
			// Convert month number to month name
			$month_names[] = date("F", strtotime($date_array[$i]))." '".date("y", strtotime($date_array[$i]));
			// Increment $i so that every other date is picked up ($date_array will always contain an even number)
			$i += 1;
		}
		
		/* Create a copy of $array so that you can reset $array['date1_sql'] and $array['date2_sql'] if they have been set.
		 * Also move them to the end of $array2 so that they can be popped off each time getRoCount() is run
		**/
		$array2 = array();
		foreach ($array as $key=>$value) {
			if ($key == 'date_range') {		
				unset($array[$key]);
				//$array2[$key] = true;
			} elseif ($key == 'date1_sql') {
				unset($array[$key]);
				//$array2[$key] = $min_date;
			} elseif ($key == 'date2_sql') {
				unset($array[$key]);
				//$array2[$key] = $max_date;
			} else {
				$array2[$key] = $value;
			}	
		}
		
		// Add metrics_trend to $array2 so that dates will be added to params
		$array2['date_range'] = true;
		$array2['stats_search'] = true;
		
		$ro_count = array();
		// Loop through each date range and get ro count for each one
		for($i=0; $i<count($date_array); $i++) {
			$array2['date1_sql'] = $date_array[$i];
			$array2['date2_sql'] = $date_array[$i+1];
			//echo 'date1_sql['.$i.']: '.$array2['date1_sql'].'<br>';
			//echo 'date2_sql['.($i+1).']: '.$array2['date2_sql'].'<br>';
			//echo '$array2: '.var_dump($array2).'<br>';
			// Reset $array 
			$ro_count[] = $this->getRoCount($array2);
			// Add 1 to $i so that next month range is correctly included in query
			$i += 1;
			
			for($a=0; $a<2; $a++) {
				array_pop($array2);
			}
		}
		//echo 'ro_count_array: '.var_dump($ro_count).'<br>';
		return array('ro_count_array'=>$ro_count, 'month_name_array'=>$month_names, 'min_date'=>$min_date, 
					 'max_date'=>$max_date, 'no_ro_entries'=>$no_ro_entries, 'ros_per_day'=>$ros_per_day);
	}
	
	public function getRoTrendTable($array) {
	
		// Get array of month names and ro counts for table generation
		$info = $this->getRoTrendData($array);
		
		// Set variables for easy access
		$month_names = $info['month_name_array'];
		$ro_count_array = $info['ro_count_array'];
		$min_date = $info['min_date'];
		$max_date = $info['max_date'];
		$ros_per_day = $info['ros_per_day'];
		$no_ro_entries = $info['no_ro_entries'];
		
		// Create ro count stats table
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4 class="table_title"> Order Entry Analysis: </h4>
					</div>
				</div>';
		$export = "Order Entry Analysis \n";
		
		// Show search feedback if true
		if($array['search_feedback']) {
			$html .='
				<div class="row">
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>
				</div>';
			//$export .= $array['search_feedback']."\n";
		}
		
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<table id="ro_trend_table" class="original responsive stat ro_trend_table">
							<thead>
								<tr>
									<th><a> Category </a></th>';
									$export .= "Category,";
								foreach($month_names as $month) {
									$html .='
									<th><a>	 '.$month.'	</a></th>';
									$export .= $month.",";
								}
								$export .= "\n";
								$html .='
								</tr>
							</thead>
							<tbody>
								<tr>
									<td> ROs Entered </td>';
									$export .= "ROs Entered,";
								foreach ($ro_count_array as $ro_count) {
									$html .='
									<td>'.number_format($ro_count).'</td>';
									$export .= $ro_count.",";
								}
							$export .= "\n\n";
							$html .='
								</tr>
							</tbody>
						</table>
					</div>
				</div>';
		
		// If there are no RO entries in dates specified, show "N/A" for 'First RO' and 'Last RO' titles
		$min_date = ($no_ro_entries) ? "N/A" : date("m/d/Y", strtotime($min_date));
		$max_date = ($no_ro_entries) ? "N/A" : date("m/d/Y", strtotime($max_date));
		
		$html .='
				<div class="row">
					<div class="large-12 columns">
						<h6 class="first_last_ro_msg"> First RO: <span class="misc_span">'.$min_date.'</span> &nbsp; &nbsp; Last RO: <span class="misc_span">'.$max_date.'</span> &nbsp; &nbsp; Avg ROs Per Day: <span class="misc_span">'.$ros_per_day.'</span></h6>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->'; 
		$export .= "First RO: ,".$min_date."\n";
		$export .= "Last RO: ,".$max_date."\n";
		$export .= "Avg ROs Per Day: ,".$ros_per_day;
		
		// Save $export data to SESSION var
		$_SESSION['export_stats'] = (!isset($_SESSION['export_stats'])) ? $export : $_SESSION['export_stats'] .= $export;
	
		return $html;
	}
}