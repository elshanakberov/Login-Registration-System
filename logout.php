<?php include ("functions/init.php");


session_destroy();
redirect("index.php");


	if(isset($_COOKIE['_tram'])){
		unset($_COOKIE['_tram']);
		setcookie('_tram','',time() - 7884000 );

	}

 ?>
