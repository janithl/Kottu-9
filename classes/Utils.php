<?php

/******************************************************************************************
	
	Utils Class - Project Roku

	For public functions that don't fit anywhere else
	
	16/12/11	Janith		Created utils class, code from index
	
******************************************************************************************/

class Utils
{
	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}

	// applies to both users and communities
	public function displayactivity($entity)
	{

		$output = '<br/>';
		$act = $entity->getactivity();

		foreach($act as $v)
		{
			if($v['type'] === 'p')
			{
				$output .= $this->displaypost_infeed($v['postid'], $v['uid']);
			}
			else if($v['type'] === 'c')
			{
				$output .= $this->displaycomment_infeed($v['postid'], $v['uid']);
			}
		}

		return $output;
	}

	// displays community annoucements
	public function displayannouncements($community)
	{
		$output = '';
		$annids = $community->getannlist();

		foreach($annids as $a)
		{
			$announce = $community->getann($a);
			$annts = $this->gethumants($announce['ts']);
			$auser = '<a href="./u/'.urlencode($announce['user']).'">'.$announce['user'].'</a>';

			$removed = ($announce['removed'] == 1) ? ' (<em>Announcement was later removed</em>)' : '';

			$output .= <<<OUT

<div class="anncont">
<strong>$auser posted Community Announcement #$a</strong> <span class="ts">($annts)$removed</span><br/><br/>
{$announce['cont']}
</div>
<br/>

OUT;
		}

		return $output;

	}

	// displays community members
	public function displaymembers($community, $modmin)
	{
		$output = '';
		$id = $community->getid();
		$uids = $community->getusers();

		foreach($uids as $u)
		{
			$thumb = '';
			$actions = '';
			$user	= new User();

			if($user->fetch($u))
			{
				$name = $user->getname();
				$pic = $user->getpic();
				$nurl = urlencode($name);

				$thumb = "<a href=\"./u/$nurl\"><span class=\"postthumb\">".
					"<img src=\"./i/?src=$pic&h=20&w=20\"/></span>$name</a>";

			}

			if($modmin)
			{
				$actions = <<<OUT

<span class="replylink"><a href="./?request=makemod&com=$id&user=$nurl">Make mod</a> · 
<a id="dlink" href="./?request=removeuser&com=$id&user=$nurl">Remove member</a></span>

OUT;
			}

			$output .= <<<OUT

<div class="clear">
$thumb $actions
</div>
OUT;
		}

		return $output;

	}

	// display a comment in the activity feed
	public function displaycomment_infeed($cid, $title)
	{
		$output		= '';
		$comment	= new Comment(); 

		if($comment->fetch($cid))
		{
			$author = $comment->getauthor();
			$cont	= $comment->getcont();
			$ts	= $this->gethumants($comment->gettime());
			$postid	= $comment->getpost();

			$user	= new User();
			$thumb = '';
			if($user->fetchbyname($author))
			{
				$name = $user->getname();
				$pic = $user->getpic();

				$thumb = "<a href=\"./u/".urlencode($name)."\"><span class=\"postthumb\">".
					"<img src=\"./i/?src=$pic&h=20&w=20\"/></span>$name</a>";

			}

			$titlesum	= $this->summer($title, 48);

			$output .=<<< OUT

	<div class="clear">
	<strong>$thumb
	commented on <a href="./p/$postid#c$cid" title="$title">$titlesum</a></strong>
	<span class="ts">($ts)</span>
	</div>

OUT;
		}

		return $output;
	}

	// display a post in the activity feed
	public function displaypost_infeed($postid, $username)
	{
		$output	= '';
		$post	= new Post();

		if($post->fetch($postid))
		{
			$star		= (int) rand(1,5);		// for teh lulz

			$title		= $post->gettitle();
			$titlesum	= $this->summer($title, 48);
			$timestamp	= $this->gethumants($post->gettime());
			$content	= preg_replace('/<img.*src="([^"]+).*>/i', '<img src="./i/?src=\1&h=300&w=400" /></a><br/>', $post->getcont());
			$contentsum	= strip_tags($this->summer($content, 400));

			$comments	= $post->getcomments();
			$count		= count($comments);
			$count		.= ($count == 1) ? ' comment:' : ' comments:' ;


			$user = new User();
			$thumb = '';
			if($user->fetch($username))
			{

				$name = $user->getname();
				$pic = $user->getpic();

				$thumb = "<a href=\"./u/".urlencode($name)."\"><span class=\"postthumb\">".
					"<img src=\"./i/?src=$pic&h=20&w=20\"/></span>$name</a>";

			}

			if(strip_tags($content) !== $contentsum)
			{
				$content =<<<OUT

	<span id="summ_$postid">$contentsum 
	<a name="ps_$postid" href="#ps_$postid"
	onclick="show('summ_$postid'); show('full_$postid'); show('comments_$postid');">(show more)</a></span>
	<span id="full_$postid" style="display:none;">$content 
	<a name="pf_$postid" href="#pf_$postid"
	onclick="show('summ_$postid'); show('full_$postid');">(show less)</a></span>

OUT;

			}

			$output .=<<< OUT

	<div class="clear">
	<span class="replylink"><img src="./i/stars/$star.png"/></span>
	<strong>$thumb
	posted <a href="./p/$postid" title="$title">$titlesum</a></strong>
	<span class="ts">($timestamp)</span>

	<div class="post">
	$content

	<div class="pbox">
	<strong><a name="c_$postid" href="#c_$postid" onclick="show('comments_$postid')">$count</a></strong>
	<div id="comments_$postid" style="display:none;">

OUT;

			foreach($comments as $c)
			{
				$output .= $this->displaycomment($c);
			}

			$output .= '<div class="incom"></div></div></div></div></div>';
		}

		return $output;
	}

	// display a comment under a post
	public function displaycomment($c)
	{
		$output = '';
		$comment = new Comment();

		if($comment->fetch($c))
		{
			$author = $comment->getauthor();
			$aurl	= urlencode($author);
			$cont	= $comment->getcont();
			$ts	= $this->gethumants($comment->gettime());
			$link	= $comment->getlink();
			$id	= $comment->getid();
			$votes	= $comment->getvotes();
			$votes	.= ($votes == 1) ? " vote" : " votes";

			
			$action = (isset($_SESSION['userid']) && $comment->hasvoted($_SESSION['userid'])) ? 'downvote' : 'upvote';

			$user	= new User();
			$pic	= ($user->fetchbyname($author)) ? $user->getpic() : './i/u/'.((strlen($author)+1)%8).'.png';
			$stars	= ($user->getadmin()) ? ' <span class="error"><img src="./i/stars/admin.png"/> admin</span> ' : '';

			/*
			$metals = array('gold','silver','bronze');
			$rand1	= (int) rand(0,2);
			$rand2	= (int) rand(1,3);
			$stars .= ' <img src="./i/stars/'.$rand2.'_'.$metals[$rand1].'.png"/> ';
			*/

			$output .=<<< OUT

	<div class="pbox clear">
	<a name="c$c" href="./u/$aurl"><span class="postthumb"><img src="./i/?src=$pic&h=20&w=20"/></span>$author</a>
	$stars<span class="ts">($ts)</span><span class="replylink">$votes  · <a href="./vote.php?cid=$c">$action</a></span>
	<br/><br/>
	$cont
	</div>

OUT;
		}
	
		return $output;	

	}

	// displays requests to join a community
	public function displayrequests($community)
	{
		$names	= $community->getreqlist();
		$id	= $community->getid();

		$output = '';
		
		if(count($names) > 0)
		{
			$output =<<<OUT

<div class="title">Requests to Join Community</div>
<div class="desc">

OUT;

			foreach($names as $n)
			{
				$user	= new User();
				$thumb = '';
				if($user->fetch($n))
				{
					$name = $user->getname();
					$pic = $user->getpic();
					$nurl = urlencode($name);

					$thumb = "<a href=\"./u/$nurl\"><span class=\"postthumb\">".
						"<img src=\"./i/?src=$pic&h=20&w=20\"/></span>$name</a>";

				}

				$output .= <<<OUT

<div class="clear"><strong>$thumb</strong>
<span class="replylink"><a href="./?request=approveuser&com=$id&user=$nurl">Allow</a> · 
<a id="dlink" href="./?request=removeuser&com=$id&user=$nurl">Reject</a></span>
</div>

OUT;
			}

			$output .= '</div>';
		}

		return $output;
	}

	public function displayhome($user)
	{
		$userobj	= new User();
		$popcoms	= $this->getpopcommunities();
		$usercoms	= null;
		$topcoms	= $this->gettopcommenters();

		$coms		= '';
		$pcom		= '';
		$ucom		= '';

		if($user != null)
		{
			if($userobj->fetch($user))
			{
				$usercoms = (count($userobj->getcommunities()) > 0) ? $userobj->getcommunities() : null; 
			}

			$ucom = '<br/><h2>Your Communities</h2><br/>';

			foreach($usercoms as $p)
			{
				$ucom	.= '<strong><a href="./?com='.$p['comid'].'">'.$p['name'].'</a></strong><br/>';
			}

			$ucom	.= '</br>';
		}

		foreach($topcoms as $u)
		{
			if($userobj->fetch($u['uid']))
			{
				$name	= $userobj->getname();
				$pic	= $userobj->getpic();
				$votes	= ($u['votes'] == 1) ? $u['votes'].' upvote' :  $u['votes'].' upvotes';
				$nurl	= urlencode($name);

				$coms .= "<div class=\"clear\"><a href=\"./u/$nurl\"><span class=\"postthumb\">".
					"<img src=\"./i/?src=$pic&h=20&w=20\"/></span>$name</a>".
					"<span class=\"replylink\">$votes</span></div>";
			}
		}

		foreach($popcoms as $p)
		{
			$userno = ($p['users'] == 1) ? $p['users'].' member' : $p['users'].' members';
			$pcom .= '<strong><a href="./?com='.$p['comid'].'">'.$p['name'].'</a></strong><span class="replylink">'.$userno.'</span><br/>';
		}

		$output .=<<< OUT

<div id="infobox">
<div class="sidebar">
<div class="title">Top Commenters</div>
<div class="desc">$coms</div>
</div>

<div class="maincol">
<h2>Popular Communities</h2><br/>
$pcom

$ucom
</div>

OUT;

		return $output;
	}

	// get popular commenters
	public function gettopcommenters()
	{
		$users = array();

		$res = $this->dbh->query("SELECT u.uid, count(v.voter) FROM user as u, comment as c, vote as v ".
			"WHERE u.name = c.author AND c.cid = v.cid GROUP BY v.cid ORDER BY count(v.voter) LIMIT 5", array());

		if($res)
		{
			while($row = $res->fetch())
			{
				$users[] = array('uid' => $row[0], 'votes' => $row[1]);
			}
		}

		return $users;
	}

	// gets popular communities
	public function getpopcommunities()
	{
		$com = array();

		$res = $this->dbh->query("SELECT c.comid, c.name, COUNT(cu.uid) FROM commuser as cu, community as c ".
			"WHERE c.comid = cu.comid AND cu.approved = 1 GROUP BY cu.comid ORDER BY COUNT(cu.uid) DESC LIMIT 5", array());

		if($res)
		{
			while($row = $res->fetch())
			{
				$com[] = array('comid' => $row[0], 'name' => $row[1], 'users' => $row[2]);
			}
		}

		return $com;
	}

	// returns unix timestamp in human readable format
	public function gethumants($timestamp)
	{
		$now = time();

		if(($now - $timestamp) < (60 * 60))
		{
			$timestamp = (int) (($now - $timestamp) / 60);
			if($timestamp == 0) { $timestamp = 'just now'; }
			else if($timestamp == 1) { $timestamp .= ' minute ago'; }
			else { $timestamp .= ' minutes ago'; }
		}
		else if(($now - $timestamp) < (24 * 60 * 60))
		{
			$timestamp = (int) (($now - $timestamp) / (60 * 60));
			if($timestamp == 1) { $timestamp .= ' hour ago'; }
			else { $timestamp .= ' hours ago'; }
		}
		else if(($now - $timestamp) < (4 * 24 * 60 * 60))
		{
			$timestamp = (int)(($now - $timestamp)/(24 * 60 * 60));
			if($timestamp == 1) { $timestamp .= ' day ago'; }
			else { $timestamp .= ' days ago'; }
		}
		else
		{
			$timestamp = date('F j, Y', $timestamp);
		}

		return $timestamp;
	}

	// summarizes content to a given number of chars
	public function summer($content, $length)
	{
		$numwords = (int)($length / 7);

		if(strlen($content) > $length)
		{
			$paragraph = explode(' ', $content);
			$paragraph = array_slice($paragraph, 0, $numwords);
			$content = implode(' ', $paragraph) . " ...";
		}

		return $content;
	}
	
	
	// return user thumbnail
	public function userthumb($uid)
	{
		$output = '';
		$user = new User();
		if($user->fetch($uid))
		{
			$name = $user->getname();
			$pic = $user->getpic();

			$output .= "<a title=\"$name\" href=\"./u/".urlencode($name)."\">".
					"<span class=\"thumb\"><img src=\"./i/?src=$pic&h=40&w=40\"/></span></a>";

		}

		return $output;
	}
}

?>
