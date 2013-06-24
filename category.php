<?php 
	//include the RainTPL class
	include "inc/rain.tpl.class.php";
	require_once "class.operations.php";
	require_once "config.php";
	require_once "authentication.php";
	
	$createMessage = (isset($_SESSION['catBDMessage']))? $_SESSION['catBDMessage'] : ''; 
	unset($_SESSION['catBDMessage']);

	//initialize a Rain TPL object
	$tpl = new RainTPL;
	raintpl::configure("base_url", null );
	raintpl::configure("tpl_dir", "tpl/" );
	raintpl::configure("cache_dir", "tmp/" );
	
	//variable assign example
	$pageTitle = "MP3 Category Management";
	$tpl->assign( "page_title", $pageTitle );
	
	$tpl->assign( "ctionAction", "category-save.php" );
	$tpl->assign( "catFieldName", "catName" );
	$tpl->assign( "catParentFieldName", "catParentName" ); 
	$tpl->assign( "submitButton", "Save Now" ); 
	$tpl->assign( "createMessage", $createMessage ); 
	
	 
	$poerations = new Operations($config); 
	$category_query = $poerations->query("SELECT * FROM ".$config['TABLES']['TBL_CATEGORY']);
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
 		
	$html = $tpl->draw( 'category', $return_string = true );

        // and then draw the output
        echo $html;
 
?>