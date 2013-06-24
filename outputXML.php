<?php 

require_once "class.operations.php";
require_once "config.php";

ob_start();

$x = new outputXML($config);
$x->getXML();
unset($x);

ob_flush();
 