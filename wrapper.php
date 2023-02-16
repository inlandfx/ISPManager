<?php

	// Created this wrapper as the Graphing Module JPGraph was not able to detect GD (image) support when being run from console
	// However the php script worked fine when executed via web browser
	// So obviously the PHP executable which is being run from console and web browser are different
	
	// Initialize basic parameters
	if(isset($argv)) parse_str(implode('&', array_slice($argv, 1)), $_GET);

	echo file_get_contents("http://127.0.0.1/ispmgr/index.php?action=".$_GET['action']."&trendperiod=".$_GET['trendperiod']."&mail=".$_GET['mail']);
?>