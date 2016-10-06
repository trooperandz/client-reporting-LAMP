<?php
/**
 * File: export_stats.php
 * Purpose: Return most current creation of stats
 * History:
 *   Date			Description									by
 *   04/25/2016		Initial design & coding	    				Matt Holland
 */

// Start session for access to SESSION vars
session_start();

// Download the file
$filename = 'StatsExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $_SESSION['export_stats'];
exit;
?>