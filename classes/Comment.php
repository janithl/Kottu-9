<?php

/******************************************************************************************
	
	Comment Class - Kottu 9

	Stores Comment data
	
	08/12/11	Janith		Started comment class
	
******************************************************************************************/

class Comment
{
	private $cid;
	private $postid;
	private $content;
	private $timestamp;
	private $author;
	private $permalink;
	private $votes = null;

	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}

	// we create a comment
	public function create($postid, $content, $timestamp, $author, $permalink)
	{
		$this->postid		= $postid;
		$this->author		= $author;
		$this->content		= $content;
		$this->timestamp	= $timestamp;
		$this->permalink	= $permalink;
		
		$this->dbh->query("INSERT INTO comment(cid, postid, author, content, timestamp, permalink) ".
			"VALUES (null, :pid, :author, :cont, :ts, :link)", 
			array(':ts' => $this->timestamp, ':link' => $this->link, ':cont' => $this->content,
			':author' => $this->author, ':pid' => $this->postid));
	}

	// in this we fetch a comment from cid
	public function fetch($cid)
	{
		$resultset = $this->dbh->query("SELECT postid, author, content, timestamp, permalink FROM comment".
		" WHERE cid = :id", array(':id' => $cid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->cid		= $cid;
			$this->postid		= $row[0];			
			$this->author		= $row[1];
			$this->content		= $row[2];
			$this->timestamp	= $row[3];
			$this->permalink	= $row[4];

			return true;
		}
		else
		{
			return false;
		}
	}

	// in this we get the number of votes a comment got
	public function getvotes()
	{
		if($this->votes == null)
		{
			$this->votes = 0;
			$resultset = $this->dbh->query("SELECT COUNT(voter) FROM vote WHERE cid = :id", 
							array(':id' => $this->cid));

			if($resultset && ($row = $resultset->fetch()) != false)
			{
				$this->votes = $row[0];
			}
		}

		return $this->votes;
	}

	// says if a user has voted for a particular comment or not
	public function hasvoted($userid)
	{
		$resultset = $this->dbh->query("SELECT cid FROM vote WHERE cid = :id AND voter = :user",
			array(':id' => $this->cid, ':user' => $userid));

		return ($resultset && ($row = $resultset->fetch()) != false);
	}

	// cast a vote, or undo a cast vote
	public function vote($userid, $username)
	{
		// no voting for your own comments, bandicoot!
		if($this->author !== $username)
		{
			// if vote is already cast, then uncast
			if($this->hasvoted($userid))
			{
				$this->dbh->query("DELETE FROM vote WHERE cid = :id AND voter = :user ", 
				array(':user' => $userid, ':id' => $this->cid));
			}
			else
			{
				$this->dbh->query("INSERT INTO vote(cid, voter, timestamp) ".
				"VALUES(:id, :user, :ts)", array(':user' => $userid, ':id' => $this->cid, ':ts' => time()));
			}
		}
	}


	// get methods
	public function getid()		{ return $this->cid; }
	public function getpost()	{ return $this->postid; }
	public function getauthor()	{ return $this->author; }
	public function getcont()	{ return $this->content; }
	public function gettime()	{ return $this->timestamp; }
	public function getlink()	{ return $this->permalink; }
		
}

?>
