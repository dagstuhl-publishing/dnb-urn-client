<?php

require __DIR__ . '/../vendor/autoload.php';

$client = new LZI\DnbUrnClient\UrnClient(
    // 'username',
    // 'password',
    // 'https://api.nbn-resolving.org/sandbox/v2/'
);

var_dump(
    $client->getNamespaceDetails('urn:nbn:de:0030'),
    $client->getNamespaceDetails('urn:nbn:de:0030'),
    $client->urnExists('urn:nbn:de:0030-drops-177320'),
    $client->getUrnDetails('urn:nbn:de:0030-drops-177320'),
    $client->getUrls('urn:nbn:de:0030-drops-177320')
);

exit();