				<li class="divider"></li>
				<?php
				if (isset($_SESSION['user'])) {
					echo'<li class="has-dropdown">
						 <a>View Metrics</a>
							<ul class="dropdown">
							<li><label>View Metrics</label></li>
							<li><a href="metrics.php">'.constant('ENTITY').' '.$dealercode.'</a></li>
							<li><a href="metrics_all_dealers.php">All '.constant('ENTITY').'s (list)</a></li>
							<li><a href="metrics_all_summary.php">All '.constant('ENTITY'). 's</a></li>
							</ul>
						</li>';
				} else {
					echo'<li><a href="metrics.php">View Metrics</a></li>';
				}
				if (isset($_SESSION['user'])) {
					echo'<li class="divider"></li>
						 <li><a href="stats.php">View Statistics</a></li>';
				}
				?>
				<li class="divider"></li>
				<li><a id="viewall_ros_link" href="#">View All ROs</a></li>
				<li class="divider"></li>
				<?php
					if (isset($_SESSION['user'])) {
					   echo'<li><a id="enter_ros_link" "href="#">Enter ROs</a></li>
					        <li class="divider"></li>';
					}
				?>
				<?php
				if (isset($_SESSION['user'])) {
					echo'<li><a href="system_summary_welr.php">Surveys Summary</a></li>
						 <li class="divider"></li>';
				}
				if (isset($_SESSION['user'])) {
					echo'<li><a href="manage_users_welr.php">Admin</a></li>
						 <li class="divider"></li>';
				}
				
				if (isset($_SESSION['user'])) {
					echo'<li><a href="manage_users_non_sos.php">Admin</a></li>
						 <li class="divider"></li>';
				}
				?>
				<li><a href="contact_us.php">Contact Us</a></li>
				<li class="divider"></li>
				<li><a href="logout.php">Logout</a></li>
				<li class="divider"></li>