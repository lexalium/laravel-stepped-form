<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\ServiceProvider;

use Lexal\HttpSteppedForm\SteppedForm as HttpSteppedForm;
use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Form\Builder\StaticStepsFormBuilder;
use Lexal\SteppedForm\Form\DataControl;
use Lexal\SteppedForm\Form\StepControl;
use Lexal\SteppedForm\Form\Storage\DataStorage;
use Lexal\SteppedForm\Form\Storage\FormStorage;
use Lexal\SteppedForm\Form\Storage\NullSessionKeyStorage;
use Lexal\SteppedForm\Form\Storage\SessionKeyStorageInterface;
use Lexal\SteppedForm\Form\Storage\StorageInterface;
use Lexal\SteppedForm\Step\Builder\StepsBuilder;
use Lexal\SteppedForm\Step\Builder\StepsBuilderInterface;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SteppedForm\SteppedForm;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
use Lexal\LaravelSteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\LaravelSteppedForm\Routing\Redirector;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use LogicException;
use Psr\Container\ContainerExceptionInterface;

use function array_map;
use function class_exists;
use function class_implements;
use function dirname;
use function get_class;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * @template TEntity of object
 */
final class ServiceProvider extends LaravelServiceProvider
{
    private const FORM_KEY_PATTERN = '/^[A-Za-z0-9-_]+$/';

    private const CONFIG_FILENAME = 'stepped-form.php';

    public function boot(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        $this->publishes([
            $path => $this->app->configPath(self::CONFIG_FILENAME),
        ]);

        $this->mergeConfigFrom($path, 'stepped-form');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        if (!$this->app->bound(Repository::class)) {
            throw new LogicException(
                sprintf(
                    'Unable to register classes without binding of %s contract to the concrete class.',
                    Repository::class,
                ),
            );
        }

        $this->registerForms();
        $this->registerRenderer();
        $this->registerRedirector();
        $this->registerEventDispatcher();
        $this->registerExceptionNormalizer();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    private function registerForms(): void
    {
        foreach ($this->getConfig('forms', []) as $key => $form) {
            if (!is_string($key)) {
                throw new BindingResolutionException(
                    sprintf('Expected argument of type "string", "%s" given.', get_debug_type($key)),
                );
            }

            $this->validateForm($key, (array)$form);

            $this->app->singleton("stepped-form.$key", fn (): HttpSteppedForm => $this->createForm($key, $form));
        }
    }

    /**
     * @param array{
     *     builder_class?: string|FormBuilderInterface<TEntity>,
     *     steps?: array<string, string|StepInterface<TEntity>>,
     *     settings_class?: string|FormSettingsInterface,
     *     storage?: string|object|array{ class?: string|object, parameters?: array<string, mixed> },
     *     session_key_storage?: string|object|array{ class?: string|object, parameters?: array<string, mixed> },
     *     } $formDefinition
     *
     * @throws BindingResolutionException
     */
    private function validateForm(string $key, array $formDefinition): void
    {
        if (!preg_match(self::FORM_KEY_PATTERN, $key)) {
            throw new BindingResolutionException(
                sprintf('Form key must have only "A-z", "0-9", "-" and "_". Given: %s.', $key),
            );
        }

        if (!isset($formDefinition['settings_class'], $formDefinition['storage'])) {
            throw new BindingResolutionException(
                sprintf(
                    '[%s] - Form Definition must have the following required options: settings_class, storage.',
                    $key,
                ),
            );
        }

        $steps = $formDefinition['steps'] ?? [];
        $builderClass = $formDefinition['builder_class'] ?? null;

        if ($builderClass !== null && !empty($steps)) {
            throw new BindingResolutionException(
                sprintf('[%s] - Only "steps" or "builder_class" is allowed to be defined.', $key),
            );
        }

        if ($builderClass === null && empty($steps)) {
            throw new BindingResolutionException(
                sprintf('[%s] - "steps" or "builder_class" must to be defined.', $key),
            );
        }

        if ($builderClass !== null) {
            $this->checkIfFormSettingImplements($key, FormBuilderInterface::class, $builderClass, 'builder_class');
        }

        $this->checkIfFormSettingImplements(
            $key,
            FormSettingsInterface::class,
            $formDefinition['settings_class'],
            'settings_class',
        );

        $this->validateStorage($key, StorageInterface::class, $formDefinition['storage'], 'storage');

        if (isset($formDefinition['session_key_storage'])) {
            $this->validateStorage(
                $key,
                SessionKeyStorageInterface::class,
                $formDefinition['session_key_storage'],
                'session_key_storage',
            );
        }
    }

    /**
     * @param string|object|array{ class?: string|object, parameters?: array<string, mixed> } $class
     *
     * @throws BindingResolutionException
     */
    private function validateStorage(string $key, string $interface, string|object|array $class, string $setting): void
    {
        if (is_array($class)) {
            if (!isset($class['class'])) {
                throw new BindingResolutionException(
                    sprintf('[%s] - "class" option for the "%s" is required.', $key, $setting),
                );
            }

            $class = $class['class'];
        }

        $this->checkIfFormSettingImplements($key, $interface, $class, $setting);
    }

    /**
     * @throws BindingResolutionException
     */
    private function checkIfFormSettingImplements(
        string $key,
        string $interface,
        string|object $class,
        string $setting,
    ): void {
        $this->checkIfClassImplements(
            $interface,
            $class,
            sprintf(
                '[%s] - Cannot use %s class as "%s". Class must implement %s interface.',
                $key,
                is_object($class) ? get_class($class) : $class,
                $setting,
                $interface,
            ),
        );
    }

    /**
     * @param array{
     *     builder_class?: string|FormBuilderInterface<TEntity>,
     *     steps?: array<string, string|StepInterface<TEntity>>,
     *     settings_class: string|FormSettingsInterface,
     *     storage: string|object|array{ class: string|object, parameters?: array<string, mixed> },
     *     session_key_storage?: string|object|array{ class: string|object, parameters?: array<string, mixed> },
     * } $formDefinition
     *
     * @throws BindingResolutionException
     */
    private function createForm(string $key, array $formDefinition): HttpSteppedForm
    {
        $parameters = ['namespace' => $key];

        $formStorage = new FormStorage(
            $this->createFormStorage($formDefinition['storage'], $parameters),
            $this->createFormStorage(
                $formDefinition['session_key_storage'] ?? NullSessionKeyStorage::class,
                $parameters,
            ),
        );

        $dataControl = new DataControl(new DataStorage($formStorage));
        $stepControl = new StepControl($formStorage);

        $form = new SteppedForm(
            $dataControl,
            $stepControl,
            $this->createFormBuilder($formDefinition, $dataControl, $stepControl),
            $this->app->make(EventDispatcherInterface::class),
        );

        return new HttpSteppedForm(
            $form,
            $this->getInstance($formDefinition['settings_class']),
            $this->app->make(RedirectorInterface::class),
            $this->app->make(RendererInterface::class),
            $this->app->make(ExceptionNormalizerInterface::class),
        );
    }

    /**
     * @param array{
     *     builder_class?: string|FormBuilderInterface<TEntity>,
     *     steps?: array<string, string|StepInterface<TEntity>>,
     * } $formDefinition
     *
     * @return StaticStepsFormBuilder|FormBuilderInterface<TEntity>
     *
     * @throws BindingResolutionException
     */
    private function createFormBuilder(
        array $formDefinition,
        DataControl $dataControl,
        StepControl $stepControl,
    ): FormBuilderInterface {
        $steps = $formDefinition['steps'] ?? [];
        $builderClass = $formDefinition['builder_class'] ?? null;

        $stepsBuilder = new StepsBuilder($stepControl, $dataControl);

        return $builderClass !== null
            ? $this->createBuilderFromClass($builderClass, $stepsBuilder)
            : $this->createBuilderFromSteps((array)$steps, $stepsBuilder);
    }

    /**
     * @return FormBuilderInterface<TEntity>
     *
     * @throws BindingResolutionException
     */
    private function createBuilderFromClass(
        string|object $builderClass,
        StepsBuilder $stepsBuilder,
    ): FormBuilderInterface {
        if (is_string($builderClass)) {
            $this->app->when($builderClass)
                ->needs(StepsBuilderInterface::class)
                ->give(static fn (): StepsBuilderInterface => $stepsBuilder);
        }

        return $this->getInstance($builderClass);
    }

    /**
     * @param array<string, string|StepInterface<TEntity>> $steps
     *
     * @throws BindingResolutionException
     */
    private function createBuilderFromSteps(array $steps, StepsBuilder $stepsBuilder): StaticStepsFormBuilder
    {
        foreach ($steps as $key => $step) {
            $stepsBuilder->add($key, $this->getInstance($step));
        }

        return new StaticStepsFormBuilder($stepsBuilder->get());
    }

    /**
     * @param string|object|array{ class: string|object, parameters?: array<string, mixed> } $definition
     * @param array<string, mixed> $parameters
     *
     * @throws BindingResolutionException
     */
    private function createFormStorage(string|object|array $definition, array $parameters): mixed
    {
        $class = $storage = $definition;

        if (is_array($storage)) {
            $class = $storage['class'];
            $parameters += $storage['parameters'] ?? [];
        }

        return $this->getInstance($class, $parameters);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    private function registerRenderer(): void
    {
        $class = $this->getConfig('renderer');

        if ($class === Renderer::class && !$this->app->bound(ViewFactory::class)) {
            $this->missingRequiredPackage('renderer', RendererInterface::class, 'illuminate/view');
        }

        $this->registerService(RendererInterface::class, 'renderer', Renderer::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    private function registerRedirector(): void
    {
        $this->registerService(RedirectorInterface::class, 'redirector', Redirector::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    private function registerEventDispatcher(): void
    {
        $class = $this->getConfig('event_dispatcher');

        if ($class === EventDispatcher::class && !$this->app->bound(Dispatcher::class)) {
            $this->missingRequiredPackage('event_dispatcher', EventDispatcherInterface::class, 'illuminate/events');
        }

        $this->registerService(EventDispatcherInterface::class, 'event_dispatcher', EventDispatcher::class);
    }

    private function registerExceptionNormalizer(): void
    {
        $this->app->singleton(ExceptionNormalizerInterface::class, function () {
            /** @var string[]|ExceptionNormalizerInterface[] $normalizers */
            $normalizers = $this->getConfig('exception_normalizers', []);

            return new ExceptionNormalizer(
                array_map(
                    fn (mixed $normalizer): ExceptionNormalizerInterface => $this->getInstance($normalizer),
                    $normalizers,
                ),
            );
        });
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws BindingResolutionException
     */
    private function getInstance(mixed $instance, array $parameters = []): mixed
    {
        return is_string($instance) ? $this->app->make($instance, $parameters) : $instance;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws BindingResolutionException
     */
    private function registerService(string $abstract, string $configKey, string $default): void
    {
        $class = $this->getConfig($configKey, $default);

        $this->checkIfClassImplements(
            $abstract,
            $class,
            sprintf(
                'Unable to register service. Class %s must implement %s interface.',
                is_object($class) ? get_class($class) : $class,
                $abstract,
            ),
        );

        if ($class instanceof $abstract) {
            $concrete = static fn (): mixed => $class;
        } else {
            $concrete = $class;
        }

        $this->app->singleton($abstract, $concrete);
    }

    /**
     * @throws BindingResolutionException
     */
    private function checkIfClassImplements(string $interface, string|object $class, string $message): void
    {
        if (
            ((is_string($class) && class_exists($class)) || is_object($class))
            && !in_array($interface, (array)class_implements($class), true)
        ) {
            throw new BindingResolutionException($message);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function getConfig(string $key, mixed $default = null): mixed
    {
        /** @var Repository $config */
        $config = $this->app->get(Repository::class);

        return $config->get("stepped-form.$key", $default);
    }

    private function missingRequiredPackage(string $service, string $abstract, string $package): void
    {
        throw new LogicException(
            sprintf(
                'Unable to register %s. Use your own implementation of %s or install %s package to use default one.',
                $service,
                $abstract,
                $package,
            ),
        );
    }
}
