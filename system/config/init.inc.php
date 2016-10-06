<?php  
/* Program:	 config.inc.php
 * Created:  02/26/2016 by Matt Holland
 * Purpose:  Define system-wide settings that may be changed easily
 *			 Define useful constants that may be used by multiple scripts
			 Start the session
 * Methods:  
 * Updates:	 
 *
**/

// Define constants, set custom session settings, and start the session
if (!defined('LIVE')) {
	define ('LIVE', false); // LIVE constant is very important/ will dictate error handling
	define ('CONTACT_EMAIL', 'mtholland10@gmail.com'); // Email address errors will be sent to when site is live
	define ('BASE_URI', '/home/sosfirm/public_html/edumix/HTML/'); // Set this to your web directory		
	define ('BASE_URL', 'www.repairordersurvey.com/edumix/HTML/');
	define ('INDEX_FULL', 'www.repairordersurvey.com/edumix/HTML/index.php'); // Use this for providing user with main login form link in reset password confirmation email (after pass has already been reset)
	define ('INDEX_SHORT', 'index.php');
	define ('MANUF', 'Acura');
	define ('MANUF_ID', 3); // Note: 1 = Nissan, 2 = JL, 3 = Acura, 4 = Subaru
	define ('CREATE_DATE', date('Y-m-d H:i:s'));
	define ('ENTITY', 'Dealer');
	define ('ENTITYLCASE', 'dealer');
	define ('RO_UCASE', 'Invoice');
	define ('RO_LCASE', 'invoice');
	define ('MAIN_ENTRY_PAGE', 'enterrofoundation_welr_test.php');
	define ('PIC_ENTERRO', 'img/acura_2.jpg');
	define ('PIC_MENUS', 'img/acura_main.jpg');
	define ('PIC_AUTH', 'img/unauthorized.jpg');
	define ('PROCESS_FILE', 'system/utils/process.inc.php');
	define ('USER_DOC_DIR', '/home/sosfirm/public_html/edumix/HTML/system/user_docs/');
	define ('USER_TEAM', 1);  // Represents Acura user.  Used for user setups
	define ('USER_TYPE', 3);  // Represents Dealer type.  Used for user setup requests (only available for dealer user type)
	ini_set('session.gc_maxlifetime', 86400);  // These settings will make session timeout 42 hours
	ini_set('session.cookie_lifetime', 86400);
	session_start();
}

// Include the necessary DB configuration info and define constants for DB cxn
require dirname(__FILE__) . '/db-cred.inc.php';
foreach ($C as $name=>$val) { define($name, $val); }

// Create a mysqli object using constants created above....shouldn't the DB class take care of this anytime a cxn needs to be created?
//$mysqli = new mysqli (DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Function to verify that the user is logged in - used on index_test.php to redirect user if not logged in
function verifyUserLogin() {
	if(!isset($_SESSION['user'])) {
		die(header('Location: index.php'));
	}
}

// Function to verify that the user is logged in
function verifyUserLoginAjax($s_name = 'user') {
	if (!isset($_SESSION[$s_name])) {
		return false;
	} else {
		return true;
	}
}

// Function for important db error handling. Used for DB_Connect class
function sendError ($error, $line, $file) {
	// For important errors, email to your personal address in case admin debugging is needed
	$subject = MANUF.' online reporting error';
	$error = "Dear admin, an important error has occurred: \n ".$error." on line ".$line." in file ".$file;
	mail(CONTACT_EMAIL, $subject, $error);
}

// Function for important db error handling. Used for PDO_Connect class
function sendErrorNew ($error, $line, $file) {
	$subject = MANUF.' Online Reporting Error';
	$msg = "";
	// Break out error array
	foreach($error as $info) {
		$msg .= $info."\n";
	}
	$error = "Dear admin, an important error has occurred: on line ".$line." in file ".$file.": \n";
	$error .= $msg;
	mail(CONTACT_EMAIL, $subject, $error);
}

// Function to show error messages 
function showErrorSuccessMsg() {
	if(isset($_SESSION['error']) || isset($_SESSION['success'])) {
		$html ='
		<div id="update_div5">
			<div class="row">
				<div class="small-12 medium-7 large-7 columns">';
		if(isset($_SESSION['error'])) {
			foreach($_SESSION['error'] as $msg) {
				$html.='<h5 class="error_msg">'.$msg.'</h5>'; 
			}
		}
	
		if(isset($_SESSION['success'])) {
			foreach($_SESSION['success'] as $msg) {
				$html.='<h5 class="error_msg">'.$msg.'</h5>';
			}
		}
		$html .='
				</div><!-- end div small-12 medium-7 large-7 -->
			</div><!-- end div row -->
		</div><!-- end div update_div5 -->';
	}
}

// Define the auto-load function for loading of classes (prevents you from having to write a bunch of include statements)
function __autoload($class) {
	$filename = $_SERVER["DOCUMENT_ROOT"]."/edumix/HTML/system/class/class." .$class. ".inc.php";
	if (file_exists($filename)) {
		include_once($filename);
	}
}

/*
// Function to logout user if 2 hrs has passed - ajax
function session_timeout_check_ajax($destination = 'session_expired_page.php') {
	// Get request time and set timeout duration value
	$time = $_SERVER['REQUEST_TIME'];
	$timeout_duration = 10;

	// If 'last_activity' session var isset, compare request time to last activity to determine session expiration action
	if(isset($_SESSION['last_activity']) && ($time - $_SESSION['last_activity']) > $timeout_duration) {
		$url = 'https://'.BASE_URL.$destination;
		session_unset();     
		session_destroy();
		setcookie (session_name(), '', time()-300); // Destroy the cookie 
		exit('error_session_timeout');
	}

	// If above code does not execute, reset $_SESSION['last_activity']
	$_SESSION['last_activity'] = $time; 
}
*/