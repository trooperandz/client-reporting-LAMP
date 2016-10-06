<?php
/* -------------------------------------------------------------------------------------*
   Program: menubar_enterrofoundation_welr_test.php

   Purpose: Provide the menu bar for enterrofoundation_welr_test.php

   History:
    Date				Description												by
	04/17/2015 (est.)	Adapted original menubar_enterrofoundation_welr.php		Matt Holland
						to menubar_enterrofoundation_welr_test.php
	09/30/2015			Added dealer name to dealer selection menu dropdown,	Matt Holland
						edited query to contain a LEFT JOIN clause and
						to return only dealers that are reporting instead of
						ALL dealers in the database
 ---------------------------------------------------------------------------------------*/
?>

<div class="fixed">
<nav class="top-bar" data-topbar>
	<ul class="title-area">
		<li class="name"><h1><a><?php echo MANUF.' - '.ENTITY.' '.$_SESSION['dealer_code']; ?></a></h1></li>
		<li class="toggle-topbar menu-icon"><a><span>Menu</span></a></li>
	</ul>
	<section class="top-bar-section">
    <ul class="left">
		<li class="divider"></li>
		<?php
		// If user is manufacturer, then do not show the 'Enter Cycle Times' link at all
		 if(isset($_SESSION['manuf_user']) && $_SESSION['manuf_user'] == 0) {
			echo'<li><a data-reveal-id="cycle_times_modal" style="color: #46BCDE; font-weight: bold;">Enter Cycle Times &raquo </a></li>
			     <li class="divider"></li>';
		 }
		?>
		<div id="cycle_times_modal" class="small reveal-modal" style="background-color: #ffffff;" data-reveal>
			<div class="row">
				<div class="medium-12 large-12 columns">
					<div class="medium-1 large-1 columns">
						<p> </p>
					</div>
					<div class="medium-10 large-10 columns">
						<form method="post" id="cycletime_form" action="cycletime_process.php">
							<h6 style="color: #008cba; text-align: center;">Cycle Time Sample - <?php echo constant('ENTITY').' '.$dealercode;?></h6>	
							<hr style="margin-top: 0px; margin-bottom: 0px; border-color: #909090;">
							<br>
							<label> Sample Date: <small class="form_error" style="color: red; font-size: 13px;" id="sample_date_error">*Incorrect date format</small>
							<input type="text"  name="sample_date" id="sample_date" placeholder="Format: mm/dd/yyyy">
							</label>
							<label> Reception Time: <small class="form_error" style="color: red; font-size: 13px;" id="reception_time_error">*Please enter as MM:SS</small>
							<input type="text" class="time_stamp"  name="reception_time" id="reception_time" placeholder="Format: mm:ss">
							</label>
							<label> ROC Completion Time: <small class="form_error" style="color: red; font-size: 13px;" id="roc_time_error">*Please enter as MM:SS</small>
							<input type="text" class="time_stamp"  name="roc_time" id="roc_time" placeholder="Format: mm:ss">
							</label>
							<label> Total Bay Time: <small class="form_error" style="color: red; font-size: 13px;" id="bay_time_error">*Please enter as MM:SS</small>
							<input type="text" class="time_stamp"  name="bay_time" id="bay_time" placeholder="Format: mm:ss">
							</label>
							<label> Total Cycle Time: <small class="form_error" style="color: red; font-size: 13px;" id="cycle_time_error">*Please enter as MM:SS</small>
							<input type="text" class="time_stamp"  name="cycle_time" id="cycle_time" placeholder="Format: mm:ss">
							</label>
							<div id="cycletime_response"></div>
							<input type="submit" id="submit" class="tiny button radius" value="Submit">
						</form>
					</div>
					<div class="medium-1 large-1 columns">
						<p>  </p>
					</div>
				</div>
			</div>
		    <a class="close-reveal-modal" style="font-size: 19px;">&#215;</a>
		</div>
		<li class="divider"></li>
		<?php 
		/*
		if (isset($_SESSION['sos_user']) && $_SESSION['sos_user'] == 1
		 || isset($_SESSION['manuf_user']) && $_SESSION['manuf_user'] == 1) {*/
		echo'
		<li class="has-form">
		<form method="post" action="dealercodeswitch_process_welr.php">
			<div class="row collapse">
				<div class="small-6 medium-8 large-8 columns">
						<select class="collapse_select" id="dealercodechange" name="dealercodechange">
							<option value="">Select '.constant('ENTITY').' </option>';
							$dlr_obj = new DealerInfo($dbo);
							$dlr = $dlr_obj->getDealerInfo();
							for ($i=0; $i<sizeof($dlr); $i++) {
								echo '<option style="width: auto;" value='.$dlr[$i]['dealercode'].'>'.strtoupper(substr($dlr[$i]['dealername'], 0, 13)).'... ('.$dlr[$i]['dealercode'].')</option>';
							}
						echo'
						</select>	
				</div>
				<div class="small-2 medium-4 large-4 columns">
					<input type="submit" value="Go" class="alert button postfix collapse_submit" id="dealercodesubmit" name="dealercodesubmit" >
				</div>
				<div class="small-4 columns">
					
				</div>
			</div>
		</form>
		</li>
		<li><a class="concierge_label" style="color: #D34836; font-weight: bold;">User ID: '.$_SESSION['user']['user_name'].'</a></li>';
		/*
		} else {
			echo'<li><a class="concierge_label" style="color: #D34836; font-weight: bold;">User ID: '.$username.'</a></li>';
		}*/
		?>
    </ul>
    <ul class="right">
		<li class="divider"></li>
		<li class="has-dropdown">
			<a>Welcome, <?php echo $_SESSION['user']['user_fname'];?> </a>
			<ul class="dropdown">
				<li class="has-dropdown">
				<?php
				include('menubar_sidecontents_welr.php');
				?>
			</ul>
        </li>
    </ul>
    </section>
</nav> 
</div>