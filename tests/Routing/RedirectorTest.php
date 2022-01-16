<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector as LaravelRedirector;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\LaravelSteppedForm\Routing\Redirector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectorTest extends TestCase
{
    private MockObject $laravelRedirector;
    private RedirectorInterface $redirector;

    public function testRedirect(): void
    {
        $response = $this->createMock(RedirectResponse::class);

        $response->expects($this->never())
            ->method('withErrors');

        $response->expects($this->never())
            ->method('withInput');

        $this->laravelRedirector->expects($this->once())
            ->method('to')
            ->with('test.com/test/url')
            ->willReturn($response);

        $this->redirector->redirect('test.com/test/url');
    }

    public function testRedirectWithErrors(): void
    {
        $response = $this->createMock(RedirectResponse::class);

        $response->expects($this->once())
            ->method('withErrors')
            ->with(['error' => 'message'])
            ->willReturn($response);

        $response->expects($this->once())
            ->method('withInput')
            ->willReturn($response);

        $this->laravelRedirector->expects($this->once())
            ->method('to')
            ->with('test.com/test/url')
            ->willReturn($response);

        $this->redirector->redirect('test.com/test/url', ['error' => 'message']);
    }

    protected function setUp(): void
    {
        $this->laravelRedirector = $this->createMock(LaravelRedirector::class);

        $this->redirector = new Redirector($this->laravelRedirector);

        parent::setUp();
    }
}
