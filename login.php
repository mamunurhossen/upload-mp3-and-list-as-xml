<?php

require_once "config.php";
	 
if(isset($_POST['login'])){
	$userName = $_POST['login']['name'];
	$userPass = $_POST['login']['pass'];
	 
	if($userName == $access['USER_NAME'] && $userPass == $access['USER_PASSWORD']){
		$_SESSION['loggedIn'] = 1;
		header("Location: upload.php");
	}
	else{
		$_SESSION['loginMessage'] = "Invalid user/password";
		header("Location: index.php");
	}
}
else{
	header("Location: index.php");
}