<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\ServiceProvider;

use Illuminate\Contracts\Session\Session;
use Lexal\HttpSteppedForm\SteppedForm as HttpSteppedForm;
use Lexal\LaravelSteppedForm\Storage\SessionSessionKeyStorage;
use Lexal\LaravelSteppedForm\Storage\SessionStorage;
use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Form\Builder\StaticStepsFormBuilder;
use Lexal\SteppedForm\Form\DataControl;
use Lexal\SteppedForm\Form\StepControl;
use Lexal\SteppedForm\Form\Storage\DataStorage;
use Lexal\SteppedForm\Form\Storage\SessionStorageInterface;
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
use Lexal\SteppedForm\EntityCopy\EntityCopyInterface;
use Lexal\SteppedForm\EntityCopy\SimpleEntityCopy;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use LogicException;
use Psr\Container\ContainerExceptionInterface;

use function array_map;
use function class_implements;
use function compact;
use function dirname;
use function get_class;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;

final class ServiceProvider extends LaravelServiceProvider
{
    private const FORM_KEY_PATTERN = '/^[A-Za-z0-9-]+$/';

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
        $this->registerEntityCopy();
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
     *     builder_class?: string|FormBuilderInterface,
     *     steps?: array<string, string|StepInterface>,
     *     settings_class?: string|FormSettingsInterface,
     *     storage?: string|object|array{ class?: string|object, parameters?: array<string, mixed> },
     *     session_storage?: string|object|array{ class?: string|object, parameters?: array<string, mixed> },
     *     } $formDefinition
     *
     * @throws BindingResolutionException
     */
    private function validateForm(string $key, array $formDefinition): void
    {
        if (!preg_match(self::FORM_KEY_PATTERN, $key)) {
            throw new BindingResolutionException('Form key must have only "A-z", "0-9" and "-".');
        }

        if (!isset($formDefinition['settings_class'], $formDefinition['storage'], $formDefinition['session_storage'])) {
            throw new BindingResolutionException(
                sprintf(
                    'Form Definition [%s] must have the following required options: '
                    . 'settings_class, storage, session_storage.',
                    $key,
                ),
            );
        }

        $steps = $formDefinition['steps'] ?? [];
        $builderClass = $formDefinition['builder_class'] ?? null;

        if ($builderClass !== null && !empty($steps)) {
            throw new BindingResolutionException('Only "steps" or "builder_class" allowed to be defined.');
        }

        if ($builderClass === null && empty($steps)) {
            throw new BindingResolutionException('"steps" or "builder_class" setting must to be defined.');
        }

        if ($builderClass !== null) {
            $this->checkIfClassImplements($key, FormBuilderInterface::class, $builderClass);
        }

        $this->checkIfClassImplements($key, FormSettingsInterface::class, $formDefinition['settings_class']);

        $this->validateStorage($key, $formDefinition['storage'], StorageInterface::class);
        $this->validateStorage($key, $formDefinition['session_storage'], SessionStorageInterface::class);
    }

    /**
     * @param string|object|array{ class?: string|object, parameters?: array<string, mixed> } $storageClass
     *
     * @throws BindingResolutionException
     */
    private function validateStorage(string $key, string|object|array $storageClass, string $interface): void
    {
        if (is_array($storageClass)) {
            if (!isset($storageClass['class'])) {
                throw new BindingResolutionException('"class" option for the storage is required.');
            }

            $storageClass = $storageClass['class'];
        }

        $this->checkIfClassImplements($key, $interface, $storageClass);
    }

    /**
     * @throws BindingResolutionException
     */
    private function checkIfClassImplements(string $key, string $interface, string|object $class): void
    {
        if (!in_array($interface, (array)class_implements($class), true)) {
            throw new BindingResolutionException(
                sprintf(
                    'Cannot use %s class as Form Settings for the [%s] form. Class must implement %s interface.',
                    is_object($class) ? get_class($class) : $class,
                    $key,
                    $interface,
                ),
            );
        }
    }

    /**
     * @param array{
     *     builder_class?: string|FormBuilderInterface,
     *     steps?: array<string, string|StepInterface>,
     *     settings_class: string|FormSettingsInterface,
     *     storage: string|object|array{ class: string|object, parameters?: array<string, mixed> },
     *     session_storage: string|object|array{ class: string|object, parameters?: array<string, mixed> },
     * } $formDefinition
     *
     * @throws BindingResolutionException
     */
    private function createForm(string $key, array $formDefinition): HttpSteppedForm
    {
        $parameters = ['namespace' => $key];

        $sessionStorage = $this->createFormStorage(
            $formDefinition['session_storage'],
            SessionStorageInterface::class,
            $parameters,
        );

        $storage = $this->createFormStorage(
            $formDefinition['storage'],
            StorageInterface::class,
            $parameters + compact('sessionStorage'),
        );

        $dataControl = new DataControl(new DataStorage($storage));
        $stepControl = new StepControl($storage);

        $form = new SteppedForm(
            $dataControl,
            $stepControl,
            $storage,
            $this->createFormBuilder($formDefinition, $dataControl, $stepControl),
            $this->app->make(EventDispatcherInterface::class),
            $this->app->make(EntityCopyInterface::class),
            $sessionStorage,
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
     *     builder_class?: string|FormBuilderInterface,
     *     steps?: array<string, string|StepInterface>,
     * } $formDefinition
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
     * @param array<string, string|StepInterface> $steps
     *
     * @throws BindingResolutionException
     */
    private function createBuilderFromSteps(array $steps, StepsBuilder $stepsBuilder): FormBuilderInterface
    {
        foreach ($steps as $key => $step) {
            $stepsBuilder->add($key, $this->getInstance($step));
        }

        return new StaticStepsFormBuilder($stepsBuilder->get());
    }

    /**
     * @template TStorage
     *
     * @param string|object|array{ class: string|object, parameters?: array<string, mixed> } $definition
     * @param class-string<TStorage> $interface
     * @param array<string, mixed> $parameters
     *
     * @return TStorage
     *
     * @throws BindingResolutionException
     */
    private function createFormStorage(string|object|array $definition, string $interface, array $parameters): mixed
    {
        $class = $storage = $definition;

        if (is_array($storage)) {
            $class = $storage['class'];
            $parameters += $storage['parameters'] ?? [];
        }

        if (
            ($class === SessionStorage::class || $class === SessionSessionKeyStorage::class)
            && !$this->app->bound(Session::class)
        ) {
            $this->missingRequiredPackage('storage', $interface, 'illuminate/session');
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
    private function registerEntityCopy(): void
    {
        $this->registerService(EntityCopyInterface::class, 'entity_copy', SimpleEntityCopy::class);
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

        if (!in_array($abstract, (array)class_implements($class), true)) {
            throw new BindingResolutionException(
                sprintf(
                    'Unable to register service. Class %s must implement %s interface.',
                    is_object($class) ? get_class($class) : $class,
                    $abstract,
                ),
            );
        }

        if ($class instanceof $abstract) {
            $concrete = static fn (): mixed => $class;
        } else {
            $concrete = $class;
        }

        $this->app->singleton($abstract, $concrete);
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
