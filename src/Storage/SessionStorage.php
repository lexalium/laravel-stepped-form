<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Exception\ReadSessionKeyException;
use Lexal\SteppedForm\Form\Storage\SessionStorageInterface;
use Lexal\SteppedForm\Form\Storage\StorageInterface;

use function sprintf;

final class SessionStorage implements StorageInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly SessionStorageInterface $sessionStorage,
        private readonly string $namespace,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws ReadSessionKeyException
     */
    public function has(string $key): bool
    {
        return $this->session->has($this->getStorageKey($key));
    }

    /**
     * @inheritDoc
     *
     * @throws ReadSessionKeyException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($this->getStorageKey($key), $default);
    }

    /**
     * @inheritDoc
     *
     * @throws ReadSessionKeyException
     */
    public function put(string $key, mixed $data): void
    {
        $this->session->put($this->getStorageKey($key), $data);
    }

    /**
     * @inheritDoc
     *
     * @throws ReadSessionKeyException
     */
    public function clear(): void
    {
        $sessionKey = $this->sessionStorage->getCurrent();

        $this->session->forget(sprintf("%s.%s", $this->namespace, $sessionKey));
    }

    /**
     * @throws ReadSessionKeyException
     */
    private function getStorageKey(string $key): string
    {
        $sessionKey = $this->sessionStorage->getCurrent();

        return sprintf("%s.%s.%s", $this->namespace, $sessionKey, $key);
    }
}
