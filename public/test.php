<?php

require __DIR__ . '/../vendor/autoload.php';

$client = new LZI\DnbUrnClient\UrnClient();

var_dump($client->getNamespaceDetails('urn:nbn:de:0030'));

exit();