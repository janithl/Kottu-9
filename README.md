Kottu 9
=======

Coded by [Janith Leanage](http://janithl.blogspot.com)

This was a planned extension of [Kottu](https://github.com/janithl/Kottu),
which was aimed to make blogging more social-network-like. Even though
the project was never completed, the code is in working order with a few
kinks and details that need to be figured out.

License
-------

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. 

(see [license.txt](https://github.com/janithl/Kottu-9/blob/master/license.txt) 
for full AGPL license)

**(For those who don't get the legal lingo: Basically what we're saying is
feel free to copy our code, but please share back any changes or improvements
that you make, in the spirit of free software)**

External Libraries
------------------

This software uses the following external libraries and CSS files:

* [SimplePie](http://simplepie.org/) 
	Version 1.2.1-dev
	Copyright 2004-2010 Ryan Parman, Geoffrey Sneddon, Ryan McCue
	Released under the [BSD License](http://www.opensource.org/licenses/bsd-license.php)

* [Timthumb](http://code.google.com/p/timthumb/)
	Version 2.8.2
	Copyright Ben Gillbanks and Mark Maunder
	Based on work done by Tim McDaniels and Darren Hoyt
	Released under [GNU General Public License, version 2](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

* [YUI CSS Reset](http://yuilibrary.com/license/)
	Version 3.4.1 (build 4118)
	Copyright 2011 Yahoo! Inc. All rights reserved.
	Licensed under the BSD License

* [BlueBubble 3.1](http://bluebubble.dosmundoscafe.com/)
	Version 3.1 
	Copyright Thomas Veit (WP 3.0 updates by Mike Walsh)
	Released under [GNU GPL Version 3](http://www.opensource.org/licenses/gpl-3.0.html)

Folders, what is where?
-----------------------

* `./Classes` contains all the entity classes etc. There is a lot of business logic inside the entity classes
though. (I know. Bad practice. Yada yada)

* `./SimplePie` has the SimplePie library file. `./cache` is used by SimplePie to cache feeds.

* `./i` is the images folder, and has the Timthumb file at `./i/index.php`. `./i/u` is used for user profile
pictures, and `./i/cache` is the Timthumb cache.

Files in the root folder
------------------------

* `./feed.php` generates RSS feeds

* `./search.php` is for searching

* `./vote.php` lets people vote on posts

* `./profile.php` displays user profiles

* `./feedget.php` is the cronjob that fetches posts and comments

* `./index.php` does a lot of things (some of which I'm sure is illegal)

* `./login.php` enables Facebook logins

* `./register.php` enables people to register a user account

How to set up Kottu 9
---------------------

* Run `kottu9.sql` in a mySQL server (I'm not sure whether K9 works in Postgre, 
the hackers among you might like to give it a try ;) )
 
* Copy files to necessary locations, set up DB connection details in `./classes/DBConn.php`

* Set up a proper salt for security purposes in `./classes/User.php` (line 34)

* Create a Facebook app (for user authentication) and set the app ID and secret in `./login.php`

* After logging in with your Facebook account and setting up your account, go to the `database -> user 
table` and set the attribute `admin` to 1 against your name for administrative privileges

Misc Kinks
----------

I have not touched the code in ages, and there might be stuff I forgot to tell you. My commenting is sparse
and sometimes a little too terse. I apologize in advance. When I began writing 9, I had a lot of hope for this
software, that it would do good to help set up user moderated blogging communities. I hope that some other,
more experienced dev with some free time on his hands can take this to the next level. I wish you well. :)
