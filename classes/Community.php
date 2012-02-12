<?php

/******************************************************************************************
	
	Community Class - Kottu 9

	Stores community data
	
	07/12/11	Janith		Created community class.
	
******************************************************************************************/

class Community
{
	private $comid;
	private $name;
	private $timestamp;
	private $description;
	private $coordinates;
	private $users = null;
	private $mods = null;

	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}


	// we create a new community from user input
	public function create($name, $description, $creator) 
	{
		$this->name		= $name;
		$this->description	= $description;
		$this->timestamp	= time();

		$com = $this->dbh->query("INSERT INTO community(comid, name, description, timestamp, coordinates)".
			" VALUES (null, :name, :des, :ts, null)",
			array(':name' => $this->name, ':des' => $this->description, ':ts' => $this->timestamp));

		if($com === true)
		{
			$com = $this->dbh->query("INSERT INTO commuser(comid, uid, timestamp, moderator, approved)".
			" VALUES (:com, :user, :ts, true, true)",
			array(':com' => $this->getlastcomid(), ':user' => $creator, ':ts' => $this->timestamp));
		}

		return ($com === true);
	}

	// in this we fetch a community based on comid
	public function fetch($comid)
	{
		$resultset = $this->dbh->query("SELECT name, description, timestamp, coordinates ".
			"FROM community WHERE comid = :id", array(':id' => $comid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->comid		= $comid;
			$this->name		= $row[0];
			$this->description	= $row[1];
			$this->timestamp	= $row[2];
			$this->coordinates	= $row[3];

			$resultset = $this->dbh->query("SELECT uid, moderator FROM commuser ".
			"WHERE comid = :id AND approved = 1 ORDER BY timestamp", array(':id' => $comid));

			while(($row = $resultset->fetch()) != false)
			{
				$this->users[]	= $row[0];
				
				if($row[1]) { $this->mods[] = $row[0]; }
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	// a user is requesting to join the community
	public function requestjoin($userid)
	{
		$this->dbh->query("INSERT INTO commuser(comid, uid, timestamp, moderator, approved)".
			" VALUES (:com, :user, :ts, false, false)", 
			array(':id' => $this->comid, ':user' => $userid, ':ts' => time()));
	}

	// approving that request
	public function approvejoin($userid)
	{
		$this->users[] = $userid;

		$this->dbh->query("UPDATE commuser SET approved = true WHERE uid = :user AND comid = :id", 
			array(':id' => $this->comid, ':user' => $userid));
	}

	// get the list of uids for people requesting to join the community
	public function getreqlist()
	{
		$names = array();

		$resultset = $this->dbh->query("SELECT uid FROM commuser WHERE approved = 0 ".
			"AND comid = :comid", array(':comid' => $this->comid));

		if($resultset)
		{
			while(($row = $resultset->fetch()) != false)
			{
				$names[] = $row[0];
			}
		}

		return $names;
	}

	// adding a moderator to this community
	public function addmod($userid)
	{
		$this->mods[] = $userid;

		$this->dbh->query("UPDATE commuser SET moderator = true WHERE uid = :user AND comid = :id", 
			array(':id' => $this->comid, ':user' => $userid));
	}

	// deleting a moderator
	public function deletemod($userid)
	{
		$this->dbh->query("UPDATE commuser SET moderator = false WHERE uid = :user AND comid = :id", 
			array(':id' => $this->comid, ':user' => $userid));

		// TODO: refetch community after deleting a mod
	}

	// deleting a user
	public function deleteuser($userid)
	{
		$this->dbh->query("DELETE FROM commuser WHERE uid = :user AND comid = :id", 
			array(':id' => $this->comid, ':user' => $userid));

		// TODO: refetch community after deleting a user
	}

	// this returns last created comid
	public function getlastcomid()
	{
		$output = null;
		$resultset = $this->dbh->query("SELECT MAX(comid) FROM community", array());

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$output = $row[0];
		}
		
		return $output;
	}

	// get the community activity feed
	public function getactivity()
	{
		$activity = array();

		$res = $this->dbh->query("(SELECT 'p', p.postid, c.uid, p.timestamp FROM post as p, ".
			"blog as b, commuser as c WHERE b.bid = p.blogid AND b.author = c.uid AND ".
			"c.comid = :comid AND c.approved = 1 ORDER BY p.timestamp DESC LIMIT 20) UNION".
			"(SELECT 'c', c.cid, p.title, c.timestamp FROM post as p, commuser as co, ".
			"comment as c, user as u WHERE c.author = u.name AND u.uid = co.uid  AND co.approved = 1 ".
			"AND co.comid = :comid AND p.postid = c.postid ORDER BY c.timestamp DESC LIMIT 20)", 
			array(':comid' => $this->comid));

		if($res)
		{
			while($row = $res->fetch())
			{
				$activity[$row[3]] = array('postid' => $row[1], 'uid' => $row[2], 'type' => $row[0]);
			}

			krsort($activity);
			$activity = array_slice($activity, 0, 20);
		}

		return $activity;
	}

	// get the annid of last announcement in this community
	public function getlastannid()
	{
		$id = null;

		$resultset = $this->dbh->query("SELECT annid, removed FROM announce WHERE comid = :comid ".
				"ORDER BY annid DESC LIMIT 1", array(':comid' => $this->comid));

		if($resultset && ($row = $resultset->fetch()) != false && $row[1] != 1)
		{
			$id = $row[0];
		}

		return $id;
	}

	// get the list of announcements in this community
	public function getannlist()
	{
		$ids = array();

		$resultset = $this->dbh->query("SELECT annid FROM announce WHERE comid = :comid ".
				"ORDER BY annid DESC LIMIT 20", array(':comid' => $this->comid));

		if($resultset)
		{
			while(($row = $resultset->fetch()) != false)
			{
				$ids[] = $row[0];
			}
		}

		return $ids;
	}

	// get an announcement by annid
	public function getann($annid)
	{
		$ann = null;

		$resultset = $this->dbh->query("SELECT a.comid, a.timestamp, a.content, a.removed, u.name ".
				"FROM announce as a, user as u WHERE a.annid = :annid AND a.uid = u.uid",
				array(':annid' => $annid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$ann = array('comid' => $row[0], 'ts' => $row[1], 'cont' => $row[2], 
				'removed' => $row[3], 'user' => $row[4]);
		}

		return $ann;
	}

	// add an announcement
	public function addann($userid, $content)
	{
		 $this->dbh->query("INSERT INTO announce(annid, comid, uid, timestamp, content, removed)".
			" VALUES (null, :com, :user, :ts, :cont, false)", 
			array(':com' => $this->comid, ':user' => $userid, ':ts' => time(), ':cont' => $content));
	}

	// 'delete' an announcement
	public function removeann($annid)
	{
		 $this->dbh->query("UPDATE announce SET removed = 1 WHERE annid = :annid", 
			array(':annid' => $annid));
	}

	// get methods
	public function getid()		{ return $this->comid; }
	public function getname()	{ return $this->name; }
	public function gettime()	{ return $this->timestamp; }
	public function getdesc()	{ return $this->description; }
	public function getloc()	{ return $this->coordinates; }
	public function getmods()	{ return $this->mods; }
	public function getusers()	{ return $this->users; }

}

?>
