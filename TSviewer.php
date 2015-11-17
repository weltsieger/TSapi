<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="shortcut icon" href="http://www.teamspeak.com/favicon.ico" type="image/icon"> 
        <link rel="icon" href="http://www.teamspeak.com/favicon.ico" type="image/icon"> 
        <title>Livespawn - TS Viewer</title>
        <script src="sort.js"></script>
        <?php if (isset($_GET['refresh']) && $_GET['refresh'] > 5) { ?>
            <META HTTP-EQUIV="refresh" CONTENT="<?php echo $_GET['refresh']; ?>">
            <script language="javascript" type="text/javascript">
                document.onreadystatechange = function () {
                    var state = document.readyState
                    
                    if (state == 'interactive') {
                        
                    } else if (state == 'complete') {
                        document.getElementById("refresh").innerHTML = "<?php echo $_GET['refresh'] + 1; ?>";
                        countdown();
                    }
                };
                function countdown() {
                    document.getElementById("refresh").innerHTML -= 1;
                    window.setTimeout(function () {
                        countdown()
                    }, 1000);
                }
            </script>
            <?php
        }
        ?>
    </head>
    <body>
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        require_once("../libraries/TeamSpeak3/TeamSpeak3.php");
        require_once 'class_server.php';
        require_once 'class_client.php';

        $server = new server();
        $server->ts_host = "localhost";
        $server->ts_username = "nicotelnet";
        $server->ts_password = "3TUy38Md";
        $server->ts_nickname = "SecrurityBot";
        $server->connect();

        $channelList = $server->getChannelList();

        if (isset($_GET['sort'])) {
            if (isset($_GET['sortOrder'])) {
                $clientList = $server->getClientList($_GET['sort'], $_GET['sortOrder']);
            } else {
                $clientList = $server->getClientList($_GET['sort']);
            }
        } else {
            $clientList = $server->getClientList();
        }

        echo "<table class=\"sortable_off\">\n";
        echo "<tr>\n" .
        "  <th><a href='?sort=status" . (isset($_GET['sort']) && $_GET['sort'] == "status" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Status</a></th>\n" .
        "  <th><a href='?sort=nickname" . (isset($_GET['sort']) && $_GET['sort'] == "nickname" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Nickname</a></th>\n" .
        "  <th><a href='?sort=channel" . (isset($_GET['sort']) && $_GET['sort'] == "channel" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Current-Channel</a></th>\n" .
        "  <th><a href='?sort=idleTime" . (isset($_GET['sort']) && $_GET['sort'] == "idleTime" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Idle</a></th>" .
        "  <th><a href='?sort=uptime" . (isset($_GET['sort']) && $_GET['sort'] == "uptime" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Uptime</a></th>\n" .
        "  <th><a href='?sort=plattform" . (isset($_GET['sort']) && $_GET['sort'] == "plattform" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Platform</a></th>\n" .
        "  <th><a href='?sort=ip" . (isset($_GET['sort']) && $_GET['sort'] == "ip" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>IP</a></th>\n" .
        "  <th><a href='?sort=id" . (isset($_GET['sort']) && $_GET['sort'] == "id" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>ID</a></th>\n" .
        "  <th><a href='?sort=identity" . (isset($_GET['sort']) && $_GET['sort'] == "identity" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Unique Identifier</a></th>\n" .
        "  <th><a href='?sort=version" . (isset($_GET['sort']) && $_GET['sort'] == "version" ? "&sortOrder=" . (isset($_GET['sortOrder']) && $_GET['sortOrder'] ? "0" : "1") : "") . "'>Version</a></th>\n" .
        "</tr>\n";
        foreach ($clientList as $client) {
            echo "<tr>\n" .
            "  <td><img src=\"../images/viewer/" . $client->status . ".png\" alt=\"" . $client->status . "\" title=\"" . $client->status . "\" /></td>\n" .
            "  <td>" . $client->nickname . "</td>\n" .
            "  <td>" . $channelList[$client->channel]->nickname . "</td>\n" .
            "  <td>" . $client->idleTime->format('%H:%I:%S') . "</td>\n" .
            "  <td>" . $client->uptime->format('%ad %H:%I:%S') . "</td>\n" .
            "  <td>" . $client->plattform . "</td>\n" .
            "  <td>" . $client->ip . "</td>\n" .
            "  <td>" . $client->id . "</td>\n" .
            "  <td>" . $client->identity . "</td>\n" .
            "  <td>" . $client->version . "</td>\n" .
            "</tr>\n";
        }
        echo "</table>\n";

        /* display runtime from adapter profiler */
        echo "<p>Clients: " . count($clientList) . ", Executed " . $server->ts_ServerInstance->getAdapter()->getQueryCount() . " queries in " . $server->ts_ServerInstance->getAdapter()->getQueryRuntime() . " seconds</p>\n";
        ?>
        <p>refresh in: <span id="refresh">deaktiviert</span> Sekunden</p>

        <form action="" method="GET">
            <input type="number" name="refresh" value="<?php echo (isset($_GET['refresh']) ? $_GET['refresh'] : ""); ?>" />
            <input type="submit" value="übernehmen" />
            <?php
            foreach ($_GET as $key => $value) {
                if($key != "refresh") {
                    echo "<input type='hidden' name='$key' value='$value' />\n";
                }
            }
            ?>
        </form>
        <form action="" method="GET">
            <input type="submit" value="Stop" />
            <?php
            foreach ($_GET as $key => $value) {
                if($key != "refresh") {
                    echo "<input type='hidden' name='$key' value='$value' />\n";
                }
            }
            ?>
        </form>
    </p>
</body>
</html>
