<?php
//error_reporting(E_ERROR); 

require('./SimplePie/simplepie.inc');
require('./classes/DBConn.php');
require('./classes/Post.php');

$dbh = new DBConn();
$resultset = $dbh->query("SELECT bid, blogrss, blogurl FROM blog ORDER BY access_ts ASC LIMIT 50", array());

if($resultset)
{
	while($array = $resultset->fetch())
	{
		// get posts
		$feed = new SimplePie();
		$feed->set_feed_url($array[1]);
 
		$feed->init();
		$feed->handle_content_type();

		foreach ($feed->get_items() as $item)
		{
			$blogid	= $array[0];
			$link	= $item->get_permalink();
			$title	= $item->get_title();

			if(strlen($title) < 2) { $title = "Untitled Post"; }			

			$cont	= nl2br(trim(strip_tags($item->get_content(), '<strong><em><br /><br><br/><code><a><img>')));
			$ts	= strtotime($item->get_date());
			$ts	= ($ts > time()) ? time() : $ts;

			$post	= new Post();
			$post->create($blogid, $link, $title, $cont, $ts);
		}

		unset($feed);

		$feed = new SimplePie();

		// get comments
		if(preg_match("/blogspot\.com/", $array[2]))
		{
			$feed->set_feed_url($array[2].'feeds/comments/default/');
		}
		else
		{
			$feed->set_feed_url($array[2].'comments/feed/');
		}
 
		$feed->init();
		$feed->handle_content_type();

		if(count($feed->get_items()) == 0)
		{
			echo 'nothing in comment feed';
		}

		foreach ($feed->get_items() as $item)
		{
			$link	= str_replace('/comment-page-1','',$item->get_permalink());
			$link	= preg_replace('/\?showComment=[0-9]+/', '', $link);
			$larr	= explode('#', $link);

			$post = new Post();
			if($post->fetchbyurl($larr[0]))
			{
				$cont	= strip_tags($item->get_content(),'<strong><em><br /><br><br/><code><a>');
				$ts	= strtotime($item->get_date());
				$ts	= ($ts > time()) ? time() : $ts;

				$author = '';
				if ($a = $item->get_author())
				{
					$author = $a->get_name();
				}

				$dbh->query("INSERT INTO comment(`cid`, `postid`, `author`, `content`, `timestamp`, `permalink`)".
				" VALUES (null, :pid, :author, :cont, :ts, :link)", 
				array(':ts' => $ts, ':link' => $link, ':cont' => $cont, ':author' => $author, ':pid' => $post->getid()));
			}
			else 
			{
				echo "cannot get post {$larr[0]}<br/>\n";
			}
		}

		$dbh->query("UPDATE blog SET access_ts = unix_timestamp() WHERE bid = :bid", 
			array(':bid'=>$array[0]));

		unset($feed);
	}
}

?>
