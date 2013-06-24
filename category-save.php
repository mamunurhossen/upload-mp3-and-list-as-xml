<?php

require_once "config.php";
require_once "class.operations.php";
require_once "authentication.php";
 

if(isset($_POST['catName'])){
	$catName = $_POST['catName'];
	$catParentName = $_POST['catParentName'];
	$statement = "INSERT INTO `".$config['TABLES']['TBL_CATEGORY']."` (`parent_id`, `category_name`) VALUES ( '$catParentName', '$catName')";
	
	$poerations = new Operations($config); 
	$category_query = $poerations->query($statement);
	if($category_query){
		$_SESSION['catBDMessage'] = 'Category saved';
		header("Location: category.php");
	}
	else{
		$_SESSION['catBDMessage'] = 'Category save operation failed';
		header("Location: category.php");
	}
	
}
else{
	header("Location: index.php");
}