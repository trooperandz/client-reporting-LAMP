<?php
/**
 * File: export_dealer_summary.php
 * Purpose: Return most current creation of dealer reporting summary table
 * History:
 *   Date			Description									by
 *   04/20/2016		Initial design & coding	    				Matt Holland
 */

// Start session for access to SESSION vars
session_start();

// Download the file
$filename = 'DealerSummaryExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $_SESSION['export_dealer_summary'];
exit;
?>