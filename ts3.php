<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../config.php");

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

    case 'getIdentities':
        $api->getIdentities();
        break;

    case 'addIdentity':
        $api->addIdentity();
        break;

    default:
        //-- not implemented
        $api->return['status']['statuscode'] = '???';
        $api->return['status']['message'] = "nicht implementiert";
        break;
}

class api {

    private $sessionPeriod = '5 minute';
    private static $_mySqlConnection;
    private static $_tsConnection;
    public $return = array(
        'status' => array(
            'statuscode' => '200',
            'message' => 'ok'
        ),
        'data' => array()
    );

    function __destruct() {
        echo json_encode($this->return);

        if (self::$_mySqlConnection && self::$_mySqlConnection->ping()) {
            self::$_mySqlConnection->close();
        }
    }

    private function connectToDb() {
        if (!self::$_mySqlConnection) {
            $servername = globalConfig::$servername;
            $username = globalConfig::$username;
            $password = globalConfig::$password;
            $dbname = globalConfig::$dbname;
            // Create connection
            self::$_mySqlConnection = new mysqli($servername, $username, $password, $dbname);
            // Check connection
            if (self::$_mySqlConnection->connect_error) {

                $this->return['status']['statuscode'] = '???';
                $this->return['status']['message'] = "DB-Connection failed: " . self::$_mySqlConnection->connect_error;
                exit;
            }
        }

        return self::$_mySqlConnection;
    }

    private function connectToTs() {

        if (!self::$_tsConnection) {

            $ts_username = globalConfig::$password;
            $ts_password = globalConfig::$password;

            try {
                // load framework files
                require_once("libraries/TeamSpeak3/TeamSpeak3.php");
                // connect to local server, authenticate and spawn an object for the virtual server on port 9987
                $_tsConnection = TeamSpeak3::factory("serverquery://" . $ts_username . ":" . $ts_password . "@127.0.0.1:10011/?server_port=9987");
            } catch (Exception $ex) {
                $this->return['status']['statuscode'] = '???';
                $this->return['status']['message'] = "TS-Connection-Error: " . $ex->getTraceAsString();
                exit;
            }
        } else {
            return self::$_tsConnection;
        }
    }

    private function checkSession($sessionId) {
        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return false;
        }

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "session WHERE expire <= now()";

        if ($dbConnection->query($sql) !== TRUE) {
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit;
        }

        $sessionId = $dbConnection->real_escape_string($sessionId);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows == 1) {
            $sql = "UPDATE " . globalConfig::$tbl_prefix . "session SET expire = now() + INTERVAL " . $this->sessionPeriod;

            if ($dbConnection->query($sql) !== TRUE) {
                $this->return['status']['statuscode'] = '???';
                $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
                exit;
            }

            return true;
        } else {
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Die Session ist tot!";
            exit;
        }
    }

    private function tsSyncIdentityGroups() {
        
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
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
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
            exit();
        }
    }

    public function login() {
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit();
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
            $this->return['status']['message'] = "Datenbank-Fehler! " . $result->num_rows;
            exit;
        }

        $sessionId = uniqid('', true);

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "session (id, user_id, expire) VALUES ('" . $sessionId . "', " . $userId . ", now() + INTERVAL " . $this->sessionPeriod . ")";

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
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";

        if ($dbConnection->query($sql) === TRUE) {
            echo "Record deleted successfully";
        } else {
            echo "Error deleting record: " . $dbConnection->error;
            exit();
        }
        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit();
        }
    }

    public function getUsername() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
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
            exit();
        }
    }

    public function setUsername() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['newName'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit();
            ;
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
            exit();
        }
    }

    public function setPassword() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['newPassword']) || !isset($_REQUEST['oldPassword'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit();
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
                exit();
            }
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit();
        }
    }

    public function getIdentities() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);

        $sql = "SELECT " . globalConfig::$tbl_prefix . "identity.id as identity FROM " . globalConfig::$tbl_prefix . "identity, " . globalConfig::$tbl_prefix . "session WHERE " . globalConfig::$tbl_prefix . "session.id = '" . $sessionId . "' AND " . globalConfig::$tbl_prefix . "identity.user_id = " . globalConfig::$tbl_prefix . "session.user_id";
        $result = $dbConnection->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $this->return['data'][] = $row["identity"];
            }
        } else {
            $this->return['data'] = array();
            $this->return['status']['statuscode'] = "??";
            exit();
        }
    }

    public function addIdentity() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['identity'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???';
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);
        $identity = $dbConnection->real_escape_string($_REQUEST['identity']);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows > 0) {
            $this->return['status']['statuscode'] = "??";
            $this->return['status']['message'] = "Identität ist beireits registriert.";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "identity (id, user_id) SELECT '" . $identity . "', user_id FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('message' => "erfolgreich hinzugefügt");
        } else {
            $this->return['status']['statuscode'] = "??";
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit();
        }

        //-- TS gruppen hinzufügen!
        $tsConnection = $this->connectToTs();
    }

}
