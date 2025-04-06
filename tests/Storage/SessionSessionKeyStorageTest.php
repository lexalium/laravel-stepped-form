<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\LaravelSteppedForm\Storage\SessionSessionKeyStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SessionSessionKeyStorageTest extends TestCase
{
    private MockObject $session;
    private SessionSessionKeyStorage $storage;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);

        $this->storage = new SessionSessionKeyStorage($this->session, 'form');
    }

    public function testGet(): void
    {
        $this->session->expects(self::once())
            ->method('get')
            ->with('form.key')
            ->willReturn('main');

        $this->assertEquals('main', $this->storage->get('key'));
    }

    public function testPut(): void
    {
        $this->session->expects(self::once())
            ->method('put')
            ->with('form.key', 'main');

        $this->storage->put('key', 'main');
    }
}
