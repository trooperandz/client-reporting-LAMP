<?php
/**
 * File: export_ros.php
 * Purpose: Process ajax calls, use echo to return output
 * Similar to process.inc.php
 * PHP version 5.5.29
 * @author   Matthew Holland
 * 
 * History:
 *   Date			Description									by
 *   04/01/2016		Initial design & coding	    				Matt Holland
 */

// Include config file
//include_once '../config/init.inc.php';
session_start();

// Download the file
$filename = 'RepairOrderExport.csv';
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);
echo $_SESSION['export_ros'];
exit;
?>