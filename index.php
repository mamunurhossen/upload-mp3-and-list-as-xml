<?php 
	//include the RainTPL class
	include "inc/rain.tpl.class.php";
	require_once "class.operations.php";
	require_once "config.php";
	$loginMessage = (isset($_SESSION['loginMessage']))? $_SESSION['loginMessage'] : '';
	unset($_SESSION['loginMessage']);
	// Rain tpl instance
	 
	
	$tpl = new RainTPL;
	raintpl::configure("base_url", null );
	raintpl::configure("tpl_dir", "tpl/" );
	raintpl::configure("cache_dir", "tmp/" );

	$pageTitle = "Login page";
	$tpl->assign( "page_title", $pageTitle );
	
	$tpl->assign( "loginAction", "login.php" );
	$tpl->assign( "loginName", "login[name]" );
	$tpl->assign( "loginPassword", "login[pass]" );
	$tpl->assign( "submitButton", "Sign in" );
	$tpl->assign( "loginMessage", $loginMessage );
	
	
	
	//Assigning to templage
	$html = $tpl->draw( 'login', $return_string = true );
	echo $html;