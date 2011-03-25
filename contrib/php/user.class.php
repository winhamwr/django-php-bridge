<?php
class User {
	private $id;
	private $username;
	private $first_name;
	private $last_name;
	private $email;
	private $password;
	private $is_staff;
	private $is_active;
	private $is_superuser;
	private $last_login;
	private $date_joined;


  function __construct($user_id = null) {
  	if(isset($user_id)){
  		$this->id = $user_id;
  		$this->load();
  	}
  }

  private function load(){
	$sql = "SELECT auth_user.id as 'id', username, first_name, last_name,
		email, password, is_staff, is_active, is_superuser, last_login,
		date_joined 
	FROM auth_user 
	WHERE auth_user.id = '$this->id'";

	$result = Database::runQuery($sql);
	while($row = mysql_fetch_assoc($result)){
		foreach($row as $key => $value){
			$this->$key = $value;
		}
	}
  }

  /**
   * Takes a salt and a password and returns the hashed password.
   * @param salt
   * @param password
   */
  public static function hashPassword($salt, $password){
	return 'sha1$'.$salt.'$'.sha1($salt.$password);
  }

  /**
   * Get the password hash from the django-formatted password column.
   * @param fullHash String in format Format: sha1$salt$hash
   */
  public static function getPasswordHash($fullHash){
  	$hashStart = strripos($fullHash, '$');
  	return substr($fullHash, $hashStart + 1);
  }
}
?>
