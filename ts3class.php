<?php

require_once("../config.php");

class api {

    private $_sessionPeriod = '50 minute';
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

                $this->return['status']['statuscode'] = '???.' . __LINE__;
                $this->return['status']['message'] = "DB-Connection failed: " . self::$_mySqlConnection->connect_error;
                exit;
            }
        }

        return self::$_mySqlConnection;
    }

    private function connectToTs() {

        if (!self::$_tsConnection) {

            $ts_host = globalConfig::$ts_host;
            $ts_username = globalConfig::$ts_username;
            $ts_password = globalConfig::$ts_password;

            try {
                // load framework files
                require_once("libraries/TeamSpeak3/TeamSpeak3.php");
                // connect to local server, authenticate and spawn an object for the virtual server on port 9987
                self::$_tsConnection = TeamSpeak3::factory("serverquery://" . $ts_username . ":" . $ts_password . "@" . $ts_host . ":10011/?server_port=9987&nickname=SecurityBot");
            } catch (Exception $ex) {
                $this->return['status']['statuscode'] = '???.' . __LINE__;
                $this->return['status']['message'] = "TS-Connection-Error: " . $ex->getTraceAsString();
                exit;
            }
        }

        return self::$_tsConnection;
    }

    private function checkSession($sessionId) {
        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return false;
        }

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "session WHERE expire <= now()";

        if ($dbConnection->query($sql) !== TRUE) {
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit;
        }

        $sessionId = $dbConnection->real_escape_string($sessionId);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows == 1) {
            $sql = "UPDATE " . globalConfig::$tbl_prefix . "session SET expire = now() + INTERVAL " . $this->_sessionPeriod;

            if ($dbConnection->query($sql) !== TRUE) {
                $this->return['status']['statuscode'] = '???.' . __LINE__;
                $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
                exit;
            }

            return true;
        } else {
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Die Session ist tot!";
            exit;
        }
    }

    private function getIdentityGroups($identity) {
        $tsConnection = $this->connectToTs();

        $tsClient = $tsConnection->clientGetByUid($identity);
        $identityGroups = array();

        foreach ($tsClient->memberOf() as $group) {
            //-- nur die Server-Gruppen ausgeben
            if ($group instanceof TeamSpeak3_Node_Channelgroup) {
                continue;
            }
            $identityGroups[] = $group->getId();
        }

        return $identityGroups;
    }

    private function addIdentityGroups($identity, $groups) {
        $tsConnection = $this->connectToTs();

        try {
            $tsClient = $tsConnection->clientGetByUid($identity);
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $log_str = date("Y-m-d H:i:s - ") . "addIdentityToGroup: id: " . $identity . " - group: " . $group . "\n";
                    $fs = fopen('log.log', "a");
                    fwrite($fs, $log_str);
                    fclose($fs);
                    try {
                        $tsClient->addServerGroup($group);
                    } catch (Exception $ex) {
                        echo "Fehler: " . $ex->getMessage();
                    }
                }
            } else {
                $fs = fopen('log.log', "a");
                fwrite($fs, date("Y-m-d H:i:s - ") . "addIdentityToGroup: id: " . $identity . " - group: " . $groups . "\n");
                fclose($fs);
                $tsClient->addServerGroup($groups);
            }
        } catch (Exception $ex) {
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler beim hinzufügen einer Gruppe: " . $ex->getMessage();
            print_r($ex);
            exit;
        }
    }

    private function deleteIdentityGroups($identity, $groups) {
        $tsConnection = $this->connectToTs();

        try {
            $tsClient = $tsConnection->clientGetByUid($identity);
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $log_str = date("Y-m-d H:i:s - ") . "deleteIdentityToGroup: id: " . $identity . " - group: " . $group . "\n";
                    $fs = fopen('log.log', "a");
                    fwrite($fs, $log_str);
                    fclose($fs);
                    try {
                        $tsClient->remServerGroup($group);
                    } catch (Exception $ex) {
                        $this->return['status']['statuscode'] = '???.' . __LINE__;
                        $this->return['status']['message'] = "Fehler beim löschen einer Gruppe: " . $ex->getMessage();
                        exit();
                    }
                }
            } else {
                $fs = fopen('log.log', "a");
                fwrite($fs, date("Y-m-d H:i:s - ") . "deleteIdentityToGroup: id: " . $identity . " - group: " . $groups . "\n");
                fclose($fs);
                $tsClient->remServerGroup($groups);
            }
        } catch (Exception $ex) {
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler beim löschen einer Gruppe: " . $ex->getMessage();
            exit;
        }
    }

    public function functionlist() {

        $this->return['data'] = array(
            array('functionname' => 'functionlist', 'param' => '', 'return' => 'array(functionname array(param array(name: type), raturn array())'),
            array('functionname' => 'register', 'param' => 'username: string, password: string', 'return' => 'success: boolean')
        );
    }

    public function register($username, $password) {

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

    public function login($username, $password) {

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $username = $dbConnection->real_escape_string($username);
        $password = $dbConnection->real_escape_string($password);

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
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Datenbank-Fehler! " . $result->num_rows;
            exit;
        }

        $sessionId = uniqid('', true);

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "session (id, user_id, expire) VALUES ('" . $sessionId . "', " . $userId . ", now() + INTERVAL " . $this->_sessionPeriod . ")";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('sessionId' => $sessionId);
        } else {
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit;
        }
    }

    public function logout($sessionId) {

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($sessionId);

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";

        if ($dbConnection->query($sql) === TRUE) {
            echo "Record deleted successfully";
        } else {
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Logout-DB-Fehler" . $dbConnection->error;
            exit();
        }
        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit();
        }
    }

    public function getUsername($sessionId) {

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($sessionId);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "user, " . globalConfig::$tbl_prefix . "session WHERE " . globalConfig::$tbl_prefix . "session.id = '" . $sessionId . "' AND " . globalConfig::$tbl_prefix . "user.id = " . globalConfig::$tbl_prefix . "session.user_id";
        $result = $dbConnection->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->return['data'] = array('username' => $row["username"]);
        } else {
            $this->return['data'] = array('username' => "");
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "DB-Fehler getUsername" . $dbConnection->error;
            exit();
        }
    }

    public function setUsername($sessionId, $newName) {

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($sessionId);
        $newName = $dbConnection->real_escape_string($newName);

        $sql = "UPDATE " . globalConfig::$tbl_prefix . "user as u, " . globalConfig::$tbl_prefix . "session as s SET u.username = '" . $newName . "' WHERE s.id = '" . $sessionId . "' AND u.id = s.user_id";

        if ($dbConnection->query($sql) === TRUE) {
            $this->return['data'] = array('success' => true);
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit();
        }
    }

    public function setPassword() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['newPassword']) || !isset($_REQUEST['oldPassword'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
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
                $this->return['status']['statuscode'] = '???.' . __LINE__;
                exit();
            }
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit();
        }
    }

    public function getIdentities() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);

        $sql = "SELECT " . globalConfig::$tbl_prefix . "identity.id as identity FROM " . globalConfig::$tbl_prefix . "identity, " . globalConfig::$tbl_prefix . "session WHERE " . globalConfig::$tbl_prefix . "session.id = '" . $sessionId . "' AND " . globalConfig::$tbl_prefix . "identity.user_id = " . globalConfig::$tbl_prefix . "session.user_id";
        $result = $dbConnection->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $this->return['data'][] = urldecode($row["identity"]);
            }
        } else {
            $this->return['data'] = array();
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "DB-Fehler getIdentities";
            exit();
        }

        return $this->return['data'];
    }

    public function addIdentity() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['identity'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);
        $identity = $dbConnection->real_escape_string(urlencode($_REQUEST['identity']));

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows > 0) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Identität ist beireits registriert.";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $sql = "INSERT INTO " . globalConfig::$tbl_prefix . "identity (id, user_id, validation_key) SELECT '" . $identity . "', user_id, 'none' FROM " . globalConfig::$tbl_prefix . "session WHERE id = '" . $sessionId . "'";

        if ($dbConnection->query($sql) === TRUE) {
            //$this->return['data'] = array('message' => "erfolgreich hinzugefügt");
        } else {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit();
        }
        $this->sendIdentityValidationkey($sessionId, urldecode($identity));
    }

    public function deleteIdentity() {
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['identity'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }

        $dbConnection = $this->connectToDb();
        if ($dbConnection === FALSE) {
            return;
        }

        $sessionId = $dbConnection->real_escape_string($_REQUEST['sessionId']);
        $identity = $dbConnection->real_escape_string(urlencode($_REQUEST['identity']));

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows != 1) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Identität nicht gefunden.";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $sql = "DELETE FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity . "'";

        if ($dbConnection->query($sql) === TRUE) {
            $groups = $this->getIdentityGroups($identity);
            $this->deleteIdentityGroups($identity, $groups);
        } else {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            exit();
        }
    }

    public function sendIdentityValidationkey($sessionId, $identity) {
        $this->checkSession($sessionId);

        $dbConnection = $this->connectToDb();

        $identity_encode = $dbConnection->real_escape_string(urlencode($identity));

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity_encode . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows != 1) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Identität nicht gefunden";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $validationKey = uniqid();
        $sql = "UPDATE " . globalConfig::$tbl_prefix . "identity SET validation_key = '$validationKey', validation_expire = now() + INTERVAL 1 hour WHERE id = '" . $identity_encode . "'";

        if ($dbConnection->query($sql) === TRUE) {
            if ($dbConnection->affected_rows == 1) {
                $this->return['data'] = array('validationKey' => $validationKey);

                $tsConnection = $this->connectToTs();

                try {
                    $tsClient = $tsConnection->clientGetByUid($identity);
                    $tsClient->poke("Validation-Key: " . $validationKey);
                } catch (Exception $ex) {
                    $this->return['status']['statuscode'] = '???.' . __LINE__;
                    $this->return['status']['message'] = "Fehler beim Übermitteln des Validation-Key: " . $ex->getMessage();
                    print_r($ex);
                    exit;
                }
            } else {
                $this->return['data'] = array('success' => false);
                $this->return['status']['message'] = "DB inkonsistenz " . $dbConnection->affected_rows;
                $this->return['status']['statuscode'] = '???.' . __LINE__;
                exit();
            }
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit();
        }
    }

    public function validateIdentity($sessionId, $identity, $validationKey) {

        $dbConnection = $this->connectToDb();

        $sessionId = $dbConnection->real_escape_string($sessionId);
        $identity = $dbConnection->real_escape_string(urlencode($identity));
        $validationKey = $dbConnection->real_escape_string(urlencode($validationKey));

        $this->checkSession($_REQUEST['sessionId']);

        $sql = "SELECT * FROM " . globalConfig::$tbl_prefix . "identity WHERE id = '" . $identity . "'";
        $result = $dbConnection->query($sql);

        if ($result->num_rows != 1) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Identität nicht gefunden";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $row = $result->fetch_assoc();
        $expire = $row['validation_expire'];
        $validation_key = $row['validation_key'];

        if (time() > strtotime($expire)) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Validierungszeit überschritten!";
            $this->return['data'] = array('success' => false);
            exit;
        }

        if ($validation_key != $validationKey) {
            $this->return['status']['statuscode'] = "???." . __LINE__;
            $this->return['status']['message'] = "Validationkey falsch";
            $this->return['data'] = array('success' => false);
            exit;
        }

        $sql = "UPDATE " . globalConfig::$tbl_prefix . "identity SET validation_key = '', validation_expire = ''";

        if ($dbConnection->query($sql) === TRUE) {
            if ($dbConnection->affected_rows == 1) {
                //-- TS gruppen hinzufügen!
                $this->tsSyncIdentityGroups();

                $this->return['data'] = array('success' => true);
            } else {
                $this->return['data'] = array('success' => false);
                $this->return['status']['message'] = "DB inkonsistenz";
                $this->return['status']['statuscode'] = '???.' . __LINE__;
                exit();
            }
        } else {
            $this->return['data'] = array('success' => false);
            $this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $dbConnection->error;
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            exit();
        }
    }

    public function tsSyncIdentityGroups() {
        $tsConnection = $this->connectToTs();
        $identities = $this->getIdentities();

//        $a = array(1, 2, 3);
//        $b = array(3, 4, 5);
//        $c = array(0, 6, 3);
//
//        $umerge = array_unique(array_merge($a, $b, $c));
//        $a_fehlt = array_diff($umerge, $a);
//        $b_fehlt = array_diff($umerge, $b);
//        $c_fehlt = array_diff($umerge, $c);
//
//        print_r($umerge);
//        print_r($a_fehlt);
//        print_r($b_fehlt);
//        print_r($c_fehlt);

        $mergeGroup = array();
        foreach ($identities as $identity) {
            $groups[$identity] = $this->getIdentityGroups($identity);
            $mergeGroup = array_merge($mergeGroup, $groups[$identity]);
        }

        $umerge = array_unique($mergeGroup);

        foreach ($identities as $identity) {
            $missedGroup = array_diff($umerge, $groups[$identity]);
            $this->addIdentityGroups($identity, $missedGroup);
        }
    }

}
