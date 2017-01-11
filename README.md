# PHP dataloader
Quick port of the [Facebook's DataLoader](https://github.com/facebook/dataloader) to PHP. Async superpowers from [ReactPHP](https://github.com/reactphp) 

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


