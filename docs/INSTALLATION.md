# Installation

Via Composer

```
composer require lexal/laravel-stepped-form
```

## Additional changes for Lumen framework

Add the following snippet to the `bootstrap/app.php` file under the providers
section as follows:

```php
$app->register(Lexal\LaravelSteppedForm\ServiceProvider\ServiceProvider::class);
```
