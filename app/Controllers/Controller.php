<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::make($template, $data), $status);
    }
}
