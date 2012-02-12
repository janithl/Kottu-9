<?php

/******************************************************************************************
	
	Blog Class - Kottu 9

	Stores blog data
	
	07/12/11	Janith		Created blog class.
	
******************************************************************************************/

class Blog
{
	private $blogid;
	private $name;
	private $accessts;
	private $url;
	private $rss;
	private $author;
	private $description;
	private $rating = null;
	private $posts = null;

	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}


	// we create a new blog
	public function create($name, $author, $description, $url, $rss) 
	{
		$this->name		= $name;
		$this->description	= $description;
		$this->accessts		= 0;
		$this->author		= $author;
		$this->url		= $url;
		$this->rss		= $rss;

		$this->dbh->query("INSERT INTO blog(bid, blogname, blogurl, blogrss, access_ts, author, description)".
			" VALUES (null, :name, :url, :rss, 0, :author, :des)", 
			array(':name' => $this->name, ':url' => $this->url, ':rss' => $this->rss,
			':author' => $this->author, ':des' => $this->description));
	}

	// in this we fetch a blog based on a bid
	public function fetch($bid)
	{
		$resultset = $this->dbh->query("SELECT blogname, blogurl, blogrss, access_ts, author, description ".
			"FROM blog WHERE bid = :id", array(':id' => $bid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->bid		= $bid;
			$this->name		= $row[0];
			$this->url		= $row[1];
			$this->rss		= $row[2];
			$this->accessts		= $row[3];
			$this->author		= $row[4];
			$this->description	= $row[5];

			return true;
		}
		else
		{
			return false;
		}
	}

	// in this we get an array of postids of posts in a blog
	public function getposts()
	{
		if($this->posts == null)
		{
			$this->posts = array();

			$resultset = $this->dbh->query("SELECT postid FROM post WHERE blogid = :id",
				array(':id' => $this->bid));

			if($resultset)
			{
				while(($row = $resultset->fetch()) != false)
				{
					$this->posts[] = $row[0];
				}
			}
		}

		return $this->posts;
	}

	// in this we get the average rating for the blog
	public function getrating()
	{
		if($this->rating == null)
		{
			$count = 0;

			foreach($this->posts as $p)
			{
				$this->rating += $p->getrating();
				$count++;
			}

			$this->rating = ($count > 0) ? ((float)$this->rating) / ($count * 1.0) : 0.0;
		}

		return $this->rating;
	}

	// get methods
	public function getid()		{ return $this->bid; }
	public function getname()	{ return $this->name; }
	public function geturl()	{ return $this->url; }
	public function getrss()	{ return $this->rss; }
	public function getauthor()	{ return $this->author; }
	public function getdesc()	{ return $this->description; }
	public function getts()		{ return $this->accessts; }
}

?>
