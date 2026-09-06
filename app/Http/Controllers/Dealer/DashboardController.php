<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dealer = $request->user()->ownedDealer()->with('brands', 'branches')->first();

        return view('dealer.dashboard', ['dealer' => $dealer]);
    }
}
