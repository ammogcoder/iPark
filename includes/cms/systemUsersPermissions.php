<?php
	if(!isset($_SESSION['useredit'])){
		$this->redirect("?systemUsers");
	}else{
		$login = $this->getFullDetailsId($_SESSION['useredit'],"login");
	}
?>
<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-user"></span> <?php echo $login[3]; ?> &nbsp;|&nbsp; System User Permissions &nbsp;&nbsp;<a href='?systemUsers' class='btn btn-xs btn-danger br tooltip-bottom' title='Close'><span class="glyphicon glyphicon-remove"></span></a></legend></div>
</div>

<div class='row' style="margin: 15px;" id="displayRes"></div>

<div class="row" style="margin: 15px;">
	<?php $this->loadAllPermissions(); ?>
</div>
<?php 
	if(isset($_POST['updateBtn'])){
		$this->updatePermissions();
	}
?>

<script type="text/javascript">
	$('#tableList1').DataTable({
	    responsive: true,
	    "pageLength": 15
	 });
</script>