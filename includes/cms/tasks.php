<?php 
	$backupTime = $this->getBackupTime();
	$this->genCronConfigurationFiles();
?>
<div class="col-lg-12" style="margin: 5px;">
	<div class="alert alert-info alert-dismissable">
	    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
	    <i class="fa fa-info-circle"></i> <strong>Welcome to </strong> <a href="#" class="alert-link" style="font-family: flower;">  the Cron Background Task Configuration</a>
	</div>
</div>

<div class="row" style="margin: 5px;">
	<div class="col-md-5">
		<form method="post" action="#" class="form well" id='backupForm'>
			<div class='form-group'>
				<label for="backup">Backup Time(Every <?php echo $backupTime[1]; ?> hour(s)):</label>
				<input type="number" id="backup" name="backup" min="1" max="23" class="form-control" value="<?php echo $backupTime[1]; ?>" placeholder="Backup Time(Hours)" required="">
			</div>
			<div class="form-group">
				<div style="text-align: center;">
					<button type="submit" name="backupBtn" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-play-circle"></span> Update</button>
				</div>
			</div>
		</form>
	</div>
	<div class="col-md-2"></div>
	<div class="col-md-5 well">
		<div class="col-sm-12" style="margin-top: 20px;">
			<div style="text-align: center;">
				<button type="button" style="width: 100%;" onclick="window.open('backups/gcr')" class="btn btn-xs btn-warning"><span class="fa fa-download"></span> Download Linux CronTask Configuration</button>
			</div>
		</div>
		<div class="col-sm-12" style="margin: 15px;"></div>
		<div class="col-sm-12" style="margin-bottom: 20px;">
			<div style="text-align: center;">
				<button type="button" style="width: 100%;" onclick="window.location='backups/gcr.bat'" class="btn btn-xs btn-primary"><span class="fa fa-download"></span> Download Windows CronTask Configuration</button>
			</div>
		</div>
	</div>
</div>


<div class="col-lg-12" style="margin: 5px;">
	<div class="alert" style="background-color: #607D8B; color: #fff;">
	    
	    <i class="fa fa-info-circle"></i> <strong><span style='color: #fff;'>How to use </span></strong> <a href="#" class="alert-link" style="font-family: flower;">  the Cron Background Task Configuration</a><br/><br>

	    <p> Check the ``dbconfig.php`` file in ``includes/`` default root directory to make sure all defined path for the cron job is correct for your operating system.</p>
	    <p>
	    	<h3> Linux Configuration</h3>
	    	<ol>
	    		<li>Download the configuration file</li>
	    		<li>Navigate to ``/etc/cron.d/``</li>
	    		<li>Copy and paste the download file into the ``/etc/cron.d`` directory</li>
	    	</ol>
	    </p>

	    <p>
	    	<h3> Windows Configuration</h3>
	    	<ol>
	    		<li>Download the configuration file</li>
	    		<li>Run the ``gcr.bat`` file as an <b>Administrator</b></li>
	    	</ol>
	    	<strong>NB:</strong> It is advisable to configure the xampp application to autostart by putting it's shortcut application in the startup folder. Make sure the XAMPP application has <b>Apache</b> and <b>MySQL</b> autostart enabled.
	    </p>
	</div>
</div>

<?php 
	if(isset($_POST['backupBtn'])){
		$this->updateBackupTime($_POST['backup']);
	}
?>

<script type="text/javascript">
	$(function(){
		$('#backupForm').bootstrapValidator({
			message: 'This is invalid',
			feedbackIcons:{
				valid: 'glyphicon glyphicon-ok',
				invalid: 'glyphicon glyphicon-remove',
				validating: 'glyphicon glyphicon-refresh'
			},
			fields:{
				backup:{
					validators:{
						notEmpty:{
							message: 'Field can\'t be empty'
						},
						stringLength:{
							min: 1,
							max: 2,
							message: 'Invalid field length'
						},
						regexp:{
							regexp: /^[0-9]+$/,
							message: 'Invalid input'
						}
					}
				}
			}
		});
	});
</script>