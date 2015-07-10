<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../config.php");
/*
  // load framework files
  require_once("libraries/TeamSpeak3/TeamSpeak3.php");
  // connect to local server, authenticate and spawn an object for the virtual server on port 9987
  $ts3_VirtualServer = TeamSpeak3::factory("serverquery://felix_bot:YBslnXcx@5.230.4.187:10011/?server_port=9987");
  // build and display HTML treeview using custom image paths (remote icons will be embedded using data URI sheme)
  echo $ts3_VirtualServer->getViewer(new TeamSpeak3_Viewer_Html("images/viewericons/", "images/countryflags/", "data:image"));
 */

if (isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = 'functionlist';
}

$api = new api();

switch ($task) {
    case 'functionlist':
        $api->functionlist();
        break;

    case 'register':
        $api->register();
        break;

    case 'login':
        $api->login();
        break;

    case 'logout':
        $api->logout();
        break;

    case 'deleteUser':
        break;

    case 'getUsername':
        $api->getUsername();
        break;

    case 'setUsername':
        $api->setUsername();
        break;

    case 'setPassword':
        $api->setPassword();
        break;

    default:
        //-- not implemented
        $this->return['status']['statuscode'] = '???';
        $this->return['status']['message'] = "nicht implementiert";
        break;
}

echo json_encode($api->return);

class api {
    private static _mySqlConnection;
    
    public $return = array(
        'status' => array(
            'statuscode' => '200',
            'message' => 'ok'
        ),
        'data' => array()
    );
    
   function __destruct() {
       if (self::$_mySqlConnection) {
         self::$_mySqlConnection->close();
       }
   }
   
    private function connectToDb() {
        if (!self::$_mySqlConnection)
        {
          $servername = globalConfig::$servername;
          $username = globalConfig::$username;
          $password = globalConfig::$password;
          $dbname = globalConfig::$dbname;
  
          // Create connection
          $conn = new mysqli($servername, $username, $password, $dbname);
          // Check connection
          if ($conn->connect_error) {
              $this->return['status']['statuscode'] = '???';
              $this->return['status']['message'] = "DB-Connection failed: " . $conn->connect_error;
              return false;
          }
        }

        return self::$_mySqlConnection;
    }

    public function functionlist() {

        $this->return['data'] = array(
            array('functionname' => 'functionlist', 'param' => '', 'return' => 'array(functionname array(param array(name: type), raturn array())'),
            array('functionname' => 'register', 'param' => 'username: string, password: string', 'return' => 'success: boolean')
        );
    }

    public function register() {
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }
        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $username = $dbConnection->real_escape_string($_REQUEST['username']);
        $password = $dbConnection->real_escape_string($_REQUEST['password']);

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "user (username, password) VALUES ('" . $username . "', '" . $password . "')";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        }
    }

    public function login() {
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $username = $dbConnection->real_escape_string($_REQUEST['username']);
        $password = $dbConnection->real_escape_string($_REQUEST['password']);

        //-- Check Login-Daten
        //	 genrate Session-ID
        //	 Insert Into Session-DB

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "user WHERE username = '" . $username . "' AND password = '" . $password . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $userId = $row["id"];
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Datenbank-Fehler!";
            return;
        }

        $sessionId = uniqid('', true);

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "session (id, user_id, expire) VALUES (" . $userId . ", '" . $sessionId . "', now() + 5 minutes)";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('sessionId' => $sessionId);
        } else {
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        }
    }

    public function logout() {
        if (!isset($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }

        $dbConnection = $this->connectToDb();
        if ($this->return['status']['statuscode'] != '200') {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";

        if ($dbConnection->query($sql) === TRUE) {
            echo "Record deleted successfully";
        } else {
            echo "Error deleting record: " . $dbConnection->error;
        }
        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        }
    }

    public function getUsername() {
        if (!isset($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "user, " . globalConfig::$tbl_prefix . "session WHERE " . globalConfig::$tbl_prefix . "session.id = '" . $sessionId . "' AND " . globalConfig::$tbl_prefix . "user.id = " . globalConfig::$tbl_prefix . "session.user_id";
        $result = $dbConnection->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->return['data'] = array('username' => $row["username"]);
        } else {
            $this->return['data'] = array('username' => "");
            $this->return['status']['statuscode'] = "??";
        }
    }

    public function setUsername() {
        if (!isset($_REQUEST['sessionId']) || !isset($_REQUEST['newName'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);
        $newName = $dbConnection->real_escape_string($_REQUEST['newName']);

        $sql = "UPDATE " . globalConfig::$tbl_prefix . "user as u, " . globalConfig::$tbl_prefix . "session as s SET u.username = '" . $newName . "' WHERE s.id = '" . $sessionId . "' AND u.id = s.user_id";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        }
    }

    public function setPassword() {
        if (!isset($_REQUEST['sessionId']) || !isset($_REQUEST['newPassword']) || !isset($_REQUEST['oldPassword'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe";
            return;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);
        $newPassword = $dbConnection->real_escape_string($_REQUEST['newPassword']);
        $oldPassword = $dbConnection->real_escape_string($_REQUEST['oldPassword']);

        $sql = "UPDATE " . globalConfig::$tbl_prefix . "user as u, " . globalConfig::$tbl_prefix . "session as s SET u.password = '" . $newPassword . "' WHERE s.id = '" . $sessionId . "' AND u.id = s.user_id AND u.password = '" . $oldPassword . "'";

        if ($dbConnection->query($sql) === TRUE) {
            if ($dbConnection->affected_rows == 1) {
                $this->return['data'] = array('success' => true);
            } else {
                $this->return['data'] = array('success' => false);
                $this->return['status']['message'] = "DB inkonsistenz";
            }
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
        }
    }

}
