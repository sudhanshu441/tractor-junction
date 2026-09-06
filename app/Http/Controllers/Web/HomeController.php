<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\State;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Phase 2 fills in the catalogue blocks; phase 1 proves the shell and masters.
        return view('web.home', [
            'brands' => Brand::active()->popular()->orderBy('sort_order')->take(12)->get(),
            'states' => State::active()->orderBy('name')->get(),
        ]);
    }
}
