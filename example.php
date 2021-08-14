<?php
require_once('vendor/autoload.php');

use Corbpie\TwitchApiClass\twitchWrapper;

$call = new twitchWrapper();

echo $call->accessCodeUrl();

echo json_encode($call->getUserDetails('shroud'));