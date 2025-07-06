<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\ServiceProvider;

final class TestApplication extends Container implements Application
{
    /**
     * @param array<string, mixed> $defaultConfig
     * @param null|Closure(string): bool $boundCallback
     */
    public function __construct(array $defaultConfig = [], private readonly ?Closure $boundCallback = null)
    {
        $this->singleton('config', fn () => new Repository($defaultConfig));
        $this->alias('config', RepositoryContract::class);
    }

    public function version(): string
    {
        return '1.0';
    }

    public function basePath($path = ''): string
    {
        return '';
    }

    public function bootstrapPath($path = ''): string
    {
        return '';
    }

    public function configPath($path = ''): string
    {
        return $path;
    }

    public function databasePath($path = ''): string
    {
        return '';
    }

    public function langPath($path = ''): string
    {
        return '';
    }

    public function publicPath($path = ''): string
    {
        return '';
    }

    public function resourcePath($path = ''): string
    {
        return '';
    }

    public function storagePath($path = ''): string
    {
        return '';
    }

    /**
     * @param string[] $environments
     */
    public function environment(...$environments): string
    {
        return 'test';
    }

    public function runningInConsole(): bool
    {
        return true;
    }

    public function runningUnitTests(): bool
    {
        return true;
    }

    public function hasDebugModeEnabled(): bool
    {
        return false;
    }

    public function maintenanceMode(): MaintenanceMode
    {
        return new class () implements MaintenanceMode
        {
            /**
             * @param array<string, mixed> $payload
             */
            public function activate(array $payload): void
            {
            }

            public function deactivate(): void
            {
            }

            public function active(): bool
            {
                return false;
            }

            /**
             * @return array<string, mixed>
             */
            public function data(): array
            {
                return [];
            }
        };
    }

    public function isDownForMaintenance(): bool
    {
        return false;
    }

    public function registerConfiguredProviders(): void
    {
    }

    /**
     * @param ServiceProvider|string $provider
     * @param bool $force
     */
    public function register($provider, $force = false): ServiceProvider
    {
        return new class ($this) extends ServiceProvider {
        };
    }

    /**
     * @param string $provider
     * @param string|null $service
     */
    public function registerDeferredProvider($provider, $service = null): void
    {
    }

    /**
     * @param string $provider
     */
    public function resolveProvider($provider): ServiceProvider
    {
        return new class ($this) extends ServiceProvider {
        };
    }

    public function boot(): void
    {
    }

    /**
     * @param callable $callback
     */
    public function booting($callback): void
    {
    }

    /**
     * @param callable $callback
     */
    public function booted($callback): void
    {
    }

    /**
     * @param array<string, object> $bootstrappers
     */
    public function bootstrapWith(array $bootstrappers): void
    {
    }

    public function getLocale(): string
    {
        return 'en';
    }

    public function getNamespace(): string
    {
        return '';
    }

    /**
     * @param ServiceProvider|string $provider
     *
     * @return ServiceProvider[]
     */
    public function getProviders($provider): array
    {
        return [];
    }

    public function hasBeenBootstrapped(): bool
    {
        return true;
    }

    public function loadDeferredProviders(): void
    {
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale): void
    {
    }

    public function shouldSkipMiddleware(): bool
    {
        return false;
    }

    /**
     * @param callable|string $callback
     */
    public function terminating($callback): self
    {
        return $this;
    }

    public function terminate(): void
    {
    }

    /**
     * @param string $abstract
     */
    public function bound($abstract): bool
    {
        return $this->boundCallback !== null ? ($this->boundCallback)($abstract) : parent::bound($abstract);
    }
}
