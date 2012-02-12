<?php

/******************************************************************************************
	
	User Class - Kottu 9

	Stores user data

	Based on User class on Project Roku:
	
	15/11/11	Janith		Created user class.
	21/11/11	Janith		Added the password/detail change funcs.
	22/11/11	Janith		Added notifications

	==================================================================================

	07/12/11	Janith		Began work on Kottu user class
	
******************************************************************************************/

class User
{
	private $uid;
	private $name;
	private $email;
	private $pw;
	private $pic;
	private $ts;
	private $bio;
	private $notifts;
	private $admin = false;
	private $votes = null;

	private $salt = 'salt';
	private $dbh;

	public function __construct() 
	{
		$this->dbh = new DBConn();
	}

	// create a user from facebook login
	// TODO: In the UI end of this, when a user signs up, ask him whether he'd like to use his Facebook 
	// image/profile details. If he doesn't pick a pic, give him a default pic there. Also get him to pick a username.
	public function create_fb($id, $name, $email, $pic, $bio)
	{
		$this->uid	= 'fb_'.$id;
		$this->name	= $name;
		$this->pw	= hash('sha256', $this->uid.$this->salt);
		$this->pic	= preg_replace('/&*(w=|h=)[0-9]+/', '', $pic);
		$this->bio	= $bio;
		$this->email	= $email;

		$result		= $this->dbh->query("INSERT INTO user(uid, name, email, pw, ts, admin, pic, bio)".
				" VALUES (:uid, :name, :email, :pw, :ts, false, :pic, :bio)", 
				array(':uid' => $this->uid, ':name' => $this->name, ':email' => $this->email,
				':pw' => $this->pw, ':ts' => time(), ':pic' => $this->pic, ':bio' => $this->bio));

		return ($result === true);
			
	}

	// we create a new user from user input
	public function create($name, $password, $pic, $bio) 
	{
		$this->uid	= 'kt_'.urlencode($name);
		$this->name	= $name;
		$this->pw	= hash('sha256', $password.$this->salt);
		$this->pic	= preg_replace('/&*(w=|h=)[0-9]+/', '', $pic);
		$this->bio	= $bio;
		
		$result 	= $this->dbh->query("INSERT INTO user(uid, name, email, pw, ts, admin, pic, bio)".
				" VALUES (:uid, :name, :email, :pw, :ts, false, :pic, :bio)", 
				array(':uid' => $this->uid, ':name' => $this->name, ':email' => $this->email,
				':pw' => $this->pw, ':ts' => time(), ':pic' => $this->pic, ':bio' => $this->bio));

		return ($result === true);
	}

	// update the pic
	public function setpic($pic) 
	{
		$this->pic	= preg_replace('/&*(w=|h=)[0-9]+/', '', $pic);
		
		$this->dbh->query("UPDATE user SET pic = :pic WHERE uid = :id", 
				array(':id' => $this->uid, ':pic' => $this->pic));
	}

	// update the bio
	public function setbio($bio) 
	{
		$this->bio	= $bio;
		
		$this->dbh->query("UPDATE user SET bio = :bio WHERE uid = :id", 
				array(':id' => $this->uid, ':bio' => $this->bio));
	}

	// here we fetch a user from the database
	public function fetch($uid)
	{
		$resultset = $this->dbh->query("SELECT name, pw, ts, admin, pic, bio, notifts, email FROM user " .
				"WHERE uid LIKE :id", array(':id' => $uid));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			$this->uid	= $uid;
			$this->name	= $row[0];
			$this->pw	= $row[1];
			$this->ts	= $row[2];
			$this->admin	= $row[3];
			$this->pic	= $row[4];
			$this->bio	= $row[5];
			$this->notifts	= $row[6];
			$this->email	= $row[7];

			return true;
		}
		else
		{
			return false;
		}
	}

	// fetch a user by name
	public function fetchbyname($name)
	{
		$resultset = $this->dbh->query("SELECT uid FROM user WHERE name LIKE :name",
				array(':name' => $name));

		if($resultset && ($row = $resultset->fetch()) != false)
		{
			return $this->fetch($row[0]);
		}
		else
		{
			return false;
		}
	}

	// and here we authenticate the user's password
	public function auth($password)
	{
		$password = hash('sha256', $password.$this->salt);

		return ($this->pw === $password);
	}

	// change passwords
	public function changepwd($oldpass, $newpass)
	{
		$change = false;
		if($this->auth($oldpass))
		{
			$newpass = hash('sha256', $newpass.$this->salt);

			$res = $this->dbh->query("UPDATE user SET password = :pw WHERE username = :name", 
				array(':name' => $this->name, ':pw' => $newpass));

			if($res && $res->rowCount() > 0)
			{
				$change = true;
			}
		}

		return $change;
	}

	// get the user activity feed
	public function getactivity()
	{
		$activity = array();

		$res = $this->dbh->query("(SELECT 'p', p.postid, b.author, p.timestamp FROM post as p, ".
			"blog as b WHERE b.bid = p.blogid AND b.author = :uid ORDER BY p.timestamp DESC LIMIT 20) ".
			"UNION (SELECT 'c', c.cid, p.title, c.timestamp FROM post as p, comment as c ".
			"WHERE c.author LIKE :name AND p.postid = c.postid ORDER BY c.timestamp DESC LIMIT 20)", 
			array(':uid' => $this->uid, ':name' => $this->name));

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

	// get blogs belonging to user
	public function getblogs()
	{
		$blogs = array();

		$res = $this->dbh->query("SELECT blogname, blogurl FROM blog WHERE author = :uid", 
			array(':uid' => $this->uid));

		if($res)
		{
			while($row = $res->fetch())
			{
				$blogs[] = array('name' => $row[0], 'url' => $row[1]);
			}
		}

		return $blogs;
	}

	// get communities where user is a member
	public function getcommunities()
	{
		$com = array();

		$res = $this->dbh->query("SELECT cu.comid, c.name FROM commuser as cu, community as c ".
			"WHERE c.comid = cu.comid AND cu.approved = 1 AND cu.uid = :uid", array(':uid' => $this->uid));

		if($res)
		{
			while($row = $res->fetch())
			{
				$com[] = array('comid' => $row[0], 'name' => $row[1]);
			}
		}

		return $com;
	}

	// get methods
	public function getid()		{ return $this->uid; }
	public function getname()	{ return $this->name; }
	public function getpic()	{ return $this->pic; }
	public function gettime()	{ return $this->ts; }
	public function getbio()	{ return $this->bio; }
	public function getnotiftime()	{ return $this->notifts; }
	public function getadmin()	{ return ($this->admin == 1); }
}

?>
