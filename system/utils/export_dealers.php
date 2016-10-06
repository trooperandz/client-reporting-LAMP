<?php
/**
 * File: export_dealers.php
 * Purpose: Return most current creation of dealer data
 * History:
 *   Date			Description									by
 *   05/31/2016		Initial design & coding	    				Matt Holland
 */

// Start session for access to SESSION vars
session_start();

// Download the file
$filename = 'DealersExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $_SESSION['export_dealer_data'];
exit;
?>