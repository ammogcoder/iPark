<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-flash"></span> Billing Params</legend></div>
</div>

<div class="row" style="margin: 15px;">
	<ul class="nav nav-tabs">
		<li id="list"><a href="?billingParams"><span class="glyphicon glyphicon-th-list"></span> Billing Params</a></li>
		<li id="category"><a href="?billingParams&category"><span class="glyphicon glyphicon-th-list"></span> SubCategories</a></li>
	</ul>
</div>

<div class="row" id="displayRes"></div>

<?php
	if(isset($_GET['category'])){
		//load category
		$this->setClassActive("category");
		include "billingParamsCategoryList.php";
	}else{
		//load default view
		$this->setClassActive("list");
		include "billingParamsList.php";
	}
?>