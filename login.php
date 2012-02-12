<?php

// This is where we authenticate users against their Facebook logins

require('./classes/DBConn.php');
require('./classes/User.php');

$app_id		= "app id";
$app_secret	= "app secret";
$my_url		= "http://kottu.org/9/login.php";

session_start();
$code		= $_REQUEST["code"];

if(empty($code)) {
	$_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
	$dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
	. $app_id . "&redirect_uri=" . urlencode($my_url) . "&state="
	. $_SESSION['state'];

	echo("<script> top.location.href='" . $dialog_url . "'</script>");
}

if($_REQUEST['state'] == $_SESSION['state']) {
	$token_url = "https://graph.facebook.com/oauth/access_token?"
	. "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
	. "&client_secret=" . $app_secret . "&code=" . $code;

	$response	= @file_get_contents($token_url);
	$params		= null;
	parse_str($response, $params);

	$graph_url	= "https://graph.facebook.com/me?access_token=" 
	. $params['access_token'];

	$user	= json_decode(file_get_contents($graph_url));

	$uid	= 'fb_'.$user->id;

	$newuser = new User();
	
	if($newuser->fetch($uid))
	{
		$_SESSION['username']	= $newuser->getname();
		$_SESSION['userid']	= $newuser->getid();
		$_SESSION['admin']	= $newuser->getadmin();
		$_SESSION['9auth']	= sha1($_SESSION['username'] . $_SESSION['userid']);

		$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'] : './profile.php';

		header('Location: '.$redir); 
	}
	else
	{
		//new user... need to register
		$_SESSION['fbid']	= $user->id;
		$_SESSION['regauth']	= sha1($_SESSION['fbid'].'salt');

		header('Location: ./register.php?reg='.sha1($_SESSION['regauth'].$user->name).'&name='.urlencode($user->name).'&other=1');
	}
}
else {
	echo("The state does not match. You may be a victim of CSRF.");
}

?>
