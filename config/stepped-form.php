<?php

use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\AlreadyStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\DefaultExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\EntityNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\FormIsNotStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotRenderableExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\SteppedFormErrorsExceptionNormalizer;
use Lexal\LaravelSteppedForm\Storage\SessionStorage;

return [
    /*
     * --------------------------------------------------------------------------------------
     * Default Storage
     * --------------------------------------------------------------------------------------
     *
     * Contains default storage settings.
     *      - 'type' - a storage class name.
     *      - 'parameters' - parameters that must be passed to the Storage instance.
     */

    'storage' => [
        'type' => SessionStorage::class,
        'parameters' => [
            'namespace' => 'stepped-form',
        ],
    ],

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
        EntityNotFoundExceptionNormalizer::class,
        FormIsNotStartedExceptionNormalizer::class,
        SteppedFormErrorsExceptionNormalizer::class,
        DefaultExceptionNormalizer::class,
    ],
];
