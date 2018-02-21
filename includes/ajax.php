<?php
	require "functions.php";
	$ajax = new Stratek();
	 if(isset($_POST['edit'])){
        //edit user details
        $ajax->verifyData($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['updateStatusPid'])){
    	$ajax->updateStatusAdminPid($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['deleteReq'])){
    	$ajax->deleteReqAdmin($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['unlock'])){
        $ajax->unlockSession($_POST['unlock']);
    }elseif(isset($_POST['lock'])){
        $ajax->lockSession();
    }
?>