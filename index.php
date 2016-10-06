<?php
/**
 * Program: index.php
 * Created: 05/05/2016 by Matt Holland
 * Purpose: User login page
 * Updates:
 */

// Require the initialization file
require_once('system/config/init.inc.php');
?>

<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EDUMIX</title>

    <link rel="stylesheet" href="css/foundation.css" />

    <!-- Custom styles for this template -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dripicon.css">
    <link rel="stylesheet" href="css/typicons.css" />
    <link rel="stylesheet" href="css/font-awesome.css" />
    <link rel="stylesheet" href="css/enterro_welr.css" />
    <link rel="stylesheet" href="css/custom_main.css" />
    <link rel="stylesheet" href="sass/css/theme.css">

    <!-- pace loader -->
    <script src="js/pace/pace.js"></script>
    <link href="js/pace/themes/orange/pace-theme-flash.css" rel="stylesheet" />
    <link rel="stylesheet" href="js/slicknav/slicknav.css" />
    
    <link rel="stylesheet" href="css/responsive-tables.css" media="screen" />
    <link rel="stylesheet" href="css/dataTables.foundation.css" />
	<link rel="stylesheet" href="css/jquery-ui.theme.min.css" />
	<link rel="stylesheet" href="css/jquery-ui.structure.min.css" />
	
	<!-- Matt custom print styles -->
	<link rel="stylesheet" media="print" href="css/print_custom.css" />

    <script src="js/vendor/modernizr.js"></script>

</head>
<body>
    <div id="preloader"></div>
    
    <div class="loader_div"></div>
    
    <div id="page"> <!-- ajax update div -->
    <?php
    	// Inistantiate Admin class object
    	$obj = new Admin($dbo=null);
    	
    	// If user has navigated to form via email reset password link, display password reset fields instead of main login
    	if (isset($_GET['user']) && $_GET['user'] != "") { 
			//$invalidAccess = false; 
			$hash = $_GET['user']; // Should I save as global?? yes.  process_ajax.inc.php will not read $_GET['user']
			$_SESSION['hash'] = $hash; // Don't forget to unset later
			echo $obj->getLoginForm(array('welcome_msg'=>'Please enter your reset information below: ', 'a_id'=>'return_loginform_link', 'enter_new_pass'=>true));
		} else {
    		// Build login form
    		echo $obj->getLoginForm(array('welcome_msg'=>'Please enter your login details below:', 'a_id'=>'forgot_pass_link', 'get_login_form'=>true));
    	}
    	
    	// Unset SESSION['error'] if page is reloaded
    	if(isset($_SESSION['error'])) {
    		unset($_SESSION['error']);
    	}
    ?>
    </div>

	<!-- main javascript library -->
    <script type='text/javascript' src="js/jquery.js"></script>
    <script type="text/javascript" src="js/waypoints.min.js"></script>
    <script type='text/javascript' src='js/preloader-script.js'></script>
    <!-- foundation javascript -->
    <script type='text/javascript' src="js/foundation.min.js"></script>
    <script type='text/javascript' src="js/foundation/foundation.#111111.js"></script>
    <!-- main edumix javascript -->
    <script type='text/javascript' src='js/slimscroll/jquery.slimscroll.js'></script>
    <script type='text/javascript' src='js/slicknav/jquery.slicknav.js'></script>
    <script type='text/javascript' src='js/sliding-menu.js'></script>
    <script type='text/javascript' src='js/scriptbreaker-multiple-accordion-1.js'></script>
    <script type="text/javascript" src="js/number/jquery.counterup.min.js"></script>
    <script type="text/javascript" src="js/circle-progress/jquery.circliful.js"></script>
    <script type='text/javascript' src='js/app.js'></script>
    <!-- additional javascript -->
    <script type='text/javascript' src="js/number-progress-bar/jquery.velocity.min.js"></script>
    <script type='text/javascript' src="js/number-progress-bar/number-pb.js"></script>
    <script type='text/javascript' src="js/loader/loader.js"></script>
    <script type='text/javascript' src="js/loader/demo.js"></script>
    <!-- FLOT CHARTS -->
    <script src="js/flot/jquery.flot.js" type="text/javascript"></script>
    <!-- FLOT RESIZE PLUGIN - allows the chart to redraw when the window is resized -->
    <script src="js/flot/jquery.flot.resize.min.js" type="text/javascript"></script>
    <!-- FLOT PIE PLUGIN - also used to draw donut charts -->
    <script src="js/flot/jquery.flot.pie.min.js" type="text/javascript"></script>
    <!-- FLOT CATEGORIES PLUGIN - Used to draw bar charts -->
    <script src="js/flot/jquery.flot.categories.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/skycons/skycons.js"></script>
    <script src="js/jquery-ui.min.js"></script>
	<script src="js/custom_enterrofoundation_welr.js"></script>
    <script src="js/responsive-tables.js"></script>
	<script src="js/jquery.dataTables.js"></script>
	<script src="js/dataTables.foundation.js"></script>
    <script>
    	$(document).foundation();
    </script>
</body>
</html>