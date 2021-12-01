<?php
	if(file_exists('vqmod/install'))
	{
		include 'vqmod/install/index.php';
		
	}
	else
	{
		echo "not-installed";
	}
	
?>