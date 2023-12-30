<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Renderer;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\SteppedForm\Step\TemplateDefinition;
use Symfony\Component\HttpFoundation\Response;

final class Renderer implements RendererInterface
{
    public function __construct(private readonly ViewFactory $view)
    {
    }

    public function render(TemplateDefinition $definition): Response
    {
        $view = $this->view->make($definition->template, $definition->data);

        return new Response($view->render());
    }
}
