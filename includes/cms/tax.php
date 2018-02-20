<?php 
	 $tax = $this->getTaxServiceCharge()
?>
<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-cog"></span> Tax/Service Charge</legend></div>
</div>

<div id="displayRes" class="row"></div>

<div class="row" style="margin: 15px;">
	<div class="col-md-4"></div>
	<div class="col-md-4">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<div class="modal-header bgblue">
					<h3 class="panel-title" style="text-align: center;"><span class="glyphicon glyphicon-cog"></span> Tax/Service Charge</h3>
				</div>
				<div class="modal-body">
					<form method="post" action="#" class="form" id="taxForm">
						<div class="form-group">
							<label for="service_charge">Service Charge(GH&cent;):</label>
							<input type="text" id="service_charge" name="service_charge" class="form-control" placeholder="Service Charge" value="<?php echo $this->formatNumber($tax[1]); ?>" required="">
							<input type="hidden" name="id" value="<?php echo $tax[0]; ?>">
						</div>
						<div class="form-group">
							<label for="vat">VAT(%):</label>
							<input type="text" id="vat" name="vat" value="<?php echo $this->formatNumber($tax[2]); ?>" class="form-control" placeholder="VAT" required="">
						</div>
						<div class="form-group">
							<label for="nhil">NHIL(%):</label>
							<input type="text" id="nhil" name="nhil" value="<?php echo $this->formatNumber($tax[3]); ?>" class="form-control" placeholder="NHIL" required="">
						</div>
						<div class="form-group">
							<label for="gtbl">GTBL(%):</label>
							<input type="text" id="gtbl" name="gtbl" value="<?php echo $this->formatNumber($tax[4]); ?>" class="form-control" placeholder="GTBL" required="">
						</div>
						<div class="form-group">
							<div style="text-align: center;">
								<button type="submit" name="updateTaxBtn" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-pencil"></span> Update Tax/Service Charges</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4"></div>
</div>
<?php 
	if(isset($_POST['updateTaxBtn'])){
		$this->updateTaxServiceCharges();
	}
?>
<script type="text/javascript">
    $(function(){
        $('#taxForm').bootstrapValidator({
            message: 'This is not valid',
            feedbackIcons: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields:{
                service_charge:{
                    validators:{
                        notEmpty:{
                            message: 'Service Charge can\'t be empty'
                        },
                        stringLength:{
                            min: 1,
                            max: 15,
                            message: 'Invalid input length'
                        },
                         regexp:{
                            regexp: /^[0-9\.]+$/,
                            message: 'Invalid input'
                        }
                    }
                },
                vat:{
                    validators:{
                        notEmpty:{
                            message: 'Field can\'t be empty'
                        },
                        stringLength:{
                            min: 1,
                            max: 5,
                            message: 'Invalid input length'
                        },
                         regexp:{
                            regexp: /^[0-9\.]+$/,
                            message: 'Invalid input'
                        }
                    }
                },
                nhil:{
                    validators:{
                        notEmpty:{
                            message: 'Field can\'t be empty'
                        },
                        stringLength:{
                            min: 1,
                            max: 5,
                            message: 'Invalid input length'
                        },
                         regexp:{
                            regexp: /^[0-9\.]+$/,
                            message: 'Invalid input'
                        }
                    }
                },
                gtbl:{
                    validators:{
                        notEmpty:{
                            message: 'Field can\'t be empty'
                        },
                        stringLength:{
                            min: 1,
                            max: 5,
                            message: 'Invalid input length'
                        },
                         regexp:{
                            regexp: /^[0-9\.]+$/,
                            message: 'Invalid input'
                        }
                    }
                }
            }
        });
    });
</script>