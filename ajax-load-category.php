<?php
	//include the RainTPL class
	include "inc/rain.tpl.class.php";
	require_once "class.operations.php";
	require_once "config.php";
	
	$parentCatId = $_POST['parentCatId'];
	
	$poerations = new Operations($config); 
	$statement = "SELECT * FROM ".$config['TABLES']['TBL_CATEGORY']." WHERE parent_id='".$parentCatId."'";
	$category_query = $poerations->query($statement);
 	 
	if($poerations->affected_rows){
 		 
 		$str = '[';
 		$i = 0;
 		while($data = mysql_fetch_assoc($category_query)){
 			if($i != 0)
 				$str .= ',["'.$data['id'].'","'.$data['category_name'].'"]';
 			else
 				$str .= '["'.$data['id'].'","'.$data['category_name'].'"]';
 			$i++;
 		}
 		 
 		$str .= ']';
 		echo $str;
 	}
 	else{
 		echo '[]';
 	}