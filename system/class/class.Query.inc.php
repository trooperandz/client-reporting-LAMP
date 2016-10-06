<?php
/**
 * Program: class.Query.inc.php
 * Created: 04/22/2016 by Matt Holland
 * Purpose: Build dynamic queries (and their params) based on user search input
 * Methods: getQueryStmtParams(): Build metrics & stats dynamic query string and params array for user-selected values
 			getUserQueryStmtParams(): Build user dynamic query string based on user-selected params
 * Updates:
 */

Class Query extends PDO_Connect  {
	
	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);
	}
	
	public static function getQueryStmtParams($array) {
		// Set $stmt and $params for easy access
		$stmt = $array['stmt'];
		$params = $array['params'];
		
		// Add remaining items to query statement and params
		if($array['dealer_id']) {
			$dealer_id = $array['dealer_id'];
			$params[] = $dealer_id;
			$stmt[] = "a.dealerID = ? ";
			$export .= "\n Dealer: ".$_SESSION["dealer_code"];
		}

		// If date_range == true, add BETWEEN statement for correct RO count
		if($array['date_range']) {
			if($array['stats_month']) {
				$params[] = $_SESSION['stats_month_date1_sql'];
				$params[] = $_SESSION['stats_month_date2_sql'];
				$export .= "\n Date Range: ".$_SESSION['stats_month_date1_pres'].' through '.$_SESSION['stats_month_date2_pres'];
			} elseif ($array['metrics_month']) {
				$params[] = $_SESSION['metrics_month_date1_sql'];
				$params[] = $_SESSION['metrics_month_date2_sql'];
				$export .= "\n Date Range: ".$_SESSION['metrics_month_date1_pres'].' through '.$_SESSION['metrics_month_date2_pres'];
			} elseif ($array['metrics_search'] || $array['stats_search'] || $array['metrics_trends']) {
				$params[] = $array['date1_sql'];
				$params[] = $array['date2_sql'];
				$export .= "\n Date Range: ".$array['date1_sql'].' through '.$array['date2_sql'];
			}
			$stmt[] = "a.ro_date BETWEEN ? AND ? ";
		}

		if($array['advisor_id']) {
			$advisor_id = $array['advisor_id'];
			$params[] = $advisor_id;
			$stmt[] = "a.userID = ? ";
			$export .= "\n Advisor: ".$array['advisor_name'];
		}
		
		if($array['district_id']) {
			$district_id = $array['district_id'];
			$params[] = $district_id;
			$stmt[] = "b.district_ID = ? ";
			$export .= "\n District: ".$array['district_name'];
		}
		
		if($array['area_id']) {
			$area_id = $array['area_id'];
			$params[] = $area_id;
			$stmt[] = "b.area_ID = ? ";
			$export .= "\n Area: ".$array['area_name'];
		}
		
		if($array['region_id']) {
			$region_id = $array['region_id'];
			$params[] = $region_id;
			$stmt[] = "b.regionID = ? ";
			$export .= "\n Region: ".$array['region_name'];
		}
		
		// $array['dealer_group'] is an array of dealer ids.  Build dynamic $stmt and run foreach for every id param value
		if($array['dealer_group']) {
			$dealer_group = $array['dealer_group'];
			foreach ($dealer_group as $id) {
				$params[] = $id;
			}
			    //" AND a.serviceID IN(".rtrim(str_repeat('?,', count($svc_ids[$key])), ',').") ";
			$stmt[] = "a.dealerID IN(".rtrim(str_repeat('?,', count($dealer_group)), ',').") ";
		}

		// Build statement dynamically based on size of array
		$query = "";
		for($i=0; $i<count($stmt); $i++) {
			if(count($stmt) >= 1 && count($stmt) < 3) {
				if($i == 0) {
					$query .= $stmt[$i];
				} else {
					$query .= " WHERE ".$stmt[$i];
				}
			} elseif(count($stmt) > 1 && count($stmt) > 2) {
				if($i == 0) {
					$query .= $stmt[$i];
				} elseif ($i == 1) {
					$query .= " WHERE ".$stmt[$i];
				} else {
					$query .= " AND ".$stmt[$i];
				}
			}
		}
		// Return $query and $params
		return array('stmt'=>$query, 'params'=>$params, 'export_data'=>$export);
	}
	
	// Function for building user info queries.  Need this for building different user tables: dealer, manuf, sos user types
	public static function getUserQueryStmtParams($array) {
	
		// Set $stmt and $params for easy access
		$stmt = $array['stmt'];
		$params = $array['params'];
		
		/* Add remaining items to query statement and params. WHERE & AND operators will be added in loop later on */
		
		// This param is used for editing selected user
		if($array['user_id']) {
			$stmt[] = " a.user_id = ? ";
			$params[] = $array['user_id'];
		}
		
		// This param is used for the login		 
		if($array['user_name']) {
			$stmt[] = " a.user_name = ? ";
			$params[] = $array['user_name'];
		}
		
		// This will be used for verifying user password reset
		if($array['user_email']) {
			$stmt[] = " a.user_email = ? ";
			$params[] = $array['user_email'];
		}
		
		// This will be used for getting specific dealer users
		if($array['dealer_id']) {
			$stmt[] = " a.user_dealer_id = ? ";
			$params[] = $array['dealer_id'];
		}
		
		// This will be used for getting list of all dealer users, SOS or manuf users
		if($array['user_type_id']) {
			$stmt[] = " a.user_type_id = ? ";
			$params[] = $array['user_type_id'];
		}
		
		// This will be used for getting admin users (email addresses to send notifications of user setups) 
		// System is set up right now to send emails to all admin users
		if($array['user_admin']) {
			$stmt[] = " a.user_admin = ? ";
			$params[] = $array['user_admin'];
		}
		
		if($array['user_active']) {
			$stmt[] = " a.user_active = ? ";
			$params[] = $array['user_active'];
		}
		
		// Build statement dynamically based on size of array
		$query = "";
		for($i=0; $i<count($stmt); $i++) {
			if(count($stmt) >= 1 && count($stmt) < 3) {
				if($i == 0) {
					$query .= $stmt[$i];
				} else {
					$query .= " WHERE ".$stmt[$i];
				}
			} elseif(count($stmt) > 1 && count($stmt) > 2) {
				if($i == 0) {
					$query .= $stmt[$i];
				} elseif ($i == 1) {
					$query .= " WHERE ".$stmt[$i];
				} else {
					$query .= " AND ".$stmt[$i];
				}
			}
		}
		// Return $query and $params
		return array('stmt'=>$query, 'params'=>$params);
	}
}