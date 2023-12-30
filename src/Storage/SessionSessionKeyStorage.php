<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Form\Storage\SessionStorageInterface;

use function sprintf;

final class SessionSessionKeyStorage implements SessionStorageInterface
{
    private const STORAGE_KEY = '__CURRENT_SESSION_KEY__';

    public function __construct(private readonly Session $session, private readonly string $namespace)
    {
    }

    public function getCurrent(): ?string
    {
        return $this->session->get($this->getStorageKey());
    }

    public function setCurrent(string $sessionKey): void
    {
        $this->session->put($this->getStorageKey(), $sessionKey);
    }

    private function getStorageKey(): string
    {
        return sprintf("%s.%s", $this->namespace, self::STORAGE_KEY);
    }
}
