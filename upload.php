<?php 
	//include the RainTPL class
	include "inc/rain.tpl.class.php";
	require_once "class.operations.php";
	require_once "config.php";
	
	require_once "authentication.php";
	
	$uploadBDMessage = (isset($_SESSION['uploadBDMessage']))? $_SESSION['uploadBDMessage'] : ''; 
	unset($_SESSION['uploadBDMessage']);
	 
	//initialize a Rain TPL object
	$tpl = new RainTPL;
	raintpl::configure("base_url", null );
	raintpl::configure("tpl_dir", "tpl/" );
	raintpl::configure("cache_dir", "tmp/" );

	//variable assign example
	$pageTitle = "Welcome to Mp3 Upload";
	$tpl->assign( "page_title", $pageTitle );
	
	$tpl->assign( "form_enctype", "multipart/form-data" );
	$tpl->assign("formAction", "upload-action.php");
	$tpl->assign( "mp3Name", "mp3_file" );
	$tpl->assign( "file_description_name", "mp3_file_description" );
	$tpl->assign( "mp3_thumbnail", "mp3_thumbnail_file" );
	$tpl->assign( "submit_button","Save Mp3" );
	$tpl->assign( "catParentFieldName","catParent" );
	$tpl->assign( "catFieldName","cat" );
	$tpl->assign( "uploadBDMessage", $uploadBDMessage );
	
	 
	$poerations = new Operations($config);
	$statement = "SELECT * FROM ".$config['TABLES']['TBL_CATEGORY']; 
	$category_query = $poerations->query($statement);
 	if($poerations->affected_rows){
 		//$tpl->assign( "parentCategory", $category_query );
 		$str = '';
 		while($data = mysql_fetch_assoc($category_query)){
 			$str .= '<option value="'.$data['id'].'">'.$data['category_name'].'</option>';
 		}
 		$tpl->assign( "parentCategory", $str );
 	}
 	else
 		$tpl->assign( "parentCategory", null );
	
 
	$html = $tpl->draw( 'page', $return_string = true );

        // and then draw the output
        echo $html;

        
        class str{
            function cut($t){
                return substr($t, 1, 2 );
            }
        }
        
?>