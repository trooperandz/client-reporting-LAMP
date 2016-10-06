<?php
/**
 * Program: class.DateTimeCalc.inc.php
 * Created: 04/26/2016 by Matt Holland
 * Purpose: Provide functions for Date and Time calculations and conversions
 * Methods: 
 * Updates:
 */

Class DateTimeCalc  {
	
	// Provide sequence of month ranges given starting and ending dates ($array['date1_sql'], $array['date2_sql'])
	public static function getMonthRanges($array) {
	
		// Establish easy access to beginning and ending date
		$date1_sql = $array['date1_sql'];
		$date2_sql = $array['date2_sql'];
		
		/* Now use DateTime diff function to return number of months between two posted dates (result in format 'm')
		 * The original formula ($months = $date1->diff($date2)->m did not work across multiple years.
		 *
		**/
		$date1 = new DateTime($date1_sql);
		$date2 = new DateTime($date2_sql);
		$months = ($date1->diff($date2)->m) + ($date1->diff($date2)->y*12);
		//$months = $date1->diff($date2)->m;
		//echo 'month diff result: '.$months.'<br>';
		
		// Now run loop to construct succession of beginning and ending dates. Add 1 to $months, as diff function above always returns one less
		$date = $date1_sql;
		for($m=0; $m<$months+1; $m++) {
			if($m == 0) {
				$date1 = $date1_sql;
			} else {
				$date1 = $date; // This was est at the end of the loop
			}
			//echo '$date1: '.$date1.'<br>';
			// Add $date1 to array
			$date_array[] = $date1;
			
			// Now figure out second date. Check to make sure that result is less than 2nd POST date.  If not, set = 2nd POST date
			$date_obj = new DateTime($date1);
			$date2 = $date_obj->format("Y-m-t");
			if($date2 > $date2_sql) {
				$date2 = $date2_sql;
			} 
			
			// Add $date2 to array
			$date_array[] = $date2;
			
			// Now get the first of the next month
			$date = strtotime(date("Y-m-d", strtotime($date2)) . " +1 day");
			$date = date("Y-m-d", $date);
		}
		return $date_array;
	}
}