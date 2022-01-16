# Configuration

## Publish the config

Run the following command to publish the package config file:

```shell
php artisan vendor:publish --provider="Lexal\LaravelSteppedForm\ServiceProvider\ServiceProvider"
```

You can update the following options in the `config/stepped-form.php` file:

1. The storage declaration which service provider will bind to the interface
   as default one.

```php
'storage' => [
    'type' => /* storage instance or class name */,
    'parameters' => [
        /* list of parameters that will be passed to the constructor */
    ],
],
```

2. Your custom exception normalizers Read more about them in the [HTTP
   Stepped Form](https://github.com/alexxxxkkk/http-stepped-form/blob/master/docs/EXCEPTION_NORMALIZER.md)
   docs.

```php
'exception_normalizers' => [
    // list of normalizers
],
```
