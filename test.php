<?php
require   './vendor/autoload.php';
$wss = 'ws://122';
$stopClient = new \StompWS\StompWSClient($wss);
$stopClient->connect();
