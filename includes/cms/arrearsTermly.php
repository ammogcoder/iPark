<div class="row" style="margin: 15px;padding-top: 0px;">
	<div class="col-md-3"></div>
	<div class="col-md-7">
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
				<label for="allTerm">All Terms:</label>
				<select id="allTerm" name="allTerm" onselect="loadItems()" onchange="loadItems()" class="form-control" required>
					<option value="0">No</option>
					<option value="1">Yes</option>
				</select>
			</div>
		</form> 
	</div>
	<div class="col-md-2"></div>
</div>

<div class="row" style="margin: 15px;" id="loadItems"></div>

<script type="text/javascript">
	function loadItems(){
		var acyear = $('#acyear').val();
		var term = $('#term').val();
		var allTerm = $('#allTerm').val();
		$.post('ajax.php',{'arrearsTermly':'y','acyear':acyear,'term':term,'allTerm':allTerm},function(data){
			//console.log(data);
			$('#loadItems').html(data);
		});
	}
	loadItems();
</script>