<?php

include "inc/rain.tpl.class.php";
require_once "class.operations.php";
require_once "config.php";

require_once "authentication.php";

if(isset($_POST['mp3_file_description']) && isset($_POST['mp3_file']) && isset($_FILES['mp3_thumbnail_file'])){
	 
	$mp3File = explode('<><>', htmlspecialchars_decode($_POST['mp3_file']));
	$mp3ThumbnailImage = $_FILES['mp3_thumbnail_file'];
	$mp3TDescription = $_POST['mp3_file_description'];
 
	$poerations = new Operations($config);
	
	$operation = $poerations->save_mp3($mp3File, $mp3ThumbnailImage, $mp3TDescription);
	
	$_SESSION['uploadBDMessage'] = $operation;
	header("Location: upload.php");
	
} 