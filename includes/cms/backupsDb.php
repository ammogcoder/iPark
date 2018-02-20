<div class="row" style="margin: 15px;">
	<div style="text-align: center;">
		<form method="post" action="#" class="form">
			<button class="btn btn-xs btn-warning" type="submit" onclick="progress()" name="backupSysBtn"><span class="fa fa-server"></span> Backup Manually</button>
		</form>
	</div>
</div>

<div class="row" style="margin: 15px;" id="progress">
	<?php $this->loadBackup("dbbackup"); ?>
</div>

<?php 
	if(isset($_POST['backupSysBtn'])){
		$this->dbBackup();
	}elseif(isset($_POST['restoreBtn'])){
		$this->dbRestore($_POST['restoreBtn']);
	}
?>

<script>
	function progress(){
		$('#progress').html("<center><div class='progress' style='margin: 50px;'><div class='progress-bar progress-bar-striped active' role='progressbar' aria-valuenow='40' aria-valuemin='0' aria-valuemax='100' style='width:40%;'> Please wait...</div></div></center>");
	}
</script>