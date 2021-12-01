<?php
	ob_start();
	include 'index.php';
	ob_end_clean();	
	echo VERSION;

?>