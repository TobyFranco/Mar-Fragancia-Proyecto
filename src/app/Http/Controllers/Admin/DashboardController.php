<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetricasAdmin;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MetricasAdmin $metricas): View
    {
        return view('admin.dashboard', $metricas->resumen());
    }
}
