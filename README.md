# DI Attribute

Automatic class registration using a simple attribute for Nette.

## Setup

DiAttibute is available on composer:

```bash
composer require awaitcz/di-attribute
```

At first register compiler extension.

```neon
extensions:
	diAttribute: Awaitcz\DiAttribute\DI\DiAttributeExtension
```

## Configuration

```neon
diAttribute:
	# Paths to scan for classes
	paths:
		- %appDir%/model
	# If you need to exclude some namespaces or classes
	excludes:
		- App\Model\IgnoreMe
```

## Usage

```php
<?php declare(strict_types=1);

namespace App\Model\Awesome;

use Awaitcz\DiAttribute\DiService;

#[DiService]
class AwesomeService
{
	...
}

