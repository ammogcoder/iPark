<?php
	session_start();
	ob_start("ob_gzhandler");
	include "includes/ajax.php";
?>