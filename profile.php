<?php

require('./classes/DBConn.php');
require('./classes/Community.php');
require('./classes/User.php');
require('./classes/Blog.php');
require('./classes/Post.php');
require('./classes/Comment.php');
require('./classes/Utils.php');

session_start();

$utils	= new Utils();
$output = '';
$name	= '';
$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"></strong><span class="search">'.
	'<form method="GET" action="search.php"><input class="textbox" type="text" name="q"  tabindex="1" />'.
	' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <a href="./login.php">Login via Facebook</a></div>';


$_SESSION['lasturl'] = 'http://kottu.org'.$_SERVER['REQUEST_URI'];	// for login and stuff

if(isset($_SESSION['username']) && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']))
{
	$topnav = '<strong><a href="./">Home</a><span style="color:#FFF;"> · <a href="./profile.php">Profile</a>'.
	'</strong><span class="search"><form method="GET" action="search.php"><input class="textbox" type="text" name="q"  tabindex="1" />'.
	' <input class="button" type="submit" value="search" tabindex="3" /></form></span><div class="user">Welcome, <strong>'.
	$_SESSION['username'].'</strong> (<a href="./?request=logout">logout</a>)</div>';
}

if(isset($_GET['u']))
{
	$output = displayuser($_GET['u']);

	if(strlen($output) < 2)
	{
		header('Location: index.php');
	}

	$name = urldecode($_GET['u']);
}
else if(isset($_SESSION['username'])  && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']))
{
	if(isset($_POST['propic']))
	{
		$user = new User();
		if($user->fetchbyname($_SESSION['username']))
		{
			$user->setpic($_POST['propic']);
		}
	}
	else if(isset($_POST['bio']))
	{
		$user = new User();
		if($user->fetchbyname($_SESSION['username']))
		{
			$user->setbio(nl2br(strip_tags($_POST['bio'],'<br/><br><strong><em><s><a><code>')));
		}
	}
	else if(isset($_POST['blogname']) && isset($_POST['blogurl']) && isset($_POST['blogrss']) && isset($_POST['blogdesc']))
	{
		if(strlen($_POST['blogname']) > 1 && strlen($_POST['blogurl']) > 1 && strlen($_POST['blogrss']) > 1)
		{
			$blog = new Blog();
			$blog->create($_POST['blogname'], $_SESSION['userid'], $_POST['blogdesc'], $_POST['blogurl'], $_POST['blogrss']);
		}
	}
	
	$output = displayuser(urlencode($_SESSION['username']));
	$name	= $_SESSION['username'];
}
else
{
	header('Location: index.php');
}


function displayuser($name)
{
	global $utils;
	$output = '';
	$name = urldecode($name);

	$user	= new User();

	if($user->fetchbyname($name))
	{
		$pic	= $user->getpic();
		$bio	= $user->getbio();
		$act	= $user->getactivity();

		$ts	= $user->gettime();
		$ts	= date("F j, Y", $ts);

		$blogs = $user->getblogs();
		$bout = '';

		if(count($blogs) > 0)
		{
			foreach($blogs as $b)
			{
				$bout .= '<a href="'.$b['url'].'" target="_blank">'.$b['name'].'</a><br/>';
			}

			$bout = '<div class="title">Blogs</div><div class="desc">'.$bout.'</div>';
		}

		$coms = $user->getcommunities();

		if(count($coms) > 0)
		{
			$cout = '';
			foreach($coms as $c)
			{
				$cout .= '<a href="./?com='.$c['comid'].'">'.$c['name'].'</a><br/>';
			}

			$bout .= '<div class="title">Communities</div><div class="desc">'.$cout.'</div>';
		}

		$bio .= ($bio != '') ? "<br/>" : '';

		if(isset($_SESSION['username'])  && $_SESSION['9auth'] === sha1($_SESSION['username'] . $_SESSION['userid']) && $name === $_SESSION['username'])
		{
			$output .=<<< OUT

<div id="infobox">        
<div class="threadinfo">
<h1>$name</h1>
<span class="ts">(This is your profile)</span><br/>
</div>       

<div class="sidebar">
	<div class="desc"><img src="./i/?src=$pic&w=216"/><br/>
	<a onclick="show('ppedit');" href="#">Change Profile Picture</a><br/>
	<div id="ppedit" style="display:none;">
	<form name="ppform" method="POST" action="profile.php">
		<input class="textbox" type="text" name="propic" tabindex="1" value="$pic"/><br/><br/>
		<input class="button" type="submit" value="Update" tabindex="2" />
		 or <a onclick="show('ppedit');" href="#">Cancel</a><br/><br/>
	</form>
	</div>
	<a onclick="show('bioedit'); show('bio');" href="#">Edit Bio</a><br/>
	</div>
	<div class="title">Info</div>
	<div class="desc">
	<div id="bio">
	$bio
	<br/>
	<strong>Joined:</strong> $ts</a></div>
	</div>
	<div id="bioedit" style="display:none;">
	<form name="bioform" method="POST" action="profile.php">
		<div class="desc"><textarea name="bio" tabindex="3">$bio</textarea><br/><br/>

		<input class="button" type="submit" value="Update" tabindex="4" /> or <a onclick="show('bioedit'); show('bio');" href="#">Cancel</a><br/><br/>

		For the bio, &lt;strong&gt;, &lt;em&gt;, &lt;s&gt;, &lt;a&gt; and &lt;code&gt; 
		tags can be directly used. Use new lines for &lt;br&gt; tags. You cannot embed images and videos.

		</div>
	</form>
	</div>
	$bout
	<div class="desc embeded"><a onclick="show('blogadd');" href="#">
	<input class="button" type="submit" value="Add a Blog" /></a>
	</div>
	<div id="blogadd" style="display:none;">
	<form name="blogform" method="POST" action="profile.php">
		Blog Name:<br/>
		<input class="textbox" type="text" name="blogname" tabindex="5" /><br/>
		Blog URL:<br/>
		<input class="textbox" type="text" name="blogurl" tabindex="6" /><br/>
		Blog RSS:<br/>
		<input class="textbox" type="text" name="blogrss" tabindex="7" /><br/>
		Description:<br/>
		<textarea name="blogdesc" tabindex="8"></textarea><br/><br/>
		<input class="button" type="submit" value="Add Blog" tabindex="9" />
		 or <a onclick="show('blogadd');" href="#">Cancel</a><br/><br/>
	</form>
	</div>
</div>
<div class="maincol">
<div class="replylink mainnav">
<ul>
<li class="feed"><a href="./feed.php?u=$name" target="_blank">Feed</a></li>
</ul>
</div>

<div class="mainnav">
<ul>
<li class="selected"><a href="#">Recent Activity</a></li>
</ul>
</div>
<div class="clear"></div>

OUT;

		}
		else
		{

			$output .=<<< OUT

<div id="infobox">        
<div class="threadinfo">
<h1>$name</h1>
</div>       

<div class="sidebar">
	<div class="desc"><img src="./i/?src=$pic&w=216"/><br/></div>
	<div class="title">Info</div>
	<div id="bio">
	<div class="desc">
	$bio
	<br/>
	<strong>Joined:</strong> $ts</a>
	</div>
	</div>
	$bout
</div>
<div class="maincol">
<div class="replylink mainnav">
<ul>
<li class="feed"><a href="./feed.php?u=$name" target="_blank">Feed</a></li>
</ul>
</div>

<div class="mainnav">
<ul>
<li class="selected"><a href="#">Recent Activity</a></li>
</ul>
</div>
<div class="clear"></div>

OUT;

		}

		$output .= $utils->displayactivity($user);
	}

	$output .= '</div>';

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
