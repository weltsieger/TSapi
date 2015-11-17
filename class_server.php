<?php

require_once("../libraries/TeamSpeak3/TeamSpeak3.php");
require_once 'class_client.php';

class server {

    private $_clientList;
    private $_channelList;
    public $ts_host = "";
    public $ts_serverport = "9987";
    public $ts_nickname = "";
    public $ts_username = "";
    public $ts_password = "";
    public $ts_ServerInstance;

    public function connect() {
        /* connect to server, login and get TeamSpeak3_Node_Host object by URI */
        $this->ts_ServerInstance = TeamSpeak3::factory("serverquery://" . $this->ts_username . ":" . $this->ts_password . "@" . $this->ts_host . ":10011/?server_port=" . $this->ts_serverport . "&nickname=" . $this->ts_nickname);
    }

    /**
     * Liest alle verbundenen Clients vom Server aus
     * @param string $sortBy Client-Property nach der sortiert werden soll
     * @param bool $sortOrder false (0) = ASC, true (1) = DESC
     * @return Array(client)
     */
    public function getClientList($sortBy = "", $sortOrder = 0) {

        $clientList = $this->ts_ServerInstance->clientList();

        $this->_clientList = Array();

        foreach ($clientList as $client) {

            $ts_client = new client();

            $clinfo = $client->getInfo();

            if (!isset($clinfo['connection_connected_time'])) {
                $clinfo['connection_connected_time'] = 0; //3600000
            }
            $uptime = date_create(date("Y-m-d H:i:s", $clinfo['connection_connected_time'] / 1000));
            $startTime = date_create(date("Y-m-d H:i:s", 0));
            $uptime_diff = date_diff($startTime, $uptime);
            $idle_diff = date_diff($startTime, date_create(date("Y-m-d H:i:s", $clinfo['client_idle_time'] / 1000)));

            $ts_client->status = $client->getIcon();
            $ts_client->nickname = htmlspecialchars($client);
            $ts_client->id = $client->getId();
            $ts_client->identity = $client["client_unique_identifier"];
            $ts_client->idleTime = $idle_diff;
            $ts_client->uptime = $uptime_diff;
            $ts_client->channel = $client['cid'];
            $ts_client->plattform = $client["client_platform"];
            $ts_client->ip = $client["connection_client_ip"];
            $ts_client->version = $client["client_version"];

            $this->_clientList[] = $ts_client;
        }

        if (property_exists('client', $sortBy)) {
            usort($this->_clientList, function($a, $b) use ($sortBy, $sortOrder) {
                if ($sortOrder) {
                    return ($a->$sortBy > $b->$sortBy) ? 1 : -1;
                } else {
                    return ($a->$sortBy < $b->$sortBy) ? 1 : -1;
                }
            });
        }

        return $this->_clientList;
    }

    public function getChannelList() {
        $channelList = $this->ts_ServerInstance->channelList();

        $this->_channelList = Array();

        foreach ($channelList as $channel) {
            $ts_channel = new client();

            $ts_channel->id = $channel->getId();
            $ts_channel->nickname = htmlspecialchars($channel);
            $ts_channel->status = $channel->getIcon();

            $this->_channelList[$ts_channel->id] = $ts_channel;
        }

        return $this->_channelList;
    }

}
