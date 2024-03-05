<?php

require __DIR__ . '/../vendor/autoload.php';

$client = new Dagstuhl\DnbUrnClient\UrnClient(
    // 'username',
    // 'password',
    // 'https://api.nbn-resolving.org/sandbox/v2/'
);

var_dump(
    $client->getNamespaceDetails('urn:nbn:de:0030'),

    $client->registerUrn('urn:nbn:de:0030-drops-177320', 'http://localhost:8000/entities/document/10.4230/OASIcs.NG-RES.2023.1'),
    $client->addUrl('urn:nbn:de:0030-drops-177320', 'http://localhost:8000/entities/document/10.4230/OASIcs.NG-RES.2023.1'),

    $client->urnExists('urn:nbn:de:0030-drops-177320'),
    $client->getUrnDetails('urn:nbn:de:0030-drops-177320'),
    $client->getUrls('urn:nbn:de:0030-drops-177320'),
);

exit();