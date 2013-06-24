<?php
if(!session_id())
	session_start();
	
	
$config = array();

$config['DB']['DB_HOST'] = 'localhost';
$config['DB']['DB_USER'] = 'erkan_dbuser';
$config['DB']['DB_PASS'] = 'Au74543S';
$config['DB']['DB_NAME'] = 'erkandb';

/*$config['DB']['DB_HOST'] = 'localhost';
$config['DB']['DB_USER'] = 'root';
$config['DB']['DB_PASS'] = '';
$config['DB']['DB_NAME'] = 'mp3file';*/
 
$config['MP3_UPLOAD_FOLDER'] = 'uploads/';

$config['TABLES']['TBL_MP3FILES'] = 'mp3_mp3';
$config['TABLES']['TBL_CATEGORY'] = 'mp3_category';


$access['USER_NAME'] = 'admin';
$access['USER_PASSWORD'] = 'admin';


ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '110M');
ini_set('memory_limit', '256M');
ini_set("max_input_time", 300);

// Rain TPL Configuration

