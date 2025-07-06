<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\ServiceProvider;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
use Lexal\HttpSteppedForm\SteppedFormInterface;
use Lexal\LaravelSteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\LaravelSteppedForm\ServiceProvider\ServiceProvider;
use Lexal\LaravelSteppedForm\Tests\FormBuilder;
use Lexal\LaravelSteppedForm\Tests\TestApplication;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\Exception\SteppedFormException;
use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Form\Storage\SessionKeyStorageInterface;
use Lexal\SteppedForm\Form\Storage\StorageInterface;
use Lexal\SteppedForm\Step\Builder\StepsBuilderInterface;
use Lexal\SteppedForm\Step\Step;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SteppedForm\Step\StepKey;
use Lexal\SteppedForm\Step\Steps;
use Lexal\SteppedForm\Step\TemplateDefinition;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

use function dirname;
use function sprintf;

final class ServiceProviderTest extends TestCase
{
    public function testBoot(): void
    {
        $app = new TestApplication(
            defaultConfig: ['stepped-form' => ['redirector' => 'custom', 'exception_normalizers' => []]],
        );

        $serviceProvider = new ServiceProvider($app);

        $serviceProvider->boot();

        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'stepped-form.php';

        self::assertEquals([$path => 'stepped-form.php'], ServiceProvider::pathsToPublish(ServiceProvider::class));

        /** @var Repository $config */
        $config = $app->get('config');

        $expectedConfig = [
            'renderer' => Renderer::class,
            'redirector' => 'custom',
            'event_dispatcher' => EventDispatcher::class,
            'forms' => [],
            'exception_normalizers' => [],
        ];

        self::assertEquals($expectedConfig, $config->get('stepped-form'));
    }

    public function testRegister(): void
    {
        $app = new TestApplication(
            defaultConfig: ['stepped-form' => [
                'renderer' => self::createRenderer(),
                'redirector' => 'redirector',
                'event_dispatcher' => 'event_dispatcher',
                'forms' => [],
                'exception_normalizers' => [
                    self::createExceptionNormalizer(),
                    'exception_normalizer',
                ],
            ]],
        );

        $app->singleton('redirector', fn () => self::createRedirector());
        $app->singleton('event_dispatcher', fn () => self::createEventDispatcher());
        $app->singleton('exception_normalizer', fn () => self::createExceptionNormalizer());

        $serviceProvider = new ServiceProvider($app);

        $serviceProvider->boot();
        $serviceProvider->register();

        self::assertTrue($app->bound(RendererInterface::class));
        self::assertTrue($app->isShared(RendererInterface::class));

        $app->get(RendererInterface::class);

        self::assertTrue($app->bound(RedirectorInterface::class));
        self::assertTrue($app->isShared(RedirectorInterface::class));

        $app->get(RedirectorInterface::class);

        self::assertTrue($app->bound(EventDispatcherInterface::class));
        self::assertTrue($app->isShared(EventDispatcherInterface::class));

        $app->get(EventDispatcherInterface::class);

        self::assertTrue($app->bound(ExceptionNormalizerInterface::class));
        self::assertTrue($app->isShared(ExceptionNormalizerInterface::class));

        $app->get(ExceptionNormalizerInterface::class);
    }

    /**
     * @param array<string, mixed> $formConfig
     * @param array<string, mixed> $expectedStorageParameters
     * @param array<string, mixed> $expectedSessionKeyStorageParameters
     */
    #[DataProvider('registerFormsDataProvider')]
    public function testRegisterForms(
        array $formConfig,
        array $expectedStorageParameters,
        array $expectedSessionKeyStorageParameters,
    ): void {
        $app = new TestApplication(
            defaultConfig: ['stepped-form' => [
                'renderer' => self::createRenderer(),
                'redirector' => self::createRedirector(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => ['form1' => $formConfig],
                'exception_normalizers' => [
                    self::createExceptionNormalizer(),
                ],
            ]],
        );

        $app->singleton('settings_class', static fn (): FormSettingsInterface => self::createFormSettings());

        $app->singleton(
            'storage',
            static function (Application $app, array $parameters) use ($expectedStorageParameters): StorageInterface {
                self::assertEquals($expectedStorageParameters, $parameters);

                return self::createStorage();
            },
        );

        $app->singleton(
            'session_key_storage',
            static function (
                Application $app,
                array $parameters,
            ) use ($expectedSessionKeyStorageParameters): SessionKeyStorageInterface {
                self::assertEquals($expectedSessionKeyStorageParameters, $parameters);

                return self::createSessionKeyStorage();
            },
        );

        $serviceProvider = new ServiceProvider($app);

        $serviceProvider->boot();
        $serviceProvider->register();

        self::assertTrue($app->bound('stepped-form.form1'));
        self::assertTrue($app->isShared('stepped-form.form1'));

        /** @var SteppedFormInterface $form */
        $form = $app->get('stepped-form.form1');

        $form->start(new stdClass());
    }

    /**
     * @return iterable<string, array{ 0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed> }>
     */
    public static function registerFormsDataProvider(): iterable
    {
        yield 'instances are used to build stepped form' => [
            [
                'builder_class' => self::createFormBuilder(),
                'settings_class' => self::createFormSettings(),
                'storage' => self::createStorage(),
                'session_key_storage' => self::createSessionKeyStorage(),
            ],
            [],
            [],
        ];

        yield 'service aliases are used to build stepped form' => [
            [
                'builder_class' => FormBuilder::class,
                'settings_class' => 'settings_class',
                'storage' => 'storage',
                'session_key_storage' => 'session_key_storage',
            ],
            ['namespace' => 'form1'],
            ['namespace' => 'form1'],
        ];

        yield '"steps" config is used to build stepped form' => [
            [
                'steps' => [
                    'step1' => self::createStep(),
                ],
                'settings_class' => 'settings_class',
                'storage' => 'storage',
                'session_key_storage' => 'session_key_storage',
            ],
            ['namespace' => 'form1'],
            ['namespace' => 'form1'],
        ];

        yield '"storage" and "session_key_storage" are defined as array' => [
            [
                'builder_class' => FormBuilder::class,
                'settings_class' => 'settings_class',
                'storage' => [
                    'class' => 'storage',
                    'parameters' => ['test' => 'test'],
                ],
                'session_key_storage' => [
                    'class' => 'session_key_storage',
                    'parameters' => ['session_key' => 'test'],
                ],
            ],
            ['namespace' => 'form1', 'test' => 'test'],
            ['namespace' => 'form1', 'session_key' => 'test'],
        ];

        yield 'default "session_key_storage" is used' => [
            [
                'builder_class' => FormBuilder::class,
                'settings_class' => 'settings_class',
                'storage' => [
                    'class' => 'storage',
                    'parameters' => ['test' => 'test'],
                ],
            ],
            ['namespace' => 'form1', 'test' => 'test'],
            [],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('registerThrowsFormValidationExceptionDataProvider')]
    public function testRegisterThrowsFormValidationException(array $config, string $expected): void
    {
        $this->expectExceptionObject(new BindingResolutionException($expected));

        $serviceProvider = new ServiceProvider(new TestApplication(defaultConfig: ['stepped-form' => $config]));

        $serviceProvider->boot();
        $serviceProvider->register();
    }

    /**
     * @return iterable<string, array{ 0: array<string, mixed>, 1: string }>
     */
    public static function registerThrowsFormValidationExceptionDataProvider(): iterable
    {
        yield 'key is not a string' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    5 => [],
                ],
            ],
            'Expected argument of type "string", "int" given.',
        ];

        yield 'invalid step key' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form definition' => [],
                ],
            ],
            'Form key must have only "A-z", "0-9", "-" and "_".',
        ];

        yield '"settings_class" is not set' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'steps' => [],
                        'storage' => self::createStorage(),
                    ],
                ],
            ],
            '[form1] - Form Definition must have the following required options: settings_class, storage.',
        ];

        yield '"storage" is not set' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'steps' => [],
                        'settings_class' => self::createFormSettings(),
                    ],
                ],
            ],
            '[form1] - Form Definition must have the following required options: settings_class, storage.',
        ];

        yield '"builder_class" asd "steps" are set' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'steps' => [
                            'step1' => self::createStep(),
                        ],
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => self::createStorage(),
                    ],
                ],
            ],
            '[form1] - Only "steps" or "builder_class" is allowed to be defined.',
        ];

        yield '"builder_class" asd "steps" are not set' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'settings_class' => self::createFormSettings(),
                        'storage' => self::createStorage(),
                    ],
                ],
            ],
            '[form1] - "steps" or "builder_class" must to be defined.',
        ];

        yield '"builder_class" instance does not implement FormBuilderInterface' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => new stdClass(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => self::createStorage(),
                    ],
                ],
            ],
            sprintf(
                '[form1] - Cannot use %s class as "builder_class". Class must implement %s interface.',
                stdClass::class,
                FormBuilderInterface::class,
            ),
        ];

        yield '"settings_class" instance does not implement FormSettingsInterface' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => stdClass::class,
                        'storage' => self::createStorage(),
                    ],
                ],
            ],
            sprintf(
                '[form1] - Cannot use %s class as "settings_class". Class must implement %s interface.',
                stdClass::class,
                FormSettingsInterface::class,
            ),
        ];

        yield 'storage "class" is not set when array' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => [],
                    ],
                ],
            ],
            '[form1] - "class" option for the "storage" is required.',
        ];

        yield 'storage "class" instance does not implement StorageInterface' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => stdClass::class,
                    ],
                ],
            ],
            sprintf(
                '[form1] - Cannot use %s class as "storage". Class must implement %s interface.',
                stdClass::class,
                StorageInterface::class,
            ),
        ];

        yield 'session_key_storage "class" is not set when array' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => self::createStorage(),
                        'session_key_storage' => [],
                    ],
                ],
            ],
            '[form1] - "class" option for the "session_key_storage" is required.',
        ];

        yield 'session_key_storage "class" instance does not implement StorageInterface' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => self::createEventDispatcher(),
                'forms' => [
                    'form1' => [
                        'builder_class' => self::createFormBuilder(),
                        'settings_class' => self::createFormSettings(),
                        'storage' => self::createStorage(),
                        'session_key_storage' => [
                            'class' => new stdClass(),
                        ],
                    ],
                ],
            ],
            sprintf(
                '[form1] - Cannot use %s class as "session_key_storage". Class must implement %s interface.',
                stdClass::class,
                SessionKeyStorageInterface::class,
            ),
        ];
    }

    public function testRegisterThrowsExceptionWhenConfigRepositoryIsNotRegistered(): void
    {
        $this->expectExceptionObject(
            new LogicException(
                sprintf(
                    'Unable to register classes without binding of %s contract to the concrete class.',
                    Repository::class,
                ),
            ),
        );

        $serviceProvider = new ServiceProvider(
            new TestApplication(
                boundCallback: fn (string $abstract) => $abstract !== Repository::class,
            ),
        );

        $serviceProvider->boot();
        $serviceProvider->register();
    }

    #[DataProvider('registerThrowsExceptionWhenRequiredPackagesMissedDataProvider')]
    public function testRegisterThrowsExceptionWhenRequiredPackagesMissed(
        string $containerAbstract,
        string $service,
        string $abstract,
        string $package,
    ): void {
        $exception = new LogicException(
            sprintf(
                'Unable to register %s. Use your own implementation of %s or install %s package to use default one.',
                $service,
                $abstract,
                $package,
            ),
        );

        $this->expectExceptionObject($exception);

        $serviceProvider = new ServiceProvider(
            new TestApplication(
                boundCallback: fn (string $abstract) => $abstract !== $containerAbstract,
            ),
        );

        $serviceProvider->boot();
        $serviceProvider->register();
    }

    /**
     * @return iterable<string, array{ 0: string, 1: string, 2: string, 3: string }>
     */
    public static function registerThrowsExceptionWhenRequiredPackagesMissedDataProvider(): iterable
    {
        yield 'renderer' => [
            Factory::class,
            'renderer',
            RendererInterface::class,
            'illuminate/view',
        ];

        yield 'event dispatcher' => [
            Dispatcher::class,
            'event_dispatcher',
            EventDispatcherInterface::class,
            'illuminate/events',
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('registerThrowsExceptionWhenMustImplementInterfaceDataProvider')]
    public function testRegisterThrowsExceptionWhenMustImplementInterface(array $config, string $abstract): void
    {
        $this->expectExceptionObject(
            new BindingResolutionException(
                sprintf(
                    'Unable to register service. Class %s must implement %s interface.',
                    stdClass::class,
                    $abstract,
                ),
            ),
        );

        $serviceProvider = new ServiceProvider(new TestApplication(defaultConfig: ['stepped-form' => $config]));

        $serviceProvider->boot();
        $serviceProvider->register();
    }

    /**
     * @return iterable<string, array{ 0: array<string, mixed>, 1: string }>
     */
    public static function registerThrowsExceptionWhenMustImplementInterfaceDataProvider(): iterable
    {
        yield 'renderer' => [
            [
                'renderer' => stdClass::class,
                'event_dispatcher' => self::createEventDispatcher(),
            ],
            RendererInterface::class,
        ];

        yield 'redirector' => [
            [
                'renderer' => self::createRenderer(),
                'redirector' => stdClass::class,
                'event_dispatcher' => self::createEventDispatcher(),
            ],
            RedirectorInterface::class,
        ];

        yield 'event dispatcher' => [
            [
                'renderer' => self::createRenderer(),
                'event_dispatcher' => stdClass::class,
            ],
            EventDispatcherInterface::class,
        ];
    }

    private static function createRenderer(): RendererInterface
    {
        return new class () implements RendererInterface {
            public function render(TemplateDefinition $definition): Response
            {
                return new Response();
            }
        };
    }

    private static function createRedirector(): RedirectorInterface
    {
        return new class () implements RedirectorInterface {
            public function redirect(string $url, array $errors = []): Response
            {
                return new Response();
            }
        };
    }

    private static function createEventDispatcher(): EventDispatcherInterface
    {
        return new class () implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
    }

    private static function createFormSettings(): FormSettingsInterface
    {
        return new class () implements FormSettingsInterface {
            public function getStepUrl(StepKey $key): string
            {
                return '';
            }

            public function getUrlAfterFinish(): string
            {
                return '';
            }

            public function getUrlBeforeStart(): string
            {
                return '';
            }
        };
    }

    /**
     * @return FormBuilderInterface<object>
     */
    private static function createFormBuilder(): FormBuilderInterface
    {
        return new class (self::createStep()) implements FormBuilderInterface {
            /**
             * @param StepInterface<object> $step
             */
            public function __construct(private readonly StepInterface $step)
            {
            }

            public function isDynamic(): bool
            {
                return false;
            }

            public function build(object $entity): Steps
            {
                return new Steps([new Step(new StepKey('step1'), $this->step, false, true)]);
            }
        };
    }

    private static function createStorage(): StorageInterface
    {
        return new class () implements StorageInterface {
            /**
             * @var array<string, mixed>
             */
            private array $data = [];

            public function get(string $key, string $session, mixed $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }

            public function put(string $key, string $session, mixed $data): void
            {
                $this->data[$key] = $data;
            }

            public function clear(string $session): void
            {
                // nothing to do
            }
        };
    }

    private static function createSessionKeyStorage(): SessionKeyStorageInterface
    {
        return new class () implements SessionKeyStorageInterface {
            public function get(string $key): string
            {
                return '';
            }

            public function put(string $key, string $session): void
            {
                // nothing to do
            }
        };
    }

    /**
     * @return StepInterface<object>
     */
    private static function createStep(): StepInterface
    {
        return new class () implements StepInterface {
            public function handle(object $entity, mixed $data): object
            {
                return $entity;
            }
        };
    }

    private static function createExceptionNormalizer(): ExceptionNormalizerInterface
    {
        return new class () implements ExceptionNormalizerInterface {
            public function supportsNormalization(SteppedFormException $exception): bool
            {
                return true;
            }

            public function normalize(SteppedFormException $exception, FormSettingsInterface $formSettings): Response
            {
                throw $exception;
            }
        };
    }
}
