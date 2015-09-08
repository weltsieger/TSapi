<?php

/**
 * Dies ist das Model für den Benutzer
 */
class User
{
  private $username = '';
  function __construct()
  {
    # code...
  }

  private function sessionIsValid()
  {
    # code...
  }
  public function register($username='', $password='')
  {
    # code...
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
    # code...
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
