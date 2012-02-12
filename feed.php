<?php

require('./classes/DBConn.php');
require('./classes/Community.php');
require('./classes/User.php');
require('./classes/Blog.php');
require('./classes/Post.php');
require('./classes/Comment.php');
require('./classes/Utils.php');

$gendate	= date('r', time());
$output		= '';
$community	= new Community();
$user		= new User();

if(isset($_GET['com']) && $community->fetch($_GET['com']))
{
	$link	= 'http://kottu.org/9/feed.php?com='.$_GET['com'];
	$name	= $community->getname();
	$desc	= strip_tags($community->getdesc());

	$act	= $community->getactivity();
}
else if(isset($_GET['u']) && $user->fetchbyname($_GET['u']))
{
	$link	= 'http://kottu.org/9/feed.php?u='.$_GET['u'];
	$name	= $user->getname();
	$desc	= strip_tags($user->getbio());

	$act	= $user->getactivity();
}
else
{
	die('Incorrect query strings passed');
}

$output	.=<<<OUT
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	>

<channel>
	<title>$name</title>
	<atom:link href="$link" rel="self" type="application/rss+xml" />
	<link>$link</link>
	<description>$desc</description>
	<lastBuildDate>$gendate</lastBuildDate>
	<language>en</language>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<generator>Kottu 9 RSS Generator</generator>

OUT;


foreach($act as $v)
{
	$title		= '';
	$timestamp	= '';
	$content	= '';
	$link		= 'http://kottu.org/9/';

	if($v['type'] === 'p')
	{
		$uname = ($user->fetch($v['uid'])) ? $user->getname() : '';

		$post = new Post();
		if($post->fetch($v['postid']))
		{
			$title		= 'posted "'.$post->gettitle().'"';
			$timestamp	= date('r', $post->gettime());
			$content	= $post->getcont();
			$link		.= 'p/'.$v['postid'];
		}
	}
	else if($v['type'] === 'c')
	{
		$comment	= new Comment(); 
		if($comment->fetch($v['postid']))
		{
			$uname		= $comment->getauthor();
			
			$title		= 'commented on '.$v['uid'];
			$timestamp	= date('r', $comment->gettime());
			$content	= $comment->getcont();
			$link		.= 'p/'.$comment->getpost().'#c'.$v['postid'];		
		}
	}

	$output .=<<<OUT

<item>
<title><![CDATA[$uname $title]]></title>
<link>$link</link>
<comments>$link#comments</comments>
<pubDate>$timestamp</pubDate>
<dc:creator>$uname</dc:creator>
<description><![CDATA[$content]]></description>
</item>

OUT;

}

header('Content-type: text/xml');
echo $output;

?>
</channel>
</rss>

