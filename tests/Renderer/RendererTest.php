<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Renderer;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\SteppedForm\Step\TemplateDefinition;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class RendererTest extends TestCase
{
    private Factory&Stub $view;
    private RendererInterface $renderer;

    protected function setUp(): void
    {
        $this->view = $this->createStub(Factory::class);

        $this->renderer = new Renderer($this->view);
    }

    public function testRender(): void
    {
        $view = $this->createStub(View::class);

        $view->method('render')
            ->willReturn('Test content');

        $this->view->method('make')
            ->willReturn($view);

        $actual = $this->renderer->render(new TemplateDefinition('test.template', ['data' => 'hello']));

        self::assertEquals(new Response('Test content'), $actual);
    }
}
