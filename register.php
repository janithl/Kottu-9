<?php

require('./classes/DBConn.php');
require('./classes/User.php');

session_start();

$output = '';
$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"></strong><div class="user">Welcome, <a href="./login.php">Login via Facebook</a></div>';
$modmin	= false; 	// moderator or admin
$ptitle	= '';

if(isset($_SESSION['fbid']) && (!isset($_SESSION['username'])) && $_SESSION['regauth'] === sha1($_SESSION['fbid'].'salt'))
{
	if(isset($_GET['name']) && isset($_GET['reg']) && $_GET['reg'] === sha1($_SESSION['regauth'].$_GET['name']))
	{
		// when you call register.php set those session vars and send

		// here output a sane looking registration form
		$name	= urldecode($_GET['name']);
		$pic	= "https://graph.facebook.com/".$_SESSION['fbid']."/picture";

		$output = <<<OUT
<div id="infobox">        
<div class="threadinfo">
<h1>Register for Kottu 9</h1>

<div class="postbox">
<form name="commentform" method="POST" action="register.php">

	<label for="name">Wordpress Username:</label>
	<input class="textbox" type="text" name="name" value="$name" tabindex="1" />
	<div class="clear ts">(The Username you use to comment on blogs)</div>
	<label for="pic">Profile picture:</label>
	<input class="textbox" type="text" name="pic" value="$pic" tabindex="2" /><br/><br/>
	<label for="bio">A short bio:</label>
	<textarea name="bio" tabindex="3"></textarea><br/><br/>

	<input class="button" type="submit" value="Register!" tabindex="4" /> or <a href="./register.php?cancel=1">Cancel</a>
	<br/><br/>
</form>
</div>

</div>
</div>

OUT;

	}
	else if(isset($_GET['cancel']))
	{
		$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'] : './';

		session_unset(); 
		session_destroy();

		header('Location: '.$redir);
	}
	else if(isset($_POST['name']) && isset($_POST['pic']) && isset($_POST['bio']))
	{
		$user = new User();

		if(!$user->create_fb($_SESSION['fbid'], strip_tags($_POST['name']), ' ', strip_tags($_POST['pic']), strip_tags($_POST['bio'],'<br/><br><strong><em><s><a><code>')))
		{
			session_unset(); 
			session_destroy();
			header('Location: ./login.php');
		}
		else
		{
			$output = '<div class="reply"><strong>There was an issue with your registration.</strong><br/><br/>'.
					' Your user account could not be created<br/><br/><a href="./login.php">Retry</a></div>';
		}
	}
	else
	{
		header('Location: ./');
	}
}
else
{
	header('Location: ./'); 
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
        "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><? echo (strlen($ptitle) > 1) ? $ptitle.' on ' : ''; ?>Kottu 9</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<link href='http://fonts.googleapis.com/css?family=Cabin:400,400italic,700' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
	<link rel="icon" href="./i/nein.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="./i/nein.ico" type="image/x-icon" />
	<script type="text/javascript">
	<!--

	function show(elem)
	{
		var ele = document.getElementById(elem);
		if(ele.style.display != "none")
		{
	    		ele.style.display = "none";
	  	}
		else
		{
			ele.style.display = "block";
		}
	}
	//-->
	</script>
</head>
<body>

<div id="header">
<?php echo $topnav; ?>	
</div> <!-- END #header -->

<div id="container">
<div id="content">
<?php echo $output; ?>	
</div>
<div style="clear:both;"></div>

</div>

<div style="clear:both;"></div>
<div id="footer">
<p>Na na na na na na na na KOTTU!</p>
</div>
</body>
</html>
