<?php

use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\AlreadyStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\DefaultExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\EntityNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\FormNotStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotRenderableExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotSubmittedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\SteppedFormErrorsExceptionNormalizer;
use Lexal\LaravelSteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\LaravelSteppedForm\Routing\Redirector;

return [
    /*
     * --------------------------------------------------------------------------------------
     * Renderer
     * --------------------------------------------------------------------------------------
     *
     * Specify Renderer class, instance or service alias that will translate step's template
     * definition to the response.
     *
     * Renderer must implement Lexal\HttpSteppedForm\Renderer\RendererInterface.
     */

    'renderer' => Renderer::class,

    /*
     * --------------------------------------------------------------------------------------
     * Redirector
     * --------------------------------------------------------------------------------------
     *
     * Specify Redirector class, instance or service alias that will redirect user between
     * different steps.
     *
     * Redirector must implement Lexal\HttpSteppedForm\Routing\RedirectorInterface.
     */

    'redirector' => Redirector::class,

    /*
     * --------------------------------------------------------------------------------------
     * Event Dispatcher
     * --------------------------------------------------------------------------------------
     *
     * Specify Event Dispatcher class, instance or service alias that will dispatch
     * form events.
     *
     * Event Dispatcher must implement Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface.
     */

    'event_dispatcher' => EventDispatcher::class,

    /*
     * --------------------------------------------------------------------------------------
     * Form Definitions
     * --------------------------------------------------------------------------------------
     *
     * Associative array of form definitions where key is a part of stepped form service alias,
     * e.g. 'customer' => [...] - will have service alias in container 'stepped-form.customer'.
     * Form Definition has the following fields:
     *      - `builder_class` - required only when `steps` field is missing, class name, instance
     *          or service alias can be used as a form builder.
     *          Must implement Lexal\SteppedForm\Form\Builder\FormBuilderInterface.
     *      - `steps` - required only when `builder_class` field is missing, associative array
     *          of stepped-form steps, where `key` is a step key and value is class, instance
     *          or service alias of StepInterface.
     *      - `settings_class` - required, class, instance or service alias can be used as a form settings.
     *          Must implement Lexal\HttpSteppedForm\Settings\FormSettingsInterface
     *      - `storage` - required, array of Storage settings or string of class or service alias.
     *      - `storage.class` - required, class or service alias.
     *          Must implement Lexal\SteppedForm\Form\Storage\StorageInterface
     *      - `storage.parameters` - optional. Describe custom arguments that the storage
     *          constructor must receive. `namespace` (equals to a form key) parameter will be added
     *          as a constructor argument automatically.
     *          Default: [].
     *      - `session_key_storage` - optional, array of Session Storage settings or string of class or service alias.
     *          Default: NullSessionKeyStorage::class.
     *      - `session_key_storage.class` - required, class or service alias.
     *          Must implement Lexal\SteppedForm\Form\Storage\SessionStorageInterface
     *      - `session_key_storage.parameters` - optional. Describe custom arguments that the storage
     *          constructor must receive. `namespace` (equals to a form key) parameter will be added
     *          as a constructor argument automatically.
     *          Default: [].
     *
     * Example:
     * 'forms' => [
     *      'customer' => [
     *          'builder_class' => CustomerFormBuilder::class,
     *          'settings_class' => CustomerFormSettings::class,
     *          'storage' => SessionStorage::class,
     *          'session_key_storage' => SessionSessionKeyStorage::class,
     *      ],
     * ],
     *
     * or
     *
     * 'forms' => [
     *      'customer' => [
     *          'steps' => [
     *              'first' => FirstStep::class,
     *              'second' => SecondStep::class,
     *              ...
     *          ],
     *          'settings_class' => CustomerFormSettings::class,
     *          'storage' => SessionStorage::class,
     *          'session_key_storage' => SessionSessionKeyStorage::class,
     *      ],
     * ],
     */

    'forms' => [],

    /*
     * --------------------------------------------------------------------------------------
     * Exception Normalizers
     * --------------------------------------------------------------------------------------
     *
     * Specify exception normalizers that the form will use to normalize
     * SteppedFormException into the Response instance.
     */

    'exception_normalizers' => [
        AlreadyStartedExceptionNormalizer::class,
        StepNotFoundExceptionNormalizer::class,
        StepNotRenderableExceptionNormalizer::class,
        StepNotSubmittedExceptionNormalizer::class,
        EntityNotFoundExceptionNormalizer::class,
        FormNotStartedExceptionNormalizer::class,
        SteppedFormErrorsExceptionNormalizer::class,
        DefaultExceptionNormalizer::class,
    ],
];
