<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\LaravelSteppedForm\Storage\SessionStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SessionStorageTest extends TestCase
{
    private MockObject $session;
    private SessionStorage $storage;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);

        $this->storage = new SessionStorage($this->session, 'form');
    }

    public function testHas(): void
    {
        $matcher = $this->exactly(2);

        $this->session->expects($matcher)
            ->method('has')
            ->willReturnCallback(static function (mixed $value) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => self::assertEquals('form.key', $value),
                    2 => self::assertEquals('form.key2', $value),
                    default => true,
                };

                $return = [1 => true, 2 => false];

                return $return[$matcher->numberOfInvocations()];
            });

        $this->assertTrue($this->storage->has('key'));
        $this->assertFalse($this->storage->has('key2'));
    }

    public function testGet(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('form.key', ['default'])
            ->willReturn(['data' => 'test']);

        $this->assertEquals(['data' => 'test'], $this->storage->get('key', ['default']));
    }

    public function testPut(): void
    {
        $this->session->expects($this->once())
            ->method('put')
            ->with('form.key', ['data' => 'test']);

        $this->storage->put('key', ['data' => 'test']);
    }

    public function testClear(): void
    {
        $this->session->expects($this->once())
            ->method('forget')
            ->with('form');

        $this->storage->clear();
    }
}
