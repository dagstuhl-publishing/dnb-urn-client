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