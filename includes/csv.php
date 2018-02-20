<?php
	require "functions.php";
	$vsoft = new Stratek();
	if(!isset($_SESSION['stratekadmin'])){
		echo "<script>close();</script>";
	}else{
		if(isset($_GET['studentsList'])){
			$class = intval($_GET['studentsList']);
			$classDetails = $vsoft->getFullDetailsId($class,"classes");
			if($class == 0){
				$filename = "All_Classes_List.csv";
			}else{
				$filename = str_replace(" ", "_", $classDetails[2])."_Students_List.csv";
			}
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename='.$filename.'');
			$vsoft->genStudentListCsv($_GET['studentsList']);
		}
	}

?>