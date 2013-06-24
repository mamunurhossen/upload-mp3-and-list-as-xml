<?php
require_once "class.operations.php";
require_once "config.php";

$UploadDirectory	= $config['MP3_UPLOAD_FOLDER'].'/'; //Upload Directory, ends with slash & make sure folder exist

// replace with your mysql database details



if (!file_exists($UploadDirectory)) {
	//destination folder does not exist
	die("Make sure Upload directory exist!");
}



if($_FILES)
{
	//if(!isset($_POST['mName']) || strlen($_POST['mName'])<1)
	//	{
	//		//required variables are empty
	//		die("Title is empty!");
	//	}

	if(!isset($_FILES['mFile']))
	{
		//required variables are empty
		die("File is empty!");
	}


	if($_FILES['mFile']['error'])
	{
		//File upload error encountered
		die(upload_errors($_FILES['mFile']['error']));
	}

	$FileName			= strtolower($_FILES['mFile']['name']); //uploaded file name
	$ImageExt			= substr($FileName, strrpos($FileName, '.')+1); //file extension
	$FileType			= $_FILES['mFile']['type']; //file type
	$FileSize			= $_FILES['mFile']["size"]; //file size
	$RandNumber   		= rand(0, 9999999999); //Random number to make each filename unique.
	$uploaded_date		= date("Y-m-d H:i:s");
	//File Title will be used as new File name
	$NewFileName 		= md5(uniqid (rand (),true)).".".$ImageExt;
	//Rename and save uploded file to destination folder.
	 
	if($ImageExt != 'mp3')
		die('error');

	ini_set('upload_max_filesize', '100M');
	ini_set('post_max_size', '110M');
	ini_set('memory_limit', '256M');
	ini_set("max_input_time", 300);

	if(move_uploaded_file($_FILES['mFile']["tmp_name"], $UploadDirectory . $NewFileName ))
	{
		//connect & insert file record in database
		//$mp3Stream = new mp3file($UploadDirectory.'/'.$NewFileName);
		//$mp3FileInfo = $mp3Stream->get_metadata(); 
		//$mp3FileDuration = $mp3FileInfo['Length mm:ss']; 
		
		 echo "$NewFileName<><>$FileName<><>ll";

	}else{
		die('error uploading File!');
	}
}

//function outputs upload error messages, http://www.php.net/manual/en/features.file-upload.errors.php#90522
function upload_errors($err_code) {
	switch ($err_code) {
		case UPLOAD_ERR_INI_SIZE:
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
		case UPLOAD_ERR_FORM_SIZE:
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
		case UPLOAD_ERR_PARTIAL:
			return 'The uploaded file was only partially uploaded';
		case UPLOAD_ERR_NO_FILE:
			return 'No file was uploaded';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Missing a temporary folder';
		case UPLOAD_ERR_CANT_WRITE:
			return 'Failed to write file to disk';
		case UPLOAD_ERR_EXTENSION:
			return 'File upload stopped by extension';
		default:
			return 'Unknown upload error';
	}
}

