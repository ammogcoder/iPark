<div class="row" style="margin: 15px;padding-top: 0px;">
	<div class="col-md-3"></div>
	<div class="col-md-8">
		<form class="form-inline">
			<div class="form-group">
				<label for="acyear">Academic Year:</label>
				<select id="acyear" name="acyear" onselect="loadItems()" onchange="loadItems()" class="form-control" required>
					<?php $this->genSelectOption("acyear"); ?>
				</select>
			</div>
			<div class="form-group">
				<label for="term">Term:</label>
				<select id="term" name="term" onselect="loadItems()" onchange="loadItems()" class="form-control" required>
					<?php $this->genSelectOption("term"); ?>
				</select>
			</div>
			<div class="form-group">
				<label for="item">Billing Item/Head:</label>
				<select id="item" name="item" onselect="preview()" onchange="preview()" class="form-control" required>
				</select>
			</div>
		</form> 
	</div>
	<div class="col-md-2"></div>
</div>

<div class="row" style="margin: 15px;" id="loadItems"></div>

<script type="text/javascript">
	function preview(){
		var acyear = $('#acyear').val();
		var term = $('#term').val();
		var item = $('#item').val();
		//getting list of items
		$.post('ajax.php',{'loadBillingItemsReport':'y','acyear':acyear,'term':term,'item':item},function(data){
			$('#loadItems').html(data);
		});
	}
	
	function loadItems(){
		var acyear = $('#acyear').val();
		var term = $('#term').val();
		//getting list of items
		$.post('ajax.php',{'getBillingItems':'y','acyear':acyear,'term':term},function(data){
			$('#item').html(data);
		});
		preview();
	}
	loadItems();
	preview();
</script>