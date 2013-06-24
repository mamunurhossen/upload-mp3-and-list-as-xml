<?php
if(!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] != 1)
		header("Location: index.php");
	 