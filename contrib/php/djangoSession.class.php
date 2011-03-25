<?php

define('TWO_WEEKS', 1209600);

/**
 * A class used to override the default PHP session handling to sync up with a
 * django_session variant. This version is mysql-specific, but can easily be
 * modified for your flavor database. open() will need particular db-specific
 * care.
 * 
 * This class currently relies on the existance of a Database class to do all
 * of the heavy lifting. A refactor to eliminate this or make it more flexible
 * would be welcome.
 *
 * Usage: In some pre-session_register location, call:
 * DjangoSession::register();
 * Then use $_SESSION as normal.
 */
class DjangoSession {

  /**
   * Register this session handler as the PHP session handler.
   */
  public static function register(){
  	session_set_save_handler(array('DjangoSession', 'open'),
  		array('DjangoSession', 'close'), array('DjangoSession', 'read'),
  		array('DjangoSession', 'write'), array('DjangoSession', 'destroy'),
  		array('DjangoSession', 'clean'));
  }

  public static function open(){
  	/**
  	 * Do whatever you need to do to connect to your database here ex:
  	 * mysql_pconnect(HOST, DB, DB_PW) or die("Could not connect to SQL
  	 * server.");
  	 * mysql_select_db(DB) or die(mysql_error());
  	 * A "gotcha" here is that if you need to use a different database for
  	 * normal data, you'll have to do some extra work to insure the correct
  	 * database connection is open for your session and for your normal data.
  	 */
  }

  public static function close(){
  	//If you have multiple DB's open, do some work here to close to correct one
  	//mysql_close();
  }

  public static function read($session_key){
  	$session_key = mysql_real_escape_string($session_key);
  	$sql = "SELECT session_data
						FROM django_session
						WHERE session_key = '$session_key'";

		if($result = Database::runQuery($sql)){
			if(mysql_num_rows($result) > 0){
				$record = mysql_fetch_assoc($result);
				return $record['session_data'];
			}else{
				return '';
			}
		}else{
			return '';
		}

  }

	/**
	 * Write the new data in to the session.
	 * @param session_key
	 * @param session_data
	 */
  public static function write($session_key, $session_data){
		//Unix timestamp expiration using the php session expiration config
		$expire_date = time() + TWO_WEEKS;
		//Convert to a mysql DATETIME value
		$expire_date = date('Y-m-d H:i:s', $expire_date);

		$session_key = mysql_real_escape_string($session_key);
		//We're keeping expire_date instead of last access
		//This departs from how PHP keeps sessions, but jives with Django
		$expire_date = mysql_real_escape_string($expire_date);
		$session_data = mysql_real_escape_string($session_data);

		$sql = "SELECT session_key
						FROM django_session
						WHERE session_key = '$session_key'";
		$result = Database::runQuery($sql);
		$num = mysql_num_rows($result);
		if($num ==1){
			$sql = "UPDATE django_session
							SET session_data = '$session_data',
								expire_date = '$expire_date'
							WHERE session_key = '$session_key'";
		}else{
			$sql = "INSERT INTO django_session(session_key, session_data, expire_date)
						VALUES('$session_key', '$session_data', '$expire_date')";
		}

		return Database::runQuery($sql);
  }

  public static function destroy($session_key){
  	$session_key = mysql_real_escape_string($session_key);
  	$sql = "DELETE
						FROM django_session
						WHERE session_key = '$session_key'";
		return Database::runQuery($sql);
  }

  /**
   * Clean out old sessions from the DB. We're ignoring the PHP configuration
   * that determines default session length to more-closely match the way Django
   * handles session expiration.
   * @param max Ignored
   */
  public static function clean($max){
  	$sql = "DELETE
						FROM django_session
						WHERE expire_date < NOW()";
		return Database::runQuery($sql);
  }
}
?>
