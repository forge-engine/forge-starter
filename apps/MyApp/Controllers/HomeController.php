<?php

namespace MyApp\Controllers;

use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ViewEngineInterface;

class HomeController
{
    /**
     * @inject
     */
    private ViewEngineInterface $view;

    public function index(Request $request): Response
    {
        return $this->view->render(view: 'home.index', layout: 'base');
    }
}
