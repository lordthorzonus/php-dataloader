# Changelog

## Updating from 1.0.0 to 2.0.0
 - Overall php version requirement was bumped from `5.5` to `7.3`
 - ReactPHP dependencies have been upped to their latest versions
 - Fluent interfaces from `Dataloader::prime()`,`DataLoader::clear()` and `DataLoader::clearAll()` were removed. So change usages like:
    ```php
    $dataloader->clear('A')->prime('A', 'Y');
    ```
    to:
    ```
    $dataloader->clear('A');
    $dataloader->prime('A', 'Y');
    ```
 - All classes in the library have been marked as final.
