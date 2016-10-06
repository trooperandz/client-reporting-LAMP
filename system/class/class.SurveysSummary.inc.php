<?php
/**
 * Program: class.SurveysSummary.inc.php
 * Created: 04/19/2016 by Matt Holland
 * Purpose: Display summaries for all dealers in the online reporting system
 * Methods: 
 * Updates:
 */

Class SurveysSummary extends PDO_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}
	
	public function getPageHeading($array) {
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'].' 
           				<span class="blue"> - All '.MANUF.' Dealers ('.$_SESSION['dealer_summary_count'].') </span>
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Metrics" href="system/utils/export_dealer_summary.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print Dealer Summary" href="#" onclick="window.print();">
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
		// State query
		$stmt = "SELECT COUNT(ronumber) FROM repairorder_welr";

		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = (int)$stmt->fetchColumn();
		}
	}
	
	// Get data for all dealers
	public function getDealerSummaryData($array) {
		// Create statement
		$sql = "SELECT a.dealerID, COUNT(a.dealerID), 
	      		MAX(a.create_date), MIN(a.create_date), SUM(a.labor), SUM(a.parts), b.dealername, b.dealercode 
		  		FROM repairorder_welr a
		  		INNER JOIN dealer b ON (a.dealerID = b.dealerID)
		  		GROUP BY a.dealerID
		  		ORDER BY b.dealerID ASC";
		  		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($sql))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
		} else {
			return $result = $stmt->fetchAll();
		}
	}
	
	// Build html table and export data for dealers summary
	public function getDealerSummaryTable($array) {
		
		// Get table data
		$data = $this->getDealerSummaryData($array);
		
		// Set SESSION var for count of reporting dealers for page title
		$_SESSION['dealer_summary_count'] = count($data);
		
		// Build html table and export data
		/*
		$html = '
		<div class="large-12 columns">
			<h4> Dealer Summary </h4>
		</div>*/
		
		$html = '
			<div class="box">
				<div class="box-body">
					<div class="row">
						<div class="large-12 columns">
							<table id="dealer_summary_table" class="original dealer_summary">
								<thead>				
								   <tr>	
								   	   <!-- note: table_action is for @media print -->			
									   <th class="table_action"><a> Action</a></th>					
									   <th><a> '.ENTITY.' Name </a></th>					
									   <th><a>  Code 		   </a></th>					
									   <th><a>	Total ROs	   </a></th>					
									   <th><a>	First Entry	   </a></th>					
									   <th><a>	Last Entry	   </a></th>					
									   <th><a>	$$ Per RO 	   </a></th>					
									   <th><a>	Labor		   </a></th>					
									   <th><a>	Parts		   </a></th>					
									   <th><a>	Total L&P	   </a></th>					
									   <th><a>	Rep Days 	   </a></th>				
								   </tr>			
							   </thead>';
		// Build export data
		$export  = "Dealer Reporting Summary \n";
		$export .= "All ".MANUF." Dealers (".count($data).")\n";
		$export .= "Total ROs: ".$this->getRoCount($array=null)."\n\n";
		$export .= ENTITY." Name,".ENTITY." Code, Total ROs, First Entry, Last Entry, Dollars Per RO, Labor, Parts, Total L&P, Reporting Days \n";
		
			if (count($data) > 0) {
				for ($i=0; $i<count($data); $i++) {
					// Set easy access to values
					$dealer_id   = $data[$i]['dealerID'];
					$dealername  = $data[$i]['dealername'];
					$dealercode  = $data[$i]['dealercode'];
					$total_ros   = $data[$i]['COUNT(a.dealerID)'];
					$first_entry = $data[$i]['MIN(a.create_date)'];
					$last_entry  = $data[$i]['MAX(a.create_date)'];
					$dollars_ro  = (($data[$i]['SUM(a.labor)']) + ($data[$i]['SUM(a.parts)']))/($data[$i]['COUNT(a.dealerID)']);
					$total_labor = $data[$i]['SUM(a.labor)'];
					$total_parts = $data[$i]['SUM(a.parts)'];
					$total_lp	 = ($data[$i]['SUM(a.labor)']) + ($data[$i]['SUM(a.parts)']);
					$rep_days    = (((strtotime(date("Y-m-d")) - (strtotime(substr($data[$i]['MIN(a.create_date)'], 0, -9)))))/86400);
					// Note that below <td class="dlr_name"> is for print media css target
					$html .='
								<tr>							
									<td class="table_action">							
										<form class="table_form" id="dealer_summary_form" method="POST" action="#">								
											<input type="hidden" value="'.$dealer_id.'#'.$dealercode.'#'.$dealername.'" id="summary_dealerID" name="summary_dealerID" />								
											<input type="submit" id="dealer_summary_submit" style="margin: 0px; padding: .2em .3em;" class="tiny button radius" value="Select" />							
										</form>							
									</td>							
									<td class="dlr_name">'.substr($dealername, 0, 15).'</td>							
									<td>'.$dealercode.'</td>							
									<td>'.number_format($total_ros).'</td>							
									<td>'.substr($first_entry, 0, -9).'</td>							
									<td>'.substr($last_entry, 0, -9).'</td>							
									<td>$'.number_format($dollars_ro, 2).'</td>							
									<td>$'.number_format($total_labor, 2).'</td>							
									<td>$'.number_format($total_parts, 2).'</td>							
									<td>$'.number_format($total_lp, 2).'</td>							
									<td>'.$rep_days.'</td>						
								</tr>';
					
					$export .= $dealername.",";
					$export .= $dealercode.",";
					$export .= $total_ros.",";
					$export .= $first_entry.",";
					$export .= $last_entry.",";
					$export .= $dollars_ro.",";
					$export .= $total_labor.",";
					$export .= $total_parts.",";
					$export .= $total_lp.",";
					$export .= $rep_days."\n";
				}
			}
			$html .='
							</tbody>		
						</table>	
					</div>
				</div> <!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		// Save $export as SESSION var
		$_SESSION['export_dealer_summary'] = $export;
		
		return $html;
	}
}