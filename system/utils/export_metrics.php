<?php
/**
 * File: export_metrics.php
 * Purpose: Return most current creation of metrics tables
 * History:
 *   Date			Description									by
 *   04/12/2016		Initial design & coding	    				Matt Holland
 */

// Start session for access to SESSION vars
session_start();

// Download the file
$filename = 'MetricsExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);

/* $_SESSION['export_metrics'] could be set by metrics trending or other metrics views.
 * If set by trending, will not be an array.
 * If set by other metrics, will be an array of two items (L1 and L2_3 table).
**/
if (is_array($_SESSION['export_metrics'])) {
	foreach ($_SESSION['export_metrics'] as $export) {
		echo $export;
	}
	echo $_SESSION['export_labor_parts'];
} else {
	echo $_SESSION['export_metrics'];
}

exit;
?>