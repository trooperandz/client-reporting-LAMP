<?php
/**
 * Program: class.Metrics.inc.php
 * Created: 04/04/2016 by Matt Holland
 * Purpose: Display metrics data etc
 * Methods: getPageHeading(): Build page heading for all metrics interfaces
 			getRoCount(): Get RO count for metrics computations (duplicated in Welr class)
 			getMetricsDlrCompData(): Builds data array of each dealer (all or group) specified in user form to be used for comparison
 			getMetricsDlrCompTable(): Builds table for dealer comparison display (metrics)
 			getMetricsTrendData(): Builds data array of metrics trends by month (dealer(s) based on user input)
 			getMetricsTrendTable(): Builds table for display of metrics trend data
 			getMetricsData(): Builds metrics data array for each specified dealer (the workhorse of this class)
 			getMetricsTable(): Builds metrics table (for non-comparison data)
 			getLaborPartsData(): Builds data array for labor and parts info
 			getLaborPartsTable(): Builds table for labor and parts info display
 * Updates:
 */

Class Metrics extends PDO_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
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
						<a class="tooltip-tip" title="Export Metrics" href="system/utils/export_metrics.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Metrics Tables" href="#" onclick="window.print();">
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
	
	// Get RO count for metrics computations.  Note that this method is duplicated in Welr class due to difficulties in design
	public function getRoCount($array) {
		
		// Initialize $stmt and $params and build query dynamically
		$stmt 	= array();
		$params = array();
		$stmt[] = "SELECT COUNT(ronumber) FROM repairorder_welr a
				   LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
				   
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.
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
		}

	}
	
	public function getMetricsDlrCompData($array) {
		/* 1) Receive input of date ranges, and possible dealer group
		 * 2) If input does not have dealer group, get list of all dealerID's from DealerInfo class and put inside an array (to use in foreach as $array['dealer_id'])
		 * 3) If input does have dealer group, take the dealer group array and set to variable for easy access (will use in foreach)
		 * 4) Run getMetricsData() funcion for each dealerID and save to an array
		**/
		
		// Get Service data to provide to getMetricsData() method
		$svc = new ServicesInfo($dbo=null);
		$services = $svc->getServiceInfo($sort_welr=false, $sort_metrics=true);
		
		// Set $svc_count equal to total services
		$svc_count = count($services['svc_info']);
		
		// Set $i increment equal to starting point 0
		$i = 0;
		
		// Set loop limit $svc_total equal to total # services
		$svc_total = $svc_count;
		
		// Add new items to the existing $array for passing into getMetricsData() (array_push is slower)
		$array['svc_count'] = $svc_count;
		$array['i'] = $i;
		$array['svc_total'] = $svc_total;
		$array['svcs_info'] = $services;
		
		// If the POST did not contain a dealer group, obtain all dealerIDs in online reporting system
		if(!$array['dealer_group']) {
			$dlr = new DealerInfo($dbo=null);
			$dealers = $dlr->getDealerInfo(); // This is an array which contains dealerIDs, codes and names for dealers in the online reporting system
			// Now run foreach to establish an easy-access variable compatible with both dealer group and all dealers
			$dealer_ids = array();
			$dealer_codes = array();
			$dealer_names = array();
			for ($dlr=0; $dlr<count($dealers); $dlr++) {
				$dealer_ids[] = $dealers[$dlr]['dealerID'];
				$dealer_codes[] = $dealers[$dlr]['dealercode'];
				$dealer_names[] = $dealers[$dlr]['dealername'];
			}
		} else {
			$dealer_ids   = $array['dealer_id_list'];
			$dealer_codes = $array['dealer_code_list'];
			$dealer_names = $array['dealer_name_list'];
		}
		
		// Now run a loop to get metrics data & labor and parts data for each dealerID, and save to an array
		$trend_data = array();
		$labor_parts_data = array();
		foreach($dealer_ids as $id) {
		
			// Add dealerID to the end of the array for correct dealer processing
			$array['dealer_id'] = $id;
			
			$trend_data[] = $this->getMetricsData($array);
			
			$labor_parts_data[] = $this->getLaborPartsData($array);
			
			// Pop off last array item ('dealer_id') and re-add it before the method is called again
			for($a=0; $a<1; $a++) {
				array_pop($array);
			}
		}
		//return 'trend_array: '.var_dump($trend_array);
		return array('trend_data'=>$trend_data, 'dealer_ids'=>$dealer_ids, 'dealer_codes'=>$dealer_codes, 
					 'dealer_names'=>$dealer_names, 'svcs_info'=>$services, 'labor_parts'=>$labor_parts_data);
	}
	
	public function getMetricsDlrCompTable($array) {
		// Must pass $array['table_title'], $array['table_id'], $array['metric_type'] into method for table generation
		
		// Get trending data.  Will include array('trend_data', 'dealer_names, 'dealer_codes', 'svcs_info', 'labor_parts')
		$trend_data = $this->getMetricsDlrCompData($array);
		
		// Establish easy access to dealercodes
		if(!$trend_data['dealer_ids']) {
			$dealer_codes = $array['dealer_code_list'];
			$dealer_names = $array['dealer_name_list'];
		} else {
			$dealer_codes = $trend_data['dealer_codes'];
			$dealer_names = $trend_data['dealer_names'];
		}
		
		// Establish easy access to $trend_data arrays
		$trend_array = $trend_data['trend_data'];
		$svcs_info = $trend_data['svcs_info']['svc_info'];
		$labor_parts_array = $trend_data['labor_parts'];
		
		// Create array of table titles
		$table_list = array('Sales Percentage', 'Opportunity', 'Close Percentage', 'Total Requests', 'Total Adds', 'Total Declines');
		
		// Create array of indexes for each metric type
		$metric_list = array('sales_rate', 'close_rate', 'frequency', 'req_rows', 'add_rows', 'dec_rows');
		
		// Create array of table id's for DataTables initialization
		$table_id_list = array('sales_table', 'close_table', 'freq_table', 'req_table', 'add_table', 'dec_table');
		
		// Set up export titles
		$export  = "Dealer Comparison Data \n";
		$export .= ($array['date_range']) ? $array['date1_pres']." through ".$array['date2_pres']."\n\n" : null;
		
		/* Run tables inside of for loop to create each metrics table type, using above arrays for data indexes
		 * Make sure that index identifier does not conflict with existing $i
		**/
		
		for($t=0; $t<count($table_list); $t++) {
			// Define variables
			$table_title = $table_list[$t];
			$table_id = $table_id_list[$t];
			$metric_type = $metric_list[$t];
			
			// Build html table
			$html .='
			<div class="box">
				<div class="box-body">
					<div class="row">
						<div class="large-12 columns">
							<h4>'.$table_title.'</h4>
						</div>';
			$export .= $table_title."\n";
			
			// Show search feedback if applicable
			if($array['search_feedback']) {
				$html .='<div class="large-12 columns">
							 <h7>'.$array['search_feedback'].'</h7>
						 </div>';
				$export .= $array['search_feedback']."\n";
			}
			
			$html .='
						<div class="large-12 columns">
							<table id="'.$table_id.'" class="original responsive trend dlr_comp">
								<thead>
									<tr>
										<th class="first"><a> Dealer Name </a></th>
										<th><a> Code		</a></th>
										<th><a> RO Count    </a></th>';
										$export .= "Dealer Name,Code,RO Count,";
										for($i=0; $i<count($svcs_info); $i++) {
											$html .='
											<th><a>'.$svcs_info[$i]['trend_nickname'].'</a></th>';
											$export .= $svcs_info[$i]['trend_nickname'].",";
										}
									$export .= "\n";
									$html .='	
									</tr>
								</thead>
								<tbody>';
									$i = 0;
									foreach($trend_array as $trend) {
										$html .='
										<tr>
											<td class="first">'.$dealer_names[$i].'</td>
											<td>'.$dealer_codes[$i].'</td>
											<td>'.number_format($trend['ro_count']).'</td>';
											$export .= $dealer_names[$i].' ('.$dealer_codes[$i]."),".$trend['ro_count'].",";
											foreach($trend[$metric_type] as $metric) {
												if($metric_type == 'sales_rate' || $metric_type == 'close_rate' || $metric_type == 'frequency') {
													$html .='
													<td>'.number_format($metric,1).'%</td>';
													$export .= number_format($metric,1)."%,";
												} else {
													$html .='
													<td>'.$metric.'</td>';
													$export .= $metric.",";
												}
											}
										$i += 1;
										$html .='
										</tr>';
										$export .= "\n";
									}
								$html .='
								</tbody>
							</table>
						</div>
					</div> <!-- end div row -->
				</div> <!-- end div box-body -->
			</div> <!-- end div box -->'; 
			$export .= "\n";
		}
		
		// Now build parts and labor comparison table using $labor_parts_array data
		$html .='
			<div class="box">
				<div class="box-body">
					<div class="row">
						<div class="large-12 columns">
							<h4>Labor and Parts Data</h4>
						</div>';
		$export .= "Labor and Parts Data \n";
		
		// Show search feedback if applicable
		if($array['search_feedback']) {
			$html .='
						<div class="large-12 column s">
						 	<h7>'.$array['search_feedback'].'</h7>
					 	</div>';
			$export .= $array['search_feedback']."\n";
		}
		$export .= "Dealer, Labor, Parts, Total L&P, Avg Labor, Avg Parts, Total Avg L&P, P/L Ratio \n";
		
		$html .='
						<div class="large-12 columns">
							<table id="comp_labor_parts_table" class="original responsive trend dlr_comp comp_labor_parts_table">
								<thead>
									<tr>
										<th class="first"><a> Dealer Name </a></th>
										<th><a> Code		 </a></th>
										<th><a> Labor  	 	 </a></th>
										<th><a> Parts  	 	 </a></th>
										<th><a> Total L&P    </a></th>
										<th><a> Avg Labor 	 </a></th>
										<th><a> Avg Parts 	 </a></th>
										<th><a> Total Avg L&P</a></th>
										<th><a> P/L Ratio 	 </a></th>
									</tr>
								</thead>
								<tbody>';
							    // Now loop through total dealers to build each table row. Use $i counter for access to dealer names
							    $i = 0;
								foreach ($labor_parts_array as $info) {
									$html .='
									<tr>
										<td class="first">'.$dealer_names[$i].				   '</td>
										<td> '.$dealer_codes[$i].							   '</td>
										<td>$'.number_format($info['total_labor'],2).		   '</td>
										<td>$'.number_format($info['total_parts'],2).		   '</td>
										<td>$'.number_format($info['total_labor_parts'],2).    '</td>
										<td>$'.number_format($info['avg_labor'],2).			   '</td>
										<td>$'.number_format($info['avg_parts'],2).			   '</td>
										<td>$'.number_format($info['avg_total_labor_parts'],2).'</td>
										<td> '.number_format($info['parts_labor_ratio'],2).	   '</td>
									</tr>';
									$export .= $dealer_names[$i]." (".$dealer_codes[$i]."),";
									$export .= "$".$info['total_labor'].",";
									$export .= "$".$info['total_parts'].",";
									$export .= "$".$info['total_labor_parts'].",";
									$export .= "$".$info['avg_labor'].",";
									$export .= "$".$info['avg_parts'].",";
									$export .= "$".$info['avg_total_labor_parts'].",";
									$export .= $info['parts_labor_ratio']."\n";
									$i += 1;
								}
								$html .='
								</tbody>
							</table>
						</div>
					</div> <!-- end div row -->
				</div> <!-- end div box-body -->
			</div> <!-- end div box -->';
		
		// Set SESSION export var for export link
		$_SESSION['export_metrics'] = $export;
		
		// Return table markup
		return $html;
	}
	
	public function getMetricsTrendData($array) {
		/* 1) Receive input of date ranges, $date1 and $date2 (first range will go through end of month, last will end on POST)
		 * 2) Get number of months and then convert date range inputs to succession of beginning and ending dates. Save to array.
		 * 3) Run getMetricsData() function for each established date range, and save to an array.
		 * 4) Run the resulting array through the getMetricsTrendsTable() method for generation of final table
		**/
		
		// Get Service data to provide to getMetricsData() method
		$svc = new ServicesInfo($dbo=null);
		$services = $svc->getServiceInfo($sort_welr=false, $sort_metrics=true);
		
		// Set $svc_count equal to the service level that has been called
		$svc_count = count($services['svc_info']);
		
		// Set $i increment equal to starting point 0
		$i = 0;
		
		// Set loop limit $svc_total equal to total # services
		$svc_total = $svc_count;
		
		// Add new items to the existing $array for passing into getMetricsData() (array_push is slower)
		$array['svc_count'] = $svc_count;
		$array['i'] 		= $i;
		$array['svc_total'] = $svc_total;
		$array['svcs_info'] = $services;
		/*
			Notes for the loop:
				*first time through the loop, use first posted date as start date
		*/
		
		//echo '$array: '.var_dump($array).'<br>';
		//exit;
		
		// Establish array for holding date ranges (first and last will always be in pairs)
		//$date_array = array(); 
		
		// Get POST dates.  These will ALWAYS be entered by user. 'date1_sql' and 'date2_sql' will be added later for looping.
		$date1_post = $array['date1_sql_user'];
		$date2_post = $array['date2_sql_user'];
		//$date1_post = "2015-11-01";  // this is POST date #1
		//$date2_post = "2016-03-11";  // this is POST date #2
		
		// Now use DateTimeClass getDateRanges() method to return array of date ranges based on posted dates
		$date_array = DateTimeCalc::getMonthRanges(array('date1_sql'=>$date1_post, 'date2_sql'=>$date2_post));
		
		// Now run a loop to get date for each date range, and then save the result in an array
		$trend_data = array();
		for($d=0; $d<count($date_array); $d++) {
		
			// Add 'date1_sql' and 'date2_sql' to end of array for correct date range processing
			$array['date1_sql'] = $date_array[$d];
			$array['date2_sql'] = $date_array[$d+1];
			
			// Create metrics data sets and add each one to $trend_data array
			$trend_data[] = $this->getMetricsData($array);
			
			// Create labor and parts data sets and add each one to $labor_parts_data array
			$labor_parts_data[] = $this->getLaborPartsData($array);
			
			// Pop off last two array items ('date1_sql' and 'date2_sql') and re-add them before method is called again
			for($a=0; $a<2; $a++) {
				array_pop($array);
			}
			$d += 1;
		}
		//return 'trend_array: '.var_dump($trend_array);
		return array('trend_data'=>$trend_data, 'date_array'=>$date_array, 'svcs_info'=>$services, 'labor_parts'=>$labor_parts_data);
	}
	
	public function getMetricsTrendTable($array) {
		// Must pass $array['table_title'], $array['table_id'], $array['metric_type'] into method for table generation
		
		// Get trending data.  Will include array('trend_data', 'date_array', 'svcs_info', 'labor_parts')
		$trend_data = $this->getMetricsTrendData($array);
		//return $trend_data;
		
		// Establish easy access to $trend_data arrays
		$trend_array = $trend_data['trend_data'];
		$date_array = $trend_data['date_array'];
		$svcs_info = $trend_data['svcs_info']['svc_info'];
		$labor_parts_array = $trend_data['labor_parts'];
		//return $date_array;
		
		/* Take $date_array and create an array of months (month name format) for table row generation
		 * Will return an array of month names
		**/
		$month_names = array();
		for($i=0; $i<count($date_array); $i++) {
			// Convert month number to month name
			$month_names[] = date("F", strtotime($date_array[$i]));
			// Increment $i so that every other date is picked up ($date_array will always contain an even number)
			$i += 1;
		}
		
		// Create array of table titles
		$table_list = array('Sales Percentage', 'Opportunity', 'Close Percentage', 'Total Requests', 'Total Adds', 'Total Declines');
		
		// Create array of indexes for each metric type
		$metric_list = array('sales_rate', 'close_rate', 'frequency', 'req_rows', 'add_rows', 'dec_rows');
		
		// Create array of table id's for DataTables initialization
		$table_id_list = array('sales_table', 'close_table', 'freq_table', 'req_table', 'add_table', 'dec_table');
		
		// Set up export titles
		$export  = "Metrics Trending By Month \n";
		$export .= ($array['dealer_id']) ? "Dealer ".$_SESSION['dealer_code']."\n\n" : "Filtered Results \n\n";
		
		/* Run tables inside of for loop to create each table type, using above arrays for data indexes
		 * Make sure that index identifier does not conflict with existing $i
		**/
		
		for($t=0; $t<count($table_list); $t++) {	
		
			// Define variables
			$table_title = $table_list[$t];
			$table_id = $table_id_list[$t];
			$metric_type = $metric_list[$t];
			
			// Build html table
			$html .='
			<div class="box">
				<div class="box-body">
					<div class="row">
						<div class="large-12 columns">
							<h4>'.$table_title.'</h4>
						</div>';
			$export .= $table_title."\n";
			
			// Show search feedback if applicable
			if($array['search_feedback']) {
				$html .='<div class="large-12 column s">
							 <h7>'.$array['search_feedback'].'</h7>
						 </div>';
				$export .= $array['search_feedback']."\n";
			}
			
			$html .='
						<div class="large-12 columns">
							<table id="'.$table_id.'" class="original responsive trend">
								<thead>
									<tr>
										<th><a>  Month	   </a></th>
										<th><a>  RO Count  </a></th>';
										$export .= "Month,RO Count,";
										for($i=0; $i<count($svcs_info); $i++) {
											$html .='
											<th><a>'.$svcs_info[$i]['trend_nickname'].'</a></th>';
											$export .= $svcs_info[$i]['trend_nickname'].",";
										}
									$export .= "\n";
									$html .='	
									</tr>
								</thead>
								<tbody>';
									$i=0;
									foreach($trend_array as $trend) {
										$html .='
										<tr>
											<td>'.$month_names[$i].'</td>
											<td>'.number_format($trend['ro_count']).'</td>';
											$export .= $month_names[$i].",".$trend['ro_count'].",";
											foreach($trend[$metric_type] as $metric) {
												if($metric_type == 'sales_rate' || $metric_type == 'close_rate' || $metric_type == 'frequency') {
													$html .='
													<td>'.number_format($metric,1).'%</td>';
													$export .= number_format($metric,1)."%,";
												} else {
													$html .='
													<td>'.$metric.'</td>';
													$export .= $metric.",";
												}
											}
										$html .='
										</tr>';
										// Increment $i for month name
										$i += 1;
										$export .= "\n";
									}
								$html .='
								</tbody>
							</table>
						</div>
					</div> <!-- end div row -->
				</div> <!-- end div box-body -->
			</div> <!-- end div box -->';
			$export .= "\n";
		}
		
		// Now build parts and labor comparison table using $labor_parts_array data
		$html .='
		<div class="box">
				<div class="box-body">
					<div class="row">
						<div class="large-12 columns">
							<h4>Labor and Parts Data</h4>
						</div>';
		$export .= "Labor and Parts Data \n";
		
		// Show search feedback if applicable
		if($array['search_feedback']) {
			$html .='
						<div class="large-12 column s">
						 	<h7>'.$array['search_feedback'].'</h7>
						</div>';
			$export .= $array['search_feedback']."\n";
		}
		$export .= "Dealer, Labor, Parts, Total L&P, Avg Labor, Avg Parts, Total Avg L&P, P/L Ratio \n";
		
		$html .='
						<div class="large-12 columns">
							<table id="comp_labor_parts_table" class="original responsive trend dlr_comp trend_labor_parts_table">
								<thead>
									<tr>
										<th><a>Month 	 	</a></th>
										<th><a>Labor  	 	</a></th>
										<th><a>Parts  	 	</a></th>
										<th><a>Total L&P    </a></th>
										<th><a>Avg Labor 	</a></th>
										<th><a>Avg Parts 	</a></th>
										<th><a>Total Avg L&P</a></th>
										<th><a>P/L Ratio 	</a></th>
									</tr>
								</thead>
								<tbody>';
							    // Now loop through total dealers to build each table row. Use $i counter for access to dealer names
							    $i = 0;
								foreach ($labor_parts_array as $info) {
									$html .='
									<tr>
										<td>'.$month_names[$i].'</td>
										<td>$'.number_format($info['total_labor'],2).'</td>
										<td>$'.number_format($info['total_parts'],2).'</td>
										<td>$'.number_format($info['total_labor_parts'],2).'</td>
										<td>$'.number_format($info['avg_labor'],2).'</td>
										<td>$'.number_format($info['avg_parts'],2).'</td>
										<td>$'.number_format($info['avg_total_labor_parts'],2).'</td>
										<td>'.number_format($info['parts_labor_ratio'],2).'</td>
									</tr>';
									$export .= $month_names[$i].",";
									$export .= "$".$info['total_labor'].",";
									$export .= "$".$info['total_parts'].",";
									$export .= "$".$info['total_labor_parts'].",";
									$export .= "$".$info['avg_labor'].",";
									$export .= "$".$info['avg_parts'].",";
									$export .= "$".$info['avg_total_labor_parts'].",";
									$export .= $info['parts_labor_ratio']."\n";
									$i += 1;
								}
								$html .='
								</tbody>
							</table>
						</div>
					</div> <!-- end div row -->
				</div> <!-- end div box-body -->
			</div> <!-- end div box -->';
		
		// Set SESSION export var for export link
		$_SESSION['export_metrics'] = $export;
		
		// Return table markup
		return $html;
	}
	
	public function getMetricsData($array) {
		/* $array contains the following: 
		 * 	'svc_count'=>$svc_count, 'i'=>$i, 'svc_total'=>$svc_total, 'svcs_info'=>$services, 'dealer_id'=>$dealer_id,
		 *  'date_range'=>$date_range (bool)
		 */
		
		// Get total RO count. Works up to here
		$ro_count = $this->getRoCount($array);
		
		// Set variables from multiple array param for easy access
		$svc_count = $array['svc_count'];
		$index = $array['i'];
		$svc_total = $array['svc_total'];
		$svcs_info = $array['svcs_info'];
		$dealer_id = $array['dealer_id'];
		
		/*
		echo 'index: '.$array['i'].'<br>';
		echo 'svcs_info: '.var_dump($svcs_info).'<br>';
		echo '$svcs_info[svc_info][0][serviceID]: '.$svcs_info['svc_info'][0]['serviceID'].'<br>';
		echo 'svc_total: '.$svc_total.'<br>';
		*/
		
		// Build export data
		$export  = "";
		$export .= MANUF." Metrics Export \n\n";
		
		// Dynamically create prepared statement for servicerendered_welr select based on user selected inputs
		$stmt = array();
		$params = array();
		$stmt[]  = "SELECT COUNT(serviceID) FROM servicerendered_welr a
				    LEFT JOIN dealer b ON(a.dealerID = b.dealerID)";
		
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		$query .= "AND a.serviceID = ? AND a.addsvc = ? AND a.decsvc = ?";
		
		// Test query statement
		//return '$query: '.$query.'<br>';
		//exit;
		
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return 'error_svc_insert';
		}
		//return var_dump($stmt);
		
		// Get Requested, Added, Declined counts for requested services.  First, initialize arrays.
		$req_rows  = array();
		$add_rows  = array();
		$dec_rows  = array();
		$frequency = array();
		$close_rate= array();
		$sales_rate= array();
		
		for($i=$index; $i<$svc_total; $i++) {
			$params[] = $svc_id = $svcs_info['svc_info'][$i]['serviceID'];
			//echo 'svc_id: '.$svc_id.'<br>';
			// Get Req counts
			$params[] = $addsvc = 0;
			$params[] = $decsvc = 0;
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return 'error_svc_insert';
			} else {
				$req_rows[$i] = $stmt->fetchColumn();
				// Now remove last two params[] (addsvc and decsvc) for next query
				for($p=0; $p<2; $p++) {
					array_pop($params);
				}
				//$req_rows[$i] = $stmt->num_rows;
			}
			
			// Get Added counts
			$params[] = $addsvc = 1;
			$params[] = $decsvc = 0;
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return 'error_svc_insert';
			} else {
				$add_rows[$i] = $stmt->fetchColumn();
				// Now remove last two params[] (addsvc and decsvc) for next query
				for($p=0; $p<2; $p++) {
					array_pop($params);
				}
				//$add_rows[$i] = $stmt->num_rows;
			}
			
			// Get Declined counts
			$params[] = $addsvc = 0;
			$params[] = $decsvc = 1;
			if(!($stmt->execute($params))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				return 'error_svc_insert';
			} else {
				$dec_rows[$i] = $stmt->fetchColumn();
				// Now remove last three params[] (serviceID, addsvc and decsvc) for next query
				for($p=0; $p<3; $p++) {
					array_pop($params);
				}
				//$dec_rows[$i] = $stmt->num_rows;
			}
			
			// Total reg + add + declined = Total Opp
			$total_opp[$i] = ($req_rows[$i] + $add_rows[$i] + $dec_rows[$i]);
			
			// Opportunities divided by total invoices
			if ($ro_count == 0) {
				$frequency[$i] = 0;
			} else {
				$frequency[$i] = ($total_opp[$i] / $ro_count)*100;
			}
			
			// Close rate: (Total regular + add) / total opp
			if ($total_opp[$i] == 0 ) {
				$close_rate[$i] = 0;
			} else {
				$close_rate[$i] = (($req_rows[$i]+$add_rows[$i]) / $total_opp[$i])*100;
			}
			
			// Percent sold: number sold divided by total invoices
			if ($ro_count == 0) {
				$sales_rate[$i] = 0;
			} else {
				$sales_rate[$i] = (($req_rows[$i] + $add_rows[$i]) / $ro_count)*100;
			}
		}
		
		// Compute total row counts for each service category for presentation of table totals
				
		// Compute total req rows
		$total_req_rows = 0;
		foreach ($req_rows as $req) {
			$total_req_rows = $total_req_rows + $req;
		}
		// Compute total add rows
		$total_add_rows = 0;
		foreach ($add_rows as $add) {
			$total_add_rows = $total_add_rows + $add;
		}
		// Compute total dec rows
		$total_dec_rows = 0;
		foreach ($dec_rows as $dec) {
			$total_dec_rows = $total_dec_rows + $dec;
		}
		
		// Generate frequency % average with only those percentages whose values are > 0
		$total_frequency = 0;
		$total_frequency_array = array();
		foreach ($frequency as $freq) {
			if ($freq > 0) {
				$total_frequency = $total_frequency + $freq;
				$total_frequency_array[] = $freq;
			}
		}
		$total_frequency_array = count($total_frequency_array);
		if ($total_frequency_array > 0) {
			$total_frequency = $total_frequency/$total_frequency_array;
		} else {
			$total_frequency = 0;
		}
		
		// Generate close % average with only those percentages whose values are > 0
		$total_close_rate = 0;
		$total_close_array = array();
		foreach ($close_rate as $close) {
			if ($close > 0) {
				$total_close_rate = $total_close_rate + $close;
				$total_close_array[] = $close;
			}
		}
		$total_close_array = count($total_close_array);
		if ($total_close_array > 0) {
			$total_close_rate = $total_close_rate / $total_close_array;
		} else {
			$total_close_rate = 0;  
		}
		
		// Generate sales % average with only those percentages whose values are > 0
		$total_sales_rate = 0;
		$total_sales_array= array();
		foreach ($sales_rate as $sales) {
			if ($sales > 0) {
				$total_sales_rate = $total_sales_rate + $sales;
				$total_sales_array[] = $sales;
			}
		}
		$total_sales_array = count($total_sales_array);
		if ($total_sales_array > 0) {
			$total_sales_rate= ($total_sales_rate / $total_sales_array);
		} else {
			$total_sales_rate = 0;
		}
		// Now return array of all results to getMetricsTable() method for generation of the metrics table
		return array('req_rows'=>$req_rows, 'add_rows'=>$add_rows, 'dec_rows'=>$dec_rows, 
					 'frequency'=>$frequency, 'close_rate'=>$close_rate, 'sales_rate'=>$sales_rate,
					 'total_req_rows'=>$total_req_rows, 'total_add_rows'=>$total_add_rows, 'total_dec_rows'=>$total_dec_rows,
					 'total_frequency'=>$total_frequency, 'total_close_rate'=>$total_close_rate, 'total_sales_rate'=>$total_sales_rate,
					 'export_data'=>$export, 'ro_count'=>$ro_count
					 );				 
	}
	
	public function getMetricsTable($array) {
		/* $array contains the following: 
		 * 'L1_svcs'=>$L1_svcs, 'L2_3_svcs'->$L2_3_svcs (true or false), 'dealer_id'=>$dealer_id
		 */
	
		// Get Service data to provide L1 and L2_3 serviceID arrays
		$svc = new ServicesInfo($dbo=null);
		$services = $svc->getServiceInfo($sort_welr=false, $sort_metrics=true);
		$L1_count = 0;
		$L2_3_count = 0;
		foreach($services['svc_level'] as $level) {
			if($level == 1) {
				$L1_count += 1;
			} else {
				$L2_3_count += 1;
			}
		}
		
		// Set $svc_count equal to the service level that has been called
		$svc_count = ($array['metrics_table'] == 'L1') ? $L1_count : $L2_3_count;
		// Set $i increment equal to 0 or $L1_count, based on service level that has been called
		$i = ($array['metrics_table'] == 'L1') ? 0 : $L1_count;
		// Set loop limit $svc_total equal to total L1 services, or total L2+3 services, based on service level that has been called
		$svc_total = ($array['metrics_table'] == 'L1') ? $L1_count : ($L1_count + $L2_3_count); 
		// Set table_id based on type of table param passed
		$table_id = ($array['metrics_table'] == 'L1') ? "L1_table" : "L2_3_table";
		$table_title = ($array['metrics_table'] == 'L1') ? "Basic Services" : "Other Services";
		
		// Add new items to the existing $array for passing into getMetricsData() (array_push is slower)
		$array['svc_count'] = $svc_count;
		$array['i'] 		= $i;
		$array['svc_total'] = $svc_total;
		$array['svcs_info'] = $services;
		
		// Get metrics data for table generation
		$metrics = $this->getMetricsData($array);
		
		// Establish export variable for easy access
		$export = $metrics['export_data'];
		
		// Print out all params in export data file
		if($array['export_feedback']) {
			$total = count($array['export_feedback']);
			$m = 0;
			foreach($array['export_feedback'] as $msg) {
				$export .= ($m == $total-1) ? $msg : $msg."\n";
				$m += 1;
			}
		} else {
			if($array['search_feedback']) {
				$export .= $array['search_feedback'];
			}
		}
		
		// Add ro count to export
		$export .= "\nTotal ROs:, ".$metrics['ro_count'];
		
		// Build html
		$html = '
		<div class="box">
			<div class="box-body">
				<div class="row">';
		
		// Build export columns
		$export .= "\n\n";
		if($array['metrics_table'] == 'L1') {
			$export .= "Basic Services \n";
		} else {
			$export .= "Other Services \n";
		}
		$export .= 'Service Type,' ;
		$export .= 'Total Req,'	   ;
		$export .= 'Total Add,'	   ;
		$export .= 'Total Dec,'	   ;
		$export .= 'Opportunity,'  ;
		$export .= 'Close Rate,'   ;
		$export .= '% Sold'		   ;
		$export .= "\n";
		
		// Build html table
		$html .='
					<div class="large-12 columns">
						<h4>'.$table_title.'</h4>
					</div>';
			
			// Show search feedback if applicable
			if($array['search_feedback']) {
				$html .='
					<div class="large-12 columns">
						<h7>'.$array['search_feedback'].'</h7>
					</div>';
			}
			
			$html .='
					<div class="large-12 columns">
						<table id="'.$table_id.'" class="original responsive metric">
							<thead>
								<tr>
									<th><a>  Service Type</a></th>
									<th><a>  Total Req	 </a></th>
									<th><a>  Total Add	 </a></th>
									<th><a>  Total Dec	 </a></th>
									<th><a>  Opportunity </a></th>
									<th><a>  Close Rate	 </a></th>
									<th><a>  % Sold		 </a></th>
								</tr>
							</thead>
							<tbody>';
		// Build html table body and export data rows based on increments set above
		for($i=$i; $i<$svc_total; $i++) {
			$html .='
								<tr>
									<td>'.$services['svc_info'][$i]['servicedescription'].'</td>
									<td>'.number_format($metrics['req_rows'][$i]).'</td>
									<td>'.number_format($metrics['add_rows'][$i]).'</td>
									<td>'.number_format($metrics['dec_rows'][$i]).'</td>
									<td>'.number_format($metrics['frequency'][$i],1).'%'.'</td>
									<td>'.number_format($metrics['close_rate'][$i],1).'%'.'</td>
									<td>'.number_format($metrics['sales_rate'][$i],1).'%'.'</td>
								</tr>';
			$export .= $services['svc_info'][$i]['servicedescription'].",";
			$export .= $metrics['req_rows'][$i].",";
			$export .= $metrics['add_rows'][$i].",";
			$export .= $metrics['dec_rows'][$i].",";
			$export .= number_format($metrics['frequency'][$i],1)."%,";
			$export .= number_format($metrics['close_rate'][$i],1)."%,";
			$export .= number_format($metrics['sales_rate'][$i],1)."%,";
			$export .= "\n";			  				  
		}
		// Close the table body and create the table footer
		$html .='
							</tbody>
							<tfoot>
								<tr class="other_sum">
									<td> Service Totals  </td>
									<td>'.number_format($metrics['total_req_rows']).'</td>
									<td>'.number_format($metrics['total_add_rows']).'</td>
									<td>'.number_format($metrics['total_dec_rows']).'</td>
									<td>'.number_format($metrics['total_frequency'],1).'%</td>
									<td>'.number_format($metrics['total_close_rate'],1).'%</td>
									<td>'.number_format($metrics['total_sales_rate'],1).'%</td>
								</tr>
							</tfoot>
						</table>
					</div> <!-- end div large-12 columns -->
				</div> <!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		$export .= "Service Totals:,";
		$export .= $metrics['total_req_rows'].",";
		$export .= $metrics['total_add_rows'].",";
		$export .= $metrics['total_dec_rows'].",";
		$export .= number_format($metrics['total_frequency'],1)."%,";
		$export .= number_format($metrics['total_close_rate'],1)."%,";
		$export .= number_format($metrics['total_sales_rate'],1)."%,";
		$export .= "\n\n";
		
		/* Check to see if SESSION['export_metrics'] is already set. 
		 * Could already be set by metrics trending (if so, is not an array).
		 * If array and is > 1, reset for most current run.  Else add the second loop to the array.
		 * If not set at all, create array and add first $export element.
		 * This code was created to prevent another export link and export file from having to be created.
		**/
		if(isset($_SESSION['export_metrics'])) {
		 	if(is_array($_SESSION['export_metrics']) && count($_SESSION['export_metrics']) > 1) {
				$_SESSION['export_metrics'] = array();
				$_SESSION['export_metrics'][] = $export;
			} elseif (is_array($_SESSION['export_metrics']) && count($_SESSION['export_metrics'] == 1)) {
				$_SESSION['export_metrics'][] = $export; 
			} else {
				$_SESSION['export_metrics'] = array();
				$_SESSION['export_metrics'][] = $export;
			}
		} else {
			$_SESSION['export_metrics'] = array();
			$_SESSION['export_metrics'][] = $export;
		}
		
		// Return table html
		return $html;
	}
	
	// Get Labor and Parts data
	public function getLaborPartsData($array) {
	
		// Dynamically create prepared statement based on $array inputs
		$stmt = array();
		$params = array();
		$stmt[]  = "SELECT SUM(a.labor), AVG(a.labor), SUM(a.parts), AVG(a.parts) FROM repairorder_welr a
				    LEFT JOIN dealer b ON(a.dealerID = b.dealerID) ";
		
		// Add $stmt[] and $params[] to array for passing to getQueryStmtParams() method
		$array['stmt'] = $stmt;
		$array['params'] = $params;
		
		// Build remaining query statement based on params.
		$data = Query::getQueryStmtParams($array);
		$query = $data['stmt'];
		$params = $data['params'];
		
		// Test query statement
		//return '$query: '.$query.'<br>';
		//exit;
		
		if(!($stmt = $this->dbo->prepare($query))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
			return 'error_query';
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return 'error_query';
		} else {
			$result = $stmt->fetchAll();
			// Do not use count($result) here, as the query will always at least return zero's even if there are no ROs
			if($result[0]['SUM(a.labor)'] > 0 && $result[0]['SUM(a.parts)'] > 0) {
				$total_labor = $result[0]['SUM(a.labor)'];
				$avg_labor	 = $result[0]['AVG(a.labor)'];
				$total_parts = $result[0]['SUM(a.parts)'];
				$avg_parts	 = $result[0]['AVG(a.parts)'];
				$total_labor_parts = $total_labor+$total_parts;
				$avg_total_labor_parts = $avg_labor + $avg_parts;
				$parts_labor_ratio = ($total_parts/$total_labor);
			} else {
				$total_labor = 0;
				$avg_labor   = 0;
				$total_parts = 0;
				$avg_parts   = 0;
				$total_labor_parts = 0;
				$avg_total_labor_parts = 0;
				$parts_labor_ratio = 0;
			}
		}
		
		return array('total_labor'=>$total_labor, 'avg_labor'=>$avg_labor, 'total_parts'=>$total_parts,
					 'avg_parts'=>$avg_parts, 'total_labor_parts'=>$total_labor_parts, 'avg_total_labor_parts'=>$avg_total_labor_parts, 
					 'parts_labor_ratio'=>$parts_labor_ratio
					);
	}
	
	// Generate labor and parts table for metrics display
	public function getLaborPartsTable($array) {
		// $array contains the following: ('dealer_id'=>$dealer_id, 'date_range'=>$date_range(bool))
		
		$data = $this->getLaborPartsData($array);
		/* $array contains the following:
		 * ('total_labor'=>$total_labor, 'avg_labor'=>$avg_labor, 'total_parts'=>$total_parts,
			'avg_parts'=>$avg_parts, 'total_labor_parts'=>$total_labor_parts, 'avg_total_labor_parts'=>$avg_total_labor_parts, 
			'parts_labor_ratio'=>$parts_labor_ratio
		   );
		**/
		
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">';
		
		// Build heading for table, and table detail
		$html .='
				<div class="small-12 medium-10 large-10 columns">
					<h4 class="table_title" > Parts & Labor Breakdown: </h4>
				</div>';
		
		// Print out all params in export data file
		if($array['export_feedback']) {
			$total = count($array['export_feedback']);
			$m = 0;
			foreach($array['export_feedback'] as $msg) {
				$export .= ($m == $total-1) ? $msg : $msg."\n";
				$m += 1;
			}
		} else {
			if($array['search_feedback']) {
				$export .= $array['search_feedback'];
			}
		}
		
		// Show search feedback if applicable
		if($array['search_feedback']) {
			$html .='
				<div class="large-12 columns">
					<h7>'.$array['search_feedback'].'</h7>
				</div>';
		}
		
		// Build labor and parts table export
		$export .= "\n\nParts & Labor Breakdown";
		
		/*
		$html .='
			<div class="large-12 columns">
				<table class="labor_parts_table">
					<thead>
					</thead>
					<tbody>
						<tr>
							<td> <h5 class="avg_labor_parts_title"> Total Labor: <span class="labor_parts_span">$'.number_format($data['total_labor'],2).'</span></h5> </td>
							<td> <h5 class="avg_labor_parts_title"> Avg Labor: <span class="labor_parts_span">$'.number_format($data['avg_labor'],2).'</span></h5> </td>
							<td> </td>
						</tr>
						<tr style="background-color: #FFFFFF;">
							<td style="border-bottom: 1px solid #000000;"> <h5 class="avg_labor_parts_title"> Total Parts: <span class="labor_parts_span">$'.number_format($data['total_parts'],2).'</span></h5> </td>
							<td style="border-bottom: 1px solid #000000;"> <h5 class="avg_labor_parts_title"> Avg Parts: <span class="labor_parts_span">$'.number_format($data['avg_parts'],2).'</span></h5> </td>
							<td> </td>
						</tr>
						<tr>
							<td> <h5 class="avg_labor_parts_title"> Total L&P: <span class="labor_parts_span">$'.number_format($data['total_labor_parts'],2).'</span></h5> </td>
							<td> <h5 class="avg_labor_parts_title"> Total Avg: <span class="labor_parts_span">$'.number_format($data['avg_total_labor_parts'],2).'</span></h5> </td>
							<td> <h5 class="avg_labor_parts_title"> P / L Ratio: <span class="labor_parts_span">'.number_format($data['parts_labor_ratio'],2).'</span></h5> </td>
						</tr>
					</tbody>
				</table>
			</div>
		</div><!-- end div row -->';*/
		
		$html .='
				  <!--<div class="row">-->
					<div class="large-12 columns">
						<table id="labor_parts_table" class="original responsive metric">
							<thead>
								<tr>
									<th><a> Category  </a></th>
									<th><a> Total Dollars  </a></th>
									<th><a> Average Dollars</a></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td> Labor </td>
									<td> $'.number_format($data['total_labor'],2).' </td>
									<td> $'.number_format($data['avg_labor'],2).'   </td>
								</tr>
								<tr>
									<td> Parts </td>
									<td> $'.number_format($data['total_parts'],2).' </td>
									<td> $'.number_format($data['avg_parts'],2).'   </td>
								</tr>
							</tbody>
							<tfoot>
								<tr class="other_sum">
									<td> L&P Totals </td>
									<td> $'.number_format($data['total_labor_parts'],2).'     </td>
									<td> $'.number_format($data['avg_total_labor_parts'],2).' </td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';	
		
		// Build export
		$export .= "\nTotal Labor:, ".$data['total_labor'].",";
		$export .= "Average Labor:, ".$data['avg_labor']."\n";
		$export .= "Total Parts:, ".$data['total_parts'].", ";
		$export .= "Average Parts:, ".$data['avg_parts']."\n";
		$export .= "Total L&P:, ".$data['total_labor_parts'].", ";
		$export .= "Total Avg:, ".$data['avg_total_labor_parts']."\n\n";
		$export .= "P/L Ratio:, ".$data['parts_labor_ratio'];
		
		$_SESSION['export_labor_parts'] = $export;
		
		return $html;
	}
}