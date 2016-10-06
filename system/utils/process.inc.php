<?php
/**
 * File: process_ajax.inc.php
 * Purpose: Process ajax calls, use echo to return output
 * Similar to process.inc.php
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description									by
 *   09/24/2015		Initial design & coding	    				Matt Holland
 *	 02/12/2016		Adapted to new Creative Tim system			Matt Holland
 *   02/12/2016		Reduced complexity by adding updated		Matt Holland
 *					init.inc.php file and removing the 
 *					__autoload function (already in init file)
 */

// Include config file
include_once '../config/init.inc.php';

// Make sure that user is logged in before any actions occur.  If not, return 'error_login'
if(verifyUserLogin()) {

	// Create a lookup array for form actions
	$actions = array(
				'export_ros' => array(
					'object' => 'Welr',
					'header' => 'Location: ../../index_test.php'
				)
			  );
			
	// Make sure the requested action exists in the lookup array
	if (isset($actions[$_POST['action']])) {
		$use_array = $actions[$_POST['action']];
		$obj = new $use_array['object']($dbo=null);
		
		if($_POST['action'] == 'export_ros') {
			//die('entered action post');
			// Download the file
			$filename = 'RepairOrderExport.csv';
			header('Content-type: application/csv');
			header('Content-Disposition: attachment; filename='.$filename);
			echo $_SESSION['export_ros'];
			exit;
		}
	}	
} else {
	die("entered else die");
	die(header("Location: ../../index_test.php"));
}
exit;
?>