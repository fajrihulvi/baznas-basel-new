<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\HomeService;
use App\Services\SiteService;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('site/home', HomeService::get() + ['pilar' => SiteService::pilar()]);
    }
}
