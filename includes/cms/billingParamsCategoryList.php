<div class="row" style="margin: 15px;">
	<div class="col-md-3"></div>
	<div class="col-md-3">
		<form class="form-inline">
			<div class="form-group">
				<label for="billingParam">Billing Params:</label>
				<select id="billingParam" name="billingParam" onselect="loadItems()" onchange="loadItems()" class="form-control" required>
					<?php  $this->genNameID("billing_params"); ?>
				</select>
			</div>
		</form> 
	</div>
	<div class="col-md-3">
		<div style="text-align: center;">
			<a href="#addBillingParamCategory" data-toggle="modal" style="margin-top: 10px;" class="btn btn-xs btn-warning tooltip-bottom" title="Add Billing Param Category"><span class="glyphicon glyphicon-plus"></span> Add Billing Param Sub Categories</a>
		</div>
	</div>
	<div class="col-md-3"></div>
</div>

<div id="loadItems" class="row" style="margin: 15px;"></div>


<!-- add billing parameter category -->
<div id="addBillingParamCategory" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header bgblue">
				<h3 class="panel-title" style="text-align: center;"><span class="glyphicon glyphicon-th-list"></span> Billing Param Sub Categories</h3>
			</div>
			<div class="modal-body">
				<form method="post" action="#" class="form" id="submitForm">
					<div class="form-group">
						<label for="name">Sub Category Name:</label>
						<input type="text" id="name" name="name" class="form-control" placeholder="Sub Category Name" required="">
						<input type="hidden" id="id" name="id" value=""/>
						<input type="hidden" name="addSubCategoryBtn"/>
					</div>
					<div class="form-group">
						<div style="text-align: center;">
							<button type="button" onclick="addSubCategory()" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-plus"></span> Add Sub Category</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php 
	if(isset($_POST['addSubCategoryBtn'])){
		$this->addBillingParamsCategoryResult($_POST['id'],$_POST['name']);
	}elseif(isset($_POST['updateBillingParamCategoryBtn'])){
		$this->updateBillingParamCategoryResult($_POST['id'],$_POST['name']);
	}
?>

<script>
	function loadItems(){
		var billing_params = $('#billingParam').val();
		//ajax request
		$.post('ajax.php',{'loadBillingParamsCategory':billing_params},function(data){
			$('#loadItems').html(data);
		});
	}

	function addSubCategory(){
		//getting current id
		var id = $('#billingParam').val();
		//setting value of id
		$('#id').attr('value',id);

		//submitting form 
		document.getElementById('submitForm').submit();
	}


	loadItems();
</script>


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