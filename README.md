# PHP dataloader
Quick port of the [Facebook's DataLoader](https://github.com/facebook/dataloader) to PHP. Async superpowers from [ReactPHP](https://github.com/reactphp) 

[![Build Status](https://travis-ci.org/lordthorzonus/php-dataloader.svg?branch=master)](https://travis-ci.org/lordthorzonus/php-dataloader)
[![Code Coverage](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/leinonen/php-dataloader/v/stable)](https://packagist.org/packages/leinonen/php-dataloader)
[![Total Downloads](https://poser.pugx.org/leinonen/php-dataloader/downloads)](https://packagist.org/packages/leinonen/php-dataloader)
[![Latest Unstable Version](https://poser.pugx.org/leinonen/php-dataloader/v/unstable)](https://packagist.org/packages/leinonen/php-dataloader)
[![License](https://poser.pugx.org/leinonen/php-dataloader/license)](https://packagist.org/packages/leinonen/php-dataloader)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lordthorzonus/php-dataloader/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/44a2e0f3-cde6-48b9-b484-8243a64145de/mini.png)](https://insight.sensiolabs.com/projects/44a2e0f3-cde6-48b9-b484-8243a64145de)



## Todo
- [x] Primary API 
- [x] Error / rejected promise handling
- [x] Options support
- [x] Abuse tests and meaningful exceptions
- [ ] Documentation for the API and usage examples
- [ ] Abstract event loop and promises to be usable with any implementation? 

## Usage with common ORM's

### Eloquent (Laravel)

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


