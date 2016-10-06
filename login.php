<?php
/**
 * Program: login_test.php
 * Created: 05/05/2016 by Matt Holland
 * Purpose: Display RO entry form and provide ajax update divs. Adapted from original EDUMIX template
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



    <script src="js/vendor/modernizr.js"></script>

</head>

<body>
    <!-- preloader -->
    <div id="preloader">
    
    </div>
    <!-- End of preloader -->
    <!-- right sidebar wrapper -->

    <div class="inner-wrap">
        <div class="wrap-fluid">
            <br>
            <br>
            <!-- Container Begin -->
            <div class="large-offset-4 large-4 columns">
                <div class="box bg-white">
                    <!-- Profile -->
                    <div class="profile">
                        <img alt="" class="" src="./img/logo.png">
                        <h3>SOS Online Reporting <small>2.0</small></h3>
                    </div>
                    <!-- End of Profile -->

                    <!-- /.box-header -->
                    <div class="box-body " style="display: block;">
                        <div class="row">

                            <div class="large-12 columns">
                                <div class="row">
                                    <div class="edumix-signup-panel">
                                        <p class="welcome"> Please enter your login details below:</p>
                                        <form method="POST" action="system/utils/process_login.php">
                                            <div class="row collapse">
                                                <div class="small-2  columns">
                                                    <span class="prefix bg-green"><i class="text-white icon-user"></i></span>
                                                </div>
                                                <div class="small-10  columns">
                                                    <input type="text" id="username" name="username" placeholder="Username">
                                                </div>
                                            </div>
                                            <div class="row collapse">
                                                <div class="small-2 columns ">
                                                    <span class="prefix bg-green"><i class="text-white icon-lock"></i></span>
                                                </div>
                                                <div class="small-10 columns ">
                                                    <input type="text" id="password" name="password" placeholder="Password">
                                                </div>
                                            </div>
                                            <div class="row collapse">
                                                <div class="small-2 columns ">
                                                    <span class="prefix bg-green"><i class="text-white icon-tag"></i></span>
                                                </div>
                                                <div class="small-10 columns ">
                                                    <input type="text" id="dealercode" name="dealercode" placeholder="Enter a valid dealer code">
                                                </div>
                                            </div>
                                        <!--</form>-->
                                        <p> <a href="#" id="forgot_password_link" name="forgot_password_link">Forgot password ?</a> </p>
                                        <!--<a href="index.html" class="bg-green"><span>Sign in</span> </a>-->
                                        <input type="submit" class="tiny button radius" id="login_submit" name="login_submit" value="Sign In" />
                                    	<?php
                                    	// Show errors if login errors exist
                                    	if(isset($_SESSION['error'])) {
											echo'<div class="row collapse">
                                                	<div class="small-12 columns ">';
                                                	foreach($_SESSION['error'] as $error) {
                                                		echo'
                                                		<p style="padding-bottom: 0; margin-bottom: 3px;">'.$error.'</p><br>';
                                                	}
                                                	echo'
                                                	</div>
                                                 </div>';
                                        }
                                    	?>
                                    </form>
                                    <br>
                                </div>
                            </div>
								<!--
                                <div class="row">
                                    <div class="large-5 columns no-pad">
                                        <div class="edumix-footer-panel">
                                            <a href="#"><span class="bg-aqua"><i class="fa fa-twitter"></i>&nbsp;&nbsp;sign in with twitter</span></a>
                                        </div>
                                    </div>
                                    <div class="large-2 columns"></div>
                                    <div class="large-5 columns no-pad">
                                        <div class="edumix-footer-panel">
                                            <a href="#"><span class="bg-dark-blue"><i class="fa fa-facebook"></i>&nbsp;&nbsp;sign in with facebook</span></a>
                                        </div>
                                    </div>
                                </div>
                                -->

                            </div>
                        </div>
					</div>
                    <!-- end .timeline -->
                </div>
                <!-- box -->
            </div>
        </div>
        <!-- End of Container Begin -->
    </div>
    
    <?php
    	// Unset SESSION['error'] if page is reloaded
    	if(isset($_SESSION['error'])) {
    		unset($_SESSION['error']);
    	}
    ?>

    <!-- end paper bg -->
    <!-- end of inner-wrap -->

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