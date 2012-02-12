<?php

require('./classes/DBConn.php');
require('./classes/Comment.php');

/******************************************************************************************
	
	Vote.php - Kottu 9

	Upvoting and unvoting posts
	
	22/11/11	Janith		Created vote.php
	
******************************************************************************************/

session_start();

if(isset($_SESSION['username']) && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']))
{
	
	if(isset($_GET['cid']))
	{
		$cmnt = new Comment();
		if($cmnt->fetch($_GET['cid']))
		{
			$cmnt->vote($_SESSION['userid'], $_SESSION['username']);
			$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'].'#c'.$_GET['cid'] : './';
			header('Location: '.$redir);
		}
	}
}

$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'] : './';
header('Location: '.$redir);

?>
