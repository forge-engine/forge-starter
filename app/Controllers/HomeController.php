<?php

declare(strict_types=1);

namespace App\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class HomeController
{
    use ControllerHelper;

    #[Route("/")]
    public function index(): Response
    {
        $data = [
            "title" => "Welcome to Forge Framework",
        ];

        return $this->view(view: "pages/home/index", data: $data);
    }
}
