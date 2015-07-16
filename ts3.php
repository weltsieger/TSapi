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

