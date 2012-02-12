<?php

/******************************************************************************************
	
	Post Class - Kottu 9

	Stores post data
	
	07/12/11	Janith		Started post class
	
******************************************************************************************/

class Post
{
	private $postid;
	private $blogid;
	private $link;
	private $title;
	private $content;
	private $timestamp;
	private $comments = null;
	private $score = null;

	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}

	// we create a new post
	public function create($blogid, $link, $title, $content, $timestamp)
	{
		$this->blogid		= $blogid;
		$this->link		= $link;
		$this->title		= $title;
		$this->content		= $content;

		$now = time();
		$this->timestamp = ($timestamp > $now) ? $now : $timestamp;
		
		$this->dbh->query("INSERT INTO post(postid, blogid, link, title, content, timestamp)".
			" VALUES (null, :bid, :link, :title, :content, :ts)", 
			array(':bid' => $this->blogid, ':link' => $this->link, ':title' => $this->title,
			':content'=>$this->content, ':ts'=> $this->timestamp));
	}

	// in this we fetch a post info based on a postid
	public function fetch($postid)
	{
		$resultset = $this->dbh->query("SELECT blogid, link, title, content, timestamp FROM post".
		" WHERE postid = :id", array(':id' => $postid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->postid		= $postid;
			$this->blogid		= $row[0];
			$this->link		= $row[1];
			$this->title		= $row[2];
			$this->content		= $row[3];
			$this->timestamp	= $row[4];

			return true;
		}
		else
		{
			return false;
		}
	}

	// fetch a post by url
	public function fetchbyurl($posturl)
	{
		$resultset = $this->dbh->query("SELECT blogid, postid, title, content, timestamp FROM post".
		" WHERE link = :url", array(':url' => $posturl));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->link		= $posturl;
			$this->blogid		= $row[0];
			$this->postid		= $row[1];
			$this->title		= $row[2];
			$this->content		= $row[3];
			$this->timestamp	= $row[4];

			return true;
		}
		else
		{
			return false;
		}
	}

	// in this we get the commentids of comments to a post
	public function getcomments()
	{			
		if($this->comments == null)
		{
			$this->comments = array();

			$resultset = $this->dbh->query("SELECT cid FROM comment".
			" WHERE postid = :id ORDER BY timestamp ASC", array(':id' => $this->postid));

			if($resultset)
			{
				while(($row = $resultset->fetch()) != false)
				{
					$this->comments[] = $row[0];
				}
			}
		}

		return $this->comments;
	}

	// return the average score that a post got
	public function getscore()
	{
		if($this->score == null)
		{
			$this->score = 0;
			$resultset = $this->dbh->query("SELECT AVG(score) FROM postrating WHERE postid = :id", 
							array(':id' => $this->postid));

			if($resultset && ($row = $resultset->fetch()) != false)
			{
				$this->score = $row[0];
			}
		}

		return $this->score;
	}

	// returns post author
	public function getauthor()
	{
		$author = null;
		$resultset = $this->dbh->query("SELECT u.name FROM user as u, blog as b, post as p ".
		"WHERE u.uid = b.author AND b.bid = p.blogid AND p.postid = :id", array(':id' => $this->postid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$author = $row[0];
		}
		return $author;
	}

	// get methods
	public function getid()		{ return $this->postid; }
	public function getblog()	{ return $this->blogid; }
	public function getlink()	{ return $this->link; }
	public function gettitle()	{ return $this->title; }
	public function getcont()	{ return $this->content; }
	public function gettime()	{ return $this->timestamp; }
		
}

?>
