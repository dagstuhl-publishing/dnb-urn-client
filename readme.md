[![SWH](https://archive.softwareheritage.org/badge/swh:1:dir:44168f996ee82a0d7208fc2fbb2c175a7319ce17/)](https://archive.softwareheritage.org/swh:1:dir:44168f996ee82a0d7208fc2fbb2c175a7319ce17;origin=https://github.com/dagstuhl-publishing/dnb-urn-client;visit=swh:1:snp:29a28a50fe48a0c8d84531ae2dfde91e569b1f33;anchor=swh:1:rev:5b248b762bfc18363048fa4efac5ef60c19c8851)


# The Dagstuhl DNB URN-API client

An API client for registering/resolving urn data at DNB.

### Code Examples:

```php
$client = new Dagstuhl\DnbUrnClient('username', 'password', 'apiUrl');
$client->getNamespaceDetails('urn:nbn:de:0030');

$client->registerUrn('urn:nbn:de:0030-drops-...', 'https://drops.dagstuhl.de/entities/document/...');
$client->addUrl('urn:nbn:de:0030-drops-...', 'https://drops.dagstuhl.de/entities/document/...');

$client->urnExists('urn:nbn:de:0030-drops-...');
$client->getUrnDetails('urn:nbn:de:0030-drops-...');
$client->getUrls('urn:nbn:de:0030-drops-...');
```
