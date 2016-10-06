<?php
/**
 * Program: class.Admin.inc.php
 * Created: 02/26/2016 by Matt Holland
 * Purpose: Provide code for login, password resets, user management
 * Methods: getPageHeading(): Builds top of page for admin interfaces
 			getLoginForm(): Build main login form, password request form, password reset form, password reset confirmations
 			getSuccessMsg(): Create success feedback message for user setup requests, user approvals
 			processLoginForm(): Process main user login and show login errors
 			getUserRequests(): Get all pending user approval information to be used for table display
 			getUserRequestTable(): Build user setup request table and pending user setup table
 			processUserSetupApprovals(): Insert new users into user table, and delete related records in user_setup_request table
 			emailUserSetupApprovalConfirmAdmin(): Send email confirmation to admin who approved new users
 			emailUserSetupApprovalConfirmRequestor(): Send email confirmation to user who requested user setups
 			addUserRequestRow(): Adds row dynamically via AJAX to user setup request table
 			processUserSetupRequest(): Process user setup request form & insert user requests into user_setup_request table
 			emailUserSetupRequestConfirm(): Email confirm to current user and all admin when user setup requests have been submitted successfully
 			emailPassResetLink(): Emails confirmation with pass reset link to user who requested password reset
 			validateResetPassData(): Processes password reset form, confirms info, runs _resetPass() and _updateResetPassActive() methods
 			_resetPass(): Updates user password in user table
 			_updateResetPassActive(): Updates reset_pass table active field value to 0 (false) upon user pass reset success
 * Updates:
 */

Class Admin extends PDO_Connect  {

    // Establish global vars for defining user access to specific components
    public $user_sos   ;
    public $user_manuf ;
    public $user_dlr   ;
    public $user_admin ;

	public function __construct($dbo=NULL) {
		// Call the parent construct to check for a database object
		parent::__construct($dbo);

        // Initialize user types, if $_SESSION['user'] isset
        // Note: This is the only class which requires the $_SESSION['user'] check, as $_SESSION['user'] will always be set for other methods
        if(isset($_SESSION['user'])) {
            $this->user_sos   = ($_SESSION['user']['user_type_id'] == 1) ? true : false;
            $this->user_manuf = ($_SESSION['user']['user_type_id'] == 2) ? true : false;
            $this->user_dlr   = ($_SESSION['user']['user_type_id'] == 3) ? true : false;
            $this->user_admin = ($_SESSION['user']['user_admin']   == 1) ? true : false;
        }
	}
	
	public function getPageHeading($array) {
		// $array contains: 'page_title', 'a_id', 'link_msg'
		$msg = $array['link_msg'];
		$html ='
		<div class="title_area">
           	<div class="row">
           		<div class="small-12 medium-9 large-9 columns">
           			<p class="large-title">'.$array['page_title'];
           				if($array['title_info']) {
           					$html .='
           					<span class="blue"> '.$array['title_info'].' </span>';
           				}
           				if($array['a_id']) {
           					$html .='
           					<a id="'.$array['a_id'].'" data-text-original="Select All" data-text-swap="Unselect All" style="color: green; font-size: 15px;"> &nbsp; '.$msg.' </a>';
           				}
           			$html .='
           			</p>
           		</div>
           		<div class="small-12 medium-3 large-3 columns">
					<p class="right-align large-title">';
					  if($array['export-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Export Users" href="system/utils/export_users.php">
							<span class="fontello-download"></span>
						</a>';
					  }
					  if($array['print-icon']) {
					    $html .='
						<a class="tooltip-tip" title="Print User Table" href="#" onclick="window.print();">
							<span class="fontello-print"></span>
						</a>';
					  }
					  if($array['user_count']) {
					  	$html .='
						&nbsp;Total Users: '.number_format($_SESSION['user_count']);
					  }
					$html .='
					</p>
				</div>	
           	</div>
        </div>
        <!-- Container Begin -->
        <div class="row" style="margin-top:-20px">';
		return $html;
	}

    public function getSidebarMenu() {
        // Define user types for access to relevant system menu items. 
        // Note: this dictates overall system access. VERY IMPORTANT
        /*
        $user_sos   = ($_SESSION['user']['user_type_id'] == 1) ? true : false;
        $user_manuf = ($_SESSION['user']['user_type_id'] == 2) ? true : false;
        $this->user_dlr   = ($_SESSION['user']['user_type_id'] == 3) ? true : false;
        $this->user_admin = ($_SESSION['user']['user_admin']   == 1) ? true : false;*/

        // The following are the instruction manual user-defined menu access rules:
        // *Enter Repair Orders: (completed)
        //     -RO Entry Form: All users except manuf users (completed)
        //
        // *View Repair Orders: (completed)
        //     -Month To Date: All users
        //     -All History: All users
        //     -Advanced Search: All users
        //         -Advanced Search modal:
        //             -RO Range, Date, Year Model, Mileage, Labor, Parts, Vehicle Make: All users
        //             -Advisor: SOS users, Admin users
        //             -Services(include): All users
        //             -Services(exclude): All users
        //     -Export Current Listing: SOS users, Dealer admin users, Manuf users
        //
        // *Dealer Summary: (completed)
        //     -View Summary Table: All SOS users, manuf users
        //     -Export Summary Data: All SOS users, manuf users
        //
        // *View Metrics: (completed)
        //     -Month To Date: All users
        //     -All History: All users
        //     -Filter Metrics: All users
        //       Note: Individual search input components require unique access:
        //          *Date Range: All Users
        //          *Advisor: SOS users, Dealer admin users, manuf users
        //          *Region, Area, District, Dealer Group: SOS admin, manuf users
        //          *Dealer Group: All SOS users, manuf users
        //          *View All Dealers checkbox: SOS admin users, manuf users
        //     -Metrics Trending: (all users)
        //       Note: Individual search input components require unique access:
        //          *Date Range: All users
        //          *Advisor: SOS users, Dealer admin users, manuf users
        //          *Region, Area, District: SOS admin users, manuf users
        //          *Dealer Group: All SOS users, manuf users
        //          *View All Dealers Checkbox: SOS admin users, manuf users
        //     -Metrics Dealer Comparison (Only SOS & manuf users)
        //       Note: Individual search input components require unique access:
        //          *Date Range: All users (from above)
        //          *Dealer Group: All users (from above)
        //          *View All Dealers checkbox: SOS admin, manuf users
        //      -Export: SOS users, Dealer admin users, Manuf users
        //
        // *View Statistics:
        //     -Month To Date: All Users
        //     -All History: All Users
        //     -Filter Stats:
        //     Note: individual search input components require unique access:
        //          *Date Range: All Users
        //          *Advisor: SOS, Dealer admin users
        //          *Region, Area, District, Dealer Group: SOS admin users, manuf users
        //          *Dealer Group: SOS users, Manuf users
        //          *View All Dealers checkbox: SOS admin, manuf users
        //     -Export: SOS users, Dealer admin users, Manuf users
        //
        // *Manage Dealers: (completed)
        //     -View All Dealers:All SOS users, Manuf users
        //     -Add New Dealer: SOS admin users
        //     -Export Listing: All SOS users, Manuf users
        //     Note: The View All Dealers table 'Select' column is only available to SOS admin users (completed)
        //           The 'Add New Dealer' link in the page header is only available to SOS admin users (completed)
        // *Manage Users: (completed)
        //     Note: At top level, all users except for Manuf users may access link (completed)
        //           In all cases, only admin users may acces table 'Action' column - defined inside of getUserTable() method (completed)
        //           The 'Add New User' link in the page header is only available to admin users (incomplete)
        //     -Request User Setup: All SOS users (completed)
        //     -Approve User Setups: SOS admin users (completed)
        //     -Dealer Users: All SOS users (allows SOS consultants to see system users, for reference) - (completed)
        //      Note: The 'Action' column in the Dealer Users table is only available to admin users - (completed)
        //     -SOS Users: SOS users (completed)
        //     -Manuf Users: SOS admin (completed)
        //     -Add New User: SOS admin users and Dealer admin users (completed)
        //     -Export Listing: All SOS users, and Dealer admin users (completed)
        // *System Docs:
        //     Note: All users have access to the System Docs link (completed)
        //     -Release Forms: SOS users (completed)
        //     -System Guides: All users (completed)
        //     -My Documents:  SOS users and Dealer admin users (completed)
        //     -View All Docs: SOS admin only (completed)
        //     -Add New Document: SOS users and Dealer admin users (completed)
        //      Note: Doc category dropdown available optinos determined in Documents class ()

        $html ='
        <!-- Right sidemenu -->
        <div id="skin-select">
            <!-- Toggle sidemenu icon button -->
            <a id="toggle">
                <span class="fa icon-menu"></span>
            </a>
            <div class="skin-part">
                <div id="tree-wrap">
                    <!-- Profile -->
                    <div class="profile">
                        <img alt="" class="" src="./img/logo3.png">
                        <h3>SOS Inc.</h3>
                    </div> <!-- .profile -->

                    <!-- Menu sidebar begin-->
                    <div class="side-bar">
                        <ul id="menu-showhide" class="topnav slicknav">';
                          if(!$this->user_manuf) {
                            $html .='
                            <li>
                                <a class="tooltip-tip" href="#" title="Enter ROs">
                                    <i class="icon-monitor"></i>
                                    <span>Enter Repair Orders</span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="#" title="RO Entry Form" class="enter_ros_link" name="enter_ros_link">RO Entry Form</a>
                                    </li>
                                </ul>
                            </li>';
                          }

                          $html .='
                            <li>
                                <a class="tooltip-tip" href="#" title="View ROs">
                                    <i class=" icon-window"></i>
                                    <span>View Repair Orders</span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="#" title="Current Month" class="view_ros_link" name="view_ros_month">Month To Date</a>
                                    </li>
                                    <li>
                                        <a href="#" title="All History" class="view_ros_link" name="view_ros_all">All History</a>
                                    </li>
                                    <li>
                                        <a href="#" data-reveal-id="ro_search_modal" title="Advanced Search" class="advanced_search" name="ro_search" >Advanced Search</a>
                                    </li>';
                                  // Only show this to dealer admin users, all sos users, and manuf users
                                  if($this->user_admin || $this->user_sos || $this->user_manuf) {
                                    $html .='
                                    <li>
                                        <a href="system/utils/export_ros.php" title="Export Listing" class="export_ros" name="export_ros">Export Current Listing</a>
                                    </li>';
                                  }
                                $html .='
                                </ul>
                            </li>';

                        // Only show dealer summary to non-dealer users
                        if(!$this->user_dlr) {
                            $html .='
                            <li>
                                <a class="tooltip-tip" href="#" title="Dealer Summary">
                                    <i class="icon-map"></i>
                                    <span>Dealer Summary</span>
                                </a>
                                <ul>

                                    <li>
                                        <a href="#" title="View Summary Table" class="dealer_summary_link" name="dealer_summary_link">View Summary Table</a>
                                    </li>
                                    <li>
                                        <a href="system/utils/export_dealer_summary.php" title="Export Summary Data" class="export_dealer_summary" name="export_dealer_summary">Export Summary Data</a>
                                    </li>
                                </ul>
                            </li>';
                        }
                            // Show metrics to all users
                            $html .='
                            <li>
                                <a class="tooltip-tip" href="#" title="Metrics">
                                    <i class="icon-graph-pie"></i>
                                    <span>View Metrics</span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="#" title="Last 30 Days" class="view_metrics_month" name="view_metrics_month">Month To Date</a>
                                    </li>
                                    <li>
                                        <a href="#" title="All History" class="view_metrics_all" name="view_metrics_all">All History</a>
                                    </li>
                                    <li>
                                        <a href="#" data-reveal-id="metrics_search_modal" title="Filter Metrics" class="metrics_search" name="metrics_search" >Filter Metrics</a>
                                    </li>
                                    <li>
                                        <a href="#" data-reveal-id="metrics_trend_modal" title="Metrics Trending" class="metrics_trend" name="metrics_trend" >Metrics Trending</a>
                                    </li>';
                                // Only show Dealer Comparison menu option to SOS & Manuf users
                                if(!$this->user_dlr) {
                                    $html .='
                                    <li>
                                        <a href="#" data-reveal-id="metrics_dlr_comp_modal" title="Dealer Comparison" class="metrics_dlr_comp" name="metrics_dlr_comp">Dealer Comparison</a>
                                    </li>';
                                }

                                // Only show export link to SOS users, Dealer admin users, and Manuf users
                                if($this->user_admin || $this->user_sos || $this->user_manuf) {
                                    $html .='
                                    <li>
                                        <a href="system/utils/export_metrics.php" title="Export Listing" class="export_metrics" name="export_metrics">Export Current Listing</a>
                                    </li>';
                                }

                                $html .='
                                </ul>
                            </li>

                            <li>
                                <a class="tooltip-tip" href="#" title="Statistics">
                                    <i class="icon-graph-line"></i>
                                    <span>View Statistics</span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="#" title="Last 30 Days" class="view_stats_month" name="view_stats_month">Month To Date</a>
                                    </li>
                                    <li>
                                        <a href="#" title="All History" class="view_stats_all" name="view_stats_all">All History</a>
                                    </li>
                                    <li>
                                        <a href="#" data-reveal-id="stats_search_modal" title="Filter Stats" class="stats_search" name="stats_search" >Filter Stats</a>
                                    </li>';

                                // Only show export link to SOS users, Dealer admin users, and Manuf users
                                if($this->user_admin || $this->user_sos || $this->user_manuf) {
                                    $html .='
                                    <li>
                                        <a href="system/utils/export_stats.php" title="Export Current Stats" class="export_stats" name="export_stats">Export Current Stats</a>
                                    </li>';
                                }

                                $html .='
                                </ul>
                            </li>';

                        // Only show the Manage Dealers link to SOS users and Manuf users
                        if($this->user_sos || $this->user_manuf) {
                            $html .='    
                            <li>
                                <a class="tooltip-tip" href="#" title="Dealer Listing">
                                    <i class="icon-document-edit"></i>
                                    <span>Manage Dealers</span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="#" title="View All Dealers" class="view_dealer_list" name="view_dealer_list">View All Dealers</a>
                                    </li>';

                                // Only allow SOS admin users to add new dealers
                                if($this->user_sos && $this->user_admin) {
                                    $html .='
                                    <li>
                                        <a href="#" title="Add New Dealer" class="add_dealer_link" name="add_dealer_link">Add New Dealer</a>
                                    </li>';
                                }
                                    $html .='
                                    <li>
                                        <a href="system/utils/export_dealers.php" title="Export Current Listing" class="export_dealers_link" name="export_dealers_link">Export Current Listing</a>
                                    </li>
                                </ul>
                            </li>';
                        }
                        
                        // Only show the Manage Users link to all users except for Manuf users
                        if(!$this->user_manuf) {
                            $html .='      
                            <li>
                                <a class="tooltip-tip" href="#" title="Manage Users">
                                    <i class="icon-document-edit"></i>
                                    <span>Manage Users</span>
                                </a>
                                <ul>';

                                // Only allow SOS users to access user setup link
                                if($this->user_sos) {
                                    $html .='
                                    <li>
                                        <a href="" title="Request User Setup" class="request_user_setup" name="request_user_setup">Request User Setup</a>
                                    </li>';
                                }

                                // Only allow SOS admin users to access user setup approvals
                                if($this->user_sos && $this->user_admin) {
                                    $html .='
                                    <li>
                                        <a href="" title="Approve User Setups" class="approve_user_setup" name="approve_user_setup">Approve User Setups</a>
                                    </li>';
                                }

                                // Only allow SOS users and Dealer admin users to view Dealer users. Note: only SOS admin have access to table 'Action' column
                                if($this->user_sos || ($this->user_dlr && $this->user_admin)) {
                                    $html .='
                                    <li>
                                        <a href="" title="View Dealer Users" class="view_users" name="view_dealer_users">Dealer Users</a>
                                    </li>';
                                }

                                // Only allow SOS users to view SOS users (Allow all SOS users access so that they may view team members etc)
                                // Note: only SOS admin have access to table 'Action' column
                                if($this->user_sos) {
                                    $html .='
                                     <li>
                                        <a href="#" title="View SOS Users" class="view_users" name="view_sos_users">SOS Users</a>
                                    </li>';
                                }

                                // Only allow SOS admin to view Manuf users
                                if($this->user_sos && $this->user_admin) {
                                    $html .='
                                     <li>
                                        <a href="#" title="View Manuf Users" class="view_users" name="view_manuf_users">Manuf Users</a>
                                    </li>';
                                }

                                // Only allow SOS admin users, or Dealer admin users to add new users. Note: SOS non-admin users may request users instead
                                if(($this->user_sos && $this->user_admin) || ($this->user_dlr && $this->user_admin)) {
                                    $html .='
                                    <li>
                                        <a href="#" title="Add New User" class="add_user_link" name="add_user_link">Add New User</a>
                                    </li>';
                                }

                                // Only allow SOS users and Dealer admin users to export user listing
                                if($this->user_sos || ($this->user_dlr && $this->user_admin)) {
                                    $html .='
                                    <li>
                                        <a href="system/utils/export_users.php" title="Export Current Listing" class="export_users_link" name="export_users_link">Export Current Listing</a>
                                    </li>';
                                }

                                $html .='
                                </ul>
                            </li>';
                        }
                        
                            $html .='        
                            <li>
                                <a class="tooltip-tip" href="#" title="System Docs">
                                    <i class=" icon-document"></i>
                                    <span>System Docs</span>
                                </a>
                                <ul>';

                                // Only allow SOS users to view release docs
                                if($this->user_sos) {
                                    $html .='
                                    <li>
                                        <a href="#" title="View Release Forms" class="view_doc_table" name="release_docs">Release Forms</a>
                                    </li>';
                                }

                                // Allow all users to view System Guides 
                                // Note: How is dealer guide vs SOS user guide (no dealer access) determined?
                                    $html .='
                                    <li>
                                        <a href="#" title="View System Guides" class="view_doc_table" name="sysguide_docs">System Guides</a>
                                    </li>';

                                // Only allow SOS users and Dealer admin users to view My Documents (no Manuf access)
                                if($this->user_sos || ($this->user_dlr && $this->user_admin)) {
                                    $html .='
                                    <li>
                                        <a href="#" title="View My Documents" class="view_doc_table" name="my_docs">My Documents</a>
                                    </li>';
                                }

                                // Only allow SOS admin users to view all docs (This will pull up ALL documents stored in the system)
                                if($this->user_sos && $this->user_admin) {
                                    $html .='
                                    <li>
                                        <a href="#" title="View All Documents" class="view_doc_link" name="view_doc_link">View All Docs</a>
                                    </li>';
                                }

                                // Only allow SOS users, and Dealer admin users to add new documents
                                if($this->user_sos || ($this->user_dlr && $this->user_admin)) {
                                    $html .='
                                    <li>
                                        <a href="#" title="Add New Document" class="add_doc_link" name="add_doc_link">Add New Document</a>
                                    </li>';
                                }

                                $html .='
                                </ul>
                            </li>
                        </ul>
                    </div> <!-- .side-bar  -->
                    <ul class="bottom-list-menu">
                        <!--
                        <li>Settings <span class="icon-gear"></span>
                        </li>-->
                        <li>
                            <a href="#" title="Contact Us" class="contact_us_link" name="contact_us_link">Contact Us <span class="icon-phone"></span></a>
                        </li>
                        <li><a href="http://www.sosfirm.com" title="About SOS" target="_blank">About SOS <span class="icon-information"></span></a>
                        </li>
                    </ul>
                </div> <!-- .tree-wrap -->
            </div> <!-- .skin-part -->
        </div> <!-- .skin-select -->';

        return $html;
    }
	
	// Build main system login form.  Also used for reset password forms
	public function getLoginForm($array) {
		// $array contains the following: 'forgot_pass_link'
		
		// Set 'welcome' msg, submit id and submit value based on params
		$msg = $array['welcome_msg'];
		
		// If login SESSION vars are set, show values in form upon login error.  Prevents user from losing entered data
		if(isset($_SESSION['error'])) {
			$error = $_SESSION['error'];
			$username = (isset($error['username'])) ? $error['username'] : null;
			$password = (isset($error['password'])) ? $error['password'] : null;
			$dealerocde = (isset($error['dealercode'])) ? $error['dealercode'] : null;
		}
		
		// Create form markup based on passed param value
		if ($array['forgot_pass_link']) {
			$submit_id  = 'send_reset_link';
			$submit_val = 'Send Link';
		} elseif ($array['enter_new_pass']) {
			$submit_id  = 'update_pass_submit';
			$submit_val = 'Reset Password';
		} elseif ($array['get_login_form']) {
			$submit_id  = 'login_submit';
			$submit_val = 'Sign In';
		}
		
		// Create an id for the <a> tag at the bottom of the form (the form link i.e. Forgot Password, Return to Login Form)
		if($array['a_id']) {
			$a_id = $array['a_id'];
		} else {
			$a_id = null;
		}
		
		// Build main form
		$html ='
		<div class="inner-wrap">
       		<div class="wrap-fluid">
       		    <br><br>
       		    <div class="large-offset-4 large-4 columns">
       		        <div class="box bg-white" style="margin: 20px 0 !important; padding: 0px !important;">
       		            <div class="profile" style="border-radius: 4px 4px 0 0;">
       		                <img alt="" class="" src="./img/logo3.png">
       		                <h3> SOS Online Reporting <small>2.0</small> </h3>
       		            </div>
       		            <div class="box-body" style="display: block; padding: 20px !important;">
       		                <div class="row">
       		                    <div class="large-12 columns">
       		                        <div class="row">
       		                            <div class="edumix-signup-panel">
       		                                <p class="welcome"> '.$msg.' </p>
       		                                <form id="login_form" method="POST" action="system/utils/process_login.php">';
       		                                // Build form inputs based on params
       		                                if ($array['forgot_pass_link']) {
       		                                  $html .='
       		                                  	<div class="row collapse">
       		                                        <div class="small-2 columns">
       		                                            <span class="prefix bg-green"><i class="text-white icon-user"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns">
       		                                            <input type="text" id="user_email" name="user_email" placeholder="Enter recovery email address">
       		                                        </div>
       		                                    </div>
       		                                    <input type="submit" class="tiny button radius" id="'.$submit_id.'" name="'.$submit_id.'" value="'.$submit_val.'" />
       		                                    <p> <a href="'.INDEX_SHORT.'" id="'.$a_id.'" name="'.$a_id.'">Return to Login Form</a> </p>';
       		                                } elseif ($array['email_resetlink_success']) {
       		                                	$html .='
       		                                    <p> '.$array['reset_msg'].'<br>
       		                                    	<a href="'.INDEX_SHORT.'" id="'.$a_id.'" name="'.$a_id.'">Return to Login Form</a> 
       		                                    </p>';
       		                                } elseif ($array['enter_new_pass']) {
       		                                  $html .='
       		                                  	 <div class="row collapse">
       		                                        <div class="small-2 columns">
       		                                            <span class="prefix bg-green"><i class="text-white icon-user"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns">
       		                                            <input type="text" id="user_email" name="user_email" placeholder="Verify email address">
       		                                        </div>
       		                                    </div>
       		                                  	<div class="row collapse">
       		                                        <div class="small-2 columns">
       		                                            <span class="prefix bg-green"><i class="text-white icon-lock"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns">
       		                                            <input type="text" id="pass1" name="pass1" placeholder="Enter new password">
       		                                        </div>
       		                                    </div>
       		                                    <div class="row collapse">
       		                                        <div class="small-2 columns">
       		                                            <span class="prefix bg-green"><i class="text-white icon-lock"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns">
       		                                            <input type="text" id="pass2" name="pass2" placeholder="Verify password">
       		                                        </div>
       		                                    </div>
       		                                    <input type="submit" class="tiny button radius" id="'.$submit_id.'" name="'.$submit_id.'" value="'.$submit_val.'" />';
       		                                } elseif ($array['get_login_form']) {
       		                             	  // Access login SESSION vars if they have been set
       		                             	  if(isset($_SESSION['login_username'])) {
       		                             	  		$username   = $_SESSION['login_username'];
       		                             	  		$password   = $_SESSION['login_password'];
       		                             	  		$dealercode = $_SESSION['login_dealercode'];
       		                             	  } else {
       		                             	  		$username   = null;
       		                             	  		$password   = null;
       		                             	  		$dealercode = null;
       		                             	  }
       		                                  $html .='
       		                                    <div class="row collapse">
       		                                        <div class="small-2 columns">
       		                                            <span class="prefix bg-green"><i class="text-white icon-user"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns">
       		                                            <input type="text" id="username" name="username" placeholder="Username" value="'.$username.'">
       		                                        </div>
       		                                    </div>
       		                                    <div class="row collapse">
       		                                        <div class="small-2 columns ">
       		                                            <span class="prefix bg-green"><i class="text-white icon-lock"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns ">
       		                                            <input type="password" id="password" name="password" placeholder="Password" value="'.$password.'">
       		                                        </div>
       		                                    </div>
       		                                    <div class="row collapse">
       		                                        <div class="small-2 columns ">
       		                                            <span class="prefix bg-green"><i class="text-white icon-tag"></i></span>
       		                                        </div>
       		                                        <div class="small-10 columns ">
       		                                            <input type="text" id="dealercode" name="dealercode" placeholder="Enter a valid dealer code" value="'.$dealercode.'">
       		                                        </div>
       		                                    </div>
       		                                	<p> <a href="'.INDEX_SHORT.'" id="'.$a_id.'" name="'.$a_id.'">Forgot password ?</a> </p>
       		                                	<input type="submit" class="tiny button radius" id="'.$submit_id.'" name="'.$submit_id.'" value="'.$submit_val.'" />';
       		                             	}
       		                             	
       		                            	// Show errors if login errors exist
       		                            	if(isset($_SESSION['error'])) {
       		                            		$html .='
													                      <div class="row collapse">
       		                                        	<div class="small-12 columns ">
       		                                        		<p style="padding-bottom: 0; margin-bottom: 3px;"> The following login errors have occurred:<br>
       		                                        			<span style="color: red;">';
       		                                        	foreach($_SESSION['error'] as $error) {
       		                                        		$html .=
       		                                        		$error.'<br>';
       		                                        	}
       		                                        	$html .='
       		                                        			</span>
       		                                        		</p>
       		                                        	</div>
       		                                     	</div>';
       		                                }
       		
       		                            $html .='
       		                            </form> <!-- end form login_form -->
       		                        </div> <!-- end div edumix-signup-panel -->
       		                    </div> <!-- end div row -->
       		                    
								               <!-- the form footer area
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
       		                     </div>-->
       		                    </div>
       		                </div>
						          </div>
       		        </div> <!-- end div box bg-white -->
       		    </div> <!-- end div large-offset-4 large-4 columns -->
       		 </div> <!-- end div wrap-fluid -->
    	</div> <!-- end div inner-wrap -->';
    	
    	return $html;
	}
	
	public function getSuccessMsg($array) {
		$html .='
		<div class="row">
			<div class="large-12 columns">
				<p style="color: green;">'.$array['success_msg'].'</p>
			</div>
		</div>';
		return $html;
	}
	
	public function processLoginForm($array) {
		// Unset SESSION errors every time form is processed. Prevents old errors from showing
		unset($_SESSION['error']);

		// Process login form POST values. Also set login SESSION vars for sticky form inputs
		$user_name = $array['username'];
		$_SESSION['login_username'] = $user_name;
		
		$login_password = $array['password'];
		$_SESSION['login_password'] = $login_password;
		
		$login_dealercode = $array['dealercode'];
		$_SESSION['login_dealercode'] = $login_dealercode;
		
		// Set errors if there were input errors.  Display with SESSION vars.
		if (!empty($user_name)) {
			$user_name = trim($user_name);
			$_SESSION['login_username'] = $user_name;
		} else {
			$_SESSION['error']['username'] = '*Please enter a valid username';
		}
		
		if (!empty($login_password)) {
			$login_password = trim($login_password);
			$_SESSION['login_password'] = $login_password;
		} else {
			$_SESSION['error']['password'] = '*Please enter a valid password';
		}
		
		if (!empty($login_dealercode)) {
			$login_dealercode = trim($login_dealercode);
			$_SESSION['login_dealercode'] = $login_dealercode;
		} else {
			$_SESSION['error']['dealercode'] = '*Please enter a valid dealer code';
		}
		
		//echo 'user: '.$user_name.' pass: '.$login_password.' code: '.$login_dealercode;
		//echo 'error: '.var_dump($_SESSION['error']).'<br>';
		
		/* If there were no errors, query the db.
		 * This login query structure is revamped from the old login structure, which required several if iterations.
		 * Old structure involved at least three tables of different user types.
		 * New 'user' table allows all users to be together in one table, thus reducing coding complexity.
		**/
		
		if(empty($_SESSION['error'])) {
			// If no errors, now query user table for user info using UserInfo class
			$obj = new UserInfo($dbo=null);
			$login_result = $obj->getUserInfo(array('user_name'=>$user_name));
			
			if(count($login_result) == 0) {
				// If there was no result at this point, then the given username does not exist
				$_SESSION['error'][] = '*Your username or password was incorrect.';
			} else {
				// Username okay. Get user data and test password.  Index will always be 0
				$user_pass = $login_result[0]['user_pass'];
				$user_dlr  = $login_result[0]['dealercode'];
				
				/* Verify that dealercode entered at login exists.  
				 * This step cannot always be completed in above query, because some user types are not assigned a dealercode.
				 * In these cases, the dealerID will be a 0.  So always must check to see if dealercode does exist,
				 * as the system default requires to have a dealerID and dealercode set for working operation.
				 * In addition, if a system dealercode has changed and the user is unaware, their original dealercode will
				 * not work.  In this case, they need to be notified that it does not exist.  
				 * Thus, always perform this query regardless of user type.
				 * Tested incorrect dealercode after including OOP query and worked successfully for "Dealer not in system" msg
				**/
				$dlr = new DealerInfo($dbo=null);
				$dealer_info = $dlr->getDealer(array('dealercode'=>$login_dealercode));
				
				// If password is verified, proceed with setting user SESSION data. Will ALWAYS be index 0
				if(password_verify($login_password, $user_pass)) {
					$user_info = array(
							'user_id'        => $login_result[0]['user_id']	   ,
							'user_name'      => $login_result[0]['user_name']  ,
							'user_type_id'   => $login_result[0]['type_id']    ,
							'user_team_id'   => $login_result[0]['team_id']    ,
							'user_type_name' => $login_result[0]['type_name']  ,
							'user_team_name' => $login_result[0]['team_name']  ,
							'user_fname'     => $login_result[0]['user_fname'] ,
							'user_lname'     => $login_result[0]['user_lname'] ,
							'user_email'     => $login_result[0]['user_email'] ,
							'user_active'    => $login_result[0]['user_active'],
							'user_admin'     => $login_result[0]['user_admin'] ,
						);
					// Test to make sure that user is active in the system
					if($user_info['user_active'] == 0) {
						$_SESSION['error'][] = '*You are no longer active in the system!';
					}
					// If user is dealer type, test dealercode access verification	
					if($user_info['user_type'] == 'dealer') {
						if($login_dealercode != $user_dlr) {
							$_SESSION['error'][] = '*You do not have access to that dealer.';
						}
					}
				} else {
					$_SESSION['error'][] = '*Your username or password was incorrect.';
				}
				
				/* If there were any errors above, return false and make sure to display error messages.
				 * Otherwise, set user SESSION array, dealer SESSION data and browser SESSION data, and
				 * then proceed with page redirect.
				 * Also unset all login $_SESSION vars
				**/
				if(!empty($_SESSION['error'])) {
					return false;
				} else {
					/* Success! Unset login SESSION vars, set SESSION vars, and proceed with program
					 * Note:  cannot use $login_result to set dealername, because user may be SOS (dealername is NULL)
					 * Use $dealer_info instead
					**/
					unset($_SESSION['login_username'], $_SESSION['login_password'], $_SESSION['login_dealercode'], $_SESSION['error']);
					$_SESSION['user'] 		 = $user_info;
					$_SESSION['dealer_id']   = $dealer_info['dealerID'];
					$_SESSION['dealer_code'] = $login_dealercode;
					$_SESSION['dealer_name'] = $dealer_info['dealername'];
					// Set Acura session so that cannot go to other manuf systems cross-browser
					$_SESSION[MANUF] = true; 

					return true;
				}
			}
		}
	}
	
	// Retrieve all rows from user request table and save as array.  Will be displayed in approval table
	public function getUserRequests() {
		$stmt = "SELECT id, fname, lname, uname, email, pass_hash, dealerID, dealercode, 
				        dealername, admin, active, user_team_id, user_type_id, user_id,
				        req_by_name, req_by_email
				 FROM user_setup_request";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			$result = false;
		}
		
		if(!($stmt->execute())) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			$result = false;
		} else {
			/* Save data as SESSION var so that user approval submit does not have to do another db call. 
		 	 * All info is already available here.
		 	 * Use checkbox id of selected users to search through SESSION array and retreive data
			**/
			$result = $stmt->fetchAll();
			$_SESSION['user_requests'] = $result;
			//echo '$result: '.var_dump($result = $stmt->fetch(PDO::FETCH_ASSOC));
		}
		return $result;
	}
	
	/* Display online reporting users (dealer, sos, manuf)
     * Note: only admin users are given the 'Action' table field
     * $array param may contain: 'user_type', 'admin'.  These will dictate what table fields are viewable.
     * Note: possible user types = sos(id == 1) or dealer (id == 3)
     * Code also contains export build
    **/
    public function getUserTable($array) {
    	/* Get admin type, user type, and table_requested values from $array requesting user table. 
    	 * $table_requested possible values: 'dealer', 'sos', 'manuf'
    	 * $requested_by possible values: 1(SOS), 3(Dealer)
    	 * $request_admin possible values: 0(non-admin), 1(admin)
    	 * Make sure that $array items received do not conflict with getUserInfo() method
    	 * Available params affecting getUserInfo(0) method: 
    	 * 'user_name', 'user_email', 'dealer_id', 'user_type_id', 'user_admin', 'user_active'
    	**/
    	$table_requested = $array['table_requested'];
    	$requested_by    = $array['requested_by'];
    	$request_admin   = $array['request_admin_val'];
    	
    	// Build beginning of export
    	$export = MANUF." User Listing ".date("m/d/Y")."\n";
    	
    	/* Add $array params to getUserInfo in order to produce correct user results, based on process_ajax rec'd params
    	 * $table_requested and $request_admin values dictate the 'user_type_id', 'dealer_id' getUserInfo() passed params
    	**/
    	// If user type is dealer, and dealer table is requested, show only SESSION['dealer_id'] users. Admin is N/A bc only dealer admin have access to Dealer Users main menu link
    	if($table_requested == 'dealer' && $requested_by == 3) {
    		$array['dealer_id'] = $_SESSION['dealer_id'];
    		$export .= $_SESSION['dealer_name']." (".$_SESSION['dealer_code'].") User Listing \n\n";
    	// If user type is sos, and dealer table is requested, show all dealer users (not just SESSION['dealer_id'] users). Admin and non-admin have same access (limited to editing users, which is set later on in table build)
    	} elseif ($table_requested == 'dealer' && $requested_by == 1) {
    		$array['user_type_id'] = 3;
    		$export .= "Dealer User Listing \n\n";
    	// If user type is sos, and sos table is requested, show all SOS users
    	} elseif ($table_requested == 'sos') {
    		$array['user_type_id'] = 1;
    		$export .= "SOS User Listing \n\n";
    	// If user type is sos, and manuf table is requested, show all manuf users
    	} elseif ($table_requested == 'manuf') {
    		$array['user_type_id'] = 2;
    		$export .= "Manufacturer User Listing \n\n";
    	}
    
    	// Get user data array based on $array params
    	$obj = new UserInfo($dbo=null);
    	$user_array = $obj->getUserInfo($array);
    	
    	// Save user count as SESSION var
    	$_SESSION['user_count'] = count($user_array);
    	
    	// Get user count for export
    	$export .= "Total Users: ".$_SESSION['user_count']."\n\n";
    	
    	// Build user table export headings
    	$export .= "First Name, Last Name, Username, Email,";
    
    	$html ='
    	<div class="box">
    		<div class="box-body">
    			<div class="row">
					<div class="large-12 columns">
						<div class="table-container">
						<table id="user_table" class="original metric user">
							<thead>
								<tr>';
								// Only show the 'Action' field if user type is admin
								if($request_admin == 1) {
									$html .='
									<th class="first"><a> Select </a></th>';
								}
								$html .='
									<th><a> First Name 	</a></th>
									<th><a> Last Name	</a></th>
									<th><a> Username 	</a></th>
									<th><a> Email	 	</a></th>';
								// Only show the dealer fields if $table_requested == 'dealer'	
								if($table_requested == 'dealer') {
									$html .='
									<th><a> Dealer Name </a></th>
									<th><a> Code        </a></th>';
									$export .= "Dealer Name, Dealer Code,";
								}
								$html .='
									<th><a> Active? 	</a></th>
									<th><a> Admin?	 	</a></th>';
									$export .= "Active?, Admin?";
								// Only show the 'Team' field if $table_requested == 'sos'
								if($table_requested == 'sos') {
									$html .='
									<th><a> Team		</a></th>';
									$export .= ",Team";
								}
								$export .= ",Registered\n";
								$html .='
									<th><a> Registered  </a></th>
								</tr>
							</thead>
							<tbody>';
							// Run table row loop based on $user_array size
							for($i=0; $i<count($user_array); $i++) {
								$html .='
								<tr>';
								// Provide readable labels for 'Active', 'Admin', 'Dealer Name' table values
								$active = ($user_array[$i]['user_active'] == 1) ? 'Yes' : 'No';
								$admin  = ($user_array[$i]['user_admin'] == 1) ? 'Yes' : 'No'; 
								$dlr_name = (strlen($user_array[$i]['dealername']) > 20) ? strtoupper(substr($user_array[$i]['dealername'],0,18).'...') : strtoupper($user_array[$i]['dealername']);
								$date = date("m/d/Y", strtotime($user_array[$i]['create_date']));
								
								// Only provide the select form if user is admin type
								if($request_admin == 1) {
									$html .='
									<td class="first">
										<form class="table_form" method="POST" action="">
											<input type="hidden" value='.$user_array[$i]['user_id'].' id="update_user_id" name="update_user_id" />
											<input type="submit" id="table_user_edit_select" name="table_user_edit_select" style="margin: 0px; padding: .2em .3em;" class="tiny button radius" value="Select" />
										</form>
									</td>';
								}
									$html .='
									<td> '.$user_array[$i]['user_fname'].' </td>
									<td> '.$user_array[$i]['user_lname'].' </td>
									<td> '.$user_array[$i]['user_name'].'  </td>
									<td> '.$user_array[$i]['user_email'].' </td>';
									$export .= $user_array[$i]['user_fname'].",".$user_array[$i]['user_lname'].",".$user_array[$i]['user_name'].",".$user_array[$i]['user_email'].",";
								// Only show the dealer fields if $table_requested == 'dealer'	
								if($table_requested == 'dealer') {
									$html .='
									<td> '.$dlr_name.'					   </td>
									<td> '.$user_array[$i]['dealercode'].' </td>';
									$export .= $user_array[$i]['dealername'].",".$user_array[$i]['dealercode'].",";
								}
									$html .='
									<td> '.$active.' 					   </td>
									<td> '.$admin.'  					   </td>';
									$export .= $active.",".$admin.",";
								// Only show the 'Team' value if $table_requested == 'sos'
								if($table_requested == 'sos') {
									$html .='
									<td> '.$user_array[$i]['team_name'].'  </td>';
									$export .= $user_array[$i]['team_name'].",";
								}
								$html .='
									<td> '.$date.'</td>
								</tr>';
								$export .= $date."\n";
							}
							$html .='
							</tbody>
						</table>
						</div> <!-- end div table-container -->
					</div> <!-- end div large-12 columns -->
				</div> <!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		// Save export data as SESSION var
		$_SESSION['export_user_data'] = $export;
		
		return $html;
    }
	
	public function getAddUserTable($array) {
		/* Note: only two user types will have access to this: SOS admin and Dealer admin
		 * This code block will be used for both adding users and editing users
		 * If SOS admin, the Type and Team fields must be viewable. Dealer list must contain all dealers AND 'N/A' for 0 value.
		 * If Dealer admin, the Type, Team, and Dealer fields are not visible. These values will be added via hidden inputs
		 * $array can contain the following: 'add_user_row' (for row append), 'edit_user' (for editing users), and 'user_id' (for passing to getUserInfo() UserInfo method)
		 * Note: user types -> 1 == SOS, 3 == Dealer
		**/
		
		// Establish $sos_admin, $add_user_row, $edit_user and $user_id vars for determining user type and form function. Will dictate display of table elements.
		$sos_admin    = ($this->user_sos && $this->user_admin) ? true : false;
		$add_user_row = (isset($array['add_user_row'])) ? true : false;
		$edit_user    = (isset($array['edit_user'])) ? true : false;
		$edit_user_id = (isset($array['user_id'])) ? $array['user_id'] : null;
		
		/* If $array['edit_user'] is set, run getUserInfo() method and pass $array['user_id'] param value.
		 * Set values for form inputs.  If $edit_user == false, set all form values to null
		 * Note: active input value is not in original add user form, as it is implied that they are active when newly set up.
		 * However, the edit form needs to display this field so that a user may mark a user as inactive when needed
		**/
		if($edit_user) {
			$obj = new UserInfo($dbo=null);
			$lookup = $obj->getUserInfo(array('user_id'=>$edit_user_id));
			// There will only be one row for this query result, so use index [0] for all lookups
			$fname 		= $lookup[0]['user_fname']		;
			$lname 		= $lookup[0]['user_lname']		;
			$uname 		= $lookup[0]['user_name']		;
			$email 		= $lookup[0]['user_email']		;
			$type_id  	= $lookup[0]['type_id']			;
			$type_name	= $lookup[0]['type_name']		;
			$team_id 	= $lookup[0]['team_id']			;
			$team_name 	= $lookup[0]['team_name']		;
			$dlr_id 	= $lookup[0]['user_dealer_id']	;
			$dlr_name 	= $lookup[0]['dealername']		;
			$dlr_code 	= $lookup[0]['dealercode']		;
			$active 	= $lookup[0]['user_active']		;
			$admin 		= $lookup[0]['user_admin']		;
			
			// Set table title
			$title = 'Edit User';
			
			/* Save $uname as SESSION var so can decide if username dupe check is necessary later on 
			 * in processAddNewUsers() method.  DO NOT forget to unset later on in program
			**/
			$_SESSION['orig_edit_user_name'] = $uname;
		} else {
			$fname 		= false;
			$lname 		= false;
			$uname 		= false;
			$email 		= false;
			$type_id 	= false;
			$type_name	= false;
			$team_id 	= false;
			$team_name	= false;
			$dlr_id 	= false;
			$dlr_name	= false;
			$dlr_code	= false;
			$active 	= null ;
			$admin 		= null ;
			
			// Set table title
			$title = 'Add New User';
		}
		
		// Left style border for First Name field
		//$left_border = ($user_approve) ? null : "border-left: none;";
		
		// Build add user table
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4>'.$title.' <a id="'.$array['a_id'].'" data-text-original="Select All" data-text-swap="Unselect All" style="color: green; font-size: 15px;"> &nbsp; '.$array['link_msg'].' </a> </h4>
					</div>	
					<div class="large-12 columns">
					<form id="add_new_users_form">
						<div class="table-container"><!-- custom responsive table class -->
							<table id="add_user_table" class="original metric no_sort_arrow user">
								<thead>
									<tr>
										<th style="width: 32px;"> </th> <!-- provide space for the remove row sign or approval checkbox -->
										<th style="border-left: none;"><a>  First Name </a></th>
										<th><a>  Last Name </a></th>
										<th><a>  Username  </a></th>
										<th><a>  Email	   </a></th>
										<th><a>  Password  </a></th>';
										if ($sos_admin) {
											// Add Type, Team and Dealer fields if user is sos admin
											$html .='
											<th><a> Type   </a></th>
											<th><a> Team   </a></th>
											<th><a> Dealer </a></th>';
										}
										$html .='
										<th><a>  Admin?	 </a></th>';
										if ($edit_user) {
											$html .='
											<th><a>  Active? </a></th>';
										}
									$html .='
									</tr>
								</thead>';
					
					/* Build tbody based on user type
					 * If sos admin, provide Type and Team dropdowns. Also provide entire dealer listing plus 'N/A' dealer
					 * If dealer admin, hide Type and Team <td>'s. Provide Just 
					**/
					$submit_value = (isset($array['edit_user'])) ? 'Save Changes' : 'Submit';
					
					$submit_id = "add_user_submit";
					
					/* Set tfoot colspan based on type of table being displayed */
					
					// If user is SOS and has requested edit user form
					if ($edit_user && $sos_admin) {
						$colspan = 9;
					} elseif (!$edit_user && $sos_admin) {
						// If user is SOS and has requested add user form
						$colspan = 8;
					// If user is Dealer and has requested edit user form
					} elseif ($edit_user && !$sos_admin) {
						$colspan = 7;
						
					} elseif (!$edit_user && !$sos_admin) {
						$colspan = 6;
					}
					
					// Create hidden input field so as to dictate password validation action based on edit or add form
					$edit_user_val = ($edit_user_id) ? 1 : 0;
					$html .='
						<input type="hidden" name="edit_user_val" id="edit_user_val" value="'.$edit_user_val.'" />';
					
					// Create hidden input field to store $edit_user_id value for passing to processAddNewUser() method for user edits
					$edit_user_id = ($edit_user_id) ? $edit_user_id : null; // Set to string to get around AJAX string conversion?
					$html .='
						<input type="hidden" name="edit_user_id" id="edit_user_id" value="'.$edit_user_id.'" />';
					
					$html .='
								<tbody>
									<div id="user_req_row"><!-- set div for retrieval of ajax row add -->';
						$html2 ='
									<tr>
										<td style="width: 32px;"> <a class="fontello-cancel-circled-outline"></a> </td> <!-- the remove row placeholder -->
										<td><input type="text" name="user_fname" id="user_fname" value="'.$fname.'"/></td>
										<td><input type="text" name="user_lname" id="user_lname" value="'.$lname.'"/></td>
										<td><input type="text" name="user_uname" id="user_uname" value="'.$uname.'"/></td>
										<td><input type="text" name="user_email" id="user_email" value="'.$email.'"/></td>
										<td><input type="text" name="user_pass" id="user_pass"/></td>';
							if(!$sos_admin) {
								// If dealer admin user, provide team, type, and dealer data as hidden, preset values. Else provide all to SOS admin
								$html2 .='
										<input type="hidden" name="user_team_id" id="user_team_id" value="'.USER_TEAM.'" />
										<input type="hidden" name="user_type_id" id="user_type_id" value="'.USER_TYPE.'" />
										<input type="hidden" name="user_dealerID" id="user_dealerID" value="'.$_SESSION['dealer_id'].'" />';
							} else {
								// Instantiate UserInfo class
								$obj = new UserInfo($dbo=null);
								
								// If user is SOS admin type, provide type, team, and dealer <select> dropdowns
								$html2 .='
										<td>
											<select id="user_type_id" name="user_type_id">';
											if ($type_id) {
												$html2 .='
												<option value="'.$type_id.'#'.$type_name.'">'.$type_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
												// Get list of user types
												$array = $obj->getUserTypes();
												for($i=0; $i<count($array); $i++) {
													$html2 .='<option value="'.$array[$i]['type_id'].'#'.$array[$i]['type_name'].'">'.$array[$i]['type_name'].'</option>';
												}
											$html2 .='
											</select>
										</td>
										<td>
											<select id="user_team_id" name="user_team_id">';
											if ($team_id) {
												$html2 .='
												<option value="'.$team_id.'#'.$team_name.'">'.$team_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
												/* Get list of user teams. Note that each system should only allow their MANUF value. 
												 * [0] will always be "All" as it is the first table row
												 * use array_search to find current MANUF index value.  Allows this code to be dynamic for all manufs
												**/
												$array = $obj->getUserTeams();
												for($i=0; $i<count($array); $i++) {
												 	if($array[$i]['team_name'] == MANUF) {
												 		$team_id = $array[$i]['team_id'];
												 		$team_name = $array[$i]['team_name'];
												 	}
												}
												
												$html2 .='<option value="'.$array[0]['team_id'].'#'.$array[0]['team_name'].'">'.$array[0]['team_name'].'</option>
														  <option value="'.$team_id.'#'.$team_name.'">'.$team_name.'</option>';
												
											$html2 .='
											</select>
										</td>
										<td>
											<select id="user_dealerID" name="user_dealerID">';
											if ($dlr_id) {
												$html2 .='
												<option value="'.$dlr_id.'#'.$dlr_code.'#'.$dlr_name.'">'.$dlr_name.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
												// Make sure to show the N/A option since this is for SOS admin users who may set up a user that should not be assigned to a dealer
												$html2 .='
												<option value="0#N/A#N/A"> N/A </option>';
												
												// Get list of dealers
		 										$obj = new DealerInfo($dbo=null);
		 										$array = $obj->getDealerInfo();
		 										for($i=0; $i<count($array); $i++) {
		 											// took below out because the code and dealer name are not needed inside of the value attribute
						 							//$html .='<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';
													$html2 .='
													  <option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';						 			
						 						}
											$html2 .='
											</select>
										</td>';
							}
								$html2 .='
										<td>
											<select id="user_admin" name="user_admin">';
											// Note: actual $admin value can be 1 or 0.  Had to test something else besides $admin
											if ($admin != null) {
												$admin_label = ($admin == 1) ? 'Yes' : 'No';
												$html2 .='
												<option value="'.$admin.'">'.$admin_label.'</option>';
											} else {
												$html2 .='
												<option value="">Select...</option>';
											}
												$html2 .='
												<option value="1">Yes</option>
												<option value="0">No</option>
											</select>
										</td>';
							// Provide 'user_active' value as hidden input if adding new user (new users will have a default active value of 1)	
							if ($edit_user) {
								$active_label = ($active == 1) ? 'Yes' : 'No';
								$html2 .='
										<td>
											<select id="user_active" name="user_active">
												<option value="'.$active.'">'.$active_label.'</option>
												<option value="1"> Yes </option>
												<option value="0"> No  </option>
											</select>
										</td>';
							} else {
								$html2 .='
									<input type="hidden" id="user_active" name="user_active" value="1" />';
							}
							$html2 .='
									</tr>';
						    $html3 ='
									</div><!-- end div user_req_row -->
								</tbody>
								<tfoot>
									<tr>
										<td colspan="2" style="height: 52px;"><input type="submit" class="tiny button radius" id="'.$submit_id.'" value="'.$submit_value.'" style="margin-bottom: 0;"/></td>
										<td colspan="'.$colspan.'" style="height: 52px;"></td>	
									</tr>
								</tfoot>
								</form><!-- end form user_req_form -->
							</table>
						</div>
						</form><!-- end user_req_form -->
					</div><!-- end div large-12 columns -->
				</div> <!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		
		// If 
		return (!$add_user_row) ? $html.$html2.$html3 : $html2;
	}
	
	public function getUserRequestTable($array) {
		/* Note: $array will contain 'user_approve' true or false value to guide table build
		 * If 'user_approve' = true, must get all values from setup table and display as rows in approval table
		 * If 'user_approve' = false, input values will be empty
		**/
		
		// Establish vars for easy access
		$user_approve = (isset($array['user_approve'])) ? $array['user_approve'] : null;
		
		// Left style border for First Name field
		$left_border = ($user_approve) ? null : "border-left: none;";
		
		// Establish table title based on $array['user_approve']
		$title = ($array['user_approve']) ? "Approve Users" : "Request User Setup";
		
		// Build user setup table
		$html ='
		<div class="box">
			<div class="box-body">
				<div class="row">
					<div class="large-12 columns">
						<h4>'.$title.' <a id="'.$array['a_id'].'" data-text-original="Select All" data-text-swap="Unselect All" style="color: green; font-size: 15px;"> &nbsp; '.$array['link_msg'].' </a> </h4>
					</div>
					<div class="large-12 columns">
						<form id="user_req_form">
						<div class="table-container">
						<table id="user_request_table" class="original metric no_sort_arrow user">
							<thead>
								<tr>';
								if(!$user_approve) {
									$html .='
									<th style="width: 32px;"> </th> <!-- provide space for the remove row sign or approval checkbox -->';
								} else {
									$html .='
									<th style="width: 32px;"><a> Select </a></th>';
								}
								$html .='
									<th style="'.$left_border.'"><a>  First Name </a></th>
									<th><a>  Last Name</a></th>
									<th><a>  Username 	 </a></th>
									<th><a>  Email	 </a></th>';
									if(!$user_approve) {
										$html .='<th><a>  Password</a></th>';
									}
									$html .='
									<th><a>  Dealer	 </a></th>
									<!--<th><a>  Active? </a></th>-->
									<th><a>  Admin?	 </a></th>
								</tr>
							</thead>';
					
					// Build tbody based on 'user_approve' value
					// If $user_approve == false, show add user table
					// Else show user approval table
					if(!$user_approve) {
					
						$submit_value = 'Submit';
						
						$submit_id = "user_req_submit";
						
						$html .='
							<tbody>
								<div id="user_req_row"><!-- set div for retrieval of ajax row add -->
								<tr>
									<td style="width: 32px;"> <a class="fontello-cancel-circled-outline"></a> </td> <!-- the remove row placeholder -->
									<td><input type="text" name="user_req_fname[]" id="user_req_fname[]"/></td>
									<td><input type="text" name="user_req_lname[]" id="user_req_lname[]"/></td>
									<td><input type="text" name="user_req_uname[]" id="user_req_uname[]"/></td>
									<td>
										<input type="text" name="user_req_email[]" id="user_req_email[]"/>
										<input type="hidden" name="user_team_id[]" id="user_team_id[]" value="'.USER_TEAM.'" />
										<input type="hidden" name="user_type_id[]" id="user_type_id[]" value="'.USER_TYPE.'" />
									</td>
									<td><input type="text" name="user_req_pass[]" id="user_req_pass[]"/></td>
									<td>
										<select id="user_req_dealerID[]" name="user_req_dealerID[]">
											<option value="">Select...</option>';
											// Get list of dealers
		 									$obj = new DealerInfo($dbo=null);
		 									$array = $obj->getDealerInfo();
		 									for($i=0; $i<count($array); $i++) {
		 										// took below out because the code and dealer name are not needed inside of the value attribute
								 				//$html .='<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';
												$html .='<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';						 			
								 			}
										$html .='
										</select>
									</td>
									<!--
									<td>
										<select>
											<option value="">Select...</option>
										</select>
									
									</td>
									-->
									<td>
										<select id="user_req_admin[]" name="user_req_admin[]">
											<option value="">Select...</option>
											<option value="1">Yes</option>
											<option value="0">No</option>
										</select>
									</td>
								</tr>
								</div><!-- end div user_req_row -->
							</tbody>';
					} else {
						// If 'user_approve' == true, show all values from db retrieval in for loop so admin can read and approve.
						// Retrieve all values from db first
						$user = $this->getUserRequests();
						
						$submit_value = 'Approve Users';
						
						$submit_id = "user_req_approve_submit";
		
						$html .='
							<tbody>';
						if(count($user) > 0) {
							for($i=0; $i<count($user); $i++) {
								// Display 'Yes' or 'No' for admin value
								$admin = ($user[$i]['admin'] == 1) ? 'Yes' : 'No';
								$html .='
								<tr>
									<td style="width: 32px;"> <input type="checkbox" id="user_approve_check[]" name="user_approve_check[]" value="'.$user[$i]['id'].'" /> </td> <!-- the remove row placeholder -->
									<td>'.$user[$i]['fname'].'</td>
									<td>'.$user[$i]['lname'].'</td>
									<td>'.$user[$i]['uname'].'</td>
									<td>'.$user[$i]['email'].'</td>
									<td>'.$user[$i]['dealername'].' ('.$user[$i]['dealercode'].')</td>
									<td>'.$admin.'</td>
								</tr>';
							}
						} else {
							$html .='
								<tr>
									<td colspan="7">There are no requests pending!</td>
								</tr>';
						}
						$html .='
							</tbody>';
					}
					$html .='
							<tfoot>
								<tr>
									<td colspan="2" style="height: 52px;"><input type="submit" class="tiny button radius" id="'.$submit_id.'" value="'.$submit_value.'" style="margin-bottom: 0;"/></td>
									<td colspan="6" style="height: 52px;"></td>	
								</tr>
							</tfoot>
							</form><!-- end form user_req_form -->
						</table>
						</div> <!-- end div table-container -->
						</form><!-- end user_req_form -->
					</div><!-- end div large-12 columns -->
				</div><!-- end div row -->
			</div> <!-- end div box-body -->
		</div> <!-- end div box -->';
		return $html;
	}
	
	/* Process add new users form 
	 * Input received from AJAX $_POST values
	**/
	public function processAddNewUsers($array) {
		// Run json_decode for the following JSON-encoded array $_POST objects.  Will result in php arrays
		$post_user_fname  = json_decode($_POST['user_fname'], true)	;
		$post_user_lname  = json_decode($_POST['user_lname'], true)	;
		$post_user_uname  = json_decode($_POST['user_uname'], true)	;
		$post_user_email  = json_decode($_POST['user_email'], true)	;
		$post_user_pass   = json_decode($_POST['user_pass'],  true)	;
		$post_user_admin  = json_decode($_POST['user_admin'], true)	;
		$post_user_active = json_decode($_POST['user_active'], true);
		$post_dealer_id   = json_decode($_POST['dealer_id'],  true)	;
		$post_dealer_code = json_decode($_POST['dealer_code'],true) ;
		$post_dealer_name = json_decode($_POST['dealer_name'],true) ;
		$post_type_id     = json_decode($_POST['type_id'],    true)	;
		$post_type_name   = json_decode($_POST['type_name'],  true)	;
		$post_team_id     = json_decode($_POST['team_id'],    true)	;
		$post_team_name   = json_decode($_POST['team_name'],  true)	;
		
		// Set edit_users_val value to provide correct instructions for handling password field.
		// If edit_users_val == true (edit form being used) and password field is blank, proceed with update query which does not include pass field
		// If edit_users_val == false (add form being used), always include the password field in the update query
		// Also store $edit_user_id value for passing to updateUser() method
		$edit_user_val = ($_POST['edit_user_val'] == 1) ? true : false;
		$edit_user_id  = ($_POST['edit_user_id'] > 0)   ? $_POST['edit_user_id'] : false;
	
		// Run through each POST array and save to param variable to be used with insertUser() method.
		// Also save array values so that they may be used in email confirmation
		$user_fname = array();
		$params = array();
		$i=0;
		foreach($post_user_fname as $fname) {
			$user_fname[$i] = $fname;
			$params[$i]['fname'] = $fname;
			$i += 1;
		}
		
		$user_lname = array();
		$i=0;
		foreach($post_user_lname as $lname) {
			$user_lname[$i] = $lname;
			$params[$i]['lname'] = $lname;
			$i += 1;
		}
		
		$user_uname = array();
		$i=0;
		foreach($post_user_uname as $uname) {
			$user_uname[$i] = $uname;
			$params[$i]['uname'] = $uname;
			$i += 1;
		}
		
		$user_email = array();
		$i=0;
		foreach($post_user_email as $email) {
			$user_email[$i] = $email;
			$params[$i]['email'] = $email;
			$i += 1;
		}
		
		// Add additional logic based on $edit_user_val
		if (($edit_user_val && $post_user_pass[0] != "false") || !$edit_user_val) {
			// Set $pass_entered value to true so that updateUser() method correctly includes " and user_pass = ? " statement
			$pass_entered = true;
			
			$user_pass = array();
			$i=0;
			foreach($post_user_pass as $pass) {
				// Use the below values to send along with confirmation email so that personnel has a record of actual passwords
				$user_pass[$i] = $pass;
				// Save the hashed pass
				$params[$i]['pass_hash'] = password_hash($pass, PASSWORD_BCRYPT);
				$i += 1;
			}
		} else {
			$pass_entered = false;
		}
		
		$user_dealer_id = array();
		$i=0;
		foreach($post_dealer_id as $dealer_id) {
			$user_dealer_id[$i] = $dealer_id;
			$params[$i]['dealerID'] = $dealer_id;
			$i += 1;
		}
		
		$user_dealer_code = array();
		$i=0;
		foreach($post_dealer_code as $dealer_code) {
			$user_dealer_code[] = $dealer_code;
			$i += 1;
		}
		
		$user_dealer_name = array();
		$i=0;
		foreach($post_dealer_name as $dealer_name) {
			$user_dealer_name[] = $dealer_name;
			$i += 1;
		}
		
		$user_admin = array();
		$i=0;
		foreach($post_user_admin as $admin) {
			$user_admin[$i] = $admin;
			$params[$i]['admin'] = $admin;
			$i += 1;
		}
		
		$user_active = array();
		$i=0;
		foreach($post_user_active as $active) {
			$user_active[$i] = $active;
			$params[$i]['active'] = $active;
			$i += 1;
		}
		
		$user_team_id = array();
		$i=0;
		foreach($post_team_id as $team_id) {
			$user_team_id[$i] = $team_id;
			$params[$i]['user_team_id'] = $team_id;
			$i += 1;
		}
		
		$user_team_name = array();
		$i=0;
		foreach($post_team_name as $team_name) {
			$user_team_name[$i] = $team_name;
			$params[$i]['user_team_name'] = $team_name;
			$i += 1;
		}
		
		$user_type_id = array();
		$i=0;
		foreach($post_type_id as $type_id) {
			$user_type_id[$i] = $type_id;
			$params[$i]['user_type_id'] = $type_id;
			
			// Also add 'user_id' field param.  Used for registered_by field
			$params[$i]['user_id'] = $_SESSION['user']['user_id'];
			
			// Note that create_date will be automatically inserted inside of insertUser() method. Do not set here.
			$i += 1;
		}
		
		$user_type_name = array();
		$i=0;
		foreach($post_type_name as $type_name) {
			$user_type_name[$i] = $type_name;
			$params[$i]['user_type_name'] = $type_name;
			$i += 1;
		}
		
		// If edit_user_val == true and user_name val has changed, OR edit_user_val == false, run checkUsernameDupe() method 
		// Before inserting into db, you must make sure that usernames do not already exist.  If they do, return error msg
		if (($edit_user_val	&& ($edit_user_id != false) && ($user_uname[0] != $_SESSION['orig_edit_user_name'])) || !$edit_user_val) {
			// Note: $user_uname is an array
			if($error_stmt = $this->checkUsernameDupe(array('user_uname'=>$user_uname))) {
				return $error_stmt;
			}
		}
		
		// Unset $_SESSION['orig_edit_user_name'] to prevent username dupe check program errors
		if(isset($_SESSION['orig_edit_user_name'])) {
			unset($_SESSION['orig_edit_user_name']);
		}
		
		// Instantiate UserInfo class for access to insertUser() and updateUser() UserInfo methodsb
		$obj = new UserInfo($dbo=null);
		
		// Insert user(s) into the user table if $edit_user_val == false.  Else run update statement
		if(!$edit_user_val) {
			if($obj->insertUser(array('users'=>$params))) {
				$this->emailAddNewUsersConfirm(array('fname'=>$user_fname,'lname'=>$user_lname,'uname'=>$user_uname,'email'=>$user_email,'pass'=>$user_pass,'dealer_id'=>$user_dealer_id,'dealername'=>$user_dealer_name,'dealercode'=>$user_dealer_code,'admin'=>$user_admin,'type_name'=>$user_type_name,'team_name'=>$user_team_name));
				return true;		
			} else {
				return false;
			}
		} else {
			if($obj->updateUser(array('params'=>$params, 'pass_entered'=>$pass_entered, 'edit_user_val'=>$edit_user_val, 'edit_user_id'=>$edit_user_id))) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	public function checkUsernameDupe($array) {	
		// Instantiate UserInfo class object for access to getUserInfo() method
		$obj = new UserInfo($dbo=null);
		
		// Establish easy access to $user_uname array element.  Note: 'user_uname' is an array
		$user_uname = $array['user_uname'];
		
		// Establish $user_dupe array for holding db user_name results (will drive error msg if username(s) already exist)
		$user_dupe = array();
		
		// Check for username duplicates.  If db returns result, $result will contain an array of user info.  Else will be false (boolean)
		for($i=0; $i<count($user_uname); $i++) {
			$result = $obj->getUserInfo(array('user_name'=>$user_uname[$i]));
			if(count($result) > 0) {
				$user_dupe[] = $user_uname[$i];
			}	
		}
		
		// Return username dupe error statement if there were any duplicates. User should have already been alerted by JS.
		$error_stmt = "";
		if(count($user_dupe) > 0) {
			$error_stmt .= "error_dupe*The following errors have occurred: \n\n";
			foreach ($user_dupe as $username) {
				$error_stmt .= "The username ".$username." has already been taken. Please choose another. \n";
			}
			$error_stmt .= "\nPlease correct the errors and try again.";
			//echo $error_stmt;
			return $error_stmt;
		} else {
			return false;
		} 
	}
	
	// Email current user new add user setup confirmation containing all new user info
	public function emailAddNewUsersConfirm($array) {
		// Build each user block message
		$info = "";
		for($i=0; $i<count($array['fname']); $i++) {
			// Display correct admin label
			$admin = ($array['admin'][$i] == 1) ? "Yes" : "No";
			
			// Display correct dealer label
			$dlr   = ($array['dealer_id'][$i] == 0) ? "N/A" : $array['dealername'][$i]." (".$array['dealercode'][$i].")";
			
			// Build main info block for each user
			$info .= "Name: ".$array['fname'][$i]." ".$array['lname'][$i]."\n";
			$info .= "Username: ".$array['uname'][$i]."\n";
			$info .= "Password: ".$array['pass'][$i]."\n";
			$info .= "Email: ".$array['email'][$i]."\n";
			$info .= "Dealer: ".$array['dealername'][$i]." (".$array['dealercode'][$i].")\n";
			$info .= "Admin: ".$admin."\n";
			$info .= "User Type: ".$array['type_name'][$i]."\n";
			$info .= "User Team: ".$array['team_name'][$i]."\n\n";
		}
	
		// Build email message
		$subject = MANUF.' Online Reporting New User Setup Confirmation';
		$msg = "Dear ".$_SESSION['user']['user_fname'].",\n\n";
		$msg.= "You have successfully added the following users to the ".MANUF." Online Reporting System: \n\n";
		$msg.= $info;
		$msg.= "Thank you,\n";
		$msg.= "SOS admin";
		
		// Mail confirm to admin user who approved the request(s)
		mail($_SESSION['user']['user_email'], $subject, $msg);
	}
	
	/* Create array of approval ids based on user selection. Use array_search fn to build user_approved array.
	 * Insert approved users into user table, and delete records in user_setup_request table based on row_ids[]
	 * Email approval confirmation to admin and each setup requestor
	**/
	public function processUserSetupApprovals($array) {
		// $array contains the following: 'row_ids'
		
		$row_ids = array();
		foreach($_POST['user_approve_check'] as $id) {
			$row_ids[] = $id;
		}
		
		// Using row_id POST values, search through $_SESSION['user_requests'] global array to find matching data.
		// Add each match to $users_approved array.
		// This will be used to populate field values & params, and execute the db insert instructions
		$users = $_SESSION['user_requests'];
		$users_approved = array();
		
		for($i=0; $i<count($users); $i++) {
			foreach($users as $key=>$value) {
				if(array_search($row_ids[$i], $value)) {
					$users_approved[] = $users[$key];
				}
			}
		}
		
		// Insert users into user table
		$obj = new UserInfo($dbo=null);
		if(!$obj->insertUser(array('users'=>$users_approved))) {
			$error = true;
		} else {
		// INSERT successful. Now delete rows from user_setup_request table
			if(!$obj->deleteUserSetupRequest(array('row_ids'=>$row_ids))) {
				$error = true;
			} else {
				$error = false;
			}
		}
		
		// If there were no errors, email confirm to admin user who approved the users
		if(!$error) {
			// Now email to admin user who approved the users
			$this->emailUserSetupApprovalConfirmAdmin(array('users_approved'=>$users_approved));
		}
		
		// Now email each original requestor confirmation of setup approval
		$this->emailUserSetupApprovalConfirmRequestor(array('users_approved'=>$users_approved));
		
		// If INSERT and DELETE operations were successful, return true
		return (!$error) ? true : false;
	}
	
	// Email user setup approvals to personnel who approved users
	public function emailUserSetupApprovalConfirmAdmin($array) {
		$users_approved = $array['users_approved'];
		
		// Build each user block message
		$approval_info = "";
		for($i=0; $i<count($users_approved); $i++) {
			$u = $users_approved[$i];
			$admin = ($u['admin'] == 1) ? "Yes" : "No";
			$approval_info .= "Name: ".$u['fname']." ".$u['lname']."\n";
			$approval_info .= "Username: ".$u['uname']."\n";
			$approval_info .= "Email: ".$u['email']."\n";
			$approval_info .= "Dealer: ".$u['dealername']." (".$u['dealercode'].")\n";
			$approval_info .= "Admin: ".$admin."\n\n";
		}
	
		// Build email message
		$subject = MANUF.' Online Reporting User Approval Notification';
		$msg = "Dear ".$_SESSION['user']['user_fname'].",\n\n";
		$msg.= "You have successfully approved the following user setup requests: \n\n";
		$msg.= $approval_info;
		$msg.= "Thank you,\n";
		$msg.= "SOS admin";
		
		// Mail confirm to admin user who approved the request(s)
		mail($_SESSION['user']['user_email'], $subject, $msg);
	}
	
	// Email each approved user to the original requestor after admin has approved them
	public function emailUserSetupApprovalConfirmRequestor($array) {
		$u = $array['users_approved'];
		
		// Run through each approved user and email to the 'req_by_email' field
		foreach($u as $app) {
			// Set yes or no string for admin value
			$admin = ($app['admin'] == 1) ? "Yes" : "No";
			
			// Build email subject line
			$subject = MANUF.' Online Reporting User Approval Notification';
			
			// Build user information msg
			$msg = "Dear User,\n\n";
			$msg.= "The following user setup request has been officially approved: \n\n";
			$msg.= "Name: "		.$app['fname']." ".$app['lname']."\n";
			$msg.= "Username: "	.$app['uname']."\n";
			$msg.= "Email: "	.$app['email']."\n";
			$msg.= "Dealer: "	.$app['dealername']." (".$app['dealercode'].")\n";
			$msg.= "Admin: "	.$admin."\n\n";
			$msg.= "Thank you,\n";
			$msg.= "SOS Admin";
			
			// Mail message to user
			mail($app['req_by_email'],$subject,$msg);
		}
	}
	
	/* Could not get the following to work from above code: 
	 * $('table#user_request_table tbody').append($('#user_req_row', returndata).html());
	 * So this code is duplicated.  Find a remedy to the problem to adhere to DRY principle
	**/
	public function addUserRequestRow() {
		$html ='
		<tr>
			<td style="width: 32px;"> <a class="fontello-cancel-circled-outline"></a> </td> <!-- the remove row placeholder -->
			<td><input type="text" name="user_req_fname[]" id="user_req_fname[]"/></td>
			<td><input type="text" name="user_req_lname[]" id="user_req_lname[]"/></td>
			<td><input type="text" name="user_req_uname[]" id="user_req_uname[]"/></td>
			<td>
				<input type="text" name="user_req_email[]" id="user_req_email[]"/>
				<input type="hidden" name="user_team_id[]" id="user_team_id[]" value="'.USER_TEAM.'" />
				<input type="hidden" name="user_type_id[]" id="user_type_id[]" value="'.USER_TYPE.'" />
			</td>
			<td><input type="text" name="user_req_pass[]" id="user_req_pass[]"/></td>
			<td>
				<select id="user_req_dealerID[]" name="user_req_dealerID[]">
					<option value="">Select...</option>';
					// Get list of dealers
					$obj = new DealerInfo($dbo=null);
					$array = $obj->getDealerInfo();
					for($i=0; $i<count($array); $i++) {
						// took below out because the code and dealer name are not needed inside of the value attribute
		 				//$html .='<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';
						$html .='<option value="'.$array[$i]['dealerID'].'#'.$array[$i]['dealercode'].'#'.$array[$i]['dealername'].'">'.$array[$i]['dealername'].'('.$array[$i]['dealercode'].')</option>';						 			
		 			}
				$html .='
				</select>
			</td>
			<td>
				<select id="user_req_admin[]" name="user_req_admin[]">
					<option value="">Select...</option>
					<option value="1">Yes</option>
					<option value="0">No</option>
				</select>
			</td>
		</tr>';
		return $html;
	}
	
	// Recieve user setup requests, and insert into user_setup_request table
	public function processUserSetupRequest() {
		// Run json_decode for the following JSON-encoded array $_POST objects.  Will result in php arrays
		$post_dealer_id = json_decode($_POST['dealer_id'], true);
		$post_dealer_code = json_decode($_POST['dealer_code'], true);
		$post_dealer_name = json_decode($_POST['dealer_name'], true);
		//return 'dealer_id: '.var_dump($post_dealer_id).'dealer_code: '.var_dump($post_dealer_code);
	
		// Run through each POST array and save to array variable
		$user_fname = array();
		$params = array();
		$i=0;
		foreach($_POST['user_req_fname'] as $fname) {
			$user_fname[$i] = $fname;
			$params[$i][0] = $fname;
			$i += 1;
		}
		
		$user_lname = array();
		$i=0;
		foreach($_POST['user_req_lname'] as $lname) {
			$user_lname[$i] = $lname;
			$params[$i][1] = $lname;
			$i += 1;
		}
		
		$user_uname = array();
		$i=0;
		foreach($_POST['user_req_uname'] as $uname) {
			$user_uname[$i] = $uname;
			$params[$i][2] = $uname;
			$i += 1;
		}
		
		$user_email = array();
		$i=0;
		foreach($_POST['user_req_email'] as $email) {
			$user_email[$i] = $email;
			$params[$i][3] = $email;
			$i += 1;
		}
		
		$user_pass = array();
		$i=0;
		foreach($_POST['user_req_pass'] as $pass) {
			// Use the below values to send along with confirmation email so that personnel has a record of actual passwords
			$user_pass[$i] = $pass;
			// Save original pass, as will be inserted into table for future retrieval. Hash the password before inserting into table
			$params[$i][4] = $pass;
			$params[$i][5] = password_hash($pass, PASSWORD_BCRYPT);
			$i += 1;
		}
		
		$user_dealer_id = array();
		$i=0;
		//foreach($_POST['dealer_id'] as $dealer_id) {
		foreach($post_dealer_id as $dealer_id) {
			$user_dealer_id[$i] = $dealer_id;
			$params[$i][6]   = $dealer_id;
			$i += 1;
		}
		
		$user_dealer_code = array(); // Note: need to finish this by providing the dealercode from form submit
		$i=0;
		foreach($post_dealer_code as $dealer_code) {
			$user_dealer_code[] = $dealer_code;
			$params[$i][7] = $dealer_code;
			$i += 1;
		}
		
		$user_dealer_name = array(); // Note: need to finish this by providing the dealercode from form submit
		$i=0;
		foreach($post_dealer_name as $dealer_name) {
			$user_dealer_name[] = $dealer_name;
			$params[$i][8] = $dealer_name;
			$i += 1;
		}
		
		$user_admin = array();
		$user_active= array();
		$i=0;
		foreach($_POST['user_req_admin'] as $admin) {
			$user_admin[$i] = $admin;
			$params[$i][9] = $admin;
			
			//$user_active[$i][7] = 1; *Only need to save active param value, as will only be used for db insert
			$params[$i][10] = 1;
			$i += 1;
		}
		
		$user_team_id = array();
		$i=0;
		foreach($_POST['user_team_id'] as $team_id) {
			$user_team_id[$i] = $team_id;
			$params[$i][11] = $team_id;
			$i += 1;
		}
		
		$user_type_id = array();
		$user_approved = array();
		$i=0;
		foreach($_POST['user_type_id'] as $type_id) {
			$user_type_id[$i] = $type_id;
			$params[$i][12] = $type_id;
			
			// Also add 'approved' field false param
			$user_approved[$i] = 0;
			$params[$i][13] = 0;
			
			// Also add 'user_id' field param.  Change this after login process vinal revamp.  Only save param value
			$params[$i][14] = 12;
			
			// Also add 'req_by_name' field param.  This will be used for reference during approval to include orig requestor name
			$params[$i][15] = $_SESSION['user']['user_fname']." ".$_SESSION['user']['user_lname'];
			
			// Alse add 'req_by_email' field param.  This will be used for sending confirm to original requestor
			$params[$i][16] = $_SESSION['user']['user_email'];
			
			// Add create_date with php date function
			$params[$i][17] = date('Y-m-d H:i:s');
			$i += 1;
		}
		
		// Now INSERT all values into user_setup_req table
		$stmt = "INSERT INTO user_setup_request 
						(fname,   lname,  uname,  email,  pass_orig,  pass_hash,  dealerID,  dealercode, 
						 dealername,  admin,  active,  user_team_id,  user_type_id,  approved,  user_id, 
						 req_by_name, req_by_email,  create_date)
				 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		// Prepare statement once
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($this->dbo->errorInfo(), __LINE__, __FILE__);
		}
					 
		// Execute statement for every array iteration.  $params is an array of arrays.  Reference each array individually.
		$sql_errors = array();
		for($i=0; $i<count($user_fname); $i++) {
			if(!($stmt->execute($params[$i]))) {
				sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
				$sql_error = true;
			} else {
				$sql_error = false;
			}
		}
		
		// If INSERTs are successful, email setup request confirm to requestor, as well as all admin users
		if(!$sql_error) {
			$this->emailUserSetupRequestConfirm(array('user_fname'=>$user_fname,'user_lname'=>$user_lname,'user_uname'=>$user_uname,'user_email'=>$user_email,'user_pass'=>$user_pass,'user_dealer_name'=>$user_dealer_name,'user_dealer_code'=>$user_dealer_code,'user_admin'=>$user_admin));
		}
		
		// Return true if there were no errors
		return ($sql_error) ? false : true;
	}
	
	// Email confirmation to current user and all admin when user setup requests have been submitted successfully.
	// Called inside of processUserSetupRequest() method
	public function emailUserSetupRequestConfirm($array) {
		// Establish easy access to variables
		$user_fname       = $array['user_fname'];
		$user_lname       = $array['user_lname'];
		$user_uname       = $array['user_uname'];
		$user_email       = $array['user_email'];
		$user_pass        = $array['user_pass'];
		$user_dealer_name = $array['user_dealer_name'];
		$user_dealer_code = $array['user_dealer_code'];
		$user_admin       = $array['user_admin'];
		
		// Build blocks of user info from above inputs.  There will be one for each requested user.
		$setup_info = "";
		for($i=0; $i<count($user_fname); $i++) {
			// Set admin to yes or no
			$admin = ($user_admin[$i] == 1) ? "Yes" : "No";
			
			$setup_info .= "Name: "		.$user_fname[$i]." ".$user_lname[$i].		"\n";
			$setup_info .= "Username: "	.$user_uname[$i].					 		"\n";
			$setup_info .= "Email: "	.$user_email[$i].					 		"\n";
			$setup_info .= "Password: "	.$user_pass[$i].					 		"\n";
			$setup_info .= "Dealer: "	.$user_dealer_name[$i]." (".$user_dealer_code[$i]. ")\n";
			$setup_info .= "Admin: "	.$admin.						   		  "\n\n";
		}	
		
		// Finish the email message, adding the above $setup_info block to the email body
		$subject = MANUF.' Online Setup Notification';
		$msg  = "Dear User,\n\n";
		$msg .= "The following requests for user setups have been submitted for approval: \n\n";
		$msg .= $setup_info;
		$msg .= "Request submitted by: ".$_SESSION['user']['user_fname']." ".$_SESSION['user']['user_lname']."\n\n";
		$msg .= "Thank you,\n";
		$msg .= "SOS admin";
		
		// Now create array of email addresses to send setup confirm/notification to (sender, and all admin personnel).
		// Get SOS admin users from the db and add to $admin array using foreach
		$obj = new UserInfo($dbo=null);
		$users = $obj->getUserInfo(array('user_admin'=>1, 'user_type_id'=>1));
		
		// Now run foreach to get each admin email, and add to $email_array
		$email_array = array();
		foreach($users as $user) {
			foreach($user as $key=>$value) {
				if($key == 'user_email') {
					$email_array[] = $value;
				}
			}
		}
		
		// Now email confirm to current session user, as well as every admin user.  Add user session email to admin email array first.
		$email_array[] = $_SESSION['user']['user_email'];
		foreach($email_array as $email) {
			mail($email, $subject, $msg);
		}
	}
	
	/* This function sends an email containing a reset password link to user requesting forgot password link
	 * will execute after user submits email address on the reset_password page 
	 * returns true or false based on successful user search and pass_rest table insert
	**/
	public function emailPassResetLink($array) { // $user
		// First, lookup the email address user entered to see if it exists.  Make sure user you specify active user.
		$obj = new UserInfo($dbo=null);
		$user = $obj->getUserInfo(array('user_email'=>$array['user_email'], 'user_active'=>1));

		// If there was no match, return false. Also make sure that there are no duplicates.
		if(count($user) == 0 || count($user) > 1) {
			echo 'count returned false';
			return false;
		}
 		
 		// Save user_id and user_name from user query
		$user_id = $user[0]['user_id'];
		$params = array($user_id);
		$params[] = $user[0]['user_name'];
		
		// Create unique hash to append to URL and add to $params
		$hash = uniqid("",TRUE);
		$params[] = $hash;
		
		// Add reset_active param value of true (boolean)
		$params[] = 1;
		
		// Add current date and time to $params
		$params[] = date("Y-m-d H:i:s");
		
		// Build entire URL string to provide as link in reset email
		$urlHash = urlencode($hash);
		$site = 'https://'.BASE_URL;
		$resetPage = "index.php";
		$fullUrl = $site.$resetPage."?user=".$urlHash;
		
		echo 'user_id: ' + $user_id + 'user_name: ' + $params[1] + ' hash: ' + $hash + 'fullUrl: ' + $fullUrl;
		
		// Insert reset info into reset_pass table. This is the only place query will be used.  Keep in this method.
		// Note that email_id field is actually the user_id value from user table
		$stmt = "INSERT INTO reset_pass (email_id, user_name, pass_key, reset_active, create_date)
				 VALUES (?,?,?,?,?)";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		// If INSERT is successful, email user reset password link
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			// Build email message
			$to = $user[0]['user_email'];
			$subject = "Password Request Confirmation for ".MANUF." Online Reporting Site";
			$msg = "Dear ".$user[0]['user_fname'].", \n\n";
			$msg.= "You have requested a password reset for the ".MANUF." Online Reporting System.\n\n";
			$msg.= "Please click on this link to reset your password:\n";
			$msg.= $fullUrl."\n\n";
			$msg.= "Thank you,\n";
			$msg.= "SOS Admin";
			//echo 'msg: '.$msg;
			// Make sure that email was successful
			return (mail($to,$subject,$msg)) ? true : false;
		}
	} // end function emailPassResetLink
	
	public function validateResetPassData($array) {
		// Get email values that user entered
		$pass1 = $array['pass1'];
 		$pass2 = $array['pass2'];
 		
 		// Make sure that both emails entered by user match.  JS should take care of this first.
		if ($pass1 != $pass2) { 
			return false;
		}
		
		// Note that hash should == pass_key field value in reset_pass table. $array['hash'] comes from SESSION var set in index.php
		$hash = $array['hash']; 
		$params = array($hash);
		
		// reset_active field value has to be 1 (true)
		$params[] = 1;
		 
		// Email value comes from form entry
		$user_email = $array['user_email'];
		$params[] = $user_email;
		
		// Create query instruction
		$stmt = "SELECT a.user_id, a.user_name, a.user_fname, a.user_email, b.id FROM user a
				 LEFT JOIN reset_pass b ON(a.user_id = b.email_id)
				 WHERE b.pass_key = ? AND b.reset_active = ? AND a.user_email = ? ";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		// If one row result is found, execute password update with _resetPass() method. Else return false.
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
			// There should be 5 results in the array if find user was successful
			if(count($user_info) == 5) {
				// Use query results to reset password in user table using _resetPass() method. Send email confirm to user.
				if($this->_resetPass(array('user_id'=>$user_info['user_id'],'pass'=>$pass1))) {
					// Update reset_pass row just used to inactive status (reset_active = 0)
					// Since user will refresh page upon successful pass update, set SESSION['error'] in here if necessary
					if(!$this->_updateResetPassActive(array('id'=>$user_info['id']))) {
						$_SESSION['error'][] = 'Password reset successful, but please contact Admin with \'reset_pass active error\' message. Thank you.';
					}
					
					// Send email to user confirming reset and reminding them of their username. Provide link to main login
					$to = $user_email;
					$subject = "Password Reset Confirmation for ".MANUF." Online Reporting Site";
					$msg = "Dear ".$user_info['user_fname'].", \n\n";
					$msg.= "Your password for the ".MANUF." online reporting system has been successfully reset.\n";
					$msg.= "For your reference, the following is your username: ".$user_info['user_name']."\n\n";
					$msg.= "You may proceed to the main login page here: \n";
					$msg.= INDEX_FULL."\n\n";
					$msg.= "Thank you,\n";
					$msg.= "SOS Admin";
					
					// Email confirmation to user and then return true. Notify user if email fn unsuccessful. Don't return false though
					if(!mail($to,$subject,$msg)) {
						$_SESSION['error'][] = 'Notice: email confirmation failed';
					}
					return true;
				} else {
					return false;
				}
			// Do not attempt to reset password if more than one identical user is found.  This should never execute.
			} elseif (count($user_info) > 5) {
				$_SESSION['error'][] = 'Multiple user error encountered.  Please contact the administrator.';
				return false;
			// This will execute if count($user_info == 0)
			} else {
				$_SESSION['error'][] = 'Password reset failed. The requested user was not found.';
				return false;
			}
		}
	} // end function validateResetPassEmail
	
	// Update user table with new reset password
	private function _resetPass($array) {
		$new_pass = password_hash($array['pass'], PASSWORD_BCRYPT);
		$params   = array($new_pass);
		$user_id  = $array['user_id'];
		$params[] = $user_id;
		
		
		// Create query instruction
		$stmt = "UPDATE user SET user_pass = ?
				 WHERE user_id = ?";
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	} // end _resetPass method
	
	/* This function executes after the _resetPass has executed successfully. 
	 * Updates reset_pass reset_active field to 0 so as to prevent future duplicate row results for reset_pass SELECT
	**/
	private function _updateResetPassActive($array) {
		$stmt = "UPDATE reset_pass SET reset_active = 0 WHERE id = ?";
		
		$params[] = $array['id'];
		
		// Prepare and execute statement
		if(!($stmt = $this->dbo->prepare($stmt))) {
			sendErrorNew($dbo->errorInfo(), __LINE__, __FILE__);
			return false;
		}
		
		if(!($stmt->execute($params))) {
			sendErrorNew($stmt->errorInfo(), __LINE__, __FILE__);
			return false;
		} else {
			return true;
		}
	} // end _updateResetPassActive method
}
?>