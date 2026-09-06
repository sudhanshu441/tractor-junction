<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\City;
use App\Models\District;
use App\Models\State;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'customers' => User::customers()->count(),
                'staff' => User::staff()->count(),
                'brands' => Brand::count(),
                'states' => State::count(),
                'districts' => District::count(),
                'cities' => City::count(),
            ],
            'recentUsers' => User::latest()->take(8)->get(),
            'recentActivity' => Activity::with('causer')->latest()->take(10)->get(),
        ]);
    }
}
