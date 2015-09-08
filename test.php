<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');

$ts3FameworkPath ="vendor/planetteamspeak/ts3phpframework/";

require_once("config.php");
require_once($ts3FameworkPath . "/libraries/TeamSpeak3/TeamSpeak3.php");

$ts_host = globalConfig::$ts_host;
$ts_username = globalConfig::$ts_username;
$ts_password = globalConfig::$ts_password;

try {

  /* connect to server, login and get TeamSpeak3_Node_Host object by URI */
  $ts3_ServerInstance = TeamSpeak3::factory("serverquery://" . $ts_username . ":" . $ts_password . "@" . $ts_host . ":10011/?server_port=9987&nickname=SecrurityBot");

  /* access server instance address using __toString() */
  echo "<h1>Client List - " . $ts3_ServerInstance . "</h1>\n";

  /* display server select form */
//  $selected_sid = form_server_selector($ts3_ServerInstance->serverList());

  /* get TeamSpeak3_Node_Server object by ID */
  $ts3_VirtualServer = $ts3_ServerInstance;

  $clientList = $ts3_VirtualServer->clientList();
  $serverGroupList = $ts3_VirtualServer->serverGroupList();
  $channelList = $ts3_VirtualServer->channelList();

  ksort($clientList);
  ksort($channelList);

 /* walk through list of clients */
  echo "<table class=\"list\">\n";
  echo "<tr>\n" .
       "  <th>ID</th>\n" .
       "  <th>Status</th>\n" .
       "  <th>Nickname</th>\n" .
       "  <th>Unique Identifier</th>\n" .
       "  <th>Platform</th>\n" .
       "  <th>Version</th>\n" .
       "  <th>Current-Channel</th>\n" .
       "  <th>IP</th>\n" .
	   "  <th>Uptime</th>\n" .
	   "  <th>Idle</th>" .
       "</tr>\n";
  foreach($clientList as $client)
  {
	$clinfo = $client->getInfo();

	if(!isset($clinfo['connection_connected_time'])) {
		$clinfo['connection_connected_time'] = 0;//3600000
	}
	$uptime = date_create(date("Y-m-d H:i:s" ,$clinfo['connection_connected_time']/1000));
	$startTime = date_create(date("Y-m-d H:i:s", 0));
	$uptime_diff = date_diff($startTime, $uptime);
	$idle_diff = date_diff($startTime, date_create(date("Y-m-d H:i:s" ,$clinfo['client_idle_time']/1000)));

/*
	$datetime1 = date_create('2009-10-11');
	$datetime2 = date_create('2009-10-13');
	$interval = date_diff($datetime1, $datetime2);
	echo $interval->format('%R%a days');
*/

    echo "<tr>\n" .
         "  <td>" . $client->getId() . "</td>\n" .
		 "  <td><img src=\"" . $ts3FameworkPath . "images/viewer/" . $client->getIcon() . ".png\" alt=\"" . $client->getIcon() . "\" title=\"" . $client->getIcon() . "\" /></td>\n".
         "  <td><a href=\"?page=clientinfo&amp;server=" . $ts3_VirtualServer->getId() . "&amp;client=" . $client->getId() . "\">" . htmlspecialchars($client) . "</a></td>\n" .
         "  <td>" . $client["client_unique_identifier"] . "</td>\n" .
         "  <td>" . $client["client_platform"] . "</td>\n" .
         "  <td>" . $client["client_version"] . "</td>\n" .
         "  <td><a href=\"?page=channelinfo&amp;server=" . $ts3_VirtualServer->getId() . "&amp;channel=" . $client['cid'] . "\">" . $channelList[$client['cid']] . "</a></td>\n" .
         "  <td>" . $client["connection_client_ip"] . "</td>\n" .
		 "  <td>" . $uptime_diff->format('%ad %H:%I:%S'). "</td>\n" .
		 "  <td>" . $idle_diff->format('%H:%I:%S'). "</td>\n" .
         "</tr>\n";
	//print_r($clinfo['connection_connected_time']);
         foreach ($clinfo as $key => $value) {
             echo "<!-- " . $key . " = " . $value." -->\n";
         }

  }
  echo "</table>\n";

  echo "<table class=\"list\">\n";
  echo "<tr>\n" .
       "  <th>ID</th>\n" .
       "  <th>Icon</th>\n" .
       "  <th>Name</th>\n" .
       "</tr>\n";

 foreach($serverGroupList as $group)
  {
    echo "<tr>\n".
         "  <td>" . $group->getId() . "</td>\n" .
         "  <td><img src=\"" . $ts3FameworkPath . "images/viewer/" . $group->getIcon() . ".png\" alt=\"\" /></td>\n" .
         "  <td><a href=\"?page=clientinfo&amp;server=" . $ts3_VirtualServer->getId() . "&amp;client=" . $group->getId() . "\">" . htmlspecialchars($group) . "</a></td>\n" .
         "</tr>\n";
  }
  echo "</table>\n";

    echo "<table class=\"list\">\n";
	echo "<tr>\n" .
       "  <th>ID</th>\n" .
       "  <th>Icon</th>\n" .
       "  <th>Name</th>\n" .
       "</tr>\n";

  foreach($channelList as $client)
  {
    echo "<tr>\n" .
         "  <td>" . $client->getId() . "</td>\n" .
         "  <td><img src=\"" . $ts3FameworkPath . "images/viewer/" . $client->getIcon() . ".png\" alt=\"\" /></td>\n" .
         "  <td><a href=\"?page=clientinfo&amp;server=" . $ts3_VirtualServer->getId() . "&amp;client=" . $client->getId() . "\">" . htmlspecialchars($client) . "</a></td>\n" .
         "</tr>\n";

         foreach ($client->getInfo() as $key => $value) {
             echo "<!--" . $key . " = " . $value."<br>-->\n";
         }
         //print_r($client->getInfo());
  }
  echo "</table>\n";

  /* display runtime from adapter profiler */
  echo "<p>Executed " . $ts3_ServerInstance->getAdapter()->getQueryCount() . " queries in " . $ts3_ServerInstance->getAdapter()->getQueryRuntime() . " seconds</p>\n";

}
catch(Exception $e)
{
  /* catch exceptions and display error message if anything went wrong */
  echo "<span class='error'><b>Error " . $e->getCode() . ":</b> " . $e->getMessage() . "</span>\n";
}
