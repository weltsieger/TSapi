<table class="list">
  <tr>
    <th>ID</th>
    <th>Status</th>
    <th>Nickname</th>
    <th>Unique Identifier</th>
    <th>Platform</th>
    <th>Version</th>
    <th>Current-Channel</th>
    <th>IP</th>
    <th>Uptime</th>
    <th>Idle</th>
  </tr>

  <tr>
  <td>" . $client->getId() . "</td>\n" .
  <td><img src=\"" . $ts3FameworkPath . "images/viewer/" . $client->getIcon() . ".png\" alt=\"" . $client->getIcon() . "\" title=\"" . $client->getIcon() . "\" /></td>\n".
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
