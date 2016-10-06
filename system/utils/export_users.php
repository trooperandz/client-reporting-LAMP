<?php
/**
 * File: export_users.php
 * Purpose: Return most current creation of user data
 * History:
 *   Date			Description									by
 *   05/31/2016		Initial design & coding	    				Matt Holland
 */

// Start session for access to SESSION vars
session_start();

// Download the file
$filename = 'UsersExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $_SESSION['export_user_data'];
exit;
?>