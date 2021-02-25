<?php

require __DIR__ . '/../vendor/autoload.php';

use LZI\DnbUrnClient\UrlObject;
use LZI\DnbUrnClient\UrnClient;

$client = new LZI\DnbUrnClient\UrnClient();

var_dump($client->getNamespaceDetails('urn:nbn:de:0030'));

exit();

$urn = 'urn:nbn:de:0030-drops-11423';
$urn2 = 'urn:nbn:de:0030-drops-114236';

//eturn response()->json($client->getUrnDetails($urn));

/*
foreach ($client->getUrls($urn) as $url) {
    var_dump($url->toJson());
    echo'<hr>';
}

exit();
*/

/*
foreach ($client->getOwnUrls($urn) as $url) {
    var_dump($url->toJson());
    echo'<hr>';
}

exit();
*/

$url = 'https://drops.dagstuhl.de/opus/volltexte/2007/1141/6';

//return $client->getUrlDetails($urn.'202', $url.'24')->toJson();

//return response()->json($client->urlExists($urn.'202', $url.'24'));

$url2 = $url.'2';
$url3 = [ 'url' => $url.'3' ];
$url4 = new App\Modules\Urn\UrlObject($url.'444');
$url5 = new App\Modules\Urn\UrlObject($url.'555555500',500);

//return $client->addUrl($urn, $url2.'20')->toJson();
//return response()->json($client->addUrl($urn, $url3));
//return response()->json($client->addUrl($urn, $url4));
//return response()->json($client->addUrl($urn, $url5));

//return response()->json($client->deleteUrl($urn, $url2.'20'));
//return response()->json($client->deleteUrl($urn, $url3));
//return response()->json($client->deleteUrl($urn, $url4));
//return response()->json($client->deleteUrl($urn, $url5));

//return response()->json($client->urlExists($urn, $url5));

//return $client->getUrlDetails($urn, $url2)->toJson();

//return response()->json($client->updatePriority($urn, $url2, 400));

//return response()->json($client->getUrnSuggestion('urn:nbn:de:0030'));

//return response()->json($client->urnExists($urn));

$urls1 = $url.'10';
$urls2 = [ $url.'11' ];
$urls3 = [ $url.'12', $url.'13' ];
$urls4 = [ 'url' => $url.'14' ];
$urls5 = [ 'url' => $url.'15', 'priority' => 7 ];
$urls6 = [[ 'url' => $url.'16', 'priority' => 8 ], [ 'url' => $url.'17', 'priority' => 9 ],  [ 'url' => $url.'18' ]];
$urls7 = new App\Modules\Urn\UrlObject($url.'19');
$urls8 = [ new App\Modules\Urn\UrlObject($url.'20') ];
$urls9 = [ new App\Modules\Urn\UrlObject($url.'21'),  new App\Modules\Urn\UrlObject($url.'22') ];
$urls10 = [ new App\Modules\Urn\UrlObject($url.'23'),  new App\Modules\Urn\UrlObject($url.'24') ];

//return response()->json($client->exchangeOwnUrls($urn, $url2.'2020'));

return response()->json($client->registerUrn($urn.'204', $urls8));

//return response()->json($client->updatePriority($urn.'202', $url.'23', 400));
//return response()->json($client->updatePriority($urn.'202', new App\Modules\Urn\UrlObject($url.'23'), 500));

//return response()->json($client->setUrnSuccessor($urn.'202', $urn2));

//return response()->json($client->deleteUrnSuccessor($urn.'202'));
//return response()->json($client->deleteUrnSuccessor($urn2));

