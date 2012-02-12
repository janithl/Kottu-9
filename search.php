<?php

/******************************************************************************************
	
	search.php - Kottu 9

	Searching

	23/11/11	Janith		Created search.php on Roku
	27/12/11	Janith		Copied from Roku, mixed with some earlier code
					written for 9 search and adapted for use here

******************************************************************************************/

require('./classes/DBConn.php');
require('./classes/Community.php');
require('./classes/User.php');
require('./classes/Post.php');
require('./classes/Comment.php');
require('./classes/Utils.php');

session_start();

$utils	= new Utils();
$output = '';
$name	= '';
$dbh	= new DBConn();

if(isset($_GET['q']) && $_GET['q'] !== '')
{
	$_SESSION['lasturl'] = 'http://kottu.org'.$_SERVER['REQUEST_URI'];	// for login and stuff

	$query		= trim(urldecode($_GET['q']));
	$name		= "Search for ".$query;
	$resultset	= false;
	$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"></strong><span class="search">'.
	'<form method="GET" action="search.php"><input class="textbox" type="text" name="q" value="'.$query.'" tabindex="1" />'.
	' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <a href="./login.php">Login via Facebook</a></div>';

	if(isset($_SESSION['username']) && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']))
	{
		$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"> · <a href="./profile.php">Profile</a>'.
		'</strong><span class="search"><form method="GET" action="search.php"><input class="textbox" type="text" name="q" value="'.$query.'" tabindex="1" />'.
		' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <strong>'.
		$_SESSION['username'].'</strong> (<a href="./?request=logout">logout</a>)</div>';
	}

	$output =<<<OUT

<div class="sidebar">
<div class="title">Filter Results</div>
<div class="desc">
<a href="./search.php?q={$_GET['q']}&user">Show only Users</a><br/>
<a href="./search.php?q={$_GET['q']}&com">Show only Communities</a><br/>
<a href="./search.php?q={$_GET['q']}&post">Show only Posts</a><br/>
<a href="./search.php?q={$_GET['q']}&comment">Show only Comments</a><br/><br/>
<strong><a href="./search.php?q={$_GET['q']}">Show all</a></strong>
</div>
</div>

OUT;

	if(isset($_GET['user']))
	{
		$resultset = $dbh->query("SELECT 'u', name FROM user".
		" WHERE name LIKE :query", array(':query' => "%$query%"));
	}
	else if(isset($_GET['com']))
	{
		$resultset = $dbh->query("SELECT 'com', comid FROM community".
		" WHERE name LIKE :query OR description LIKE :query", array(':query' => "%$query%"));
	}
	else if(isset($_GET['post']))
	{
		$resultset = $dbh->query("SELECT 'p', postid FROM post".
		" WHERE title LIKE :query OR content LIKE :query", array(':query' => "%$query%"));
	}
	else if(isset($_GET['comment']))
	{
		$resultset = $dbh->query("SELECT 'cmnt', cid FROM comment".
		" WHERE content LIKE :query OR author LIKE :query", array(':query' => "%$query%"));
	}
	else
	{
		$resultset = $dbh->query("(SELECT 'u', name FROM user".
		" WHERE name LIKE :query) UNION (SELECT 'com', comid FROM community".
		" WHERE name LIKE :query or description LIKE :query) UNION".
		" (SELECT 'p', postid FROM post WHERE title LIKE :query".
		" OR content LIKE :query) UNION (SELECT 'cmnt', cid FROM comment".
		" WHERE content LIKE :query OR author LIKE :query)", array(':query' => "%$query%"));
	}

	if($resultset && count($resultset) > 0)
	{
		$prev = '';
		$output .= display($resultset);
	}
	else
	{
		$output		.= '<div class="maincol"><em>There were no results for your search</em></div>';
	}
}
else
{
	$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'] : './';

	header('Location: '.$redir);
}

function display($result)
{
	$output = '<div class="maincol"><h2>Search Results</h2><br/>';
	global $utils;

	$commu	= new Community();
	$post	= new Post();
	$cmnt	= new Comment();
	$user	= new User();
	$count	= 0;

	foreach($result as $row)
	{
		$title = '';
		$count++;

		switch($row[0])
		{
			case 'u':
				if($user->fetchbyname($row[1]))
				{
					$title	= '[User] '.$row[1];
					$link	= './u/'.$row[1];
					$cont	= $user->getbio();
					$ts	= date("F j, Y", $user->gettime());
					$author = '';
				}
				break;
			case 'com':
				if($commu->fetch($row[1]))
				{
					$title = '[Community] '.$commu->getname();
					$link	= './?com='.$row[1];
					$cont	= $commu->getdesc();
					$ts	= date("F j, Y", $commu->gettime());
					$author = '';
				}
				break;
			case 'cmnt':
				if($cmnt->fetch($row[1]))
				{
					$title	= '[Comment] #'.$row[1].' on [Post] #'.$cmnt->getpost();
					$link	= './p/'.$cmnt->getpost().'#c'.$row[1];
					$cont	= $cmnt->getcont();
					$ts	= date("F j, Y", $cmnt->gettime());
					$author = $cmnt->getauthor();
					$author = " by <a href=\"./u/".urlencode($author)."\">$author</a>";
				}
				break;
			case 'p':
				if($post->fetch($row[1]))
				{
					$title	= '[Post] '.$post->gettitle();
					$link	= './p/'.$row[1];
					$cont	= $post->getcont();
					$ts	= date("F j, Y", $post->gettime());
					$author = $post->getauthor();
					$author = " by <a href=\"./u/".urlencode($author)."\">$author</a>";
				}
		}

		$cont = strip_tags($utils->summer($cont, 400));

		$output .=<<< OUT

	<div class="clear">
	<strong><a href="$link">$title</a>$author</strong> <span class="ts">($ts)</span><br/>
	<div class="post">
	$cont

	</div>
	</div>

OUT;

	}

	if($count == 0)
	{
		
		$output	.= '<div class="maincol"><em>There were no results for your search</em></div>';
	}

	$output .= '<div class="clear"></div></div>';

	return $output;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
        "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title><?php echo $name; ?> on Kottu 9</title>
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
