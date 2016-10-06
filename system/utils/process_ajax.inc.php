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

/* This is for testing.  Try to put non-session AJAX processes here, before the verifyUserLoginAjax() code */
if(isset($_POST['no_session'])) {
	// Create a lookup array for form actions
	$actions = array(
				'get_login_form' => array(
					'object'  => 'Admin',
					'method1' => 'getLoginForm'
				),
				'forgot_pass_link' => array(
					'object'  => 'Admin',
					'method1' => 'getLoginForm',
					'method2' => 'getSuccessMsg'
				),
				'send_reset_link' => array(
					'object'  => 'Admin',
					'method1' => 'emailPassResetLink',
					'method2' => 'getLoginForm'
				),
				'reset_user_pass' => array(
					'object'  => 'Admin',
					'method1' => 'validateResetPassData',
					'method2' => 'getLoginForm'
				)
			);
	if (isset($actions[$_POST['action']])) {
		$use_array = $actions[$_POST['action']];
		$obj = new $use_array['object']($dbo=null);
		
		if($_POST['action'] == 'forgot_pass_link') {
			// If there was no form load error, update content with new form.  Else return original login form with error msg at bottom of form
			if($form = $obj->$use_array['method1'](array('welcome_msg'=>'Please Enter Your Email Address Below:', 'forgot_pass_link'=>true, 'a_id'=>'return_loginform_link'))) {
				echo $form;
			} else {
				echo $obj->$use_array['method1'](array('welcome_msg'=>'Please Enter Your Login Details Below:', 'get_login_form'=>true)).
					 '<p> There was an error processing your request. Please see the administrator. </p>';
			}
		}
		
		if($_POST['action'] == 'get_login_form') {
			echo $obj->$use_array['method1'](array('welcome_msg'=>'Please Enter Your Login Details Below:', 'a_id'=>'forgot_pass_link', 'get_login_form'=>true));
		}
		
		if($_POST['action'] == 'send_reset_link') {
			//echo 'email post: '.$_POST['user_email'];
			// Run emailPassResetLink method. Trim email whitespace to ensure no spaces cause error. If true, return success msg
			if($obj->$use_array['method1'](array('user_email'=>trim($_POST['user_email'])))) {
				echo $obj->$use_array['method2'](array('email_resetlink_success'=>true, 'a_id'=>'return_loginform_link', 'reset_msg'=>'Thank you. A reset password link has been emailed to: '.$_POST['user_email']));
			} else {
				echo $obj->$use_array['method2'](array('email_resetlink_success'=> true, 'a_id'=>'return_loginform_link', 'reset_msg'=>'There was an error processing your password reset.  Please see the administrator.'));
			}
		}
		
		if($_POST['action'] == 'reset_user_pass') {
			/* If reset password is successful, display success message with 'Return to Login Form' link. 
			 * Make sure that Url $_GET['user'] value is removed so that correct form is displayed
			 * Else show error.
			**/
			echo 'hash: '.$_SESSION['hash'];
			if($obj->$use_array['method1'](array('pass1'=>$_POST['pass1'], 'pass2'=>$_POST['pass2'], 'user_email'=>$_POST['user_email'], 'hash'=>$_SESSION['hash']))) {
				unset($_SESSION['hash']); // Unset $_SESSION['hash']
				echo $obj->$use_array['method2'](array('email_resetlink_success'=>true, 'reset_msg'=>'Your password has been successfully reset. A confirmation has been emailed to: '.$_POST['user_email']));	
			} else {
				echo $obj->$use_array['method2'](array('email_resetlink_success'=>true, 'reset_msg'=>'There was an error processing your request.<br>  Please try again or contact the administrator if the problem persists.'));
			}
		}
	}
// Make sure that program exists before any further code could be executed accidentally
exit;
}

/* Make sure that user is logged in before any actions occur. If not, return 'error_login'
 * This is needed not only for security, but also for user feedback.  If a user clicks a link to 
 * run a process, and they are not logged in, they need to be shown the 'error_login' message so 
 * that they know they need to log in again.
**/
if(verifyUserLoginAjax()) {
	
	// Create a lookup array for form actions
	$actions = array(
				'ro_entry' => array(
					'object' => 'Welr',
					'method' => 'processRoEntry'
				),
				'change_advisor' => array(
					'object' => 'Welr',
					'method' => 'processAdvisorSelection'
				),
				'update_ro_form' => array(
					'object' => 'Welr',
					'method1' => 'getPageHeading',
					'method2' => 'getRoEntryForm',
					'method3' => 'getRoEntryTable'
				),
				'view_ros_month' => array(
					'object' => 'Welr',
					'method1' => 'getPageHeading',
					'method2' => 'getRoEntryTable'
				),
				'view_ros_all' => array(
					'object' => 'Welr',
					'method1' => 'getPageHeading',
					'method2' => 'getRoEntryTable'
				),
				'enter_ros' => array(
					'object'  => 'Welr',
					'method1' => 'getPageHeading',
					'method2' => 'getAdvisorDropdown',
					'method3' => 'getRoEntryForm',
					'method4' => 'getRoEntryTable'
				),
				'ro_search' => array(
					'object'  => 'Welr',
					'method1' => 'getPageHeading',
					'method2' => 'getRoEntryTable'
				),
				'view_metrics_month' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsTable',
					'method3'=> 'getLaborPartsTable'
				),
				'view_metrics_all' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsTable',
					'method3'=> 'getLaborPartsTable'
				),
				'metrics_dlr_comp' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsDlrCompTable'
				),
				'metrics_search' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsTable',
					'method3'=> 'getLaborPartsTable'
				),
				'metrics_trend' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsTrendTable'
				),
				'view_stats_month' => array(
					'object' => 'Stats',
					'method1'=> 'getPageHeading',
					'method2'=> 'getServiceLevelTable',
					'method3'=> 'getLofTable',
					'method4'=> 'getVehicleTable',
					'method5'=> 'getYearModelTable',
					'method6'=> 'getMileageTable',
					'method7'=> 'getRoTrendTable',
					//'method8'=> 'getRoEntryStatsTable'
				),
				'view_stats_all' => array(
					'object' => 'Stats',
					'method1'=> 'getPageHeading',
					'method2'=> 'getServiceLevelTable',
					'method3'=> 'getLofTable',
					'method4'=> 'getVehicleTable',
					'method5'=> 'getYearModelTable',
					'method6'=> 'getMileageTable',
					'method7'=> 'getRoTrendTable',
					//'method8'=> 'getRoEntryStatsTable'
				),
				'stats_search' => array(
					'object' => 'Stats',
					'method1'=> 'getPageHeading',
					'method2'=> 'getServiceLevelTable',
					'method3'=> 'getLofTable',
					'method4'=> 'getVehicleTable',
					'method5'=> 'getYearModelTable',
					'method6'=> 'getMileageTable',
					'method7'=> 'getRoTrendTable',
					//'method8'=> 'getRoEntryStatsTable'
				),
				'dealer_summary' => array(
					'object' => 'SurveysSummary',
					'method1'=> 'getPageHeading',
					'method2'=> 'getDealerSummaryTable'
				),
				'dealer_summary_select' => array(
					'object' => 'Metrics',
					'method1'=> 'getPageHeading',
					'method2'=> 'getMetricsTable',
					'method3'=> 'getLaborPartsTable'
				),
				'view_dealer_list_all' => array(
					'object' => 'DealerInfo',
					'method1'=> 'getPageHeading',
					'method2'=> 'getDealerListingTable'
				),
				'get_dealer_add_form' => array(
					'object' => 'DealerInfo',
					'method1'=> 'getPageHeading',
					'method2'=> 'getAddDealerTable'
				),
				'add_dealer_row' => array(
					'object' => 'DealerInfo',
					'method1'=> 'getAddDealerTable'
				),
				'add_dealers' => array(
					'object' => 'DealerInfo',
					'method1'=> 'processAddNewDealers',
					'method2'=> 'getPageHeading',
					'method3'=> 'getAddDealerTable',
					'method4'=> 'getSuccessMsg'
				),
				'get_user_request_form' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getUserRequestTable'
				),
				'add_user_req_row' => array(
					'object' => 'Admin',
					'method1'=> 'addUserRequestRow'
				),
				'get_dealer_info_js' => array(
					'object' => 'DealerInfo',
					'method1'=> 'getDealerListing'
				),
				'process_user_setup_request' => array(
					'object' => 'Admin',
					'method1'=> 'processUserSetupRequest',
					'method2'=> 'getPageHeading',
					'method3'=> 'getUserRequestTable',
					'method4'=> 'getSuccessMsg'
				),
				'view_user_setup_requests' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getUserRequestTable'
				),
				'approve_user_setup_requests' => array(
					'object' => 'Admin',
					'method1'=> 'processUserSetupApprovals',
					'method2'=> 'getPageHeading',
					'method3'=> 'getUserRequestTable',
					'method4'=> 'getSuccessMsg'
				),
				'add_new_user_table' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getAddUserTable'
				),
				'add_new_user_row' => array(
					'object' => 'Admin',
					'method1'=> 'getAddUserTable'
				),
				'add_new_users' => array(
					'object' => 'Admin',
					'method1'=> 'processAddNewUsers',
					'method2'=> 'getPageHeading',
					'method3'=> 'getAddUserTable',
					'method4'=> 'getSuccessMsg'
				),
				'check_username_dupe' => array(
					'object' => 'Admin',
					'method1'=> 'checkUsernameDupe'
				),
				'view_dealer_users' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getUserTable'
				),
				'view_sos_users' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getUserTable'
				),
				'view_manuf_users' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getUserTable'
				),
				'table_user_edit_select' => array(
					'object' => 'Admin',
					'method1'=> 'getPageHeading',
					'method2'=> 'getAddUserTable'
				),
				'table_dealer_edit_select' => array(
					'object' => 'DealerInfo',
					'method1'=> 'getPageHeading',
					'method2'=> 'getAddDealerTable'
				),
				'add_doc_link' => array(
					'object' => 'Documents',
					'method1'=> 'getPageHeading',
					'method2'=> 'getFileUploadForm'
				),
				'file_submit' => array(
					'object' => 'Documents',
					'method1'=> 'processFileUpload',
					'method2'=> 'getPageHeading',
					'method3'=> 'getFileUploadForm',
					'method4'=> 'getSuccessMsg'
				),
				'view_doc_link' => array(
					'object' => 'Documents',
					'method1'=> 'getDocTable',
					'method2'=> 'getPageHeading'
				),
				'view_doc_table' => array(
					'object' => 'Documents',
					'method1'=> 'getDocTable',
					'method2'=> 'getPageHeading'
				),
				'table_doc_select' => array(
					'object' => 'Documents',
					'method1'=> 'viewFile'
				),
				'delete_doc' => array(
					'object' => 'Documents',
					'method1'=> 'deleteDoc',
					'method2'=> 'getDocTable',
					'method3'=> 'getPageHeading',
					'method4'=> 'getSuccessMsg'
				),
				'edit_doc_form' => array(
					'object' => 'Documents',
					'method1'=> 'getPageHeading',
					'method2'=> 'getFileUploadForm'
				),
				'file_update_submit' => array(
					'object' => 'Documents',
					'method1'=> 'updateDoc',
					'method2'=> 'getDocTable',
					'method3'=> 'getPageHeading',
					'method4'=> 'getSuccessMsg'
				),
				'contact_us_link' => array(
					'object' => 'ContactUs',
					'method1'=> 'getPageHeading',
					'method2'=> 'getContactForm'
				),
				'contact_us_submit' => array(
					'object' => 'ContactUs',
					'method1'=> 'processContactForm',
					'method2'=> 'getPageHeading',
					'method3'=> 'getSuccessMsg',
					'method4'=> 'getContactForm'
				)
			);
			
	// Make sure the requested action exists in the lookup array
	if (isset($actions[$_POST['action']])) {
		$use_array = $actions[$_POST['action']];
		$obj = new $use_array['object']($dbo=null);
		
		if($_POST['action'] == 'ro_entry') {
			// Prevent undefined index notices from service arrays
			$svc_reg = (isset($_POST['svc_reg'])) ? $_POST['svc_reg'] : null;
			$svc_add = (isset($_POST['svc_add'])) ? $_POST['svc_add'] : null;
			$svc_dec = (isset($_POST['svc_dec'])) ? $_POST['svc_dec'] : null;
			
			$array = array('submit_name'=>$_POST['submit_name'], 'ronumber'=>$_POST['ronumber'], 'ro_date'=>$_POST['ro_date'], 
						  'yearmodel'=>$_POST['yearmodel'], 'mileage'=>$_POST['mileage'], 'vehicle'=>$_POST['vehicle'], 'labor'=>$_POST['labor'], 
						  'parts'=>$_POST['parts'], 'dealer_id'=>$_SESSION['dealer_id'], 'comment'=>$_POST['comment'], 'svc_reg'=>$svc_reg,
						  'svc_add'=>$svc_add, 'svc_dec'=>$svc_dec, 'svc_hidden'=>$_POST['svc_hidden'], 'search_params'=>false);
			echo $obj->$use_array['method']($array);
		}
		
		if($_POST['action'] == 'update_ro_form') {
			// Set $_SESSION['update_ronumber'] for future checking of ro update form. Do not forget to unset later
			$_SESSION['update_ronumber'] = $_POST['ro_number'];
			// Set $_SESSION['update_ro_id'] for updating of repairorder_welr record. Do not forget to unset later
			$_SESSION['update_ro_id'] = $_POST['ro_id'];
			
			$array = array('page_title'=>'Update Order', 'ro_count'=>true, 'entry_form'=>true, 'update_form'=>true,
						   'dealer_id'=>$_SESSION['dealer_id'], 'dealer_code'=>$_SESSION['dealer_code'],
						   'print-icon'=>false, 'export-icon'=>false);
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($update_form = true, $update_ro_id = $_POST['ro_id'], $search_params = false).
				 $obj->$use_array['method3'](array('entry_form' => true, 'date_range' => false, 'search_params' => false));
		}
		
		if($_POST['action'] == 'view_ros_month') {
			// Set dates to current month to date
			$_SESSION['ro_date_range1'] = date("Y-m-01");
			$_SESSION['ro_date_range2'] = date("Y-m-d");
			
			$date1 = date("m/d/y", strtotime(date("Y-m-01")));
			$date2 = date("m/d/y", strtotime(date("Y-m-d")));
			
			$array = array('page_title'=>'Repair Order Listing ('.$date1.' - '.$date2.')', 'ro_count'=>true, 'entry_form'=>false, 
						   'update_form'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 'dealer_code'=>$_SESSION['dealer_code'], 
						   'date_range'=>true, 'print-icon'=>true, 'export-icon'=>true);
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2'](array('entry_form' => false, 'date_range' => true, 'search_params' => false));
		}
		
		if($_POST['action'] == 'view_ros_all') {
			$array = array('page_title'=>'Repair Order Listing (All History)', 'ro_count'=>true, 'entry_form'=>false, 
						   'update_form'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 'dealer_code'=>$_SESSION['dealer_code'],
						   'date_range'=>false, 'search_params'=>false, 'print-icon'=>true, 'export-icon'=>true);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2'](array('entry_form' => false, 'date_range' => false, 'search_params' => false));
		}
		
		if($_POST['action'] == 'enter_ros') {
			$array = array('page_title'=>'Enter Repair Orders', 'ro_count'=>true, 'entry_form'=>true, 'update_form'=>false,
						   'dealer_id'=>$_SESSION['dealer_id'], 'dealer_code'=>$_SESSION['dealer_code'],
						   'print-icon'=>false, 'export-icon'=>false);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']().
				 $obj->$use_array['method3']($update_form = false, $update_ro_id = null, $search_params = false).
				 $obj->$use_array['method4'](array('entry_form' => true, 'date_range' => false, 'search_params' => false));
		}
		
		if($_POST['action'] == 'ro_search') {
			$array = array('page_title'=>'Repair Order Search Results', 'ro_count'=>false, 'entry_form'=>false, 'update_form'=>false,
						   'dealer_id'=>$_SESSION['dealer_id'], 'dealer_code'=>$_SESSION['dealer_code'],
						   'print-icon'=>true, 'export-icon'=>true);
			echo $obj->$use_array['method1']($array).
			     $obj->$use_array['method2'](array('entry_form' => false, 'date_range' => false, 'search_params' => array('ro_params'=>$_POST['ro_params'], 'svc_reg'=>$_POST['svc_reg'], 'svc_add'=>$_POST['svc_add'], 'svc_dec'=>$_POST['svc_dec'], 'svc_exclude'=>$_POST['svc_exclude'])));
		}
		
		if($_POST['action'] == 'metrics_search') {
			/* Possible metrics search options include the following:
			 * dealer, dealer group, area, region, district, all dealers
			 * Leave these out of the original array, and then use foreach to add search items to array
			**/
			//echo 'action: '.$_POST['action'].'<br>';
			$metrics_params = json_decode($_POST['metrics_params'], true);
			
			$array = array('page_title'=>'View Metrics - ', 'title_info'=>'Filtered Results', 'ro_count'=>true, 
						   'metrics_table'=>'L1', 'metrics_month'=>false, 'metrics_search'=>true,
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
						  
			// Now add $metrics_params to $array for submission to class methods
			foreach($metrics_params as $key=>$value) {
				$array[$key] = $value;
			}
			
			// If dates were entered as search params, set $array['date_range'] = true, create sql-compatible format and add to $array for passing to methods
			if($array['date1_pres']) {
				$array['date_range'] = true;
				$date = new DateTime($array['date1_pres']);
				$array['date1_sql'] = $date->format("Y-m-d");
				$date = new DateTime($array['date2_pres']);
				$array['date2_sql'] = $date->format("Y-m-d");
			}
			
			// If only dates and/or date fields were entered, add 'Dealer: Name + Code' to search_feedback string so user knows which dealer the info pertains to
			if (( $array['date1_pres'] &&  $array['date2_pres'] &&  $array['advisor_id']) || 
			    ( $array['date1_pres'] &&  $array['date2_pres'] && !$array['advisor_id']) ||
			    (!$array['date1_pres'] && !$array['date2_pres'] &&  $array['advisor_id'])) {
				if (!$array['region_id'] && !$array['area_id'] && !$array['district_id'] && !$array['dealer_group']) {
					$array['search_feedback'] .= 'Dealer: '.$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')';
				}	
			}
			
			// Create $array copy and change value of 'metrics_table' so that correct L2_3 table data is generated
			$array2 = array();
			foreach($array as $key=>$value) {
				if($key == 'metrics_table') {
					$array2[$key] = 'L2_3';
				} else {
					$array2[$key] = $value;
				}
			}
			
			/* If dates only were selected (and no region, district, etc):
			 * Pass 'dealer_id' param SESSION var as default UNLESS
			 * 'View All Dealers' has been checked
			**/
			if(!$array['region_id'] && !$array['area_id'] && !$array['district_id'] && !$array['dealer_group']) {
				if (!$array['all_dealers_checkbox']) {
					$array['dealer_id'] = $_SESSION['dealer_id'];
				}
			}
			
			//echo 'array2 after metrics_table change: '.var_dump($array2);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method2']($array2);
		}
		
		if($_POST['action'] == 'metrics_dlr_comp') {
			/* Possible dealer comp filter options include the following:
			 * date range, dealer group
			 * Leave these out of the original array, and then use foreach to add search items to array
			**/
			$params = json_decode($_POST['params'], true);
			
			$array = array('page_title'=>'View Metrics - ','title_info'=>'Dealer Comparison Data', 'ro_count'=>false, 
						   'metrics_search'=>true, 'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
			
			// Now add $params to $array for submission to class methods
			foreach($params as $key=>$value) {
				$array[$key] = $value;
			}
			
			// If dates were entered as params, set $array['date_range'] = true, create sql-compatible format and add to $array for passing to methods
			if($array['date1_pres']) {
				$array['date_range'] = true;
				$date = new DateTime($array['date1_pres']);
				$array['date1_sql'] = $date->format("Y-m-d");
				$date = new DateTime($array['date2_pres']);
				$array['date2_sql'] = $date->format("Y-m-d");
			}
			
			//echo var_dump($obj->$use_array['method2']($array));
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array);
		}
			
		if($_POST['action'] == 'view_metrics_all') {
			$array = array('page_title'=>'View Metrics (All History) - ', 'title_info'=>$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')',
						   'ro_count'=>true, 'metrics_table'=>'L1', 'dealer_group'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 
						   'date_range'=>false, 'metrics_month'=>false, 'metrics_search'=>false, 'advisor_id'=>false, 
						   'district_id'=>false, 'area_id'=>false, 'region_id'=>false, 'search_feedback'=> 'Showing: All History',
						   'export_feedback'=> array('Dealer: '.$_SESSION['dealer_code'], 'Showing: All History'),
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
			// Create $array copy and change value of 'metrics_table' so that correct L2_3 table data is generated
			$array2 = array();
			foreach($array as $key=>$value) {
				if($key == 'metrics_table') {
					$array2[$key] = 'L2_3';
				} else {
					$array2[$key] = $value;
				}
			}
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method2']($array2);
		}				  
		
		if($_POST['action'] == 'view_metrics_month' || $_POST['action'] == 'dealer_summary_select') {
			// Set dates to month to date
			$_SESSION['metrics_month_date1_sql'] = date("Y-m-01");
			$_SESSION['metrics_month_date2_sql'] = date("Y-m-d");
			$_SESSION['metrics_month_date1_pres']= $date1 = date("m-01-y");
			$_SESSION['metrics_month_date2_pres']= $date2 = date("m-d-y");
			
			// If action was 'dealer_summary_select', change dealer SESSION vars
			if ($_POST['action'] == 'dealer_summary_select') {
				$_SESSION['dealer_id'] = $_POST['dealer_id'];
				$_SESSION['dealer_code'] = $_POST['dealer_code'];
				$_SESSION['dealer_name'] = $_POST['dealer_name'];
			}
			
			$array = array('page_title'=>'View Metrics (Month To Date) - ', 'title_info'=>$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')',
						   'ro_count'=>true, 'metrics_table'=>'L1', 'dealer_group'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 
						   'date_range'=>true, 'metrics_month'=>true, 'metrics_search'=>false, 'advisor_id'=>false, 
						   'district_id'=>false, 'area_id'=>false, 'region_id'=>false, 'search_feedback'=> 'Date Range: '.$date1.' through '.$date2,
						   'export_feedback'=> array('Dealer: '.$_SESSION['dealer_code'], 'Date Range: '.$date1.' through '.$date2),
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
						  
			// Create $array copy and change value of 'metrics_table' so that correct L2_3 table data is generated
			$array2 = array();
			foreach($array as $key=>$value) {
				if($key == 'metrics_table') {
					$array2[$key] = 'L2_3';
				} else {
					$array2[$key] = $value;
				}
			}
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method2']($array2);
		}
		
		if($_POST['action'] == 'metrics_trend') {
			/* Possible search options include the following:
			 * dealer group, area, region, district
			 * Leave these out of the original array, and then use foreach to add search items to array
			**/
			
			// Use json_decode to turn JS params into array
			$params = json_decode($_POST['params'], true);
			
			// Add the page title and make sure that misc $array params are set for success
			$array = array('page_title'=>'View Metrics - ', 'title_info'=>'Trending By Month', 'ro_count'=>false, 
						   'stats_month'=>false, 'stats_search'=>false, 'metrics_trends'=>true,
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
						  
			// Now add $params to $array for submission to class methods
			foreach($params as $key=>$value) {
				$array[$key] = $value;
			}
			
			// If dates were entered as search params, set $array['date_range'] = true, create sql-compatible format and add to $array for passing to methods
			if($array['date1_pres']) {
				$array['date_range'] = true;
				$date = new DateTime($array['date1_pres']);
				$array['date1_sql_user'] = $date->format("Y-m-d");
				$date = new DateTime($array['date2_pres']);
				$array['date2_sql_user'] = $date->format("Y-m-d");
			}
			
			/* If dates only were selected (and no region, district, dealer group etc):
			 * Pass 'dealer_id' param SESSION var as default UNLESS
			 * 'View All Dealers' has been checked
			**/
			if(!$array['region_id'] && !$array['area_id'] && !$array['district_id'] && !$array['dealer_group']) {
				if (!$array['all_dealers_checkbox']) {
					$array['dealer_id'] = $_SESSION['dealer_id'];
				}
			}
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array);
		}
		
		if($_POST['action'] == 'view_stats_month') {
			// Set dates to month to date
			//$date1 = $date->format("Y-m-d");
			$_SESSION['stats_month_date1_sql'] = date("Y-m-01");
			$_SESSION['stats_month_date2_sql'] = date("Y-m-d");
			$_SESSION['stats_month_date1_pres']= $date1 = date("m-01-y");
			$_SESSION['stats_month_date2_pres']= $date2 = date("m-d-y");
			
			$array = array('page_title'=>'View Statistics (Month To Date) - ', 'title_info'=>$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')',
						   'ro_count'=>true, 'dealer_group'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 
						   'date_range'=>true, 'stats_month'=>true, 'stats_search'=>false, 'advisor_id'=>false, 
						   'district_id'=>false, 'area_id'=>false, 'region_id'=>false, 'search_feedback'=> 'Date Range: '.$date1.' through '.$date2,
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method4']($array).
				 $obj->$use_array['method5']($array).
				 $obj->$use_array['method6']($array).
				 $obj->$use_array['method7']($array);
				 //$obj->$use_array['method8']($array);
		}
		
		if($_POST['action'] == 'view_stats_all') {
			$array = array('page_title'=>'View Statistics (All History) - ', 'title_info'=>$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')',
						   'ro_count'=>true, 'dealer_group'=>false, 'dealer_id'=>$_SESSION['dealer_id'], 
						   'date_range'=>false, 'stats_month'=>false, 'stats_search'=>false, 'advisor_id'=>false, 
						   'district_id'=>false, 'area_id'=>false, 'region_id'=>false, 'search_feedback'=> 'Showing: All History',
						   'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
			
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method4']($array).
				 $obj->$use_array['method5']($array).
				 $obj->$use_array['method6']($array).
				 $obj->$use_array['method7']($array);
				 //$obj->$use_array['method8']($array);
		}
		
		if($_POST['action'] == 'stats_search') {
			/* Possible search options include the following:
			 * dealer, dealer group, area, region, district
			 * Leave these out of the original array, and then use foreach to add search items to array
			**/
			//echo 'action: '.$_POST['action'].'<br>';
			$search_params = json_decode($_POST['search_params'], true);
			
			/* For testing
			foreach($search_params as $key=>$value) {
				echo '$array: '.$key.'=>'.$value.'<br>';
			}
			*/
			
			$array = array('page_title'=>'View Statistics - ', 'title_info'=>'Filtered Results', 'ro_count'=>true, 
						   'stats_month'=>false, 'stats_search'=>true, 'print-icon'=>true, 'export-icon'=>true, 'a_id'=>false
						  );
						  
			// Now add $search_params to $array for submission to class methods
			foreach($search_params as $key=>$value) {
				$array[$key] = $value;
			}
			
			// If dates were entered as search params, set $array['date_range'] = true, create sql-compatible format and add to $array for passing to methods
			if($array['date1_pres']) {
				$array['date_range'] = true;
				$date = new DateTime($array['date1_pres']);
				$array['date1_sql'] = $date->format("Y-m-d");
				$date = new DateTime($array['date2_pres']);
				$array['date2_sql'] = $date->format("Y-m-d");
			}
			
			// If only dates and/or date fields were entered, add 'Dealer: Name + Code' to search_feedback string so user knows which dealer the info pertains to
			if (( $array['date1_pres'] &&  $array['date2_pres'] &&  $array['advisor_id']) || 
			    ( $array['date1_pres'] &&  $array['date2_pres'] && !$array['advisor_id']) ||
			    (!$array['date1_pres'] && !$array['date2_pres'] &&  $array['advisor_id'])) {
				if (!$array['region_id'] && !$array['area_id'] && !$array['district_id'] && !$array['dealer_group']) {
					$array['search_feedback'] .= 'Dealer: '.$_SESSION['dealer_name'].' ('.$_SESSION['dealer_code'].')';
				}	
			}
			
			/* If dates only were selected (and no region, district, etc):
			 * Pass 'dealer_id' param SESSION var as default UNLESS
			 * 'View All Dealers' has been checked
			**/
			if(!$array['region_id'] && !$array['area_id'] && !$array['district_id'] && !$array['dealer_group']) {
				if (!$array['all_dealers_checkbox']) {
					$array['dealer_id'] = $_SESSION['dealer_id'];
				}
			}
			
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2']($array).
				 $obj->$use_array['method3']($array).
				 $obj->$use_array['method4']($array).
				 $obj->$use_array['method5']($array).
				 $obj->$use_array['method6']($array).
				 $obj->$use_array['method7']($array);
				 //$obj->$use_array['method8']($array);
		}
		
		if($_POST['action'] == 'dealer_summary') {
			// Run 'method2' first so that dealer count is available as SESSION['dealer_summary_count'] var
			$table = $obj->$use_array['method2']($array = null);
			echo $obj->$use_array['method1'](array('ro_count'=>true, 'page_title'=>'Dealer Reporting Summary', 'export-icon'=>true, 'print-icon'=>true)).
				 $table;
		}	
		
		if($_POST['action'] == 'view_dealer_list_all') {
			$array = array('page_title'=>'Manage Dealers - ', 'title_info'=>'All '.MANUF.' Dealers', 
						   'a_id'=>'add_dealer_link', 'link_msg'=>'Add New Dealer', 'dealer_count'=>true,
						   'print-icon'=>true, 'export-icon'=>true);
			// Run getDealerListingTable() method first so that $_SESSION['dealer_count'] may be used in title etc.
			$dealer_table = $obj->$use_array['method2']($array);
			echo $obj->$use_array['method1']($array).
				 $dealer_table;
		}
		
		if($_POST['action'] == 'get_dealer_add_form') {
			$array = array('page_title'=>'Manage Dealers - ', 'title_info'=>'Add New Dealers', 'a_id'=>false, 'link_msg'=>false, 'dealer_count'=>false);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2'](array('edit_dealer_val'=>false, 'a_id'=>'add_dealer_row', 'link_msg'=>'Add Row'));
		}
		
		if($_POST['action'] == 'add_dealer_row') {
			echo $obj->$use_array['method1'](array('add_dealer_row'=>true, 'edit_dealer_val'=>false));
		}
		
		if($_POST['action'] == 'add_dealers') {
			// remove after testing
			echo $obj->$use_array['method1'](array('edit_dealer_val'=>$_POST['edit_dealer_val'], 'edit_dealer_id'=>$_POST['edit_dealer_id']));
			// Instantiate Admin class for access to getSuccessMsg method
			/*
			$admin = new Admin($dbo=null);
			// Execute UPDATE method if edit_dealer_val == 1. Else execute INSERT statement.
			if(true === $result = $obj->$use_array['method1'](array('edit_dealer_val'=>$_POST['edit_dealer_val'], 'edit_dealer_id'=>$_POST['edit_dealer_id']))) {
				if($_POST['edit_dealer_val'] == true) {
					// Set dealer code value to you can echo it with the result
					$dealer_code = $_POST['edit_dealer_code'];
					echo $obj->$use_array['method2'](array('page_title'=>'Manage Dealers - ', 'title_info'=>'Edit '.MANUF.' Dealer')).
					     $admin->$use_array['method4'](array('success_msg'=>'*Dealer '.$dealer_code.' has been updated successfully'));
				} else {
					echo $obj->$use_array['method2'](array('page_title'=>'Manage Dealers - ', 'title_info'=>'Add '.MANUF.' Dealers')).
						 $obj->$use_array['method3'](array('edit_dealer_val'=>false, 'a_id'=>'add_dealer_row', 'link_msg'=>'Add Row')).
						 $admin->$use_array['method4'](array('success_msg'=>'The dealers you submitted have been processed successfully.'));
				}
			} else {
				echo $result;
			}*/
		}
		
		if($_POST['action'] == 'get_user_request_form') {
			$array = array('page_title'=>'Manage Users - ', 'title_info'=>'Submit User Setup Request', 'a_id'=>false, 'link_msg'=>false);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2'](array('a_id'=>'add_user_req_row', 'link_msg'=>'Add Row'));
		}
		
		if($_POST['action'] == 'add_user_req_row') {
			// Make sure to make the result js-compatible
			echo $obj->$use_array['method1']();
		}
		
		if($_POST['action'] == 'add_new_user_table') {
			$array = array('page_title'=>'Manage Users - ', 'title_info'=>'Add New Users', 'a_id'=>false, 'link_msg'=>false);
			echo $obj->$use_array['method1']($array).
				 $obj->$use_array['method2'](array('a_id'=>'add_new_user_row', 'link_msg'=>'Add Row'));
		}
		
		if($_POST['action'] == 'add_new_user_row') {
			$array = array('add_user_row'=>true);
			echo $obj->$use_array['method1']($array);
		}
	
		if($_POST['action'] == 'add_new_users') {
			// Execute INSERT instruction; if successful, proceed with table form reload and success msg
			if($result = $obj->$use_array['method1'](array('edit_user_val'=>$_POST['edit_user_val'], 'edit_user_id'=>$_POST['edit_user_id']))) {
				if(substr($result, 0, 10) == "error_dupe") {
					echo $result;
					exit;
				}
				
				if($_POST['edit_user_val'] == 1) {
					// Configure username POST for success message
					$user_name_success = json_decode($_POST['user_uname'], true);
					$user_name_success = $user_name_success[0];
					$array = array('page_title'=>'Manage Users - ', 'title_info'=>'Edit '.MANUF.' User', 'success_msg'=>'*User '.$user_name_success.' has been updated successfully!');
					echo $obj->$use_array['method2']($array).
					     $obj->$use_array['method4']($array);
				} elseif (substr($result, 0, 10) != "error_dupe") {
					$array = array('page_title'=>'Manage Users - ', 'title_info'=>'Add New Users', 'a_id'=>'add_new_user_row', 'link_msg'=>'Add Row', 'success_msg'=>'*The users you submitted have been processed successfully.  An email confirmation has been sent to: '.$_SESSION['user']['user_email']);
					echo $obj->$use_array['method2']($array).
						 $obj->$use_array['method3']($array).
						 $obj->$use_array['method4']($array);
				}
			} else {
				echo $result;
			}
		}
		
		if($_POST['action'] == 'check_username_dupe') {
			// Capture POST user_name value
			$array = array('user_name'=>$_POST['user_name']);
			// If username duplicate was found, return 'username_dupe'
			if($obj->$use_array['method1']($array)) {
				echo 'username_dupe';
			}
		}
		
		if($_POST['action'] == 'get_dealer_info_js') {
			// Make sure to make the result js-compatible
			echo json_encode($obj->$use_array['method1']());
		}
		
		if($_POST['action'] == 'process_user_setup_request') {
			//echo $_POST['user_req_fname'];
			//echo $obj->$use_array['method1']();
			
			/* Note that this process contains a js array string for $_POST['dealer_id'] and $_POST['dealer_code']
			 * Must use php json_decode($var, true) to convert back to arrays on server side
			**/
			
			// Run the method for db INSERT
			if($obj->$use_array['method1']()) {
				// If successful, reload page with new heading and fresh table so user can see something happened
				echo $obj->$use_array['method2'](array('page_title'=>'Manage Users - ', 'title_info'=>'Approve User Setup Requests', 'a_id'=>'add_user_req_row', 'link_msg'=>'Add Row')).
				     $obj->$use_array['method3'](array('user_approve'=>false)).
				     $obj->$use_array['method4'](array('success_msg'=>'*Your request was successfully submitted. An email confirmation will be sent to '.$_SESSION['user']['user_email']));
			} else {
				echo '<p>There was an error submitting your request.  Please see the administrator.</p>';
			}
		}
		
		if($_POST['action'] == 'view_user_setup_requests') {
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Users - ', 'title_info'=>'Approve User Setup Requests', 'a_id'=>false, 'link_msg'=>false)).
				 $obj->$use_array['method2'](array('user_approve'=>true, 'a_id'=>'select_all_user_requests', 'link_msg'=>'Select All'));
		}
		
		if($_POST['action'] == 'approve_user_setup_requests') {
			// If processUserSetupApprovals() returns true, display table and success msg. Else display error
			if($obj->$use_array['method1']($array = null)) {
				echo $obj->$use_array['method2'](array('page_title'=>'Approve User Setup Requests', 'a_id'=>'select_all_user_requests', 'link_msg'=>'Add Row')).
				     $obj->$use_array['method3'](array('user_approve'=>true)).
				     $obj->$use_array['method4'](array('success_msg'=>'*Your approvals were successfully submitted. An email confirmation will be sent to '.$_SESSION['user']['user_email'].'. <br> &nbsp; All requestors have been notified.'));
			} else {
				echo '<p>There was an error submitting your request.  Please see the administrator.</p>';
			}
		}
		
		/* Note: if dealer user, should only see table of their dealers (provide SESSION dealer_id)
		 * If SOS admin, should see list of all available dealer users
		 * If SOS non-admin, should see list of all available dealer users
		 * User type reference:  1 == SOS, 2 == Manuf, 3 == Dealer
		**/
		if($_POST['action'] == 'view_dealer_users') {
			// Set page 'title_info' based on the type of user requesting to view Manage Users page
			if($_SESSION['user']['user_type_id'] == 3) {
				$info = $_SESSION['dealer_name'];
			} else {
				$info = MANUF.' Dealer Users';
			}
			// Run table generation code first so that dealer count SESSION var is available to getPageHeading() method
			$user_table = $obj->$use_array['method2'](array('table_requested'=>'dealer','requested_by'=>$_SESSION['user']['user_type_id'], 'request_admin_val'=>$_SESSION['user']['user_admin']));
			
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Users - ', 'title_info'=>$info, 'link_msg'=>null, 'print-icon'=>true, 'export-icon'=>true, 'user_count'=>true)).
				 $user_table;
		}
		
		if($_POST['action'] == 'view_sos_users') {
			// Run table generation code first so that dealer count SESSION var is available to getPageHeading() method
			$user_table = $obj->$use_array['method2'](array('table_requested'=>'sos','requested_by'=>$_SESSION['user']['user_type_id'], 'request_admin_val'=>$_SESSION['user']['user_admin']));
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Users - ', 'title_info'=>MANUF.' SOS Users',  'link_msg'=>null, 'print-icon'=>true, 'export-icon'=>true, 'user_count'=>true)).
				 $user_table;
		}
		
		if($_POST['action'] == 'view_manuf_users') {
			// Run table generation code first so that dealer count SESSION var is available to getPageHeading() method
			$user_table = $obj->$use_array['method2'](array('table_requested'=>'manuf','requested_by'=>$_SESSION['user']['user_type_id'], 'request_admin_val'=>$_SESSION['user']['user_admin']));
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Users - ', 'title_info'=>MANUF.' Manufacturer Users', 'link_msg'=>null, 'print-icon'=>true, 'export-icon'=>true, 'user_count'=>true)).
				 $user_table;
		}
		
		if($_POST['action'] == 'table_user_edit_select') {
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Users - ', 'title_info'=>'Edit '.MANUF.' User', 'link_msg'=>null)).
			     $obj->$use_array['method2'](array('edit_user'=>true, 'user_id'=>$_POST['user_id']));
		}
		
		if($_POST['action'] == 'table_dealer_edit_select') {
			echo $obj->$use_array['method1'](array('page_title'=>'Manage Dealers - ', 'title_info'=>'Edit '.MANUF.' Dealer', 'link_msg'=>null)).
			     $obj->$use_array['method2'](array('edit_dealer_val'=>true, 'dealer_id'=>$_POST['dealer_id']));
		}
		
		if($_POST['action'] == 'add_doc_link') {
			echo $obj->$use_array['method1'](array('page_title'=>'System Documents - ', 'title_info'=>'Add New Document', 'a_id'=>'view_doc_link', 'link_msg'=>'View System Documents')).
			     $obj->$use_array['method2']($array = null);
		}
	
		if($_POST['action'] == 'file_submit') {
			// Run the processFileUpload() method. $status will contain uploaded filename if successful. Else false (bool)
			$status = $obj->$use_array['method1']();
			
			// Build feedback msg based on $status value
			if($status) {
				$msg = $status.' has been uploaded successfully!';
			} else {
				$msg = 'There was an error uploading '.$status.'<br>Please try again, and contant the administrator if the problem persists.';
			}
			
			// Send back html results
			echo $obj->$use_array['method2'](array('page_title'=>'System Documents - ', 'title_info'=>'Add New Document', 'a_id'=>'view_doc_link', 'link_msg'=>'View System Documents')).
				 $obj->$use_array['method4'](array('success_msg'=>$msg)).
			     $obj->$use_array['method3']($array = null);
		}
		
		// View document table
		if($_POST['action'] == 'view_doc_link') {
			$table = $obj->$use_array['method1']($array = null);
			echo $obj->$use_array['method2'](array('page_title'=>'System Documents - ', 'title_info'=>'View Documents', 'doc_count'=>true, 'export-icon'=>true, 'a_id'=>'add_doc_link', 'link_msg'=>'Add New Document')).
				 $table;
		}
		
		// View document table. Note doc types: 1 == 'release_docs', 2 == 'sysguide_docs', 3 == 'my_docs'
		if($_POST['action'] == 'view_doc_table') {
			// Assign $doc_type variable for db SELECT doc_type field value, and $doc_title for page title_info display
			switch ($_POST['doc_type']) {
				case 'release_docs':
					$doc_type = 1;
					$doc_title= 'Release Forms';
					break;
				case 'sysguide_docs':
					$doc_type = 2;
					$doc_title= 'System Guides';
					break;
				case 'my_docs':
					$doc_type = 3;
					$doc_title= 'My Documents';
					break;
			}
			
			// Save doc_type and doc_title as SESSION vars so that correct table displays after delete or edit doc action. Will be used in 'delet_doc' and 'file_update_submit' actions.
			$_SESSION['doc_type']  = $doc_type;
			$_SESSION['doc_title'] = $doc_title;
			
			$table = $obj->$use_array['method1'](array('doc_type'=>$doc_type));
			echo $obj->$use_array['method2'](array('page_title'=>'System Documents - ', 'title_info'=>$doc_title, 'doc_count'=>true, 'export-icon'=>true, 'a_id'=>'add_doc_link', 'link_msg'=>'Add New Document')).
				 $table;
		}
		
		// View actual pdf document
		if($_POST['action'] == 'table_doc_select') {
			//echo 'view_doc_id: '.$_POST['view_doc_id'];
			//exit;
			$obj->$use_array['method1'](array('view_doc_id'=>$_POST['view_doc_id']));
		}
		
		// Delete document from db if user confirms delete icon
		if($_POST['action'] == 'delete_doc') {
			$result = $obj->$use_array['method1'](array('view_doc_id'=>$_POST['view_doc_id'], 'tmp_name'=>$_POST['tmp_name']));
			// Set sucess msg based on $result.
			$msg = ($result) ? "The document was successfully deleted!" : "Error: The document could not be deleted. Please see the administrator.";
			// Now reload doc table and page heading. Table must be loaded first to acquire doc count SESSION var
			$table = $obj->$use_array['method2'](array('doc_type'=>$_SESSION['doc_type']));
			echo $obj->$use_array['method3'](array('page_title'=>'System Documents - ', 'title_info'=>$_SESSION['doc_title'], 'doc_count'=>true, 'export-icon'=>true, 'a_id'=>'add_doc_link', 'link_msg'=>'Add New Document')).
				 $obj->$use_array['method4'](array('success_msg'=>$msg)).
				 $table;
		}
		
		if($_POST['action'] == 'edit_doc_form') {
			echo $obj->$use_array['method1'](array('page_title'=>'System Documents - ', 'title_info'=>'Edit Document', 'doc_count'=>false, 'a_id'=>'view_doc_link', 'export-icon'=>false, 'link_msg'=>'View System Docs')).
				 $obj->$use_array['method2'](array('edit_doc_id'=>$_POST['edit_doc_id']));
		}
		
		if($_POST['action'] == 'file_update_submit') {
			$status = $obj->$use_array['method1']();
			$msg = ($status) ? 'The file was updated successfully!' : 'Error: The file was not updated.  Please try again or see the administrator.';
			$table = $obj->$use_array['method2'](array('doc_type'=>$_SESSION['doc_type']));
			echo $obj->$use_array['method3'](array('page_title'=>'System Documents - ', 'title_info'=>$_SESSION['doc_title'], 'doc_count'=>true, 'export-icon'=>true, 'a_id'=>'add_doc_link', 'link_msg'=>'Add New Document')).
				 $obj->$use_array['method4'](array('success_msg'=>$msg)).
				 $table;
		}
		
		if($_POST['action'] == 'contact_us_link') {
			echo $obj->$use_array['method1'](array('page_title'=>'Contact Us - ', 'title_info'=>'Comments? Suggestions?')).
				 $obj->$use_array['method2']();
		}
		
		if($_POST['action'] == 'contact_us_submit') {
			$result = $obj->$use_array['method1']();
			// Set feedback based on $result
			$msg = ($result) ? 'Thank you for your inquiry. A copy of your request has been sent to: '.$_SESSION['inquiry_email'].'.  We will contact you as soon as possible.' : 'Sorry! There was an error processing your request.  Please try again or contact the administrator.';
			unset($_SESSION['inquiry_email']);
			echo $obj->$use_array['method2'](array('page_title'=>'Contact Us - ', 'title_info'=>'Comments? Suggestions?')).
				 $obj->$use_array['method3'](array('success_msg'=>$msg)).
				 $obj->$use_array['method4']();
		}
		
		// Set $_SESSION['advisor_enterro'] if user selects advisor from enterro advisor dropdown option
		if($_POST['action'] == 'change_advisor') {
			$result = $obj->$use_array['method']();
		}
	}	
} else {
	echo 'error_login';
}
exit;
?>