<?php
require_once('twitch-class.php');

$call = new twitch_call();
$call->setApiKey('TWITCHCLIENTID');//Set client id
$call->getUserStream('shroud');//Set streamers username
if ($call->userIsLive()) {//Will return true is user is live/streaming
    echo "Shroud is streaming right now";
}