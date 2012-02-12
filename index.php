<?php

require('./classes/DBConn.php');
require('./classes/Community.php');
require('./classes/User.php');
require('./classes/Blog.php');
require('./classes/Post.php');
require('./classes/Comment.php');
require('./classes/Utils.php');

session_start();

$user	= null;
$output = '';
$utils	= new Utils();
$modmin	= false; 	// moderator or admin
$ptitle	= '';
$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"></strong><span class="search">'.
	'<form method="GET" action="search.php"><input class="textbox" type="text" name="q"  tabindex="1" />'.
	' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <a href="./login.php">Login via Facebook</a></div>';


if(isset($_GET['request']) && $_GET['request'] === 'logout')
{
	$redir = (isset($_SESSION['lasturl'])) ? $_SESSION['lasturl'] : './';

	session_unset(); 
	session_destroy();

	header('Location: '.$redir);
}
else
{
	$_SESSION['lasturl'] = 'http://kottu.org'.$_SERVER['REQUEST_URI'];	// for login and stuff
}

if(isset($_SESSION['username']) && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']))
{
	$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"> · <a href="./profile.php">Profile</a>'.
	'</strong><span class="search"><form method="GET" action="search.php"><input class="textbox" type="text" name="q"  tabindex="1" />'.
	' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <strong>'.
	$_SESSION['username'].'</strong> (<a href="./?request=logout">logout</a>)</div>';

	$user = $_SESSION['userid'];

	$community = new Community();

	// below: payin' bills, payin' bills
	if(isset($_GET['com']) && $community->fetch($_GET['com']))
	{
		$mods	= $community->getmods();
		$modmin	= ($_SESSION['admin'] == 1 || in_array($_SESSION['userid'], $mods));

		if(isset($_GET['request']))
		{
			if($_GET['request'] === 'join')
			{
				// request to join a community
				$community->requestjoin($_SESSION['userid']);
			}
			else if($_GET['request'] === 'makemod' && isset($_GET['user']) && $modmin)
			{
				// make someone a mod
				$user = new User();
				if($user->fetchbyname($_GET['user']))
				{
					$community->addmod($user->getid());
				}
			}
			else if($_GET['request'] === 'removeuser' && isset($_GET['user']) && $modmin)
			{
				// remove a user from the community
				$user = new User();
				if($user->fetchbyname($_GET['user']))
				{
					$community->deleteuser($user->getid());
				}
			}
			else if($_GET['request'] === 'approveuser' && isset($_GET['user']) && $modmin)
			{
				// approving a request to join the community
				$user = new User();
				if($user->fetchbyname($_GET['user']))
				{
					$community->approvejoin($user->getid());
				}
			}
			else if($_GET['request'] === 'removeann' && isset($_GET['annid']) && $modmin)
			{
				// remove an announcement
				$community->removeann($_GET['annid']);
			}

			header("Location: ./?com=".$_GET['com']);
		}	
	}
	else if(isset($_POST['comann']) && isset($_POST['comid']))
	{
		// add a community announcement
		if($community->fetch($_POST['comid']))
		{
			$mods	= $community->getmods();
			if($_SESSION['admin'] == 1 || in_array($_SESSION['userid'], $mods))
			{
				$ann = nl2br(strip_tags($_POST['comann'],'<strong><em><s><code><a>'));

				if(strlen($ann) > 1)
				{
					$community->addann($_SESSION['userid'], $ann);
				}
			}		
		}

		header('Location: ./?com='.$_POST['comid']);
	}
}

if(isset($_GET['com']))
{
	$output = displaycommunity($_GET['com'], $modmin);
}
else if(isset($_GET['pid']))
{
	$output = displaypost($_GET['pid']);
}
else
{
	$output = $utils->displayhome($user);
}

// display a community based on comid
function displaycommunity($comid, $modmin)
{
	global $utils;
	global $ptitle;
	$output = '';
	$community = new Community();

	if($community->fetch($comid))
	{
		$name	= $community->getname();
		$desc	= $community->getdesc();
		$loc	= $community->getloc();

		$ptitle = $name;

		if($loc != null)
		{
			$loc = <<<OUT

<div class="title">Location</div>
<div class="desc"><img src="http://maps.googleapis.com/maps/api/staticmap?
center=$loc&zoom=14&size=216x216&sensor=false"/></div>
OUT;
		}		

		$users	= $community->getusers();
		$ucount = count($users);

		$lastid = $community->getlastannid();
		$announce = '';

		if($lastid != null && !isset($_GET['announce']) && !isset($_GET['members']))
		{
			$announce = $community->getann($lastid);
			$annts = $utils->gethumants($announce['ts']);
			$auser = '<a href="./u/'.urlencode($announce['user']).'">'.$announce['user'].'</a>';

			$remlink = ($modmin) ? "<span class=\"replylink\"><a id=\"dlink\" href=\"./?request=removeann&com=$comid&annid=$lastid\">(Remove)</a></span>" : '';

			$announce = <<<OUT

<br/>
<div class="anncont">
<strong>$auser posted Community Announcement #$lastid</strong> <span class="ts">($annts)</span>
$remlink
<br/><br/>
{$announce['cont']}
</div>
OUT;
		}

		$output .=<<< OUT

<div id="infobox">        
<div class="threadinfo">
<h1>$name</h1>
</div>

<div class="sidebar">
<div class="title">Description</div>
<div class="desc">$desc</div>
$loc
<div class="title">Members ($ucount)</div>

OUT;

		if($ucount > 0  && $users[0] != '')
		{
			$output .= '<div class="desc">';

			foreach($users as $u)
			{
				$output .= $utils->userthumb($u);
			}
			// display latest posts / popular posts from community

			$output .= '</div>';

		}

		$mods = $community->getmods();

		if(count($mods) > 0 && $mods[0] != '')
		{
			$output .= "<div class=\"title\">Moderators</div>\n<div class=\"desc\">";

			foreach($mods as $m)
			{
				$output .= $utils->userthumb($m);
			}

			$output .= '</div>';

		}

		$actions = '';
		$annform = '';

		if($user != null && !in_array($user, $users))
		{
			$actions = "<br/><div class=\"desc embeded\"><a href=\"./?com=$comid&request=join\">".
				'<input class="button" type="submit" value="Request to join Community" /></a></div><br/>';
		}

		if($modmin)
		{
			$actions = $utils->displayrequests($community).'<br/><div class="desc embeded"><a onclick="show(\'comann\');" '.
				'href="#"><input class="button" type="submit" value="Make Community Announcement" /></a></div><br/>';

			$annform = <<<OUT

<div id="comann" class="postbox" style="display:none;">
<form name="commentform" method="POST" action="index.php">

	<h2>Make a Community Announcement</h2><br/>

	<blockquote>
	&lt;strong&gt;, &lt;em&gt;, &lt;s&gt;, &lt;a&gt; and &lt;code&gt; tags can be directly used. Use 
	new lines for &lt;br&gt; tags.
	</blockquote><br/>

	<textarea name="comann" tabindex="1"></textarea><br/><br/>
	<input type="hidden" name="comid" value="$comid" />
	<input class="button" type="submit" value="Post Announcement" tabindex="2" /> or <a onclick="show('comann');" href="#">Cancel</a>
	<br/><br/>
</form>
</div>

OUT;

		}

		$output .=<<< OUT

$actions

</div>
<div class="maincol">
<div class="replylink mainnav">
<ul>
<li class="feed"><a href="./feed.php?com=$comid" target="_blank">Feed</a></li>
</ul>
</div>

<div class="mainnav">
<ul>
<li class="act"><a href="./?com=$comid">Recent Activity</a></li>
<li class="ann"><a href="./?com=$comid&announce">Announcements</a></li>
<li class="mem"><a href="./?com=$comid&members">Members List</a></li>
</ul>
</div>
<div class="clear"></div>
$announce
$annform
<br/>

OUT;


		if(isset($_GET['announce']))
		{
			$output	= str_replace('class="ann"', 'class="selected"', $output);	
			$output .= $utils->displayannouncements($community);
		}
		else if(isset($_GET['members']))
		{
			$output	= str_replace('class="mem"', 'class="selected"', $output);			
			$output .= $utils->displaymembers($community, $modmin);
		}
		else
		{
			$output	= str_replace('class="act"', 'class="selected"', $output);
			$output .= $utils->displayactivity($community);
		}
	}

	$output .= '</div>';
	return $output;

}

// display a post and blog info based on pid
function displaypost($pid)
{
	global $utils;
	global $ptitle;
	$output = '';
	$post	= new Post();
	$blog	= new Blog();

	if($post->fetch($pid))
	{
		$title	= $post->gettitle();
		$cont	= $post->getcont();
		$ts	= $utils->gethumants($post->gettime());
		$link	= $post->getlink();
		$bid	= $post->getblog();

		$ptitle = $title;

		$comnts	= $post->getcomments();
		$count	= count($comnts);
		$count	.= ($count == 1) ? ' comment:' : ' comments:' ;

		if($blog->fetch($bid))
		{
			$burl	= $blog->geturl();
			$bname	= $blog->getname();
			$bauth	= $utils->userthumb($blog->getauthor());
			$bdesc	= ($blog->getdesc() != '') ? '<div class="desc">'.$blog->getdesc().'</div>' : '';
			$bts	= $utils->gethumants($blog->getts());

			$output .=<<< OUT

<div id="infobox">        

<div class="sidebar">
	<div class="title">Blog Description</div>
	$bdesc
	<div class="desc"><strong>Name:</strong> $bname</div>
	<div class="desc"><strong>URL:</strong> <a href="$burl">$burl</a></div>
	<div class="desc"><strong>Last Polled:</strong> $bts</a></div>
	<div class="title">Author</div>
	<div class="desc">$bauth</div>
</div>
<div class="maincol">
<h2>$title</h2>
<span class="ts">($ts)</span><span class="replylink"><a title="$link" href="$link" target="_blank">Original Post</a></span>
<br /><br />

$cont

<div class="pbox">
<strong><a name="c_$pid" href="#c_$pid">$count</a></strong>
<div id="comments_$pid">

OUT;

			foreach($comnts as $c)
			{
				$output .= $utils->displaycomment($c);
			}

			$output .= '<div class="clear"></div></div></div>';
		}
	}

	return $output;

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
