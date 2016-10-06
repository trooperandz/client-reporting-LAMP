<?php
/**
 * File: getFile.php
 * Purpose: Display pdf files
 * History:
 *   Date			Description									by
 *   06/07/2016		Initial design & coding	    				Matt Holland
 */

// Require the initialization file
require_once('../config/init.inc.php');

$obj = new Documents($dbo=null);
$obj->viewFile(array('view_doc_id'=>$_POST['view_doc_id']));
exit;
?>