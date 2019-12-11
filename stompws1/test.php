<?php
require   './vendor/autoload.php';
$wss = '122';
$stopClient = new \StompWS\StompWSClient($wss);
$stopClient->connect();
