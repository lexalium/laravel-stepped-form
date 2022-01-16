<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Routing;

use Illuminate\Routing\Redirector as LaravelRedirector;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Symfony\Component\HttpFoundation\Response;

class Redirector implements RedirectorInterface
{
    public function __construct(private LaravelRedirector $redirector)
    {
    }

    public function redirect(string $url, array $errors = []): Response
    {
        $response = $this->redirector->to($url);

        if ($errors) {
            $response->withErrors($errors)
                ->withInput();
        }

        return $response;
    }
}
