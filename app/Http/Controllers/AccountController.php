<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['room', 'payment', 'accessCode'])
            ->latest('starts_at')
            ->paginate(10);

        return view('account.dashboard', compact('bookings'));
    }
}
