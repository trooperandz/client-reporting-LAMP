<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RO Survey - <?php echo constant('MANUF');?></title>
	<link rel="icon" href="../img/sos_logo3.ico" type="image/x-icon" />
    <link rel="stylesheet" href="../css/foundation.css" />
    <link rel="stylesheet" href="../css/responsive-tables.css" media="screen" />
	<link rel="stylesheet" href="../css/sticky_footer.css" />
	<link rel="stylesheet" type="text/css" href="../css/dataTables.foundation.css" />
	<link rel="stylesheet" href="../css/jquery-ui.theme.min.css" />
	<link rel="stylesheet" href="../css/jquery-ui.structure.min.css" />
	<link rel="stylesheet" href="../css/main.css" />
	<?php
		if(is_array($css_files)) {
			foreach ($css_files as $css_file) {
				echo '<link rel="stylesheet" href="../css/'.$css_file.'" />';
			}
		}
	?>
	<script src="../js/vendor/modernizr.js"></script>
	<script src="../js/vendor/jquery.js"></script>
	<script src="../js/jquery-ui.min.js"></script>
	<script src="../js/custom_enterrofoundation_welr.js"></script>
  </head>
  <body>
    <div class="wrapper">
    <div class="loader_div"></div>