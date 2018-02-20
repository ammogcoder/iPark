<?php
	ob_start("ob_gzhandler");
	session_start();
	require_once "dompdf/autoload.inc.php";
	require "functions.php";
	$app = new Stratek();
	//$app->checkIfNotLoggedIn($_SESSION['vsoftadmin']);
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$layout="A6";
	$layout1="A5";
	$receiptHeader="<div style='page-break-after: always;'>";
	$receiptHeader.="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/gofike_logo.png' style='margin-top: 0px; padding-top: 0px;float: left;width: 45px; height: auto;'/>
				<p style='float: left;font-size: 20px;padding-left: 10px; padding-top: 10px; margin-top: 0px;'>Gold Coast Restaurant<br>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";	
	$footer="<div class='row' style='padding-top: 5px;position: absolute;right: 0; bottom: 0;left:0;'>
			<center>
				<hr style='padding: 0px; margin: 0px;'>
				<h5 style='margin: 0px; padding: 0px;font-size: 12px; font-weight: normal;'>Powered by SPERIXLABS</h5>
				<h6 style='margin: 0px; padding: 0px;font-size: 12px; font-weight: normal;'>(0205737153)&nbsp; www.sperixlabs.org</h6>
			</center>
		</div>
		</div>";

	if(isset($_GET['report'])){
		$dompdf->setPaper("gcr", 'portrait');
		if(isset($_GET['existingOrders'])){
			$result = $app->previewOrderedItemsReport($_GET['existingOrders']);
			$dompdf->loadHtml($receiptHeader.$result.$footer);
		}elseif(isset($_GET['receipt'])){
		    $result = $app->previewReceiptReport($_GET['receipt']);
		    $dompdf->loadHtml($receiptHeader.$result.$footer);
        }elseif(isset($_GET['summary'])){
		    $result = $app->previewSummaryReport($_GET['from'],$_GET['to']);
		    $dompdf->loadHtml($receiptHeader.$result.$footer);
        }else{
			$dompdf->loadHtml($receiptHeader.$footer);
		}
		$dompdf->render();
		$dompdf->stream("Report", array("Attachment"=>false));
	}else{
		$dompdf->loadHtml($receiptHeader.$footer);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('gcr', 'portrait');
		
		// Render the HTML as PDF
		$dompdf->render();
		$dompdf->stream("Receipt", array("Attachment"=>false));
	}
