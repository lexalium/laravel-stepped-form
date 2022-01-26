<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\ServiceProvider;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\LaravelSteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\LaravelSteppedForm\Event\Listener\BeforeHandleStepListener;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\LaravelSteppedForm\Routing\Redirector;
use Lexal\LaravelSteppedForm\Validator\Validator;
use Lexal\LaravelSteppedForm\Validator\ValidatorInterface;
use Lexal\SteppedForm\Data\FormDataStorage;
use Lexal\SteppedForm\Data\FormDataStorageInterface;
use Lexal\SteppedForm\Data\StepControl;
use Lexal\SteppedForm\Data\StepControlInterface;
use Lexal\SteppedForm\Data\Storage\StorageInterface;
use Lexal\SteppedForm\EntityCopy\EntityCopyInterface;
use Lexal\SteppedForm\EntityCopy\SimpleEntityCopy;
use Lexal\SteppedForm\EventDispatcher\Event\BeforeHandleStep;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\State\FormState;
use Lexal\SteppedForm\State\FormStateInterface;
use Lexal\SteppedForm\Steps\Builder\StepsBuilder;
use Lexal\SteppedForm\Steps\Builder\StepsBuilderInterface;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_map;
use function dirname;
use function is_string;
use function sprintf;

class ServiceProvider extends LaravelServiceProvider
{
    private const CONFIG_FILENAME = 'stepped-form.php';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function boot(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        $this->publishes([
            $path => $this->app->configPath(self::CONFIG_FILENAME),
        ]);

        $this->mergeConfigFrom($path, 'stepped-form');

        if ($this->app->bound(Dispatcher::class) && $this->app->bound(ValidationFactory::class)) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = $this->app->get(Dispatcher::class);

            $dispatcher->listen(BeforeHandleStep::class, [BeforeHandleStepListener::class, 'handle']);
        }
    }

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

        $this->registerExceptionNormalizer();
        $this->registerRenderer();
        $this->registerRedirector();
        $this->registerDefaultStorage();
        $this->registerFormState();
        $this->registerStepsBuilder();
        $this->registerEventDispatcher();
        $this->registerValidator();
        $this->registerEntityCopy();
    }

    private function registerExceptionNormalizer(): void
    {
        $this->app->singleton(ExceptionNormalizerInterface::class, function () {
            /** @var string[]|ExceptionNormalizerInterface[] $normalizers */
            $normalizers = $this->getConfig('exception_normalizers', []);

            return new ExceptionNormalizer(
                array_map(fn (mixed $normalizer) => $this->getInstance($normalizer), $normalizers),
            );
        });
    }

    private function registerRenderer(): void
    {
        if ($this->app->bound(ViewFactory::class)) {
            $this->app->singleton(RendererInterface::class, Renderer::class);
        }
    }

    private function registerRedirector(): void
    {
        $this->app->singleton(RedirectorInterface::class, Redirector::class);
    }

    private function registerDefaultStorage(): void
    {
        $this->app->singleton(StorageInterface::class, fn () => $this->getInstance(
            $this->getConfig('storage.type'),
            $this->getConfig('storage.parameters', []),
        ));
    }

    private function registerFormState(): void
    {
        $this->app->singleton(FormDataStorageInterface::class, FormDataStorage::class);
        $this->app->singleton(StepControlInterface::class, StepControl::class);
        $this->app->singleton(FormStateInterface::class, FormState::class);
    }

    private function registerStepsBuilder(): void
    {
        $this->app->singleton(StepsBuilderInterface::class, StepsBuilder::class);
    }

    private function registerEventDispatcher(): void
    {
        if ($this->app->bound(Dispatcher::class)) {
            $this->app->singleton(EventDispatcherInterface::class, EventDispatcher::class);
        }
    }

    private function registerValidator(): void
    {
        $this->app->singleton(ValidatorInterface::class, Validator::class);
    }

    private function registerEntityCopy(): void
    {
        $this->app->singleton(EntityCopyInterface::class, SimpleEntityCopy::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getConfig(string $key, mixed $default = null): mixed
    {
        /** @var Repository $config */
        $config = $this->app->get(Repository::class);

        return $config->get("stepped-form.$key", $default);
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
}
