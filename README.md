# AMP Toolbox Bundle

[![Travis CI - Build Status](https://travis-ci.org/holadev/amp-toolbox-bundle.svg?branch=main)](https://travis-ci.org/holadev/amp-toolbox-bundle)
[![Scrutinizer - Build Status](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/badges/build.png?b=main)](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/build-status/main)
[![Scrutinizer - Code Quality](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/?branch=main)
[![Scrutinizer - Code Coverage](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/holadev/amp-toolbox-bundle/?branch=main)

**Symfony integration for [AMP Toolbox for PHP](https://github.com/ampproject/amp-toolbox-php).**

## Installation

To install the bundle with Symfony Flex, use the recipe:

``` bash
$ composer require holadev/amp-toolbox-bundle
```

Config file is needed to run this project. Must be contains a minimal config to enable:

```
# app/config/amp_toolbox.yaml

amp_toolbox:
  transform_enabled: true
```

Alternatively, the property of activating the transformer can be defined through the class itself via autowiring.
This property override config value of `transform_enabled` 
```php
# src/ExampleController.php

public function index(
        AmpOptimizerSubscriber $ampOptimizerSubscriber
    ): array {
        $ampOptimizerSubscriber->setEnabled(false);
    // controller code...
}
```

## Testing

``` bash
$ composer test
```
