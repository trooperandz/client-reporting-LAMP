<div class="row">
	<div class="small-12 medium-12 large-12 columns">
		<p> &nbsp; </p>
	</div>
</div>
<div class="push"></div> 
</div><!-- end div wrapper (I think) -->
<footer>
	<span class="footer_span"><span class="copyright">&copy; <?php echo date('Y');?></span>&nbsp; Service Operations Specialists, Inc.</span>
	<span class="footer_feedback"><a href ="http://www.sosfirm.com" target="_blank"><img src="../img/info-24.ico"></a></span>
</footer>

<script src="../js/foundation.min.js"></script>
<script src="../js/responsive-tables.js"></script>
<script src="../js/jquery.dataTables.js"></script>
<script type="text/javascript" language="javascript" src="../js/dataTables.foundation.js"></script>
<script>
	$(document).foundation();
	
	$(document).ready(function() {
		$('#ro_date').datepicker({
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