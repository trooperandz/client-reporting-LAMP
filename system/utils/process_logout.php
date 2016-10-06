<?php
/**
 * File: process_logout.php
 * Purpose: Unset all SESSION vars for user logout
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description							by
 *   05/05/2016		Initial design & coding	    		Matt Holland
 */

// Include config file
include_once '../config/init.inc.php';

	/* Unset all session variables for user logout.
	 * This file was written as a stand-alone file instead of being included 
	 * in process_ajax.inc.php because if it were included in the process_ajax file,
	 * and the user SESSION had timed out, a 'You are no longer logged in' message would
	 * appear and the system would not redirect to the main login page like it should.
	**/
	
	// Destroy the session
	session_destroy();
	
	// Return to login page
	die(header('Location: ../../index.php'));
?>