# Stepped Form for Laravel & Lumen

[![PHPUnit, PHPCS, PHPStan Tests](https://github.com/lexalium/laravel-stepped-form/actions/workflows/tests.yml/badge.svg)](https://github.com/lexalium/laravel-stepped-form/actions/workflows/tests.yml)

The package is based on the 
[HTTP Stepped Form](https://github.com/lexalium/http-stepped-form)
and built for the Laravel & Lumen framework.

<a id="readme-top" mame="readme-top"></a>

Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
   - [Publish the config](#publish-the-config)
   - [Available config options](#available-config-options)
4. [Usage](#usage)
5. [License](#license)

## Requirements

**PHP:** >=8.1

**Laravel:** ^9.0 || ^10.0

## Installation

Via Composer

```
composer require lexal/laravel-stepped-form
```

### Additional changes for Lumen framework

Add the following snippet to the `bootstrap/app.php` file under the providers section as follows:

```php
$app->register(Lexal\LaravelSteppedForm\ServiceProvider\ServiceProvider::class);
```

<div style="text-align: right">(<a href="#readme-top">back to top</a>)</div>

## Configuration

### Publish the config

Run the following command to publish the package config file:

```shell
php artisan vendor:publish --provider="Lexal\LaravelSteppedForm\ServiceProvider\ServiceProvider"
```

### Available config options

The configuration file `config/stepped-form.php` has the following options:

1. `renderer` - contains Renderer class, instance or service alias that will translate step's template definition
   to the response. Must implement RendererInterface;
2. `redirector` - contains Redirector class, instance or service alias that will redirect user between different
   steps. Must implement RedirectorInterface;
3. `entity_copy` - contains Entity Copy class, instance or service alias that will clone entity of the given step.
   Must implement EntityCopyInterface;
4. `event_dispatcher` - contains Event Dispatcher class, instance or service alias that will dispatch form events.
   Must implement EventDispatcherInterface;
5. `exception_normalizers` - contains exception normalizers that the form will use to normalize SteppedFormException
   into the Response instance. Read more about them in the [HTTP Stepped Form](https://github.com/lexalium/http-stepped-form#exception-normalizers)
   docs;
6. `forms` - contains array of all application forms definitions. Form definition must have builder class
   for dynamic forms or array of steps for the static forms, settings class and storage where the form will store data.

<div style="text-align: right">(<a href="#readme-top">back to top</a>)</div>

## Usage

1. [Publish configuration file](#publish-the-config).
2. Replace with custom implementation of redirector, renderer and entity copy if necessary. Add custom exception
   normalizers if necessary.
3. Declare your form settings.
   ```php
    use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;                                                            
    use Lexal\SteppedForm\Step\StepKey;
    
    final class FormSettings implements FormSettingsInterface
    {
        public function getStepUrl(StepKey $key): string
        {
            // return step URL
        }

        public function getUrlBeforeStart(): string
        {
            // returns a URL to redirect to when there is no previously renderable step
        }

        public function getUrlAfterFinish(): string
        {
            // return a URL to redirect to when the form was finishing
        }
    }

    $formSettings = new FormSettings();
    ```

4. Add forms definitions.
   - Static form
     ```php
     return [
        // ...
 
        'forms' => [
            'customer' => [
                'steps' => [
                    'customer' => CustomerStep::class,
                    'broker' => BrokerStep::class,
                    'confirmation' => ConfirmationStep::class,
                    // other steps
                ],
                'settings_class' => FormSettings::class,
                'storage' => SessionStorage::class,
            ],
        ],
 
        // ...
     ];
     ```
   - Dynamic form
     ```php
     return [
        // ...
 
        'forms' => [
            'customer' => [
                'builder_class' => CustomBuilder::class,
                'settings_class' => FormSettings::class,
                'storage' => SessionStorage::class,
            ],
        ],
 
        // ...
     ];
     ```

5. Use Stepped Form in you controller. Stepped Form will have service alias as "stepped-form." + form key form
   configuration.

   **ServiceProvider.php**
   ```php
   use Lexal\HttpSteppedForm\SteppedFormInterface;
   
   $this->app->when(CustomerController::class)
        ->needs(SteppedFormInterface::class)
        ->give('stepped-form.customer');
   ```
   
   **CustomerController.php**
   ```php
   use Lexal\HttpSteppedForm\SteppedFormInterface;
   
   final class CustomerController
   {
       public function __construct(private readonly SteppedFormInterface $form)
       {
       }

       // POST /customers
       public function start(): Response
       {
           return $this->form->start(new Customer());
       }

       // GET /customers/step/{step-key}
       public function render(string $key): Response
       {
           return $this->form->render($key);
       }

       // POST /customers/step/{step-key}
       public function handle(Request $request, string $key): Response
       {
           return $this->form->handle($key, $request);
       }

       // POST /customers/cansel
       public function cancel(): Response
       {
           return $this->form->cancel(route('customer.index'));
       }
   }
   ```

See configuration file for more information.

<div style="text-align: right">(<a href="#readme-top">back to top</a>)</div>

## License

Laravel & Lumen Stepped Form is licensed under the MIT License. See [LICENSE](LICENSE) for the full license text.
