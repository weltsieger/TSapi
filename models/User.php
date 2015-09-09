<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Dies ist das Model für den Benutzer
 */
class User extends Eloquent
{
  protected $fillable = ['username','first_name','last_name'];
  protected $guarded  = ['id', 'password'];

  private function sessionIsValid()
  {
    return true;
  }
  /**
  * Regis
  */
  public function register($username='', $password='')
  {
    if (User::where('username','=', $username)->get()) {
      // Benutzer exstiert schon
      return false;
    } else {
      $user = new User;
      $user->username = $username;
      
    }

    $user = ;

    $dbConnection = $this->connectToDb();
    if ($dbConnection === FALSE) {
        return;
    }

    $username = $dbConnection->real_escape_string($username);
    $password = $dbConnection->real_escape_string($password);

    $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "user (username, password) VALUES ('" . $username . "', '" . $password . "')";

    if ($dbConnection->query($sql) === TRUE) {
        $this->return['data'] = array('success' => true);
    } else {
        $this->return['data'] = array('success' => false);
        $this->return['status']['statuscode'] = '???.' . __LINE__;
        $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        exit();
    }
  }

  public function delete($sessionId='')
  {
    # code...
  }

  public function login($username='', $currentPassword='')
  {
    # code...
  }

  public function logout($sessionId='')
  {
    # code...
  }

  public function getUsername($sessionId='')
  {
    if (sessionIsValid) {
      return $username;
    }
  }

  public function setUsername($sessionId='', $newName='')
  {
    # code...
  }

  public function setPassword($sessionId='', $currentPassword='', $newPassword='')
  {
    # code...
  }

  public function validatePassword($sessionId='', $currentPassword='')
  {
    # code...
  }

  public function getAllSessions($sessionId='')
  {
    # code...
  }

  public function getAllIdentities($sessionId='')
  {
    # code...
  }

  public function addIdentity($sessionId='', $identity='')
  {
    # code...
  }
}

?>
