<div class="row" style="margin: 15px;">
	<div style="text-align: center;">
		<a href="#addBillingParam" data-toggle="modal" class="btn btn-xs btn-warning tooltip-bottom" title="Add Billing Param"><span class="glyphicon glyphicon-plus"></span> Add Billing Param</a>
	</div>
</div>

<div class="row" style="margin: 15px;">
	<?php $this->loadBillingParams(); ?>
</div>

<!--modal for adding billing param -->
<div id="addBillingParam" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header bgblue">
				<h3 class="panel-title" style="text-align: center;"><span class="glyphicon glyphicon-th-list"></span> Billing Param</h3>
			</div>
			<div class="modal-body">
				<form method="post" action="#" class="form well" id="submitForm">
					<div class="form-group">
						<label for="name">Name of Billing Param:</label>
						<input type="text" id="name" name="name" class="form-control" placeholder="Name of Billing Param" required="">
					</div>
					<div class="form-group">
						<div style="text-align: center;">
							<button type="submit" name="addBillingParamBtn" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-plus"></span> Add Billing Param</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<?php
	if(isset($_POST['addBillingParamBtn'])){
		$this->addBillingParam($_POST['name']);
	}elseif(isset($_POST['updateBillingParamBtn'])){
		$this->updateBillingParamResult($_POST['id'],$_POST['name']);
	}
?>

<script type="text/javascript">
    $(function(){
        $('#submitForm').bootstrapValidator({
            message: 'This is not valid',
            feedbackIcons: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            name:{
                category:{
                    validators:{
                        notEmpty:{
                            message: 'Field can\'t be empty'
                        },
                        stringLength:{
                            min: 1,
                            max: 100,
                            message: 'Invalid input length'
                        }
                    }
                }
            }
        });
    });
</script>