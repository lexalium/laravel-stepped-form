<?php

use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\AlreadyStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\DefaultExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\EntityNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\FormIsNotStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepIsNotSubmittedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotRenderableExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\SteppedFormErrorsExceptionNormalizer;
use Lexal\LaravelSteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\LaravelSteppedForm\Routing\Redirector;
use Lexal\SteppedForm\EntityCopy\SimpleEntityCopy;

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
     * Entity Copy
     * --------------------------------------------------------------------------------------
     *
     * Specify Entity Copy class, instance or service alias that will clone entity
     * of the given step.
     *
     * Entity Copy must implement Lexal\SteppedForm\EntityCopy\EntityCopyInterface.
     */

    'entity_copy' => SimpleEntityCopy::class,

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
     * Forms Definitions
     * --------------------------------------------------------------------------------------
     *
     * Associative array of forms definition where key is a part of stepped form service alias,
     * e.g. 'customer' => [...] - will have service alias in container 'stepped-form.customer'.
     * Form Definition has the following fields:
     *      - `builder_class` - required only when `steps` field is missing, class, instance
     *          or service alias used for building steps collection. Use this field for
     *          dynamic forms.
     *          Must implement Lexal\SteppedForm\Form\Builder\FormBuilderInterface.
     *      - `steps` - required only when `builder_class` field is missing, associative array
     *          of stepped-form steps, where `key` is a step key and value is class, instance
     *          or service alias of StepInterface. Use this field for static forms.
     *      - `settings_class` - required, class, instance or service alias used for configuring
     *          stepped form.
     *          Must implement Lexal\HttpSteppedForm\Settings\FormSettingsInterface
     *      - `storage` - required, array of Storage settings or string of class or service alias.
     *      - `storage.class` - required, class or service alias used for storing form data
     *          between steps.
     *          Must implement Lexal\SteppedForm\Form\Storage\StorageInterface
     *      - `storage.parameters` - optional. Describe custom parameters that the storage
     *          constructor must receive.
     *          Default: [].
     *
     * Example:
     * 'forms' => [
     *      'customer' => [
     *          'builder_class' => CustomerFormBuilder::class,
     *          'settings_class' => CustomerFormSettings::class,
     *          'storage' => SessionStorage::class,
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
        StepIsNotSubmittedExceptionNormalizer::class,
        EntityNotFoundExceptionNormalizer::class,
        FormIsNotStartedExceptionNormalizer::class,
        SteppedFormErrorsExceptionNormalizer::class,
        DefaultExceptionNormalizer::class,
    ],
];
