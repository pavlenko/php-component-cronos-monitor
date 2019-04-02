## Requirements

The following versions of PHP are supported.

* PHP 7.1+

## Installation

To install, use composer:

```
php composer.phar require pe/component-cronos-monitor
```

## Usage

Server usage

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Core\ServerInterface;
use PE\Component\Cronos\Monitor\Storage\StorageInterface;
use PE\Component\Cronos\Monitor\MonitorModule;

// Initialize server somewhere and use some of options below (one or more)
/* @var $server ServerInterface */
$server = '...';

// Create storage
/* @var $storage StorageInterface */
$storage = '...';

// Attach module
$module = new MonitorModule($storage);
$server->attachModule('monitor', $module);
```

Client usage

```php
<?php

namespace PE\Component\Cronos;

use PE\Component\Cronos\Core\ClientInterface;
use PE\Component\Cronos\Monitor\MonitorAPI;

// Initialize client
/* @var $client ClientInterface */
$client = '...';

// Create API instance
$api = new MonitorAPI($client);

// Call for fetch server status
$api->getStatus();// -> Current server status constant

// Call for fetch active task from remote
$api->getTasks();// -> Array of TaskInterface instances
```
