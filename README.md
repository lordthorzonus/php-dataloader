# PHP DataLoader
Port of the [Facebook's DataLoader](https://github.com/facebook/dataloader) to PHP. Async superpowers from [ReactPHP](https://github.com/reactphp).

DataLoader is a generic utility to be used as part of your application's data fetching layer to provide a simplified and consistent API over various remote data sources such as databases or web services via batching and caching.

[![Build Status](https://travis-ci.org/lordthorzonus/php-dataloader.svg?branch=master)](https://travis-ci.org/lordthorzonus/php-dataloader)
[![Code Coverage](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/leinonen/php-dataloader/v/stable)](https://packagist.org/packages/leinonen/php-dataloader)
[![Total Downloads](https://poser.pugx.org/leinonen/php-dataloader/downloads)](https://packagist.org/packages/leinonen/php-dataloader)
[![Latest Unstable Version](https://poser.pugx.org/leinonen/php-dataloader/v/unstable)](https://packagist.org/packages/leinonen/php-dataloader)
[![License](https://poser.pugx.org/leinonen/php-dataloader/license)](https://packagist.org/packages/leinonen/php-dataloader)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/44a2e0f3-cde6-48b9-b484-8243a64145de/mini.png)](https://insight.sensiolabs.com/projects/44a2e0f3-cde6-48b9-b484-8243a64145de)

Table of contents
=================

* [Installation](#installation)
* [Usage](#usage)
    * [Batch function](#batch-function)
    * [Caching](#caching)
    * [Usage with common ORM's](#usage-with-common-orms)
* [API](#api)

## Installation

Require this package, with [Composer](https://getcomposer.org/), in the root directory of your project.

```bash
composer require leinonen/php-dataloader
```

## Usage
To create a loader you must provide a batching function, an internal memoization cache and the global event loop from ReactPHP. To have better understanding what the ReactPHP event loop is and how it is used refer to it's [documentation](https://github.com/reactphp/event-loop).

```php
use leinonen\DataLoader\Dataloader;
use React\EventLoop\Factory;

$eventLoop = Factory::create();

$bandLoader = new DataLoader(
    function ($keys) {
        // Batch load bands with given keys.
    },
    $eventLoop,
    new CacheMap()
);
```

Then load individual values from the loader. DataLoader will coalesce all individual loads which occur within a single tick of the event loop and then call your batch function with all requested keys.

```php
$bandLoader->load(1)->then(function ($band) {
    echo "Band #${$band->getId()} loaded";
});

$bandLoader->load(2)->then(function ($band) {
    echo "Band #${$band->getId()} loaded";
});

$eventLoop->run();
```

Calling the load function returns `React\Promise\Promise`s. To have a better understanding how to use promises within PHP refer to the [ReactPHP docs](https://github.com/reactphp/promise).

### Batch Function

The batch loading function accepts an array of keys, and must return a Promise which resolves to an Array of values. There are a few other constraints:

- The Array of values must be the same length as the Array of keys.
- Each index in the Array of values must correspond to the same index in the Array of keys i.e. The order of the batch loaded results must be the same as the order of given keys.

For example, if your batch function was provided the Array of keys: `[2, 9, 6, 1]`, and the batch loaded results were:
```php
[
    ['id' => 1, 'name' => 'Mojo Waves'],
    ['id' => 2, 'name' => 'Pleasure Hazard'],
    ['id' => 9, 'name' => 'Leka'],
]
```

The loaded results are in a different order that we requested which is quite common with most of the relation dbs for example. Also result for key `6` is omitted which we can interpret as no value existing for that key.

To satisfy the constraints of the batch function we need to modify the results to be the same length as the Array of keys and re-order them to ensure each index aligns with the original keys:

```php
[
    ['id' => 2, 'name' => 'Pleasure Hazard'],
    ['id' => 9, 'name' => 'Leka'],
    null,
    ['id' => 1, 'name' => 'Mojo Waves'],
]
```

### Caching
DataLoader provides a memoization cache for all loads which occur in a single request to your application. After `load()` is called once with a given key, the resulting value is cached to eliminate redundant loads.

In addition to relieving load on your data storage, caching results per-request also creates fewer objects which may relieve memory pressure on your application:

```php
$promise1 = $bandLoader->load(1);
$promise2 = $bandLoader->load(2);

($promise1 === $promise2) // true
```

DataLoader caching does not replace Redis, Memcache, or any other shared application-level cache. DataLoader is first and foremost a data loading mechanism, and its cache only serves the purpose of not repeatedly loading the same data in the context of a single request to your Application. To do this it utilizes the CacheMap given as a constructor argument.

This package provides a simple CacheMap (`leinonen\DataLoader\CacheMap`) implementation to be used with DataLoader. You can also use your custom CacheMap with various different [cache algorithms](https://en.wikipedia.org/wiki/Cache_algorithms) by implementing the `leinonen\DataLoader\CacheMapInterface`.

### Usage with common ORM's

#### Eloquent (Laravel)

```php
$userByIdLoader = new DataLoader(function ($ids) {
  $users = User::findMany($ids);

  // Make sure that the users are on the same order as the given ids for the loader
  $orderedUsers = collect($ids)->map(function ($id) use ($users) {
    return $users->first(function ($user) use ($id) {
      return $user->id === $id;
    });
  });

   return \React\Promise\resolve($orderedUsers);
}, $eventLoopFromIoCContainer, $cacheMapFromIoCContainer);
```

#### ActiveRecord (Yii2)
```php
$usersByIdLoader = new DataLoader(function ($ids) {
    $users = User::find()->where(['id' => $ids])->all();

    $orderedUsers = \array_map(function ($id) use ($users) {
        foreach ($users as $user) {
            if ($user->id === $id) {
                return $user;
            }
        }

        return null;
    }, $ids);

    return \React\Promise\resolve($orderedUsers);
}, $eventLoopFromDiContainer, $cacheMapImplementationFromDiContainer);
```

## API

### `load($key)`

Loads a key, returning a `Promise` for the value represented by that key.

- `@param mixed $key An key value to load.`

### `loadMany($keys)`

Loads multiple keys, promising an array of values.

This is equivalent to the more verbose:

```php
$promises = \React\Promise\all([
  $myLoader->load('a'),
  $myLoader->load('b')
]);
```

- `@param array $keys: An array of key values to load.`

### `clear($key)`

Clears the value at `$key` from the cache, if it exists. Returns itself for
method chaining.

- `@param mixed key: An key value to clear.`

### `clearAll()`

Clears the entire cache. Returns itself for method chaining.

### `prime($key, $value)`

Primes the cache with the provided key and value. If the key already exists, no
change is made. (To forcefully prime the cache, clear the key first with
`$loader->clear($key)->prime($key, $value)`. Returns itself for method chaining.



