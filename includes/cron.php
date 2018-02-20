<?php 
	require __DIR__."/functions.php";
	$cron = new Stratek();

	$cron->backgroundTask();
?>