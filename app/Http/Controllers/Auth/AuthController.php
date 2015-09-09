<?php

namespace App\Controllers\Auth;

use Validator;

/**
 * Registration and Login Controller
 */
class AuThController extends Controller
{

  use AuthenticateAndRegistersUsers;

  function __construct(argument)
  {
    # code...
  }

  public function validator(array $data)
  {
    return Validator::make($data, [
      'username' => 'required|max:30'
      'password' => 'required|confirmed|min:12'
    ]);
  }

  public function create(array $data)
  {
    return User::create([
      'username' => $data['username'],
      'password' => bcrypt($data['password'])
      ]);
  }
}

?>
