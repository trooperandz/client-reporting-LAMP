<?php
/**
 * Program: class.ServicesInfo.inc.php
 * Created: 03/01/2016 by Matt Holland
 * Purpose: Retrieve array of service info from services table
 * Methods: 
 * Updates:
 */

Class ServicesInfo extends DB_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}
	
	// Get all service items from services table
	public function getServiceInfo($sort_welr, $sort_metrics) {
		// Originally stored as SESSION['svc_list'] but then removed due to different sort parameters

		// Query services table to retrieve values and labels for all checkboxes
		$sql = "SELECT serviceID, servicedescription, service_nickname, trend_nickname, servicelevel FROM services
				WHERE welr_svc = 1";
				
		if($sort_welr) {
			$sql .= " ORDER BY servicesort_welr ASC ";
		}
		
		if($sort_metrics) {
			$sql .=" ORDER BY servicesort_metrics ASC ";
		}
			
		$result = $this->dbo->query($sql);
		if (!$result) {
			$_SESSION['error'][] = 'A program error has occurred.  Please see administrator.';
			return false;
		} else {
			$rows = $result->num_rows;
			$svc_info = array();
			$svc_level = array();
			$index = 0;
			while ($value = $result->fetch_assoc()) {
				$svc_info[$index]['serviceID'] = $value['serviceID'];
				$svc_info[$index]['servicedescription'] = $value['servicedescription'];
				$svc_info[$index]['service_nickname'] = $value['service_nickname'];
				$svc_info[$index]['trend_nickname'] = $value['trend_nickname'];
				$svc_info[$index]['servicelevel'] = $value['servicelevel'];
				$svc_level[$index] = $value['servicelevel'];
				$index += 1;
			}
			$_SESSION['svc_list'] = array('svc_info'=>$svc_info, 'svc_level'=>$svc_level);
			return array('svc_info'=>$svc_info, 'svc_level'=>$svc_level);
		}	
	}
	
	public function testSvcQuery() {
		/* Test servicerendered_welr query search results
		 * 1 = LOF, 2 = Rotate, 3 = Wipers, 4 = Bulbs, 5 = Other, 6 = Air F, 7 = Cab F, 8 = Battery, 11 = Repl T, 12 = T Rep
		 * 13 = T Bal, 14 = Wheel Align, 15 = Drive B, 16 = BPads & R, 18 = Trans, 19 = Coolant, 21 = Spark, 22 = Other Fluid
		 * 25 = State Insp, 27 = Warranty, 28 = Recall, 29 = Differential, 30 = Br Fluid, 33 = Mech Rep, 34 = Other Svc
		**/

// The following two queries will return ros with LOF, Rotate(decline), Wipers, Air Filter, and no Cab Filter.  Success!  Returns RO# 29396

// The following query will create a comma-delimitted list of RO numbers that have services that the user chose to exclude. To be included in next query for NOT IN statement
$sql = "SELECT ronumber FROM servicerendered_welr
		WHERE dealerID = 129 AND serviceID IN(16)";
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
		//return $ro_string;

/* The following query returns services (with associated addsvc and decsvc) that the user specifies less $ro_string from above
 * Nested SELECT statements are necessary for partitioning out services with their associated addsvc and decsvc values
**/
/* Self inner joins alternative test 

$sql = "SELECT a.ronumber FROM servicerendered_welr a
		INNER JOIN servicerendered_welr b ON (a.dealerID = b.dealerID AND b.serviceID = 1)
		WHERE a.dealerID = 129
		GROUP BY a.ronumber
		ORDER BY a.ronumber";

/*
$sql = "SELECT a.ronumber FROM servicerendered_welr a
		INNER JOIN servicerendered_welr b ON (a.dealerID = b.dealerID AND b.serviceID = 1 AND b.addsvc = 0 and b.decsvc = 0)
		INNER JOIN servicerendered_welr c ON (b.dealerID = c.dealerID AND c.serviceID = 2 AND c.addsvc = 0 and c.decsvc = 1)
		INNER JOIN servicerendered_welr d ON (c.dealerID = d.dealerID AND d.serviceID = 3 AND d.addsvc = 0 and d.decsvc = 0)
		INNER JOIN servicerendered_welr e ON (d.dealerID = e.dealerID AND e.serviceID = 7 AND e.addsvc = 0 and e.decsvc = 0)
		WHERE a.dealerID = 129
		GROUP BY a.ronumber
		ORDER BY a.ronumber";
*/

/* Nested selects - this works */
$sql = "SELECT a.ronumber FROM servicerendered_welr a
		NATURAL JOIN services b
		WHERE a.dealerID = 129 AND a.ronumber IN
			(SELECT ronumber FROM servicerendered_welr WHERE serviceID = 1 AND ronumber IN
				(SELECT ronumber FROM servicerendered_welr WHERE serviceID = 2 AND addsvc = 0 and decsvc = 1 AND ronumber IN
					(SELECT ronumber FROM servicerendered_welr WHERE serviceID = 3 AND ronumber IN
						(SELECT ronumber FROM servicerendered_welr WHERE serviceID = 7)
					)
				) 
			)
		AND a.ronumber NOT IN($ro_string)				
		GROUP BY a.ronumber
		ORDER By b.servicesort_metrics ";
			$result2 = $this->dbo->query($sql);
			if (!$result2) {
				return 'Services query error. See administrator: '.$this->dbo->error;
			} else {
				$stuff = 0;
				$rows2 = $result2->num_rows;
				while($item = $result2->fetch_assoc()) {
					// Build services data array.  Test successful.
					$svc[$stuff]['ronumber'] = $item['ronumber'];
					//$svc[$stuff]['servicedescription'] = $item['servicedescription'];
					//$svc[$stuff]['addsvc'] = $item['addsvc'];
					//$svc[$stuff]['decsvc'] = $item['decsvc'];
					$stuff += 1;
				}
			}
		return var_dump($svc);
	}
}
?>