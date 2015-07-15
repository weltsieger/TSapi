<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../config.php");

    $ts_host = globalConfig::$ts_host;
    $ts_username = globalConfig::$ts_username;
    $ts_password = globalConfig::$ts_password;

    try {
        // load framework files
        require_once("libraries/TeamSpeak3/TeamSpeak3.php");
        // connect to local server, authenticate and spawn an object for the virtual server on port 9987
        $tsConnection = TeamSpeak3::factory("serverquery://" . $ts_username . ":" . $ts_password . "@" . $ts_host . ":10011/?server_port=9987e=Felix-User-Sync-Bot");
        
    $tsClient = $tsConnection->clientGetByUid(urldecode(urlencode("GC+HsrmjIDIrAsX4bkBjLH7ok2c="))); 
   print_r($tsClient);
    } catch (Exception $ex) {
        print_r($ex);
        exit;
    }
  