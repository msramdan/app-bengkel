<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }
}
