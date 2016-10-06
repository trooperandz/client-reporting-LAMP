<?php
/**
 * Program: index_test.php
 * Created: 02/29/2016 by Matt Holland
 * Purpose: Display RO entry form and provide ajax update divs
 * Updates: 5/10/16: Added verifyUserLogin() check
 */

// Require the initialization file
require_once('system/config/init.inc.php');

// If user is not logged in, redirect to login screen (index.php)
verifyUserLogin();

// Instantiate Admin class
$adminObj = new Admin($dbo = null);

?>

<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SOS Online Reporting</title>

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
    <!-- preloader -->
    <div id="preloader">
    
    </div>
    <!-- End of preloader -->

   <div class="off-canvas-wrap" data-offcanvas>
        <!-- right sidebar wrapper -->
        <div class="inner-wrap">

        <?php
            // Include the sidebar.  User types dictate visibility of menu options, which dictates ability to access various pages
            echo $adminObj->getSidebarMenu();
        ?>

            <div class="wrap-fluid" id="paper-bg">
                <!-- top nav -->
                <div class="top-bar-nest">
                    <nav class="top-bar" data-topbar role="navigation" data-options="is_hover: false">
                        <ul class="title-area left">


                            <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
                            <li class="toggle-topbar menu-icon"><a href="#"><span></span></a>
                            </li>
                        </ul>

                        <section class="top-bar-section ">
                            <!-- Right Nav Section -->
                           
                            <!-- Left Nav Section -->
                            <ul class="left">

                                <!-- Search | has-form wrapper -->
                                <li class="has-form bg-white">
                                    <div class="row collapse">
                                        <div class="large-12 columns">
                                            <!--<div class="dark"> </div>
                                            <input class="input-top" type="text" placeholder="search">-->
                                            <h3 style="font-weight: 300;"><?php echo MANUF; ?> Online Reporting Portal </h3>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <ul class="right">
                                <li class=" has-dropdown bg-white">
                                    <a class="bg-white" href="#">
                                    	<!--<img alt="" class="admin-pic img-circle" src="http://api.randomuser.me/portraits/thumb/men/28.jpg">-->
                                    	<span class="admin-pic-text text-gray">
                                    		<i class="icon-user" style="color: maroon !important;"></i>
                                    		Hi, <?php echo $_SESSION['user']['user_fname']; ?> 
                                    	</span>
                                    </a>

                                    <ul class="dropdown dropdown-nest profile-dropdown">

                                        <li>
                                            <i class="icon-user"></i>
                                            <a href="#" data-reveal-id="user-profile-modal">
                                                <h4>Profile<span class="text-aqua fontello-record" ></span></h4>
                                            </a>
                                        </li>
                                        <li>
                                            <i class="icon-upload"></i>
                                            <a href="system/utils/process_logout.php">
                                                <h4>Logout<span class="text-dark-blue fontello-record" ></span></h4>
                                            </a>
                                        </li>
                                        <!--
                                        <li class="active right">
                                            <a href="#">
                                                <div class="label bg-white">More</div>
                                            </a>
                                        </li>-->
                                    </ul>
                                </li>
                                <!--
                                <li class="bg-white">
                                    <a class="right-off-canvas-toggle bg-white text-gray" href="#"><span style="font-size:13px" class="icon-view-list" ></span></a>
                                </li>
                                -->
                            </ul>
                        </section>
                    </nav>
                </div><!-- end of top nav -->
                <div class="loader_div"></div>
                	
                	<?php
                		// Create system modals
                		$modal = new Modal;
                		echo $modal->getRoSearchModal($title = "Filter Repair Orders");
                		echo $modal->getMetricsSearchModal($title = "Filter Metrics Data");
                		echo $modal->getStatsSearchModal($title = "Filter Stats Data");
                		echo $modal->getMetricsTrendModal($title = "Trend Metrics Data");
                		echo $modal->getMetricsDlrCompModal($title = "Dealer Comparison Data");
                        echo $modal->getUserProfileModal();
                	?>
				
				<div id="page"> <!-- ajax update div -->
					<?php
						$welr_obj = new Welr($dbo=null);
						// Build page heading.  This includes the opening container div.
						echo $welr_obj->getPageHeading(array('page_title'=>'Enter Repair Orders', 'ro_count'=>true, 'entry_form'=>true));
						
						// Show error and success messages
						showErrorSuccessMsg();
						
						// Build the rest of the page. If admin, show advisor selection dropdown
						if($_SESSION['user']['user_admin']) {
							echo $welr_obj->getAdvisorDropdown();
						}
						echo $welr_obj->getRoEntryForm($update_form = false, $update_ro_id = null, $search_params = false);
						echo $welr_obj->getRoEntryTable(array('entry_form' => true, 'date_range' => false, 'search_params' => false, 'export' => false));
					?>
					<!-- do not forget to add </div> to echo for end container div -->
                	</div> <!-- end Container Begin div -->
                </div> <!-- end div page -->


                <footer>
                    <div id="footer">Copyright &copy; <?php echo date("Y"); ?> <a href="http://www.sosfirm.com" target="_blank">Service Operations Specialists, Inc.</a></div>
                </footer>
            </div> <!-- end div wrap-fluid -->

        </div> <!-- end div inner-wrap -->

   </div> <!-- end of off-canvas-wrap -->

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
    
    $(document).ready(function() {
		
		$('#ro_date').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#ro_date1').datepicker({
			dateFormat: 'mm/dd/yy'
		});
		
		$('#ro_date2').datepicker({
			dateFormat: 'mm/dd/yy'
		});
		
		$('#metrics_date1').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#metrics_date2').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#metrics_trend_date1').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#metrics_trend_date2').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#metrics_dlr_comp_date1').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#metrics_dlr_comp_date2').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#stats_date1').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$('#stats_date2').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
			
		$('#sample_date').datepicker({
			dateFormat: 'mm/dd/yy',
			maxDate: "+0D"
		});
		
		$("#enterrotable").DataTable({
			paging: false,
			searching: false
		});
	
		$("#viewallros_table").DataTable({
			paging: true,
			searching: true
		});
		
		// Re-initialize table functionality
		$("#dealer_summary_table").DataTable({
		   "scrollX" : true,
		   paging: true,
		   searching: true,
		   "order": [[1,'asc']],			
		   "pageLength": 25
		});
		
		$("#hide_button").click(function(){
			$("#hide").toggle(100);
		});
		
		$("#hide_button").on("click", function() {
		  var el = $(this);
		  if (el.text() == el.data("text-swap")) {
			el.text(el.data("text-original"));
		  } else {
			el.data("text-original", el.text());
			el.text(el.data("text-swap"));
		  }
		});
	});
    </script>

</body>
</html>