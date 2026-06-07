<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sweepstakes = $request->user()
            ->sweepstakes()
            ->withCount(['members', 'paidMembers', 'assignments'])
            ->latest()
            ->get();

        return view('dashboard', [
            'sweepstakes' => $sweepstakes,
            'totals' => [
                'sweepstakes' => $sweepstakes->count(),
                'paidMembers' => $sweepstakes->sum('paid_members_count'),
                'assignments' => $sweepstakes->sum('assignments_count'),
            ],
        ]);
    }
}
