<?php
/**
 * File: process_ajax.inc.php
 * Purpose: Process ajax calls, use echo to return output
 * Similar to process.inc.php
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description							by
 *   05/05/2016		Initial design & coding	    		Matt Holland
 */

// Include config file
include_once '../config/init.inc.php';

	// Instantiate Admin object
	$obj = new Admin($dbo=null);
	
	// Capture POST values
	$array = array('username'=>$_POST['username'], 'password'=>$_POST['password'], 'dealercode'=>$_POST['dealercode']);
	
	// Process user login.  Die to correct page
	if($obj->processLoginForm($array)) {
		// Login successful.  Proceed to main program page
		die(header('Location: ../../index_test.php'));
	} else {
		// Return to login screen and show errors
		die(header('Location: ../../'.INDEX_SHORT));
	}
?>