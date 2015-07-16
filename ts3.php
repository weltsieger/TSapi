<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'ts3class.php';

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
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            // ERROR
            $api->return['status']['statuscode'] = '???.' . __LINE__;
            $api->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        } else {
            $api->register($_REQUEST['username'], $_REQUEST['password']);
        }

        break;

    case 'login':
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            // ERROR
            $api->return['status']['statuscode'] = '???.' . __LINE__;
            $api->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        } else {
            $api->login($_REQUEST['username'], $_REQUEST['password']);
        }
        break;

    case 'logout':
        if (!isset($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        } else {
            $api->logout($_REQUEST['sessionId']);
        }
        break;

    case 'deleteUser':
        break;

    case 'getUsername':
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        } else {
            $api->getUsername($_REQUEST['sessionId']);
        }
        break;

    case 'setUsername':
        if (!isset($_REQUEST['sessionId']) || !$this->checkSession($_REQUEST['sessionId']) || !isset($_REQUEST['newName'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit();
        } else {
            $api->setUsername($_REQUEST['sessionId'], $_REQUEST['newName']);
        }

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

    case 'deleteIdentity':
        $api->deleteIdentity();
        break;

    case 'validateIdentity':
        if (!isset($_REQUEST['sessionId']) || !isset($_REQUEST['identity']) || !isset($_REQUEST['validationKey'])) {
            // ERROR
            $this->return['status']['statuscode'] = '???.' . __LINE__;
            $this->return['status']['message'] = "Fehler bei der Parameter-Übergabe" . __LINE__;
            exit;
        }
        $sessionId = $_REQUEST['sessionId'];
        $identity = $_REQUEST['identity'];
        $validationKey = $_REQUEST['validationKey'];
        $api->validateIdentity($sessionId, $identity, $validationKey);
        break;

    case 'tsSyncIdentityGroups':
        $api->tsSyncIdentityGroups();
        break;

    default:
        //-- not implemented
        $api->return['status']['statuscode'] = '???';
        $api->return['status']['message'] = "nicht implementiert";
        break;
}

