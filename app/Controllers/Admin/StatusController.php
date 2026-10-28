<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\StatusSistemService;

/** /admin/status — kesiapan server (PHP, ekstensi, HTTPS, folder, migrasi, error log). */
final class StatusController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/status', ['grup' => StatusSistemService::periksa($request)]);
    }
}
